<?php
// sites/vodien.php

function getVodienProducts($query) {
    // 1. Chuẩn bị URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://vodien.vn/?s=" . urlencode($search_slug) . "&post_type=product";

    $results = [];

    // 2. Tải HTML
    $html = curl_get($url); // Dùng hàm từ core/helpers.php
    if (!$html) {
        return $results;
    }

    // 3. Regex tìm từng khối sản phẩm (mỗi sản phẩm nằm trong <div class="product-small ...">)
    $pattern = '/<div[^>]*class="[^"]*\bproduct-small\b[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is';
    
    if (preg_match_all($pattern, $html, $matches)) {
        foreach ($matches[1] as $block) {
            $name = '';
            $product_url = '';
            $price_str = '';
            $price_int = 0;

            // 4. Lấy Tên và URL sản phẩm
            if (preg_match('/<p[^>]*class="[^"]*product-title[^"]*"[^>]*>\s*<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>\s*<\/p>/is', $block, $m_name)) {
                $product_url = trim($m_name[1]);
                $name = trim(strip_tags($m_name[2]));
            }

            // 5. Ưu tiên lấy giá trong <ins> (giá khuyến mãi)
            if (preg_match('/<ins[^>]*>.*?<bdi>([\d.,]+).*?<\/bdi>.*?<\/ins>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            }
            // Nếu không có <ins>, lấy giá trong <span class="woocommerce-Price-amount">
            elseif (preg_match('/<span[^>]*class="[^"]*woocommerce-Price-amount[^"]*"[^>]*>.*?<bdi>([\d.,]+).*?<\/bdi>.*?<\/span>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } else {
                $price_html = '';
            }

            // 6. Chuẩn hóa giá
            $price_str = strip_tags($price_html);
            $price_str = html_entity_decode(trim($price_str), ENT_QUOTES, 'UTF-8');
            $price_str = preg_replace('/[\x{00A0}\s]+/u', '', $price_str); // bỏ &nbsp; và khoảng trắng
            $price_int = (int) preg_replace('/[^\d]/', '', $price_str);

            // 7. Bỏ qua nếu không hợp lệ
            if ($name && $price_int > 100000 && $product_url) {
                $results[] = [
                    'site'  => 'Vodien',
                    'name'  => $name,
                    'price' => $price_int,
                    'url'   => $product_url
                ];
            }
        }
    }

    return $results;
}
?>
