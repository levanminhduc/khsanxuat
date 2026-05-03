<?php
// Script để cập nhật tên cột trong bảng khsanxuat_default_settings
require "contdb.php";

echo "Bắt đầu cập nhật tên cột trong bảng khsanxuat_default_settings...\n";

// Kiểm tra bảng
$check_table = $connect->query("SHOW TABLES LIKE 'khsanxuat_default_settings'");
if ($check_table->num_rows === 0) {
    echo "Bảng khsanxuat_default_settings không tồn tại, không thể cập nhật.\n";
    exit();
}

// Kiểm tra xem cột mới đã tồn tại chưa
$check_new_column = $connect->query("SHOW COLUMNS FROM khsanxuat_default_settings LIKE 'nguoi_chiu_trachnhiem_default'");
if ($check_new_column->num_rows > 0) {
    echo "Cột nguoi_chiu_trachnhiem_default đã tồn tại, không cần cập nhật.\n";
    exit();
}

// Kiểm tra xem cột cũ có tồn tại không
$check_old_column = $connect->query("SHOW COLUMNS FROM khsanxuat_default_settings LIKE 'nguoi_thuchien_default'");
if ($check_old_column->num_rows === 0) {
    echo "Cột nguoi_thuchien_default không tồn tại, không thể thực hiện cập nhật.\n";
    exit();
}

// Thực hiện cập nhật tên cột
$alter_query = "ALTER TABLE khsanxuat_default_settings 
                CHANGE COLUMN nguoi_thuchien_default nguoi_chiu_trachnhiem_default INT(11) NULL";

try {
    // Bắt đầu transaction
    $connect->begin_transaction();
    
    if ($connect->query($alter_query)) {
        echo "Đã cập nhật thành công cột nguoi_thuchien_default thành nguoi_chiu_trachnhiem_default.\n";
        $connect->commit();
    } else {
        throw new Exception("Lỗi khi cập nhật cột: " . $connect->error);
    }
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $connect->rollback();
    echo "Lỗi: " . $e->getMessage() . "\n";
}

echo "Hoàn tất quá trình cập nhật tên cột.\n";
?> 