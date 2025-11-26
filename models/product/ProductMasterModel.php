<?php


// Giả định getPDO() đã được nhúng

class ProductMasterModel {
    private $pdo;

    public function __construct() {
        $this->pdo = getPDO();
    }

    /**
     * Chèn hoặc Cập nhật thông tin sản phẩm vào products_master
     * Sử dụng source_url làm khóa duy nhất để tránh trùng lặp
     *
     * @param array $product Mảng dữ liệu sản phẩm đã scrape.
     * @return int|bool product_id nếu thành công, FALSE nếu thất bại
     */
    public function insertOrUpdate(array $product) {
        // Chuẩn bị dữ liệu
        $url = $product['url'];
        $price = $product['price'];
        $old_price = $product['old_price'] ?? null;
        $name = $product['name'];
        $site = $product['source_site'];
        $specs = $product['specs_summary'] ?? null;
        
        
        // 1. CHÈN HOẶC CẬP NHẬT (UPSERT)
        // Kỹ thuật này sử dụng cơ chế ON DUPLICATE KEY UPDATE của MySQL
        $sql = "INSERT INTO products_master (
                    url, name, price, old_price, source_site, specs_summary, last_updated
                ) VALUES (
                    :url, :name, :price, :old_price, :site, :specs, NOW()
                )
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    price = VALUES(price),
                    old_price = VALUES(old_price),
                    source_site = VALUES(source_site),
                    specs_summary = VALUES(specs_summary),
                    last_updated = NOW()";

        $stmt = $this->pdo->prepare($sql);
        
        $success = $stmt->execute([
            ':url' => $url,
            ':name' => $name,
            ':price' => $price,
            ':old_price' => $old_price,
            ':site' => $site,
            ':specs' => $specs,
            
        ]);
        
        if ($success) {
            // Nếu là INSERT mới, trả về ID vừa tạo
            if ($stmt->rowCount() == 1) {
                return (int)$this->pdo->lastInsertId();
            }
            // Nếu là UPDATE, cần tìm ID cũ
            $stmt_select = $this->pdo->prepare("SELECT product_id FROM products_master WHERE url = ?");
            $stmt_select->execute([$url]);
            $existing_id = $stmt_select->fetchColumn();
            return $existing_id;
        }

        return false;
    }
}