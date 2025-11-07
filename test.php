<?php
$html = file_get_contents('debug20251106_154205.html'); // hoặc dùng biến chứa HTML

// Lấy link sản phẩm
preg_match('/<a[^>]*href="([^"]+)"[^>]*class="productName[^"]*"[^>]*>/', $html, $link);
$link = $link[1] ?? '';

// Lấy tên sản phẩm
preg_match('/<a[^>]*class="productName[^"]*"[^>]*>(.*?)<\/a>/', $html, $name);
$name = trim(strip_tags($name[1] ?? ''));

// Lấy giá sản phẩm
preg_match('/<p class="pdPrice">.*?<span>\s*([\d.,]+)₫\s*<\/span>/s', $html, $price);
$price = $price[1] ?? '';

// Lấy mã sản phẩm
preg_match('/<li><strong>Mã sản phẩm:\s*<\/strong><span>([^<]+)<\/span><\/li>/', $html, $code);
$code = trim($code[1] ?? '');

// Lấy thương hiệu
preg_match('/<li><strong>Thương hiệu:\s*<\/strong><span>([^<]+)<\/span><\/li>/', $html, $brand);
$brand = trim($brand[1] ?? '');

// Lấy link ảnh
preg_match('/<img[^>]*src="([^"]+)"[^>]*alt="[^"]*"[^>]*>/', $html, $img);
$img = $img[1] ?? '';

// Lấy mô tả ngắn
preg_match('/<span class="short-des">\s*(.*?)\s*<\/span>/s', $html, $desc);
$desc = trim(strip_tags($desc[1] ?? ''));

$result = [
    'name' => $name,
    'link' => $link,
    'price' => $price,
    'code' => $code,
    'brand' => $brand,
    'image' => $img,
    'description' => $desc
];

print_r($result);
