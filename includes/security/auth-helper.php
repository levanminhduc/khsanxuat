<?php
// includes/security/auth-helper.php
// Phan quyen per-app: role nap vao $_SESSION['app_role'] moi request boi khsxSyncSessionContext().
// Call-site check theo FEATURE, khong so role truc tiep.
// Authentication (ai dang dang nhap) do authV2 lo; file nay chi con AUTHORIZATION.
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
    return \Hoatho\SharedAuth\SessionContract::isLoggedIn();
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

function requireLogin($mode = 'page')
{
    // Endpoint JSON khong the nhan 302 sang trang login: fetch() se follow redirect roi
    // tra ve HTML, JS parse JSON that bai va user thay loi kho hieu thay vi "het phien".
    if ($mode === 'json' && !isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn, vui lòng tải lại trang để đăng nhập lại']);
        exit;
    }
    (new \Hoatho\SharedAuth\Guard())->requireLogin();
}

function requireFeature($feature, $mode)
{
    if (userCan($feature)) {
        return;
    }
    // Chua/het dang nhap KHAC thieu quyen: dua ve trang login thay vi bao "khong co quyen"
    // (phien het han hoac phien cu truoc port authV2 se gay hieu nham "mat quyen").
    if (!isLoggedIn()) {
        requireLogin($mode); // json -> 401 JSON; con lai -> 302 sang login kem ?redirect=
    }
    $message = 'Bạn không có quyền thực hiện thao tác này';
    if ($mode === 'json') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
    } elseif ($mode === 'redirect') {
        header('Location: ' . BASE_URL . '/index.php?error=1&message=' . urlencode($message));
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
