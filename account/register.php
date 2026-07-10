<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

// Register gio la cong cu admin: user chung ~10 du an, tu dang ky se login duoc ca 10 he thong
requireLogin();
requireFeature('manage_users', 'page');
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/account.css">
</head>

<body>
    <div class="login-container">
        <div class="logo-container">
            <a href="<?php echo BASE_URL; ?>/"><img width="300px" src="<?php echo BASE_URL; ?>/img/logo.png" alt="Logo" /></a>
        </div>
        <h2>Đăng Ký</h2>

        <?php if (isset($_GET['error_message'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_GET['error_message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success_message'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['success_message']); ?></div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>/account/register_action.php" method="POST">
            <?php echo getCsrfInput(); ?>
            <label for="name">Tên đăng nhập</label>
            <input type="text" id="name" name="name" required><br>
            
            <label for="full_name">Họ tên đầy đủ</label>
            <input type="text" id="full_name" name="full_name" required><br>

            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" required><br>
            
            <label for="confirm_password">Xác nhận mật khẩu</label>
            <input type="password" id="confirm_password" name="confirm_password" required><br>

            <button type="submit">Đăng ký</button>
        </form>
        <a href="<?php echo BASE_URL; ?>/index.php">Về trang chủ</a>
    </div>
</body>

</html> 