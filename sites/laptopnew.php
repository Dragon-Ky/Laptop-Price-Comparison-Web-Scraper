<?php
// sites/laptopnew.php

function getLaptopNewProducts($query) {
    // 1. Chuẩn bị URL tìm kiếm
    // Với nền tảng Bizweb/Sapo, URL search thường là /search?query=...
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://laptopnew.vn/search?query=" . urlencode($search_slug);
    $base_domain = "https://laptopnew.vn";

    $results = [];

    // 2. Tải HTML về
    $html = curl_get($url);
    if (!$html) {
        return $results;
    }

    // --- CẮT KHỐI SẢN PHẨM (Chiến thuật Explode) ---
    // Cắt HTML dựa trên class bao quanh của thẻ div sản phẩm
    // Dựa trên HTML mẫu: <div class="product-item position-relative ...">
    $blocks = explode('class="product-item position-relative', $html);
    
    // Bỏ phần tử rác đầu tiên (header, menu...)
    array_shift($blocks);

    foreach ($blocks as $block) {
        $name = '';
        $product_url = '';
        $image_url = '';
        $price_int = 0;
        $old_price_int = 0;
        $brand = 'Khác'; 

        // Khởi tạo thông số (để tránh lỗi undefined)
        $cpu = ''; $ram = ''; $hdd = ''; $vga = ''; $screen = '';

        // --- A. LẤY TÊN VÀ URL ---
        // Tìm thẻ h3 chứa class item-title, bên trong có thẻ a
        if (preg_match('/<h3[^>]*class="item-title[^"]*"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $block, $m_name)) {
            $product_url = trim($m_name[1]);
            // Link thường là dạng tương đối (/laptop-dell...), cần nối thêm domain
            if (strpos($product_url, 'http') === false) {
                $product_url = $base_domain . $product_url;
            }
            $name = trim(strip_tags($m_name[2]));
        } else {
            // Không thấy tên thì bỏ qua luôn
            continue;
        }

        // --- B. LẤY ẢNH ---
        // Tìm class="thumb...", bên trong có img
        if (preg_match('/<a[^>]*class="thumb[^"]*"[^>]*>.*?<img[^>]*src="([^"]+)"/is', $block, $m_img)) {
            $image_url = trim($m_img[1]);
            // Link ảnh thường bắt đầu bằng //bizweb..., cần thêm https:
            if (strpos($image_url, '//') === 0) {
                $image_url = 'https:' . $image_url;
            }
        }

        // --- C. LẤY GIÁ ---
        // 1. Giá khuyến mãi (special-price)
        if (preg_match('/class="special-price[^"]*">([\d\.,]+)/is', $block, $m_price)) {
            $price_str = str_replace('.', '', $m_price[1]); // Xóa dấu chấm
            $price_int = (int) $price_str;
        }

        // 2. Giá gốc (old-price)
        if (preg_match('/class="old-price">([\d\.,]+)/is', $block, $m_old)) {
            $old_str = str_replace('.', '', $m_old[1]);
            $old_price_int = (int) $old_str;
        }

        // Nếu không có giá cũ, gán bằng giá hiện tại
        if ($old_price_int == 0 && $price_int > 0) {
            $old_price_int = $price_int;
        }

        // --- D. LẤY CẤU HÌNH (CHIẾN THUẬT CHECK ICON) ---
        // Hàm làm sạch text (vì HTML mẫu có rất nhiều xuống dòng và khoảng trắng)
        $cleanSpec = function($raw) {
            $txt = strip_tags($raw);
            $txt = preg_replace('/\s+/', ' ', $txt); // Thay thế nhiều khoảng trắng/xuống dòng thành 1 dấu cách
            return trim($txt);
        };

        // Tìm tất cả các thẻ li trong block
        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $block, $lis)) {
            foreach ($lis[1] as $li_content) {
                // Lấy nội dung text trong thẻ span
                $val = '';
                if (preg_match('/<span>(.*?)<\/span>/is', $li_content, $m_val)) {
                    $val = $cleanSpec($m_val[1]);
                }

                if (empty($val)) continue;

                // Check tên file ảnh icon để xác định loại thông số (Chính xác 100%)
                // icon_pro1: CPU
                if (strpos($li_content, 'icon_pro1.png') !== false) {
                    $cpu = $val;
                }
                // icon_pro3: RAM
                elseif (strpos($li_content, 'icon_pro3.png') !== false) {
                    $ram = $val;
                }
                // icon_pro4: Ổ cứng
                elseif (strpos($li_content, 'icon_pro4.png') !== false) {
                    $hdd = $val;
                }
                // icon_pro2: VGA
                elseif (strpos($li_content, 'icon_pro2.png') !== false) {
                    $vga = $val;
                }
                // icon_pro5: Màn hình
                elseif (strpos($li_content, 'icon_pro5.png') !== false) {
                    $screen = $val;
                }
            }
        }

        // --- E. XỬ LÝ HÃNG (BRAND) ---
        $known_brands = [
            'Dell', 'HP', 'Acer', 'Asus', 'Lenovo', 'MSI', 'Apple', 'MacBook', 
            'Surface', 'LG', 'Gigabyte', 'Samsung', 'Sony', 'Toshiba'
        ];
        foreach ($known_brands as $b) {
            if (stripos($name, $b) !== false) {
                $brand = $b;
                if (strtolower($brand) === 'macbook') $brand = 'Apple';
                break;
            }
        }

        // --- F. LƯU KẾT QUẢ ---
        if ($name && $price_int > 100000 && $product_url) {
            $results[] = [
                'site'      => 'LaptopNew',
                'name'      => $name,
                'brand'     => $brand,
                'price'     => $price_int,
                'old_price' => $old_price_int,
                'image'     => $image_url,
                'url'       => $product_url,
                'cpu'       => $cpu,
                'ram'       => $ram,
                'hdd'       => $hdd,
                'vga'       => $vga,
                'screen'    => $screen
            ];
        }
    }

    return $results;
}
?>