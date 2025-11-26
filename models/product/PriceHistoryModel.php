<?php
// models/PriceHistoryModel.php

class PriceHistoryModel {
    private $pdo;

    public function __construct() {
        // Giả định hàm getPDO() đã được nhúng
        $this->pdo = getPDO();
    }

    /**
     * Ghi lại một mốc giá mới cho sản phẩm.
     *
     * @param int $product_id ID của sản phẩm (từ products_master)
     * @param int $price Giá mới
     * @return bool
     */
    /**
     * Ghi lại giá (Chỉ ghi nếu giá thay đổi để tiết kiệm DB)
     */
    public function recordPrice(int $product_id, int $price, bool $is_sale = false): bool {
        
        // 1. Kiểm tra giá cũ gần nhất
        $last_price = $this->getLatestPrice($product_id);
        
        // Nếu giá y hệt lần trước -> Không lưu (đỡ rác database)
        if ($last_price !== false && (int)$last_price === $price) {
            return true; 
        }

        // 2. Nếu giá khác -> Insert dòng mới
        // Lưu ý: bảng bạn dùng history_id tự tăng, nên không cần điền nó
        $sql = "INSERT INTO price_history (product_id, recorded_price, recorded_at, is_sale) 
                VALUES (:product_id, :price, NOW(), :is_sale)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':product_id' => $product_id,
                ':price'      => $price,
                ':is_sale'    => $is_sale ? 1 : 0
            ]);
        } catch (PDOException $e) {
            error_log("Price history error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * (Hàm hỗ trợ) Lấy giá mới nhất đã được ghi lại
     */
    private function getLatestPrice(int $product_id) {
        $sql = "SELECT recorded_price FROM price_history 
                WHERE product_id = :product_id 
                ORDER BY recorded_at DESC 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_id' => $product_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Lấy toàn bộ lịch sử giá của một sản phẩm (để vẽ biểu đồ)
     */
    public function getHistoryForProduct(int $product_id): array {
        $sql = "SELECT recorded_price, recorded_at FROM price_history 
                WHERE product_id = :product_id 
                ORDER BY recorded_at ASC"; // ASC để vẽ biểu đồ
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_id' => $product_id]);
        return $stmt->fetchAll();
    }

/**
     * Đếm số lượng sản phẩm đang có xu hướng GIẢM GIÁ so với lần cập nhật trước.
     * Logic: Lấy giá mới nhất (Current) so sánh với giá liền kề trước đó (Previous).
     */
    public function countPriceDrops(): int {
        $sql = "
            SELECT COUNT(*) 
            FROM (
                SELECT 
                    curr.product_id,
                    curr.recorded_price AS current_price,
                    (
                        -- Tìm giá của lần ghi nhận ngay trước đó
                        SELECT prev.recorded_price 
                        FROM price_history prev
                        WHERE prev.product_id = curr.product_id 
                        AND prev.recorded_at < curr.recorded_at
                        ORDER BY prev.recorded_at DESC 
                        LIMIT 1
                    ) AS previous_price
                FROM price_history curr
                WHERE curr.history_id IN (
                    -- Chỉ lấy bản ghi mới nhất của từng sản phẩm
                    SELECT MAX(history_id) 
                    FROM price_history 
                    GROUP BY product_id
                )
            ) AS comparison
            -- Chỉ đếm những sản phẩm có giá hiện tại RẺ HƠN giá cũ
            WHERE current_price < previous_price 
        ";

        try {
            $stmt = $this->pdo->query($sql);
            // fetchColumn() sẽ trả về số lượng (COUNT)
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    public function getPriceChangePercent(int $product_id): array {
        try {
            $sql = "
                SELECT 
                    recorded_price, 
                    recorded_at
                FROM price_history 
                WHERE product_id = :product_id 
                ORDER BY recorded_at DESC 
                LIMIT 2
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':product_id' => $product_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                return [
                    'current_price' => 0,
                    'previous_price' => null,
                    'change_percent' => 0,
                    'change_amount' => 0,
                    'status' => 'no_data'
                ];
            }
            
            if (count($results) < 2) {
                return [
                    'current_price' => (int)$results[0]['recorded_price'],
                    'previous_price' => null,
                    'change_percent' => 0,
                    'change_amount' => 0,
                    'status' => 'new'
                ];
            }
            
            $current = (int)$results[0]['recorded_price'];
            $previous = (int)$results[1]['recorded_price'];
            $change_amount = $current - $previous;
            $change_percent = $previous > 0 ? round(($change_amount / $previous) * 100, 2) : 0;
            
            return [
                'current_price' => $current,
                'previous_price' => $previous,
                'change_percent' => $change_percent,
                'change_amount' => $change_amount,
                'status' => $change_percent < 0 ? 'down' : ($change_percent > 0 ? 'up' : 'same')
            ];
        } catch (PDOException $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
}