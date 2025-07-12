<?php
// Kết nối cơ sở dữ liệu
require "contdb.php";

// Kiểm tra tham số bộ phận
if (!isset($_GET['dept']) || empty($_GET['dept'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin bộ phận'
    ]);
    exit();
}

$dept = $_GET['dept'];

// Kiểm tra tính hợp lệ của bộ phận
$valid_departments = [
    'kehoach',
    'chuanbi_sanxuat_phong_kt',
    'kho',
    'cat',
    'ep_keo',
    'co_dien',
    'chuyen_may',
    'kcs',
    'ui_thanh_pham',
    'hoan_thanh'
];

if (!in_array($dept, $valid_departments)) {
    echo json_encode([
        'success' => false,
        'message' => 'Bộ phận không hợp lệ'
    ]);
    exit;
}

try {
    // Lấy danh sách người thực hiện thuộc bộ phận
    $sql = "SELECT id, ten, chuc_vu, active FROM nhan_vien WHERE phong_ban = ? AND active = 1 ORDER BY ten";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $staff_list = [];
    while ($row = $result->fetch_assoc()) {
        $staff_list[] = [
            'id' => $row['id'],
            'ten' => $row['ten'],
            'chuc_vu' => $row['chuc_vu'],
            'active' => $row['active']
        ];
    }
    
    // Trả về kết quả thành công
    echo json_encode([
        'success' => true,
        'message' => 'Đã lấy danh sách người chịu trách nhiệm thành công',
        'data' => $staff_list,
        'count' => count($staff_list)
    ]);
    
} catch (Exception $e) {
    // Ghi log lỗi
    error_log("Lỗi lấy danh sách người chịu trách nhiệm: " . $e->getMessage());
    
    // Trả về thông báo lỗi
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Đóng kết nối
$connect->close();
?> 