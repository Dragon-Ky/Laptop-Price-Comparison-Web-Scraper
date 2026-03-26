<?php
// models/BookmarkModel.php

require_once BASE_PATH . "/config/connetdata.php";   

class BookmarkModel {
    private $pdo;

    public function __construct() {
        $this->pdo = getPDO();
    }

    /**
     * Thêm một sản phẩm vào danh sách yêu thích của người dùng.
     *
     * @param int $user_id
     * @param int $product_id (Lấy từ products_master)
     * @return bool
     */
    public function addBookmark(int $user_id, int $product_id): bool {
        // Kiểm tra xem đã bookmark chưa (để tránh lỗi UNIQUE key)
        if ($this->isBookmarked($user_id, $product_id)) {
            return true; // Đã bookmark rồi, coi như thành công
        }

        $sql = "INSERT INTO bookmarks (user_id, product_id) VALUES (:user_id, :product_id)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':user_id' => $user_id,
                ':product_id' => $product_id
            ]);
        } catch (PDOException $e) {
            error_log("Bookmark add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Xóa một sản phẩm khỏi danh sách yêu thích.
     *
     * @param int $user_id
     * @param int $product_id
     * @return bool
     */
    public function removeBookmark(int $user_id, int $product_id): bool {
        $sql = "DELETE FROM bookmarks WHERE user_id = :user_id AND product_id = :product_id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':product_id' => $product_id
            ]);
            return $stmt->rowCount() > 0; // Trả về true nếu có dòng bị xóa
        } catch (PDOException $e) {
            error_log("Bookmark remove error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem người dùng đã bookmark sản phẩm này chưa.
     *
     * @param int $user_id
     * @param int $product_id
     * @return bool
     */
    public function isBookmarked(int $user_id, int $product_id): bool {
        $sql = "SELECT 1 FROM bookmarks WHERE user_id = :user_id AND product_id = :product_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Lấy tất cả sản phẩm đã bookmark của một người dùng.
     *
     * @param int $user_id
     * @return array
     */
    public function getBookmarksByUser(int $user_id): array {
        // Sửa lại câu SQL
        $sql = "SELECT pm.*, bm.bookmark_id FROM bookmarks bm
                JOIN products_master pm ON bm.product_id = pm.product_id
                WHERE bm.user_id = :user_id
                ORDER BY bm.created_at DESC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }
    /**
    * @param int $user_id
    * @return array
    */
    public function getSearchBookmarksByUser($user_id) {
    $sql = "
        SELECT pm.url 
        FROM bookmarks b
        JOIN products_master pm ON pm.product_id = b.product_id
        WHERE b.user_id = :uid
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':uid' => $user_id]);

    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'url');
}
    public function removeSearchBookmark($user_id, $product_id) {
    $stmt = $this->pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND product_id = ?");
    return $stmt->execute([$user_id, $product_id]);
}

public function getProductIdByUrl($url) {
    // Trực tiếp tìm kiếm bằng URL đầy đủ được gửi từ form
    $stmt = $this->pdo->prepare("SELECT product_id FROM products_master WHERE url=?");
    $stmt->execute([$url]);
    return $stmt->fetchColumn();
}

// models/BookmarkModel.php
    
    
    public function removeBookmarkById(int $bookmark_id, int $user_id): bool {
        $sql = "DELETE FROM bookmarks WHERE bookmark_id = :bookmark_id AND user_id = :user_id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':bookmark_id' => $bookmark_id,
                ':user_id'     => $user_id
            ]);
            return $stmt->rowCount() > 0; // Trả về true nếu có dòng bị xóa
        } catch (PDOException $e) {
            error_log("Bookmark remove error: " . $e->getMessage());
            return false;
        }
    }
}