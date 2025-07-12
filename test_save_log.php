<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kết nối database và logger
include 'db_connect.php';
include 'activity_logger.php';

// Khởi tạo phiên làm việc
session_start();

// Lấy logger
$logger = getActivityLogger($connect);

// Mảng ánh xạ tên bộ phận
$dept_names = [
    'kehoach' => 'BỘ PHẬN KẾ HOẠCH',
    'chuanbi_sanxuat_phong_kt' => 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)',
    'kho' => 'KHO NGUYÊN, PHỤ LIỆU',
    'cat' => 'BỘ PHẬN CẮT',
    'ep_keo' => 'BỘ PHẬN ÉP KEO',
    'co_dien' => 'BỘ PHẬN CƠ ĐIỆN',
    'chuyen_may' => 'BỘ PHẬN CHUYỀN MAY',
    'kcs' => 'BỘ PHẬN KCS',
    'ui_thanh_pham' => 'BỘ PHẬN ỦI THÀNH PHẨM',
    'hoan_thanh' => 'BỘ PHẬN HOÀN THÀNH'
];

$message = '';
$status = '';

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept = isset($_POST['dept']) ? trim($_POST['dept']) : '';
    $id_sanxuat = isset($_POST['id_sanxuat']) ? intval($_POST['id_sanxuat']) : 0;
    
    if (!empty($dept) && $id_sanxuat > 0) {
        // Tạo thông tin test
        $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
        $old_value = "tiêu chí 1: điểm 0, người thực hiện Test";
        $new_value = "tiêu chí 1: điểm 3, người thực hiện Test";
        
        // Ghi log
        try {
            $log_result = $logger->logActivity(
                'update_multiple',
                'tieuchi',
                $target_id,
                $id_sanxuat,
                $dept,
                $old_value,
                $new_value,
                [
                    'action' => 'update_multiple',
                    'status' => 'success',
                    'changes' => "Thay đổi tiêu chí 1",
                    'changed_tieuchi' => [
                        [
                            'thutu' => 1,
                            'noidung' => 'Tiêu chí test 1'
                        ]
                    ]
                ]
            );
            
            if ($log_result) {
                $message = "Đã ghi log thành công với bộ phận: " . $dept;
                $status = "success";
            } else {
                $message = "Lỗi khi ghi log";
                $status = "error";
            }
        } catch (Exception $e) {
            $message = "Lỗi: " . $e->getMessage();
            $status = "error";
        }
    } else {
        $message = "Vui lòng điền đầy đủ thông tin";
        $status = "error";
    }
}

// Lấy 10 bản ghi gần nhất
$latest_logs = $logger->getActivityLogs(['limit' => 10]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Ghi Log DEPT</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        h1, h2 {
            color: #1a365d;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        button {
            background-color: #1a365d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #0d2240;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        
        th {
            background-color: #1a365d;
            color: white;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .important {
            font-weight: bold;
            color: #cc0000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Ghi Log DEPT</h1>
        
        <?php if (!empty($message)): ?>
        <div class="message <?php echo $status; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <h2>Nhập thông tin để test</h2>
        <form method="POST">
            <div class="form-group">
                <label for="id_sanxuat">ID Sản Xuất:</label>
                <input type="number" id="id_sanxuat" name="id_sanxuat" value="7674" required>
            </div>
            
            <div class="form-group">
                <label for="dept">Bộ Phận:</label>
                <select id="dept" name="dept" required>
                    <option value="">-- Chọn bộ phận --</option>
                    <?php foreach ($dept_names as $key => $value): ?>
                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="target_id">Target ID (ID Tiêu Chí):</label>
                <input type="number" id="target_id" name="target_id" value="1">
                <small>Điền 0 để test với trường hợp không có tiêu chí cụ thể</small>
            </div>
            
            <button type="submit">Tạo Log Test</button>
        </form>
        
        <h2>Log Gần Đây</h2>
        <p>Kiểm tra xem thông tin bộ phận có được hiển thị chính xác không:</p>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Thời gian</th>
                    <th>Người thực hiện</th>
                    <th>Target ID</th>
                    <th>Mã hàng</th>
                    <th class="important">Bộ phận (Raw)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latest_logs as $log): ?>
                <tr>
                    <td><?php echo $log['id']; ?></td>
                    <td><?php echo $log['action_time']; ?></td>
                    <td><?php echo htmlspecialchars($log['user_full_name']); ?></td>
                    <td><?php echo $log['target_id']; ?></td>
                    <td><?php echo $log['id_khsanxuat']; ?></td>
                    <td class="important"><?php echo htmlspecialchars($log['dept']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px;">
            <h3>Hướng dẫn kiểm tra:</h3>
            <ol>
                <li>Chọn một bộ phận từ dropdown và nhấn "Tạo Log Test"</li>
                <li>Kiểm tra trong bảng Log Gần Đây, đảm bảo bộ phận hiển thị chính xác</li>
                <li>Truy cập trang <a href="theodoi.php">Xem Log Hoạt Động</a> để kiểm tra xem bộ phận có hiển thị đúng</li>
            </ol>
        </div>
        
        <p><a href="theodoi.php" style="display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #1a365d; color: white; text-decoration: none; border-radius: 4px;">Đi đến trang Xem Log Hoạt Động</a></p>
    </div>
</body>
</html> 