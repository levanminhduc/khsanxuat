<?php

function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function getCsrfInput() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function verifyCsrfOrDie() {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
    rotateCsrfToken();
}

function rotateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
