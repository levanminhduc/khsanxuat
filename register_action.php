<?php
session_start();
include('contdb.php');

// Lấy dữ liệu từ form
$name = $_POST['name'];
$full_name = $_POST['full_name'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Kiểm tra mật khẩu xác nhận
if ($password !== $confirm_password) {
    header("Location: register.php?error_message=Mật khẩu xác nhận không khớp");
    exit();
}

// Kiểm tra xem tên đăng nhập đã tồn tại chưa
$check_sql = "SELECT id FROM user WHERE name = ?";
$check_stmt = mysqli_prepare($connect, $check_sql);
mysqli_stmt_bind_param($check_stmt, "s", $name);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) > 0) {
    // Tên đăng nhập đã tồn tại
    mysqli_stmt_close($check_stmt);
    header("Location: register.php?error_message=Tên đăng nhập đã tồn tại");
    exit();
}

mysqli_stmt_close($check_stmt);

// Thêm người dùng mới vào cơ sở dữ liệu
$insert_sql = "INSERT INTO user (name, password, full_name) VALUES (?, ?, ?)";
$insert_stmt = mysqli_prepare($connect, $insert_sql);
mysqli_stmt_bind_param($insert_stmt, "sss", $name, $password, $full_name);

if (mysqli_stmt_execute($insert_stmt)) {
    // Đăng ký thành công
    mysqli_stmt_close($insert_stmt);
    mysqli_close($connect);
    header("Location: login.php?success_message=Đăng ký thành công! Vui lòng đăng nhập");
    exit();
} else {
    // Lỗi khi thêm người dùng
    $error = mysqli_error($connect);
    mysqli_stmt_close($insert_stmt);
    mysqli_close($connect);
    header("Location: register.php?error_message=Lỗi khi đăng ký: " . urlencode($error));
    exit();
} 