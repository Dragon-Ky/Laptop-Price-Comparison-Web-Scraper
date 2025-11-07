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
            $name = $url = $price = '';

            if (preg_match('/<a[^>]*href="([^"]+)"[^>]*>\s*<h3[^>]*class="[^"]*product-title[^"]*"[^>]*>(.*?)<\/h3>/is', $block, $m)) {
                $url = 'https://nguyencongpc.vn' . trim($m[1]);
                $name = trim(strip_tags($m[2]));
            }

            if (preg_match('/<div[^>]*class="[^"]*\bproduct-price-main\b[^"]*"[^>]*>([\s\S]*?)<\/div>/isu', $block, $m_price)) {
                $price_html = strip_tags($m_price[1]);
            }
            elseif (preg_match('/<p[^>]*class="[^"]*product-market-price[^"]*"[^>]*>(.*?)<\/p>/is', $block, $m)) {
                $price = $m[1];
            }

            

            $price_int = (int) preg_replace('/[^\d]/', '', strip_tags($price));
            if ($name && $price_int > 100000) {
                $results[] = [
                    'site' => 'NguyenCongPC',
                    'name' => $name,
                    'price' => $price_int,
                    'url' => $url
                ];
            }
        }
    }
    return $results;
}


?>

