<?php
session_start();
include('contdb.php');

$name = $_POST['name'];
$pass = $_POST['password'];

// Truy vấn lấy thêm full_name
$sql = "SELECT id, name, password, full_name FROM user WHERE name = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, "s", $name);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_bind_result($stmt, $user_id, $db_name, $db_password, $full_name);
    mysqli_stmt_fetch($stmt);
    if ($pass == $db_password) {
        // Lưu thông tin người dùng vào session
        $_SESSION['id'] = $user_id;
        $_SESSION['name'] = $db_name;
        $_SESSION['full_name'] = $full_name; // Lưu full_name
        $_SESSION['username'] = $db_name;    // Lưu username cho ActivityLogger
        $_SESSION['user_id'] = $user_id;     // Thêm user_id cho kiểm tra đăng nhập
        
        // Debug thông tin đăng nhập
        error_log("Đăng nhập thành công - ID: {$user_id}, Username: {$db_name}, Full name: {$full_name}");
        
        // Kiểm tra và xử lý redirect_url
        if (isset($_SESSION['redirect_url'])) {
            $redirect_to = $_SESSION['redirect_url'];
            unset($_SESSION['redirect_url']); // Xóa redirect_url sau khi sử dụng
            header("Location: " . $redirect_to);
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        // Trả về lỗi mật khẩu sai
        error_log("Đăng nhập thất bại - Sai mật khẩu cho username: {$name}");
        header("Location: login.php?error_message=Sai tên đăng nhập hoặc mật khẩu");
        exit();
    }
} else {
    // Trả về lỗi tên đăng nhập không tồn tại
    error_log("Đăng nhập thất bại - Username không tồn tại: {$name}");
    header("Location: login.php?error_message=Sai tên đăng nhập hoặc mật khẩu");
    exit();
}

mysqli_stmt_close($stmt);
mysqli_close($connect);
