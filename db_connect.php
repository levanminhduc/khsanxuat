<?php
    // Thông tin kết nối cơ sở dữ liệu
    $db_server = 'localhost';
    $db_user = 'root';
    $db_password = '';
    $db_name = 'mysqli'; // Cơ sở dữ liệu mặc định
    
    // Tạo kết nối
    $connect = mysqli_connect($db_server, $db_user, $db_password, $db_name);
    
    // Kiểm tra kết nối
    if (!$connect) {
        die("Lỗi kết nối: " . mysqli_connect_error());
    }
?> 