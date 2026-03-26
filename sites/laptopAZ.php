<?php
// sites/laptopaz.php

function getLaptopAZProducts($query) {
    // Xử lý từ khóa tìm kiếm: thay khoảng trắng bằng dấu + để đúng chuẩn URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://laptopaz.vn/tim?q=" . urlencode($search_slug);

    $results = [];
    
    // Gọi curl lấy nội dung HTML về, nếu lỗi thì trả về mảng rỗng ngay
    $html = curl_get($url); 
    
    if (!$html) {
        return $results;
    }

    // Cắt lấy từng khối HTML chứa sản phẩm (class p-item)
    $product_blocks = [];
    if (preg_match_all('/(<div[^>]*class="p-item js-p-item[^"]*">.*?<\/div>\s*<\/div>)/is', $html, $matches_blocks)) {
        $product_blocks = $matches_blocks[1];
    }
        
    if (!empty($product_blocks)) {
        // Duyệt qua từng khối sản phẩm tìm được
        foreach ($product_blocks as $block) {
            $name = '';
            $product_url = '';
            $image_url = ''; 
            $brand = ''; // Reset biến brand mỗi lần lặp để tránh lấy nhầm của con trước

            // --- LẤY TÊN VÀ LINK ---
            // Tìm thẻ a chứa link và tên sản phẩm, nhớ nối thêm domain vào link
            if (preg_match('/<a[^>]*href="([^"]+)"[^>]*class="p-name">(.*?)<\/a>/is', $block, $m_name)) {
                $product_url = 'https://laptopaz.vn' . trim($m_name[1]);
                $name = trim(strip_tags($m_name[2]));
            }

            // --- LẤY ẢNH ---
            // Link ảnh trong source thường thiếu domain gốc, nên phải nối thêm vào
            if (preg_match('/<img[^>]*src="([^"]+)"/i', $block, $m_img)) {
                $image_url = 'https://laptopaz.vn' . trim($m_img[1]);
            }

            // --- LẤY GIÁ ---
            // Lấy chuỗi giá trong thẻ span class p-price
            if (preg_match('/<span[^>]*class="p-price"[^>]*>([\s\S]*?)<\/span>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } else {
                $price_html = '';
            }

            // Dọn dẹp giá: xóa tag html, xóa ký tự lạ, chỉ giữ lại số nguyên
            $price_str_raw = strip_tags($price_html);
            $price_str_clean = html_entity_decode(trim($price_str_raw), ENT_QUOTES, 'UTF-8');
            $price_digits_only = preg_replace('/[^\d]/', '', $price_str_clean);
            $final_price_int = (int) $price_digits_only;

            // --- XÁC ĐỊNH THƯƠNG HIỆU ---
            // So sánh tên sản phẩm với danh sách hãng có sẵn
            $known_brands = [
                'Dell','HP','Acer','Asus','Lenovo','MSI','Apple','MacBook',
                'Surface','LG','Gigabyte','Samsung','Sony','Toshiba'
            ];

            foreach ($known_brands as $b) {
                if (stripos($name, $b) !== false) {
                    $brand = $b;
                    // Gom MacBook về chung nhà Apple
                    if (strtolower($b) === 'macbook') $brand = 'Apple';
                    break;
                }
            }

            // --- LƯU KẾT QUẢ ---
            // Chỉ lấy nếu có tên, link và giá trên 1 triệu (lọc rác phụ kiện)
            if ($name && $product_url && $final_price_int > 1000000) { 
                $results[] = [
                    'site'  => 'LaptopAZ',
                    'name'  => $name,
                    'price' => $final_price_int,
                    'url'   => $product_url,
                    'image' => $image_url,
                    'brand' => $brand
                ];
            }
        }
    }

    return $results;
}
?>