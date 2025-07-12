<?php
// Thiết lập báo cáo lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kết nối database
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database: " . mysqli_connect_error());
}

// Bắt đầu transaction
$connect->begin_transaction();

try {
    // Tạo bảng tạm để lưu dữ liệu
    $sql_create_temp = "CREATE TEMPORARY TABLE temp_danhgia_tieuchi AS SELECT * FROM danhgia_tieuchi";
    if (!$connect->query($sql_create_temp)) {
        throw new Exception("Lỗi khi tạo bảng tạm: " . $connect->error);
    }
    echo "Đã tạo bảng tạm.<br>";

    // Lấy danh sách nhân viên
    $sql_staff = "SELECT id, ten FROM nhan_vien WHERE active = 1";
    $result_staff = $connect->query($sql_staff);
    $staff_map = [];
    
    if ($result_staff) {
        while ($row = $result_staff->fetch_assoc()) {
            $staff_map[$row['ten']] = $row['id'];
        }
        echo "Đã lấy danh sách " . count($staff_map) . " nhân viên.<br>";
    } else {
        throw new Exception("Lỗi khi lấy danh sách nhân viên: " . $connect->error);
    }

    // Thay đổi cấu trúc bảng danhgia_tieuchi
    $sql_alter = "ALTER TABLE danhgia_tieuchi MODIFY COLUMN nguoi_thuchien INT(11) NULL";
    if (!$connect->query($sql_alter)) {
        throw new Exception("Lỗi khi thay đổi cấu trúc bảng: " . $connect->error);
    }
    echo "Đã thay đổi cấu trúc bảng danhgia_tieuchi.<br>";

    // Lấy dữ liệu từ bảng tạm
    $sql_get_data = "SELECT id, nguoi_thuchien FROM temp_danhgia_tieuchi";
    $result_data = $connect->query($sql_get_data);
    
    if (!$result_data) {
        throw new Exception("Lỗi khi lấy dữ liệu từ bảng tạm: " . $connect->error);
    }

    // Cập nhật dữ liệu
    $updated_count = 0;
    $not_found_count = 0;
    
    while ($row = $result_data->fetch_assoc()) {
        $id = $row['id'];
        $ten_nguoi_thuchien = $row['nguoi_thuchien'];
        
        if (empty($ten_nguoi_thuchien)) {
            // Bỏ qua nếu không có người thực hiện
            continue;
        }
        
        // Tìm ID của người thực hiện
        $id_nguoi_thuchien = $staff_map[$ten_nguoi_thuchien] ?? null;
        
        if ($id_nguoi_thuchien) {
            // Cập nhật ID người thực hiện
            $sql_update = "UPDATE danhgia_tieuchi SET nguoi_thuchien = ? WHERE id = ?";
            $stmt_update = $connect->prepare($sql_update);
            $stmt_update->bind_param("ii", $id_nguoi_thuchien, $id);
            
            if ($stmt_update->execute()) {
                $updated_count++;
            } else {
                throw new Exception("Lỗi khi cập nhật ID người thực hiện: " . $stmt_update->error);
            }
        } else {
            // Không tìm thấy ID người thực hiện
            $not_found_count++;
            echo "Không tìm thấy ID cho người thực hiện: " . htmlspecialchars($ten_nguoi_thuchien) . "<br>";
        }
    }
    
    echo "Đã cập nhật $updated_count bản ghi.<br>";
    echo "Không tìm thấy ID cho $not_found_count người thực hiện.<br>";

    // Xóa bảng tạm
    $sql_drop_temp = "DROP TEMPORARY TABLE IF EXISTS temp_danhgia_tieuchi";
    if (!$connect->query($sql_drop_temp)) {
        throw new Exception("Lỗi khi xóa bảng tạm: " . $connect->error);
    }
    echo "Đã xóa bảng tạm.<br>";

    // Commit transaction
    $connect->commit();
    echo "Đã hoàn thành việc cập nhật cấu trúc bảng danhgia_tieuchi.<br>";

} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $connect->rollback();
    echo "Lỗi: " . $e->getMessage();
}

// Đóng kết nối
$connect->close();
?> 