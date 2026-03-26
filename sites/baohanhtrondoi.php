<?php
// sites/baohanhtrondoi.php
// Lấy dữ liệu sản phẩm từ baohanhtrondoi.com

function getBaoHanhTronDoiProducts($query) {
    // Xử lý từ khóa tìm kiếm: đổi khoảng trắng thành dấu + cho đúng chuẩn URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://baohanhtrondoi.com/search/" . urlencode($search_slug) . "&post_type=product";

    $results = [];
    
    // Gọi hàm curl lấy nội dung HTML về, nếu tạch thì dừng luôn
    $html = curl_get($url);
    if (!$html) {
        return $results;
    }

    // --- QUÉT HTML ---
    // Web này cấu trúc hơi lạ, sản phẩm nằm cả ở khung chính lẫn sidebar nên phải quét cả 2 nơi
    $search_blocks = [];

    // Regex 1: Quét khối sản phẩm ở khu vực chính (div class product-small)
    $pattern_main = '/<div[^>]*class="[^"]*product-small col[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is';

    // Regex 2: Quét sản phẩm nằm trong Sidebar (widget list)
    $pattern_sidebar = '/<ul[^>]*class="product_list_widget"[^>]*>(.*?)<\/ul>/is';
    
    // Bắt đầu nhặt dữ liệu từ khung chính
    if (preg_match_all($pattern_main, $html, $matches_main)) {
        $search_blocks = array_merge($search_blocks, $matches_main[1]);
    }

    // Nhặt tiếp dữ liệu từ sidebar nếu có
    if (preg_match($pattern_sidebar, $html, $matches_sidebar)) {
        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $matches_sidebar[1], $matches_sidebar_item)) {
            $search_blocks = array_merge($search_blocks, $matches_sidebar_item[1]);
        }
    }

    // Duyệt qua tất cả các khối HTML vừa tìm được
    foreach ($search_blocks as $block) {
        $product_url = '';
        $name        = '';
        $price_html  = '';
        $price_int   = 0;
        $image_url   = ''; 
        $brand       = ''; // Reset biến brand mỗi vòng lặp để không bị dính của thằng trước

        // --- LẤY TÊN VÀ LINK ---
        // Web này đặt tên lung tung, lúc thì dùng thẻ span, lúc thì thẻ p nên phải check cả 2 case
        if (preg_match('/<a[^>]*href="([^"]+)"[^>]*>.*?<span[^>]*class="product-title[^"]*"[^>]*>(.*?)<\/span>.*?<\/a>/is', $block, $m_name_sidebar)) {
             $product_url = trim($m_name_sidebar[1]);
             $name = trim(strip_tags($m_name_sidebar[2]));
        } 
        elseif (preg_match('/<p[^>]*class="[^"]*product-title[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $block, $m_name_main)) {
             $product_url = trim($m_name_main[1]);
             $name = trim(strip_tags($m_name_main[2]));
        } else {
             // Không lấy được tên thì bỏ qua luôn
             continue; 
        }

        // --- LẤY ẢNH ---
        // Tìm thẻ img và lấy đường dẫn trong src
        if (preg_match('/<img[^>]+src="([^"]+)"/i', $block, $m_img)) {
            $image_url = $m_img[1];
        }

        // --- LẤY GIÁ ---
        // Giá nằm trong thẻ bdi, nếu có nhiều thẻ thì lấy cái cuối cùng (thường là giá sau giảm)
        if (preg_match_all('/<bdi>(.*?)<\/bdi>/is', $block, $m_price_bdi)) {
            $price_html = end($m_price_bdi[1]); 
        } elseif (preg_match('/<span[^>]*class="amount">Liên hệ<\/span>/is', $block)) {
             $price_int = 0;
        } else {
            // Không có giá thì next
            continue;
        }
        
        // Dọn dẹp giá: xóa tag html, xóa ký tự lạ, ép kiểu về số nguyên
        if ($price_html) {
            $price_str = html_entity_decode($price_html, ENT_QUOTES, 'UTF-8');
            $price_str = preg_replace('/[\x{00A0}\s]+/u', '', $price_str);
            $price_int = (int) preg_replace('/[^\d]/', '', $price_str);
        }

        // --- NHẬN DIỆN THƯƠNG HIỆU ---
        $known_brands = [
            'Dell','HP','Acer','Asus','Lenovo','MSI','Apple','MacBook',
            'Surface','LG','Gigabyte','Samsung','Sony','Toshiba'
        ];

        foreach ($known_brands as $b) {
            // So sánh xem tên hãng có nằm trong tên sản phẩm không
            if (stripos($name, $b) !== false) {
                $brand = $b;
                // Gom MacBook về Apple
                if (strtolower($b) === 'macbook') $brand = 'Apple';
                break;
            }
        }

        // --- LƯU KẾT QUẢ ---
        // Lọc bớt rác: chỉ lấy nếu có tên, link và giá > 100k
        if ($name && $product_url && $price_int > 100000) { 
            $results[] = [
                'site'  => 'BaoHanhTronDoi',
                'name'  => $name,
                'price' => $price_int,
                'url'   => $product_url,
                'image' => $image_url,
                'brand' => $brand,
            ];
        }
    }

    return $results;
}
?>