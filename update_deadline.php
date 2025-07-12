<?php
// Khởi tạo phiên làm việc
session_start();

// Đảm bảo hiển thị lỗi chi tiết
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kiểm tra quyền truy cập
/* if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    // Chuyển hướng với thông báo lỗi
    header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=not_authorized");
    exit();
} */

// Kết nối database
include 'contdb.php';

// Kiểm tra kết nối
if (!isset($connect) || $connect === null) {
    die("Lỗi kết nối database");
}

// Debug: Ghi lại thông tin POST
$debug_info = "POST data received in update_deadline.php: " . json_encode($_POST) . "\n";
file_put_contents('update_deadline_debug.log', $debug_info, FILE_APPEND);

// Kiểm tra dữ liệu gửi lên
if (!isset($_POST['id_sanxuat']) || !isset($_POST['so_ngay_xuly'])) {
    $error_msg = "Missing required data. POST: " . json_encode($_POST);
    file_put_contents('update_deadline_debug.log', $error_msg . "\n", FILE_APPEND);
    
    // Chuyển hướng về trang indexdept.php nếu có thông tin dept và id_sanxuat
    if (isset($_POST['dept']) && isset($_POST['id_sanxuat'])) {
        header("Location: indexdept.php?dept=" . urlencode($_POST['dept']) . "&id=" . urlencode($_POST['id_sanxuat']) . "&error=missing_data");
    } else {
        header("Location: index.php?error=missing_data");
    }
    exit();
}

$id_sanxuat = intval($_POST['id_sanxuat']);
$so_ngay_xuly = intval($_POST['so_ngay_xuly']);
$dept = isset($_POST['dept']) ? $_POST['dept'] : '';

// Lưu thông tin để sử dụng trong trường hợp lỗi
$return_url = "indexdept.php?dept=" . urlencode($dept) . "&id=" . urlencode($id_sanxuat);

// Giới hạn số ngày xử lý từ 1-30
if ($so_ngay_xuly < 1) $so_ngay_xuly = 1;
if ($so_ngay_xuly > 30) $so_ngay_xuly = 30;

try {
    // Bắt đầu transaction để đảm bảo tính nhất quán của dữ liệu
    $connect->begin_transaction();
    
    // Lấy ngày vào từ database
    $sql = "SELECT ngayin FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id_sanxuat);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Không tìm thấy bản ghi
        file_put_contents('update_deadline_debug.log', "Record not found: $id_sanxuat\n", FILE_APPEND);
        $connect->rollback(); // Rollback transaction
        header("Location: $return_url&error=record_not_found");
        exit();
    }
    
    $row = $result->fetch_assoc();
    $ngayin = new DateTime($row['ngayin']);
    $ngayin_str = $ngayin->format('Y-m-d');
    
    // QUAN TRỌNG: Tính hạn xử lý = Ngày vào - Số ngày
    $han_xuly = clone $ngayin;
    $han_xuly->modify("-{$so_ngay_xuly} days");
    $han_xuly_formatted = $han_xuly->format('Y-m-d');
    
    // Debug: Ghi lại thông tin tính toán chi tiết
    $debug_calc = "Tính toán hạn xử lý chung: Ngày vào ($ngayin_str) - $so_ngay_xuly ngày = Hạn xử lý ($han_xuly_formatted)\n";
    file_put_contents('update_deadline_debug.log', $debug_calc, FILE_APPEND);
    
    // Cập nhật số ngày xử lý chung vào bảng khsanxuat
    $sql_update = "UPDATE khsanxuat SET so_ngay_xuly = ?, han_xuly = ? WHERE stt = ?";
    $stmt_update = $connect->prepare($sql_update);
    $stmt_update->bind_param("isi", $so_ngay_xuly, $han_xuly_formatted, $id_sanxuat);
    $success = $stmt_update->execute();
    
    if (!$success) {
        throw new Exception("Lỗi khi cập nhật khsanxuat: " . $stmt_update->error);
    }
    
    $rows_affected = $stmt_update->affected_rows;
    file_put_contents('update_deadline_debug.log', "Update khsanxuat SQL: $sql_update (Affected: $rows_affected)\n", FILE_APPEND);
    
    // Cập nhật hạn xử lý cho tất cả tiêu chí chưa có hạn xử lý riêng
    if (!empty($dept)) {
        $sql_update_tieuchi = "UPDATE danhgia_tieuchi dg
                JOIN tieuchi_dept tc ON dg.id_tieuchi = tc.id
                SET dg.han_xuly = ?, dg.so_ngay_xuly = ?
                WHERE dg.id_sanxuat = ? 
                AND tc.dept = ?
                AND (dg.han_xuly IS NULL OR dg.han_xuly = '' OR dg.so_ngay_xuly IS NULL)";
                
        $stmt_update_tieuchi = $connect->prepare($sql_update_tieuchi);
        $stmt_update_tieuchi->bind_param("siss", $han_xuly_formatted, $so_ngay_xuly, $id_sanxuat, $dept);
        $success = $stmt_update_tieuchi->execute();
        
        if (!$success) {
            throw new Exception("Lỗi khi cập nhật danhgia_tieuchi: " . $stmt_update_tieuchi->error);
        }
        
        $rows_affected_tieuchi = $stmt_update_tieuchi->affected_rows;
        file_put_contents('update_deadline_debug.log', "Update danhgia_tieuchi SQL: $sql_update_tieuchi (Affected: $rows_affected_tieuchi)\n", FILE_APPEND);
    }
    
    // Commit transaction để lưu các thay đổi
    $connect->commit();
    
    // Chuyển về trang indexdept.php với dept và id tương ứng
    if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
        $final_url = $_POST['return_url'] . "&success=updated";
    } else {
        $final_url = "indexdept.php?dept=" . urlencode($dept) . "&id=" . urlencode($id_sanxuat) . "&success=updated";
    }
    file_put_contents('update_deadline_debug.log', "Chuyển hướng đến URL mới (update_deadline.php): $final_url\n", FILE_APPEND);
    
    // Luôn coi như cập nhật thành công
    header("Location: " . $final_url);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if ($connect->ping()) {
        $connect->rollback();
    }
    
    // Ghi lại lỗi chi tiết
    $error_message = "Lỗi: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString() . "\n";
    file_put_contents('update_deadline_debug.log', $error_message, FILE_APPEND);
    
    // Nếu có lỗi, vẫn chuyển về trang indexdept.php với thông báo lỗi
    if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
        $error_url = $_POST['return_url'] . "&error=" . urlencode($e->getMessage());
    } else {
        $error_url = "indexdept.php?dept=" . urlencode($dept) . "&id=" . urlencode($id_sanxuat) . "&error=" . urlencode($e->getMessage());
    }
    file_put_contents('update_deadline_debug.log', "Chuyển hướng đến URL lỗi (update_deadline.php): $error_url\n", FILE_APPEND);
    header("Location: " . $error_url);
    exit();
}
?> 