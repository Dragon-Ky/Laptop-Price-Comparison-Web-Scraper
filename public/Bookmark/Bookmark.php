<?php
session_start();
if (!isset($_SESSION['user_id'])) {
   
    header("Location: /public/user/login.php"); 
    exit(); 
}

define('BASE_PATH', dirname(dirname(dirname(__FILE__)))); 

require_once BASE_PATH . "/models/product/BookmarkModel.php";

// 2. Lấy danh sách sản phẩm (Lúc này chắc chắn đã có user_id)
$controller = new BookmarkModel();
$bookmarkedProducts = $controller->getBookmarksByUser($_SESSION['user_id']); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Bookmark Laptop - Trang Yêu Thích Sản Phẩm</title>

<link rel="icon" type="image/png" href="/public/images/logo_icon.png">
    
<link rel="shortcut icon" href="/public/images/logo_icon.png">

</head>
<body>
<main >
    <div class="flex min-h-screen bg-gray-100">

    <?php 
            $active_page = 'Bookmarks'; 
            define('BASE', dirname(dirname(__FILE__)));
            require_once BASE . '/includes/sidebar.php';
    ?>
    
    <main class="flex-1 ml-64 p-6 overflow-hidden">
         <?php require_once BASE_PATH ."/models/product/Bookmark_View.php"; ?>

    </main>
</div>

</body>
</html>
