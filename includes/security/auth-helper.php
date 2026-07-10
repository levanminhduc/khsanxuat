<?php
// includes/security/auth-helper.php
// Phan quyen per-app: role nap vao $_SESSION['app_role'] luc login (1 lan; thu hoi quyen
// co hieu luc khi login lai). Call-site check theo FEATURE, khong so role truc tiep.
// Yeu cau nap SAU bootstrap.php (da start session + define BASE_URL).

// Nguon duy nhat dinh nghia quyen cua khsanxuat. Them role/feature = sua mang nay.
$GLOBALS['app_role_features'] = [
    'admin'       => ['edit_settings', 'manage_users'],
    'super_admin' => ['edit_settings', 'manage_users'], // luon la superset cua admin
];

function currentAppRole()
{
    return isset($_SESSION['app_role']) ? $_SESSION['app_role'] : null;
}

function isLoggedIn()
{
    return !empty($_SESSION['user_id']);
}

function userCan($feature)
{
    if (!isLoggedIn()) {
        return false;
    }
    $role = currentAppRole();
    if ($role === null || !isset($GLOBALS['app_role_features'][$role])) {
        return false;
    }
    return in_array($feature, $GLOBALS['app_role_features'][$role], true);
}

function requireLogin()
{
    if (isLoggedIn()) {
        return;
    }
    // Luu URL hien tai de login_action redirect ve (pattern redirect_url co san)
    $_SESSION['redirect_url'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : BASE_URL . '/index.php';
    header('Location: ' . BASE_URL . '/account/login.php');
    exit;
}

function requireFeature($feature, $mode)
{
    if (userCan($feature)) {
        return;
    }
    $message = 'Bạn không có quyền thực hiện thao tác này';
    if ($mode === 'json') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
    } elseif ($mode === 'redirect') {
        header('Location: ' . BASE_URL . '/index.php?error=' . urlencode($message));
    } else { // 'page'
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>Không có quyền</title></head>'
            . '<body style="font-family:sans-serif;text-align:center;padding:48px">'
            . '<h2>Bạn không có quyền truy cập trang này</h2>'
            . '<p>Liên hệ quản trị viên nếu cần cấp quyền.</p>'
            . '<a href="' . BASE_URL . '/index.php">Về trang chủ</a></body></html>';
    }
    exit;
}
