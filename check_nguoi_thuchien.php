<?php
// Kết nối database
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database: " . mysqli_connect_error());
}

// Kiểm tra cấu trúc cột nguoi_thuchien
$sql = "SHOW COLUMNS FROM danhgia_tieuchi LIKE 'nguoi_thuchien'";
$result = $connect->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Cấu trúc cột nguoi_thuchien:<br>";
    echo "Kiểu dữ liệu: " . $row['Type'] . "<br>";
    echo "Cho phép NULL: " . $row['Null'] . "<br>";
    echo "Giá trị mặc định: " . $row['Default'] . "<br>";
} else {
    echo "Không tìm thấy cột nguoi_thuchien trong bảng danhgia_tieuchi";
}

// Đóng kết nối
$connect->close();
?> 