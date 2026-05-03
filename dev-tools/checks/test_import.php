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

echo "<h1>Kiểm tra lỗi import dữ liệu</h1>";

// Test 1: Thêm dữ liệu thử nghiệm vào bảng khsanxuat
function test_insert_khsanxuat() {
    global $connect;
    
    echo "<h2>Test 1: Thêm dữ liệu vào khsanxuat</h2>";
    
    // Thử thêm một dòng dữ liệu vào bảng khsanxuat
    $stmt = $connect->prepare("INSERT INTO khsanxuat (line1, xuong, po, style, model, qty, ngayin, ngayout, ngay_tinh_han) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        echo "<p style='color: red;'>❌ Lỗi chuẩn bị câu lệnh: " . $connect->error . "</p>";
        return null;
    }
    
    $line = "Line Test";
    $xuong = "Xưởng Test";
    $po = "PO-TEST-" . date('YmdHis');
    $style = "ST-TEST";
    $model = "MODEL-TEST";
    $qty = 100;
    $ngayin = date('Y-m-d');
    $ngayout = date('Y-m-d', strtotime("+30 days"));
    $ngay_tinh_han = "ngay_vao_cong"; // Mặc định là ngày vào cộng số ngày
    
    $stmt->bind_param("sssssssss", $line, $xuong, $po, $style, $model, $qty, $ngayin, $ngayout, $ngay_tinh_han);
    
    if ($stmt->execute()) {
        $new_id = $connect->insert_id;
        echo "<p style='color: green;'>✅ Thành công: Đã thêm dữ liệu vào khsanxuat (ID: $new_id)</p>";
        
        // Cập nhật hạn xử lý cho bản ghi vừa thêm
        echo "<p>Cập nhật hạn xử lý cho bản ghi mới...</p>";
        
        try {
            // Cập nhật hạn xử lý dựa trên ngày vào
            $update_han_sql = "UPDATE khsanxuat SET han_xuly = DATE_ADD(ngayin, INTERVAL 7 DAY), so_ngay_xuly = 7 WHERE stt = ?";
            $update_han_stmt = $connect->prepare($update_han_sql);
            
            if (!$update_han_stmt) {
                echo "<p style='color: red;'>❌ Lỗi chuẩn bị câu lệnh cập nhật: " . $connect->error . "</p>";
            } else {
                $update_han_stmt->bind_param("i", $new_id);
                
                if ($update_han_stmt->execute()) {
                    $affected_rows = $update_han_stmt->affected_rows;
                    echo "<p style='color: green;'>✅ Đã cập nhật hạn xử lý cho $affected_rows bản ghi</p>";
                    
                    // Hiển thị thông tin bản ghi sau khi cập nhật
                    $select_sql = "SELECT stt, po, ngayin, ngayout, han_xuly, so_ngay_xuly, ngay_tinh_han FROM khsanxuat WHERE stt = ?";
                    $select_stmt = $connect->prepare($select_sql);
                    $select_stmt->bind_param("i", $new_id);
                    $select_stmt->execute();
                    $result = $select_stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    echo "<p>Thông tin bản ghi sau khi cập nhật:</p>";
                    echo "<ul>";
                    echo "<li>ID: {$row['stt']}</li>";
                    echo "<li>PO: {$row['po']}</li>";
                    echo "<li>Ngày vào: {$row['ngayin']}</li>";
                    echo "<li>Ngày ra: {$row['ngayout']}</li>";
                    echo "<li>Hạn xử lý: {$row['han_xuly']}</li>";
                    echo "<li>Số ngày xử lý: {$row['so_ngay_xuly']}</li>";
                    echo "<li>Ngày tính hạn: {$row['ngay_tinh_han']}</li>";
                    echo "</ul>";
                } else {
                    echo "<p style='color: red;'>❌ Lỗi cập nhật hạn xử lý: " . $update_han_stmt->error . "</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Lỗi ngoại lệ khi cập nhật hạn xử lý: " . $e->getMessage() . "</p>";
        }
        
        return $new_id;
    } else {
        echo "<p style='color: red;'>❌ Lỗi: " . $stmt->error . "</p>";
        return false;
    }
}

// Test 2: Lấy tên cột stt từ bảng khsanxuat
function test_get_column_name() {
    global $connect;
    
    echo "<h2>Test 2: Kiểm tra tên cột ID</h2>";
    
    $result = mysqli_query($connect, "SHOW COLUMNS FROM khsanxuat");
    
    echo "<p>Danh sách các cột trong bảng khsanxuat:</p>";
    echo "<ul>";
    $id_column = "";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>{$row['Field']} - {$row['Type']} - Key: {$row['Key']}</li>";
        if ($row['Key'] == 'PRI') {
            $id_column = $row['Field'];
        }
    }
    
    echo "</ul>";
    
    if (!empty($id_column)) {
        echo "<p style='color: green;'>✅ Tên cột ID (Primary Key): <strong>$id_column</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Không tìm thấy cột ID (Primary Key)</p>";
    }
    
    return $id_column;
}

// Test 3: Kiểm tra hàm applyDefaultSettings
function test_apply_default_settings($id_sanxuat) {
    echo "<h2>Test 3: Kiểm tra hàm applyDefaultSettings</h2>";
    
    if (empty($id_sanxuat)) {
        echo "<p style='color: orange;'>⚠️ Không có ID để kiểm tra</p>";
        return;
    }
    
    try {
        include_once 'apply_default_settings.php';
        
        if (!function_exists('applyDefaultSettings')) {
            echo "<p style='color: red;'>❌ Hàm applyDefaultSettings không tồn tại</p>";
            return;
        }
        
        echo "<p>Gọi hàm applyDefaultSettings($id_sanxuat)...</p>";
        $result = applyDefaultSettings($id_sanxuat);
        
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        if ($result['success']) {
            echo "<p style='color: green;'>✅ Thành công: Đã áp dụng {$result['count']} cài đặt mặc định</p>";
        } else {
            echo "<p style='color: red;'>❌ Lỗi: " . $result['message'] . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Lỗi ngoại lệ: " . $e->getMessage() . "</p>";
    }
}

// Test 4: Kiểm tra bảng default_settings
function test_default_settings() {
    global $connect;
    
    echo "<h2>Test 4: Kiểm tra dữ liệu trong bảng default_settings</h2>";
    
    $result = mysqli_query($connect, "SELECT COUNT(*) as total FROM default_settings");
    $row = mysqli_fetch_assoc($result);
    
    echo "<p>Tổng số bản ghi trong bảng default_settings: <strong>{$row['total']}</strong></p>";
    
    if ($row['total'] == 0) {
        echo "<p style='color: orange;'>⚠️ Bảng default_settings không có dữ liệu nào</p>";
    } else {
        echo "<p style='color: green;'>✅ Bảng default_settings có dữ liệu</p>";
        
        // Lấy 5 bản ghi đầu tiên để kiểm tra
        $result = mysqli_query($connect, "SELECT * FROM default_settings LIMIT 5");
        
        echo "<p>Mẫu dữ liệu (5 bản ghi đầu tiên):</p>";
        echo "<table border='1'>";
        echo "<tr>";
        
        $fields = mysqli_fetch_fields($result);
        foreach ($fields as $field) {
            echo "<th>{$field->name}</th>";
        }
        
        echo "</tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

// Test 5: Kiểm tra bảng danhgia_tieuchi
function test_danhgia_tieuchi() {
    global $connect;
    
    echo "<h2>Test 5: Kiểm tra cấu trúc bảng danhgia_tieuchi</h2>";
    
    $result = mysqli_query($connect, "DESCRIBE danhgia_tieuchi");
    
    if (!$result) {
        echo "<p style='color: red;'>❌ Lỗi: Không thể truy vấn cấu trúc bảng danhgia_tieuchi</p>";
        return;
    }
    
    echo "<p>Cấu trúc bảng danhgia_tieuchi:</p>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Hàm kiểm thử xử lý cập nhật date_display sau khi import 
function test_update_date_display() {
    global $connect;
    
    echo "<h3>Kiểm tra cập nhật date_display</h3>";
    
    // Lấy order_id từ đơn hàng vừa được import gần nhất
    $sql = "SELECT id_sanxuat FROM khsanxuat ORDER BY id_sanxuat DESC LIMIT 1";
    $result = mysqli_query($connect, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id_sanxuat = $row['id_sanxuat'];
        
        echo "<p>Đang kiểm tra cập nhật date_display cho đơn hàng gần nhất - ID: $id_sanxuat</p>";
        
        // Load file display_deadline.php nếu chưa được load
        if (!function_exists('updateImportDateDisplay')) {
            include_once 'display_deadline.php';
        }
        
        // Log bắt đầu quá trình cập nhật date_display
        $log_file = 'logs/date_display_update.log';
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Bắt đầu kiểm tra cập nhật date_display từ test_import.\n", FILE_APPEND);
        
        // Gọi hàm updateImportDateDisplay từ display_deadline.php
        $result = updateImportDateDisplay($id_sanxuat, $connect);
        
        if ($result['success']) {
            echo "<div class='alert alert-success'>";
            echo "<strong>✅ Cập nhật thành công:</strong> Đã cập nhật " . $result['updated'] . " tiêu chí.";
            echo "</div>";
            
            // Hiển thị thông tin về các tiêu chí đã cập nhật
            if (!empty($result['criteria'])) {
                echo "<div class='table-responsive'>";
                echo "<table class='table table-striped table-sm'>";
                echo "<thead class='thead-dark'>";
                echo "<tr>";
                echo "<th>ID Tiêu chí</th><th>Ngày vào</th><th>Ngày ra</th><th>date_display trước</th><th>date_display sau</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                
                foreach ($result['criteria'] as $criterion) {
                    echo "<tr>";
                    echo "<td>" . $criterion['id'] . "</td>";
                    echo "<td>" . $criterion['ngay_vao'] . "</td>";
                    echo "<td>" . $criterion['ngay_ra'] . "</td>";
                    echo "<td>" . $criterion['old_date_display'] . "</td>";
                    echo "<td><strong>" . $criterion['new_date_display'] . "</strong></td>";
                    echo "</tr>";
                }
                
                echo "</tbody>";
                echo "</table>";
                echo "</div>";
            }
            
            // Ghi log thành công
            $success_log = "[" . date('Y-m-d H:i:s') . "] Test: Đã cập nhật date_display cho đơn hàng ID: $id_sanxuat. ";
            $success_log .= "Số tiêu chí cập nhật: " . $result['updated'] . "\n";
            file_put_contents($log_file, $success_log, FILE_APPEND);
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<strong>❌ Lỗi:</strong> " . $result['message'];
            echo "</div>";
            
            // Ghi log lỗi
            $error_log = "[" . date('Y-m-d H:i:s') . "] Test: Lỗi cập nhật date_display cho đơn hàng ID: $id_sanxuat. ";
            $error_log .= "Lỗi: " . $result['message'] . "\n";
            file_put_contents($log_file, $error_log, FILE_APPEND);
        }
    } else {
        echo "<div class='alert alert-warning'>";
        echo "Không tìm thấy đơn hàng nào trong cơ sở dữ liệu để kiểm tra.";
        echo "</div>";
        
        // Ghi log lỗi
        $error_log = "[" . date('Y-m-d H:i:s') . "] Test: Không tìm thấy đơn hàng nào trong cơ sở dữ liệu để kiểm tra date_display.\n";
        file_put_contents($log_file, $error_log, FILE_APPEND);
    }
}

// Thực hiện các bài kiểm tra
echo "<div style='margin: 20px;'>";

$id_column = test_get_column_name();
$id_sanxuat = test_insert_khsanxuat();
test_default_settings();
test_danhgia_tieuchi();
test_apply_default_settings($id_sanxuat);

// Kiểm tra cập nhật date_display
test_update_date_display();

echo "</div>";

// Đóng kết nối database
mysqli_close($connect);
?> 