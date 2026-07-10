<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

// Nhan dich den tu nut "Dang nhap" o header de login_action redirect ve.
// Chi chap nhan path noi bo: bat dau '/', khong '//' (protocol-relative),
// khong backslash — chan open redirect.
if (isset($_GET['redirect']) && is_string($_GET['redirect'])) {
    $redirect_target = $_GET['redirect'];
    if (strpos($redirect_target, '/') === 0
        && strpos($redirect_target, '//') !== 0
        && strpos($redirect_target, '\\') === false) {
        $_SESSION['redirect_url'] = $redirect_target;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/account.css">
</head>

<body>
    <div class="login-container">
        <div class="logo-container">
            <a href="<?php echo BASE_URL; ?>/"><img width="300px" src="<?php echo BASE_URL; ?>/img/logo.png" alt="Logo" /></a>
        </div>
        <h2>Đăng Nhập</h2>

        <?php if (isset($_GET['error_message'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_GET['error_message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success_message'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['success_message']); ?></div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>/account/login_action.php" method="POST">
            <?php echo getCsrfInput(); ?>
            <label for="name">Tên đăng nhập</label>
            <input type="text" id="name" name="name" required><br>

            <label for="password">Mật khẩu</label>
            <input type="password" id="password" name="password" required><br>

            <button type="submit">Đăng nhập</button>
        </form>
    </div>
</body>

</html>