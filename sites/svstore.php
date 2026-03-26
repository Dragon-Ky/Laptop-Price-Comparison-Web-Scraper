<?php
// sites/svstore.php

function getSVStoreProducts($query) {
    // Xử lý từ khóa tìm kiếm: đổi khoảng trắng thành dấu + để gắn vào URL cho đúng chuẩn
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://svstore.com.vn/?s=" . urlencode($search_slug) . "&post_type=product";

    $results = [];
    
    // Gọi hàm lấy nội dung HTML về, nếu lỗi hoặc không có nội dung thì dừng luôn
    $html = curl_get($url); 
    if (!$html) {
        return $results;
    }

    // Quét toàn bộ HTML để tìm các thẻ <li> chứa thông tin sản phẩm (dựa trên class product type-product)
    $product_blocks = [];
    $pattern = '/<li[^>]*class="[^"]*product type-product[^"]*"[^>]*>(.*?)<\/li>/is';
    
    if (preg_match_all($pattern, $html, $matches)) {
        $product_blocks = $matches[1];
    }
    
    // Nếu tìm thấy khối sản phẩm nào thì bắt đầu duyệt qua từng cái
    if (!empty($product_blocks)) {
        foreach ($product_blocks as $block) {
            $name = '';
            $product_url = '';
            $image_url = '';
            $final_price_int = 0;

            // Lọc lấy Tên sản phẩm và Link chi tiết từ thẻ <a> (class woocommerce-LoopProduct-link)
            if (preg_match('/<a[^>]*href="([^"]+)"[^>]*class="[^"]*woocommerce-LoopProduct-link[^"]*"[^>]*>\s*<h2[^>]*class="[^"]*woocommerce-loop-product__title[^"]*">(.*?)<\/h2>/is', $block, $m_name)) {
                $product_url = trim($m_name[1]);
                $name = trim(strip_tags($m_name[2]));
            }

            // Lấy link ảnh thumbnail từ thẻ <img> nằm trong div product-thumbnail
            if (preg_match('/<div[^>]*class="[^"]*product-thumbnail[^"]*"[^>]*>.*?<img[^>]*src="([^"]+)"/is', $block, $m_img)) {
                $image_url = trim($m_img[1]);
            }

            // Xử lý phần Giá: tìm trong thẻ <bdi>, sau đó xóa hết chữ chỉ giữ lại số nguyên
            if (preg_match('/<span class="price">.*?<bdi>([\s\S]*?)<\/bdi>/is', $block, $m_price)) {
                $price_html_raw = $m_price[1];
                // Loại bỏ mọi ký tự không phải số
                $price_digits_only = preg_replace('/[^\d]/', '', $price_html_raw);
                $final_price_int = (int) $price_digits_only;
            }

            // Đoạn này check xem tên sản phẩm có chứa tên hãng nào quen thuộc không (Dell, HP...)
            $brand = 'Khác';
            $known_brands = ['Dell','HP','Acer','Asus','Lenovo','MSI','Apple','MacBook','Surface','LG','Gigabyte','Samsung','Sony','Toshiba'];
            foreach ($known_brands as $b) {
                // Nếu tìm thấy tên hãng trong tên sản phẩm
                if (stripos($name, $b) !== false) {
                    $brand = $b;
                    // Gom MacBook về chung nhà Apple
                    if (strtolower($b) === 'macbook') $brand = 'Apple';
                    break;
                }
            }

            // Kiểm tra lần cuối: có tên, có link và giá > 1 triệu (tránh rác) thì mới thêm vào danh sách
            if ($name && $product_url && $final_price_int > 1000000) { 
                $results[] = [
                    'site'      => 'SVStore',
                    'name'      => $name,
                    'price'     => $final_price_int,
                    'url'       => $product_url,
                    'image'     => $image_url,
                    'brand'     => $brand,
                ];
            }
        }
    }

    return $results;
}
?>