<?php
// sites/vodien.php

function getVodienProducts($query) {
    // Xử lý từ khóa tìm kiếm: đổi khoảng trắng thành dấu + để gắn vào URL cho đúng chuẩn
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://vodien.vn/?s=" . urlencode($search_slug) . "&post_type=product";

    $results = [];

    // Gọi hàm lấy nội dung HTML về, nếu lỗi hoặc không có nội dung thì dừng luôn
    $html = curl_get($url); 
    if (!$html) {
        return $results;
    }

    // Cái pattern này dùng để cắt lấy từng khối HTML (div) chứa thông tin của một sản phẩm
    $pattern = '/<div[^>]*class="[^"]*\bproduct-small\b[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is';
    
    if (preg_match_all($pattern, $html, $matches)) {
        // Duyệt qua từng khối sản phẩm tìm được
        foreach ($matches[1] as $block) {
            $name = '';
            $product_url = '';
            $img_url = '';
            $price_int = 0;
            $old_price_int = 0;
            $brand = 'Khác'; // Mặc định là 'Khác' nếu không nhận diện được hãng

            // Lọc lấy Tên sản phẩm và Link chi tiết từ thẻ <a>
            if (preg_match('/<p[^>]*class="[^"]*product-title[^"]*"[^>]*>\s*<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>\s*<\/p>/is', $block, $m_name)) {
                $product_url = trim($m_name[1]);
                $name = trim(strip_tags($m_name[2]));
            }

            // Lấy link ảnh thumbnail nằm trong thẻ <img>
            if (preg_match('/<div class="box-image">.*?<img[^>]+src="([^"]+)"/is', $block, $m_img)) {
                $img_url = $m_img[1];
            }

            // Đoạn này check xem tên sản phẩm có chứa tên hãng nào quen thuộc không (Dell, HP...)
            $known_brands = ['Dell', 'HP', 'Acer', 'Asus', 'Lenovo', 'MSI', 'Apple', 'MacBook', 'Surface', 'LG', 'Gigabyte', 'Samsung', 'Sony', 'Toshiba'];
            
            foreach ($known_brands as $b) {
                // Nếu tìm thấy tên hãng trong tên sản phẩm (không phân biệt hoa thường)
                if (stripos($name, $b) !== false) {
                    $brand = $b;
                    // Gom MacBook về chung nhà Apple
                    if (strtolower($brand) === 'macbook') {
                        $brand = 'Apple';
                    }
                    break; // Thấy rồi thì thoát vòng lặp, đỡ chạy tiếp
                }
            }

            // Xử lý Giá hiện tại: Tìm trong thẻ <ins> (giá đã giảm) hoặc thẻ giá thường
            $price_html = '';
            if (preg_match('/<ins[^>]*>.*?<bdi>([\d.,]+).*?<\/bdi>.*?<\/ins>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } elseif (preg_match('/<span[^>]*class="[^"]*woocommerce-Price-amount[^"]*"[^>]*>.*?<bdi>([\d.,]+).*?<\/bdi>.*?<\/span>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            }

            // Nếu lấy được giá thì lọc bỏ mấy ký tự lạ, chỉ giữ lại số nguyên
            if ($price_html) {
                $price_str = strip_tags($price_html);
                $price_int = (int) preg_replace('/[^\d]/', '', $price_str);
            }

            // Xử lý Giá cũ (giá gạch ngang) trong thẻ <del>
            if (preg_match('/<del[^>]*>.*?<bdi>([\d.,]+).*?<\/bdi>.*?<\/del>/is', $block, $m_old_price)) {
                $old_price_str = strip_tags($m_old_price[1]);
                $old_price_int = (int) preg_replace('/[^\d]/', '', $old_price_str);
            }

            // Nếu không có giá cũ thì gán bằng giá hiện tại luôn cho đủ dữ liệu
            if ($old_price_int == 0 && $price_int > 0) {
                $old_price_int = $price_int;
            }

            // Kiểm tra lần cuối: có tên, có link và giá > 100k (tránh rác) thì mới thêm vào danh sách
            if ($name && $price_int > 100000 && $product_url) {
                $results[] = [
                    'site'      => 'Vodien',
                    'name'      => $name,
                    'brand'     => $brand,
                    'price'     => $price_int,
                    'old_price' => $old_price_int,
                    'image'     => $img_url,
                    'url'       => $product_url
                ];
            }
        }
    }

    return $results;
}
?>