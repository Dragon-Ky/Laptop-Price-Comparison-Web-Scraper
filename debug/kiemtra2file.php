<?php
/**
 * So sánh 2 lần tải HTML từ ankhang.vn
 * - Lưu ankhang_1.html (lần 1)
 * - Lưu ankhang_2.html (lần 2)
 * - So sánh xem có gì khác
 * - Test regex để xem có sản phẩm không
 */

function tai_html_website($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => '',
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Accept-Language: vi-VN,vi;q=0.9,en;q=0.8',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        ]
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

$url = "https://www.ankhang.vn/tim?q=dell";
echo "<h2>Đang tải HTML từ: <a href='$url' target='_blank'>$url</a></h2>";

// --- Lần 1 ---
$file1 = __DIR__ . "/ankhang_1.html";
if (!file_exists($file1)) {
    echo "<p>📥 Lần đầu tải HTML và lưu vào <b>ankhang_1.html</b>...</p>";
    $html1 = tai_html_website($url);
    file_put_contents($file1, $html1);
    echo "<p>✅ Đã lưu ankhang_1.html (" . strlen($html1) . " bytes)</p>";
} else {
    echo "<p>✅ Đã có ankhang_1.html (bỏ qua tải lại lần 1)</p>";
    $html1 = file_get_contents($file1);
}

// --- Lần 2 ---
echo "<p>📥 Đang tải lại lần 2...</p>";
$html2 = tai_html_website($url);
$file2 = __DIR__ . "/ankhang_2.html";
file_put_contents($file2, $html2);
echo "<p>✅ Đã lưu ankhang_2.html (" . strlen($html2) . " bytes)</p>";

// --- So sánh ---
$len1 = strlen($html1);
$len2 = strlen($html2);
echo "<hr><h3>🔍 So sánh 2 file:</h3>";
echo "Kích thước lần 1: <b>$len1</b> bytes<br>";
echo "Kích thước lần 2: <b>$len2</b> bytes<br>";

if ($len1 === $len2) {
    echo "<p style='color:green'>✅ Hai file có kích thước giống nhau.</p>";
} else {
    echo "<p style='color:red'>⚠️ Hai file khác kích thước → web có thể đổi nội dung!</p>";
}

// Đếm số thẻ <li class="p-item">
$regex = '/<li[^>]*class="[^"]*p-item[^"]*"[^>]*>/i';
$count1 = preg_match_all($regex, $html1);
$count2 = preg_match_all($regex, $html2);

echo "<p>Số sản phẩm tìm thấy:</p>";
echo "- Lần 1: <b>$count1</b> thẻ &lt;li class='p-item'&gt;<br>";
echo "- Lần 2: <b>$count2</b> thẻ &lt;li class='p-item'&gt;<br>";

if ($count1 == 0 && $count2 == 0) {
    echo "<p style='color:red'>❌ Không thấy sản phẩm trong cả hai file. Có thể web render bằng JavaScript hoặc chặn bot.</p>";
} elseif ($count1 != $count2) {
    echo "<p style='color:orange'>⚠️ Số lượng sản phẩm khác nhau giữa hai lần tải!</p>";
} else {
    echo "<p style='color:green'>✅ Hai lần tải có cùng số lượng sản phẩm.</p>";
}

// --- Test regex trích xuất sản phẩm từ lần 2 ---
echo "<hr><h3>🧪 Test regex trên file ankhang_2.html:</h3>";

// --- Regex chính xác hơn ---
$pattern = '/<li[^>]*class="[^"]*p-item[^"]*"[^>]*>.*?<\/li>/is';
preg_match_all($pattern, $html2, $matches);

echo "<p>Tìm thấy " . count($matches[0]) . " khối sản phẩm match regex</p>";

$products = [];
foreach ($matches[0] as $block) {
    $name = $price = $url_sp = '';

    // Tên sản phẩm
    if (preg_match('/<h3[^>]*class="p-name"[^>]*>\s*<a[^>]*>(.*?)<\/a>/is', $block, $m))
        $name = trim(strip_tags($m[1]));

    // Giá sản phẩm
    if (preg_match('/<span[^>]*class="p-price"[^>]*>(.*?)<\/span>/is', $block, $m))
        $price = trim(strip_tags($m[1]));

    // Link sản phẩm
    if (preg_match('/<a[^>]*class="p-img"[^>]*href="([^"]+)"/is', $block, $m))
        $url_sp = "https://www.ankhang.vn" . $m[1];

    if ($name && $price)
        $products[] = ['name' => $name, 'price' => $price, 'url' => $url_sp];
}

?>
