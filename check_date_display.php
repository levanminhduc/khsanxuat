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
    <title>Kiểm tra Date Display</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4"><i class="fas fa-calendar-alt"></i> Kiểm tra Date Display</h1>
        
        <div class="card">
            <div class="card-header">
                Kiểm tra cách hiển thị date_display
            </div>
            <div class="card-body">
                <form id="testForm" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_sanxuat">ID Đơn hàng:</label>
                                <input type="number" id="id_sanxuat" name="id_sanxuat" class="form-control" placeholder="Nhập ID để kiểm tra đơn hàng cụ thể" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" name="check_submit" class="btn btn-primary form-control">
                                    <i class="fas fa-search"></i> Kiểm tra
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <?php
                // Xử lý form
                if (isset($_POST['check_submit']) && isset($_POST['id_sanxuat'])) {
                    $id_sanxuat = intval($_POST['id_sanxuat']);
                    
                    // Lấy thông tin đơn hàng
                    $sql = "SELECT stt, po, xuong, ngayin, ngayout, han_xuly, ngay_tinh_han, so_ngay_xuly 
                            FROM khsanxuat WHERE stt = ?";
                    $stmt = $connect->prepare($sql);
                    $stmt->bind_param("i", $id_sanxuat);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $order = $result->fetch_assoc();
                        
                        // Format lại các ngày
                        $ngayin_display = getDateDisplay($order['ngayin']);
                        $ngayout_display = getDateDisplay($order['ngayout']);
                        $han_xuly_display = getDateDisplay($order['han_xuly']);
                        
                        echo '<div class="test-result mt-4">';
                        echo '<h4 class="test-heading">Thông tin đơn hàng</h4>';
                        
                        echo '<div class="row">';
                        echo '<div class="col-md-6">';
                        echo '<p><strong>ID:</strong> ' . $order['stt'] . '</p>';
                        echo '<p><strong>PO:</strong> ' . $order['po'] . '</p>';
                        echo '<p><strong>Xưởng:</strong> ' . $order['xuong'] . '</p>';
                        echo '<p><strong>Ngày vào:</strong> ' . $ngayin_display . ' (' . $order['ngayin'] . ')</p>';
                        echo '<p><strong>Ngày ra:</strong> ' . $ngayout_display . ' (' . $order['ngayout'] . ')</p>';
                        echo '</div>';
                        
                        echo '<div class="col-md-6">';
                        echo '<p><strong>Hạn xử lý:</strong> ' . $han_xuly_display . ' (' . $order['han_xuly'] . ')</p>';
                        echo '<p><strong>Hiển thị badge:</strong> ' . displayDeadlineBadge($order['han_xuly'], false, $order['ngay_tinh_han'], $order['so_ngay_xuly']) . '</p>';
                        
                        // Mô tả cách tính hạn
                        $cach_tinh = '';
                        switch ($order['ngay_tinh_han']) {
                            case 'ngay_vao':
                                $cach_tinh = 'Ngày vào - ' . $order['so_ngay_xuly'] . ' ngày';
                                break;
                            case 'ngay_vao_cong':
                                $cach_tinh = 'Ngày vào + ' . $order['so_ngay_xuly'] . ' ngày';
                                break;
                            case 'ngay_ra':
                                $cach_tinh = 'Ngày ra';
                                break;
                            case 'ngay_ra_tru':
                                $cach_tinh = 'Ngày ra - ' . $order['so_ngay_xuly'] . ' ngày';
                                break;
                        }
                        echo '<p><strong>Cách tính hạn:</strong> ' . $cach_tinh . '</p>';
                        
                        // Form cập nhật date_display
                        echo '<form method="post" action="import_date_display.php" id="updateForm">';
                        echo '<input type="hidden" name="id" value="' . $order['stt'] . '">';
                        echo '<button type="button" onclick="updateDateDisplay(' . $order['stt'] . ')" class="btn btn-warning">';
                        echo '<i class="fas fa-sync-alt"></i> Cập nhật date_display</button>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                        
                        // Lấy danh sách tiêu chí
                        $sql_tc = "SELECT dt.id, dt.id_tieuchi, dt.han_xuly, dt.so_ngay_xuly, dt.ngay_tinh_han,
                                          tc.noidung, tc.dept
                                  FROM danhgia_tieuchi dt
                                  JOIN tieuchi_dept tc ON dt.id_tieuchi = tc.id
                                  WHERE dt.id_sanxuat = ?";
                        $stmt_tc = $connect->prepare($sql_tc);
                        $stmt_tc->bind_param("i", $id_sanxuat);
                        $stmt_tc->execute();
                        $result_tc = $stmt_tc->get_result();
                        
                        if ($result_tc->num_rows > 0) {
                            echo '<h4 class="test-heading mt-4">Danh sách tiêu chí</h4>';
                            echo '<table class="table table-bordered table-striped">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>ID</th>';
                            echo '<th>Nội dung</th>';
                            echo '<th>Hạn xử lý</th>';
                            echo '<th>Cách tính</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                            
                            while ($tc = $result_tc->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $tc['id_tieuchi'] . '</td>';
                                echo '<td>' . $tc['noidung'] . '</td>';
                                
                                // Format lại ngày hạn xử lý
                                $han_tc_display = getDateDisplay($tc['han_xuly']);
                                
                                echo '<td>';
                                echo '<span class="date_display">' . $han_tc_display . '</span> ';
                                echo '<span class="text-muted">(' . $tc['han_xuly'] . ')</span><br>';
                                echo displayDeadlineBadge($tc['han_xuly'], true, $tc['ngay_tinh_han'], $tc['so_ngay_xuly']);
                                echo '</td>';
                                
                                // Mô tả cách tính hạn
                                $cach_tinh = '';
                                switch ($tc['ngay_tinh_han']) {
                                    case 'ngay_vao':
                                        $cach_tinh = 'Ngày vào - ' . $tc['so_ngay_xuly'] . ' ngày';
                                        break;
                                    case 'ngay_vao_cong':
                                        $cach_tinh = 'Ngày vào + ' . $tc['so_ngay_xuly'] . ' ngày';
                                        break;
                                    case 'ngay_ra':
                                        $cach_tinh = 'Ngày ra';
                                        break;
                                    case 'ngay_ra_tru':
                                        $cach_tinh = 'Ngày ra - ' . $tc['so_ngay_xuly'] . ' ngày';
                                        break;
                                }
                                echo '<td>' . $cach_tinh . '</td>';
                                
                                echo '</tr>';
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<div class="alert alert-info mt-3">Đơn hàng này chưa có tiêu chí nào.</div>';
                        }
                        
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-warning mt-3">Không tìm thấy đơn hàng với ID ' . $id_sanxuat . '</div>';
                    }
                }
                ?>
                
                <div id="updateResult" class="mt-3" style="display: none;"></div>
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
                                echo '<td>' . getDateDisplay($row['ngayin']) . '</td>';
                                echo '<td>' . getDateDisplay($row['ngayout']) . '</td>';
                                echo '<td>' . displayDeadlineBadge($row['han_xuly'], false, $row['ngay_tinh_han'], $row['so_ngay_xuly']) . '</td>';
                                
                                echo '<td>';
                                echo '<form method="post">';
                                echo '<input type="hidden" name="id_sanxuat" value="' . $row['stt'] . '">';
                                echo '<button type="submit" name="check_submit" class="btn btn-sm btn-info">';
                                echo '<i class="fas fa-search"></i> Chi tiết</button>';
                                echo '</form>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">Không có đơn hàng nào</td></tr>';
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
        
        function updateDateDisplay(id) {
            // Hiển thị thông báo đang cập nhật
            $('#updateResult').html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Đang cập nhật date_display...</div>').show();
            
            // Gửi yêu cầu AJAX để cập nhật
            $.ajax({
                url: 'import_date_display.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var html = '<div class="alert alert-success">';
                        html += '<i class="fas fa-check-circle"></i> ' + response.message;
                        html += '<br>Số tiêu chí đã cập nhật: ' + response.updated;
                        html += '</div>';
                        
                        if (response.order) {
                            html += '<div class="mt-3">';
                            html += '<p><strong>Hạn xử lý:</strong> ' + response.order.han_xuly_display + '</p>';
                            html += '</div>';
                        }
                        
                        $('#updateResult').html(html);
                        
                        // Tự động tải lại trang sau 2 giây
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#updateResult').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#updateResult').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Lỗi kết nối server!</div>');
                }
            });
        }
    </script>
</body>
</html> 