<?php
// Khởi tạo phiên làm việc
session_start();

// Đảm bảo hiển thị lỗi chi tiết
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kết nối database
include 'contdb.php';

// Kiểm tra kết nối
if (!$connect) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi kết nối database'
    ]);
    exit();
}

// Kiểm tra dữ liệu gửi lên
if (!isset($_POST['dept']) || !isset($_POST['so_ngay_xuly']) || !isset($_POST['ngay_tinh_han'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Thiếu dữ liệu cần thiết'
    ]);
    exit();
}

$dept = $_POST['dept'];
$so_ngay_xuly = intval($_POST['so_ngay_xuly']);
$ngay_tinh_han = $_POST['ngay_tinh_han'];

// Giới hạn số ngày xử lý từ 1-30
if ($so_ngay_xuly < 1) $so_ngay_xuly = 1;
if ($so_ngay_xuly > 30) $so_ngay_xuly = 30;

try {
    // Bắt đầu transaction để đảm bảo tính nhất quán của dữ liệu
    $connect->begin_transaction();
    
    // Lấy danh sách tiêu chí của bộ phận
    $sql_tieuchi = "SELECT id FROM tieuchi_dept WHERE dept = ?";
    $stmt_tieuchi = $connect->prepare($sql_tieuchi);
    $stmt_tieuchi->bind_param("s", $dept);
    $stmt_tieuchi->execute();
    $result_tieuchi = $stmt_tieuchi->get_result();
    
    $affected_rows = 0;
    
    while ($row_tieuchi = $result_tieuchi->fetch_assoc()) {
        $id_tieuchi = $row_tieuchi['id'];
        
        // Kiểm tra xem đã có cài đặt mặc định cho tiêu chí này chưa
        $sql_check = "SELECT id FROM default_settings WHERE dept = ? AND id_tieuchi = ? AND xuong = ''";
        $stmt_check = $connect->prepare($sql_check);
        $stmt_check->bind_param("si", $dept, $id_tieuchi);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Đã có cài đặt, cập nhật
            $row_check = $result_check->fetch_assoc();
            $sql_update = "UPDATE default_settings SET so_ngay_xuly = ?, ngay_tinh_han = ? WHERE id = ?";
            $stmt_update = $connect->prepare($sql_update);
            $stmt_update->bind_param("isi", $so_ngay_xuly, $ngay_tinh_han, $row_check['id']);
            $stmt_update->execute();
            $affected_rows += $stmt_update->affected_rows;
        } else {
            // Chưa có cài đặt, thêm mới
            $sql_insert = "INSERT INTO default_settings (dept, xuong, id_tieuchi, so_ngay_xuly, ngay_tinh_han) 
                           VALUES (?, '', ?, ?, ?)";
            $stmt_insert = $connect->prepare($sql_insert);
            $stmt_insert->bind_param("ssis", $dept, $id_tieuchi, $so_ngay_xuly, $ngay_tinh_han);
            $stmt_insert->execute();
            $affected_rows += $stmt_insert->affected_rows;
        }
    }
    
    // Commit transaction để lưu các thay đổi
    $connect->commit();
    
    // Kiểm tra kết quả cập nhật
    if ($affected_rows > 0) {
        // Ghi log
        error_log("Đã cập nhật $affected_rows cài đặt mặc định cho bộ phận $dept");
    } else {
        error_log("Không có cài đặt mặc định nào được cập nhật cho bộ phận $dept");
    }
    
    // Trả về kết quả thành công dưới dạng JSON
    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu cài đặt mặc định cho ' . $affected_rows . ' tiêu chí',
        'affected_rows' => $affected_rows,
        'so_ngay_xuly' => $so_ngay_xuly,
        'ngay_tinh_han' => $ngay_tinh_han
    ]);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if ($connect->ping()) {
        $connect->rollback();
    }
    
    // Ghi lại lỗi chi tiết
    $error_message = "Lỗi khi lưu cài đặt mặc định: " . $e->getMessage();
    error_log($error_message);
    
    // Trả về lỗi dưới dạng JSON
    echo json_encode([
        'success' => false,
        'message' => $error_message
    ]);
    exit();
}
?> 