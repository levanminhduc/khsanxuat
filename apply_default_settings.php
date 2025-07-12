<?php
/**
 * File này xử lý việc áp dụng cài đặt mặc định hạn xử lý cho các đơn hàng mới
 * Gọi hàm applyDefaultSettings($id_sanxuat) sau khi đơn hàng mới được tạo
 */

// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database - Sử dụng cùng file kết nối với import.php để đảm bảo tính nhất quán
if (!isset($connect)) {
    include_once 'contdb.php';
}

/**
 * Hàm áp dụng cài đặt mặc định cho một đơn hàng
 * @param int $id_sanxuat ID của đơn hàng cần áp dụng cài đặt
 * @return array Kết quả của quá trình áp dụng cài đặt
 */
function applyDefaultSettings($id_sanxuat) {
    global $connect;
    
    if (empty($id_sanxuat)) {
        return [
            'success' => false,
            'message' => 'Thiếu thông tin cần thiết',
            'count' => 0
        ];
    }
    
    // Biến để theo dõi trạng thái transaction
    $transaction_started = false;
    
    // Kiểm tra xem bảng ngay_den_han có tồn tại không
    $table_exists = false;
    $result_check_table = mysqli_query($connect, "SHOW TABLES LIKE 'ngay_den_han'");
    if ($result_check_table && mysqli_num_rows($result_check_table) > 0) {
        $table_exists = true;
}

try {
        // Lấy thông tin của đơn hàng
        $sql = "SELECT xuong FROM khsanxuat WHERE stt = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $id_sanxuat);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng với ID đã cho',
                'count' => 0
            ];
        }
        
        $row = $result->fetch_assoc();
        $xuong = $row['xuong'];
        
        // Bắt đầu transaction
        $connect->begin_transaction();
        $transaction_started = true;
        
        // Áp dụng cài đặt mặc định cho tất cả bộ phận
        $result_counts = [];
        $total_settings_applied = 0;
        
        // Danh sách bộ phận cần áp dụng cài đặt mặc định
        $departments = [
            'kehoach', 'chuanbi_sanxuat_phong_kt', 'kho', 'cat', 'ep_keo', 
            'co_dien', 'chuyen_may', 'kcs', 'ui_thanh_pham', 'hoan_thanh'
        ];
        
        foreach ($departments as $dept) {
            $settings_applied = 0;
            
            // Lấy danh sách tiêu chí của bộ phận
            $sql_tieuchi = "SELECT id FROM tieuchi_dept WHERE dept = ?";
            $stmt_tieuchi = $connect->prepare($sql_tieuchi);
            $stmt_tieuchi->bind_param("s", $dept);
            $stmt_tieuchi->execute();
            $result_tieuchi = $stmt_tieuchi->get_result();
            
            while ($tieuchi = $result_tieuchi->fetch_assoc()) {
                $id_tieuchi = $tieuchi['id'];
                
                // Thứ tự ưu tiên: 1. Cài đặt theo xưởng cụ thể, 2. Cài đặt mặc định cho tất cả xưởng
                $sql_setting = "SELECT * FROM default_settings 
                               WHERE dept = ? AND id_tieuchi = ? 
                               AND (xuong = ? OR xuong = '')
                               ORDER BY CASE WHEN xuong = ? THEN 0 ELSE 1 END
                               LIMIT 1";
                $stmt_setting = $connect->prepare($sql_setting);
                $stmt_setting->bind_param("siss", $dept, $id_tieuchi, $xuong, $xuong);
                $stmt_setting->execute();
                $result_setting = $stmt_setting->get_result();
                
                if ($result_setting->num_rows > 0) {
                    $setting = $result_setting->fetch_assoc();
                    
                    // Kiểm tra xem đã có đánh giá cho tiêu chí này chưa
                    $sql_check = "SELECT id FROM danhgia_tieuchi WHERE id_sanxuat = ? AND id_tieuchi = ?";
                    $stmt_check = $connect->prepare($sql_check);
                    $stmt_check->bind_param("ii", $id_sanxuat, $id_tieuchi);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    
                    if ($result_check->num_rows === 0) {
                        // Chưa có đánh giá, thêm mới với cài đặt mặc định
                        $sql_insert = "INSERT INTO danhgia_tieuchi (id_sanxuat, id_tieuchi, nguoi_thuchien, ngay_tinh_han, so_ngay_xuly) 
                                      VALUES (?, ?, ?, ?, ?)";
                        $stmt_insert = $connect->prepare($sql_insert);
                        $stmt_insert->bind_param("iiisi", $id_sanxuat, $id_tieuchi, $setting['nguoi_chiu_trachnhiem_default'], $setting['ngay_tinh_han'], $setting['so_ngay_xuly']);
                        
                        if ($stmt_insert->execute()) {
                            $settings_applied++;
                            $total_settings_applied++;
                            
                            // Chỉ cập nhật ngay_den_han nếu bảng này tồn tại
                            if ($table_exists) {
                                try {
                                    // Cập nhật bảng ngay_den_han để đảm bảo tính nhất quán với apply_default_setting.php
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
                                    
                                    // Thử cập nhật ngay_den_han (không bắt buộc phải thành công)
                                    $update_stmt->execute();
                                    
                                    // Kiểm tra nếu không có bản ghi nào được cập nhật, có thể cần thêm mới
                                    if ($update_stmt->affected_rows == 0) {
                                        // Thêm mới vào bảng ngay_den_han nếu không có bản ghi nào
                                        $insert_den_han_sql = "INSERT INTO ngay_den_han (dept, id_tieuchi, type, so_ngay, nguoi_chiu_trachnhiem)
                                                              VALUES (?, ?, ?, ?, ?)
                                                              ON DUPLICATE KEY UPDATE 
                                                              type = VALUES(type),
                                                              so_ngay = VALUES(so_ngay),
                                                              nguoi_chiu_trachnhiem = VALUES(nguoi_chiu_trachnhiem)";
                                                              
                                        $insert_den_han_stmt = $connect->prepare($insert_den_han_sql);
                                        $insert_den_han_stmt->bind_param("sisis", 
                                                                        $dept, 
                                                                        $id_tieuchi, 
                                                                        $setting['ngay_tinh_han'], 
                                                                        $setting['so_ngay_xuly'], 
                                                                        $setting['nguoi_chiu_trachnhiem_default']);
                                        
                                        $insert_den_han_stmt->execute();
                                    }
                                } catch (Exception $e) {
                                    // Bỏ qua lỗi khi thao tác với bảng ngay_den_han
                                    // Không ảnh hưởng đến quá trình import chính
                                }
                            }
                        }
                    }
                }
            }
            
            $result_counts[$dept] = $settings_applied;
        }
        
        // Commit transaction
        $connect->commit();
        $transaction_started = false;
        
        // Trả về kết quả thành công
        return [
            'success' => true,
            'message' => 'Đã áp dụng cài đặt mặc định',
            'count' => $total_settings_applied,
            'result_counts' => $result_counts
        ];
        
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi và transaction đã được bắt đầu
        if ($transaction_started) {
            try {
                $connect->rollback();
            } catch (Exception $rollback_error) {
                // Xử lý lỗi rollback nếu cần
            }
        }
        
        return [
            'success' => false,
            'message' => 'Lỗi truy vấn: ' . $e->getMessage(),
            'count' => 0
        ];
    }
}

// Xử lý các yêu cầu POST trực tiếp
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_sanxuat']) && !function_exists('applyDefaultSettings')) {
    $id_sanxuat = isset($_POST['id_sanxuat']) ? intval($_POST['id_sanxuat']) : 0;
    
    // Áp dụng cài đặt mặc định
    $result = applyDefaultSettings($id_sanxuat);
    
    // Trả về kết quả dưới dạng JSON
    header('Content-Type: application/json');
    echo json_encode($result);
    
    // Đóng kết nối database
    $connect->close();
}
?> 