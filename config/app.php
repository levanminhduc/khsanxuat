<?php
if (!defined('BASE_URL')) {
    $app_root = str_replace('\\', '/', realpath(defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/..'));
    $doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($doc_root && stripos($app_root, $doc_root) === 0) {
        define('BASE_URL', rtrim(substr($app_root, strlen($doc_root)), '/'));
    } else {
        define('BASE_URL', '/' . basename($app_root)); // fallback: tên folder app
    }
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
