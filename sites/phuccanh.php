<?php
// sites/phucanh.php

function getPhucAnhProducts($query) {
    // 1. Chuẩn bị URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://www.phucanh.vn/tim?q=" . urlencode($search_slug); 

    $results = [];
    
    // 2. Tải HTML (Sử dụng curl_get để lấy dữ liệu thật)
    $html = curl_get($url); 
    
    if (!$html) {
        return $results;
    }

    // 3. Regex tìm tất cả các khối sản phẩm
    $product_blocks = [];
    $pattern = '/<div[^>]*class="p-container">[\s\S]*?(?=<div class="p-container">|<\/div>$)/is';
    
    if (preg_match_all($pattern, $html, $matches)) {
        $product_blocks = $matches[0];
    } else {
        return $results;
    }
    
    if (!empty($product_blocks)) {
        foreach ($product_blocks as $block) {
            $name = '';
            $product_url = '';
            $final_price_int = 0;

            // 3b. TIỀN XỬ LÝ: Loại bỏ giá VNPAY và Giá Khuyến mãi bị ẩn (ĐÃ SỬA TRIỆT ĐỂ)
            // Loại bỏ bất kỳ thẻ <span> nào chứa chuỗi 'VNPAY' hoặc 'Giá Khuyến mãi' để tránh nhầm lẫn.
            $block = preg_replace('/<span[^>]*class="p-price2"[^>]*>[\s\S]*?VNPAY[\s\S]*?<\/span>/is', '', $block);
            $block = preg_replace('/<span[^>]*class="p-oldprice2"[^>]*>[\s\S]*?Khuyến mãi[\s\S]*?<\/span>/is', '', $block);

            
            // 4. Regex con để lấy Tên và URL
            if (preg_match('/<a[^>]*href="([^"]+)"[^>]*><h3[^>]*class="p-name">(.*?)<\/h3><\/a>/is', $block, $m_name)) {
                 $product_url = trim($m_name[1]);
                 $name = trim(strip_tags($m_name[2]));
            } else {
                continue;
            }
           
            // 5. Regex con để lấy GIÁ BÁN/Giá đang áp dụng (Sử dụng phương pháp an toàn nhất)
            // Lấy giá trị số nằm SAU comment "Giá bán:" và trước ₫
            if (preg_match('/[\s\S]*?([\d\.]+)\s*₫/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } 
            // Nếu không tìm thấy comment, lấy giá trị số đầu tiên trong thẻ p-price2 còn lại
            else if (preg_match('/<span[^>]*class="p-price2"[^>]*>[\s\S]*?([\d\.]+)\s*₫/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } else {
                $price_html = '';
            }

            // LÀM SẠCH GIÁ BÁN/ÁP DỤNG
            if (!empty($price_html)) {
                $price_str_clean = html_entity_decode(trim($price_html), ENT_QUOTES, 'UTF-8');
                // Loại bỏ tất cả ký tự không phải số
                $price_digits_only = preg_replace('/[^\d]/', '', $price_str_clean);
                $final_price_int = (int) $price_digits_only;
            } else {
                $final_price_int = 0;
            }
            
            // 6. Thêm vào kết quả
            if ($name && $product_url && $final_price_int > 1000000) { 
                
                // Xử lý URL relative
                if (strpos($product_url, 'http') === false) {
                    $product_url = 'https://www.phucanh.vn' . $product_url;
                }
                
                $results[] = [
                    'site'  => 'PhucAnh', 
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