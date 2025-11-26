<?php
session_start(); // <-- BẮT BUỘC VẪN PHẢI CÓ Ở ĐẦU TIÊN
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="stylesheet" href="/assets/CSS/user.css"> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
</head>
<body>
<div class="form-container" id="login-form">
    <?php
    
    include '_display_messages.php'; 
    // ------------------------------------
    ?>
    <h2>Đăng Nhập</h2>
    
    <form action="/controllers/user/auth_process.php" method="POST">
        
        <input type="hidden" name="action" value="login">

        <label for="login_email">Email:</label>
        <input type="email" id="login_email" name="email" required placeholder="Nhập email của bạn">

        <label for="login_password">Mật khẩu:</label>
        <input type="password" id="login_password" name="password" required placeholder="Nhập mật khẩu">
        
        <button type="submit">Đăng Nhập</button>
    </form>
    <a href="register.php">Tạo tài khoản</a>
    
    <a href="forgot_password_OTP.php">Quên mật khẩu?</a>
</div>
</body>
</html>