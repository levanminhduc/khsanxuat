<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

// Bat buoc dang nhap + CSRF: doi mat khau chi cho user dang session, khong con hoi username
requireLogin();
verifyCsrfOrDie();

$user_id = \Hoatho\SharedAuth\SessionContract::userId();

// Mat khau khong trim - giu nguyen theo quy uoc cu
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($current_password === '' || $new_password === '' || $confirm_password === '') {
    header("Location: " . BASE_URL . "/account/change_password.php?error_message=Vui lòng nhập đầy đủ thông tin");
    exit();
}

if ($new_password !== $confirm_password) {
    header("Location: " . BASE_URL . "/account/change_password.php?error_message=Mật khẩu xác nhận không khớp");
    exit();
}

if (strlen($new_password) < 8) {
    header("Location: " . BASE_URL . "/account/change_password.php?error_message=Mật khẩu mới phải có ít nhất 8 ký tự");
    exit();
}

$check_sql = "SELECT password FROM user WHERE id = ?";
$check_stmt = mysqli_prepare($connect, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $user_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) > 0) {
    mysqli_stmt_bind_result($check_stmt, $db_password);
    mysqli_stmt_fetch($check_stmt);
    mysqli_stmt_close($check_stmt);

    // Diem DOC password: phai hieu ca plaintext lan bcrypt (bat bien rollout authV2), vi bang user
    // dung chung ~10 du an va se co hash tron lan khi bat upgrade-on-login o Giai doan 2.
    $current_ok = strncmp($db_password, '$2', 2) === 0
        ? password_verify($current_password, $db_password)
        : hash_equals($db_password, $current_password);

    if (!$current_ok) {
        header("Location: " . BASE_URL . "/account/change_password.php?error_message=Mật khẩu hiện tại không đúng");
        exit();
    }

    $update_sql = "UPDATE user SET password = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($connect, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "si", $new_password, $user_id);

    if (mysqli_stmt_execute($update_stmt)) {
        mysqli_stmt_close($update_stmt);
        mysqli_close($connect);
        header("Location: " . BASE_URL . "/account/change_password.php?success_message=Mật khẩu đã được thay đổi thành công");
        exit();
    } else {
        // Loi DB: log chi tiet noi bo, khong lo mysqli_error() ra URL
        error_log("change_password_action: update password that bai cho user_id=$user_id - " . mysqli_error($connect));
        mysqli_stmt_close($update_stmt);
        mysqli_close($connect);
        header("Location: " . BASE_URL . "/account/change_password.php?error_message=Có lỗi xảy ra, vui lòng thử lại sau");
        exit();
    }
} else {
    // User bi xoa trong luc session con song - khong lo chi tiet
    mysqli_stmt_close($check_stmt);
    header("Location: " . BASE_URL . "/account/change_password.php?error_message=Có lỗi xảy ra, vui lòng thử lại sau");
    exit();
}
