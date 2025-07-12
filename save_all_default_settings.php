<?php
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

// Lấy thông tin từ request
$dept = isset($_POST['dept']) ? $_POST['dept'] : '';
$xuong = isset($_POST['xuong']) ? $_POST['xuong'] : '';
$settings = isset($_POST['settings']) ? json_decode($_POST['settings'], true) : [];

if (empty($dept) || empty($settings) || !is_array($settings)) {
    die(json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết'
    ]));
}

// Khởi tạo mảng để theo dõi kết quả
$results = [
    'success' => true,
    'message' => 'Đã lưu tất cả cài đặt mặc định',
    'total' => count($settings),
    'saved' => 0,
    'failed' => 0,
    'errors' => []
];

try {
    // Bắt đầu transaction
    $connect->begin_transaction();
    
    foreach ($settings as $setting) {
        $id_tieuchi = isset($setting['id_tieuchi']) ? intval($setting['id_tieuchi']) : 0;
        $ngay_tinh_han = isset($setting['ngay_tinh_han']) ? $setting['ngay_tinh_han'] : 'ngay_vao';
        $so_ngay_xuly = isset($setting['so_ngay_xuly']) ? intval($setting['so_ngay_xuly']) : 7;
        $nguoi_chiu_trachnhiem = isset($setting['nguoi_chiu_trachnhiem']) ? intval($setting['nguoi_chiu_trachnhiem']) : 0;
        
        if (empty($id_tieuchi)) {
            $results['failed']++;
            $results['errors'][] = "Thiếu ID tiêu chí";
            continue;
        }
        
        // Kiểm tra xem đã có cài đặt này chưa
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
        
        if ($stmt->execute()) {
            $results['saved']++;
        } else {
            $results['failed']++;
            $results['errors'][] = "Lỗi khi lưu cài đặt cho tiêu chí ID {$id_tieuchi}: " . $stmt->error;
        }
    }
    
    // Commit transaction
    $connect->commit();
    
    // Cập nhật thông báo thành công/thất bại
    if ($results['failed'] > 0) {
        $results['message'] = "Đã lưu {$results['saved']}/{$results['total']} cài đặt mặc định. Có {$results['failed']} lỗi.";
        $results['success'] = $results['saved'] > 0; // Nếu có ít nhất một cài đặt được lưu thì vẫn coi là thành công
    }
    
    echo json_encode($results);
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $connect->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn: ' . $e->getMessage()
    ]);
}
?> 