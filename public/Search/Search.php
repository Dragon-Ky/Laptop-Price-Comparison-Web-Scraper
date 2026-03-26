<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Search Laptop - Trang Tìm Kiếm</title>

    <link rel="icon" type="image/png" href="/public/images/logo_icon.png">
        
    <link rel="shortcut icon" href="/public/images/logo_icon.png">
    <script src="https://cdn.tailwindcss.com"></script> 
    <link rel="stylesheet" href="/assets/CSS/search.css">
</head>
<body>

<div class="flex min-h-screen bg-gray-100">

    <?php 
    $active_page = 'Search'; 
    define('BASE', dirname(dirname(__FILE__)));
    
    require_once BASE . '/includes/sidebar.php';
    ?>

    <main class="main-content flex-1 ml-64">
        
        <h2 class="search-title">Tra Cứu Giá Laptop</h2>
        
        <form action="../../controllers/Search/search_new.php" method="get" class="search-box">
            <input type="text" name="products" class="search-input" placeholder="Nhập tên laptop (vd: MacBook Air M1)..." required>
            <button type="submit" class="search-btn">Tìm kiếm</button>
        </form>

    </main>

</div>

</body>
</html>