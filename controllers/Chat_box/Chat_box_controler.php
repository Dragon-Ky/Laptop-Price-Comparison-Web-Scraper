<?php
session_start();
define('BASE_PATH', dirname(dirname(dirname(__FILE__)))); 
require_once BASE_PATH . '/config/connetdata.php'; 
header('Content-Type: application/json');

// --- CẤU HÌNH API AI ---
$apiKey = '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'; 
$apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
// -----------------------

$user_id = $_SESSION['user_id'] ?? 1; 
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    $pdo = getPDO();

    switch ($action) {
        case 'fetch':
            $stmt = $pdo->prepare("SELECT sender, message, created_at FROM chat_history WHERE user_id = ? ORDER BY created_at ASC");
            $stmt->execute([$user_id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'send':
            $userMsg = trim($input['message'] ?? '');
            if (empty($userMsg)) exit(json_encode(['status' => 'error', 'message' => 'Rỗng']));

            // 1. Lưu tin nhắn User
            $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, sender, message) VALUES (?, 'user', ?)");
            $stmt->execute([$user_id, $userMsg]);

            // 2. GỌI AI ĐỂ XỬ LÝ
            $ai_reply = processAI($userMsg, $user_id, $apiKey, $apiUrl, $pdo);

            // 3. Lưu tin nhắn AI trả lời
            $stmtAI = $pdo->prepare("INSERT INTO chat_history (user_id, sender, message) VALUES (?, 'ai', ?)");
            $stmtAI->execute([$user_id, $ai_reply]);

            echo json_encode(['status' => 'success', 'reply' => $ai_reply]);
            break;

        case 'clear':
            $pdo->prepare("DELETE FROM chat_history WHERE user_id = ?")->execute([$user_id]);
            echo json_encode(['status' => 'success']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// =================================================================
// HÀM XỬ LÝ AI
// =================================================================
function processAI($userInput, $userId, $apiKey, $apiUrl, $pdo) {
    // 1. Cập nhật Prompt: Yêu cầu lấy thêm cột `image_url`
    $systemPrompt = "
        Bạn là chuyên gia tư vấn Laptop.
        Database có bảng `products_master`: name, price, brand, cpu, ram, vga, image_url, url, source_site.

        QUY TẮC QUAN TRỌNG:
        1. TRẢ VỀ DUY NHẤT 1 CÂU LỆNH SQL `SELECT`.
        2. Luôn SELECT các cột: name, price, image_url, url. (Bắt buộc phải có image_url để hiển thị ảnh).
        3. Logic tìm kiếm:
           - 'Gaming': vga LIKE '%RTX%' OR vga LIKE '%GTX%'...
           - 'Rẻ nhất': ORDER BY price ASC.
           - 'Mạnh nhất': ORDER BY price DESC.
        
        VÍ DỤ TRẢ LỜI:
        'Tìm laptop Dell' -> SELECT name, price, image_url, url FROM products_master WHERE brand LIKE '%Dell%' LIMIT 5;
        KHÔNG giải thích, KHÔNG markdown.
    ";

    // --- BƯỚC 1: HỎI AI LẤY SQL ---
    $response1 = callOpenRouter($apiUrl, $apiKey, [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $userInput]
    ]);
    
    $content1 = $response1['choices'][0]['message']['content'] ?? "";
    $content1 = trim($content1);

    // Kiểm tra xem AI có trả về SQL không
    if (stripos($content1, 'SELECT') === 0) {
        try {
            $sql = str_replace('{{user_id}}', $userId, $content1); 
            if (stripos($sql, 'LIMIT') === false) $sql .= " LIMIT 5"; // Giới hạn 5 sản phẩm

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$results) {
                return "Tiếc quá, tôi không tìm thấy sản phẩm nào khớp với yêu cầu của bạn.";
            }

            // --- BƯỚC 2 (MỚI): KHÔNG GỌI AI NỮA -> DÙNG PHP TẠO HTML HIỂN THỊ ---
            
            $html = '<p>Dưới đây là các sản phẩm tôi tìm được cho bạn:</p>';
            // Tạo container cuộn ngang hoặc lưới (tùy CSS frontend của bạn)
            $html .= '<div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px;">';

            foreach ($results as $item) {
                // Xử lý dữ liệu
                $name = htmlspecialchars($item['name']);
                $price = number_format($item['price'], 0, ',', '.') . '₫';
                // Nếu không có ảnh thì dùng ảnh placeholder
                $img = !empty($item['image_url']) ? $item['image_url'] : 'https://via.placeholder.com/150?text=No+Image';
                $link = $item['url'];

                // Tạo thẻ Card sản phẩm nhỏ gọn
                $html .= "
                <div style='min-width: 200px; max-width: 200px; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background: #fff;'>
                    <div style='height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 8px;'>
                        <img src='{$img}' alt='{$name}' style='max-width: 100%; max-height: 100%; object-fit: contain;'>
                    </div>
                    <h4 style='font-size: 14px; margin: 0 0 5px; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;'>{$name}</h4>
                    <div style='color: #d70018; font-weight: bold; margin-bottom: 8px;'>{$price}</div>
                    <a href='{$link}' target='_blank' style='display: block; text-align: center; background: #007bff; color: white; padding: 6px; border-radius: 4px; text-decoration: none; font-size: 13px;'>Xem chi tiết</a>
                </div>
                ";
            }
            $html .= '</div>';

            return $html; // Trả về HTML để frontend hiển thị

        } catch (Exception $e) {
            return "Lỗi truy vấn dữ liệu. Bạn thử lại nhé.";
        }
    } else {
        // Nếu không phải câu hỏi tìm hàng thì trả lời bình thường
        return $content1;
    }
}

// Hàm curl giữ nguyên
function callOpenRouter($url, $key, $messages) {
    $ch = curl_init($url);
    $data = [
        "model" => "meta-llama/llama-3.3-70b-instruct",
        "messages" => $messages,
        "temperature" => 0.7
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $key",
            "Content-Type: application/json",
            "HTTP-Referer: http://localhost" 
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}
?>