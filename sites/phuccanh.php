<?php
// sites/phucanh.php

function getPhucAnhProducts($query) {
    // 1. Chuẩn bị URL tìm kiếm
    // Đổi khoảng trắng thành dấu + để đúng chuẩn URL
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://www.phucanh.vn/tim?q=" . urlencode($search_slug); 

    $results = [];
    $html = curl_get($url); 
    
    // Không lấy được nội dung thì nghỉ luôn
    if (!$html) return $results;

    // --- CẮT HTML RA TỪNG KHỐI SẢN PHẨM ---
    // Dùng explode cắt cho lẹ vì thẻ li bị lồng nhau, regex dễ tạch
    $product_blocks = explode('<li class="p-item-group', $html);
    
    // Bỏ cái mảng đầu tiên đi vì nó là phần header/menu thừa trước khi vào list sản phẩm
    array_shift($product_blocks); 

    foreach ($product_blocks as $block) {
        $name = '';
        $product_url = '';
        $final_price_int = 0;
        // Khởi tạo mảng thông số rỗng
        $specs = ['cpu' => '', 'ram' => '', 'hdd' => '', 'vga' => '', 'screen' => ''];

        // --- LẤY TÊN VÀ LINK ---
        // Tìm thẻ a chứa tiêu đề h3
        if (preg_match('/<a[^>]*href="([^"]+)"[^>]*>.*?<h3[^>]*class="p-name">(.*?)<\/h3>.*?<\/a>/is', $block, $m_name)) {
            $product_url = trim($m_name[1]);
            $name = trim(strip_tags($m_name[2]));
        }
        else {
            // Không có tên thì next qua sản phẩm khác
            continue; 
        }

        // --- LẤY GIÁ ---
        // Đoạn này quan trọng: Phải xóa mấy cái giá ảo (display:none) hoặc giá VNPAY đi để tránh lấy nhầm
        $block_clean_price = preg_replace('/<span[^>]*style="[^"]*display:\s*none[^"]*"[^>]*>.*?<\/span>/is', '', $block);
        $block_clean_price = preg_replace('/Giá VNPAY/u', '', $block_clean_price);

        // Lọc lấy con số cuối cùng trong class p-price2
        if (preg_match('/<span[^>]*class="p-price2"[^>]*>(.*?)<\/span>/is', $block_clean_price, $m_price)) {
            $price_text = strip_tags($m_price[1]);
            $final_price_int = (int) preg_replace('/[^\d]/', '', $price_text);
        }

        // --- LẤY THÔNG SỐ (CHIẾN THUẬT LÀM SẠCH TEXT) ---
        
        // Biến tấu lại block HTML: thay thẻ xuống dòng bằng \n để dễ tách dòng
        $text_block = $block;
        $text_block = preg_replace('/<(li|br|tr|div)[^>]*>/i', "\n", $text_block); 
        $text_block = strip_tags($text_block); // Xóa sạch HTML còn lại
        $text_block = html_entity_decode($text_block, ENT_QUOTES, 'UTF-8'); // Xử lý lỗi font/ký tự lạ

        // Tách thành mảng các dòng văn bản
        $lines = explode("\n", $text_block);

        // Định nghĩa từ khóa để bắt dính cấu hình
        $keywords = [
            'cpu'    => ['CPU:', 'Vi xử lý:', 'Bộ VXL:', 'Processor:'],
            'ram'    => ['RAM:', 'Bộ nhớ:', 'Bộ nhớ RAM:'],
            'hdd'    => ['Ổ cứng:', 'HDD:', 'SSD:', 'Storage:'],
            'vga'    => ['Card:', 'VGA:', 'Đồ họa:', 'Card màn hình:', 'VGA onboard'],
            'screen' => ['Màn hình:', 'LCD:', 'Kích thước màn hình:']
        ];

        // Quét từng dòng text, so khớp với từ khóa
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            foreach ($keywords as $field => $labels) {
                // Nếu thông số này chưa có dữ liệu thì mới đi tìm
                if ($specs[$field] === '') {
                    foreach ($labels as $label) {
                        // Tìm thấy từ khóa (không phân biệt hoa thường)
                        if (stripos($line, $label) !== false) {
                            // Cắt bỏ từ khóa, lấy phần nội dung ngon lành phía sau
                            $value = str_ireplace($label, '', $line);
                            $specs[$field] = trim($value, " \t\n\r\0\x0B:-");
                            break 2; // Tìm thấy rồi thì thoát vòng lặp ngay cho đỡ tốn resource
                        }
                    }
                }
            }
        }

        // --- LƯU KẾT QUẢ ---
        // Chỉ lấy hàng có tên, link và giá trên 1 triệu (lọc rác phụ kiện)
        if ($name && $product_url && $final_price_int > 1000000) { 
            // Fix link nếu thiếu domain
            if (strpos($product_url, 'http') === false) {
                $product_url = 'https://www.phucanh.vn' . $product_url;
            }
            
            $results[] = [
                'site'      => 'PhucAnh', 
                'name'      => $name,
                'price'     => $final_price_int,
                'url'       => $product_url,
                'cpu'       => $specs['cpu'],
                'ram'       => $specs['ram'],
                'hdd'       => $specs['hdd'],
                'vga'       => $specs['vga'],
                'screen'    => $specs['screen'],
            ];
        }
    }

    return $results;
}
?>