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

// Lấy thông tin từ request
$id_tieuchi = isset($_POST['id_tieuchi']) ? intval($_POST['id_tieuchi']) : 0;
$dept = isset($_POST['dept']) ? $_POST['dept'] : '';
$xuong = isset($_POST['xuong']) ? $_POST['xuong'] : '';

if (empty($id_tieuchi) || empty($dept)) {
    die(json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết'
    ]));
}

try {
    // Truy vấn với ưu tiên cài đặt cho xưởng cụ thể, sau đó là cài đặt chung
    $sql = "SELECT ds.* FROM default_settings ds
            WHERE ds.dept = ? AND ds.id_tieuchi = ? 
            AND (ds.xuong = ? OR ds.xuong = '')
            ORDER BY CASE WHEN ds.xuong = ? THEN 0 ELSE 1 END
            LIMIT 1";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("siss", $dept, $id_tieuchi, $xuong, $xuong);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $setting = $result->fetch_assoc();
        
        // Áp dụng cài đặt mặc định vào bảng ngay_den_han
        $update_sql = "UPDATE ngay_den_han SET 
                       type = ?,
                       so_ngay = ?,
                       nguoi_chiu_trachnhiem = ?
                       WHERE dept = ? AND id_tieuchi = ?";
                       
        $update_stmt = $connect->prepare($update_sql);
        $update_stmt->bind_param("siisi", 
                                $setting['ngay_tinh_han'], 
                                $setting['so_ngay_xuly'], 
                                $setting['nguoi_chiu_trachnhiem_default'],
                                $dept, 
                                $id_tieuchi);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã áp dụng cài đặt mặc định thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi áp dụng cài đặt: ' . $update_stmt->error
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy cài đặt mặc định phù hợp'
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