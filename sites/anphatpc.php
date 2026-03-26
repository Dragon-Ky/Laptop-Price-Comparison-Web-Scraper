<?php
// sites/anphatpc.php

function getAnPhatPCProducts($query) {
    // 1. Chuẩn bị URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://www.anphatpc.com.vn/tim?q=" . urlencode($search_slug);
    $base_domain = "https://www.anphatpc.com.vn";

    $results = [];

    // 2. Tải HTML
    $html = curl_get($url);
    if (!$html) return $results;

    // --- CHIẾN THUẬT CẮT CHUỖI MỚI (AN TOÀN HƠN) ---
    // Chỉ cắt bằng cụm ngắn nhất định danh sản phẩm để tránh lỗi khi class thay đổi
    // HTML mẫu: <div class="p-item js-p-item summary-loaded"...>
    $blocks = explode('<div class="p-item', $html);
    
    // Bỏ phần rác đầu tiên
    array_shift($blocks);

    foreach ($blocks as $block) {
        // Biến tạm
        $name = '';
        $url_sp = '';
        $image = '';
        $price = 0;
        $price_old = 0;
        $brand = 'Khác';
        
        // Khởi tạo specs
        $specs = ['cpu' => '', 'ram' => '', 'hdd' => '', 'vga' => '', 'screen' => ''];

        // --- A. LẤY TÊN VÀ URL ---
        // Tìm thẻ a có class p-name (chứa h3 hoặc không)
        // Regex này bắt: <a ... class="p-name"> ... <h3>TÊN</h3> ... </a>
        if (preg_match('/class="p-name"[^>]*href="([^"]+)"[^>]*>.*?<h3>(.*?)<\/h3>/is', $block, $m_name)) {
            $url_sp = trim($m_name[1]);
            $name = trim(strip_tags($m_name[2]));
            
            // Fix link thiếu domain
            if (strpos($url_sp, 'http') === false) {
                $url_sp = $base_domain . $url_sp;
            }
        } 
        // Dự phòng: Nếu cấu trúc h3 thay đổi, lấy text trong thẻ a luôn
        elseif (preg_match('/class="p-name"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $block, $m_name_bk)) {
            $url_sp = trim($m_name_bk[1]);
            $name = trim(strip_tags($m_name_bk[2]));
            if (strpos($url_sp, 'http') === false) $url_sp = $base_domain . $url_sp;
        }
        else {
            continue; // Không lấy được tên thì bỏ qua
        }

        // --- B. LẤY ẢNH ---
        // Tìm class p-img chứa thẻ img
        if (preg_match('/class="p-img"[^>]*>.*?<img[^>]+src="([^"]+)"/is', $block, $m_img)) {
            $image = trim($m_img[1]);
            if (strpos($image, 'http') === false) $image = $base_domain . $image;
        }

        // --- C. LẤY GIÁ ---
        // 1. Giá hiện tại (p-price)
        // Quét class="p-price", lấy nội dung bên trong (bất kể là thẻ span hay p)
        if (preg_match('/class="p-price"[^>]*>(.*?)<\/(?:span|p|div)>/is', $block, $m_price)) {
            $p_str = strip_tags($m_price[1]); // Xóa chữ đ, khoảng trắng
            $price = (int) preg_replace('/[^\d]/', '', $p_str);
        }

        // 2. Giá cũ (p-old-price)
        if (preg_match('/class="p-old-price"[^>]*>(.*?)<\/del>/is', $block, $m_old)) {
            $old_str = strip_tags($m_old[1]);
            $price_old = (int) preg_replace('/[^\d]/', '', $old_str);
        }

        // Nếu không có giá cũ, gán bằng giá hiện tại
        if ($price_old == 0 && $price > 0) $price_old = $price;

        // --- D. LẤY CẤU HÌNH (QUÉT DATA-INFO) ---
        // AnPhat để thông số trong attribute data-info="KEY" và giá trị trong div class="txt"
        // Dùng preg_match_all để lấy toàn bộ danh sách specs trong block này
        
        if (preg_match_all('/data-info="([^"]+)"[^>]*>.*?<div class="txt">(.*?)<\/div>/is', $block, $matches_specs)) {
            // $matches_specs[1]: Danh sách tên (CPU, RAM...)
            // $matches_specs[2]: Danh sách giá trị (Core i5, 8GB...)
            
            foreach ($matches_specs[1] as $index => $label) {
                $val = trim(strip_tags($matches_specs[2][$index]));
                $lbl = mb_strtolower(trim($label), 'UTF-8');

                // Mapping từ khóa
                if (strpos($lbl, 'cpu') !== false || strpos($lbl, 'vi xử lý') !== false) {
                    $specs['cpu'] = $val;
                }
                elseif (strpos($lbl, 'ram') !== false || strpos($lbl, 'bộ nhớ') !== false) {
                    $specs['ram'] = $val;
                }
                elseif (strpos($lbl, 'ổ cứng') !== false || strpos($lbl, 'ssd') !== false) {
                    $specs['hdd'] = $val;
                }
                elseif (strpos($lbl, 'vga') !== false || strpos($lbl, 'đồ họa') !== false) {
                    $specs['vga'] = $val;
                }
                elseif (strpos($lbl, 'màn hình') !== false || strpos($lbl, 'inch') !== false) {
                    $specs['screen'] = $val;
                }
            }
        }

        // --- E. XỬ LÝ HÃNG ---
        $known_brands = ['Dell', 'HP', 'Acer', 'Asus', 'Lenovo', 'MSI', 'Apple', 'MacBook', 'Surface', 'LG', 'Gigabyte', 'Samsung'];
        foreach ($known_brands as $b) {
            if (stripos($name, $b) !== false) {
                $brand = $b;
                if (strtolower($brand) === 'macbook') $brand = 'Apple';
                break;
            }
        }

        // --- F. LƯU KẾT QUẢ ---
        if ($name && $price > 100000 && $url_sp) {
            $results[] = [
                'site'      => 'AnPhatPC',
                'name'      => $name,
                'brand'     => $brand,
                'price'     => $price,
                'old_price' => $price_old,
                'image'     => $image,
                'url'       => $url_sp,
                'cpu'       => $specs['cpu'],
                'ram'       => $specs['ram'],
                'hdd'       => $specs['hdd'],
                'vga'       => $specs['vga'],
                'screen'    => $specs['screen']
            ];
        }
    }

    return $results;
}
?>