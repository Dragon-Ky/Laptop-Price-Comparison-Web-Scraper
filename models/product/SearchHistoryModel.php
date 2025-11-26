<?php
// models/SearchHistoryModel.php

class SearchHistoryModel {
    private $pdo;

    public function __construct() {
        $this->pdo = getPDO();
    }

    /**
     * Lưu lại từ khóa tìm kiếm
     */
    public function saveSearch(?int $user_id, string $query_text): bool {
        try {
            // Cập nhật 1: Đổi tên tham số product_name -> query_text cho khớp DB
            // Cập nhật 2: Thêm source_site vào INSERT để tránh lỗi thiếu cột
            $sql = "INSERT INTO search_history (user_id, query_text, searched_at)
                    VALUES (:user_id, :query_text, NOW() )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id'     => $user_id,
                ':query_text'  => $query_text,
                
            ]);
            
            return true;
        } catch (PDOException $e) {
            // QUAN TRỌNG: Dòng này sẽ in lỗi ra màn hình web để bạn thấy ngay
            echo "<div style='background:red; color:white; padding:10px; z-index:9999; position:relative;'>";
            echo "LỖI SQL: " . $e->getMessage();
            echo "</div>";
            die(); // Dừng chương trình để bạn kịp đọc lỗi
        }
    
    }

    /**
     * Lấy bản ghi tìm kiếm gần nhất (của user nếu có user_id, ngược lại lấy bản ghi mới nhất toàn bộ)
     *
     * @param int|null $user_id
     * @return array|null ['query_text' => string, 'searched_at' => string] hoặc null nếu không có
     */
    public function getLastSearch(?int $user_id = null): ?array {
        try {
            if ($user_id) {
                $sql = "SELECT query_text, searched_at FROM search_history WHERE user_id = :user_id ORDER BY searched_at DESC LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':user_id' => $user_id]);
            } else {
                $sql = "SELECT query_text, searched_at FROM search_history ORDER BY searched_at DESC LIMIT 1";
                $stmt = $this->pdo->query($sql);
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row : null;
        } catch (PDOException $e) {
            error_log("SearchHistoryModel::getLastSearch error: " . $e->getMessage());
            return null;
        }
    }

}