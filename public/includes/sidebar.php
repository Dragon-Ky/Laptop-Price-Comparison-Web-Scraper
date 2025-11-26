<?php 
$BASE_URL = "/public"; 
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<aside class="w-64 bg-white shadow-md p-4 min-h-screen flex flex-col">
    
    <div class="mb-6 text-xl font-bold text-center text-blue-600">My App</div>

    <nav class="space-y-3 text-gray-700 flex-1">
        <a href="<?= $BASE_URL ?>/Daskboard/Daskboard.php" class="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-md transition-colors">
            <i class="fa-solid fa-house w-5 text-center"></i> 
            <span>Dashboard</span>
        </a>
        <a href="<?= $BASE_URL ?>/Search/Search.php" class="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-md transition-colors">
            <i class="fa-solid fa-magnifying-glass w-5 text-center"></i> 
            <span>Search</span>
        </a>
        <a href="<?= $BASE_URL ?>/Bookmark/Bookmark.php" class="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-md transition-colors">
            <i class="fa-regular fa-bookmark w-5 text-center"></i> 
            <span>Bookmarks</span>
        </a>
    </nav> <div class="border-t border-gray-200 pt-3"> <a href="/models/user/logout.php" class="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-md transition-colors text-red-600 font-semibold">
            <i class="fa-solid fa-sign-out-alt w-5 text-center"></i>
            <span>Đăng Xuất</span>
        </a>
    </div>

</aside>