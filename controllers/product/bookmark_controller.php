<?php
// /controllers/user/bookmark_controller.php

// --- 1. SETUP ---
session_start();

// Trả về JSON cho JavaScript/AJAX
header('Content-Type: application/json');


define('BASE_PATH', dirname(dirname(dirname(__FILE__)))); 

// Nhúng các file Model cần thiết
require_once BASE_PATH . '/config/connetdata.php'; 
require_once BASE_PATH . '/models/product/BookmarkModel.php'; 
require_once BASE_PATH . '/models/product/ProductMasterModel.php'; // Model bạn đã có

$response = ['success' => false, 'message' => 'Lỗi không xác định.'];

// --- 2. XÁC THỰC VÀ KIỂM TRA DỮ LIỆU ---

// 2a. Kiểm tra Đăng nhập (Bắt buộc)
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Bạn cần đăng nhập để thực hiện chức năng này.';
    echo json_encode($response);
    exit;
}
$user_id = $_SESSION['user_id'];

// 2b. Kiểm tra phương thức POST (Bảo mật)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response);
    exit;
}
$old_price_raw = $_POST['old_price'] ?? null;
// 2c. Lấy dữ liệu sản phẩm (do AJAX gửi lên)
$product_data = [
    'url'           => $_POST['url'] ?? '',
    'name'          => $_POST['name'] ?? '',
    'price'         => $_POST['price'] ?? 0,
    'old_price'     => ($old_price_raw === '') ? null : $old_price_raw,
    'source_site'   => $_POST['source_site'] ?? '',
    'specs_summary' => $_POST['specs_summary'] ?? null,
    'image_url'    => $_POST['image_url'] ?? null,
    
];

// 2d. Kiểm tra dữ liệu tối thiểu
if (empty($product_data['url']) || empty($product_data['name']) || empty($product_data['price'])) {
    $response['message'] = 'Thiếu dữ liệu sản phẩm để bookmark.';
    echo json_encode($response);
    exit;
}
$action = $_POST['action'] ?? 'add';
// --- 3. THỰC THI LOGIC (LƯU VĨNH VIỄN) ---

try {
    $productMasterModel = new ProductMasterModel();
    $bookmarkModel = new BookmarkModel();

    // BƯỚC 1: LƯU VÀO MASTER DATA (Kích hoạt Lưu Vĩnh Viễn)
    // Hàm này sẽ INSERT (nếu SP mới) hoặc UPDATE (nếu SP đã cũ)
    // và trả về product_id (mới hoặc cũ).
    $product_id = $productMasterModel->insertOrUpdate($product_data);

    if (!$product_id) {
        $response['message'] = 'Lỗi: Không thể lưu sản phẩm vào Master Data.';
        echo json_encode($response);
        exit;
    }

    // BƯỚC 2: LƯU VÀO BOOKMARKS
    if ($bookmarkModel->isBookmarked($user_id, $product_id)) {
        // Tùy chọn: Nếu đã bookmark, nhấn lại là HỦY bookmark
        // $bookmarkModel->removeBookmark($user_id, $product_id);
        // $response['action'] = 'removed';
        $response['success'] = true;
        $response['message'] = 'Sản phẩm này đã có trong danh sách yêu thích của bạn.';
    } else {
        // Thêm bookmark mới
        if ($bookmarkModel->addBookmark($user_id, $product_id)) {
            $response['success'] = true;
            $response['message'] = 'Đã thêm vào danh sách yêu thích!';
            // $response['action'] = 'added';
        } else {
            $response['message'] = 'Lỗi: Không thể lưu bookmark.';
        }
    }
    if ($action === "remove") {

        if (empty($_POST['url'])) {
            echo json_encode(['success' => false, 'message' => 'URL không hợp lệ']);
            exit;
        }

        $url = trim($_POST['url']);

        // Lấy product_id từ url
        $product_id = $bookmarkModel->getProductIdByUrl($url);

        if (!$product_id) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong DB']);
            exit;
        }

        $ok = $bookmarkModel->removeBookmark($user_id, $product_id);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Đã xoá bookmark' : 'Xoá thất bại'
        ]);
        exit;
    }


} catch (PDOException $e) {
    // $response['message'] = 'Lỗi cơ sở dữ liệu. Vui lòng thử lại.';
    $response['message'] = $e->getMessage();
}

// --- 4. TRẢ KẾT QUẢ VỀ CHO JAVASCRIPT ---
echo json_encode($response);
exit;

?>
