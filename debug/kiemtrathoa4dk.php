<?php
/**
 * Test xem một trang web có thể scrape bằng Regex (HTML tĩnh) không.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

function tai_html_website($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => ''
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

$url = $_GET['url'] ?? '';
if (!$url) {
    echo "<h3>🔍 Nhập URL để kiểm tra (ví dụ: https://laptopkimanh.vn/search?s=dell):</h3>";
    echo "<form><input name='url' style='width:500px' placeholder='https://example.com'><button>Kiểm tra</button></form>";
    exit;
}

echo "<h2>🛠️ Báo Cáo Phân Tích Khả Năng Scrape HTML Tĩnh</h2>";
echo "<h3>Đang tải HTML từ:</h3><p><code>$url</code></p>";
$html = tai_html_website($url);
$length = strlen($html);
echo "<p>📄 Kích thước HTML: <b>$length bytes</b></p>";

if ($length < 1000) {
    echo "<p style='color:red'>⚠️ HTML quá ngắn — có thể bị chặn hoặc redirect. Kiểm tra thủ công trang web trước.</p>";
}

// Lưu lại để kiểm tra thủ công
file_put_contents("debug_test.html", $html);
echo "<p>✅ HTML đã lưu vào <code>debug_test.html</code></p>";

// ------------------------------------------
echo "<hr><h3>1. Phân tích Dấu hiệu AJAX/JS Rendering</h3>";
// 1. Kiểm tra dấu hiệu của các framework render dữ liệu client-side
if (preg_match('/<script[^>]+src=.*(vue|react|angular|app\.js|main\.js)/i', $html) || 
    preg_match('/<div[^>]*id="(app|root|mount)"/i', $html)) {
    echo "<p style='color:red'>❌ Phát hiện dấu hiệu JavaScript Frameworks (Vue/React/Angular/etc.) hoặc tên file/ID container phổ biến.</p>";
    $js_risk = true;
} else {
    echo "<p style='color:green'>✅ Không thấy dấu hiệu rõ ràng của các Framework JS. Rủi ro thấp.</p>";
    $js_risk = false;
}

// ------------------------------------------
echo "<hr><h3>2. Kiểm tra Trích xuất Sản phẩm Cốt lõi (Mục tiêu của chúng ta)</h3>";
// 2. Kiểm tra trích xuất CÁC THÀNH PHẦN CẦN THIẾT (để xác nhận dữ liệu sản phẩm có trong HTML ban đầu không)

$all_good = true;

// A. Tìm kiếm khối sản phẩm (sử dụng cấu trúc phổ biến nhất trong e-commerce)
// Thường là thẻ <div> hoặc <li> có class 'product', 'item', 'col', 'box'
$product_block_regex = '/<(div|li)[^>]*class="[^"]*(product|item|col|box)[^"]*"[^>]*>[\s\S]*?(laptop|dell|hp)[\s\S]*?price[\s\S]*?<\/(div|li)>/i';
if (preg_match_all($product_block_regex, $html, $m_blocks)) {
    echo "<p style='color:green'>✅ **Thành phần A (Khối Sản phẩm):** Đã tìm thấy <b>" . count($m_blocks[0]) . "</b> khối có chứa Tên Sản phẩm & Giá.</p>";
    if (count($m_blocks[0]) > 0) {
        // In ra 1000 ký tự đầu tiên của khối đầu tiên để xem cấu trúc
        echo "<details><summary>Xem trước Khối Sản phẩm #1 (" . strlen($m_blocks[0][0]) . " bytes)</summary>";
        echo "<pre style='background:#f4f4f4; padding:10px; border:1px dashed #ccc;'>" . htmlspecialchars(substr($m_blocks[0][0], 0, 1000)) . "...</pre>";
        echo "</details>";
    }
} else {
    echo "<p style='color:red'>❌ **Thành phần A (Khối Sản phẩm):** KHÔNG tìm thấy khối HTML chứa cả Tên & Giá sản phẩm. (Rất có thể do JS render)</p>";
    $all_good = false;
}

// B. Kiểm tra Tên & URL sản phẩm (Link phổ biến)
if (preg_match('/<a[^>]+href="([^"]+)"[^>]*>(.*?)(laptop|dell|hp|lenovo|macbook)(.*?)<\/a>/i', $html)) {
    echo "<p style='color:green'>✅ **Thành phần B (Tên & URL):** Đã tìm thấy link sản phẩm có chứa tên hãng/từ khóa quan trọng.</p>";
} else {
    echo "<p style='color:red'>❌ **Thành phần B (Tên & URL):** KHÔNG tìm thấy link sản phẩm phù hợp. (Cần kiểm tra kỹ Regex)</p>";
    $all_good = false;
}

// C. Kiểm tra Giá (Cần có ₫, VNĐ, hoặc con số)
if (preg_match('/(?:[0-9]{1,3}(?:[.,][0-9]{3})*|price|amount|₫|vnđ)/i', $html)) {
    echo "<p style='color:green'>✅ **Thành phần C (Giá):** Đã tìm thấy các dấu hiệu về giá (tiền tệ hoặc cấu trúc giá).</p>";
} else {
    echo "<p style='color:red'>❌ **Thành phần C (Giá):** KHÔNG có dấu hiệu về giá. (Gần như chắc chắn do JS render)</p>";
    $all_good = false;
}

// ------------------------------------------
echo "<hr><h3>3. Kết luận và Hướng đi</h3>";

if ($all_good && !$js_risk) {
    echo "<h3 style='color:green'>🎉 **KẾT LUẬN CUỐI CÙNG:** Khả năng scrape bằng Regex là **RẤT CAO**!</h3>";
    echo "<p>Bây giờ bạn có thể dùng Regex để trích xuất: <kbd>$product_block_regex</kbd> để lấy từng khối sản phẩm, sau đó dùng các Regex con để lấy Tên, URL, Giá từ mỗi khối.</p>";
} else if ($all_good && $js_risk) {
    echo "<h3 style='color:orange'>⚠️ **KẾT LUẬN CUỐI CÙNG:** Rủi ro do JS cao, nhưng có dữ liệu sản phẩm trong HTML ban đầu.</h3>";
    echo "<p>Nên thử viết code Regex trước. Nếu thất bại, bắt buộc phải dùng **Selenium** hoặc **Goutte (mô phỏng browser)**.</p>";
} else {
    echo "<h3 style='color:red'>🛑 **KẾT LUẬN CUỐI CÙNG:** Trang này **KHÔNG** thể scrape bằng Regex!</h3>";
    echo "<p>Lý do: Dữ liệu sản phẩm không được nhúng trong HTML tĩnh, mà được tải sau bởi JavaScript.</p>";
    echo "<p>→ **Hướng giải quyết:** Sử dụng công cụ mô phỏng trình duyệt (headless browser) như **Selenium** để chờ JS tải xong dữ liệu, hoặc tìm kiếm API mà trang web đó đang gọi để lấy dữ liệu trực tiếp.</p>";
}

?>