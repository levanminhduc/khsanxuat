<?php
// Bật chế độ hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kết nối đến database
require_once 'db_connect.php';

// Header trang
include 'header.php';

// Định nghĩa danh sách bộ phận
$dept_names = [
    'kehoach' => 'Phòng Kế Hoạch',
    'chuanbi_sanxuat_phong_kt' => 'Chuẩn Bị Sản Xuất - Phòng KT',
    'kho' => 'Kho',
    'cat' => 'Công Đoạn Cắt',
    'ep_keo' => 'Công Đoạn Ép Keo',
    'co_dien' => 'Cơ Điện',
    'chuyen_may' => 'Chuyền May',
    'kcs' => 'KCS',
    'ui_thanh_pham' => 'Ủi Thành Phẩm',
    'hoan_thanh' => 'Hoàn Thành'
];

// Kiểm tra file log
$log_file = 'C:/xampp/php/logs/php_log.txt';
$log_path_created = false;

// Nếu file log không tồn tại, tạo các thư mục cần thiết
if (!file_exists($log_file)) {
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
        $log_path_created = true;
    }
    // Tạo file log trống
    file_put_contents($log_file, "");
}

// Hàm đọc file log và lọc các dòng có chứa "dept"
function getLogEntries($file, $filter = null) {
    if (!file_exists($file)) {
        return ["File log không tồn tại: $file"];
    }
    
    // Đọc file log (lấy 1000 dòng gần nhất)
    $lines = [];
    $handle = fopen($file, "r");
    if ($handle) {
        $count = 0;
        $buffer = [];
        
        // Đọc từng dòng của file
        while (($line = fgets($handle)) !== false) {
            $buffer[] = $line;
            $count++;
            
            // Nếu có quá nhiều dòng, loại bỏ dòng đầu tiên
            if ($count > 1000) {
                array_shift($buffer);
            }
        }
        
        fclose($handle);
        $lines = $buffer;
    }
    
    // Lọc các dòng có chứa "dept" hoặc bộ lọc khác
    if ($filter !== null) {
        $filtered_lines = [];
        foreach ($lines as $line) {
            if (stripos($line, $filter) !== false) {
                $filtered_lines[] = $line;
            }
        }
        return $filtered_lines;
    }
    
    return $lines;
}

// Kiểm tra các giá trị dept trong bảng activity_logs
function getDeptStats($connect) {
    $stats = [];
    
    // Lấy tất cả các giá trị dept khác nhau và số lượng
    $sql = "SELECT dept, COUNT(*) as count FROM activity_logs GROUP BY dept ORDER BY count DESC";
    $result = $connect->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dept = $row['dept'];
            $count = $row['count'];
            $dept_name = isset($dept_names[$dept]) ? $dept_names[$dept] : "KHÔNG XÁC ĐỊNH ($dept)";
            $stats[] = [
                'dept' => $dept,
                'dept_name' => $dept_name,
                'count' => $count
            ];
        }
    }
    
    return $stats;
}

// Lấy các bản ghi có dept không xác định
function getUnknownDeptRecords($connect, $limit = 50) {
    $records = [];
    
    $valid_depts = array_keys($GLOBALS['dept_names']);
    $valid_depts_str = "'" . implode("','", $valid_depts) . "'";
    
    $sql = "SELECT * FROM activity_logs WHERE dept NOT IN ($valid_depts_str) OR dept IS NULL OR dept = '' ORDER BY action_time DESC LIMIT ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    
    return $records;
}

// Hàm sửa chữa dữ liệu
function fixDeptData($connect) {
    $fixed_count = 0;
    
    // Lấy danh sách các bản ghi cần sửa
    $valid_depts = array_keys($GLOBALS['dept_names']);
    $valid_depts_str = "'" . implode("','", $valid_depts) . "'";
    
    $sql = "SELECT id, target_id FROM activity_logs WHERE dept NOT IN ($valid_depts_str) OR dept IS NULL OR dept = ''";
    $result = $connect->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $log_id = $row['id'];
            $target_id = $row['target_id'];
            
            // Tìm dept từ bảng tieuchi_dept
            if ($target_id > 0) {
                $stmt = $connect->prepare("SELECT dept FROM tieuchi_dept WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $target_id);
                    $stmt->execute();
                    $dept_result = $stmt->get_result();
                    
                    if ($dept_result && $dept_result->num_rows > 0) {
                        $dept_row = $dept_result->fetch_assoc();
                        $dept = $dept_row['dept'];
                        
                        // Nếu tìm thấy dept hợp lệ, cập nhật bản ghi
                        if (in_array($dept, $valid_depts)) {
                            $update_stmt = $connect->prepare("UPDATE activity_logs SET dept = ? WHERE id = ?");
                            if ($update_stmt) {
                                $update_stmt->bind_param("si", $dept, $log_id);
                                if ($update_stmt->execute()) {
                                    $fixed_count++;
                                }
                                $update_stmt->close();
                            }
                        }
                    }
                    $stmt->close();
                }
            }
        }
    }
    
    // Nếu vẫn còn bản ghi không có giá trị dept hợp lệ, đặt giá trị mặc định 'kehoach'
    $sql = "UPDATE activity_logs SET dept = 'kehoach' WHERE dept NOT IN ($valid_depts_str) OR dept IS NULL OR dept = ''";
    $connect->query($sql);
    
    $affected_rows = $connect->affected_rows;
    $fixed_count += $affected_rows;
    
    return $fixed_count;
}

// Thực hiện sửa chữa nếu được yêu cầu
$fixed_count = 0;
if (isset($_POST['fix_data'])) {
    $fixed_count = fixDeptData($connect);
}

// Lấy dữ liệu
$log_entries = getLogEntries($log_file, "dept");
$dept_stats = getDeptStats($connect);
$unknown_records = getUnknownDeptRecords($connect);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Logs - Giá trị Dept</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .section h2 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .logs { font-family: monospace; font-size: 14px; height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
        .logs p { margin: 2px 0; }
        .unknown { color: red; }
        .action-buttons { margin: 20px 0; }
        .btn { padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Log - Phân tích giá trị Dept</h1>
        
        <?php if ($log_path_created): ?>
        <div class="section">
            <p><strong>Thông báo:</strong> Đã tạo thư mục log tại: <?php echo $log_dir; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Thông tin error_log PHP</h2>
            <p>Đường dẫn file log: <?php echo $log_file; ?></p>
            <div class="logs">
                <?php if (empty($log_entries)): ?>
                <p>Không tìm thấy log nào có chứa từ khóa "dept"</p>
                <?php else: ?>
                    <?php foreach ($log_entries as $entry): ?>
                    <p><?php echo htmlspecialchars($entry); ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Thống kê giá trị Dept trong bảng activity_logs</h2>
            <table>
                <tr>
                    <th>Giá trị Dept</th>
                    <th>Tên hiển thị</th>
                    <th>Số lượng bản ghi</th>
                </tr>
                <?php foreach ($dept_stats as $stat): ?>
                <tr <?php echo (!isset($dept_names[$stat['dept']])) ? 'class="unknown"' : ''; ?>>
                    <td><?php echo htmlspecialchars($stat['dept']); ?></td>
                    <td><?php echo htmlspecialchars($stat['dept_name']); ?></td>
                    <td><?php echo $stat['count']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="section">
            <h2>Các bản ghi có giá trị Dept không xác định</h2>
            <?php if (empty($unknown_records)): ?>
            <p>Không tìm thấy bản ghi nào có giá trị Dept không xác định.</p>
            <?php else: ?>
            <form method="post" action="">
                <div class="action-buttons">
                    <button type="submit" name="fix_data" class="btn">Sửa chữa dữ liệu</button>
                </div>
                <?php if ($fixed_count > 0): ?>
                <p><strong>Đã sửa chữa <?php echo $fixed_count; ?> bản ghi.</strong></p>
                <?php endif; ?>
            </form>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Thời gian</th>
                    <th>Người thực hiện</th>
                    <th>Loại hoạt động</th>
                    <th>Target ID</th>
                    <th>Dept hiện tại</th>
                    <th>Dept tìm thấy từ tieuchi_dept</th>
                </tr>
                <?php foreach ($unknown_records as $record): ?>
                <tr>
                    <td><?php echo $record['id']; ?></td>
                    <td><?php echo $record['action_time']; ?></td>
                    <td><?php echo htmlspecialchars($record['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($record['action_type']); ?></td>
                    <td><?php echo $record['target_id']; ?></td>
                    <td><?php echo htmlspecialchars($record['dept']); ?></td>
                    <td>
                        <?php
                        // Tìm dept từ tieuchi_dept
                        $target_id = $record['target_id'];
                        $dept_found = "";
                        if ($target_id > 0) {
                            $stmt = $connect->prepare("SELECT dept FROM tieuchi_dept WHERE id = ?");
                            if ($stmt) {
                                $stmt->bind_param("i", $target_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($row = $result->fetch_assoc()) {
                                    $dept_found = $row['dept'];
                                    echo htmlspecialchars($dept_found) . 
                                         " (" . (isset($dept_names[$dept_found]) ? 
                                                htmlspecialchars($dept_names[$dept_found]) : 
                                                "KHÔNG XÁC ĐỊNH") . ")";
                                } else {
                                    echo "KHÔNG TÌM THẤY";
                                }
                                $stmt->close();
                            }
                        } else {
                            echo "N/A";
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 