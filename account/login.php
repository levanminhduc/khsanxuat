<?php
// Forwarder: dang nhap gio o authV2 (SSO noi bo). Giu file lai cho bookmark/link cu.
require_once __DIR__ . '/../bootstrap.php';

$redirect = isset($_GET['redirect']) && is_string($_GET['redirect']) ? $_GET['redirect'] : '';
if ($redirect === '') {
    $redirect = BASE_URL . '/index.php';
}

header('Location: ' . \Hoatho\SharedAuth\EnvConfig::loginUrl() . '?redirect=' . rawurlencode($redirect), true, 302);
exit;
