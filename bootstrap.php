<?php
// Điểm neo nạp cấu hình + kết nối DB cho toàn dự án.
// Mọi file nạp bootstrap qua đường dẫn tuyệt đối theo __DIR__ (xem từng Task).
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

require_once BASE_PATH . '/config/app.php';

// session_start có guard: an toàn cho file đã tự gọi session_start trước đó.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once BASE_PATH . '/config/database.php'; // cung cấp $connect
