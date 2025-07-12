<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database");
}

// Khởi tạo phiên làm việc nếu chưa có
session_start();

// Lấy thông tin từ URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';

if ($id <= 0 || empty($dept)) {
    die("Thiếu thông tin cần thiết");
}

// Tạm thời bỏ kiểm tra user
$is_admin = true;

// Lấy thông tin từ database
try {
    $sql = "SELECT style, po, xuong, line1, qty FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Không tìm thấy dữ liệu");
    }

    $row = $result->fetch_assoc();
    $style = $row['style'];
    $po = $row['po'];
    $xuong = $row['xuong'];
    $line = $row['line1'];
    $qty = $row['qty'];

    // Tạo tên thư mục file
    $file_folder = "template_files/{$dept}/{$id}";

    // Kiểm tra và tạo thư mục nếu chưa tồn tại
    if (!file_exists("template_files")) {
        mkdir("template_files", 0777, true);
    }

    if (!file_exists("template_files/{$dept}")) {
        mkdir("template_files/{$dept}", 0777, true);
    }

    if (!file_exists($file_folder)) {
        mkdir($file_folder, 0777, true);
    }

    // Kiểm tra nếu không tồn tại bảng templates
    $sql_check_table_templates = "SHOW TABLES LIKE 'dept_templates'";
    $result_check_templates = $connect->query($sql_check_table_templates);
    if ($result_check_templates->num_rows == 0) {
        $sql_create_template_table = "CREATE TABLE dept_templates (
            id INT(11) NOT NULL AUTO_INCREMENT,
            dept VARCHAR(50) NOT NULL,
            template_name VARCHAR(100) NOT NULL,
            template_description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_dept (dept)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if (!$connect->query($sql_create_template_table)) {
            echo "Lỗi tạo bảng dept_templates: " . $connect->error;
        }
    }

    // Kiểm tra nếu không tồn tại bảng template_files
    $sql_check_table_files = "SHOW TABLES LIKE 'dept_template_files'";
    $result_check_files = $connect->query($sql_check_table_files);
    if ($result_check_files->num_rows == 0) {
        $sql_create_files_table = "CREATE TABLE dept_template_files (
            id INT(11) NOT NULL AUTO_INCREMENT,
            id_template INT(11) NOT NULL,
            id_khsanxuat INT(11) NOT NULL,
            dept VARCHAR(50) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_id_template (id_template),
            KEY idx_id_khsanxuat (id_khsanxuat),
            KEY idx_dept (dept)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if (!$connect->query($sql_create_files_table)) {
            echo "Lỗi tạo bảng dept_template_files: " . $connect->error;
        }
    }

    $message = '';
    $message_type = '';

    // Kiểm tra thông báo từ delete_file.php
    if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
        $message = "Đã xóa file thành công.";
        $message_type = "success";
    }

    // Xử lý thêm template mới
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_template'])) {
        $template_name = $_POST['template_name'];
        $template_description = $_POST['template_description'];

        if (empty($template_name)) {
            $message = "Vui lòng nhập tên template.";
            $message_type = "error";
        } else {
            // Kiểm tra xem template đã tồn tại chưa
            $check_template = "SELECT id FROM dept_templates WHERE dept = ? AND template_name = ?";
            $stmt_check = $connect->prepare($check_template);
            $stmt_check->bind_param("ss", $dept, $template_name);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $message = "Template '{$template_name}' đã tồn tại trong phòng ban này.";
                $message_type = "error";
            } else {
                // Thêm template mới vào database
                $sql_insert = "INSERT INTO dept_templates (dept, template_name, template_description) VALUES (?, ?, ?)";
                $stmt_insert = $connect->prepare($sql_insert);
                $stmt_insert->bind_param("sss", $dept, $template_name, $template_description);

                if ($stmt_insert->execute()) {
                    $message = "Đã thêm template '{$template_name}' thành công.";
                    $message_type = "success";
                } else {
                    $message = "Lỗi khi thêm template: " . $connect->error;
                    $message_type = "error";
                }
            }
        }
    }

    // Xử lý upload file
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["template_file"])) {
        $id_template = isset($_POST['id_template']) ? intval($_POST['id_template']) : 0;

        if ($id_template <= 0) {
            $message = "Vui lòng chọn template cho file.";
            $message_type = "error";
        } else {
            $total = count($_FILES['template_file']['name']);

            // Kiểm tra thư mục upload
            $template_folder = $file_folder . '/template_' . $id_template;
            if (!file_exists($template_folder)) {
                mkdir($template_folder, 0777, true);
            }

            $success_count = 0;
            $error_messages = [];

            // Loop through each file
            for ($i = 0; $i < $total; $i++) {
                $file_name = $_FILES['template_file']['name'][$i];
                $file_tmp = $_FILES['template_file']['tmp_name'][$i];
                $file_size = $_FILES['template_file']['size'][$i];
                $file_error = $_FILES['template_file']['error'][$i];

                // Kiểm tra lỗi
                if ($file_error === 0) {
                    // Kiểm tra kích thước (giới hạn 30MB)
                    if ($file_size <= 30485760) { // 30MB = 30*1024*1024
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'xls', 'xlsx', 'doc', 'docx', 'bmp', 'tif', 'tiff', 'webp');

                        if (in_array($file_ext, $allowed_exts)) {
                            // Tạo tên file duy nhất
                            $safe_style = preg_replace('/[^a-zA-Z0-9_]/', '_', $style);
                            $new_file_name = $safe_style . '_' . $dept . '_' . date('YmdHis') . '_' . $i . '.' . $file_ext;

                            $upload_path = $template_folder . '/' . $new_file_name;

                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                $success_count++;

                                // Xác định loại file
                                $file_type = '';
                                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'webp'])) {
                                    $file_type = 'image';
                                } elseif ($file_ext === 'pdf') {
                                    $file_type = 'pdf';
                                } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
                                    $file_type = 'excel';
                                } elseif (in_array($file_ext, ['doc', 'docx'])) {
                                    $file_type = 'word';
                                }

                                // Thêm thông tin file vào database
                                $sql_insert = "INSERT INTO dept_template_files (id_template, id_khsanxuat, dept, file_path, file_name, file_type, upload_date)
                                              VALUES (?, ?, ?, ?, ?, ?, NOW())";
                                $stmt_insert = $connect->prepare($sql_insert);
                                $stmt_insert->bind_param("iissss", $id_template, $id, $dept, $upload_path, $file_name, $file_type);
                                $stmt_insert->execute();
                            } else {
                                $error_messages[] = "Không thể upload file $file_name. Lỗi hệ thống!";
                            }
                        } else {
                            $error_messages[] = "File $file_name không đúng định dạng. Chỉ cho phép JPG, JPEG, PNG, GIF, PDF, Excel và Word!";
                        }
                    } else {
                        $error_messages[] = "File $file_name quá lớn. Giới hạn 30MB!";
                    }
                } else {
                    $error_messages[] = "Có lỗi khi upload file $file_name: " . $file_error;
                }
            }

            if ($success_count > 0) {
                $message = "Đã upload thành công $success_count file.";
                $message_type = "success";
            }

            if (!empty($error_messages)) {
                $message .= "<br>Lỗi: " . implode("<br>", $error_messages);
                $message_type = "error";
            }
        }
    }

    // Lấy danh sách templates
    $templates = [];
    $sql_templates = "SELECT id, template_name, template_description FROM dept_templates WHERE dept = ? ORDER BY id ASC";
    $stmt_templates = $connect->prepare($sql_templates);
    $stmt_templates->bind_param("s", $dept);
    $stmt_templates->execute();
    $result_templates = $stmt_templates->get_result();
    while ($row_template = $result_templates->fetch_assoc()) {
        $templates[] = $row_template;
    }

    // Lấy danh sách files đã upload
    $template_files = [];
    $sql_files = "SELECT f.*, t.template_name
                 FROM dept_template_files f
                 JOIN dept_templates t ON f.id_template = t.id
                 WHERE f.id_khsanxuat = ? AND f.dept = ?
                 ORDER BY f.upload_date DESC";
    $stmt_files = $connect->prepare($sql_files);
    $stmt_files->bind_param("is", $id, $dept);
    $stmt_files->execute();
    $result_files = $stmt_files->get_result();
    while ($row_file = $result_files->fetch_assoc()) {
        $template_files[] = $row_file;
    }

} catch (Exception $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Thêm mảng chuyển đổi tên bộ phận
$dept_names = array(
    'kehoach' => 'Kế Hoạch',
    'cat' => 'Cắt',
    'ep_keo' => 'Ép Keo',
    'chuanbi_sanxuat_phong_kt' => 'Phòng Kỹ Thuật',
    'may' => 'May',
    'hoan_thanh' => 'Hoàn Thành',
    'co_dien' => 'Cơ Điện',
    'kcs' => 'KCS',
    'ui_thanh_pham' => 'Ủi Thành Phẩm',
    'chuyen_may' => 'Chuyền May',
    'kho' => 'Kho Nguyên, Phụ Liệu',
    'quan_ly_cl' => 'Quản Lý Chất Lượng',
    'quan_ly_sx' => 'Quản Lý Sản Xuất'
);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Template - <?php echo htmlspecialchars($style); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .navbar {
            display: flex;
            background-color: #1a365d;
            color: white;
            padding: 10px 20px;
            align-items: center;
        }

        .navbar-left {
            margin-right: 20px;
        }

        .navbar-center {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        h1 {
            color: rgb(255, 255, 255);
        }

        h2, h3 {
            color: rgb(20, 53, 131);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #1a365d;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table th, .info-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .info-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .upload-form {
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .textarea-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #1a365d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #0d2240;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .file-list {
            margin-top: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }

        .file-list-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            font-weight: bold;
            border-bottom: 1px solid #eee;
        }

        .file-list-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            background-color: white;
            transition: background-color 0.2s;
        }

        .file-list-item:last-child {
            border-bottom: none;
        }

        .file-list-item:hover {
            background-color: #f8f9fa;
        }

        .file-icon {
            width: 40px;
            text-align: center;
            font-size: 20px;
            margin-right: 15px;
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .file-meta {
            color: #6c757d;
            font-size: 13px;
        }

        .file-actions {
            display: flex;
            gap: 5px;
        }

        .file-actions a {
            padding: 5px 8px;
            font-size: 12px;
        }

        .no-files {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 4px;
            color: #6c757d;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            background-color: #f2f2f2;
            color: #333;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
            cursor: pointer;
        }

        .tab.active {
            background-color: #1a365d;
            color: white;
            border-color: #1a365d;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Style cho thông tin chi tiết mã hàng */
        .product-info {
            margin-bottom: 20px;
            background-color: #f0f7ff;
            border-radius: 4px;
            border-left: 4px solid #1a365d;
            padding: 15px;
        }

        .style-header {
            margin-bottom: 15px;
        }

        .style-header h3 {
            color: #1a365d;
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .product-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .detail-row {
            display: flex;
            align-items: center;
        }

        .detail-item {
            font-size: 14px;
            line-height: 1.4;
        }

        .detail-item strong {
            color: #333;
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }

        /* Cải thiện cho điện thoại */
        @media only screen and (max-width: 700px) {
            .container {
                padding: 0 10px;
            }

            .info-table {
                font-size: 14px;
            }

            .file-list-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .file-icon {
                margin-bottom: 10px;
            }

            .file-actions {
                margin-top: 10px;
                width: 100%;
                justify-content: space-between;
            }

            .product-details {
                gap: 5px;
            }

            .detail-item {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Thanh điều hướng -->
    <div class="navbar">
        <div class="navbar-left">
            <a href="/khsanxuat/index.php"><img width="45px" src="img/logoht.png" alt="Logo"></a>
        </div>
        <!-- <div class="navbar-center">
            <h1>Quản Lý Hồ Sơ SA</h1>
        </div> -->
        <!-- <div class="navbar-center">
            <h1 style="font-size: 24px; margin: 0;">Quản Lý Template - <?php echo htmlspecialchars($style); ?></h1>
        </div> -->
    </div>

    <div class="container">
        <a href="indexdept.php?dept=<?php echo urlencode($dept); ?>&id=<?php echo $id; ?>" class="back-link">
            &larr; Quay lại trang chi tiết
        </a>

        <div class="card">
            <div class="product-info">
                <div class="style-header">
                    <h3>Style: <?php echo htmlspecialchars($style); ?> (STT: <?php echo $id; ?>)</h3>
                </div>
                <div class="product-details">
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>PO:</strong> <?php echo htmlspecialchars($po); ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Line:</strong> <?php echo htmlspecialchars($line); ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Xưởng:</strong> <?php echo htmlspecialchars($xuong); ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Số lượng:</strong> <?php echo htmlspecialchars($qty); ?>
                        </div>
                    </div>
                    <?php
                    // Lấy thông tin ngày vào và ngày ra từ database
                    $sql_dates = "SELECT ngayin, ngayout FROM khsanxuat WHERE stt = ?";
                    $stmt_dates = $connect->prepare($sql_dates);
                    $stmt_dates->bind_param("i", $id);
                    $stmt_dates->execute();
                    $result_dates = $stmt_dates->get_result();
                    $row_dates = $result_dates->fetch_assoc();

                    if ($row_dates) {
                        $ngayin = new DateTime($row_dates['ngayin']);
                        $ngayout = new DateTime($row_dates['ngayout']);
                        $ngayin_formatted = $ngayin->format('d/m/Y');
                        $ngayout_formatted = $ngayout->format('d/m/Y');
                    }
                    ?>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Ngày vào:</strong> <?php echo isset($ngayin_formatted) ? $ngayin_formatted : 'N/A'; ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Ngày ra:</strong> <?php echo isset($ngayout_formatted) ? $ngayout_formatted : 'N/A'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Tab navigation -->
        <div class="tabs">
            <div class="tab active" data-tab="templates">Biểu Mẫu</div>
            <div class="tab" data-tab="upload">Upload Files</div>
            <div class="tab" data-tab="files">Danh sách Files</div>
        </div>

        <!-- Tab Templates -->
        <div class="tab-content active" id="tab-templates">
            <div class="card">
                <h2>Danh sách Biểu Mẫu</h2>

                <?php if (empty($templates)): ?>
                <div class="no-files">
                    <p>Chưa có biểu mẫu nào cho phòng ban này.</p>
                    <p>Thêm biểu mẫu mới để bắt đầu quản lý files.</p>
                </div>
                <?php else: ?>
                <table class="info-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Tên Biểu Mẫu</th>
                            <th>Mô tả</th>
                            <th style="width: 100px;">Số Files</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><?php echo $template['id']; ?></td>
                            <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                            <td><?php echo htmlspecialchars($template['template_description']); ?></td>
                            <td>
                                <?php
                                // Đếm số file của template
                                $sql_count = "SELECT COUNT(*) as count FROM dept_template_files WHERE id_template = ? AND id_khsanxuat = ? AND dept = ?";
                                $stmt_count = $connect->prepare($sql_count);
                                $stmt_count->bind_param("iis", $template['id'], $id, $dept);
                                $stmt_count->execute();
                                $result_count = $stmt_count->get_result();
                                $count = $result_count->fetch_assoc()['count'];
                                echo $count;
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <h3 style="margin-top: 30px;">Thêm Biểu Mẫu Mới</h3>
                <form action="" method="post" class="upload-form">
                    <div class="form-group">
                        <label for="template_name" class="form-label">Tên Biểu Mẫu:</label>
                        <input type="text" id="template_name" name="template_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="template_description" class="form-label">Mô tả:</label>
                        <textarea id="template_description" name="template_description" class="form-control textarea-control"></textarea>
                    </div>

                    <button type="submit" name="add_template" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Thêm Template
                    </button>
                </form>
            </div>
        </div>

        <!-- Tab Upload Files -->
        <div class="tab-content" id="tab-upload">
            <div class="card">
                <h2>Upload Files</h2>

                <?php if (empty($templates)): ?>
                <div class="alert alert-error">
                    <p>Vui lòng tạo biểu mẫu trước khi upload files.</p>
                </div>
                <?php else: ?>
                <form action="" method="post" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="id_template" class="form-label">Chọn Biểu Mẫu:</label>
                        <select id="id_template" name="id_template" class="form-control" required>
                            <option value="">-- Chọn Biểu Mẫu --</option>
                            <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>">
                                 <?php echo $template['id']; ?> - <?php echo htmlspecialchars($template['template_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="template_file" class="form-label">Chọn Files:</label>
                        <input type="file" id="template_file" name="template_file[]" class="form-control" multiple required>
                        <small style="display: block; margin-top: 5px; color: #6c757d;">
                            Chọn nhiều files (Hỗ trợ: JPG, JPEG, PNG, PDF, Excel và Word) - Dung lượng: < 30MB ( Liên Hệ IT để tăng giới hạn )
                        </small>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Upload Files
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Files List -->
        <div class="tab-content" id="tab-files">
            <div class="card">
                <h2>Danh sách Files đã Upload</h2>

                <?php if (empty($template_files)): ?>
                <div class="no-files">
                    <p>Chưa có file nào được upload.</p>
                </div>
                <?php else: ?>
                <div class="file-list">
                    <div class="file-list-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>Tổng cộng: <?php echo count($template_files); ?> files</div>
                        <?php if (count($template_files) > 0): ?>
                        <a href="download_all_files.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>" class="btn btn-success" style="margin-left: 10px;">
                            <i class="fas fa-cloud-download-alt"></i> Tải xuống tất cả
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php foreach ($template_files as $file): ?>
                    <div class="file-list-item">
                        <div class="file-icon">
                            <?php
                            $icon = 'fas fa-file';
                            switch ($file['file_type']) {
                                case 'image':
                                    $icon = 'fas fa-file-image';
                                    break;
                                case 'pdf':
                                    $icon = 'fas fa-file-pdf';
                                    break;
                                case 'excel':
                                    $icon = 'fas fa-file-excel';
                                    break;
                                case 'word':
                                    $icon = 'fas fa-file-word';
                                    break;
                            }
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                        </div>

                        <div class="file-info">
                            <div class="file-name"><?php echo htmlspecialchars($file['file_name']); ?></div>
                            <div class="file-meta">
                                 <?php echo htmlspecialchars($file['template_name']); ?><br>
                                Uploaded: <?php echo date('d/m/Y H:i', strtotime($file['upload_date'])); ?>
                            </div>
                        </div>

                        <div class="file-actions">
                            <a href="<?php echo $file['file_path']; ?>" target="_blank" class="btn">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                            <a href="<?php echo $file['file_path']; ?>" download class="btn btn-success">
                                <i class="fas fa-download"></i> Tải về
                            </a>
                            <a href="delete_template_file.php?id=<?php echo $file['id']; ?>&id_sanxuat=<?php echo $id; ?>&dept=<?php echo $dept; ?>"
                               class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa file này?');">
                                <i class="fas fa-trash-alt"></i> Xóa
                            </a>
                        </div>

                        <?php if ($file['file_type'] === 'image'): ?>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                            <img src="<?php echo $file['file_path']; ?>" alt="<?php echo htmlspecialchars($file['file_name']); ?>" style="max-width: 100%; max-height: 200px; display: block; margin: 0 auto; border-radius: 4px;">
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Tabs functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));

                    // Add active class to clicked tab
                    this.classList.add('active');

                    // Hide all tab contents
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Show the selected tab content
                    const tabId = 'tab-' + this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>