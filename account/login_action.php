<?php
// Forwarder: xac thuc gio o authV2. Giu file lai cho form/POST cu con tro toi.
require_once __DIR__ . '/../bootstrap.php';

header('Location: ' . \Hoatho\SharedAuth\EnvConfig::loginUrl()
    . '?redirect=' . rawurlencode(BASE_URL . '/index.php'), true, 302);
exit;
