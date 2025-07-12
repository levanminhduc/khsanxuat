<?php

    // Thông tin kết nối cơ sở dữ liệu
    $connect = mysqli_connect('localhost', 'root', '', 'mysqli');

    // Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối: " . mysqli_connect_error());
}
