<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
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
            /* Màu chữ cho phần "Đăng Nhập" */
        }

        label {
            color: #rgb(95, 19, 19);
            /* Màu chữ cho "Tên đăng nhập" và "Mật khẩu" */
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
            <a href="./"><img width="300px" src="img/logo.png" alt="Logo" /></a>
        </div>
        <h2>Đăng Nhập</h2>

        <?php if (isset($_GET['error_message'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_GET['error_message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success_message'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['success_message']); ?></div>
        <?php endif; ?>

        <form action="login_action.php" method="POST">
            <label for="name">Tên đăng nhập</label>
            <input type="text" id="name" name="name" required><br>

            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" required><br>

            <button type="submit">Đăng nhập</button>
        </form>
        <a href="register.php">Chưa có tài khoản? Đăng ký ngay</a>
        <a href="account/forgot_password.php">Quên mật khẩu?</a>
        <a href="account/change_password.php">Thay đổi mật khẩu</a>
    </div>
</body>

</html>