<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kết nối database
include 'db_connect.php';
include 'check_tieuchi_image.php';

// Lấy tham số từ URL
$id_khsanxuat = isset($_GET['id_khsanxuat']) ? intval($_GET['id_khsanxuat']) : 0;
$id_tieuchi = isset($_GET['id_tieuchi']) ? intval($_GET['id_tieuchi']) : 0;

// Kiểm tra dữ liệu đầu vào
if ($id_khsanxuat <= 0 || $id_tieuchi <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Dữ liệu đầu vào không hợp lệ']);
    exit;
}

// Sử dụng hàm kiểm tra đã tồn tại
$has_image = checkTieuchiHasImage($connect, $id_khsanxuat, $id_tieuchi);

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'has_image' => $has_image,
    'id_khsanxuat' => $id_khsanxuat,
    'id_tieuchi' => $id_tieuchi
]); 