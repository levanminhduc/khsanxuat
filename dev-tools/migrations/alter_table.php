<?php
require "contdb.php";

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database: " . mysqli_connect_error());
}

echo "Đang thay đổi kiểu dữ liệu của cột diem_danhgia...<br>";

// Thay đổi kiểu dữ liệu từ INT thành FLOAT
$sql = "ALTER TABLE danhgia_tieuchi MODIFY COLUMN diem_danhgia FLOAT";
if ($connect->query($sql) === TRUE) {
    echo "Thay đổi thành công! Cột diem_danhgia đã được chuyển sang kiểu FLOAT.<br>";
} else {
    echo "Lỗi khi thay đổi cấu trúc bảng: " . $connect->error . "<br>";
}

// Đóng kết nối
$connect->close();
?> 