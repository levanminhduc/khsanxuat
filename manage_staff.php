<?php
// Kết nối cơ sở dữ liệu
require "contdb.php";

// Xử lý POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy hành động từ request
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                // Thêm người thực hiện mới
                if (empty($_POST['ten']) || empty($_POST['phong_ban'])) {
                    throw new Exception('Thiếu thông tin bắt buộc (tên, phòng ban)');
                }
                
                $ten = $_POST['ten'];
                $phong_ban = $_POST['phong_ban'];
                $chuc_vu = $_POST['chuc_vu'] ?? '';
                
                // Kiểm tra xem người thực hiện đã tồn tại chưa
                $sql_check = "SELECT id FROM nhan_vien WHERE ten = ? AND phong_ban = ?";
                $stmt_check = $connect->prepare($sql_check);
                $stmt_check->bind_param("ss", $ten, $phong_ban);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows > 0) {
                    throw new Exception('Người thực hiện này đã tồn tại trong bộ phận');
                }
                
                // Thêm mới vào database
                $sql_insert = "INSERT INTO nhan_vien (ten, phong_ban, chuc_vu, active) VALUES (?, ?, ?, 1)";
                $stmt_insert = $connect->prepare($sql_insert);
                $stmt_insert->bind_param("sss", $ten, $phong_ban, $chuc_vu);
                
                if (!$stmt_insert->execute()) {
                    throw new Exception('Không thể thêm người thực hiện: ' . $stmt_insert->error);
                }
                
                // Trả về kết quả thành công
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã thêm người thực hiện thành công',
                    'id' => $connect->insert_id
                ]);
                break;
                
            case 'update':
                // Cập nhật thông tin người thực hiện
                if (empty($_POST['id']) || empty($_POST['ten'])) {
                    throw new Exception('Thiếu thông tin bắt buộc (id, tên)');
                }
                
                $id = intval($_POST['id']);
                $ten = $_POST['ten'];
                $chuc_vu = $_POST['chuc_vu'] ?? '';
                
                // Kiểm tra xem người thực hiện có tồn tại không
                $sql_check = "SELECT id, phong_ban FROM nhan_vien WHERE id = ?";
                $stmt_check = $connect->prepare($sql_check);
                $stmt_check->bind_param("i", $id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows === 0) {
                    throw new Exception('Không tìm thấy người thực hiện');
                }
                
                $row = $result_check->fetch_assoc();
                $phong_ban = $row['phong_ban'];
                
                // Kiểm tra xem tên mới có trùng với người khác không
                $sql_check_name = "SELECT id FROM nhan_vien WHERE ten = ? AND phong_ban = ? AND id != ?";
                $stmt_check_name = $connect->prepare($sql_check_name);
                $stmt_check_name->bind_param("ssi", $ten, $phong_ban, $id);
                $stmt_check_name->execute();
                $result_check_name = $stmt_check_name->get_result();
                
                if ($result_check_name->num_rows > 0) {
                    throw new Exception('Tên người thực hiện đã tồn tại trong bộ phận');
                }
                
                // Cập nhật thông tin
                $sql_update = "UPDATE nhan_vien SET ten = ?, chuc_vu = ? WHERE id = ?";
                $stmt_update = $connect->prepare($sql_update);
                $stmt_update->bind_param("ssi", $ten, $chuc_vu, $id);
                
                if (!$stmt_update->execute()) {
                    throw new Exception('Không thể cập nhật người thực hiện: ' . $stmt_update->error);
                }
                
                // Ghi log và kết quả
                error_log("Đã cập nhật thông tin người thực hiện ID: $id, Tên: $ten, Chức vụ: $chuc_vu");
                
                // Trả về kết quả thành công
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã cập nhật thông tin thành công',
                    'id' => $id,
                    'ten' => $ten,
                    'chuc_vu' => $chuc_vu
                ]);
                break;
                
            case 'delete':
                // Xóa người thực hiện
                if (empty($_POST['id'])) {
                    throw new Exception('Thiếu thông tin bắt buộc (id)');
                }
                
                $id = intval($_POST['id']);
                
                // Kiểm tra xem người thực hiện có tồn tại không
                $sql_check = "SELECT id FROM nhan_vien WHERE id = ?";
                $stmt_check = $connect->prepare($sql_check);
                $stmt_check->bind_param("i", $id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows === 0) {
                    throw new Exception('Không tìm thấy người thực hiện');
                }
                
                // Kiểm tra xem người thực hiện có đang được sử dụng không
                $sql_check_usage = "SELECT COUNT(*) as count FROM khsanxuat_default_settings WHERE nguoi_chiu_trachnhiem_default = ?";
                $stmt_check_usage = $connect->prepare($sql_check_usage);
                $stmt_check_usage->bind_param("i", $id);
                $stmt_check_usage->execute();
                $result_check_usage = $stmt_check_usage->get_result();
                $row_usage = $result_check_usage->fetch_assoc();
                
                if ($row_usage['count'] > 0) {
                    // Người thực hiện đang được sử dụng, không xóa mà chỉ đánh dấu không hoạt động
                    $sql_deactivate = "UPDATE nhan_vien SET active = 0 WHERE id = ?";
                    $stmt_deactivate = $connect->prepare($sql_deactivate);
                    $stmt_deactivate->bind_param("i", $id);
                    
                    if (!$stmt_deactivate->execute()) {
                        throw new Exception('Không thể vô hiệu hóa người thực hiện: ' . $stmt_deactivate->error);
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Đã vô hiệu hóa người thực hiện (không thể xóa hoàn toàn vì đang được sử dụng)'
                    ]);
                } else {
                    // Không được sử dụng, có thể xóa hoàn toàn
                    $sql_delete = "DELETE FROM nhan_vien WHERE id = ?";
                    $stmt_delete = $connect->prepare($sql_delete);
                    $stmt_delete->bind_param("i", $id);
                    
                    if (!$stmt_delete->execute()) {
                        throw new Exception('Không thể xóa người thực hiện: ' . $stmt_delete->error);
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Đã xóa người thực hiện thành công'
                    ]);
                }
                break;
                
            default:
                throw new Exception('Hành động không hợp lệ');
        }
    } catch (Exception $e) {
        // Trả về lỗi
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    // Chỉ chấp nhận POST request
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ chấp nhận POST request'
    ]);
}
?> 