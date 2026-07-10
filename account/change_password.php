<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

// Bat buoc dang nhap: doi mat khau khong con la cong rieng hoi username + mat khau hien tai
requireLogin();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thay Đổi Mật Khẩu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: rgba(143, 245, 245, 0.92);
            /* Có thể thay đổi nền cho toàn bộ trang tại đây */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        h2 {
            margin-bottom: 20px;
            color: rgb(42, 7, 240);
            /* Màu chữ cho phần "Thay Đổi Mật Khẩu" */
        }

        label {
            color: #rgb(95, 19, 19);
            /* Màu chữ cho các nhãn trường nhập liệu */
            margin-bottom: 5px;
        }

        /* Nền của phần login-container có thể thay đổi ở đây */
        .login-container {
            background-color: rgb(255, 255, 255);
            /* Nền có thể thay đổi tại đây */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(4, 32, 155, 0.1);
            width: 300px;
            text-align: center;
            /* Giúp logo canh giữa */
        }

        h2 {
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        input {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            padding: 10px;
            background-color: rgb(42, 7, 240);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: rgb(69, 86, 160);
        }

        a {
            margin-top: 10px;
            display: block;
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
            margin-bottom: 10px;
        }
        
        .success-message {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo-container">
            <a href="<?php echo BASE_URL; ?>/"><img width="300px" src="<?php echo BASE_URL; ?>/img/logo.png" alt="Logo" /></a>
        </div>
        <h2>Thay Đổi Mật Khẩu</h2>

        <?php if (isset($_GET['error_message'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_GET['error_message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success_message'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['success_message']); ?></div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>/account/change_password_action.php" method="POST">
            <?php echo getCsrfInput(); ?>
            <label for="current_password">Mật khẩu hiện tại</label>
            <input type="password" id="current_password" name="current_password" required><br>

            <label for="new_password">Mật khẩu mới</label>
            <input type="password" id="new_password" name="new_password" required><br>

            <label for="confirm_password">Xác nhận mật khẩu mới</label>
            <input type="password" id="confirm_password" name="confirm_password" required><br>

            <button type="submit">Thay đổi mật khẩu</button>
        </form>
        <a href="<?php echo BASE_URL; ?>/index.php">Về trang chủ</a>
    </div>
</body>

</html> 