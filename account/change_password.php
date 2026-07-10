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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/account.css">
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