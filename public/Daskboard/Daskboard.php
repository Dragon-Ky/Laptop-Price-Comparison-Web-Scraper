<?php
session_start();
define('BASE_PATH', dirname(dirname(dirname(__FILE__)))); 
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard_style.css"> 
</head>
<body>
    <div class="flex min-h-screen bg-gray-100">

        <?php 
            $active_page = 'Dashboard';
            define('BASE', dirname(dirname(__FILE__)));
            require_once BASE . '/includes/sidebar.php';
        ?>

        <main class="flex-1 ">
            <?php require_once BASE_PATH ."/models/Daskboard/Daskboard_View.php"; ?>
        </main>
    </div>
</body>
</html>


