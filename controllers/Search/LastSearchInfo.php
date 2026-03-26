<?php
// controllers/Dashboard/SearchLogic.php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2)); 
}

require_once BASE_PATH . '/models/product/SearchHistoryModel.php';

function getLastSearchInfo($userId) {
    // Mặc định
    $result = [
        'label' => 'Chưa có lịch sử',
        'time'  => ''
    ];

    $queryText = null;
    $searchedAt = null;

    // CÁCH 1: Nếu đã đăng nhập, thử lấy từ Database trước
    if ($userId && class_exists('SearchHistoryModel')) {
        $sh = new SearchHistoryModel();
        $last = $sh->getLastSearch($userId);
        if ($last) {
            $queryText  = $last['query_text'] ?? null;
            $searchedAt = $last['searched_at'] ?? null;
        }
    }

    // CÁCH 2: Nếu Database không có (hoặc là khách), thử lấy từ Cookie
    if (!$queryText && isset($_COOKIE['last_guest_search'])) {
        $queryText  = $_COOKIE['last_guest_search'];
        $searchedAt = $_COOKIE['last_guest_time'] ?? null;
    }

    // XỬ LÝ HIỂN THỊ (Nếu tìm thấy dữ liệu)
    if ($queryText) {
        // Xử lý thời gian cho đẹp (Hôm qua, Hôm nay...)
        $timeNice = '';
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
                    $timeNice = $dt->format('d/m/Y');
                }
            } catch (Exception $e) {
                $timeNice = '';
            }
        }

        // Cắt ngắn nếu từ khóa quá dài
        $lastSearchLabel = mb_strlen($queryText) > 40 
            ? mb_substr($queryText, 0, 37) . '...' 
            : $queryText;

        $result['label'] = $lastSearchLabel;
        $result['time']  = $timeNice;
    }

    return $result;
}
?>