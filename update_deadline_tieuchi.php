<?php
// Khởi tạo phiên làm việc
session_start();

// Đảm bảo hiển thị lỗi chi tiết
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kiểm tra quyền truy cập (tạm thời bỏ qua cho mục đích test)
/* Đã comment để test code
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    // Trả về lỗi dưới dạng JSON
    echo json_encode([
        'success' => false, 
        'message' => 'Không có quyền thực hiện thao tác này'
    ]);
    exit();
} 
*/

// Kết nối database
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi kết nối database'
    ]);
    exit();
}

// Kiểm tra cấu trúc bảng và thêm cột nếu cần
try {
    // Kiểm tra xem cột so_ngay_xuly đã tồn tại chưa
    $check_column = $connect->query("SHOW COLUMNS FROM danhgia_tieuchi LIKE 'so_ngay_xuly'");
    
    if ($check_column->num_rows == 0) {
        // Nếu cột chưa tồn tại, thêm vào
        $connect->query("ALTER TABLE danhgia_tieuchi ADD COLUMN so_ngay_xuly INT NULL AFTER han_xuly");
        file_put_contents('update_deadline_debug.log', "Đã thêm cột so_ngay_xuly vào bảng danhgia_tieuchi\n", FILE_APPEND);
    }
    
    // Kiểm tra xem cột ngay_tinh_han đã tồn tại chưa
    $check_ngay_tinh_han = $connect->query("SHOW COLUMNS FROM danhgia_tieuchi LIKE 'ngay_tinh_han'");
    
    if ($check_ngay_tinh_han->num_rows == 0) {
        // Nếu cột chưa tồn tại, thêm vào
        $connect->query("ALTER TABLE danhgia_tieuchi ADD COLUMN ngay_tinh_han VARCHAR(20) DEFAULT 'ngay_vao' AFTER so_ngay_xuly");
        file_put_contents('update_deadline_debug.log', "Đã thêm cột ngay_tinh_han vào bảng danhgia_tieuchi\n", FILE_APPEND);
    }
} catch (Exception $e) {
    file_put_contents('update_deadline_debug.log', "Lỗi kiểm tra/thêm cột: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi kiểm tra/thêm cột: ' . $e->getMessage()
    ]);
    exit();
}

// Debug: Ghi lại thông tin POST
$debug_info = "POST data received: " . json_encode($_POST) . "\n";
file_put_contents('update_deadline_debug.log', $debug_info, FILE_APPEND);

// Kiểm tra dữ liệu gửi lên
if (!isset($_POST['id_sanxuat']) || !isset($_POST['id_tieuchi']) || !isset($_POST['so_ngay_xuly'])) {
    $error_msg = "Missing required data. POST: " . json_encode($_POST);
    file_put_contents('update_deadline_debug.log', $error_msg . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Thiếu dữ liệu cần thiết'
    ]);
    exit();
}

$id_sanxuat = intval($_POST['id_sanxuat']);
$id_tieuchi = intval($_POST['id_tieuchi']);
$so_ngay_xuly = intval($_POST['so_ngay_xuly']);
$dept = $_POST['dept'];
$ngay_tinh_han = isset($_POST['ngay_tinh_han']) ? $_POST['ngay_tinh_han'] : 'ngay_vao'; // Mặc định là ngày vào
$is_default = isset($_POST['is_default']) ? filter_var($_POST['is_default'], FILTER_VALIDATE_BOOLEAN) : false; // Xác định đây có phải là cập nhật vào cài đặt mặc định không

// Giới hạn số ngày xử lý từ 1-30
if ($so_ngay_xuly < 1) $so_ngay_xuly = 1;
if ($so_ngay_xuly > 30) $so_ngay_xuly = 30;

try {
    // Bắt đầu transaction để đảm bảo tính nhất quán của dữ liệu
    $connect->begin_transaction();
    
    // Nếu là cập nhật cài đặt mặc định
    if ($is_default) {
        // Cập nhật vào bảng default_settings
        $sql_check_default = "SELECT id FROM default_settings WHERE dept = ? AND id_tieuchi = ? AND xuong = ''";
        $stmt_check_default = $connect->prepare($sql_check_default);
        $stmt_check_default->bind_param("si", $dept, $id_tieuchi);
        $stmt_check_default->execute();
        $result_check_default = $stmt_check_default->get_result();
        
        if ($result_check_default->num_rows > 0) {
            // Cập nhật cài đặt mặc định hiện có
            $row_default = $result_check_default->fetch_assoc();
            $sql_update_default = "UPDATE default_settings SET 
                                   ngay_tinh_han = ?, 
                                   so_ngay_xuly = ? 
                                   WHERE id = ?";
            $stmt_update_default = $connect->prepare($sql_update_default);
            $stmt_update_default->bind_param("sii", $ngay_tinh_han, $so_ngay_xuly, $row_default['id']);
            $stmt_update_default->execute();
        } else {
            // Thêm mới cài đặt mặc định
            $sql_insert_default = "INSERT INTO default_settings (dept, xuong, id_tieuchi, ngay_tinh_han, so_ngay_xuly) 
                                   VALUES (?, '', ?, ?, ?)";
            $stmt_insert_default = $connect->prepare($sql_insert_default);
            $stmt_insert_default->bind_param("sisi", $dept, $id_tieuchi, $ngay_tinh_han, $so_ngay_xuly);
            $stmt_insert_default->execute();
        }
        
        // Commit transaction để lưu các thay đổi vào cài đặt mặc định
        $connect->commit();
        
        // Trả về kết quả thành công dưới dạng JSON
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật cài đặt mặc định thành công',
            'so_ngay_xuly' => $so_ngay_xuly,
            'ngay_tinh_han' => $ngay_tinh_han,
            'is_default' => true
        ]);
        exit();
    }
    
    // Xử lý cập nhật hạn xử lý thực tế cho trường hợp không phải cài đặt mặc định
    // Lấy ngày vào và ngày ra từ database
    $sql = "SELECT ngayin, ngayout FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id_sanxuat);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Không tìm thấy bản ghi
        file_put_contents('update_deadline_debug.log', "Record not found: $id_sanxuat\n", FILE_APPEND);
        $connect->rollback(); // Rollback transaction
        
        echo json_encode([
            'success' => false, 
            'message' => 'Không tìm thấy bản ghi'
        ]);
        exit();
    }
    
    $row = $result->fetch_assoc();
    $ngayin = new DateTime($row['ngayin']);
    $ngayout = new DateTime($row['ngayout']);
    $ngayin_str = $ngayin->format('Y-m-d');
    $ngayout_str = $ngayout->format('Y-m-d');
    
    // Tính hạn xử lý dựa vào loại ngày được chọn
    $han_xuly = null;
    if ($ngay_tinh_han === 'ngay_vao') {
        // Hạn xử lý = Ngày vào - Số ngày
        $han_xuly = clone $ngayin;
        $han_xuly->modify("-{$so_ngay_xuly} days");
        $debug_calc = "Tính toán hạn xử lý: Ngày vào ($ngayin_str) - $so_ngay_xuly ngày = Hạn xử lý (" . $han_xuly->format('Y-m-d') . ")\n";
    } else if ($ngay_tinh_han === 'ngay_vao_cong') {
        // Hạn xử lý = Ngày vào + Số ngày
        $han_xuly = clone $ngayin;
        $han_xuly->modify("+{$so_ngay_xuly} days");
        $debug_calc = "Tính toán hạn xử lý: Ngày vào ($ngayin_str) + $so_ngay_xuly ngày = Hạn xử lý (" . $han_xuly->format('Y-m-d') . ")\n";
    } else if ($ngay_tinh_han === 'ngay_ra') { 
        // Hạn xử lý = Ngày ra + Số ngày 
        $han_xuly = clone $ngayout;
        $han_xuly->modify("+{$so_ngay_xuly} days");
        $debug_calc = "Tính toán hạn xử lý: Ngày ra ($ngayout_str) + $so_ngay_xuly ngày = Hạn xử lý (" . $han_xuly->format('Y-m-d') . ")\n";
    } else if ($ngay_tinh_han === 'ngay_ra_tru') {
        // Hạn xử lý = Ngày ra - Số ngày
        $han_xuly = clone $ngayout;
        $han_xuly->modify("-{$so_ngay_xuly} days");
        $debug_calc = "Tính toán hạn xử lý: Ngày ra ($ngayout_str) - $so_ngay_xuly ngày = Hạn xử lý (" . $han_xuly->format('Y-m-d') . ")\n";
    }
    
    // Đảm bảo ngày là đúng định dạng
    $han_xuly_formatted = $han_xuly->format('Y-m-d');
    $han_xuly_display = $han_xuly->format('d/m/Y');
    
    // Debug: Ghi lại thông tin tính toán chi tiết
    file_put_contents('update_deadline_debug.log', $debug_calc, FILE_APPEND);
    
    // Kiểm tra nếu đã có bản ghi đánh giá tiêu chí
    $sql_check = "SELECT * FROM danhgia_tieuchi WHERE id_sanxuat = ? AND id_tieuchi = ?";
    $stmt_check = $connect->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_sanxuat, $id_tieuchi);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Cập nhật hạn xử lý cho tiêu chí này
        $sql_update = "UPDATE danhgia_tieuchi SET han_xuly = ?, so_ngay_xuly = ?, ngay_tinh_han = ? WHERE id_sanxuat = ? AND id_tieuchi = ?";
        $stmt_update = $connect->prepare($sql_update);
        $stmt_update->bind_param("sisii", $han_xuly_formatted, $so_ngay_xuly, $ngay_tinh_han, $id_sanxuat, $id_tieuchi);
        $success = $stmt_update->execute();
        
        if (!$success) {
            throw new Exception("Lỗi khi cập nhật: " . $stmt_update->error);
        }
        
        $rows_affected = $stmt_update->affected_rows;
        file_put_contents('update_deadline_debug.log', "Update SQL: $sql_update (Affected: $rows_affected)\nValues: han_xuly=$han_xuly_formatted, so_ngay_xuly=$so_ngay_xuly, ngay_tinh_han=$ngay_tinh_han\n", FILE_APPEND);
    } else {
        // Lấy thông tin người thực hiện mặc định từ bảng khsanxuat_default_settings
        $sql_nguoi_thuchien = "SELECT nguoi_chiu_trachnhiem_default FROM khsanxuat_default_settings 
                              WHERE id_tieuchi = ? AND dept = ?";
        $stmt_nguoi_thuchien = $connect->prepare($sql_nguoi_thuchien);
        $stmt_nguoi_thuchien->bind_param("is", $id_tieuchi, $dept);
        $stmt_nguoi_thuchien->execute();
        $result_nguoi_thuchien = $stmt_nguoi_thuchien->get_result();
        
        $nguoi_thuchien = null;
        if ($result_nguoi_thuchien->num_rows > 0) {
            $row_nguoi_thuchien = $result_nguoi_thuchien->fetch_assoc();
            $nguoi_thuchien = $row_nguoi_thuchien['nguoi_chiu_trachnhiem_default'];
        }
        
        // Tạo mới bản ghi đánh giá tiêu chí với hạn xử lý và người thực hiện
        if ($nguoi_thuchien) {
            $sql_insert = "INSERT INTO danhgia_tieuchi 
                          (id_sanxuat, id_tieuchi, han_xuly, so_ngay_xuly, ngay_tinh_han, nguoi_thuchien) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $connect->prepare($sql_insert);
            $stmt_insert->bind_param("iisisi", $id_sanxuat, $id_tieuchi, $han_xuly_formatted, $so_ngay_xuly, $ngay_tinh_han, $nguoi_thuchien);
        } else {
            // Nếu không có người thực hiện mặc định, chỉ thêm hạn xử lý
            $sql_insert = "INSERT INTO danhgia_tieuchi 
                          (id_sanxuat, id_tieuchi, han_xuly, so_ngay_xuly, ngay_tinh_han) 
                          VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $connect->prepare($sql_insert);
            $stmt_insert->bind_param("iisis", $id_sanxuat, $id_tieuchi, $han_xuly_formatted, $so_ngay_xuly, $ngay_tinh_han);
        }
        
        $success = $stmt_insert->execute();
        
        if (!$success) {
            throw new Exception("Lỗi khi thêm mới: " . $stmt_insert->error);
        }
        
        $rows_affected = $stmt_insert->affected_rows;
        file_put_contents('update_deadline_debug.log', "Insert SQL: $sql_insert (Affected: $rows_affected, Người thực hiện: " . ($nguoi_thuchien ?? 'NULL') . ")\n", FILE_APPEND);
    }
    
    // Commit transaction để lưu các thay đổi
    $connect->commit();
    
    // Kiểm tra kết quả cập nhật
    if ($rows_affected > 0) {
        file_put_contents('update_deadline_debug.log', "Thành công: Cập nhật $rows_affected dòng\n", FILE_APPEND);
    } else {
        file_put_contents('update_deadline_debug.log', "Lưu ý: Không có dòng nào bị ảnh hưởng, có thể dữ liệu không thay đổi\n", FILE_APPEND);
    }
    
    // Trả về kết quả thành công dưới dạng JSON
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật hạn xử lý thành công',
        'new_date' => $han_xuly_display,
        'so_ngay_xuly' => $so_ngay_xuly,
        'ngay_tinh_han' => $ngay_tinh_han,
        'han_xuly_formatted' => $han_xuly_formatted,
        'is_default' => false,
        'id_tieuchi' => $id_tieuchi,
        'id_sanxuat' => $id_sanxuat
    ]);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if ($connect->ping()) {
        $connect->rollback();
    }
    
    // Ghi lại lỗi chi tiết
    $error_message = "Lỗi: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString() . "\n";
    file_put_contents('update_deadline_debug.log', $error_message, FILE_APPEND);
    
    // Trả về lỗi dưới dạng JSON
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}
?> 