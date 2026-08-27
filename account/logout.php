<?php
// Forwarder: logout huy phien dung chung → lan ra MOI du an cung host, khong rieng khsanxuat.
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

// Link GET dung chung token trang (khong rotate) — cung pattern delete_image.php.
// Token sai/thieu: khong huy session (chan CSRF force-logout), ve trang chu.
$token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
if (!validateCsrfToken($token)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

header('Location: ' . \Hoatho\SharedAuth\EnvConfig::logoutUrl(BASE_URL . '/index.php'), true, 302);
exit;
