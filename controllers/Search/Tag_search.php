<?php
/* ======================  HÀM TÁCH THÔNG SỐ (GIỮ NGUYÊN)  ====================== */
function parse_laptop_specs($name) {
    $spec = [
        'cpu' => '',
        'ram' => '',
        'storage' => '',
        'gpu' => '',
        'display' => ''
    ];

    // CPU Patterns
    $cpu_patterns = [
        '/\bXeon(?:®|™)?\s*(?:[A-Z]+[-]?\s*)?\d{3,5}[A-Z0-9]*\b/i',
        '/\b(?:Ryzen\s*)?AI\s*\d+\s*(?:[A-Z]+\s*)?\d+\b/i',
        '/\bSnapdragon\s*X\s*(?:Elite|Plus)?\s*[-A-Z0-9]+\b/i',
        '/\b(?:Intel\s*)?(?:Core\s*)?Ultra\s*[3579]\s*[-]?\s*\d+[A-Z]*\b/i',
        '/\b(?:Core\s*)?i3\s*[-]?\s*N\d{3,4}\b/i',    
        '/\b(?:Processor\s*)?N\d{3,4}\b/i',
        '/\bCore\s*[3579]\s*[-]?\s*\d{3,4}[A-Z]*\b/i',
        '/\bCore\s*i[3579]\s*[-]?\s*\d{3,5}[A-Z0-9]{0,4}\b/i',
        '/\bi[3579]\s*[- ]+\s*\d{3,5}[A-Z0-9]{0,4}\b/i',
        '/\bRyzen(?:®|™)?\s*\d{1,2}\s*[-]?\s*(?:[A-Z]+\s*)?\d{3,5}[A-Z]*\b/i',
        '/\bRyzen\s*\d{1,2}\s*[-]?\s*\d{3,5}[A-Z]*\b/i',
        '/\bR[3579]\s*[-]?\s*\d{3,5}[A-Z]*\b/i',
    ];

    foreach ($cpu_patterns as $pat) {
        if (preg_match($pat, $name, $m)) {
            $spec['cpu'] = trim($m[0]);
            break; 
        }
    }

    // RAM
    preg_match('/(\d+)\s?gb( ram)?/i', $name, $m);
    if ($m) $spec['ram'] = $m[1] . "GB";

    // Storage
    $storage_found = false;
    // 1) size trước type
    if (preg_match('/(\d+)\s?(gb|tb)\s*(?:ssd|hdd|nvme)/i', $name, $m)) {
        $num = intval($m[1]); $unit = strtolower($m[2]);
        $size_gb = ($unit === 'tb') ? $num * 1024 : $num;
        if ($size_gb > 100) {
            $spec['storage'] = strtoupper($m[1] . $m[2]);
            $storage_found = true;
        }
    }
    // 2) type trước size
    if (!$storage_found && preg_match('/(?:m\.?2|m2|nvme|ssd|hdd)[^\d]{0,6}(\d+)\s?(gb|tb)/i', $name, $m2)) {
        $num = intval($m2[1]); $unit = strtolower($m2[2]);
        $size_gb = ($unit === 'tb') ? $num * 1024 : $num;
        if ($size_gb > 100) {
            $spec['storage'] = strtoupper($m2[1] . $m2[2]);
            $storage_found = true;
        }
    }
    // 3) fallback
    if (!$storage_found) {
        if (preg_match_all('/(\d+)\s?(gb|tb)/i', $name, $matches, PREG_SET_ORDER)) {
            $best = null;
            foreach ($matches as $match) {
                $num = intval($match[1]); $unit = strtolower($match[2]);
                $size_gb = ($unit === 'tb') ? $num * 1024 : $num;
                if ($size_gb > 100) {
                    if ($best === null || $size_gb > $best['size_gb']) {
                        $best = ['text' => strtoupper($match[1] . $match[2]), 'size_gb' => $size_gb];
                    }
                }
            }
            if ($best !== null) $spec['storage'] = $best['text'];
        }
    }

    // GPU
    $gpu_patterns = [
        '/\b(?:NVIDIA[®™]*\s*)?(?:GeForce[®™]*\s*)?(?:RTX|GTX|MX|Quadro)[®™]*\s*[-]?\s*[A-Z]*\d+[A-Z0-9]*(?:\s*(?:Ti|Super|Ada))?(?:\s+\d+\s*GB)?\b/u',
        '/\b(?:AMD[®™]*\s*)?Radeon[®™]*\s*RX\s*\d+[A-Z]*\b/u',
        '/\b(?:AMD[®™]*\s*)?Radeon[®™]*\s*\d+M\b/u',
        '/\bIntel[®™]*\s*Arc[®™]*(?:\s*Graphics)?(?:\s*[A-Z0-9]+)?\b/u',
        '/\bIntel[®™]*\s*(?:Iris[®™]*\s*Xe|UHD)?\s*Graphics\b/u',
        '/\bIntel[®™]*\s*(?:Iris[®™]*\s*Xe|UHD)?\b/u',
    ];
    foreach ($gpu_patterns as $pat) {
        if (preg_match($pat, $name, $m)) {
            $raw_gpu = $m[0];
            $clean_gpu = preg_replace('/[®™]/u', '', $raw_gpu);
            $spec['gpu'] = strtoupper(preg_replace('/\s+/', ' ', trim($clean_gpu)));
            break;
        }
    }

    // Display
    preg_match('/(\d{2}\.?\d*)\s?inch/i', $name, $m);
    if ($m) $spec['display'] = $m[1] . " inch";

    return $spec;
}

/* ======================  HÀM LÀM SẠCH TÊN (FINAL VERSION)  ====================== */
function clean_product_name($name) {
    // =========================================================================
    // BƯỚC 1: CHUẨN HÓA CƠ BẢN
    // =========================================================================
    
    // 1. Giải mã ký tự HTML (Sửa lỗi &#8243; thành " và &#8211; thành -)
    $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 2. Chuyển về chữ thường để xử lý đồng nhất
    $name = mb_strtolower($name, 'UTF-8');

    // =========================================================================
    // BƯỚC 2: XÓA TỪ KHÓA RÁC & MARKETING
    // =========================================================================
    $remove_keywords = [
        // --- Từ khóa bán hàng/Trạng thái ---
        'chính hãng', 'vn/a', 'nhập khẩu', 'giá rẻ', 'like new', '99%', 'new seal', 
        'full box', 'fullbox', 'bảo hành', 'trọn đời', 'xách tay', 'cũ', 
        'mới', 'new', 'hết hàng', 'out of stock', 'ngừng kinh doanh',

        // --- Loại sản phẩm (Thừa) ---
        'laptop', 'máy tính xách tay', 'notebook', 'ultrabook',
        'laptop lai máy tính bảng', 'máy tính bảng',

        // --- Từ khóa Marketing/Tính năng ---
        'gọn nhẹ', 'mạnh mẽ', 'mỏng nhẹ', 'siêu mỏng', 'sang trọng', 'thời trang',
        'văn phòng', 'doanh nhân', 'gaming', 'đồ họa', 'gamer', 'creator',
        'cấu hình cao', 'cấu hình mạnh mẽ', 'siêu mỏng nhẹ', 'hiệu năng cao',
        'propanel', // <--- Từ khóa gây lệch nhóm trong ảnh bạn gửi
        
        // --- Tính năng vật lý ---
        '2-in-1', '2 in 1', 'xoay gập', '360 độ', '360', 'cảm ứng', 'touch', 'flip',

        // --- Màu sắc ---
        'đen', 'trắng', 'bạc', 'xám', 'vàng', 'xanh', 'hồng',
        'black', 'silver', 'grey', 'gray', 'gold', 'blue', 'pink',

        // --- Từ đệm vô nghĩa ---
        'màn', 'hình', 'inch',
    ];

    // Sắp xếp từ khóa dài lên trước để tránh xóa nhầm từ con
    usort($remove_keywords, function($a, $b) {
        return strlen($b) - strlen($a);
    });

    foreach ($remove_keywords as $word) {
        // Dùng \b để đảm bảo xóa nguyên từ, không xóa ký tự trong từ khác
        $name = preg_replace('/\b'.preg_quote($word, '/').'\b/u', ' ', $name);
    }

    // =========================================================================
    // BƯỚC 3: XÓA CẤU HÌNH & MÃ SẢN PHẨM (REGEX)
    // =========================================================================
    
    // Xóa nội dung trong ngoặc đơn trước (thường là SKU hoặc ghi chú thừa)
    $name = preg_replace('/\([^\)]+\)/u', ' ', $name);

    $specs_remove = [
        // 1. Xóa Mã SKU Part Number dạng dấu chấm (VD: NH.QPFSV.00, NH.QPGSV.004)
        '/\b[a-z0-9]+\.[a-z0-9]+\.[a-z0-9]+\b/u',

        // 2. Xóa Mã Model dài nối gạch ngang có chứa số (VD: ANV15-41-R9M1, AN515-57)
        // Logic: Bắt buộc phần đầu phải có số (ANV15) để không xóa nhầm tên (như dell-xps)
        '/\b[a-z]*\d+[a-z0-9]*(-[a-z0-9]+){2,}\b/u',

        // 3. Xóa Kích thước màn hình & Số đứng lẻ
        '/\b\d+(\.\d+)?\s*(inch|”|")\b/u', 
        '/\b(13|14|15|16|17)\b/u', // <--- Xóa số 15 trong "Nitro V 15" để gom chung với "Nitro V"

        // 4. Xóa Độ phân giải (Viết tắt & Chi tiết)
        '/\b(fhd|hd|2k|3k|4k|8k|qhd|wqhd|uxga|wuxga)\b/u', 
        '/\b\d{3,4}\s*x\s*\d{3,4}\b/u',

        // 5. Xóa CPU - Intel Core Ultra (VD: Ultra 5 125U)
        '/\bultra\s*\d(\s*\w+)?\b/u',

        // 6. Xóa CPU - Mã lẻ (VD: 7300U, 1135G7, 1255U)
        // Logic: 4-5 số + đuôi đặc thù CPU (u, p, h, hs...)
        '/\b\d{4,5}(u|p|g\d|h|hs|hx|k|f|qm|hq|y)\b/u',

        // 7. Xóa CPU - Core i / Ryzen truyền thống
        '/\b(thế hệ|thế hệ thứ|gen|generation)\s*\d+(th|nd|rd|st)?\b/u',
        '/\b(core\s*)?i\d(-\d{4,5}[a-z]*)?\b/u', 
        '/\bryzen\s*\d(\s*\d{4,5}[a-z]*)?\b/u',

        // 8. Xóa RAM / Ổ cứng
        '/\b\d+\s*gb\b/u', 
        '/\b\d+\s*tb\b/u',
        '/\b(ssd|hdd|emmc|nvme)\s*(\d+\w*)?\b/u',
        
        // 9. Xóa Tần số quét (VD: 144hz, 165hz)
        '/\b\d+\s*hz\b/u',
        
        // 10. Xóa card đồ họa (Cơ bản)
        '/\b(rtx|gtx|rx)\s*\d{3,4}\w*\b/u',
        '/\b(vga|card)\b/u',
    ];

    foreach ($specs_remove as $pat) {
        $name = preg_replace($pat, ' ', $name);
    }

    // =========================================================================
    // BƯỚC 4: XỬ LÝ MÃ ĐẶC THÙ CÒN SÓT & CẮT CHUỖI
    // =========================================================================

    // Xóa các mã SKU đặc thù dạng cũ (HP, Dell hay dùng)
    $name = preg_replace('/\b[a-z0-9]{4,10}pa(#\w+)?\b/i', ' ', $name);
    $name = preg_replace('/\b[a-z0-9]{5,12}(vn|us|tu|hn|in|ww|aum|sus)\b/i', ' ', $name);

    // Cắt chuỗi tại các ký tự phân tách mạnh (|, /, ,, - dài)
    // Thêm ký tự '–' (dash dài) do html_entity_decode tạo ra
    $parts = preg_split('/[|\/\\\,–]/u', $name); 
    
    // Lấy phần đầu tiên làm tên gốc
    $clean_name = trim($parts[0]);

    // =========================================================================
    // BƯỚC 5: LÀM SẠCH CUỐI CÙNG
    // =========================================================================
    
    // Chỉ giữ lại chữ cái và số, thay ký tự đặc biệt bằng khoảng trắng
    $clean_name = preg_replace('/[^a-z0-9\s]/u', ' ', $clean_name);

    // Xóa khoảng trắng thừa (nhiều dấu cách thành 1 dấu cách)
    $final_name = trim(preg_replace('/\s+/', ' ', $clean_name));

    // Fallback: Nếu lọc xong mà chuỗi rỗng (do tên toàn rác), trả về phần đầu gốc đã clean sơ
    if (strlen($final_name) < 2 && isset($parts[0])) {
         return trim(preg_replace('/[^a-z0-9\s]/u', ' ', $parts[0]));
    }

    return $final_name;
}
?>