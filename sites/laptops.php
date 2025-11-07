<?php
// sites/laptopsvn.php

function getLaptopsProducts($query) {
    // 1. Chuẩn bị URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://laptops.vn/?s=" . urlencode($search_slug) . "&post_type=product&dgwt_wcas=1";

    $results = [];
    
    // 2. Tải HTML
    $html = curl_get($url); // Dùng hàm từ core/helpers.php
    if (!$html) {
        return $results;
    }

    // 3. Regex tìm từng khối sản phẩm
    // ✅ SỬA LỖI Ở ĐÂY:
    // Regex này sẽ tìm từ đầu khối sản phẩm (<div class="product-small col...)
    // và bắt (capture) mọi thứ cho đến khi gặp khối <div class="count">.
    // Điều này đảm bảo cả tên, URL và giá đều nằm trong khối $block.
    $pattern = '/<div class="product-small col [^>]*>([\s\S]*?)<div class="count">/is';
    
    if (preg_match_all($pattern, $html, $matches)) {
        // Vòng lặp sẽ chạy qua $matches[0] (toàn bộ khối HTML của sản phẩm)
        foreach ($matches[0] as $block) {
            $name = '';
            $price_str = '';
            $product_url = '';

            // 4. Regex con để lấy Tên và URL (Regex này của bạn đã đúng)
            if (preg_match('/<a href="([^"]+)"[^>]*aria-label="([^"]+)"/is', $block, $m_name)) {
                $product_url = trim($m_name[1]);
                $name = trim(strip_tags($m_name[2]));
            }

            // 5. Regex con để lấy giá (Regex này của bạn cũng đã đúng)
            if (preg_match('/<span[^>]*class="[^"]*sale-price[^"]*"[^>]*>[\s\S]*?<bdi>(.*?)<\/bdi>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            }
            // Nếu không có giá sale → lấy giá gốc
            elseif (preg_match('/<span[^>]*class="[^"]*regular-price[^"]*"[^>]*>[\s\S]*?<bdi>(.*?)<\/bdi>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } else {
                $price_html = '';
            }

            // Chuẩn hóa giá
            $price_str = strip_tags($price_html);
            $price_str = html_entity_decode(trim($price_str), ENT_QUOTES, 'UTF-8');
            $price_str = preg_replace('/[\x{00A0}\s]+/u', '', $price_str);

            // Chỉ giữ lại số
            $price_int = (int) preg_replace('/[^\d]/', '', $price_str);

            // 6. Chuẩn hóa và thêm vào kết quả
            if ($name && $price_str && $product_url) {

                // ✅ Làm sạch ký tự lạ & khoảng trắng
                $price_str = preg_replace('/[\x{00A0}\s]+/u', '', $price_str);

                // ✅ Bỏ ký tự không phải số
                $price_int = (int) preg_replace('/[^\d]/', '', $price_str);

                // ✅ Chỉ thêm nếu giá hợp lệ
                if ($price_int > 100000) {
                    $results[] = [
                        'site'  => 'LaptopsVn',
                        'name'  => $name,
                        'price' => $price_int,
                        'url'   => $product_url
                    ];
                }
            }
        }
    }

    return $results;
}
?>