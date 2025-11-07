<?php
// sites/laptopaz.php

function getLaptopAZProducts($query) {
    // 1. Chuẩn bị URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://laptopaz.vn/tim?q=" . urlencode($search_slug);

    $results = [];
    
    // 2. Tải HTML (Sử dụng curl_get để lấy dữ liệu thật)
    $html = curl_get($url); 
    
    if (!$html) {
        return $results;
    }

    // 3. Regex tìm từng khối sản phẩm
    $product_blocks = [];
    // Pattern này tìm toàn bộ khối sản phẩm (<div class="p-item...">)
    if (preg_match_all('/(<div[^>]*class="p-item js-p-item[^"]*">.*?<\/div>\s*<\/div>)/is', $html, $matches_blocks)) {
        $product_blocks = $matches_blocks[1];
    }
    
    if (!empty($product_blocks)) {
        foreach ($product_blocks as $block) {
            $name = '';
            $product_url = '';

            // 4. Regex con để lấy Tên và URL (Đã sửa cho LaptopAZ: <a href="..." class="p-name">)
            if (preg_match('/<a[^>]*href="([^"]+)"[^>]*class="p-name">(.*?)<\/a>/is', $block, $m_name)) {
                // URL trên LaptopAZ là relative, cần nối với domain
                $product_url = 'https://laptopaz.vn' . trim($m_name[1]);
                $name = trim(strip_tags($m_name[2]));
            }

           
            // 5. Regex con để lấy giá đang áp dụng (Đã sửa cho LaptopAZ: <span class="p-price">)
            if (preg_match('/<span[^>]*class="p-price"[^>]*>([\s\S]*?)<\/span>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } else {
                $price_html = '';
            }

            // Chuẩn hóa giá
            $price_str_raw = strip_tags($price_html);
            $price_str_clean = html_entity_decode(trim($price_str_raw), ENT_QUOTES, 'UTF-8');
            
            // **SỬA LỖI TRIỆT ĐỂ:** Chỉ giữ lại các ký tự số [0-9]
            $price_digits_only = preg_replace('/[^\d]/', '', $price_str_clean);
            
            // Chuyển chuỗi chỉ có số sang integer
            $final_price_int = (int) $price_digits_only;


            // 6. Chuẩn hóa và thêm vào kết quả
            if ($name && $product_url && $final_price_int > 1000000) { // Đảm bảo là giá trị thực tế
                $results[] = [
                    'site'  => 'LaptopAZ',
                    'name'  => $name,
                    'price' => $final_price_int,
                    'url'   => $product_url
                ];
            }
        }
    }

    return $results;
}
?>