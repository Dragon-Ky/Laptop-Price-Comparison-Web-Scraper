<?php
session_start();

define('BASE_PATH', dirname(dirname(dirname(__FILE__)))); 

require_once BASE_PATH . "/models/product/BookmarkModel.php";

// Lấy danh sách sản phẩm đã bookmark
$controller = new BookmarkModel();
$bookmarkedProducts = $controller->getBookmarksByUser($_SESSION['user_id']); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Sản phẩm đã bookmark</title>
</head>
<body>
<main >
    <div class="flex min-h-screen bg-gray-100">

    <?php 
            $active_page = 'Bookmarks'; 
            define('BASE', dirname(dirname(__FILE__)));
            require_once BASE . '/includes/sidebar.php';
    ?>
    
    <main class="flex-1 p-6 overflow-hidden">
         <?php require_once BASE_PATH ."/models/product/Bookmark_View.php"; ?>

    </main>
</div>

</body>
</html>
