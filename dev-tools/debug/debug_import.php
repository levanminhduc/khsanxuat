<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'contdb.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối: " . mysqli_connect_error());
}

// Định nghĩa lại hàm applyDefaultSettings
function applyDefaultSettingsDebug($id_sanxuat) {
    global $connect;
    
    $debug_log = [];
    $debug_log[] = "Gọi hàm applyDefaultSettingsDebug cho ID: $id_sanxuat";
    
    if (empty($id_sanxuat)) {
        $debug_log[] = "Lỗi: Thiếu thông tin ID đơn hàng";
        return [
            'success' => false,
            'message' => 'Thiếu thông tin cần thiết',
            'count' => 0,
            'debug_log' => $debug_log
        ];
    }
    
    try {
        // Kiểm tra xem stt có tồn tại không
        $sql = "SELECT stt, xuong FROM khsanxuat WHERE stt = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $id_sanxuat);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $debug_log[] = "Kiểm tra đơn hàng với ID: $id_sanxuat";
        
        if ($result->num_rows === 0) {
            $debug_log[] = "Không tìm thấy đơn hàng với ID: $id_sanxuat";
            return [
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng với ID đã cho',
                'count' => 0,
                'debug_log' => $debug_log
            ];
        }
        
        $row = $result->fetch_assoc();
        $xuong = $row['xuong'];
        $debug_log[] = "Đơn hàng xuong: $xuong";
        
        // Kiểm tra bảng default_settings
        $sql_check_settings = "SELECT COUNT(*) as count FROM default_settings";
        $result_check = mysqli_query($connect, $sql_check_settings);
        $settings_count = mysqli_fetch_assoc($result_check)['count'];
        $debug_log[] = "Số lượng bản ghi trong default_settings: $settings_count";
        
        if ($settings_count == 0) {
            $debug_log[] = "Lỗi: Không có cài đặt mặc định nào trong hệ thống";
            return [
                'success' => false,
                'message' => 'Không có cài đặt mặc định nào',
                'count' => 0,
                'debug_log' => $debug_log
            ];
        }
        
        // Kiểm tra bảng tieuchi_dept
        $sql_check_tieuchi = "SELECT COUNT(*) as count FROM tieuchi_dept";
        $result_check = mysqli_query($connect, $sql_check_tieuchi);
        $tieuchi_count = mysqli_fetch_assoc($result_check)['count'];
        $debug_log[] = "Số lượng tiêu chí: $tieuchi_count";
        
        if ($tieuchi_count == 0) {
            $debug_log[] = "Lỗi: Không có tiêu chí nào trong hệ thống";
            return [
                'success' => false,
                'message' => 'Không có tiêu chí nào',
                'count' => 0,
                'debug_log' => $debug_log
            ];
        }
        
        // Thực hiện một cài đặt đơn giản để kiểm tra
        $debug_log[] = "Thử thêm một bản ghi vào danhgia_tieuchi";
        
        // Kiểm tra xem bảng danhgia_tieuchi có tồn tại không
        $sql_check_table = "SHOW TABLES LIKE 'danhgia_tieuchi'";
        $result_check = mysqli_query($connect, $sql_check_table);
        
        if (mysqli_num_rows($result_check) === 0) {
            $debug_log[] = "Lỗi: Bảng danhgia_tieuchi không tồn tại";
            return [
                'success' => false,
                'message' => 'Bảng danhgia_tieuchi không tồn tại',
                'count' => 0,
                'debug_log' => $debug_log
            ];
        }
        
        // Lấy một tiêu chí làm mẫu
        $sql_sample = "SELECT id FROM tieuchi_dept LIMIT 1";
        $result_sample = mysqli_query($connect, $sql_sample);
        
        if (mysqli_num_rows($result_sample) === 0) {
            $debug_log[] = "Lỗi: Không thể lấy mẫu tiêu chí";
            return [
                'success' => false,
                'message' => 'Không thể lấy mẫu tiêu chí',
                'count' => 0,
                'debug_log' => $debug_log
            ];
        }
        
        $sample_tieuchi = mysqli_fetch_assoc($result_sample)['id'];
        $debug_log[] = "Mẫu tiêu chí ID: $sample_tieuchi";
        
        // Thử thêm một bản ghi vào bảng danhgia_tieuchi
        $ngay_tinh_han = 'ngay_vao';
        $so_ngay_xuly = 7;
        $nguoi_thuchien = 1; // ID mặc định
        
        $sql_insert_test = "INSERT INTO danhgia_tieuchi (id_sanxuat, id_tieuchi, nguoi_thuchien, ngay_tinh_han, so_ngay_xuly) 
                           VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $connect->prepare($sql_insert_test);
        $stmt_insert->bind_param("iiisi", $id_sanxuat, $sample_tieuchi, $nguoi_thuchien, $ngay_tinh_han, $so_ngay_xuly);
        
        if ($stmt_insert->execute()) {
            $debug_log[] = "Thêm bản ghi thành công: ID mới = " . $connect->insert_id;
            return [
                'success' => true,
                'message' => 'Đã thử nghiệm thành công',
                'count' => 1,
                'debug_log' => $debug_log
            ];
        } else {
            $error = $stmt_insert->error;
            $debug_log[] = "Lỗi khi thêm bản ghi: $error";
            return [
                'success' => false,
                'message' => "Lỗi khi thêm bản ghi: $error",
                'count' => 0,
                'debug_log' => $debug_log
            ];
        }
        
    } catch (Exception $e) {
        $debug_log[] = "Lỗi ngoại lệ: " . $e->getMessage();
        return [
            'success' => false,
            'message' => 'Lỗi truy vấn: ' . $e->getMessage(),
            'count' => 0,
            'debug_log' => $debug_log
        ];
    }
}

// Kiểm tra nếu có hành động
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'test_import') {
    // Thực hiện kiểm tra import
    echo "<h1>Kiểm tra lỗi import</h1>";
    
    // Thêm một đơn hàng thử nghiệm
    $line = "L1-TEST";
    $xuong = "X1-TEST";
    $po = "PO-TEST-" . time();
    $style = "STYLE-TEST-" . time();
    $model = "MODEL-TEST";
    $qty = "100";
    $ngayin = date('Y-m-d');
    $ngayout = date('Y-m-d', strtotime('+7 days'));
    
    try {
        $connect->begin_transaction();
        
        $stmt = $connect->prepare("INSERT INTO khsanxuat (line1, xuong, po, style, model, qty, ngayin, ngayout, ngay_tinh_han) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $imported_ids = []; // Mảng lưu các ID vừa được thêm vào
        $ngay_tinh_han_default = 'ngay_vao_cong'; // Mặc định sử dụng "ngay_vao_cong"
        
        $stmt->bind_param("sssssssss", 
            $line,
            $xuong,
            $po,
            $style,
            $model,
            $qty,
            $ngayin,
            $ngayout,
            $ngay_tinh_han_default
        );
        
        if ($stmt->execute()) {
            $new_id = $connect->insert_id;
            $imported_ids[] = $new_id; // Thêm ID vào mảng
            echo "<p style='color: green;'>✅ Thành công: Đã thêm đơn hàng mới (ID: $new_id)</p>";
            
            // Cập nhật hạn xử lý cho bản ghi khsanxuat
            $update_han_sql = "UPDATE khsanxuat SET han_xuly = DATE_ADD(ngayin, INTERVAL 7 DAY) WHERE stt = ? AND (han_xuly IS NULL OR han_xuly = '')";
            $update_han_stmt = $connect->prepare($update_han_sql);
            $update_han_stmt->bind_param("i", $new_id);
            $update_han_stmt->execute();
            $update_han_result = $update_han_stmt->affected_rows;
            echo "<p style='color: green;'>✅ Cập nhật hạn xử lý: " . ($update_han_result > 0 ? "Thành công" : "Không có thay đổi") . "</p>";
            
            // Thử gọi hàm applyDefaultSettingsDebug
            $result = applyDefaultSettingsDebug($new_id);
            
            echo "<h2>Kết quả:</h2>";
            echo "<pre>" . print_r($result, true) . "</pre>";
            
            if ($result['success']) {
                echo "<p style='color: green;'>✅ Áp dụng cài đặt mặc định thành công</p>";
                $connect->commit();
                echo "<p style='color: green;'>✅ Transaction đã được commit</p>";
            } else {
                echo "<p style='color: red;'>❌ Lỗi khi áp dụng cài đặt mặc định: " . $result['message'] . "</p>";
                
                // Kiểm tra nếu file apply_default_settings.php tồn tại
                if (file_exists('apply_default_settings.php')) {
                    echo "<p style='color: blue;'>ℹ️ File apply_default_settings.php tồn tại, thử include file này</p>";
                    include_once 'apply_default_settings.php';
                    
                    if (function_exists('applyDefaultSettings')) {
                        echo "<p style='color: blue;'>ℹ️ Hàm applyDefaultSettings() tồn tại, thử gọi hàm này</p>";
                        $apply_result = applyDefaultSettings($new_id);
                        echo "<pre>" . print_r($apply_result, true) . "</pre>";
                        
                        if ($apply_result['success']) {
                            echo "<p style='color: green;'>✅ Gọi trực tiếp hàm applyDefaultSettings() thành công</p>";
                            $connect->commit();
                            echo "<p style='color: green;'>✅ Transaction đã được commit</p>";
                        } else {
                            echo "<p style='color: red;'>❌ Lỗi khi gọi trực tiếp hàm applyDefaultSettings(): " . $apply_result['message'] . "</p>";
                            $connect->rollback();
                            echo "<p style='color: orange;'>⚠️ Transaction đã được rollback</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>❌ Hàm applyDefaultSettings() không tồn tại</p>";
                        $connect->rollback();
                        echo "<p style='color: orange;'>⚠️ Transaction đã được rollback</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ File apply_default_settings.php không tồn tại</p>";
                    $connect->rollback();
                    echo "<p style='color: orange;'>⚠️ Transaction đã được rollback</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Lỗi khi thêm đơn hàng: " . $stmt->error . "</p>";
            $connect->rollback();
            echo "<p style='color: orange;'>⚠️ Transaction đã được rollback</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Lỗi ngoại lệ: " . $e->getMessage() . "</p>";
        
        if ($connect->inTransaction()) {
            $connect->rollback();
            echo "<p style='color: orange;'>⚠️ Transaction đã được rollback</p>";
        }
    }

    // Đoạn code để cập nhật date_display sau khi import dữ liệu
    if (isset($imported_ids) && !empty($imported_ids)) {
        // Load file display_deadline.php nếu chưa được load
        if (!function_exists('updateImportDateDisplay')) {
            include_once 'display_deadline.php';
        }
        
        // Log bắt đầu quá trình cập nhật date_display
        file_put_contents('debug_import.log', "[" . date('Y-m-d H:i:s') . "] Bắt đầu cập nhật date_display sau khi debug import.\n", FILE_APPEND);
        
        // Hiển thị thông báo debug
        echo "<h3>Cập nhật date_display sau khi import</h3>";
        
        // Cập nhật date_display cho từng đơn hàng đã import
        $updated_count = 0;
        $total_updated_tieuchi = 0;
        $total_orders = count($imported_ids);
        
        foreach ($imported_ids as $id_sanxuat) {
            echo "<p>Đang cập nhật date_display cho đơn hàng ID: $id_sanxuat</p>";
            
            // Gọi hàm updateImportDateDisplay từ display_deadline.php
            $result = updateImportDateDisplay($id_sanxuat, $connect);
            
            if ($result['success']) {
                $updated_count++;
                $total_updated_tieuchi += $result['updated'];
                
                echo "<p style='color: green;'>✅ Cập nhật thành công " . $result['updated'] . " tiêu chí.</p>";
                
                // Ghi log thành công
                $success_log = "[" . date('Y-m-d H:i:s') . "] Đã cập nhật date_display cho đơn hàng ID: $id_sanxuat. ";
                $success_log .= "Số tiêu chí cập nhật: " . $result['updated'] . "\n";
                file_put_contents('debug_import.log', $success_log, FILE_APPEND);
            } else {
                echo "<p style='color: red;'>❌ Lỗi: " . $result['message'] . "</p>";
                
                // Ghi log lỗi
                $error_log = "[" . date('Y-m-d H:i:s') . "] Lỗi cập nhật date_display cho đơn hàng ID: $id_sanxuat. ";
                $error_log .= "Lỗi: " . $result['message'] . "\n";
                file_put_contents('debug_import.log', $error_log, FILE_APPEND);
            }
        }
        
        // Kết thúc ghi log
        $end_log = "[" . date('Y-m-d H:i:s') . "] Hoàn tất cập nhật date_display trong debug. ";
        $end_log .= "Đã cập nhật $updated_count/$total_orders đơn hàng, tổng cộng $total_updated_tieuchi tiêu chí.\n";
        file_put_contents('debug_import.log', $end_log, FILE_APPEND);
        
        // Hiển thị tổng kết
        echo "<div style='margin: 10px 0; padding: 10px; background-color: #f0f9ff; border: 1px solid #c0d8e8; border-radius: 4px;'>";
        echo "<strong>Tổng kết cập nhật date_display:</strong><br>";
        echo "Đã cập nhật $updated_count/$total_orders đơn hàng<br>";
        echo "Tổng cộng $total_updated_tieuchi tiêu chí được cập nhật";
        echo "</div>";
    }
} else {
    // Hiển thị form 
    echo "<h1>Debug Import Dữ Liệu</h1>";
    echo "<p>Công cụ này giúp bạn kiểm tra và khắc phục lỗi trong quá trình import dữ liệu.</p>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='?action=test_import' style='padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Kiểm tra import</a>";
    echo "</div>";
    
    echo "<h2>Thông tin hệ thống:</h2>";
    echo "<ul>";
    echo "<li>PHP version: " . phpversion() . "</li>";
    echo "<li>MySQL server version: " . mysqli_get_server_info($connect) . "</li>";
    echo "<li>MySQL client version: " . mysqli_get_client_info() . "</li>";
    echo "</ul>";
    
    // Kiểm tra các bảng cần thiết
    echo "<h2>Kiểm tra các bảng cần thiết:</h2>";
    $tables = ['khsanxuat', 'default_settings', 'tieuchi_dept', 'danhgia_tieuchi'];
    echo "<ul>";
    
    foreach ($tables as $table) {
        $result = mysqli_query($connect, "SHOW TABLES LIKE '$table'");
        $exists = mysqli_num_rows($result) > 0;
        
        if ($exists) {
            echo "<li style='color: green;'>✅ Bảng $table: Tồn tại</li>";
            
            // Đếm số bản ghi
            $count_result = mysqli_query($connect, "SELECT COUNT(*) as count FROM $table");
            $count = mysqli_fetch_assoc($count_result)['count'];
            echo "<li style='margin-left: 20px;'>- Số bản ghi: $count</li>";
        } else {
            echo "<li style='color: red;'>❌ Bảng $table: Không tồn tại</li>";
        }
    }
    
    echo "</ul>";
}

// Đóng kết nối
mysqli_close($connect);
?> 