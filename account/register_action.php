<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

// Register gio la cong cu admin: user chung ~10 du an, tu dang ky se login duoc ca 10 he thong
requireLogin();
requireFeature('manage_users', 'redirect');
verifyCsrfOrDie();

$name = trim($_POST['name'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($name === '' || $full_name === '' || $password === '' || $confirm_password === '') {
    header("Location: " . BASE_URL . "/account/register.php?error_message=Vui lòng nhập đầy đủ thông tin");
    exit();
}

if ($password !== $confirm_password) {
    header("Location: " . BASE_URL . "/account/register.php?error_message=Mật khẩu xác nhận không khớp");
    exit();
}

if (strlen($password) < 8) {
    header("Location: " . BASE_URL . "/account/register.php?error_message=Mật khẩu phải có ít nhất 8 ký tự");
    exit();
}

$check_sql = "SELECT id FROM user WHERE name = ?";
$check_stmt = mysqli_prepare($connect, $check_sql);
mysqli_stmt_bind_param($check_stmt, "s", $name);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) > 0) {
    mysqli_stmt_close($check_stmt);
    header("Location: " . BASE_URL . "/account/register.php?error_message=Tên đăng nhập đã tồn tại");
    exit();
}

mysqli_stmt_close($check_stmt);

// Mat khau plaintext - giu nguyen co che cu (bang user dung chung ~10 du an)
$insert_sql = "INSERT INTO user (name, password, full_name) VALUES (?, ?, ?)";
$insert_stmt = mysqli_prepare($connect, $insert_sql);
mysqli_stmt_bind_param($insert_stmt, "sss", $name, $password, $full_name);

if (mysqli_stmt_execute($insert_stmt)) {
    // Ve lai register.php (khong ve login.php) de admin tao tiep tai khoan
    mysqli_stmt_close($insert_stmt);
    mysqli_close($connect);
    header("Location: " . BASE_URL . "/account/register.php?success_message=Tạo tài khoản thành công");
    exit();
} else {
    // Loi DB: log chi tiet noi bo, khong lo mysqli_error() ra URL
    error_log("register_action: insert user that bai cho name=$name - " . mysqli_error($connect));
    mysqli_stmt_close($insert_stmt);
    mysqli_close($connect);
    header("Location: " . BASE_URL . "/account/register.php?error_message=Có lỗi xảy ra, vui lòng thử lại sau");
    exit();
}
