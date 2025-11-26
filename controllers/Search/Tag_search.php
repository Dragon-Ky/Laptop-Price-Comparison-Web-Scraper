<?php
/* ======================  HÀM TÁCH THÔNG SỐ  ====================== */
function parse_laptop_specs($name) {
    $spec = [
        'cpu' => '',
        'ram' => '',
        'storage' => '',
        'gpu' => '',
        'display' => ''
    ];

    // CPU - cải thiện để bắt các dạng như "Ultra 5 225H", "Core i5-12500H", "i5 12500H", "i3 N305", "Ryzen 5 7520U", ...

    $spec['cpu'] = '';

    $cpu_patterns = [
        // 0. [MỚI] Bắt dòng Xeon (Ưu tiên để trên cùng hoặc nhóm đặc biệt)
        '/\bXeon(?:®|™)?\s*(?:[A-Z]+[-]?\s*)?\d{3,5}[A-Z0-9]*\b/i',

        // 1. Các dòng AMD / Snapdragon / Intel Ultra
        '/\b(?:Ryzen\s*)?AI\s*\d+\s*(?:[A-Z]+\s*)?\d+\b/i',
        '/\bSnapdragon\s*X\s*(?:Elite|Plus)?\s*[-A-Z0-9]+\b/i',
        '/\b(?:Intel\s*)?(?:Core\s*)?Ultra\s*[3579]\s*[-]?\s*\d+[A-Z]*\b/i',

        // 2. BẮT DÒNG i3-N SERIES
        '/\b(?:Core\s*)?i3\s*[-]?\s*N\d{3,4}\b/i',    
        '/\b(?:Processor\s*)?N\d{3,4}\b/i',

        // 3. Bắt dòng Core mới (Core 3/5/7 - Series 1)
        '/\bCore\s*[3579]\s*[-]?\s*\d{3,4}[A-Z]*\b/i',

        // 4. Bắt các dòng Core "i" truyền thống
        '/\bCore\s*i[3579]\s*[-]?\s*\d{3,5}[A-Z0-9]{0,4}\b/i',

        '/\bi[3579]\s*[- ]+\s*\d{3,5}[A-Z0-9]{0,4}\b/i',

        // 5. Các dòng Ryzen thường
        '/\bRyzen(?:®|™)?\s*\d{1,2}\s*[-]?\s*(?:[A-Z]+\s*)?\d{3,5}[A-Z]*\b/i',
        '/\bRyzen\s*\d{1,2}\s*[-]?\s*\d{3,5}[A-Z]*\b/i',
        '/\bR[3579]\s*[-]?\s*\d{3,5}[A-Z]*\b/i',
    ];

// Kết quả $spec['cpu'] sẽ là: Xeon® W-11855M
// Hoặc nếu bạn muốn sạch hơn (bỏ chữ ®), bạn có thể dùng str_replace sau khi bắt được.
echo $spec['cpu'];

    foreach ($cpu_patterns as $pat) {
        if (preg_match($pat, $name, $m)) {
            $spec['cpu'] = trim($m[0]);
            break; // Tìm thấy thì dừng ngay để tránh bị ghi đè bởi pattern kém chính xác hơn bên dưới
        }
    }

    // RAM
    preg_match('/(\d+)\s?gb( ram)?/i', $name, $m);
    if ($m) $spec['ram'] = $m[1] . "GB";

    // Storage: ưu tiên có "ssd"/"hdd"/"nvme"/"m.2" (có thể đứng trước hoặc sau số),
    // nếu không thì tìm tất cả "NNN GB/TB" và chọn cái >100GB (tránh nhầm RAM) — chọn lớn nhất
    $storage_found = false;

    // 1) size trước type: "512GB SSD", "256 GB SSD"
    if (preg_match('/(\d+)\s?(gb|tb)\s*(?:ssd|hdd|nvme)/i', $name, $m)) {
        $num = intval($m[1]);
        $unit = strtolower($m[2]);
        $size_gb = ($unit === 'tb') ? $num * 1024 : $num;
        if ($size_gb > 100) {
            $spec['storage'] = strtoupper($m[1] . $m[2] . " " . strtoupper($m[3] ?? 'SSD'));
            $storage_found = true;
        }
    }

    // 2) type trước size: "M2.SSD 512GB", "M.2 NVMe 512 GB", "SSD 512GB"
    if (!$storage_found && preg_match('/(?:m\.?2|m2|nvme|ssd|hdd)[^\d]{0,6}(\d+)\s?(gb|tb)/i', $name, $m2)) {
        $num = intval($m2[1]);
        $unit = strtolower($m2[2]);
        $size_gb = ($unit === 'tb') ? $num * 1024 : $num;
        if ($size_gb > 100) {
            $spec['storage'] = strtoupper($m2[1] . $m2[2]);
            $storage_found = true;
        }
    }

    // 3) fallback: lấy tất cả các "NNN GB/TB" và chọn mục hợp lệ (>100GB) lớn nhất
    if (!$storage_found) {
        if (preg_match_all('/(\d+)\s?(gb|tb)/i', $name, $matches, PREG_SET_ORDER)) {
            $best = null;
            foreach ($matches as $match) {
                $num = intval($match[1]);
                $unit = strtolower($match[2]);
                $size_gb = ($unit === 'tb') ? $num * 1024 : $num;
                if ($size_gb > 100) {
                    if ($best === null || $size_gb > $best['size_gb']) {
                        $best = [
                            'text' => strtoupper($match[1] . $match[2]),
                            'size_gb' => $size_gb
                        ];
                    }
                }
            }
            if ($best !== null) {
                $spec['storage'] = $best['text'];
                $storage_found = true;
            }
        }
    }

    if (!$storage_found) {
        $spec['storage'] = '';
    }

    // GPU - mở rộng để bắt "NVIDIA® GeForce RTX™ 4050 6GB", "AMD Radeon 890M Graphics", v.v.
        $gpu_patterns = [
        // 1. Bắt NVIDIA (RTX, GTX...) - Chấp nhận ® ™ chen giữa
        // Giải thích: [®™]* nghĩa là có thể có ký tự bản quyền hoặc không
        '/\b(?:NVIDIA[®™]*\s*)?(?:GeForce[®™]*\s*)?(?:RTX|GTX|MX|Quadro)[®™]*\s*[-]?\s*[A-Z]*\d+[A-Z0-9]*(?:\s*(?:Ti|Super|Ada))?(?:\s+\d+\s*GB)?\b/u',

        // 2. Bắt AMD Radeon (RX...) - Chấp nhận ® ™
        '/\b(?:AMD[®™]*\s*)?Radeon[®™]*\s*RX\s*\d+[A-Z]*\b/u',

        // 3. Bắt AMD Radeon Onboard (780M, 890M...)
        '/\b(?:AMD[®™]*\s*)?Radeon[®™]*\s*\d+M\b/u',
        
        // 4. Bắt Intel Arc
        '/\bIntel[®™]*\s*Arc[®™]*(?:\s*Graphics)?(?:\s*[A-Z0-9]+)?\b/u',

        // 5. Bắt Intel Graphics chung
        '/\bIntel[®™]*\s*(?:Iris[®™]*\s*Xe|UHD)?\s*Graphics\b/u',
        '/\bIntel[®™]*\s*(?:Iris[®™]*\s*Xe|UHD)?\b/u',
    ];

    foreach ($gpu_patterns as $pat) {
        // Dùng 'u' modifier để hỗ trợ Unicode nếu chuỗi có ký tự lạ
        if (preg_match($pat, $name, $m)) {
            // Xử lý làm sạch chuỗi tìm được
            $raw_gpu = $m[0];
            
            // Xóa các ký tự rác thương hiệu (®, ™)
            $clean_gpu = preg_replace('/[®™]/u', '', $raw_gpu);
            
            // Chuẩn hóa khoảng trắng và viết hoa
            $spec['gpu'] = strtoupper(preg_replace('/\s+/', ' ', trim($clean_gpu)));
            
            break; // Tìm thấy cái xịn nhất thì dừng ngay
        }
    }

    // Display
    preg_match('/(\d{2}\.?\d*)\s?inch/i', $name, $m);
    if ($m) $spec['display'] = $m[1] . " inch";

    return $spec;
}