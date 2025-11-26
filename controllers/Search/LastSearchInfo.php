<?php
// controllers/Dashboard/SearchLogic.php

// Đảm bảo đường dẫn BASE_PATH đã được định nghĩa ở file config chung trước khi require file này
if (!defined('BASE_PATH')) {
    // Fallback nếu chưa định nghĩa (tùy chỉnh theo đường dẫn thực tế của bạn)
    define('BASE_PATH', dirname(__DIR__, 2)); 
}

require_once BASE_PATH . '/models/product/SearchHistoryModel.php';

function getLastSearchInfo($userId) {
    // Giá trị mặc định
    $result = [
        'label' => 'Không Có',
        'time' => 'Không Có'
    ];

    if (!$userId) return $result;

    if (class_exists('SearchHistoryModel')) {
        $sh = new SearchHistoryModel();
        $last = $sh->getLastSearch($userId);

        if ($last) {
            $queryText = $last['query_text'] ?? 'Không Có';
            $searchedAt = $last['searched_at'] ?? null;

            // Xử lý hiển thị thời gian
            $timeNice = '—';
            if ($searchedAt) {
                try {
                    $dt = new DateTime($searchedAt);
                    $now = new DateTime();
                    $yesterday = (new DateTime())->modify('-1 day');

                    if ($dt->format('Y-m-d') === $now->format('Y-m-d')) {
                        $timeNice = 'Hôm nay, ' . $dt->format('H:i');
                    } elseif ($dt->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                        $timeNice = 'Hôm qua, ' . $dt->format('H:i');
                    } else {
                        $timeNice = $dt->format('d M Y, H:i');
                    }
                } catch (Exception $e) {
                    $timeNice = $searchedAt;
                }
            }

            // Cắt chuỗi nếu quá dài
            $lastSearchLabel = mb_strlen($queryText) > 60 
                ? mb_substr($queryText, 0, 57) . '...' 
                : $queryText;

            $result['label'] = $lastSearchLabel;
            $result['time'] = $timeNice;
        }
    }

    return $result;
}
?>