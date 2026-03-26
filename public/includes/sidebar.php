<?php 
$BASE_URL = "/public"; 
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<nav class="bg-white shadow-sm sticky top-0 z-50">
    
<aside class="fixed top-0 left-0 w-64 h-screen bg-white shadow-md p-4 flex flex-col z-50 overflow-y-auto">
    
    <div class="mt-2 mb-6 text-xl font-bold text-center text-blue-600"> <a href="<?= $BASE_URL ?>/Daskboard/Daskboard.php">LAPTOPSS</a></div>

    <nav class="space-y-3 text-gray-700 flex-1 " >
        <a href="<?= $BASE_URL ?>/Daskboard/Daskboard.php" class="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-md transition-colors">
            <i class="fa-solid fa-house w-5 text-center"></i> 
            <span>TRANG CHỦ</span>
        </a>
        <a href="<?= $BASE_URL ?>/Search/Search.php" class="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-md transition-colors">
            <i class="fa-solid fa-magnifying-glass w-5 text-center"></i> 
            <span>TÌM KIẾM</span>
        </a>
        <a href="<?= $BASE_URL ?>/Bookmark/Bookmark.php" class="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-md transition-colors">
            <i class="fa-regular fa-bookmark w-5 text-center"></i> 
            <span>YÊU THÍCH</span>
        </a>
        <a href="<?= $BASE_URL ?>/ChatBot/ChatBot_view.php" class="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-md transition-colors">
            <i class="fa-solid fa-robot w-5 text-center"></i>   
            <span>TRỢ LÝ ẢO</span>
        </a>
    </nav> <div class="border-t border-gray-200 pt-3"> <a href="/models/user/logout.php" class="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-md transition-colors text-red-600 font-semibold">
            <i class="fa-solid fa-sign-out-alt w-5 text-center"></i>
            <span>Đăng Xuất</span>
        </a>
    </div>

</aside>
</nav>