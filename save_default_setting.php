<?php
// Khởi tạo phiên làm việc
session_start();

// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    die(json_encode([
        'success' => false,
        'message' => 'Lỗi kết nối database'
    ]));
}

// Tạm thời bỏ qua kiểm tra quyền
/*
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Bạn chưa đăng nhập. Vui lòng đăng nhập để sử dụng chức năng này.'
    ]);
    exit();
}
*/

// Lấy thông tin từ request
$id_tieuchi = isset($_POST['id_tieuchi']) ? intval($_POST['id_tieuchi']) : 0;
$dept = isset($_POST['dept']) ? $_POST['dept'] : '';
$xuong = isset($_POST['xuong']) ? $_POST['xuong'] : '';
$ngay_tinh_han = isset($_POST['ngay_tinh_han']) ? $_POST['ngay_tinh_han'] : 'ngay_vao';
$so_ngay_xuly = isset($_POST['so_ngay_xuly']) ? intval($_POST['so_ngay_xuly']) : 7;
$nguoi_chiu_trachnhiem = isset($_POST['nguoi_chiu_trachnhiem']) ? intval($_POST['nguoi_chiu_trachnhiem']) : 0;

if (empty($id_tieuchi) || empty($dept)) {
    die(json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết'
    ]));
}

try {
    // Kiểm tra xem có tồn tại bản ghi cũ với dept và id_tieuchi
    $sql_check_old = "SELECT id FROM default_settings WHERE dept = ? AND id_tieuchi = ? AND xuong = ''";
    $stmt_check_old = $connect->prepare($sql_check_old);
    $stmt_check_old->bind_param("si", $dept, $id_tieuchi);
    $stmt_check_old->execute();
    $result_check_old = $stmt_check_old->get_result();
    
    // Nếu tồn tại bản ghi cũ và đang lưu cho trường hợp "Tất cả xưởng" thì cập nhật bản ghi cũ
    if ($result_check_old->num_rows > 0 && empty($xuong)) {
        $row_old = $result_check_old->fetch_assoc();
        $sql = "UPDATE default_settings SET 
                ngay_tinh_han = ?, 
                so_ngay_xuly = ?, 
                nguoi_chiu_trachnhiem_default = ? 
                WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("siii", $ngay_tinh_han, $so_ngay_xuly, $nguoi_chiu_trachnhiem, $row_old['id']);
    } 
    // Kiểm tra xem đã có cài đặt cho xưởng cụ thể chưa
    else {
        $sql_check = "SELECT id FROM default_settings WHERE dept = ? AND xuong = ? AND id_tieuchi = ?";
        $stmt_check = $connect->prepare($sql_check);
        $stmt_check->bind_param("ssi", $dept, $xuong, $id_tieuchi);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Cập nhật cài đặt hiện có
            $sql = "UPDATE default_settings SET 
                    ngay_tinh_han = ?, 
                    so_ngay_xuly = ?, 
                    nguoi_chiu_trachnhiem_default = ? 
                    WHERE dept = ? AND xuong = ? AND id_tieuchi = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("siissi", $ngay_tinh_han, $so_ngay_xuly, $nguoi_chiu_trachnhiem, $dept, $xuong, $id_tieuchi);
        } else {
            // Thêm cài đặt mới
            $sql = "INSERT INTO default_settings (dept, xuong, id_tieuchi, ngay_tinh_han, so_ngay_xuly, nguoi_chiu_trachnhiem_default) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ssissi", $dept, $xuong, $id_tieuchi, $ngay_tinh_han, $so_ngay_xuly, $nguoi_chiu_trachnhiem);
        }
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã lưu cài đặt mặc định'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi lưu cài đặt: ' . $stmt->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn: ' . $e->getMessage()
    ]);
}

// Đóng kết nối database
$connect->close();
?> 