<?php
// Điểm neo nạp cấu hình + kết nối DB cho toàn dự án.
// Mọi file nạp bootstrap qua đường dẫn tuyệt đối theo __DIR__ (xem từng Task).
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

require_once BASE_PATH . '/config/app.php';

// Phiên dùng chung authV2 (SSO nội bộ). .htaccess auto_prepend_file đã cấu hình phiên trước mọi
// request; start() ở đây là điểm neo idempotent cho file nào chưa tự session_start.
require_once __DIR__ . '/../authV2/bootstrap.php';

require_once BASE_PATH . '/config/database.php'; // cung cấp $connect

require_once BASE_PATH . '/includes/security/session_context.php';
khsxSyncSessionContext($connect);
