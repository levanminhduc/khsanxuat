<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database");
}

// Khởi tạo phiên làm việc nếu chưa có
session_start();

// Lấy thông tin từ URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_sanxuat = isset($_GET['id_sanxuat']) ? intval($_GET['id_sanxuat']) : 0;
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';

if ($id <= 0 || $id_sanxuat <= 0 || empty($dept)) {
    die("Thiếu thông tin cần thiết");
}

// Tạm thời bỏ kiểm tra user
$is_admin = true;

try {
    // Lấy đường dẫn file từ database
    $sql_get_file = "SELECT file_path FROM dept_template_files WHERE id = ? AND id_khsanxuat = ? AND dept = ?";
    $stmt_get_file = $connect->prepare($sql_get_file);
    $stmt_get_file->bind_param("iis", $id, $id_sanxuat, $dept);
    $stmt_get_file->execute();
    $result = $stmt_get_file->get_result();
    
    if ($result->num_rows === 0) {
        die("Không tìm thấy file cần xóa");
    }
    
    $file_path = $result->fetch_assoc()['file_path'];
    
    // Xóa file vật lý nếu tồn tại
    if (file_exists($file_path) && is_file($file_path)) {
        if (!unlink($file_path)) {
            // Nếu không xóa được file vật lý, vẫn tiếp tục xóa record trong database
            error_log("Không thể xóa file: " . $file_path);
        }
    }
    
    // Xóa thông tin file từ database
    $sql_delete = "DELETE FROM dept_template_files WHERE id = ?";
    $stmt_delete = $connect->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        // Chuyển hướng về trang file_templates.php với thông báo thành công
        header("Location: file_templates.php?id=$id_sanxuat&dept=$dept&success=deleted");
        exit;
    } else {
        die("Lỗi khi xóa file: " . $connect->error);
    }
    
} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage());
} 