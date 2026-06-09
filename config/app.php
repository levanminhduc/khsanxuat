<?php
// Base URL cho mọi link/redirect/form action. Đổi 1 chỗ khi deploy folder/host khác.
// Môi trường thật: http://localhost/khsanxuat/  → '/khsanxuat'
if (!defined('BASE_URL')) {
    define('BASE_URL', '/khsanxuat');
}

// Error config thống nhất (giữ nguyên hành vi production hiện tại của các trang).
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
