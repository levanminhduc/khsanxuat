<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'contdb.php';
include 'display_deadline.php';

// Khởi tạo biến
$success_message = "";
$error_message = "";
$orders_updated = 0;
$filter_xuong = isset($_POST['xuong']) ? $_POST['xuong'] : '';
$filter_start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$filter_end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$available_workshops = [];

// Lấy danh sách xưởng
$workshop_query = "SELECT DISTINCT xuong FROM khsanxuat ORDER BY xuong";
$workshop_result = $connect->query($workshop_query);
if ($workshop_result) {
    while ($row = $workshop_result->fetch_assoc()) {
        $available_workshops[] = $row['xuong'];
    }
}

// Xử lý cập nhật hàng loạt
if (isset($_POST['batch_update'])) {
    try {
        // Bắt đầu ghi log
        $log_file = fopen("update_deadline_batch.log", "a");
        $log_entry = "[" . date("Y-m-d H:i:s") . "] Bắt đầu cập nhật hàng loạt\n";
        fwrite($log_file, $log_entry);
        
        // Xây dựng truy vấn với các điều kiện lọc
        $sql = "SELECT stt, po, xuong, ngayin, ngayout FROM khsanxuat WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($filter_xuong)) {
            $sql .= " AND xuong = ?";
            $params[] = $filter_xuong;
            $types .= "s";
        }
        
        if (!empty($filter_start_date)) {
            $sql .= " AND ngayin >= ?";
            $params[] = $filter_start_date;
            $types .= "s";
        }
        
        if (!empty($filter_end_date)) {
            $sql .= " AND ngayin <= ?";
            $params[] = $filter_end_date;
            $types .= "s";
        }
        
        // Chuẩn bị và thực thi truy vấn
        $stmt = $connect->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $total_orders = $result->num_rows;
        $orders_updated = 0;
        
        $log_entry = "Tìm thấy $total_orders đơn hàng để cập nhật\n";
        fwrite($log_file, $log_entry);
        
        if ($total_orders > 0) {
            // Bắt đầu transaction
            $connect->begin_transaction();
            
            while ($order = $result->fetch_assoc()) {
                $id_sanxuat = $order['stt'];
                $xuong = $order['xuong'];
                $ngayin = $order['ngayin'];
                $ngayout = $order['ngayout'];
                $po = $order['po'];
                
                // Lấy cài đặt mặc định từ bảng default_settings
                $settings_sql = "SELECT ngay_tinh_han, so_ngay_xuly FROM default_settings 
                               WHERE dept = 'kehoach' AND (xuong = ? OR xuong = '') 
                               ORDER BY CASE WHEN xuong = ? THEN 0 ELSE 1 END
                               LIMIT 1";
                $settings_stmt = $connect->prepare($settings_sql);
                $settings_stmt->bind_param("ss", $xuong, $xuong);
                $settings_stmt->execute();
                $settings_result = $settings_stmt->get_result();
                
                $ngay_tinh_han = 'ngay_vao_cong'; // Mặc định
                $so_ngay_xuly = 7; // Mặc định
                
                // Nếu có cài đặt mặc định, sử dụng cài đặt đó
                if ($settings_result->num_rows > 0) {
                    $settings = $settings_result->fetch_assoc();
                    $ngay_tinh_han = $settings['ngay_tinh_han'];
                    $so_ngay_xuly = $settings['so_ngay_xuly'];
                    
                    $log_entry = "Đơn hàng ID: $id_sanxuat, PO: $po - Cài đặt mặc định: $ngay_tinh_han, $so_ngay_xuly\n";
                    fwrite($log_file, $log_entry);
                } else {
                    $log_entry = "Đơn hàng ID: $id_sanxuat, PO: $po - Không tìm thấy cài đặt mặc định, sử dụng mặc định: $ngay_tinh_han, $so_ngay_xuly\n";
                    fwrite($log_file, $log_entry);
                }
                
                // Tính toán hạn xử lý
                $han_xuly = calculateDeadline($ngayin, $ngayout, $ngay_tinh_han, $so_ngay_xuly);
                
                if ($han_xuly) {
                    // Cập nhật hạn xử lý
                    $update_sql = "UPDATE khsanxuat SET 
                                  han_xuly = ?, 
                                  ngay_tinh_han = ?, 
                                  so_ngay_xuly = ? 
                                  WHERE stt = ?";
                    $update_stmt = $connect->prepare($update_sql);
                    $update_stmt->bind_param("ssii", $han_xuly, $ngay_tinh_han, $so_ngay_xuly, $id_sanxuat);
                    $update_stmt->execute();
                    
                    if ($update_stmt->affected_rows > 0) {
                        $orders_updated++;
                        $log_entry = "Đơn hàng ID: $id_sanxuat, PO: $po - Cập nhật thành công, hạn xử lý mới: $han_xuly\n";
                        fwrite($log_file, $log_entry);
                    } else {
                        $log_entry = "Đơn hàng ID: $id_sanxuat, PO: $po - Không có thay đổi\n";
                        fwrite($log_file, $log_entry);
                    }
                } else {
                    $log_entry = "Đơn hàng ID: $id_sanxuat, PO: $po - Không thể tính hạn xử lý\n";
                    fwrite($log_file, $log_entry);
                }
            }
            
            // Commit transaction
            $connect->commit();
            
            $success_message = "Đã cập nhật thành công $orders_updated/$total_orders đơn hàng!";
            $log_entry = "Kết thúc cập nhật: $success_message\n";
            fwrite($log_file, $log_entry);
        } else {
            $error_message = "Không tìm thấy đơn hàng nào phù hợp với điều kiện lọc!";
            $log_entry = "Kết thúc cập nhật: $error_message\n";
            fwrite($log_file, $log_entry);
        }
        
        fclose($log_file);
        
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        if ($connect->connect_errno == 0) {
            $connect->rollback();
        }
        
        $error_message = "Lỗi: " . $e->getMessage();
        
        // Ghi log lỗi
        if (isset($log_file) && $log_file) {
            $log_entry = "Lỗi: " . $e->getMessage() . "\n";
            fwrite($log_file, $log_entry);
            fclose($log_file);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật Hàng loạt Hạn Xử lý</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 1200px;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .badge {
            font-size: 90%;
            padding: 6px 10px;
            border-radius: 4px;
        }
        .badge-deadline-none {
            background-color: #6c757d;
            color: white;
        }
        .badge-deadline-danger {
            background-color: #dc3545;
            color: white;
        }
        .badge-deadline-warning {
            background-color: #ffc107;
            color: #343a40;
        }
        .badge-deadline-ok {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4"><i class="fas fa-clock"></i> Cập nhật Hàng loạt Hạn Xử lý</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter"></i> Lọc đơn hàng
            </div>
            <div class="card-body">
                <form method="post" action="batch_update_deadline.php">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="xuong">Xưởng:</label>
                                <select id="xuong" name="xuong" class="form-control">
                                    <option value="">Tất cả xưởng</option>
                                    <?php foreach ($available_workshops as $workshop): ?>
                                        <option value="<?php echo $workshop; ?>" <?php echo ($filter_xuong == $workshop) ? 'selected' : ''; ?>>
                                            <?php echo $workshop; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date">Từ ngày:</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $filter_start_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date">Đến ngày:</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $filter_end_date; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="batch_update" class="btn btn-primary" onclick="return confirm('Bạn có chắc chắn muốn cập nhật hạn xử lý cho tất cả đơn hàng phù hợp với điều kiện lọc?')">
                            <i class="fas fa-sync-alt"></i> Cập nhật hàng loạt
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Thông tin
            </div>
            <div class="card-body">
                <p>Công cụ này cho phép bạn cập nhật hàng loạt hạn xử lý cho các đơn hàng dựa trên cài đặt mặc định trong hệ thống.</p>
                <p>Quy trình cập nhật:</p>
                <ol>
                    <li>Hệ thống sẽ lấy các đơn hàng phù hợp với điều kiện lọc</li>
                    <li>Với mỗi đơn hàng, hệ thống sẽ tìm cài đặt mặc định trong bảng <code>default_settings</code> cho xưởng tương ứng</li>
                    <li>Tính toán hạn xử lý dựa trên cài đặt mặc định</li>
                    <li>Cập nhật hạn xử lý, loại tính hạn và số ngày xử lý cho đơn hàng</li>
                </ol>
                <p>Quá trình cập nhật sẽ được ghi lại trong file log <code>update_deadline_batch.log</code> để theo dõi.</p>
                
                <h5 class="mt-4"><i class="fas fa-list-ul"></i> Các loại tính hạn:</h5>
                <ul>
                    <li><strong>ngay_vao:</strong> Ngày vào - số ngày xử lý</li>
                    <li><strong>ngay_vao_cong:</strong> Ngày vào + số ngày xử lý</li>
                    <li><strong>ngay_ra:</strong> Sử dụng ngày ra làm hạn xử lý</li>
                    <li><strong>ngay_ra_tru:</strong> Ngày ra - số ngày xử lý</li>
                </ul>
            </div>
        </div>
        
        <?php if ($orders_updated > 0): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-check-circle"></i> Kết quả cập nhật
                </div>
                <div class="card-body">
                    <p>Đã cập nhật thành công <?php echo $orders_updated; ?> đơn hàng.</p>
                    <p>Chi tiết xem trong file log: <code>update_deadline_batch.log</code></p>
                    <a href="check_default_settings.php" class="btn btn-info">
                        <i class="fas fa-search"></i> Kiểm tra cài đặt hạn xử lý
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại Trang chủ</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html> 