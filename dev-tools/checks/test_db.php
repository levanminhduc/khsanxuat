<?php
// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối cơ sở dữ liệu
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    die('Lỗi kết nối cơ sở dữ liệu: ' . mysqli_connect_error());
}

echo '<h2>Kết nối cơ sở dữ liệu thành công!</h2>';

// Kiểm tra bảng default_settings
$result = $connect->query("SHOW TABLES LIKE 'default_settings'");
if ($result->num_rows > 0) {
    echo '<p>Bảng default_settings đã tồn tại.</p>';
    
    // Kiểm tra dữ liệu trong bảng
    $data = $connect->query("SELECT COUNT(*) as count FROM default_settings");
    $row = $data->fetch_assoc();
    echo '<p>Số lượng bản ghi trong bảng: ' . $row['count'] . '</p>';
} else {
    echo '<p>Bảng default_settings chưa tồn tại.</p>';
    
    // Tạo bảng
    echo '<p>Đang tạo bảng default_settings...</p>';
    $sql_create_table = "CREATE TABLE default_settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        dept VARCHAR(50) NOT NULL,
        id_tieuchi INT(11) NOT NULL,
        ngay_tinh_han VARCHAR(30) NOT NULL DEFAULT 'ngay_vao',
        so_ngay_xuly INT(11) NOT NULL DEFAULT 7,
        ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
        ngay_capnhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_dept_tieuchi (dept, id_tieuchi)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($connect->query($sql_create_table)) {
        echo '<p>Đã tạo bảng default_settings thành công!</p>';
    } else {
        echo '<p>Lỗi khi tạo bảng: ' . $connect->error . '</p>';
    }
}

// Kiểm tra file JavaScript và PHP
echo '<h3>Kiểm tra các file xử lý</h3>';
$files = [
    'save_default_setting.php',
    'save_all_default_settings.php',
    'apply_default_settings.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p>File $file tồn tại</p>";
    } else {
        echo "<p style='color: red;'>File $file không tồn tại!</p>";
    }
}

// Kiểm tra bảng danhgia_tieuchi
$tieuchi_result = $connect->query("SHOW COLUMNS FROM danhgia_tieuchi LIKE 'ngay_tinh_han'");
if ($tieuchi_result->num_rows > 0) {
    echo '<p>Cột ngay_tinh_han đã tồn tại trong bảng danhgia_tieuchi.</p>';
} else {
    echo '<p style="color: orange;">Cột ngay_tinh_han chưa tồn tại trong bảng danhgia_tieuchi.</p>';
    
    echo '<p>Đang thêm cột ngay_tinh_han vào bảng danhgia_tieuchi...</p>';
    $sql_add_column = "ALTER TABLE danhgia_tieuchi ADD COLUMN ngay_tinh_han VARCHAR(20) DEFAULT 'ngay_vao' AFTER so_ngay_xuly";
    
    if ($connect->query($sql_add_column)) {
        echo '<p>Đã thêm cột ngay_tinh_han thành công!</p>';
    } else {
        echo '<p>Lỗi khi thêm cột: ' . $connect->error . '</p>';
    }
}

echo '<p><a href="indexdept.php?dept=kehoach&id=1">Quay lại indexdept.php</a></p>';
?> 