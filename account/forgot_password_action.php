<?php
session_start();
include('../contdb.php');

// Lấy dữ liệu từ form
$name = $_POST['name'];
$full_name = $_POST['full_name'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Kiểm tra mật khẩu xác nhận
if ($new_password !== $confirm_password) {
    header("Location: forgot_password.php?error_message=Mật khẩu xác nhận không khớp");
    exit();
}

// Kiểm tra xem tên đăng nhập có tồn tại không và họ tên đầy đủ có khớp không
$check_sql = "SELECT id, full_name FROM user WHERE name = ?";
$check_stmt = mysqli_prepare($connect, $check_sql);
mysqli_stmt_bind_param($check_stmt, "s", $name);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) > 0) {
    mysqli_stmt_bind_result($check_stmt, $user_id, $db_full_name);
    mysqli_stmt_fetch($check_stmt);
    
    // Kiểm tra họ tên đầy đủ có khớp không
    if ($full_name !== $db_full_name) {
        mysqli_stmt_close($check_stmt);
        header("Location: forgot_password.php?error_message=Họ tên đầy đủ không khớp với tài khoản");
        exit();
    }
    
    mysqli_stmt_close($check_stmt);
    
    // Cập nhật mật khẩu mới
    $update_sql = "UPDATE user SET password = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($connect, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "si", $new_password, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        // Cập nhật thành công
        mysqli_stmt_close($update_stmt);
        mysqli_close($connect);
        header("Location: login.php?success_message=Mật khẩu đã được đặt lại thành công! Vui lòng đăng nhập");
        exit();
    } else {
        // Lỗi khi cập nhật
        $error = mysqli_error($connect);
        mysqli_stmt_close($update_stmt);
        mysqli_close($connect);
        header("Location: forgot_password.php?error_message=Lỗi khi đặt lại mật khẩu: " . urlencode($error));
        exit();
    }
} else {
    // Tên đăng nhập không tồn tại
    mysqli_stmt_close($check_stmt);
    header("Location: forgot_password.php?error_message=Tên đăng nhập không tồn tại");
    exit();
} 