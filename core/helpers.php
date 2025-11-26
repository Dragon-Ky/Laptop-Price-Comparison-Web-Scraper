<?php
// core/helpers.php

/**
 * Tải HTML từ một URL dùng cURL, giả lập trình duyệt
 */
function curl_get($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36';
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
    $html_text = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Lỗi cURL khi tải '. $url. ': '. curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    return $html_text;
}

/**
 * Lấy số từ một chuỗi giá (ví dụ: "10.390.000 đ" -> 10390000)
 */
function parse_price_vi($text) {
    // Xóa tất cả mọi thứ không phải là số (dấu chấm, dấu cách, chữ "đ", "₫"...)
    $price_only_digits = preg_replace('/[^\d]/', '', $text);
    
    // Chuyển chuỗi số thành số nguyên
    return (int) $price_only_digits;
}


/**
 * So sánh độ tương đồng của 2 chuỗi (đơn giản)
 */
function fuzzy_match($query, $string) {
    $percent = 0;
    similar_text(strtolower($query), strtolower($string), $percent);
    return $percent;
}

/**
 * Định dạng số thành tiền tệ (ví dụ: 10390000 -> "10.390.000 ₫")
 */
function format_price($price) {
    // Ép về chuỗi
    $price = trim((string)$price);

    // ✅ Nếu có chuỗi dư 8363 hoặc 8.363 ở cuối thì xóa
    if (preg_match('/(8[.,]?363)$/', $price)) {
        $price = preg_replace('/(8[.,]?363)$/', '', $price);
    }

    // Xóa ký tự không cần thiết
    $price = str_replace(['₫', ',', ' '], '', $price);

    // Chuyển sang số
    $price = (float) $price;

    // Format lại dạng 1.234.567₫
    return number_format($price, 0, ',', '.') . '₫';
}

/**
 * Chuẩn hóa văn bản: bỏ dấu, lowercase, bỏ ký tự đặc biệt
 */
function normalize_text($str) {
    $str = trim($str);
    $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $str = mb_strtolower($str, 'UTF-8'); // dùng mb thay vì strtolower để không lỗi unicode

    // Thử bỏ dấu tiếng Việt (an toàn hơn iconv)
    $trans = [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a',
        'ă'=>'a','ằ'=>'a','ắ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ầ'=>'a','ấ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
        'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
        'ê'=>'e','ề'=>'e','ế'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o',
        'ô'=>'o','ồ'=>'o','ố'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
        'ư'=>'u','ừ'=>'u','ứ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
        'đ'=>'d',
        'À'=>'a','Á'=>'a','Ả'=>'a','Ã'=>'a','Ạ'=>'a',
        'Ă'=>'a','Ằ'=>'a','Ắ'=>'a','Ẳ'=>'a','Ẵ'=>'a','Ặ'=>'a',
        'Â'=>'a','Ầ'=>'a','Ấ'=>'a','Ẩ'=>'a','Ẫ'=>'a','Ậ'=>'a',
        'È'=>'e','É'=>'e','Ẻ'=>'e','Ẽ'=>'e','Ẹ'=>'e',
        'Ê'=>'e','Ề'=>'e','Ế'=>'e','Ể'=>'e','Ễ'=>'e','Ệ'=>'e',
        'Ì'=>'i','Í'=>'i','Ỉ'=>'i','Ĩ'=>'i','Ị'=>'i',
        'Ò'=>'o','Ó'=>'o','Ỏ'=>'o','Õ'=>'o','Ọ'=>'o',
        'Ô'=>'o','Ồ'=>'o','Ố'=>'o','Ổ'=>'o','Ỗ'=>'o','Ộ'=>'o',
        'Ơ'=>'o','Ờ'=>'o','Ớ'=>'o','Ở'=>'o','Ỡ'=>'o','Ợ'=>'o',
        'Ù'=>'u','Ú'=>'u','Ủ'=>'u','Ũ'=>'u','Ụ'=>'u',
        'Ư'=>'u','Ừ'=>'u','Ứ'=>'u','Ử'=>'u','Ữ'=>'u','Ự'=>'u',
        'Ỳ'=>'y','Ý'=>'y','Ỷ'=>'y','Ỹ'=>'y','Ỵ'=>'y',
        'Đ'=>'d'
    ];
    $str = strtr($str, $trans);

    // Giữ lại chữ và số
    $str = preg_replace('/[^a-z0-9]+/', ' ', $str);
    $str = trim($str); // Trim lại 1 lần nữa sau khi thay thế

    return $str;
}
?>

<?php
function is_laptop($name) {
    $laptop_keywords = ['laptop', 'notebook', 'book', 'ultrabook', 'macbook','DELL','HP','ASUS','ACER','LENOVO','MSI','MACBOOK','SURFACE','IDEAPAD','VIVOBOOK','PAVILION','ENVY','ROG','TUF','LEGION','THINKPAD'];
    $name_lower = strtolower($name);
    
    foreach ($laptop_keywords as $keyword) {
        if (str_contains($name_lower, $keyword)) {
            return true;
        }
    }
    return false;
}

/**
 * Kiểm tra xem tên sản phẩm có phải là phụ kiện/linh kiện không
 * (VD: Sạc laptop, Pin, Màn hình, RAM...)
 */
function is_accessory($name) {
    $accessory_keywords = [
        'sạc',
        'pin',
        'pin dự phòng',
        'adapter',
        'charger',
        'cáp',
        'cable',
        'bàn phím',
        'keyboard',
        'chuột',
        'mouse',
        'tai nghe',
        'headphone',
        'speaker',
        'loa',
        'màn hình',
        'monitor',
        'ram',
        'ổ cứng',
        'ssd',
        'hdd',
        'gpu',
        'vga',
        'card màn hình',
        'bộ nhớ',
        'cpu',
        'mainboard',
        'bo mạch',
        'quạt',
        'fan',
        'tản nhiệt',
        'cooling pad',
        'pad',
        'túi',
        'balo',
        'case',
        'bao',
        'kính cường lực',
        'miếng dán',
        'dán màn hình',
        'hub',
        'dock',
        'bộ chuyển',
        'converter',
        'nút cách ly',
        'bộ vệ sinh',
        'làm sạch'
    ];
    
    $name_lower = strtolower($name);
    
    foreach ($accessory_keywords as $keyword) {
        if (str_contains($name_lower, $keyword)) {
            return true;
        }
    }
    return false;
}
?>