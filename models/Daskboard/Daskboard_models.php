<?php
require_once BASE_PATH . "/config/connetdata.php";

class Daskboard_models {
    private $conn;

    public function __construct() {
        $this->conn = getPDO();
    }


    /**
     * Summary of getTotalTrackedLaptops
     * @return int
     */
    public function getTotalTrackedLaptops(): int {
        try {
            // Cải tiến: Đếm các product_id duy nhất (DISTINCT)
            // Nếu bảng trong ảnh tên là 'bookmarks', hãy giữ nguyên. 
            // Nếu tên khác (vd: user_follows), hãy sửa lại tên bảng.
            $sql = "SELECT COUNT(DISTINCT product_id) as total FROM bookmarks";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Summary of getPriceDropsToday
     * @return int
     */
    public function getPriceDropsToday(): int {
         
         try {
            $sql = "SELECT COUNT(DISTINCT product_id) as total 
                    FROM price_history 
                    WHERE is_sale = 1 
                    AND DATE(recorded_at) = CURDATE()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Summary of getSearchCountLast24h
     * @return int
     */

    public function getSearchCountLast24h() { // Bỏ tham số $conn
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM search_history 
                    WHERE searched_at >= NOW() - INTERVAL 24 HOUR";
            
            $stmt = $this->conn->prepare($sql); // Dùng $this->conn
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }
    /**
     * Summary of getBookmarkCountLast24h
     * @return int
     */
    public function getBookmarkCountLast24h() { // Bỏ tham số $conn
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM bookmarks 
                    WHERE created_at >= NOW() - INTERVAL 24 HOUR";
            
            $stmt = $this->conn->prepare($sql); // Dùng $this->conn
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }
    /**
     * Summary of getTopSearchKeywords
     * @param mixed $limit số lượng từ tìm kiếm muốn lấy, mặc định là 5
     * @return array
     */
    public function getTopSearchKeywords($limit = 5) {
        try {
            // Logic: Gom nhóm theo từ khóa (query_text) và đếm số lần xuất hiện
            $sql = "SELECT query_text, COUNT(*) as search_count 
                    FROM search_history 
                    GROUP BY query_text 
                    ORDER BY search_count DESC 
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return []; // Trả về mảng rỗng nếu lỗi
        }
    }
    /**
     * Lấy danh sách sản phẩm được bookmark nhiều nhất
     * @param int $limit Số lượng sản phẩm muốn lấy
     * @return array
     */
        public function getTopBookmarkedProducts($limit = 5) {
        try {
            // THÊM p.url VÀO SELECT VÀ GROUP BY
            $sql = "SELECT p.product_id, p.name, p.url, COUNT(b.user_id) as total_bookmarks 
                    FROM bookmarks b
                    JOIN products_master p ON b.product_id = p.product_id
                    GROUP BY b.product_id, p.name, p.url 
                    ORDER BY total_bookmarks DESC 
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
}
?>