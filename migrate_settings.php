<?php
// Script để chuyển dữ liệu từ bảng default_settings sang khsanxuat_default_settings
require "contdb.php";

echo "Bắt đầu chuyển dữ liệu cài đặt mặc định...\n";

// Kiểm tra bảng cũ
$check_old_table = $connect->query("SHOW TABLES LIKE 'default_settings'");
if ($check_old_table->num_rows === 0) {
    echo "Bảng default_settings không tồn tại, không cần chuyển dữ liệu.\n";
    exit();
}

// Kiểm tra và tạo bảng mới nếu chưa tồn tại
$check_new_table = $connect->query("SHOW TABLES LIKE 'khsanxuat_default_settings'");
if ($check_new_table->num_rows === 0) {
    echo "Bảng khsanxuat_default_settings chưa tồn tại, tạo bảng mới...\n";
    
    $sql_create_table = "CREATE TABLE khsanxuat_default_settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        dept VARCHAR(50) NOT NULL,
        id_tieuchi INT(11) NOT NULL,
        ngay_tinh_han VARCHAR(30) NOT NULL DEFAULT 'ngay_vao',
        so_ngay_xuly INT(11) NOT NULL DEFAULT 7,
        nguoi_chiu_trachnhiem_default INT(11) NULL,
        ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
        ngay_capnhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_dept_tieuchi (dept, id_tieuchi)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!$connect->query($sql_create_table)) {
        echo "Lỗi khi tạo bảng khsanxuat_default_settings: " . $connect->error . "\n";
        exit();
    }
    
    echo "Đã tạo bảng khsanxuat_default_settings thành công.\n";
}

// Lấy dữ liệu từ bảng cũ
$sql_get_old_data = "SELECT dept, id_tieuchi, ngay_tinh_han, so_ngay_xuly FROM default_settings";
$result_old_data = $connect->query($sql_get_old_data);

if ($result_old_data->num_rows === 0) {
    echo "Không có dữ liệu trong bảng default_settings để chuyển.\n";
    exit();
}

echo "Đã tìm thấy " . $result_old_data->num_rows . " bản ghi để chuyển.\n";

// Bắt đầu transaction
$connect->begin_transaction();

try {
    // Chuẩn bị câu lệnh insert
    $sql_insert = "INSERT INTO khsanxuat_default_settings (dept, id_tieuchi, ngay_tinh_han, so_ngay_xuly) 
                  VALUES (?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                      ngay_tinh_han = VALUES(ngay_tinh_han), 
                      so_ngay_xuly = VALUES(so_ngay_xuly)";
    $stmt_insert = $connect->prepare($sql_insert);
    
    // Chuyển từng bản ghi
    $count = 0;
    while ($row = $result_old_data->fetch_assoc()) {
        $stmt_insert->bind_param("sisi", 
            $row['dept'], 
            $row['id_tieuchi'], 
            $row['ngay_tinh_han'], 
            $row['so_ngay_xuly']
        );
        
        if ($stmt_insert->execute()) {
            $count++;
        } else {
            echo "Lỗi khi chuyển bản ghi (dept: {$row['dept']}, id_tieuchi: {$row['id_tieuchi']}): " . $stmt_insert->error . "\n";
        }
    }
    
    // Commit transaction
    $connect->commit();
    
    echo "Đã chuyển thành công $count bản ghi.\n";
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $connect->rollback();
    echo "Lỗi: " . $e->getMessage() . "\n";
}

echo "Hoàn tất quá trình chuyển dữ liệu.\n";
?> 