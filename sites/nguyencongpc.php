<?php
// sites/nguyencongpc.php

function getNguyenCongPCProducts($query) {
    $search_slug = str_replace(' ', '+', trim(strtolower($query)));
    $url = "https://nguyencongpc.vn/tim?q=" . urlencode($search_slug);
    $results = [];

    $html = curl_get($url);
    if (!$html) return $results;

    $pattern = '/<div[^>]*class="[^"]*product-item[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>/is';
    if (preg_match_all($pattern, $html, $matches)) {
        foreach ($matches[1] as $block) {
            $name = $url = '';
            $price_new = 0;
            $price_old = 0;

            // 1. Lấy Tên và URL
            if (preg_match('/<a[^>]*href="([^"]+)"[^>]*>\s*<h3[^>]*class="[^"]*product-title[^"]*"[^>]*>(.*?)<\/h3>/is', $block, $m)) {
                $url = 'https://nguyencongpc.vn' . trim($m[1]);
                $name = trim(strip_tags($m[2]));
            }

            // 2. Lấy Giá Mới (Giá bán hiện tại - product-price-main)
            // Dùng if riêng biệt
            if (preg_match('/<div[^>]*class="[^"]*\bproduct-price-main\b[^"]*"[^>]*>([\s\S]*?)<\/div>/isu', $block, $m_price)) {
                $price_new = (int) preg_replace('/[^\d]/', '', strip_tags($m_price[1]));
            }

            // 3. Lấy Giá Cũ (Giá thị trường - product-market-price)
            // Dùng if riêng biệt để không bị bỏ qua
            if (preg_match('/<p[^>]*class="[^"]*product-market-price[^"]*"[^>]*>(.*?)<\/p>/is', $block, $m_market)) {
                $price_old = (int) preg_replace('/[^\d]/', '', strip_tags($m_market[1]));
            }

            // Xử lý logic chọn giá hiển thị:
            // Nếu có giá mới thì lấy giá mới, nếu không thì lấy giá cũ
            $final_price = ($price_new > 0) ? $price_new : $price_old;

            if ($name && $final_price > 100000) {
                $results[] = [
                    'site'      => 'NguyenCongPC',
                    'name'      => $name,
                    'price'     => $final_price,     // Giá bán thực tế
                    'price_old' => $price_old,       // Giá gốc (để hiển thị so sánh nếu cần)
                    'url'       => $url
                ];
            }
        }
    }
    return $results;
}


?>

