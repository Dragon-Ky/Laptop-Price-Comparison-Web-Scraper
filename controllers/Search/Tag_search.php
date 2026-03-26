<?php
// Tag_search.php

/* =================================================================================
   PHẦN 1: HÀM TÁCH THÔNG SỐ KỸ THUẬT (PARSE SPECS)
   ================================================================================= */
function parse_laptop_specs($name) {
    $spec = [
        'cpu' => '',
        'ram' => '',
        'storage' => '',
        'gpu' => '',
        'display' => ''
    ];

    // --- 1. XỬ LÝ CPU ---
    $cpu_patterns = [
        // Bắt Xeon có version (Vd: Xeon E3-1505M v5)
        '/\bXeon(?:®|™)?\s*(?:[A-Z0-9]+\s*[-]?\s*)?\d{3,5}[A-Z0-9]*(?:\s*v\d+)?\b/i',
        
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

    // --- 2. XỬ LÝ RAM ---
    preg_match('/(\d+)\s?gb( ram)?/i', $name, $m);
    if ($m) $spec['ram'] = $m[1] . "GB";

    // --- 3. XỬ LÝ Ổ CỨNG (STORAGE) ---
    $storage_found = false;
    // Case 1: Số trước (512GB SSD)
    if (preg_match('/(\d+)\s?(gb|tb)\s*(?:ssd|hdd|nvme)/i', $name, $m)) {
        $num = intval($m[1]); $unit = strtolower($m[2]);
        $size_gb = ($unit === 'tb') ? $num * 1024 : $num;
        if ($size_gb > 100) {
            $spec['storage'] = strtoupper($m[1] . $m[2]);
            $storage_found = true;
        }
    }
    // Case 2: Loại trước (SSD 512GB)
    if (!$storage_found && preg_match('/(?:m\.?2|m2|nvme|ssd|hdd)[^\d]{0,6}(\d+)\s?(gb|tb)/i', $name, $m2)) {
        $num = intval($m2[1]); $unit = strtolower($m2[2]);
        $size_gb = ($unit === 'tb') ? $num * 1024 : $num;
        if ($size_gb > 100) {
            $spec['storage'] = strtoupper($m2[1] . $m2[2]);
            $storage_found = true;
        }
    }
    // Case 3: Tìm số GB lớn nhất nếu chưa thấy
    if (!$storage_found) {
        if (preg_match_all('/(\d+)\s?(gb|tb)/i', $name, $matches, PREG_SET_ORDER)) {
            $best = null;
            foreach ($matches as $match) {
                $num = intval($match[1]); $unit = strtolower($match[2]);
                $size_gb = ($unit === 'tb') ? $num * 1024 : $num;
                // Lọc ram 8GB/16GB ra, chỉ lấy > 100GB
                if ($size_gb > 100) {
                    if ($best === null || $size_gb > $best['size_gb']) {
                        $best = ['text' => strtoupper($match[1] . $match[2]), 'size_gb' => $size_gb];
                    }
                }
            }
            if ($best !== null) $spec['storage'] = $best['text'];
        }
    }

    // --- 4. XỬ LÝ GPU (VGA) ---
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

    // --- 5. XỬ LÝ MÀN HÌNH ---
    preg_match('/(\d{2}\.?\d*)\s?inch/i', $name, $m);
    if ($m) $spec['display'] = $m[1] . " inch";

    return $spec;
}

/* =================================================================================
   PHẦN 2: HÀM LÀM SẠCH TÊN (GROUPING LOGIC)
   ================================================================================= */
function clean_product_name($name) {
    // 1. CHUẨN HÓA CƠ BẢN
    $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $name = str_replace(['–', '—'], '-', $name);
    $name = str_replace(['“', '”', '‘', '’', '"'], ' ', $name);
    $name = mb_strtolower($name, 'UTF-8');

    // 2. XÓA NỘI DUNG TRONG NGOẶC
    $name = preg_replace('/[\(\[\{].*?[\)\]\}]/u', ' ', $name);

    // =========================================================================
    // 3. XỬ LÝ GOM NHÓM THÔNG MINH (Đặt trước khi cắt chuỗi)
    // =========================================================================

    // A. Xử lý ACER NITRO V15
    if (preg_match('/(ANV15[\s-]+\d{2})[\s-]+[A-Z0-9]{4}/i', $name, $matches)) {
        $name = str_replace($matches[0], $matches[1], $name);
    }

    // B. Xử lý ACER NITRO 5
    if (preg_match('/(AN\d{3}[-\s]+\d{2})[-\s]+[A-Z0-9]{4}/i', $name, $matches)) {
        $name = str_replace($matches[0], $matches[1], $name);
    }

    // C. Xử lý ASUS TỔNG QUÁT
    if (preg_match('/([A-Z]{1,2}\d{3,4}[A-Z]{1,2})[-\s]+[A-Z0-9]{3,}/i', $name, $matches)) {
        $name = str_replace($matches[0], $matches[1], $name);
    }

    // =========================================================================
    // 4. CẮT ĐUÔI (TRUNCATE) DỰA TRÊN TỪ KHÓA CẤU HÌNH
    // =========================================================================
    $cut_patterns = [
        '/\bnext\s*gen(\s*ai)?\b/u',
        '/\bcopilot\+?\b/u',
        '/\bnk\b/u',
        '/\bchíp\s*ai\b/u',
        '/\bchip\s*ai\b/u',
        '/\bwin(dows)?\s*\d+/u',
        '/\bos\s*:/u',
        '/\bchính hãng\b/u',
        '/\bnhập khẩu\b/u',
        '/\bxeon\b/u',
        '/\bultra\s*[3579]\b/u',       
        '/\bcore\s*ultra\b/u',         
        '/\bcore\s*[3579]\s+\d+/u',
        '/\b(?:Intel\s*)?Core\s*[3579]\s*[-]?\s*\d{3,5}[A-Z0-9]*\b/i',
        '/\bi[3579][\s-]*\d{4}/u',     
        '/\br[3579][\s-]*\d{4}/u',    
        '/\bryzen\s*\d/u',
        '/\bcore\s*i[3579]\b/u',
        '/\b\d+\s*gb\b/u',
        '/\b\d+\s*tb\b/u',
        '/\b\d+(\.\d+)?\s*(inch|”|")/u',
        '/\b(full\s*hd|fhd|2k|4k)\b/u',
    ];

    $shortest_length = mb_strlen($name);
    $found_cut = false;

    foreach ($cut_patterns as $pat) {
        if (preg_match($pat, $name, $matches, PREG_OFFSET_CAPTURE)) {
            $match_str = $matches[0][0];
            $pos = mb_strpos($name, $match_str);
            if ($pos !== false && $pos < $shortest_length) {
                $shortest_length = $pos;
                $found_cut = true;
            }
        }
    }

    if ($found_cut) {
        $name = mb_substr($name, 0, $shortest_length);
    }

    // =========================================================================
    // 5. XÓA MÃ SKU RÁC (CHỈ CHẠY 1 LẦN DUY NHẤT TẠI ĐÂY)
    // =========================================================================
    $parts = explode(' ', preg_replace('/\s+/', ' ', $name));
    $clean_parts = [];
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;

        // A. Giữ lại mã có dấu gạch ngang NHƯNG phải kiểm tra độ dài
        if (str_contains($part, '-') && mb_strlen($part) > 1) {
            // [MỚI] Nếu mã dài hơn 10 ký tự (như 16-ar0013dx là 11 ký tự) -> XÓA NGAY
            if (mb_strlen($part) > 10) {
                continue; 
            }
            
            $clean_parts[] = $part;
            continue;
        }

        // B. BẢO VỆ MÃ MODEL CHUẨN
        
        // Rule 1: Dạng kẹp (Vd: FX506HC, K3605ZF - Chữ + Số + Chữ)
        if (preg_match('/^[a-z]{1,2}\d{3,4}[a-z]{1,2}$/i', $part)) {
            $clean_parts[] = $part;
            continue;
        }

        // Rule 2: Dạng Chữ + Số (Vd: PC14250, G15, Nitro5...) - QUAN TRỌNG ĐỂ GIỮ PC14250
        if (preg_match('/^[a-z]+\d+$/i', $part)) {
            $clean_parts[] = $part;
            continue;
        }

        // C. Xóa mã rác dài (Vd: B93GZAT)
        if (mb_strlen($part) >= 6) {
            $has_letter = preg_match('/[a-z]/', $part);
            $has_digit  = preg_match('/[0-9]/', $part);
            
            // Nếu có cả chữ và số (nhưng không lọt vào Rule 1 hoặc Rule 2) thì xóa
            if ($has_letter && $has_digit) {
                continue; 
            }
        }
        
        $clean_parts[] = $part;
    }
    $name = implode(' ', $clean_parts);

    // 6. CLEANUP TỪ KHÓA THỪA
    $remove_keywords = [
        'laptop', 'máy tính', 'ultrabook',
        'black', 'silver', 'grey', 'white', 'gold', 'blue',
        'đen', 'trắng', 'bạc', 'xám', 'vàng', 'xanh',
        'chính', 'hãng',
    ];
    
    foreach ($remove_keywords as $word) {
        $name = preg_replace('/\b'.preg_quote($word, '/').'\b/u', ' ', $name);
    }

    // 7. HOÀN THIỆN & VIẾT HOA CHUẨN
    $name = preg_replace('/[^a-z0-9\s\-]/u', ' ', $name);
    $name = trim($name, " -");
    $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8");

    // Viết hoa toàn bộ các mã máy (Fx506hc -> FX506HC, Pc14250 -> PC14250)
    $name = preg_replace_callback('/\b[a-zA-Z]+\d+[a-zA-Z0-9]*\b/', function($m) {
        return strtoupper($m[0]);
    }, $name);

    return trim(preg_replace('/\s+/', ' ', $name));
}
?>