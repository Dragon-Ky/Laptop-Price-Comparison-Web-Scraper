<?php
// ======================================================================
// TEST SCRAPER CHO TIN HỌC NGÔI SAO - CẢI TIẾN DEBUG
// ======================================================================

// --- 1. Thiết lập môi trường ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Định nghĩa BASE_PATH trỏ lên thư mục project (giống search.php)
define('BASE_PATH', dirname(__DIR__)); 

// Tải helper & file scraper
// Giả định các file này đã được định nghĩa đúng đường dẫn
require_once BASE_PATH . '/core/helpers.php';
require_once BASE_PATH . '/sites/phuccanh.php'; 

// --- 2. Nhập từ khóa test ---
$query = 'LapTop Dell'; // 👉 bạn có thể đổi từ khóa này để test
echo "<h1>Bắt đầu test scraper cho <em>Tin Hoc Ngoi Sao</em></h1>";
echo "<p>Từ khóa test: <strong>$query</strong></p>";
echo "<hr>";

// --- 3. Tải HTML ---
echo "<h3>Bước 1: Tải và Tiền Xử Lý HTML...</h3>";
$search_slug = urlencode(trim($query));
$url = "https://www.phucanh.vn/tim?q=" . urlencode($search_slug);
echo "<p>Đang tải từ URL: <a href='$url' target='_blank'>$url</a></p>";

$html = curl_get($url); 

if (!$html || strlen($html) < 3000) {
    echo "<h2 style='color:red;'>❌ LỖI TẢI HTML (Bước 1)</h2>";
    echo "<p>Không tải được HTML hợp lệ.</p>";
    exit;
}

// 3b. TIỀN XỬ LÝ HTML: Loại bỏ các ký tự xuống dòng, tab, và khoảng trắng thừa (CẦN THIẾT CHO REGEX)
$html_raw = $html; // Giữ lại bản thô để debug
$html_cleaned = str_replace(array("\n", "\r", "\t"), ' ', $html);
$html_cleaned = preg_replace('/\s+/', ' ', $html_cleaned);

// ✅ Lưu file HTML
$save_path = BASE_PATH . '/debug' . date('Ymd_His') . '.html';
file_put_contents($save_path, $html_raw);

echo "<p style='color:green;'>✅ Tải HTML thành công (" . strlen($html_raw) . " bytes).</p>";
echo "<p>Đã lưu file HTML thô vào: <code>" . basename($save_path) . "</code></p>";
echo "<hr>";

// --- 4. Test Tách Khối Sản Phẩm (Mục tiêu: Cô lập lỗi Regex) ---
echo "<h3>Bước 2: Phân tích và Tách Khối Sản Phẩm...</h3>";

$product_blocks = [];
// Sử dụng Regex tách khối sản phẩm đã được tối ưu cho HTML đã CLEANED
$blocks_raw = preg_split('/<div[^>]*class="product-item">/i', $html_cleaned);
array_shift($blocks_raw); // Loại bỏ phần trước sản phẩm đầu tiên

// Dùng Regex con để lấy toàn bộ khối pdLoopItem (bắt đầu từ pdLoopItem và kết thúc ngay trước </div> của product-item)
foreach ($blocks_raw as $raw_block) {
    if (preg_match('/(<div[^>]*class="pdLoopItem[^"]*"[^>]*>[\s\S]*?)<\/div>\s*<\/div>\s*<\/div>/is', $raw_block, $m_content)) {
        // Tái tạo lại thẻ mở cho khối để tiện debug
        $product_blocks[] = '<div class="product-item">' . $m_content[0];
    }
}

$total_blocks = count($product_blocks);
echo "<p style='font-size: 1.1em; font-weight: bold;'>🎯 Số lượng khối &lt;div class=\"product-item\"&gt; tách được: <span style='color:" . ($total_blocks > 0 ? 'green' : 'red') . ";'>$total_blocks</span></p>";

if ($total_blocks > 0) {
    echo "<p>💡 **Debug Khối Thô:** In ra <strong>khối sản phẩm đầu tiên</strong> đã được tách thành công:</p>";
    echo "<textarea style='width:100%; height:300px; color: #000; background: #fff;'>";
    echo htmlspecialchars($product_blocks[0]);
    echo "</textarea>";
    echo "<p style='color:green;'>✅ **Bước 2 Kết luận:** Khối đã được tách ra. Lỗi nằm ở **Trích xuất chi tiết** (Bước 3).</p>";
} else {
    echo "<p style='color:red;'>❌ **Bước 2 Kết luận:** KHÔNG tách được khối sản phẩm nào.</p>";
    echo "<p>Vui lòng kiểm tra lại Regex tách khối trong <code>getTinHocNgoiSaoProducts</code> (Mục 3) và cấu trúc HTML thô.</p>";
}

echo "<hr>";

// --- 5. Test Lọc Sản Phẩm (Sử dụng hàm chính) ---
echo "<h3>Bước 3: Test Hàm Lọc Chi Tiết (getTinHocNgoiSaoProducts)...</h3>";

$results = getPhucAnhProducts($query); // Gọi hàm đã có code debug bên trong

if (!empty($results)) {
    echo "<h2 style='color:green;'>🎉 THÀNH CÔNG!</h2>";
    echo "<p>Tìm thấy <strong>" . count($results) . "</strong> sản phẩm hợp lệ.</p>";
    echo "<pre>";
    print_r($results);
    echo "</pre>";
} else {
    echo "<h2 style='color:orange;'>⚠️ KHÔNG CÓ KẾT QUẢ CUỐI CÙNG</h2>";
    echo "<p>Vui lòng xem lại các **Khối Debug** ở trên để xác định sản phẩm nào bị lỗi ở bước trích xuất chi tiết (Tên/Giá/URL).</p>";
}

echo "<hr><p><a href='index.php'>← Quay lại trang chính</a></p>";
?>