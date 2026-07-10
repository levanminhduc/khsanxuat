<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

verifyCsrfOrDie();

$name = trim($_POST['name'] ?? '');
$pass = $_POST['password'] ?? '';

if ($name === '' || $pass === '') {
    header("Location: " . BASE_URL . "/account/login.php?error_message=Vui lòng nhập đầy đủ thông tin");
    exit();
}

$sql = "SELECT id, name, password, full_name FROM user WHERE name = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, "s", $name);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_bind_result($stmt, $user_id, $db_name, $db_password, $full_name);
    mysqli_stmt_fetch($stmt);
    if ($pass == $db_password) {
        session_regenerate_id(true);

        $_SESSION['id'] = $user_id;
        $_SESSION['name'] = $db_name;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['username'] = $db_name;    // trung voi 'name' nhung ActivityLogger doc key nay
        $_SESSION['user_id'] = $user_id;     // trung voi 'id' nhung auth-helper doc key nay

        // Nap role per-app cho khsanxuat; dong app='*' (super_admin) thang dong app cu the.
        // Role chi nap 1 lan luc login — thu hoi quyen co hieu luc khi login lai.
        $app_role = null;
        $role_sql = "SELECT app, role FROM user_app_role WHERE user_id = ? AND app IN ('khsanxuat', '*')";
        $role_stmt = mysqli_prepare($connect, $role_sql);
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

        error_log("Đăng nhập thành công - ID: {$user_id}, Username: {$db_name}, Full name: {$full_name}");

        if (isset($_SESSION['redirect_url'])) {
            $redirect_to = $_SESSION['redirect_url'];
            unset($_SESSION['redirect_url']);
            header("Location: " . $redirect_to);
        } else {
            header("Location: " . BASE_URL . "/index.php");
        }
        exit();
    } else {
        error_log("Đăng nhập thất bại - Sai mật khẩu cho username: {$name}");
        header("Location: " . BASE_URL . "/account/login.php?error_message=Sai tên đăng nhập hoặc mật khẩu");
        exit();
    }
} else {
    error_log("Đăng nhập thất bại - Username không tồn tại: {$name}");
    header("Location: " . BASE_URL . "/account/login.php?error_message=Sai tên đăng nhập hoặc mật khẩu");
    exit();
}

mysqli_stmt_close($stmt);
mysqli_close($connect);
