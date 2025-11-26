<?php
// cron/cleanup_orphaned_products.php

// Nạp file kết nối CSDL của bạn
require_once __DIR__ . '/../config/connetdata.php'; // Điều chỉnh đường dẫn

try {
    $pdo = getPDO();
    echo "Bắt đầu dọn dẹp sản phẩm mồ côi...\n";

    /**
     * Câu lệnh SQL này:
     * 1. Tìm trong `products_master` (pm)
     * 2. JOIN (LEFT JOIN) với `bookmarks` (bm)
     * 3. Chỉ giữ lại những hàng mà `bm.bookmark_id` là NULL 
     * (nghĩa là sản phẩm có trong master nhưng không có trong bookmark)
     * 4. Chỉ xóa sp mồ côi đã không được cập nhật trong 30 ngày
     * 5. Xóa những hàng đó khỏi `products_master`.
     */
    $sql = "
        DELETE pm 
        FROM products_master AS pm
        LEFT JOIN bookmarks AS bm ON pm.product_id = bm.product_id
        WHERE bm.bookmark_id IS NULL 
        AND pm.last_updated < DATE_SUB(NOW(), INTERVAL 30 DAY);
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $count = $stmt->rowCount();
    echo "Hoàn thành. Đã xóa $count sản phẩm mồ côi.\n";

} catch (PDOException $e) {
    error_log("Lỗi dọn dẹp Cron Job: " . $e->getMessage());
    echo "Đã xảy ra lỗi: " . $e->getMessage() . "\n";
}

?>