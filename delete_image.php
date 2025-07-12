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
$id_image = isset($_GET['id_image']) ? intval($_GET['id_image']) : 0;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';

if ($id_image <= 0 || $id <= 0 || empty($dept)) {
    die("Thiếu thông tin cần thiết");
}

try {
    // Lấy thông tin hình ảnh từ database
    $sql = "SELECT image_path FROM khsanxuat_images WHERE id = ? AND id_khsanxuat = ? AND dept = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("iis", $id_image, $id, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Không tìm thấy hình ảnh");
    }
    
    $row = $result->fetch_assoc();
    $image_path = $row['image_path'];
    
    // Xóa file hình ảnh
    if (file_exists($image_path)) {
        if (!unlink($image_path)) {
            die("Không thể xóa file hình ảnh");
        }
        
        // Xóa thư mục nếu rỗng
        $dir = dirname($image_path);
        if (is_dir($dir) && count(scandir($dir)) == 2) { // . và ..
            rmdir($dir);
        }
    }
    
    // Xóa record trong database
    $sql_delete = "DELETE FROM khsanxuat_images WHERE id = ?";
    $stmt_delete = $connect->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_image);
    
    if (!$stmt_delete->execute()) {
        die("Lỗi xóa dữ liệu: " . $stmt_delete->error);
    }
    
    // Chuyển hướng về trang image_handler.php với thông báo thành công
    header("Location: image_handler.php?id=" . $id . "&dept=" . urlencode($dept) . "&success=deleted");
    exit();
    
} catch (Exception $e) {
    die("Lỗi xử lý: " . $e->getMessage());
} 