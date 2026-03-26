<?php
// sites/laptopxachtay.php

function getLaptopXachTayProducts($query) {
    // Xử lý từ khóa tìm kiếm: đổi khoảng trắng thành dấu + cho chuẩn URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://laptopxachtayshop.com/tim-kiem/" . urlencode($search_slug);
    $base_url = "https://laptopxachtayshop.com"; 

    $results = [];
    
    // Gọi hàm lấy nội dung HTML, nếu tạch (không có dữ liệu) thì dừng luôn
    $html = curl_get($url); 
    if (!$html) {
        return $results;
    }

    // Pattern này dùng để cắt lấy khối HTML của từng sản phẩm (class product-small)
    // Dùng (.*?) quét lỏng lẻo để lấy nội dung bên trong mà không bị sót
    $pattern = '/<div[^>]*class="[^"]*product-small box[^"]*"[^>]*>(.*?)<span class="hn-menu-order-data"/is';
    
    if (preg_match_all($pattern, $html, $matches)) {
        foreach ($matches[1] as $block) {
            $name = '';
            $price_str = '';
            $old_price_str = '';
            $product_url = '';
            $img_url = '';
            $brand = 'Other'; 
            
            // Khởi tạo bộ khung thông số rỗng
            $specs = [
                'cpu' => '', 'ram' => '', 'hdd' => '', 'vga' => '', 'screen' => ''
            ];

            // --- LẤY TÊN VÀ LINK ---
            if (preg_match('/<p[^>]*class="[^"]*woocommerce-loop-product__title[^"]*"[^>]*>\s*<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>\s*<\/p>/is', $block, $m_name)) {
                $product_url = $m_name[1];
                $name = trim(strip_tags($m_name[2]));
            }

            // --- LẤY ẢNH ---
            if (preg_match('/<div[^>]*class="[^"]*box-image[^"]*"[^>]*>.*?<img[^>]*src="([^"]+)"/is', $block, $m_img)) {
                $img_url = $m_img[1];
                // Nếu ảnh là đường dẫn tương đối (thiếu domain) thì nối thêm vào
                if (strpos($img_url, 'http') === false) $img_url = $base_url . $img_url;
            }

            // --- LẤY GIÁ ---
            // Ưu tiên tìm giá khuyến mãi (thẻ ins) trước
            if (preg_match('/<ins[^>]*>(.*?)<\/ins>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } elseif (preg_match('/<span[^>]*class="[^"]*(?:spanprice|woocommerce-Price-amount)[^"]*"[^>]*>(.*?)<\/span>/is', $block, $m_price)) {
                $price_html = $m_price[1];
            } else {
                $price_html = '';
            }

            // Tìm giá cũ (giá gạch ngang trong thẻ del)
            if (preg_match('/<del[^>]*>(.*?)<\/del>/is', $block, $m_old_price)) {
                $old_price_str = $m_old_price[1];
            }

            // --- LẤY CẤU HÌNH (SPECS) ---
            // Quét toàn bộ các thẻ <li> trong khối sản phẩm để nhặt thông số
            if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $block, $li_matches)) {
                
                foreach ($li_matches[0] as $li_html) {
                    // Lấy giá trị nằm trong class "text-var" trước
                    $value = '';
                    if (preg_match('/class=["\']text-var["\'][^>]*>(.*?)<\/span>/is', $li_html, $m_val)) {
                        $value = trim(strip_tags($m_val[1]));
                    }
                    
                    // Nếu dòng này không có giá trị gì thì bỏ qua, đỡ tốn time check tiếp
                    if (empty($value)) continue;

                    // Nhận diện loại thông số (CPU, RAM...) dựa vào tên class hoặc từ khóa
                    // 1. CPU
                    if (strpos($li_html, 'pa_cpu') !== false || stripos($li_html, 'CPU') !== false) {
                        $specs['cpu'] = $value;
                    }
                    // 2. RAM
                    elseif (strpos($li_html, 'pa_ram') !== false || stripos($li_html, 'Ram') !== false || stripos($li_html, 'Bộ nhớ') !== false) {
                        $specs['ram'] = $value;
                    }
                    // 3. Ổ cứng
                    elseif (strpos($li_html, 'pa_ssd') !== false || strpos($li_html, 'pa_hdd') !== false || stripos($li_html, 'Ổ cứng') !== false) {
                        $specs['hdd'] = $value;
                    }
                    // 4. VGA (Card đồ họa)
                    elseif (strpos($li_html, 'pa_vga') !== false || stripos($li_html, 'Đồ họa') !== false || stripos($li_html, 'VGA') !== false) {
                        $specs['vga'] = $value;
                    }
                    // 5. Màn hình
                    elseif (strpos($li_html, 'pa_inch') !== false || stripos($li_html, 'Màn hình') !== false || stripos($li_html, 'LCD') !== false) {
                        $specs['screen'] = $value;
                    }
                }
            }

            // --- LÀM SẠCH GIÁ ---
            // Hàm nhỏ xử lý chuỗi giá: xóa tag html, giải mã ký tự lạ, giữ lại số
            $cleanPrice = function($str) {
                $str = strip_tags($str);
                $str = html_entity_decode(trim($str), ENT_QUOTES, 'UTF-8');
                $str = preg_replace('/[\x{00A0}\s]+/u', '', $str);
                $str = preg_replace('/8[.,]?363$/', '', $str); // Fix cái lỗi đuôi giá ảo đặc thù của site này
                return (int) preg_replace('/[^\d]/', '', $str);
            };

            $price_int = $cleanPrice($price_html);
            $old_price_int = $cleanPrice($old_price_str);
            
            // Nếu không có giá cũ thì gán bằng giá hiện tại cho đủ bộ
            if ($old_price_int === 0) $old_price_int = $price_int;

            // --- XỬ LÝ BRAND (HÃNG) ---
            if ($name) {
                $known_brands = ['Dell', 'HP', 'Acer', 'Asus', 'Lenovo', 'MSI', 'Apple', 'MacBook', 'Surface', 'LG', 'Gigabyte', 'Samsung', 'Sony', 'Toshiba'];
                foreach ($known_brands as $b) {
                    if (stripos($name, $b) !== false) {
                        $brand = $b;
                        // Gom MacBook về chung nhà Apple
                        if (strtolower($brand) === 'macbook') $brand = 'Apple';
                        break; 
                    }
                }
            }

            // --- LƯU KẾT QUẢ ---
            // Điều kiện lọc: Phải có Tên, có Link và Giá > 100k (tránh rác)
            if ($name && $price_int > 100000 && $product_url) {
                $results[] = [
                    'site'      => 'LaptopXachTay',
                    'name'      => $name,
                    'price'     => $price_int,
                    'old_price' => $old_price_int,
                    'brand'     => $brand,
                    'image'     => $img_url,
                    'url'       => $product_url,
                    'cpu'       => $specs['cpu'],
                    'ram'       => $specs['ram'],
                    'hdd'       => $specs['hdd'],
                    'vga'       => $specs['vga'],
                    'screen'    => $specs['screen']
                ];
            }
        }
    }

    return $results;
}
?>