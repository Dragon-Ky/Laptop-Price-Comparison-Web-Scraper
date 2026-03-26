<?php
header('Content-Type: text/html; charset=utf-8');

// 1. MẪU HTML BẠN CUNG CẤP (Dùng Heredoc để giữ nguyên định dạng xuống dòng)
$html_input = <<<HTML
<div class="product-item js-p-item" data-id="27850">
  <a href="/laptop-dell-vostro-3530-core-i3-1305u-ram-8gb-ssd-512gb" class="product-image"> 
    <img src="/media/product/250-27850-laptop_dell_vostro_3530__0002.jpg"> 
  </a>

  <div class="product-info flex-1">
    <a href="/laptop-dell-vostro-3530-core-i3-1305u-ram-8gb-ssd-512gb">
      <h3 class="product-title line-clamp-3">Laptop Dell Vostro 3530 Testing</h3>
    </a>

    <div class="product-martket-main d-flex align-items-center">
      <p class="product-market-price">11.990.000đ</p>
      <div class="product-percent-price"> -17% </div>
    </div>
    
    <div class="product-price-main font-weight-600">
      9.900.000đ
    </div>

    <div class="box-tooltip-gift">...</div>
  </div>
</div>
HTML;

echo "<h1>🔍 KẾT QUẢ DEBUG</h1>";

// 2. GIẢ LẬP LOGIC CRAWL
// Regex bao quanh khối sản phẩm (giả lập vòng lặp foreach)
// Lưu ý: Thêm modifier 's' (PCRE_DOTALL) để dấu chấm (.) khớp cả xuống dòng
$pattern_block = '/<div[^>]*class="[^"]*product-item[^"]*"[^>]*>(.*?)<\/div>\s*$/is'; 
// (Tôi chỉnh nhẹ regex block để khớp với mẫu test đơn lẻ này)

// Nếu chạy thật trong vòng lặp thì biến $block chính là nội dung bên trong
$block = $html_input; 

echo "<div style='border:1px solid #ccc; padding:10px; margin-bottom:10px;'>";

// --- TEST 1: GIÁ MỚI (QUAN TRỌNG NHẤT) ---
echo "<h3>1. Kiểm tra Giá Mới (product-price-main)</h3>";
$price_new = 0;

// Regex mới: Bỏ \b, thêm modifier 's' để bắt xuyên qua xuống dòng
$regex_new = '/class="[^"]*product-price-main[^"]*"[^>]*>(.*?)<\/div>/is';

if (preg_match($regex_new, $block, $m_price)) {
    echo "<p style='color:green'>✅ Đã khớp Regex!</p>";
    echo "<b>Dữ liệu thô (Raw HTML):</b> <pre style='background:#eee;padding:5px;'>" . htmlspecialchars($m_price[1]) . "</pre>";
    
    $clean_str = strip_tags($m_price[1]);
    echo "<b>Sau khi strip_tags:</b> [" . $clean_str . "] (Lưu ý các khoảng trắng thừa)<br>";
    
    $price_new = (int) preg_replace('/[^\d]/', '', $clean_str);
    echo "<b>Kết quả số (Final Int):</b> <span style='color:red; font-size:20px; font-weight:bold'>" . number_format($price_new) . "</span>";
} else {
    echo "<p style='color:red'>❌ Không bắt được thẻ product-price-main. Kiểm tra lại Regex!</p>";
}

echo "<hr>";

// --- TEST 2: GIÁ CŨ ---
echo "<h3>2. Kiểm tra Giá Cũ (product-market-price)</h3>";
$price_old = 0;
$regex_old = '/class="[^"]*product-market-price[^"]*"[^>]*>(.*?)<\/[a-z]+>/is';

if (preg_match($regex_old, $block, $m_market)) {
    $raw_old = strip_tags($m_market[1]);
    $price_old = (int) preg_replace('/[^\d]/', '', $raw_old);
    echo "<b>Kết quả số:</b> " . number_format($price_old);
} else {
    echo "❌ Không tìm thấy giá cũ bên ngoài.";
}

// --- TEST 3: TOOLTIP (Dự phòng) ---
if ($price_old == 0) {
    echo "<br><i>...Đang tìm trong Tooltip...</i>";
    // Giả lập regex tooltip
}

echo "<hr>";

// --- KẾT QUẢ CUỐI CÙNG ---
echo "<h3>3. Logic chọn giá cuối</h3>";
$final_price = ($price_new > 0) ? $price_new : $price_old;

echo "Giá Mới: " . number_format($price_new) . "<br>";
echo "Giá Cũ: " . number_format($price_old) . "<br>";
echo "=> <b>GIÁ SẼ LƯU VÀO DB: " . number_format($final_price) . "</b>";

echo "</div>";
?>