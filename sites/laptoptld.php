<?php
// sites/laptoptld.php

function getLaptopTLDProducts($query) {
    // 1. Chuẩn bị URL (giữ nguyên)
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://laptoptld.com/?s=" . urlencode($search_slug) . "&post_type=product";

    $results = [];

    // 2. Tải HTML
    $html = curl_get($url);
    if (!$html) {
        return $results;
    }

    // --- PHƯƠNG PHÁP MỚI: CẮT CHUỖI (EXPLODE) ---
    
    // Dựa trên HTML bạn cung cấp: <div class="product-small box ">
    $blocks = explode('class="product-small box', $html);
    
    // Bỏ phần tử đầu tiên (là phần header/menu thừa trước khi đến sản phẩm đầu tiên)
    array_shift($blocks);

    foreach ($blocks as $block) {
        $name = '';
        $product_url = '';
        $image_url = '';
        $price_int = 0;
        $old_price_int = 0;
        $brand = 'Khác'; 

        // --- LẤY TÊN VÀ URL ---
        // Tìm thẻ <p class="name product-title"> rồi lấy thẻ <a> bên trong
        if (preg_match('/class="[^"]*product-title[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $block, $m_name)) {
            $product_url = trim($m_name[1]);
            $name = trim(strip_tags($m_name[2]));
        } else {
            // Nếu không tìm thấy tên thì bỏ qua khối này (có thể là rác cuối trang)
            continue;
        }

        // --- LẤY ẢNH ---
        // Tìm class="box-image", rồi tìm thẻ img bên trong
        if (preg_match('/class="box-image".*?<img[^>]+src="([^"]+)"/is', $block, $m_img)) {
            $image_url = $m_img[1];
        }

        // --- LẤY GIÁ (Logic Flatsome Theme) ---
        
        // 1. Tìm giá khuyến mãi (nằm trong thẻ <ins>)
        $price_html = '';
        if (preg_match('/<ins[^>]*>.*?<bdi>([\d.,]+).*?<\/bdi>.*?<\/ins>/is', $block, $m_price)) {
            $price_html = $m_price[1];
        } 
        // 2. Nếu không có <ins>, tìm giá thường (thẻ <span class="amount"><bdi>)
        // Lưu ý: Phải tránh thẻ <del> (giá cũ)
        elseif (preg_match('/<span[^>]*class="[^"]*amount[^"]*"[^>]*>.*?<bdi>([\d.,]+).*?<\/bdi>.*?<\/span>/is', $block, $m_price)) {
            // Kiểm tra xem giá này có nằm trong thẻ del không (đề phòng)
            if (strpos($block, '<del') === false || strpos($block, '<ins') === false) {
                 $price_html = $m_price[1];
            }
        }

        // Làm sạch giá: xóa dấu chấm, chữ đ
        if ($price_html) {
            $price_str = strip_tags($price_html);
            $price_int = (int) preg_replace('/[^\d]/', '', $price_str);
        }

        // 3. Tìm giá cũ (trong thẻ <del>)
        if (preg_match('/<del[^>]*>.*?<bdi>([\d.,]+).*?<\/bdi>.*?<\/del>/is', $block, $m_old_price)) {
            $old_price_str = strip_tags($m_old_price[1]);
            $old_price_int = (int) preg_replace('/[^\d]/', '', $old_price_str);
        }

        // Nếu không có giá cũ, gán bằng giá hiện tại
        if ($old_price_int == 0 && $price_int > 0) {
            $old_price_int = $price_int;
        }

        // --- XỬ LÝ HÃNG (BRAND) ---
        $known_brands = [
            'Dell', 'HP', 'Acer', 'Asus', 'Lenovo', 'MSI', 'Apple', 'MacBook', 
            'Surface', 'LG', 'Gigabyte', 'Samsung', 'Sony', 'Toshiba', 'Alienware'
        ];
        
        foreach ($known_brands as $b) {
            if (stripos($name, $b) !== false) {
                $brand = $b;
                if (strtolower($brand) === 'macbook') $brand = 'Apple';
                if (strtolower($brand) === 'alienware') $brand = 'Dell';
                break;
            }
        }

        // --- LƯU KẾT QUẢ ---
        if ($name && $price_int > 100000 && $product_url) {
            $results[] = [
                'site'      => 'LaptopTLD',
                'name'      => $name,
                'brand'     => $brand,
                'price'     => $price_int,
                'old_price' => $old_price_int,
                'image'     => $image_url,
                'url'       => $product_url
            ];
        }
    }

    return $results;
}
?>