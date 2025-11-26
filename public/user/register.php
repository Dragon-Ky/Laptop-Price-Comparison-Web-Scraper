<?php
session_start(); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký</title>
    <link rel="stylesheet" href="/assets/CSS/user.css"> 
</head>
<body>
<div class="form-container" id="register-form">
    <?php
    // --- CHỈ CẦN 1 DÒNG DUY NHẤT NÀY ---
    include '_display_messages.php'; 
    // ------------------------------------
    ?>
    <h2>Đăng Ký Tài Khoản Mới</h2>
    
    <form action="/controllers/user/auth_process.php" method="POST"> 
        
        <input type="hidden" name="action" value="register">
        
        <label for="reg_email">Email (Dùng làm Tên đăng nhập):</label>
        <input type="email" id="reg_email" name="email" required placeholder="example@email.com">
        
        <label for="reg_password">Mật khẩu:</label>
        <input type="password" id="reg_password" name="password" required placeholder="Tối thiểu 6 ký tự">
        
        <label for="reg_password_confirm">Xác nhận mật khẩu:</label>
        <input type="password" id="reg_password_confirm" name="password_confirm" required placeholder="Nhập lại mật khẩu">
        
        <button type="submit">Đăng Ký</button>
    </form>
    <a href="login.php">Đã có tài khoản? Đăng nhập</a>
</div>
</body>
</html>