<?php
// Nguồn kết nối DB duy nhất. Thay cho contdb.php + db_connect.php (đã xóa).
// Giữ nguyên hành vi cũ: cùng host/user/pass/db, cùng biến $connect.
$db_server   = 'localhost';
$db_user     = 'root';
$db_password = '';
$db_name     = 'mysqli';

$connect = mysqli_connect($db_server, $db_user, $db_password, $db_name);
if (!$connect) {
    error_log('config/database.php: DB connection failed: ' . mysqli_connect_error());
    die('Lỗi hệ thống. Vui lòng thử lại sau.');
}
