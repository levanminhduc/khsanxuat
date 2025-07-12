<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'contdb.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối: " . mysqli_connect_error());
}

echo "<h1>Danh sách các bảng trong cơ sở dữ liệu</h1>";

// Liệt kê tất cả các bảng
$result = mysqli_query($connect, "SHOW TABLES");

echo "<ul>";
while ($row = mysqli_fetch_row($result)) {
    echo "<li>{$row[0]}</li>";
}
echo "</ul>";

// Kiểm tra cấu trúc bảng default_settings
echo "<h2>Cấu trúc bảng default_settings</h2>";
$result = mysqli_query($connect, "DESCRIBE default_settings");

if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Bảng default_settings không tồn tại hoặc có lỗi: " . mysqli_error($connect) . "</p>";
}

// Kiểm tra các khóa của bảng default_settings
echo "<h2>Các khóa của bảng default_settings</h2>";
$result = mysqli_query($connect, "SHOW KEYS FROM default_settings");

if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Table</th><th>Non_unique</th><th>Key_name</th><th>Seq_in_index</th><th>Column_name</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Table']}</td>";
        echo "<td>{$row['Non_unique']}</td>";
        echo "<td>{$row['Key_name']}</td>";
        echo "<td>{$row['Seq_in_index']}</td>";
        echo "<td>{$row['Column_name']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Không thể lấy thông tin khóa của bảng default_settings: " . mysqli_error($connect) . "</p>";
}

// Kiểm tra cấu trúc bảng khsanxuat
echo "<h2>Cấu trúc bảng khsanxuat</h2>";
$result = mysqli_query($connect, "DESCRIBE khsanxuat");

if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Bảng khsanxuat không tồn tại hoặc có lỗi: " . mysqli_error($connect) . "</p>";
}

// Đóng kết nối
mysqli_close($connect);
?> 