<?php
// search.php (Đặt trong thư mục public/)

// BASE_PATH trỏ lên thư mục project/
define('BASE_PATH', dirname(__DIR__)); 
require_once BASE_PATH . '/core/helpers.php';

// 1. "DANH BẠ" CÁC TRANG WEB
$site_registry = [
    //'laptopxachtay' => 'getLaptopXachTayProducts', 
    //'nguyencongpc'  => 'getNguyenCongPCProducts', 
    //'vodien'        => 'GetVoDienProducts',
    //'laptops'      => 'getLaptopsProducts',
    //'baohanhtrondoi' => 'getBaoHanhTronDoiProducts',
    //'laptopAZ'     => 'getLaptopAZProducts',
    //'svstore'      => 'getSVStoreProducts',
    //'phuccanh'     => 'getPhucAnhProducts',
    'tinhocngoisao' => 'getTinhHocNgoiSaoProducts',
];

// 2. Lấy từ khóa
$query = trim($_GET['q'] ?? '');
if (!$query) {
    die("Vui lòng nhập từ khóa tìm kiếm!");
}

echo "<h2>Kết quả tìm kiếm cho: <em>$query</em></h2>";

// 3. Vòng lặp "DANH BẠ" để lấy dữ liệu
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

// 4. Lọc và Sắp xếp
if (empty($allResults)) {
    echo "<p>Không tìm thấy sản phẩm phù hợp (lỗi cURL hoặc Regex).</p>";
    exit;
}

// *** SỬA LỖI LỌC TẠI ĐÂY ***
// Thay vì dùng fuzzy_match, chúng ta dùng stripos
// stripos sẽ kiểm tra xem $query có NẰM TRONG $item['name'] hay không
// ================== LỌC THÔNG MINH (TÌM THEO TỪ KHÓA LINH HOẠT) ==================
$filtered = [];
$norm_query = normalize_text($query);
$query_words = array_filter(explode(' ', $norm_query));

foreach ($allResults as $item) {
    $norm_name = normalize_text($item['name']);

    $match_count = 0;
    foreach ($query_words as $word) {
        if (strlen($word) > 1 && str_contains($norm_name, $word)) {
            $match_count++;
        }
    }

    // Tính tỷ lệ trùng
    $word_ratio = $match_count / max(1, count($query_words));

    // Tính % tương đồng tổng thể (chống trường hợp sai chính tả nhẹ)
    similar_text($norm_query, $norm_name, $percent);

    // Nếu khớp >= 60% từ khóa hoặc tương đồng tổng thể > 65%
    if ($word_ratio >= 0.6 || $percent >= 65) {
        $item['match_score'] = max($word_ratio * 100, $percent);
        $filtered[] = $item;
    }
}

// Sắp xếp theo điểm khớp giảm dần trước, rồi theo giá tăng dần
usort($filtered, function($a, $b) {
    $scoreA = $a['match_score'] ?? 0;
    $scoreB = $b['match_score'] ?? 0;
    if ($scoreA === $scoreB) {
        return $a['price'] <=> $b['price'];
    }
    return $scoreB <=> $scoreA;
});
// ================== KẾT THÚC LỌC ==================



// Sắp xếp theo giá tăng dần
usort($filtered, fn($a, $b) => $a['price'] <=> $b['price']);

if (empty($filtered)) {
    // Nếu $allResults có sản phẩm, nhưng $filtered rỗng,
    // có nghĩa là bộ lọc đã loại bỏ hết
    echo "<p>Đã tìm thấy ". count($allResults) ." sản phẩm, nhưng không có sản phẩm nào khớp với từ khóa <em>'$query'</em>.</p>";
    exit;
}

// 5. In kết quả
echo "<p>Tìm thấy ". count($filtered) ." sản phẩm khớp:</p>";
echo "<table border='1' cellpadding='8' cellspacing='0'>
        <tr style='background:#eee'>
          <th>Trang</th>
          <th>Tên sản phẩm</th>
          <th>Giá</th>
          <th>Link</th>
        </tr>";

foreach ($filtered as $r) {
    echo "<tr>
            <td>{$r['site']}</td>
            <td>{$r['name']}</td>
            <td>" . format_price($r['price']) . "</td>
            <td><a href='{$r['url']}' target='_blank'>Xem</a></td>
          </tr>";
}

echo "</table>";
echo "<br><a href='index.php'>← Quay lại</a>";

?>