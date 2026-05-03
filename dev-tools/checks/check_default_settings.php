<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'contdb.php';
include 'display_deadline.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra Cài đặt Mặc định Hạn Xử lý</title>
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
        .test-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .test-heading {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .test-result {
            padding: 15px;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-top: 15px;
        }
        .btn-test {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .code-block {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #dee2e6;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4"><i class="fas fa-clock"></i> Kiểm tra Cài đặt Mặc định Hạn Xử lý</h1>
        
        <div class="card">
            <div class="card-header">
                Kiểm tra cách tính hạn xử lý
            </div>
            <div class="card-body">
                <form id="testForm" method="post">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ngay_vao">Ngày vào:</label>
                                <input type="date" id="ngay_vao" name="ngay_vao" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ngay_ra">Ngày ra:</label>
                                <input type="date" id="ngay_ra" name="ngay_ra" class="form-control" value="<?php echo date('Y-m-d', strtotime('+10 days')); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="id_sanxuat">ID Đơn hàng (tùy chọn):</label>
                                <input type="number" id="id_sanxuat" name="id_sanxuat" class="form-control" placeholder="Nhập ID để kiểm tra đơn hàng cụ thể">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ngay_tinh_han">Loại tính hạn:</label>
                                <?php echo displayNgayTinhHanSelect('ngay_vao', 'ngay_tinh_han'); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="so_ngay_xuly">Số ngày xử lý:</label>
                                <input type="number" id="so_ngay_xuly" name="so_ngay_xuly" class="form-control" value="1" min="1" max="30" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" name="test_submit" class="btn btn-primary form-control">
                                    <i class="fas fa-calculator"></i> Tính toán
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <?php
                // Xử lý form
                if (isset($_POST['test_submit'])) {
                    $ngay_vao = isset($_POST['ngay_vao']) ? $_POST['ngay_vao'] : date('Y-m-d');
                    $ngay_ra = isset($_POST['ngay_ra']) ? $_POST['ngay_ra'] : date('Y-m-d', strtotime('+10 days'));
                    $ngay_tinh_han = isset($_POST['ngay_tinh_han']) ? $_POST['ngay_tinh_han'] : 'ngay_vao_cong';
                    $so_ngay_xuly = isset($_POST['so_ngay_xuly']) ? intval($_POST['so_ngay_xuly']) : 7;
                    $id_sanxuat = isset($_POST['id_sanxuat']) ? intval($_POST['id_sanxuat']) : 0;
                    
                    // Tính toán hạn xử lý
                    $han_xuly = calculateDeadline($ngay_vao, $ngay_ra, $ngay_tinh_han, $so_ngay_xuly);
                    
                    echo '<div class="test-result mt-4">';
                    echo '<h4 class="test-heading">Kết quả tính toán</h4>';
                    
                    echo '<div class="row">';
                    echo '<div class="col-md-6">';
                    echo '<p><strong>Ngày vào:</strong> ' . date('d/m/Y', strtotime($ngay_vao)) . '</p>';
                    echo '<p><strong>Ngày ra:</strong> ' . date('d/m/Y', strtotime($ngay_ra)) . '</p>';
                    echo '<p><strong>Loại tính hạn:</strong> ' . $ngay_tinh_han . '</p>';
                    echo '<p><strong>Số ngày xử lý:</strong> ' . $so_ngay_xuly . '</p>';
                    echo '</div>';
                    
                    echo '<div class="col-md-6">';
                    echo '<p><strong>Hạn xử lý tính được:</strong> ' . ($han_xuly ? date('d/m/Y', strtotime($han_xuly)) : 'Không xác định') . '</p>';
                    echo '<p><strong>Hiển thị badge:</strong> ' . displayDeadlineBadge($han_xuly, false, $ngay_tinh_han, $so_ngay_xuly) . '</p>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Nếu có ID đơn hàng, hiển thị thông tin
                    if ($id_sanxuat > 0) {
                        $sql = "SELECT stt, po, xuong, ngayin, ngayout, han_xuly, ngay_tinh_han, so_ngay_xuly 
                                FROM khsanxuat WHERE stt = ?";
                        $stmt = $connect->prepare($sql);
                        $stmt->bind_param("i", $id_sanxuat);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $order = $result->fetch_assoc();
                            
                            echo '<h4 class="test-heading mt-4">Thông tin đơn hàng</h4>';
                            echo '<div class="row">';
                            echo '<div class="col-md-6">';
                            echo '<p><strong>ID:</strong> ' . $order['stt'] . '</p>';
                            echo '<p><strong>PO:</strong> ' . $order['po'] . '</p>';
                            echo '<p><strong>Xưởng:</strong> ' . $order['xuong'] . '</p>';
                            echo '<p><strong>Ngày vào:</strong> ' . date('d/m/Y', strtotime($order['ngayin'])) . '</p>';
                            echo '<p><strong>Ngày ra:</strong> ' . date('d/m/Y', strtotime($order['ngayout'])) . '</p>';
                            echo '</div>';
                            
                            echo '<div class="col-md-6">';
                            echo '<p><strong>Hạn xử lý hiện tại:</strong> ' . ($order['han_xuly'] ? date('d/m/Y', strtotime($order['han_xuly'])) : 'Chưa thiết lập') . '</p>';
                            echo '<p><strong>Loại tính hạn:</strong> ' . ($order['ngay_tinh_han'] ?: 'Chưa thiết lập') . '</p>';
                            echo '<p><strong>Số ngày xử lý:</strong> ' . ($order['so_ngay_xuly'] ?: 'Chưa thiết lập') . '</p>';
                            echo '<p><strong>Hiển thị badge:</strong> ' . displayDeadlineBadge($order['han_xuly'], false, $order['ngay_tinh_han'], $order['so_ngay_xuly']) . '</p>';
                            
                            // Tính lại hạn xử lý dựa trên cài đặt mặc định
                            $calculated_deadline = calculateDeadline(
                                $order['ngayin'], 
                                $order['ngayout'], 
                                $order['ngay_tinh_han'] ?: 'ngay_vao_cong', 
                                $order['so_ngay_xuly'] ?: 7
                            );
                            
                            echo '<p><strong>Hạn xử lý tính lại:</strong> ' . ($calculated_deadline ? date('d/m/Y', strtotime($calculated_deadline)) : 'Không xác định') . '</p>';
                            
                            // Form cập nhật hạn xử lý
                            echo '<form method="post">';
                            echo '<input type="hidden" name="update_id" value="' . $order['stt'] . '">';
                            echo '<button type="submit" name="update_deadline" class="btn btn-warning">';
                            echo '<i class="fas fa-sync-alt"></i> Cập nhật hạn xử lý</button>';
                            echo '</form>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning mt-3">Không tìm thấy đơn hàng với ID ' . $id_sanxuat . '</div>';
                        }
                    }
                    
                    echo '</div>';
                }
                
                // Xử lý cập nhật hạn xử lý
                if (isset($_POST['update_deadline']) && isset($_POST['update_id'])) {
                    $update_id = intval($_POST['update_id']);
                    
                    // Lấy thông tin đơn hàng
                    $sql = "SELECT xuong, ngayin, ngayout, ngay_tinh_han, so_ngay_xuly FROM khsanxuat WHERE stt = ?";
                    $stmt = $connect->prepare($sql);
                    $stmt->bind_param("i", $update_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $order = $result->fetch_assoc();
                        
                        // Lấy cài đặt mặc định từ bảng default_settings
                        $sql_default = "SELECT ngay_tinh_han, so_ngay_xuly FROM default_settings 
                                      WHERE dept = 'kehoach' AND (xuong = ? OR xuong = '') 
                                      ORDER BY CASE WHEN xuong = ? THEN 0 ELSE 1 END
                                      LIMIT 1";
                        $stmt_default = $connect->prepare($sql_default);
                        $stmt_default->bind_param("ss", $order['xuong'], $order['xuong']);
                        $stmt_default->execute();
                        $result_default = $stmt_default->get_result();
                        
                        $ngay_tinh_han = $order['ngay_tinh_han'] ?: 'ngay_vao_cong';
                        $so_ngay_xuly = $order['so_ngay_xuly'] ?: 7;
                        
                        // Nếu có cài đặt mặc định, sử dụng cài đặt đó
                        if ($result_default->num_rows > 0) {
                            $default_settings = $result_default->fetch_assoc();
                            $ngay_tinh_han = $default_settings['ngay_tinh_han'];
                            $so_ngay_xuly = $default_settings['so_ngay_xuly'];
                        }
                        
                        // Tính toán hạn xử lý
                        $han_xuly = calculateDeadline($order['ngayin'], $order['ngayout'], $ngay_tinh_han, $so_ngay_xuly);
                        
                        // Cập nhật hạn xử lý
                        if ($han_xuly) {
                            $sql_update = "UPDATE khsanxuat SET 
                                          han_xuly = ?, 
                                          ngay_tinh_han = ?, 
                                          so_ngay_xuly = ? 
                                          WHERE stt = ?";
                            $stmt_update = $connect->prepare($sql_update);
                            $stmt_update->bind_param("ssii", $han_xuly, $ngay_tinh_han, $so_ngay_xuly, $update_id);
                            
                            if ($stmt_update->execute()) {
                                echo '<div class="alert alert-success mt-3">';
                                echo '<i class="fas fa-check-circle"></i> Đã cập nhật hạn xử lý thành công!';
                                echo '<ul>';
                                echo '<li>ID đơn hàng: ' . $update_id . '</li>';
                                echo '<li>Hạn xử lý mới: ' . date('d/m/Y', strtotime($han_xuly)) . '</li>';
                                echo '<li>Loại tính hạn: ' . $ngay_tinh_han . '</li>';
                                echo '<li>Số ngày xử lý: ' . $so_ngay_xuly . '</li>';
                                echo '</ul>';
                                echo '<a href="check_default_settings.php" class="btn btn-sm btn-outline-secondary">Quay lại</a>';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-danger mt-3">';
                                echo '<i class="fas fa-exclamation-triangle"></i> Lỗi khi cập nhật hạn xử lý: ' . $stmt_update->error;
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning mt-3">';
                            echo '<i class="fas fa-exclamation-triangle"></i> Không thể tính toán hạn xử lý!';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger mt-3">';
                        echo '<i class="fas fa-exclamation-triangle"></i> Không tìm thấy đơn hàng với ID ' . $update_id;
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                Ví dụ cách tính hạn xử lý
            </div>
            <div class="card-body">
                <h5>1. Ngày vào trừ (ngay_vao)</h5>
                <p>Giả sử:</p>
                <ul>
                    <li>Ngày vào: 10/03/2025</li>
                    <li>Số ngày xử lý: 1</li>
                    <li>Loại tính hạn: ngay_vao</li>
                </ul>
                <p>Hạn xử lý sẽ là: <strong>09/03/2025</strong> (Ngày vào - 1 ngày)</p>
                
                <h5>2. Ngày vào cộng (ngay_vao_cong)</h5>
                <p>Giả sử:</p>
                <ul>
                    <li>Ngày vào: 10/03/2025</li>
                    <li>Số ngày xử lý: 7</li>
                    <li>Loại tính hạn: ngay_vao_cong</li>
                </ul>
                <p>Hạn xử lý sẽ là: <strong>17/03/2025</strong> (Ngày vào + 7 ngày)</p>
                
                <h5>3. Ngày ra (ngay_ra)</h5>
                <p>Giả sử:</p>
                <ul>
                    <li>Ngày ra: 20/03/2025</li>
                    <li>Loại tính hạn: ngay_ra</li>
                </ul>
                <p>Hạn xử lý sẽ là: <strong>20/03/2025</strong> (Chính là ngày ra)</p>
                
                <h5>4. Ngày ra trừ (ngay_ra_tru)</h5>
                <p>Giả sử:</p>
                <ul>
                    <li>Ngày ra: 20/03/2025</li>
                    <li>Số ngày xử lý: 5</li>
                    <li>Loại tính hạn: ngay_ra_tru</li>
                </ul>
                <p>Hạn xử lý sẽ là: <strong>15/03/2025</strong> (Ngày ra - 5 ngày)</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                Các đơn hàng gần đây
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>PO</th>
                            <th>Xưởng</th>
                            <th>Ngày vào</th>
                            <th>Ngày ra</th>
                            <th>Hạn xử lý</th>
                            <th>Tính hạn</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Lấy danh sách các đơn hàng gần đây
                        $sql = "SELECT stt, po, xuong, ngayin, ngayout, han_xuly, ngay_tinh_han, so_ngay_xuly 
                                FROM khsanxuat 
                                ORDER BY stt DESC 
                                LIMIT 10";
                        $result = $connect->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $row['stt'] . '</td>';
                                echo '<td>' . $row['po'] . '</td>';
                                echo '<td>' . $row['xuong'] . '</td>';
                                echo '<td>' . date('d/m/Y', strtotime($row['ngayin'])) . '</td>';
                                echo '<td>' . date('d/m/Y', strtotime($row['ngayout'])) . '</td>';
                                echo '<td>' . displayDeadlineBadge($row['han_xuly'], false, $row['ngay_tinh_han'], $row['so_ngay_xuly']) . '</td>';
                                
                                $ngay_tinh_info = '';
                                if (!empty($row['ngay_tinh_han'])) {
                                    if ($row['ngay_tinh_han'] == 'ngay_vao') {
                                        $ngay_tinh_info = 'Ngày vào - ' . $row['so_ngay_xuly'];
                                    } else if ($row['ngay_tinh_han'] == 'ngay_vao_cong') {
                                        $ngay_tinh_info = 'Ngày vào + ' . $row['so_ngay_xuly'];
                                    } else if ($row['ngay_tinh_han'] == 'ngay_ra') {
                                        $ngay_tinh_info = 'Ngày ra';
                                    } else if ($row['ngay_tinh_han'] == 'ngay_ra_tru') {
                                        $ngay_tinh_info = 'Ngày ra - ' . $row['so_ngay_xuly'];
                                    }
                                } else {
                                    $ngay_tinh_info = 'Không xác định';
                                }
                                
                                echo '<td>' . $ngay_tinh_info . '</td>';
                                
                                echo '<td>';
                                echo '<form method="post" action="?id=' . $row['stt'] . '">';
                                echo '<input type="hidden" name="id_sanxuat" value="' . $row['stt'] . '">';
                                echo '<button type="submit" name="test_submit" class="btn btn-sm btn-info">';
                                echo '<i class="fas fa-search"></i> Chi tiết</button>';
                                echo '</form>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="8" class="text-center">Không có đơn hàng nào</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
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
            
            // Cập nhật giá trị số ngày xử lý khi thay đổi loại tính hạn
            $('#ngay_tinh_han').change(function() {
                var selectedOption = $(this).val();
                if (selectedOption == 'ngay_ra') {
                    $('#so_ngay_xuly').val(0);
                    $('#so_ngay_xuly').prop('disabled', true);
                } else {
                    $('#so_ngay_xuly').prop('disabled', false);
                    if ($('#so_ngay_xuly').val() == '0') {
                        $('#so_ngay_xuly').val(1);
                    }
                }
            });
        });
    </script>
</body>
</html> 