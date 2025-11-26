<?php
// File: /public/user/_display_messages.php
// File này KHÔNG cần session_start()
// vì nó sẽ được nhúng vào file CHA (như login.php) VỐN ĐÃ CÓ session_start()

if (isset($_SESSION['error'])) {
    // Hiển thị Lỗi (bạn có thể đổi style)
    echo '<div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px;">';
    echo $_SESSION['error'];
    echo '</div>';
    
    // Xóa thông báo lỗi sau khi đã hiển thị
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    // Hiển thị Thành công (bạn có thể đổi style)
    echo '<div style="color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px;">';
    echo $_SESSION['success'];
    echo '</div>';
    
    // Xóa thông báo thành công sau khi đã hiển thị
    unset($_SESSION['success']);
}
?>