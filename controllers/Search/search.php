<?php
session_start();
define('BASE_PATH', dirname(dirname(__DIR__))); 
require_once BASE_PATH . '/core/helpers.php';
require_once __DIR__ . '/Tag_search.php'; 

/* ======================  BOOKMARK SESSION  ====================== */
$user_id = $_SESSION['user_id'] ?? null;
$user_bookmarked_urls = [];

if ($user_id) {
    require_once BASE_PATH . '/models/product/BookmarkModel.php';
    $bm = new BookmarkModel();
    $user_bookmarked_urls = $bm->getsearchBookmarksByUser($user_id);
    $user_bookmarked_urls = array_values(array_filter(array_map('trim', $user_bookmarked_urls)));
}

/* ======================  REGISTER SITE  ====================== */
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

/* ======================  GET INPUT  ====================== */
$query = trim($_GET['q'] ?? '');

/* ======================  LƯU LỊCH SỬ TÌM KIẾM  ====================== */
// Lưu lịch sử ngay khi người dùng bấm tìm (không kiểm tra gì)
require_once BASE_PATH . '/models/product/SearchHistoryModel.php';
$shModel = new SearchHistoryModel();
$product_name = mb_substr($query, 0, 255);
$shModel->saveSearch($user_id ?? null, $product_name);

if (!$query) die("Vui lòng nhập từ khóa tìm kiếm!");

// Lấy các tham số từ Form lọc mới
$filter_ram         = $_GET['ram'] ?? '';
$filter_cpu         = $_GET['cpu'] ?? '';
$filter_gpu         = $_GET['gpu'] ?? '';
$filter_storage     = $_GET['storage'] ?? '';
$filter_price_range = $_GET['price_range'] ?? ''; // Mới: 10000000-15000000
$filter_screen      = $_GET['screen'] ?? '';      // Mới: 13-14 hoặc 15-16
$sort_price         = $_GET['sort'] ?? '';

/* ======================  GET DATA FROM SITES  ====================== */
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

/* ======================  SMART FILTER (REGEX PARSING)  ====================== */
$filtered = [];
if (empty($error_message)) {
    $norm_query = normalize_text($query); 
    $query_words = array_filter(explode(' ', $norm_query));

    foreach ($allResults as $item) {
        $norm_name = normalize_text($item['name']);
        
        if (!is_laptop($norm_name)) continue; // Chỉ lấy laptop
        if (is_accessory($item['name'])) continue; // ← Thêm dòng này để loại phụ kiện
        
        $match_count = 0;
        foreach ($query_words as $word) {
            if (strlen($word) > 1 && str_contains($norm_name, $word)) {
                $match_count++;
            }
        }

        $word_ratio = $match_count / max(1, count($query_words));
        similar_text($norm_query, $norm_name, $percent);

        if ($word_ratio >= 0.6 || $percent >= 65) {
            // Bóc tách thông số kỹ thuật
            $spec = parse_laptop_specs($item['name']);
            
            $item['cpu']     = $spec['cpu'];
            $item['ram']     = $spec['ram'];
            $item['gpu']     = $spec['gpu'];
            $item['storage'] = $spec['storage'];
            $item['display'] = $spec['display']; // Regex phải lấy được chuỗi (VD: 15.6 inch)
            
            $item['match_score'] = max($word_ratio * 100, $percent);
            $filtered[] = $item;
        }
    }
}

/* ======================  APPLY FILTERS (LOGIC NÂNG CAO)  ====================== */
if (!empty($filtered)) {
    
    $filtered = array_filter($filtered, function($item) use ($filter_ram, $filter_cpu, $filter_gpu, $filter_storage, $filter_price_range, $filter_screen) {
        
        // 1. Lọc CPU (So sánh tương đối)
        // VD: Filter chọn "i5" sẽ khớp với "Core i5-12400H"
        if ($filter_cpu && stripos($item['cpu'], $filter_cpu) === false) return false;

        // 2. Lọc RAM
        if ($filter_ram && stripos($item['ram'], $filter_ram) === false) return false;

        // 3. Lọc Storage (Xử lý map 1024GB = 1TB)
        if ($filter_storage) {
            $search_key = $filter_storage;
            if ($filter_storage == '1024') $search_key = '1TB'; 
            if ($filter_storage == '2048') $search_key = '2TB';
            
            if (stripos($item['storage'], $search_key) === false) return false;
        }

        // 4. Lọc GPU (Xử lý theo nhóm)
        if ($filter_gpu) {
            $gpu_name = strtoupper($item['gpu']);
            
            if ($filter_gpu === 'onboard') {
                // Nếu chọn Onboard: Phải không có RTX, GTX, RX và có Intel/UHD/Iris
                if (strpos($gpu_name, 'RTX') !== false || strpos($gpu_name, 'GTX') !== false || strpos($gpu_name, 'RX') !== false) return false;
            } 
            elseif ($filter_gpu === 'nvidia_rtx') {
                if (strpos($gpu_name, 'RTX') === false) return false;
            }
            elseif ($filter_gpu === 'nvidia_gtx') {
                if (strpos($gpu_name, 'GTX') === false) return false;
            }
            elseif ($filter_gpu === 'amd_rx') {
                if (strpos($gpu_name, 'RX') === false) return false;
            }
            // Các trường hợp khác (VD: NVIDIA chung chung)
            elseif (stripos($gpu_name, $filter_gpu) === false) return false;
        }

        // 5. Lọc Giá Tiền (Min - Max)
        if ($filter_price_range) {
            $price = (float)$item['price']; 
            // Giá trị input dạng "10000000-15000000"
            $range = explode('-', $filter_price_range);
            $min = (float)$range[0];
            $max = (float)$range[1];

            if ($price < $min || $price > $max) return false;
        }

        // 6. Lọc Màn hình (Xử lý số thực từ chuỗi)
        if ($filter_screen) {
            // Lấy số từ chuỗi display (VD: "15.6 inch" -> 15.6)
            if (preg_match('/(\d+(\.\d+)?)/', $item['display'], $m)) {
                $screen_size = (float)$m[1];
                
                $range = explode('-', $filter_screen);
                
                // Nếu range có dấu gạch (13-14)
                if (count($range) == 2) {
                    if ($screen_size < $range[0] || $screen_size > $range[1]) return false;
                } 
                // Nếu range là số đơn (VD: 17 -> Nghĩa là >= 17)
                else {
                    if ($screen_size < (float)$filter_screen) return false;
                }
            } else {
                // Nếu không parse được màn hình mà user lại filter màn hình -> Bỏ qua hoặc giữ lại tùy logic
                // Ở đây tôi chọn return false để đảm bảo chính xác
                return false;
            }
        }

        return true;
    });
}

/* ======================  SORT  ====================== */
if (!empty($filtered)) {
    if ($sort_price === 'asc') {
        usort($filtered, fn($a, $b) => $a['price'] <=> $b['price']);
    }
    else if ($sort_price === 'desc') {
        usort($filtered, fn($a, $b) => $b['price'] <=> $a['price']);
    }
}

/* ======================  CHECK EMPTY (AFTER FILTER)  ====================== */
if (empty($filtered) && empty($error_message)) {
    $error_message = "Không có sản phẩm nào khớp với bộ lọc.";
}

/* ======================  LOAD VIEW  ====================== */
require_once __DIR__ . '/search_view.php';

?>