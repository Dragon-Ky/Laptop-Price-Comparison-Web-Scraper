<?php
// sites/svstore.php

function getSVStoreProducts($query) {
    // 1. Chuẩn bị URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    // Đây là URL mẫu, bạn cần xác định chính xác cấu trúc tìm kiếm của SVStore
    // Dựa trên cấu trúc chung của các trang WP/WooCommerce.
    $url = "https://svstore.com.vn/?s=" . urlencode($search_slug) . "&post_type=product";

    $results = [];
    
    // 2. Tải HTML (Sử dụng curl_get để lấy dữ liệu thật)
    // LƯU Ý: Đảm bảo hàm curl_get được định nghĩa và hoạt động.
    $html = curl_get($url); 
    
    if (!$html) {
        return $results;
    }

    // 3. Regex tìm từng khối sản phẩm (ĐÃ SỬA: Nhắm vào khối <div class="product-inner product-item__inner"> của SVStore)
    // Khối sản phẩm kết thúc bằng </div></div>
    $product_blocks = [];
    // Pattern tìm khối sản phẩm bao ngoài cùng
    $pattern = '/<div[^>]*class="[^"]*product-inner product-item__inner[^"]*"[^>]*>([\s\S]*?)<\/div>\s*<\/div>/is';
    
    if (preg_match_all($pattern, $html, $matches)) {
        $product_blocks = $matches[1];
    }
    
    if (!empty($product_blocks)) {
        foreach ($product_blocks as $block) {
            $name = '';
            $product_url = '';

            // 4. Regex con để lấy Tên và URL (ĐÃ SỬA: Nhắm vào <a class="woocommerce-LoopProduct-link..." href="...">)
            // Tên nằm trong thẻ <h2 class="woocommerce-loop-product__title">
            if (preg_match('/<a\s+href="([^"]+)"[^>]*class="woocommerce-LoopProduct-link[^"]*"[^>]*>\s*<h2[^>]*class="woocommerce-loop-product__title">(.*?)<\/h2>/is', $block, $m_name)) {
                $product_url = trim($m_name[1]);
                $name = trim(strip_tags($m_name[2]));
            }

           
            // 5. Regex con để lấy giá đang áp dụng (ĐÃ SỬA: Giá nằm trong <bdi> cuối cùng)
            $final_price_int = 0;
            if (preg_match('/<span class="woocommerce-Price-amount amount"><bdi>([\s\S]*?)<\/bdi>/is', $block, $m_price)) {
                $price_html_raw = $m_price[1];
                
                // Loại bỏ &nbsp;, ký tự tiền tệ và dấu phân cách hàng nghìn (dấu chấm)
                $price_str_clean = html_entity_decode(trim($price_html_raw), ENT_QUOTES, 'UTF-8');
                $price_digits_only = preg_replace('/[^\d]/', '', $price_str_clean);
                
                // Chuyển chuỗi chỉ có số sang integer
                $final_price_int = (int) $price_digits_only;
            }

            // 6. Chuẩn hóa và thêm vào kết quả
            if ($name && $product_url && $final_price_int > 1000000) { 
                $results[] = [
                    'site'  => 'SVStore', // Cập nhật tên site
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