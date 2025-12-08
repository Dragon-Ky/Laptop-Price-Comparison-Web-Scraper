<?php
session_start();
define('BASE_PATH', dirname(dirname(__DIR__))); 
require_once BASE_PATH . '/core/helpers.php';
require_once __DIR__ . '/Tag_search.php'; 

/* ======================  CONFIG & HISTORY  ====================== */
$user_id = $_SESSION['user_id'] ?? null;
$user_bookmarked_urls = [];

// Lấy bookmark
if ($user_id) {
    require_once BASE_PATH . '/models/product/BookmarkModel.php';
    $bm = new BookmarkModel();
    $user_bookmarked_urls = $bm->getsearchBookmarksByUser($user_id);
    $user_bookmarked_urls = array_values(array_filter(array_map('trim', $user_bookmarked_urls)));
}

// Lưu lịch sử tìm kiếm
$query = trim($_GET['products'] ?? '');
require_once BASE_PATH . '/models/product/SearchHistoryModel.php';
$shModel = new SearchHistoryModel();
$shModel->saveSearch($user_id ?? null, mb_substr($query, 0, 255));

if (!$query) die("Vui lòng nhập từ khóa tìm kiếm!");

/* ======================  LẤY DỮ LIỆU TỪ CÁC SITE  ====================== */
$site_registry = [
    'baohanhtrondoi' => 'getBaoHanhTrongDoiProducts',
    'laptopAZ'       => 'getLaptopAZProducts',
    'laptops'        => 'getLaptopsProducts',
    'laptopxachtay'  => 'getLaptopXachTayProducts',
    'nguyencongpc'   => 'getNguyenCongPCProducts',
    'phuccanh'       => 'getPhucAnhProducts',
    'svstore'        => 'getSVStoreProducts',
    'vodien'         => 'getVoDienProducts',
];

// Lấy tham số filter
$filter_price_range = $_GET['price_range'] ?? '';
$filter_num_shops   = $_GET['num_shops'] ?? '';
$sort_price         = $_GET['sort'] ?? '';

// Chạy vòng lặp lấy dữ liệu
$allResults = [];
foreach ($site_registry as $site_key => $function_name) {
    $site_file = BASE_PATH . "/sites/{$site_key}.php";
    if (file_exists($site_file)) {
        require_once $site_file;
        if (function_exists($function_name)) {
            $results = $function_name($query);
            $allResults = array_merge($allResults, $results);
        }
    }
}

$error_message = '';
if (empty($allResults)) {
    $error_message = "Không tìm thấy sản phẩm phù hợp.";
}

/* ======================  LỌC SƠ BỘ (PRE-FILTER)  ====================== */
// Bước này chỉ để loại bỏ các kết quả rác không liên quan đến từ khóa
$filtered = [];
if (empty($error_message)) {
    $norm_query = normalize_text($query); 
    $query_words = array_filter(explode(' ', $norm_query));

    foreach ($allResults as $item) {
        $norm_name = normalize_text($item['name']);
        
        if (!is_laptop($norm_name)) continue; 
        if (is_accessory($item['name'])) continue; 
        
        $match_count = 0;
        foreach ($query_words as $word) {
            if (strlen($word) > 1 && str_contains($norm_name, $word)) {
                $match_count++;
            }
        }
        $word_ratio = $match_count / max(1, count($query_words));
        similar_text($norm_query, $norm_name, $percent);

        if ($word_ratio >= 0.6 || $percent >= 65) {
            // Vẫn phân tích thông số để hiển thị cho đẹp
            $spec = parse_laptop_specs($item['name']);
            $item['cpu']     = $spec['cpu'];
            $item['ram']     = $spec['ram'];
            $item['gpu']     = $spec['gpu'];
            $item['storage'] = $spec['storage'];
            $item['display'] = $spec['display'];
            
            $filtered[] = $item;
        }
    }
}

/* ======================  LỌC THEO GIÁ (NẾU CÓ)  ====================== */
if (!empty($filtered) && $filter_price_range) {
    $filtered = array_filter($filtered, function($item) use ($filter_price_range) {
        $price = (float)$item['price']; 
        $range = explode('-', $filter_price_range);
        $min = (float)$range[0];
        $max = (float)$range[1];
        return ($price >= $min && $price <= $max);
    });
}

/* ======================  LOGIC GOM NHÓM THÔNG MINH (FUZZY MATCHING)  ====================== */
$grouped_products = [];

if (!empty($filtered)) {
    foreach ($filtered as $item) {
        
        // 1. Vẫn dùng hàm clean của bạn để làm sạch rác trước
        $base_name = function_exists('clean_product_name') ? clean_product_name($item['name']) : $item['name'];
        if (empty($base_name)) $base_name = $item['name'];
        
        // Chuyển về chữ thường để so sánh
        $current_name_lower = mb_strtolower($base_name);

        $found_group_key = null;

        // 2. DUYỆT QUA CÁC NHÓM ĐÃ CÓ ĐỂ TÌM "ANH EM"
        foreach ($grouped_products as $key => $group) {
            $existing_name_lower = mb_strtolower($group['display_name']);

            // Dùng hàm similar_text của PHP
            similar_text($current_name_lower, $existing_name_lower, $percent);

            // 3. ĐIỀU KIỆN GOM NHÓM:
            // - Nếu giống nhau trên 90%
            // - HOẶC: Nếu một chuỗi chứa trọn vẹn chuỗi kia (str_contains)
            if ($percent > 90 || str_contains($existing_name_lower, $current_name_lower) || str_contains($current_name_lower, $existing_name_lower)) {
                $found_group_key = $key;
                break; // Tìm thấy rồi thì dừng, không tìm nữa
            }
        }

        // 4. XỬ LÝ GOM HOẶC TẠO MỚI
        if ($found_group_key) {
            // A. Đã tìm thấy nhóm tương đồng -> Đẩy vào
            $grouped_products[$found_group_key]['sellers'][] = $item;
            
            // Cập nhật giá Min/Max
            if ($item['price'] < $grouped_products[$found_group_key]['min_price']) {
                $grouped_products[$found_group_key]['min_price'] = $item['price'];
            }
            if ($item['price'] > $grouped_products[$found_group_key]['max_price']) {
                $grouped_products[$found_group_key]['max_price'] = $item['price'];
            }
        } else {
            // B. Không thấy ai giống mình -> Tạo nhóm mới
            $group_key = md5($current_name_lower);
            
            // [SỬA ĐỔI] Chuyển đổi tên hiển thị sang dạng In Hoa Chữ Cái Đầu (Title Case)
            // Ví dụ: "dell xps 13" -> "Dell Xps 13"
            $formatted_name = mb_convert_case($base_name, MB_CASE_TITLE, "UTF-8");

            $grouped_products[$group_key] = [
                'display_name' => $formatted_name, 
                'min_price' => $item['price'],
                'max_price' => $item['price'],
                'sellers' => [$item]
            ];
        }
    }
}
/* ======================  LỌC SỐ LƯỢNG SHOP (CÁCH 2 - UNIQUE) ====================== */
// Lọc những nhóm nào có ít shop bán quá (nếu user yêu cầu)
$min_shops = (int)$filter_num_shops;

if ($min_shops > 1 && !empty($grouped_products)) {
    $grouped_products = array_filter($grouped_products, function($group) use ($min_shops) {
        // Lấy danh sách tên site (Vodien, FPT...)
        $list_site_names = array_column($group['sellers'], 'site');
        // Loại bỏ trùng lặp (Ví dụ Vodien xuất hiện 3 lần thì chỉ tính là 1 shop)
        $unique_sites = array_unique($list_site_names);
        
        return count($unique_sites) >= $min_shops;
    });
}

/* ======================  SẮP XẾP KẾT QUẢ  ====================== */
if (!empty($grouped_products)) {
    if ($sort_price === 'increase' || $sort_price === 'asc') {
        uasort($grouped_products, fn($a, $b) => $a['min_price'] <=> $b['min_price']);
    } elseif ($sort_price === 'decreased' || $sort_price === 'desc') {
        uasort($grouped_products, fn($a, $b) => $b['min_price'] <=> $a['min_price']);
    }
}

if (empty($grouped_products) && empty($error_message)) {
    $error_message = "Không có sản phẩm nào khớp với bộ lọc.";
}

/* ======================  HIỂN THỊ VIEW  ====================== */
require_once __DIR__ . '/search_view_new.php'; 

?>