<?php
// sites/laptopsvn.php

function getLaptopsProducts($query) {
    // Xử lý từ khóa: thay khoảng trắng bằng dấu + để đúng chuẩn URL tìm kiếm của site này
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://laptops.vn/?s=" . urlencode($search_slug) . "&post_type=product&dgwt_wcas=1";

    $results = [];
    
    // Gọi hàm curl lấy nội dung HTML về, nếu lỗi thì dừng luôn
    $html = curl_get($url); 
    if (!$html) {
        return $results;
    }

    // Pattern regex này dùng để cắt lấy khối HTML chứa thông tin sản phẩm
    // Quét từ div product-small đến div count để bao trọn nội dung cần thiết
    $pattern = '/<div class="product-small[^>]*>([\s\S]*?)<div class="count">/is';
    
    if (preg_match_all($pattern, $html, $matches)) {
        foreach ($matches[0] as $block) {
            $name = '';
            $price_int = 0;
            $product_url = '';
            $image_url = '';
            $brand = '';

            // Khởi tạo trước mấy biến cấu hình rỗng để tránh lỗi undefined index nếu không tìm thấy
            $cpu = '';
            $ram = '';
            $hdd = ''; // Lưu ý: Sẽ lấy dữ liệu từ class 'ssd' gán vào đây
            $vga = '';
            $screen = '';

            // --- LẤY TÊN VÀ LINK ---
            if (preg_match('/<a href="([^"]+)"[^>]*aria-label="([^"]+)"/is', $block, $m_name)) {
                $product_url = trim($m_name[1]);
                $name = trim(strip_tags($m_name[2]));
            }

            // --- LẤY GIÁ ---
            // Ưu tiên lấy giá sale trước, nếu không có thì lấy giá thường
            $price_html = '';
            if (preg_match('/<span[^>]*class="[^"]*sale-price[^"]*"[^>]*>[\s\S]*?<bdi>(.*?)<\/bdi>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } elseif (preg_match('/<span[^>]*class="[^"]*regular-price[^"]*"[^>]*>[\s\S]*?<bdi>(.*?)<\/bdi>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            }
            
            // Dọn dẹp chuỗi giá: xóa tag HTML, giải mã ký tự lạ và chỉ giữ lại số
            $price_str = strip_tags($price_html);
            $price_str = html_entity_decode($price_str, ENT_QUOTES, 'UTF-8');
            $price_int = (int) preg_replace('/[^\d]/', '', $price_str);

            // --- LẤY ẢNH ---
            if (preg_match('/<img[^>]+src="([^"]+)"/i', $block, $m_img)) {
                $image_url = $m_img[1];
            }

            // --- LẤY CẤU HÌNH (SPECS) ---
            // Viết cái hàm nhỏ này để làm sạch text: xóa tag HTML, sửa lỗi font chữ
            $cleanSpec = function($str) {
                $str = strip_tags($str);
                $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
                $str = preg_replace('/[\s\x{00A0}]+/u', ' ', $str); 
                return trim($str);
            };

            // 1. CPU
            if (preg_match('/<div[^>]*class="[^"]*cpu[^"]*"[^>]*>.*?<\/i>(.*?)<\/div>/is', $block, $m_cpu)) {
                $cpu = $cleanSpec($m_cpu[1]);
            }
            // 2. RAM
            if (preg_match('/<div[^>]*class="[^"]*ram[^"]*"[^>]*>.*?<\/i>(.*?)<\/div>/is', $block, $m_ram)) {
                $ram = $cleanSpec($m_ram[1]);
            }
            // 3. HDD (Chỗ này quan trọng: web để class là ssd nhưng mình gán vào biến hdd)
            if (preg_match('/<div[^>]*class="[^"]*ssd[^"]*"[^>]*>.*?<\/i>(.*?)<\/div>/is', $block, $m_ssd)) {
                $hdd = $cleanSpec($m_ssd[1]);
            }
            // 4. VGA
            if (preg_match('/<div[^>]*class="[^"]*vga[^"]*"[^>]*>.*?<\/i>(.*?)<\/div>/is', $block, $m_vga)) {
                $vga = $cleanSpec($m_vga[1]);
            }
            // 5. Screen (Màn hình)
            if (preg_match('/<div[^>]*class="[^"]*monitor[^"]*"[^>]*>.*?<\/i>(.*?)<\/div>/is', $block, $m_monitor)) {
                $screen = $cleanSpec($m_monitor[1]);
            }

            // --- XÁC ĐỊNH THƯƠNG HIỆU ---
            $known_brands = [
                'Dell','HP','Acer','Asus','Lenovo','MSI','Apple','MacBook',
                'Surface','LG','Gigabyte','Samsung','Sony','Toshiba'
            ];
            foreach ($known_brands as $b) {
                // Kiểm tra xem tên sản phẩm có chứa tên hãng không
                if (stripos($name, $b) !== false) {
                    $brand = $b;
                    // Gom MacBook về chung nhà Apple
                    if (strtolower($b) === 'macbook') $brand = 'Apple';
                    break;
                }
            }

            // --- LƯU KẾT QUẢ ---
            // Chỉ lấy sản phẩm có tên, có link và giá trị trên 100k
            if ($name && $price_int > 100000 && $product_url) {
                $results[] = [
                    'site'      => 'LaptopsVn',
                    'name'      => $name,
                    'price'     => $price_int,
                    'url'       => $product_url,
                    'image'     => $image_url,
                    'brand'     => $brand,
                    'cpu'       => $cpu,
                    'ram'       => $ram,
                    'hdd'       => $hdd,   // HTML là SSD nhưng gán vào key hdd
                    'vga'       => $vga,
                    'screen'    => $screen
                ];
            }
        }
    }

    return $results;
}
?>