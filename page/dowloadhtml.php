<?php

/**
 * Hàm chuyên dụng để tải nội dung HTML từ một URL
 * Sử dụng cURL để giả lập làm trình duyệt.
 *
 * @param string $url Địa chỉ web cần tải
 * @return string|false Nội dung HTML (dạng text), hoặc false nếu thất bại.
 */
function tai_html_website($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36';
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $html_text = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Lỗi cURL: ' . curl_error($ch);
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    return $html_text;
}

// ==========================================================
//           PHẦN THỰC THI (NÂNG CẤP VỚI CACHING)
// ==========================================================

$url_can_tai = 'https://example.com/'; // <-- THAY ĐỔI Ở ĐÂY
$file_cache = 'my_cache_file.html'; // Tên file để lưu cache
$thoi_gian_cache = 600; // 600 giây = 10 phút

$html_dang_text = ''; // Chuẩn bị biến chứa HTML

// --- Bước 1: Kiểm tra Cache ---
if (file_exists($file_cache) && (time() - filemtime($file_cache) < $thoi_gian_cache)) {
    
    // Nếu file cache tồn tại VÀ còn mới (chưa hết 10 phút)
    echo "Đang dùng file cache (tiết kiệm băng thông)...\n\n";
    $html_dang_text = file_get_contents($file_cache);
    
} else {
    
    // Nếu file cache không tồn tại HOẶC đã quá cũ (hơn 10 phút)
    echo "Đang tải HTML mới từ: $url_can_tai ...\n\n";
    $html_dang_text = tai_html_website($url_can_tai);
    
    if ($html_dang_text !== false) {
        // Tải thành công -> Lưu file cache mới (ghi đè file cũ)
        file_put_contents($file_cache, $html_dang_text);
        echo "Tải thành công và đã lưu cache mới.\n";
    }
}


// --- Bước 2: Xử lý Regex (Giống hệt như cũ) ---
if (empty($html_dang_text)) {
    echo "Không có dữ liệu HTML để xử lý.\n";
} else {
    // Dùng preg_match_all để lọc text đó
    $pattern = '/<h1(.*?)>(.*?)<\/h1>/is';
    echo "Đang dùng Regex để lọc thẻ <h1>...\n\n";
    
    if (preg_match_all($pattern, $html_dang_text, $matches)) {
        echo "--- KẾT QUẢ BẰNG REGEX ---\n";
        echo "Tìm thấy " . count($matches[2]) . " kết quả:\n";
        foreach ($matches[2] as $tieu_de) {
            echo "- " . trim(strip_tags($tieu_de)) . "\n";
        }
    } else {
        echo "--- REGEX THẤT BẠI --- \nKhông tìm thấy thẻ <h1> nào.\n";
    }
}

?>