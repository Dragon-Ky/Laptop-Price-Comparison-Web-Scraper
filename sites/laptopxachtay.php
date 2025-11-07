<?php
// sites/laptopxachtay.php

function getLaptopXachTayProducts($query) {
    // 1. Chuẩn bị URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://laptopxachtayshop.com/tim-kiem/" . urlencode($search_slug);

    $results = [];
    
    // 2. Tải HTML
    $html = curl_get($url); // Dùng hàm từ core/helpers.php
    if (!$html) {
        return $results;
    }

    // 3. Regex tìm từng khối sản phẩm
    $pattern = '/<div[^>]*class="[^"]*product type-product[^"]*"[^>]*>(.*?)<span class="hn-menu-order-data"/is';
    
    if (preg_match_all($pattern, $html, $matches)) {
        foreach ($matches[1] as $block) {
            $name = '';
            $price_str = '';
            $product_url = '';

            // 4. Regex con để lấy Tên và URL
            if (preg_match('/<p[^>]*class="[^"]*woocommerce-loop-product__title[^"]*"[^>]*>\s*<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>\s*<\/p>/is', $block, $m_name)) {
                $product_url = $m_name[1];
                $name = trim(strip_tags($m_name[2]));
            }

           
            // 5. Regex con để lấy giá khuyến mãi trong thẻ <ins> (ưu tiên)
            if (preg_match('/<ins[^>]*>(.*?)<\/ins>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            }
            // Nếu không có <ins>, lấy giá thường trong <span class="spanprice">
            elseif (preg_match('/<span[^>]*class="[^"]*(?:spanprice|woocommerce-Price-amount)[^"]*"[^>]*>(.*?)<\/span>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } else {
                $price_html = '';
            }

            // Chuẩn hóa giá
            $price_str = strip_tags($price_html);
            $price_str = html_entity_decode(trim($price_str), ENT_QUOTES, 'UTF-8');
            $price_str = preg_replace('/[\x{00A0}\s]+/u', '', $price_str);

            // Nếu giá có đuôi "8.363" thì bỏ
            $price_str = preg_replace('/8[.,]?363$/', '', $price_str);

            // Chỉ giữ số
            $price_int = (int) preg_replace('/[^\d]/', '', $price_str);



            // 6. Chuẩn hóa và thêm vào kết quả
            if ($name && $price_str && $product_url) {

                // ✅ Làm sạch các ký tự lạ (bao gồm &nbsp; và khoảng trắng đặc biệt)
                $price_str = preg_replace('/[\x{00A0}\s]+/u', '', $price_str);

                // ✅ Cắt đuôi “8.363” nếu xuất hiện (dù là 8363, 8.363 hay 8,363)
                $price_str = preg_replace('/8[.,]?363$/', '', $price_str);

                // ✅ Bỏ mọi ký tự không phải số
                $price_int = (int) preg_replace('/[^\d]/', '', $price_str);

                // ✅ Nếu giá nhỏ bất thường (vd < 1.000.000) thì có thể bị lỗi → bỏ qua
                if ($price_int > 100000) {
                    $results[] = [
                        'site'  => 'LaptopXachTay',
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
