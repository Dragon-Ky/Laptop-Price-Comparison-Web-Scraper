<?php
// sites/baohanhtrondoi.php
// Lấy dữ liệu sản phẩm từ baohanhtrondoi.com

function getBaoHanhTronDoiProducts($query) {
    // 1. Chuẩn bị URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://baohanhtrondoi.com/search/" . urlencode($search_slug) . "&post_type=product";

    $results = [];

    // 2. Tải HTML (dùng curl_get từ core/helpers.php)
    $html = curl_get($url);
    if (!$html) {
        return $results;
    }

    // --- QUÉT CẢ KHU VỰC CHÍNH VÀ SIDEBAR ---
    
    $search_blocks = [];

    // Regex 1: Quét các khối sản phẩm chính (class="product-small col...")
    $pattern_main = '/<div[^>]*class="[^"]*product-small col[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is';

    // Regex 2: Quét các sản phẩm trong Sidebar (Widget Sản phẩm mới)
    $pattern_sidebar = '/<ul[^>]*class="product_list_widget"[^>]*>(.*?)<\/ul>/is';
    
    // Lấy tất cả các khối từ khu vực chính
    if (preg_match_all($pattern_main, $html, $matches_main)) {
        $search_blocks = array_merge($search_blocks, $matches_main[1]);
    }

    // Lấy các mục sản phẩm từ sidebar
    if (preg_match($pattern_sidebar, $html, $matches_sidebar)) {
        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $matches_sidebar[1], $matches_sidebar_item)) {
            $search_blocks = array_merge($search_blocks, $matches_sidebar_item[1]);
        }
    }


    // 3. Xử lý từng khối tìm được
    foreach ($search_blocks as $block) {
        $product_url = '';
        $name        = '';
        $price_html  = '';
        $price_int   = 0;

        // Lấy URL sản phẩm và Tên (kiểm tra 2 cấu trúc)
        // Cấu trúc sidebar
        if (preg_match('/<a[^>]*href="([^"]+)"[^>]*>.*?<span[^>]*class="product-title[^"]*"[^>]*>(.*?)<\/span>.*?<\/a>/is', $block, $m_name_sidebar)) {
             $product_url = trim($m_name_sidebar[1]);
             $name = trim(strip_tags($m_name_sidebar[2]));
        } 
        // Cấu trúc khu vực chính
        elseif (preg_match('/<p[^>]*class="[^"]*product-title[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $block, $m_name_main)) {
             $product_url = trim($m_name_main[1]);
             $name = trim(strip_tags($m_name_main[2]));
        } else {
             continue; // Bỏ qua nếu không tìm thấy tên và URL
        }

        // LẤY GIÁ: Lấy giá trị *số* cuối cùng hiển thị trong <bdi> (sau khi đã loại bỏ giá sale <del>)
        // Cấu trúc: Giá sale (<del>...</del>) Giá cuối cùng (<ins><bdi>...</bdi></ins>)
        // Hoặc: Giá thường (<bdi>...</bdi>)
        // Chúng ta sẽ tìm giá trị BDI cuối cùng, thường là giá chính thức/giá sale.
        if (preg_match_all('/<bdi>(.*?)<\/bdi>/is', $block, $m_price_bdi)) {
            // Lấy BDI cuối cùng (thường là giá hiển thị lớn nhất)
            $price_html = end($m_price_bdi[1]); 
        } elseif (preg_match('/<span[^>]*class="amount">Liên hệ<\/span>/is', $block)) {
             $price_int = 0; // Vẫn đặt là 0 để bị loại ở bước lọc
        } else {
            continue; // Bỏ qua nếu không tìm thấy giá
        }
        
        // Chuẩn hóa giá
        if ($price_html) {
            $price_str = html_entity_decode($price_html, ENT_QUOTES, 'UTF-8');
            $price_str = preg_replace('/[\x{00A0}\s]+/u', '', $price_str); // bỏ khoảng trắng đặc biệt
            $price_int = (int) preg_replace('/[^\d]/', '', $price_str);
        }

        // 4. LỌC: CHỈ LẤY SẢN PHẨM CÓ GIÁ TRỊ SỐ LỚN HƠN 100,000
        if ($name && $product_url && $price_int > 100000) { 
            $results[] = [
                'site'  => 'BaoHanhTronDoi',
                'name'  => $name,
                'price' => $price_int,
                'url'   => $product_url
            ];
        }
    }

    return $results;
}
?>