<?php
// sites/nguyencongpc.php

function getNguyenCongPCProducts($query) {
    // Xử lý từ khóa tìm kiếm: thay khoảng trắng bằng dấu + để đúng chuẩn URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://nguyencongpc.vn/tim?q=" . urlencode($search_slug);
    $results = [];

    // Gọi curl lấy nội dung html về, nếu lỗi thì trả về mảng rỗng
    $html = curl_get($url);
    if (!$html) return $results;

    // Pattern regex này dùng để cắt khối HTML chứa thông tin từng sản phẩm (product-item)
    // Cắt đến thẻ div tooltip để giới hạn phạm vi, tránh lấy thừa
    $pattern = '/<div[^>]*class="[^"]*product-item[^"]*"[^>]*>(.*?)<\/div>\s*<div class="tooltip/is';

    if (preg_match_all($pattern, $html, $matches)) {

        // Duyệt qua từng khối sản phẩm tìm thấy
        foreach ($matches[1] as $block) {

            $name = $url = $image = "";
            $price_new = 0;
            $price_old = 0;
            $brand     = "";

            // --- LẤY TÊN VÀ LINK SẢN PHẨM ---
            // Tìm thẻ a chứa link và thẻ h3 chứa tên
            if (preg_match('/<a[^>]*href="([^"]+)"[^>]*>.*?<h3[^>]*class="[^"]*product-title[^"]*"[^>]*>(.*?)<\/h3>/is', $block, $m)) {
                $url  = 'https://nguyencongpc.vn' . trim($m[1]);
                $name = trim(strip_tags($m[2]));
            }

            // --- LẤY ẢNH ---
            // Tìm thẻ img, nếu link ảnh thiếu domain thì nối thêm vào cho đủ
            if (preg_match('/<img[^>]+src="([^"]+)"/i', $block, $m_img)) {
                $img_path = trim($m_img[1]);
                $image = (strpos($img_path, 'http') === false)
                       ? 'https://nguyencongpc.vn' . $img_path
                       : $img_path;
            }

            // --- LẤY GIÁ MỚI (GIÁ BÁN) ---
            // Tìm giá trong class product-price-main, lọc bỏ chữ đ để lấy số
            if (preg_match('/<div[^>]*class="[^"]*product-price-main[^"]*"[^>]*>\s*([\d\.]+)đ/i', $block, $m_price)) {
                $price_new = (int) preg_replace('/[^\d]/', '', $m_price[1]);
            }

            // --- LẤY GIÁ CŨ (GIÁ NIÊM YẾT) ---
            // Tìm trong class product-market-price
            if (preg_match('/class="[^"]*product-market-price[^"]*"[^>]*>\s*([^<]+)/i', $block, $m_market)) {
                $price_old = (int) preg_replace('/[^\d]/', '', $m_market[1]);
            }

            // Nếu chưa tìm thấy giá cũ thì tìm tiếp trong class card-price-origin (dự phòng)
            if ($price_old == 0 && preg_match('/class="[^"]*card-price-origin[^"]*"[^>]*>\s*([^<]+)/i', $block, $m_origin)) {
                $price_old = (int) preg_replace('/[^\d]/', '', $m_origin[1]);
            }

            // --- CHỐT GIÁ CUỐI CÙNG ---
            // Ưu tiên lấy giá mới, nếu không có thì mới lấy giá cũ
            $final_price = $price_new > 0 ? $price_new : $price_old;

            // --- NHẬN DIỆN THƯƠNG HIỆU ---
            // Danh sách các hãng phổ biến để đối chiếu
            $known_brands = [
                'Dell','HP','Acer','Asus','Lenovo','MSI','Apple','MacBook',
                'Surface','LG','Gigabyte','Samsung','Sony','Toshiba'
            ];

            foreach ($known_brands as $b) {
                // Kiểm tra xem tên sản phẩm có chứa tên hãng không
                if (stripos($name, $b) !== false) {
                    $brand = $b;
                    // Gom MacBook về chung với Apple
                    if (strtolower($b) === 'macbook') $brand = 'Apple';
                    break; // Tìm thấy rồi thì thoát vòng lặp
                }
            }

            // --- LƯU KẾT QUẢ ---
            // Chỉ thêm vào danh sách nếu có tên và giá trị trên 100k
            if ($name && $final_price > 100000) {
                $results[] = [
                    'site'      => 'NguyenCongPC',
                    'name'      => $name,
                    'price'     => $final_price,
                    'price_old' => $price_old,
                    'url'       => $url,
                    'image'     => $image,
                    'brand'     => $brand
                ];
            }
        }
    }

    return $results;
}
?>