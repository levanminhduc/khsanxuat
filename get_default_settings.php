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
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$xuong = isset($_GET['xuong']) ? $_GET['xuong'] : '';

if (empty($dept)) {
    die(json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin bộ phận'
    ]));
}

try {
    // Nếu có xưởng thì lấy theo xưởng cụ thể, nếu không thì lấy cài đặt mặc định cho tất cả xưởng
    if (!empty($xuong)) {
        // Lấy dữ liệu ưu tiên theo thứ tự:
        // 1. Cài đặt cho xưởng cụ thể (nếu có)
        // 2. Cài đặt mặc định cho tất cả xưởng (nếu không có cài đặt cho xưởng cụ thể)
        $sql = "SELECT t1.* FROM (
                   SELECT * FROM default_settings WHERE dept = ? AND xuong = ?
                   UNION ALL
                   SELECT * FROM default_settings WHERE dept = ? AND xuong = '' AND id_tieuchi NOT IN 
                       (SELECT id_tieuchi FROM default_settings WHERE dept = ? AND xuong = ?)
               ) t1
               GROUP BY t1.id_tieuchi
               ORDER BY t1.id_tieuchi";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssss", $dept, $xuong, $dept, $dept, $xuong);
    } else {
        // Chỉ lấy cài đặt mặc định cho tất cả xưởng
        $sql = "SELECT * FROM default_settings WHERE dept = ? AND xuong = ''";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $dept);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $settings
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn: ' . $e->getMessage()
    ]);
} 