<?php
// Khởi tạo phiên làm việc
session_start();

// Kết nối database
require "contdb.php";

// Kiểm tra quyền truy cập
$access_allowed = true; // Thay đổi điều kiện này tùy theo cấu trúc xác thực của hệ thống

// Kiểm tra xem có hành động gì không
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$message_type = '';

// Kiểm tra xem đã tồn tại bảng default_settings chưa
$table_exists = false;
$result = $connect->query("SHOW TABLES LIKE 'default_settings'");
if ($result->num_rows > 0) {
    $table_exists = true;
} else {
    // Tạo bảng default_settings nếu chưa tồn tại
    $sql_create_table = "CREATE TABLE default_settings (
                         id INT(11) NOT NULL AUTO_INCREMENT,
                         dept VARCHAR(50) NOT NULL,
                         xuong VARCHAR(50) NOT NULL DEFAULT '',
                         id_tieuchi INT(11) NOT NULL,
                         so_ngay_xuly INT(11) DEFAULT 7,
                         ngay_tinh_han VARCHAR(30) DEFAULT 'ngay_vao',
                         PRIMARY KEY (id),
                         KEY dept_tieuchi_xuong (dept, id_tieuchi, xuong)
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    if ($connect->query($sql_create_table) === TRUE) {
        $message = "Đã tạo bảng default_settings thành công";
        $message_type = 'success';
        $table_exists = true;
    } else {
        $message = "Lỗi khi tạo bảng: " . $connect->error;
        $message_type = 'danger';
    }
}

// Xử lý các hành động
if ($action == 'reset_defaults') {
    // Reset về cài đặt mặc định
    $dept = isset($_GET['dept']) ? $_GET['dept'] : '';
    
    if (!empty($dept)) {
        $connect->begin_transaction();
        
        try {
            // Xóa cài đặt mặc định hiện tại
            $sql_delete = "DELETE FROM default_settings WHERE dept = ?";
            $stmt_delete = $connect->prepare($sql_delete);
            $stmt_delete->bind_param("s", $dept);
            $stmt_delete->execute();
            
            // Lấy danh sách tiêu chí của bộ phận
            $sql_tieuchi = "SELECT id FROM tieuchi_dept WHERE dept = ?";
            $stmt_tieuchi = $connect->prepare($sql_tieuchi);
            $stmt_tieuchi->bind_param("s", $dept);
            $stmt_tieuchi->execute();
            $result_tieuchi = $stmt_tieuchi->get_result();
            
            // Thêm cài đặt mặc định mới
            $sql_insert = "INSERT INTO default_settings (dept, xuong, id_tieuchi, so_ngay_xuly, ngay_tinh_han) 
                           VALUES (?, '', ?, 7, 'ngay_vao')";
            $stmt_insert = $connect->prepare($sql_insert);
            
            while ($row_tieuchi = $result_tieuchi->fetch_assoc()) {
                $id_tieuchi = $row_tieuchi['id'];
                $stmt_insert->bind_param("si", $dept, $id_tieuchi);
                $stmt_insert->execute();
            }
            
            $connect->commit();
            $message = "Đã reset cài đặt mặc định cho bộ phận $dept";
            $message_type = 'success';
        } catch (Exception $e) {
            $connect->rollback();
            $message = "Lỗi: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
} elseif ($action == 'apply_global') {
    // Áp dụng cài đặt mặc định toàn cục
    $dept = isset($_GET['dept']) ? $_GET['dept'] : '';
    $ngay_tinh_han = isset($_GET['ngay_tinh_han']) ? $_GET['ngay_tinh_han'] : 'ngay_vao';
    $so_ngay_xuly = isset($_GET['so_ngay_xuly']) ? intval($_GET['so_ngay_xuly']) : 7;
    
    if (!empty($dept)) {
        $connect->begin_transaction();
        
        try {
            // Cập nhật cài đặt mặc định
            $sql_update = "UPDATE default_settings SET so_ngay_xuly = ?, ngay_tinh_han = ? WHERE dept = ?";
            $stmt_update = $connect->prepare($sql_update);
            $stmt_update->bind_param("iss", $so_ngay_xuly, $ngay_tinh_han, $dept);
            $stmt_update->execute();
            
            $affected_rows = $stmt_update->affected_rows;
            
            if ($affected_rows == 0) {
                // Không có cài đặt nào được cập nhật, có thể chưa có dữ liệu
                // Lấy danh sách tiêu chí của bộ phận
                $sql_tieuchi = "SELECT id FROM tieuchi_dept WHERE dept = ?";
                $stmt_tieuchi = $connect->prepare($sql_tieuchi);
                $stmt_tieuchi->bind_param("s", $dept);
                $stmt_tieuchi->execute();
                $result_tieuchi = $stmt_tieuchi->get_result();
                
                // Thêm cài đặt mặc định mới
                $sql_insert = "INSERT INTO default_settings (dept, xuong, id_tieuchi, so_ngay_xuly, ngay_tinh_han) 
                               VALUES (?, '', ?, ?, ?)";
                $stmt_insert = $connect->prepare($sql_insert);
                
                while ($row_tieuchi = $result_tieuchi->fetch_assoc()) {
                    $id_tieuchi = $row_tieuchi['id'];
                    $stmt_insert->bind_param("ssis", $dept, $id_tieuchi, $so_ngay_xuly, $ngay_tinh_han);
                    $stmt_insert->execute();
                    $affected_rows++;
                }
            }
            
            $connect->commit();
            $message = "Đã áp dụng cài đặt mặc định cho $affected_rows tiêu chí của bộ phận $dept";
            $message_type = 'success';
        } catch (Exception $e) {
            $connect->rollback();
            $message = "Lỗi: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Tạo danh sách bộ phận
$departments = [
    'kehoach' => 'Kế hoạch',
    'chuanbi_sanxuat_phong_kt' => 'Chuẩn bị sản xuất (Phòng KT)',
    'kho' => 'Kho', 
    'cat' => 'Cắt',
    'ep_keo' => 'Ép keo',
    'co_dien' => 'Cơ điện',
    'chuyen_may' => 'Chuyền may',
    'kcs' => 'KCS',
    'ui_thanh_pham' => 'Ủi thành phẩm',
    'hoan_thanh' => 'Hoàn thành'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống hạn xử lý</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .header {
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e5e5;
        }
        .footer {
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid #e5e5e5;
            text-align: center;
        }
        .setting-card {
            margin-bottom: 20px;
        }
        .badge-deadline-ok {
            background-color: #28a745;
            color: white;
        }
        .badge-deadline-warning {
            background-color: #ffc107;
            color: black;
        }
        .badge-deadline-danger {
            background-color: #dc3545;
            color: white;
        }
        .badge-deadline-info {
            background-color: #17a2b8;
            color: white;
        }
        .badge-deadline-none {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cài đặt hệ thống hạn xử lý</h1>
            <p class="lead">Quản lý cài đặt hạn xử lý tiêu chí đánh giá</p>
            
            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Bộ phận</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($departments as $key => $name): ?>
                        <a href="?dept=<?php echo $key; ?>" class="list-group-item list-group-item-action <?php echo (isset($_GET['dept']) && $_GET['dept'] == $key) ? 'active' : ''; ?>">
                            <?php echo $name; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($table_exists && isset($_GET['dept'])): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Công cụ</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <a href="?action=reset_defaults&dept=<?php echo $_GET['dept']; ?>" class="btn btn-warning btn-block" onclick="return confirm('Bạn có chắc chắn muốn reset cài đặt mặc định về giá trị ban đầu?');">
                                <i class="fas fa-undo"></i> Reset về mặc định
                            </a>
                        </div>
                        
                        <form method="get" action="">
                            <input type="hidden" name="action" value="apply_global">
                            <input type="hidden" name="dept" value="<?php echo $_GET['dept']; ?>">
                            
                            <div class="form-group">
                                <label>Số ngày xử lý</label>
                                <input type="number" name="so_ngay_xuly" class="form-control" value="7" min="1" max="30">
                            </div>
                            
                            <div class="form-group">
                                <label>Tính hạn dựa trên</label>
                                <select name="ngay_tinh_han" class="form-control">
                                    <option value="ngay_vao">Ngày vào</option>
                                    <option value="ngay_vao_cong">Ngày vào + số ngày</option>
                                    <option value="ngay_ra">Ngày ra</option>
                                    <option value="ngay_ra_tru">Ngày ra - số ngày</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Áp dụng cho tất cả tiêu chí
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-9">
                <?php if (isset($_GET['dept'])): ?>
                <?php
                $dept = $_GET['dept'];
                
                // Lấy danh sách tiêu chí của bộ phận
                $sql_tieuchi = "SELECT t.id, t.noidung, t.thutu, 
                                COALESCE(d.so_ngay_xuly, 7) as so_ngay_xuly, 
                                COALESCE(d.ngay_tinh_han, 'ngay_vao') as ngay_tinh_han
                                FROM tieuchi_dept t
                                LEFT JOIN default_settings d ON t.id = d.id_tieuchi AND d.dept = ?
                                WHERE t.dept = ?
                                ORDER BY t.thutu, t.id";
                
                $stmt_tieuchi = $connect->prepare($sql_tieuchi);
                $stmt_tieuchi->bind_param("ss", $dept, $dept);
                $stmt_tieuchi->execute();
                $result_tieuchi = $stmt_tieuchi->get_result();
                ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Cài đặt hạn xử lý cho bộ phận: <?php echo $departments[$dept]; ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result_tieuchi->num_rows > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tiêu chí</th>
                                    <th>Số ngày xử lý</th>
                                    <th>Tính hạn dựa trên</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; ?>
                                <?php while ($row = $result_tieuchi->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo $row['noidung']; ?></td>
                                    <td><?php echo $row['so_ngay_xuly']; ?> ngày</td>
                                    <td>
                                        <?php 
                                        $ngay_tinh_han_text = 'Ngày vào';
                                        if ($row['ngay_tinh_han'] == 'ngay_vao_cong') {
                                            $ngay_tinh_han_text = 'Ngày vào + số ngày';
                                        } else if ($row['ngay_tinh_han'] == 'ngay_ra') {
                                            $ngay_tinh_han_text = 'Ngày ra';
                                        } else if ($row['ngay_tinh_han'] == 'ngay_ra_tru') {
                                            $ngay_tinh_han_text = 'Ngày ra - số ngày';
                                        }
                                        echo $ngay_tinh_han_text;
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Không có tiêu chí nào cho bộ phận này.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Hướng dẫn sử dụng</h5>
                    </div>
                    <div class="card-body">
                        <h6>1. Cài đặt hạn xử lý mặc định</h6>
                        <p>Cài đặt này sẽ được áp dụng cho tất cả đơn hàng mới. Các đơn hàng hiện tại sẽ không bị ảnh hưởng trừ khi bạn chọn áp dụng lại cài đặt mặc định.</p>
                        
                        <h6>2. Các loại tính hạn xử lý</h6>
                        <ul>
                            <li><strong>Ngày vào</strong>: Hạn xử lý = Ngày vào</li>
                            <li><strong>Ngày vào + số ngày</strong>: Hạn xử lý = Ngày vào + số ngày xử lý</li>
                            <li><strong>Ngày ra</strong>: Hạn xử lý = Ngày ra</li>
                            <li><strong>Ngày ra - số ngày</strong>: Hạn xử lý = Ngày ra - số ngày xử lý</li>
                        </ul>
                        
                        <h6>3. Cài đặt cho từng đơn hàng</h6>
                        <p>Khi vào trang chi tiết đơn hàng, bạn có thể tùy chỉnh hạn xử lý cho từng tiêu chí của đơn hàng đó.</p>
                        <p>Các tùy chỉnh này sẽ chỉ áp dụng cho đơn hàng hiện tại và không ảnh hưởng đến cài đặt mặc định.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Vui lòng chọn một bộ phận để xem cài đặt.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>Hệ thống quản lý hạn xử lý tiêu chí đánh giá &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 