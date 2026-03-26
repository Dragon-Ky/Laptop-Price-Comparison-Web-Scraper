<?php
// models/product/ProductMasterModel.php

require_once BASE_PATH . '/controllers/Search/Tag_search.php'; 
require_once BASE_PATH . '/core/helpers.php';

class ProductMasterModel {
    private $pdo;

    public function __construct() {
        $this->pdo = getPDO(); 
    }

    public function insertOrUpdate(array $product) {
        
        // BƯỚC 1: TỰ ĐỘNG TÁCH THÔNG SỐ
        $parsed_specs = parse_laptop_specs($product['name']);

        // Logic: Laptop luôn phải có CPU hoặc Màn hình được định nghĩa trong tên.
        // Nếu hàm parse không bắt được CPU và không bắt được Màn hình => Đây là phụ kiện (Chuột, Balo, RAM...)
        
        $has_cpu    = !empty($parsed_specs['cpu']);
        $has_screen = !empty($parsed_specs['display']);

        // Kiểm tra thêm từ khóa "cấm" để chắc chắn loại bỏ phụ kiện
        $name_lower = mb_strtolower($product['name']);
        $is_accessory = false;
        is_accessory($name_lower) && $is_accessory = true;

        // ĐIỀU KIỆN TỪ CHỐI LƯU:
        // 1. Là phụ kiện (có từ khóa cấm) VÀ không tìm thấy CPU (để tránh xóa nhầm combo Laptop + quà)
        // 2. HOẶC: Không tìm thấy cả CPU lẫn Màn hình (thông tin quá nghèo nàn hoặc không phải laptop)
        if (($is_accessory && !$has_cpu) || (!$has_cpu && !$has_screen)) {
            return false; // Dừng lại, không lưu vào database
        }
      


        // Gán thông số sau khi đã qua bộ lọc
        $product['cpu']     = !empty($product['cpu'])     ? $product['cpu']     : $parsed_specs['cpu'];
        $product['ram']     = !empty($product['ram'])     ? $product['ram']     : $parsed_specs['ram'];
        
        $input_storage      = $product['hdd'] ?? $product['storage'] ?? null;
        $product['storage'] = !empty($input_storage)      ? $input_storage      : $parsed_specs['storage'];

        $product['vga']     = !empty($product['vga'])     ? $product['vga']     : $parsed_specs['gpu'];
        $product['screen']  = !empty($product['screen'])  ? $product['screen']  : $parsed_specs['display'];

        // BƯỚC 2: CHUẨN BỊ SQL
        $sql = "INSERT INTO products_master (
                    url, name, price, old_price, source_site, image_url,
                    brand, cpu, ram, storage, vga, screen, last_updated
                ) VALUES (
                    :url, :name, :price, :old_price, :source_site, :image_url,
                    :brand, :cpu, :ram, :storage, :vga, :screen, NOW()
                )
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    price = VALUES(price),
                    old_price = VALUES(old_price),
                    image_url = VALUES(image_url),
                    brand = VALUES(brand),
                    cpu = VALUES(cpu),
                    ram = VALUES(ram),
                    storage = VALUES(storage),
                    vga = VALUES(vga),
                    screen = VALUES(screen),
                    last_updated = NOW()";

        try {
            $stmt = $this->pdo->prepare($sql);
            
            $img = $product['image'] ?? $product['image_url'] ?? null;

            $success = $stmt->execute([
                ':url'         => $product['url'],
                ':name'        => $product['name'],
                ':price'       => $product['price'],
                ':old_price'   => $product['old_price'] ?? 0,
                ':source_site' => $product['site'] ?? $product['source_site'],
                ':image_url'   => $img,
                
                ':brand'       => $product['brand'] ?? null,
                ':cpu'         => $product['cpu'],
                ':ram'         => $product['ram'],
                ':storage'     => $product['storage'],
                ':vga'         => $product['vga'],
                ':screen'      => $product['screen']
            ]);
            
            if ($success) {
                if ($stmt->rowCount() > 0 && $this->pdo->lastInsertId()) {
                    return (int)$this->pdo->lastInsertId();
                }
                $stmt_select = $this->pdo->prepare("SELECT product_id FROM products_master WHERE url = ?");
                $stmt_select->execute([$product['url']]);
                return $stmt_select->fetchColumn();
            }

        } catch (PDOException $e) {
            error_log("Error inserting product: " . $e->getMessage());
        }

        return false;
    }

    public function searchProducts($keyword, $limit = 50) {
            $sql = "SELECT * FROM products_master 
                    WHERE name LIKE :kw 
                    OR brand LIKE :kw 
                    ORDER BY last_updated DESC 
                    LIMIT :limit";
            
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':kw', '%' . $keyword . '%');
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("DB Search Error: " . $e->getMessage());
                return [];
            }
        }
}
?>