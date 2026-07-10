<?php
session_start();
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

// Link GET dung chung token trang (khong rotate) — cung pattern delete_image.php.
// Token sai/thieu: khong huy session (chan CSRF force-logout), ve trang chu.
$token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
if (!validateCsrfToken($token)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

header('Location: ' . BASE_URL . '/account/login.php?success_message=' . urlencode('Đã đăng xuất'));
exit;
