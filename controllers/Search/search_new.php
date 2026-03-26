<?php
// controllers/Search/SearchController.php

session_start();
define('BASE_PATH', dirname(dirname(__DIR__))); 
require_once BASE_PATH . '/core/helpers.php';
require_once __DIR__ . '/Tag_search.php'; 
require_once BASE_PATH . '/config/connetdata.php';

/* =================================================================================
   PHẦN 1: CẤU HÌNH & LỊCH SỬ
   ================================================================================= */
$user_id = $_SESSION['user_id'] ?? null;
$user_bookmarked_urls = [];

if ($user_id) {
    require_once BASE_PATH . '/models/product/BookmarkModel.php';
    $bm = new BookmarkModel();
    $user_bookmarked_urls = $bm->getsearchBookmarksByUser($user_id);
    $user_bookmarked_urls = array_values(array_filter(array_map('trim', $user_bookmarked_urls)));
}

// Ưu tiên lấy 'products', nếu không có thì lấy 'keyword'
$query = trim($_GET['products'] ?? $_GET['keyword'] ?? '');

if ($query) {
    // 1. LƯU COOKIE (Để hiển thị lại cho khách xem ngay trên trình duyệt)
    // Lưu trong 30 ngày, dấu '/' để dùng được toàn trang web
    setcookie('last_guest_search', $query, time() + (86400 * 30), "/");
    setcookie('last_guest_time', date("Y-m-d H:i:s"), time() + (86400 * 30), "/");

    // 2. LƯU VÀO DATABASE (Để Admin thống kê xu hướng)
    require_once BASE_PATH . '/models/product/SearchHistoryModel.php';
    $shModel = new SearchHistoryModel();
    
    // Nếu $user_id là null (khách), hệ thống vẫn lưu dòng này với user_id = NULL
    $shModel->saveSearch($user_id ?? null, mb_substr($query, 0, 255));
    
} else {
    // Nếu không nhập gì thì báo lỗi hoặc quay lại
    echo "<script>alert('Vui lòng nhập từ khóa!'); window.history.back();</script>";
    exit();
}

/* =================================================================================
   PHẦN 2: LIVE CRAWLING
   ================================================================================= */
$site_registry = [
    //'phucanh' => 'getPhucAnhProducts',                 // lỗi không Regex được
    //'svnstore' => 'getSVNStoreProducts',               // lỗi không Regex được
    'laptops' => 'getLaptopsProducts',                   //Regex thành công
    'laptopaz' => 'getLaptopAZProducts',                 //Regex thành công
    'baohanhtrondoi' => 'getBaoHanhTronDoiProducts',     //Regex thành công
    'laptopxachtay' => 'getLaptopXachTayProducts',       //Regex thành công
    'nguyencongpc' => 'getNguyenCongPCProducts',         //Regex thành công
    'vodien' => 'getVoDienProducts',
    'laptoptld' => 'getLaptopTLDProducts',               //regex thành công    
    'laptopnew' => 'getLaptopNewProducts',               //regex thành công
    'anphatpc' => 'getAnPhatPCProducts',                 //lỗi không regex dược
];

$liveResults = []; 

foreach ($site_registry as $site_key => $function_name) {
    $site_file = BASE_PATH . "/sites/{$site_key}.php";
    if (file_exists($site_file)) {
        require_once $site_file;
        if (function_exists($function_name)) {
            $results = $function_name($query);
            if (is_array($results)) {
                $liveResults = array_merge($liveResults, $results);
            }
        }
    }
}

/* =================================================================================
   PHẦN 3: MERGE & KHỬ TRÙNG TUYỆT ĐỐI (FIX LỖI NHÂN ĐÔI)
   ================================================================================= */
require_once BASE_PATH . '/models/product/ProductMasterModel.php';
$productModel = new ProductMasterModel();

// 3.1: Lưu dữ liệu LIVE vào DB (Giữ nguyên logic cũ)
if (!empty($liveResults)) {
    foreach ($liveResults as &$item) {
        $specs_from_name = parse_laptop_specs($item['name']);
        if (empty($item['cpu'])) $item['cpu'] = $specs_from_name['cpu'];
        if (empty($item['ram'])) $item['ram'] = $specs_from_name['ram'];
        if (empty($item['hdd']) && empty($item['storage'])) $item['storage'] = $specs_from_name['storage'];
        if (empty($item['vga'])) $item['vga'] = $specs_from_name['gpu'];
        if (empty($item['screen'])) $item['screen'] = $specs_from_name['display'];
        if (!empty($item['hdd']) && empty($item['storage'])) $item['storage'] = $item['hdd'];
        $item['image_url'] = $item['image'] ?? $item['image_url'] ?? '';
        $productModel->insertOrUpdate($item);
    }
    unset($item);
}

// 3.2: Lấy dữ liệu từ Database
$dbResults = $productModel->searchProducts($query, 100);

// 3.3: GỘP VÀ KHỬ TRÙNG (LOGIC MỚI: DÙNG MẢNG KẾT HỢP)
$unique_map = []; // Mảng tạm dùng URL làm key để chống trùng

// A. Ưu tiên duyệt Live Results trước
foreach ($liveResults as $liveItem) {
    if (empty($liveItem['name']) || empty($liveItem['url'])) continue;
    if (!isset($liveItem['price']) || $liveItem['price'] <= 0) continue;

    // Chuẩn hóa URL để làm Key (Xóa khoảng trắng, xóa dấu / ở cuối nếu có)
    $url_key = rtrim(trim($liveItem['url']), '/');
    
    // Gán vào map
    $unique_map[$url_key] = $liveItem;
}

// B. Bổ sung từ DB (Chỉ lấy nếu URL chưa tồn tại trong map)
foreach ($dbResults as $dbItem) {
    if (empty($dbItem['url'])) continue;
    
    $url_key = rtrim(trim($dbItem['url']), '/');

    // Nếu key này chưa có trong danh sách Live -> Thêm vào
    if (!isset($unique_map[$url_key])) {
        
        // Fix lỗi hiển thị ảnh từ DB
        if (empty($dbItem['image']) && !empty($dbItem['image_url'])) {
            $dbItem['image'] = $dbItem['image_url'];
        }
        
        // Parse lại thông số nếu thiếu
        if (empty($dbItem['cpu'])) { 
             $s = parse_laptop_specs($dbItem['name']);
             $dbItem['cpu'] = $s['cpu']; $dbItem['ram'] = $s['ram']; 
             $dbItem['storage'] = $s['storage']; $dbItem['display'] = $s['display'];
        }
        
        if (empty($dbItem['site'])) {
            $dbItem['site'] = $dbItem['source_site'] ?? 'Unknown';
        }

        $unique_map[$url_key] = $dbItem;
    }
}

// Chuyển mảng kết hợp về mảng tuần tự bình thường
$allResults = array_values($unique_map);
/* =================================================================================
   PHẦN 4: CHUẨN HÓA DỮ LIỆU
   ================================================================================= */
foreach ($allResults as &$item) {
    $item['name'] = html_entity_decode($item['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    if (empty($item['cpu'])) {
        $specs = parse_laptop_specs($item['name']);
        $item['cpu'] = $specs['cpu']; $item['ram'] = $specs['ram']; 
        $item['storage'] = $specs['storage']; $item['vga'] = $specs['gpu']; $item['screen'] = $specs['display'];
    }

    // Đồng bộ key cho View
    if (!isset($item['gpu']))     $item['gpu']     = $item['vga'] ?? ''; 
    if (!isset($item['display'])) $item['display'] = $item['screen'] ?? '';
    if (!isset($item['price']))   $item['price']   = 0; // Tránh lỗi undefined

    $item['grouping_name'] = clean_product_name($item['name']); 
}
unset($item);

$error_message = '';
if (empty($allResults)) {
    $error_message = "Không tìm thấy laptop.";
}

/* =================================================================================
   PHẦN 5: LỌC SƠ BỘ (ĐÃ NÂNG CẤP: BRAND FILTER + NUMBER CHECK)
   ================================================================================= */
$filtered = [];
if (empty($error_message)) {
    $norm_query = normalize_text($query); 
    $query_words = array_filter(explode(' ', $norm_query));

    // [MỚI] B1. Xác định Thương hiệu mục tiêu (Target Brand)
    // Nếu người dùng gõ "Dell XPS 13", target_brand sẽ là "dell".
    // Nếu người dùng chỉ gõ "Laptop giá rẻ", target_brand sẽ là null.
    $known_brands = ['dell', 'hp', 'acer', 'asus', 'lenovo', 'msi', 'apple', 'macbook', 'surface', 'lg', 'gigabyte', 'samsung', 'toshiba', 'fujitsu', 'huawei', 'xiaomi'];
    $target_brand = null;
    foreach ($known_brands as $brand) {
        if (str_contains($norm_query, $brand)) {
            $target_brand = $brand;
            // Nếu là macbook thì coi như là apple
            if ($brand === 'macbook') $target_brand = 'apple';
            break; 
        }
    }

    // [MỚI] B2. Tách các con số trong từ khóa tìm kiếm (Ví dụ: 14, 15, 3050)
    preg_match_all('/\d+/', $norm_query, $q_nums);
    $query_numbers = $q_nums[0] ?? [];

    foreach ($allResults as $item) {
        $norm_name = normalize_text($item['name']);
        
        // 1. Kiểm tra Laptop/Phụ kiện
        $is_valid_item = false;
        if (function_exists('is_laptop') && is_laptop($norm_name)) {
            $is_valid_item = true;
        } 
        if (!$is_valid_item && preg_match('/(dell|hp|acer|asus|lenovo|macbook|msi|surface|thinkpad|lg gram|gigabyte)/i', $norm_name)) {
            $is_valid_item = true;
        }
        if (!$is_valid_item) continue; 

        $has_specs = !empty($item['cpu']) || !empty($item['vga']); 
        if (!$has_specs && function_exists('is_accessory') && is_accessory($item['name'])) {
            continue; 
        }

        // --- [QUAN TRỌNG] BỘ LỌC THƯƠNG HIỆU (STRICT BRAND FILTER) ---
        // Nếu người dùng đã tìm đích danh "Dell", thì máy "HP", "Asus" phải bị loại ngay.
        if ($target_brand) {
            // Kiểm tra tên sản phẩm có chứa thương hiệu đó không
            $item_brand_found = false;
            
            // Case đặc biệt cho Apple/Macbook
            if ($target_brand === 'apple' && (str_contains($norm_name, 'macbook') || str_contains($norm_name, 'apple'))) {
                $item_brand_found = true;
            } 
            // Case thông thường
            elseif (str_contains($norm_name, $target_brand)) {
                $item_brand_found = true;
            }

            // Nếu không tìm thấy thương hiệu trong tên sp -> BỎ QUA LUÔN
            if (!$item_brand_found) continue;
        }
        // -----------------------------------------------------------

        //  B3. BỘ LỌC SỐ HỌC (STRICT NUMBER CHECK)
        // Nếu User tìm "14", máy "15" sẽ bị loại ngay lập tức tại đây.
        if (!empty($query_numbers)) {
            $missing_number = false;
            // Tách số trong tên sản phẩm
            preg_match_all('/\d+/', $norm_name, $n_nums);
            $name_numbers = $n_nums[0] ?? [];

            foreach ($query_numbers as $q_num) {
                // Logic: Nếu số tìm kiếm không xuất hiện độc lập trong mảng số của tên
                // VÀ cũng không nằm trong chuỗi tên (đề phòng trường hợp dính chữ như G15)
                if (!in_array($q_num, $name_numbers) && !str_contains($norm_name, $q_num)) {
                    $missing_number = true;
                    break;
                }
            }
            // Nếu thiếu số quan trọng -> Bỏ qua sản phẩm này luôn
            if ($missing_number) continue;
        }

        // 4. Kiểm tra độ khớp từ khóa (Fuzzy Matching)
        $match_count = 0;
        foreach ($query_words as $word) {
            if (strlen($word) > 1 && str_contains($norm_name, $word)) {
                $match_count++;
            }
        }
        
        $word_ratio = $match_count / max(1, count($query_words));
        similar_text($norm_query, $norm_name, $percent);

        // Nới lỏng điều kiện similar_text một chút vì đã có bộ lọc Brand và Số bảo kê rồi
        if ($word_ratio >= 0.5 || $percent >= 30) {
            $filtered[] = $item;
        }
    }
}

/* =================================================================================
   PHẦN 6: LỌC GIÁ
   ================================================================================= */
$filter_price_range = $_GET['price_range'] ?? '';
if (!empty($filtered) && $filter_price_range) {
    $range = explode('-', $filter_price_range);
    if (count($range) == 2) {
        $min_p = (float)$range[0];
        $max_p = (float)$range[1];
        $filtered = array_filter($filtered, fn($item) => $item['price'] >= $min_p && $item['price'] <= $max_p);
    }
}

/* =================================================================================
   PHẦN 7: GOM NHÓM 
   ================================================================================= */
$grouped_products = [];
if (!empty($filtered)) {
    foreach ($filtered as $item) {
        // Lấy tên đã làm sạch từ Tag_search.php
        $current_clean = $item['grouping_name']; 
        
        // Nếu tên quá ngắn, dùng tên gốc viết thường
        if (mb_strlen($current_clean) < 3) $current_clean = mb_strtolower($item['name']);

        // Lấy danh sách số trong tên đã làm sạch (Chỉ còn số hiệu máy: 506, 15...)
        preg_match_all('/\d+/', $current_clean, $m_curr);
        $curr_nums = $m_curr[0] ?? [];

        $found_key = null;
        
        foreach ($grouped_products as $key => $group) {
            $exist_item = $group['sellers'][0];
            $exist_clean = $exist_item['grouping_name'];
            
            if (mb_strlen($exist_clean) < 3) $exist_clean = mb_strtolower($exist_item['name']);


            // 1. ƯU TIÊN KHỚP CHÍNH XÁC 100% (CASE SENSITIVE)
            // Nếu clean_product_name đã chuẩn (FX506HC vs FX506HC), gộp luôn không cần check số
            if ($current_clean === $exist_clean) {
                $found_key = $key;
                break;
            }

            // 2. CHECK SỐ (Nếu tên không giống hệt, kiểm tra xem có lệch đời máy không)
            preg_match_all('/\d+/', $exist_clean, $m_exist);
            $exist_nums = $m_exist[0] ?? [];

            // Nếu tập hợp số khác nhau (VD: 506 vs 516), bỏ qua ngay
            if (!empty(array_diff($curr_nums, $exist_nums)) || !empty(array_diff($exist_nums, $curr_nums))) {
                continue; 
            }

            // 3. CHECK SIMILARITY (CHỈ DÙNG KHI SỐ ĐÃ GIỐNG HỆT)
            // Tăng độ khó lên 98% để tránh gộp nhầm HC và HE (chỉ khác 1 chữ cái)
            // HC và HE độ giống nhau khoảng 96%, nên đặt 98% sẽ tách được chúng ra.
            similar_text($current_clean, $exist_clean, $percent);
            if ($percent >= 98) { 
                $found_key = $key; 
                break; 
            }
        }

        // --- PHẦN THÊM VÀO MẢNG (GIỮ NGUYÊN) ---
        if ($found_key) {
            $grouped_products[$found_key]['sellers'][] = $item;
            // Cập nhật min/max price
            if ($item['price'] < $grouped_products[$found_key]['min_price']) 
                $grouped_products[$found_key]['min_price'] = $item['price'];
            if ($item['price'] > $grouped_products[$found_key]['max_price']) 
                $grouped_products[$found_key]['max_price'] = $item['price'];
        } else {
            // Tạo nhóm mới
            $new_key = md5($item['url'] . rand(0,99999));
            
            // Tên hiển thị ưu tiên grouping_name đã làm đẹp
            $disp_name = $item['grouping_name']; 
            
            $img = $item['image'] ?? ''; 

            $grouped_products[$new_key] = [
                'display_name' => $disp_name,
                'min_price'    => $item['price'],
                'max_price'    => $item['price'],
                'image'        => $img,
                'specs'        => [
                    'cpu' => $item['cpu'], 
                    'ram' => $item['ram'], 
                    'gpu' => $item['gpu'],
                    'screen' => $item['display']
                ],
                'sellers'      => [$item]
            ];
        }
    }
}
/* =================================================================================
   PHẦN 8: HIỂN THỊ
   ================================================================================= */
$min_shops = (int)($_GET['num_shops'] ?? 0);
if ($min_shops > 1 && !empty($grouped_products)) {
    $grouped_products = array_filter($grouped_products, function($g) use ($min_shops) {
        return count(array_unique(array_column($g['sellers'], 'site'))) >= $min_shops;
    });
}

$sort = $_GET['sort'] ?? '';
if ($sort === 'increase' || $sort === 'asc') {
    uasort($grouped_products, fn($a, $b) => $a['min_price'] <=> $b['min_price']);
} elseif ($sort === 'decreased' || $sort === 'desc') {
    uasort($grouped_products, fn($a, $b) => $b['min_price'] <=> $a['min_price']);
}

if (empty($grouped_products) && empty($error_message)) {
    $error_message = "Không có sản phẩm nào khớp với bộ lọc.";
}

require_once __DIR__ . '/search_view_new.php'; 
?>