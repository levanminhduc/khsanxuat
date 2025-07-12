<?php
// Kết nối cơ sở dữ liệu
require "contdb.php";

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['ten']) || empty($_POST['ten']) || !isset($_POST['phong_ban']) || empty($_POST['phong_ban'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết (tên, phòng ban)'
    ]);
    exit();
}

$ten = $_POST['ten'];
$phong_ban = $_POST['phong_ban'];
$chuc_vu = isset($_POST['chuc_vu']) ? $_POST['chuc_vu'] : '';

try {
    // Kiểm tra xem tên người thực hiện đã tồn tại chưa
    $sql_check = "SELECT id FROM nhan_vien WHERE ten = ? AND phong_ban = ? AND active = 1";
    $stmt_check = $connect->prepare($sql_check);
    $stmt_check->bind_param("ss", $ten, $phong_ban);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Tên người chịu trách nhiệm đã tồn tại trong bộ phận'
        ]);
        exit();
    }
    
    // Thêm người thực hiện vào database
    $sql_insert = "INSERT INTO nhan_vien (ten, phong_ban, chuc_vu, active) VALUES (?, ?, ?, 1)";
    $stmt_insert = $connect->prepare($sql_insert);
    $stmt_insert->bind_param("sss", $ten, $phong_ban, $chuc_vu);
    
    if (!$stmt_insert->execute()) {
        throw new Exception("Lỗi khi thêm người chịu trách nhiệm: " . $stmt_insert->error);
    }
    
    $id = $connect->insert_id;
    
    // Trả về kết quả thành công
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm người chịu trách nhiệm thành công',
        'id' => $id,
        'ten' => $ten,
        'chuc_vu' => $chuc_vu
    ]);
    
} catch (Exception $e) {
    // Ghi log lỗi
    error_log("Lỗi thêm người chịu trách nhiệm: " . $e->getMessage());
    
    // Trả về thông báo lỗi
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Đóng kết nối
$connect->close();
?> 