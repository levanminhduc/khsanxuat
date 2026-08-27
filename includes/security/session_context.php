<?php
// authV2 chi ghi user_id/name/logged_in_at. Cac khoa danh tinh duoi day truoc kia do login_action
// dung MOT LAN luc login; nay login xay ra o du an khac nen phai dung lai MOI REQUEST tu DB.
// Gia tri luu THO — view (components/header.php, activity_logger) tu escape.

function khsxSyncSessionContext($connect)
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (!\Hoatho\SharedAuth\SessionContract::isLoggedIn()) {
        unset($_SESSION['full_name'], $_SESSION['username'], $_SESSION['app_role']);
        return;
    }

    $user_id = (int) \Hoatho\SharedAuth\SessionContract::userId();

    $stmt = mysqli_prepare($connect, "SELECT name, full_name FROM user WHERE id = ? LIMIT 1");
    if (!$stmt) {
        error_log('khsxSyncSessionContext: prepare user that bai - ' . mysqli_error($connect));
        return;
    }
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $db_name, $db_full_name);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$found) {
        // User bi xoa trong luc phien con song: khong de lai bối cảnh/quyen cu
        unset($_SESSION['full_name'], $_SESSION['username'], $_SESSION['app_role']);
        return;
    }

    $_SESSION['username'] = $db_name;
    $_SESSION['full_name'] = $db_full_name;

    // Role per-app cho khsanxuat; dong app='*' (super_admin) thang dong app cu the.
    // Doc lai moi request → thu hoi quyen co hieu luc ngay, khong can login lai.
    $app_role = null;
    $role_stmt = mysqli_prepare(
        $connect,
        "SELECT app, role FROM user_app_role WHERE user_id = ? AND app IN ('khsanxuat', '*')"
    );
    if (!$role_stmt) {
        error_log('khsxSyncSessionContext: prepare user_app_role that bai - ' . mysqli_error($connect));
        $_SESSION['app_role'] = null;
        return;
    }
    mysqli_stmt_bind_param($role_stmt, "i", $user_id);
    mysqli_stmt_execute($role_stmt);
    mysqli_stmt_bind_result($role_stmt, $row_app, $row_role);
    while (mysqli_stmt_fetch($role_stmt)) {
        $app_role = $row_role;
        if ($row_app === '*') {
            break;
        }
    }
    mysqli_stmt_close($role_stmt);

    $_SESSION['app_role'] = $app_role;
}
