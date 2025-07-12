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
    $sql = "SELECT style, po, xuong, line1 FROM khsanxuat WHERE stt = ?";
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
    
    // Tạo tên thư mục hình ảnh
    $image_folder = "images/{$dept}/{$id}";
    
    // Kiểm tra và tạo thư mục nếu chưa tồn tại
    if (!file_exists("images")) {
        mkdir("images", 0777, true);
    }
    
    if (!file_exists("images/{$dept}")) {
        mkdir("images/{$dept}", 0777, true);
    }
    
    if (!file_exists($image_folder)) {
        mkdir($image_folder, 0777, true);
    }
    
    // Lấy danh sách tiêu chí của phòng ban
    $sql_tieuchi = "SELECT id, thutu, noidung FROM tieuchi_dept WHERE dept = ? ORDER BY thutu ASC";
    $stmt_tieuchi = $connect->prepare($sql_tieuchi);
    $stmt_tieuchi->bind_param("s", $dept);
    $stmt_tieuchi->execute();
    $result_tieuchi = $stmt_tieuchi->get_result();

    $tieuchi_list = [];
    while ($row_tieuchi = $result_tieuchi->fetch_assoc()) {
        $tieuchi_list[$row_tieuchi['id']] = $row_tieuchi['thutu'] . '. ' . $row_tieuchi['noidung'];
    }
    
    // Xử lý upload hình ảnh
    $message = '';
    $message_type = '';
    
    // Kiểm tra thông báo từ delete_image.php
    if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
        $message = "Đã xóa hình ảnh thành công.";
        $message_type = "success";
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image_file"])) {
        $total = count($_FILES['image_file']['name']);
        
        // Kiểm tra thư mục upload
        if (!file_exists($image_folder)) {
            mkdir($image_folder, 0777, true);
        }
        
        $success_count = 0;
        $error_messages = [];
        
        // Lấy id tiêu chí đã chọn
        $id_tieuchi = isset($_POST['id_tieuchi']) ? intval($_POST['id_tieuchi']) : 0;
        if ($id_tieuchi <= 0) {
            $error_messages[] = "Vui lòng chọn tiêu chí cho hình ảnh.";
        } else {
            // Loop through each file
            for ($i = 0; $i < $total; $i++) {
                $file_name = $_FILES['image_file']['name'][$i];
                $file_tmp = $_FILES['image_file']['tmp_name'][$i];
                $file_size = $_FILES['image_file']['size'][$i];
                $file_error = $_FILES['image_file']['error'][$i];
                
                // Kiểm tra lỗi
                if ($file_error === 0) {
                    // Kiểm tra kích thước (giới hạn 10MB)
                    if ($file_size <= 30485760) { // 10MB = 10*1024*1024
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed_exts = array('jpg', 'jpeg', 'png', 'gif');
                        
                        if (in_array($file_ext, $allowed_exts)) {
                            // Tạo tên file duy nhất - loại bỏ ký tự đặc biệt trong style
                            $safe_style = preg_replace('/[^a-zA-Z0-9_]/', '_', $style);
                            $new_file_name = $safe_style . '_' . $dept . '_' . date('YmdHis') . '_' . $i . '.' . $file_ext;
                            
                            // Tạo thư mục cho tiêu chí nếu cần
                            $tieuchi_folder = $image_folder . '/tieuchi_' . $id_tieuchi;
                            if (!file_exists($tieuchi_folder)) {
                                if (!mkdir($tieuchi_folder, 0777, true)) {
                                    $error_messages[] = "Không thể tạo thư mục $tieuchi_folder";
                                    continue;
                                }
                            }
                            
                            $upload_path = $tieuchi_folder . '/' . $new_file_name;
                            
                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                $success_count++;
                                
                                // Thêm thông tin hình ảnh vào database
                                $sql_insert = "INSERT INTO khsanxuat_images (id_khsanxuat, dept, image_path, id_tieuchi, upload_date) 
                                              VALUES (?, ?, ?, ?, NOW())";
                                $stmt_insert = $connect->prepare($sql_insert);
                                $image_path = $upload_path;
                                $stmt_insert->bind_param("issi", $id, $dept, $image_path, $id_tieuchi);
                                $stmt_insert->execute();
                            } else {
                                $error_messages[] = "Không thể upload file $file_name. Lỗi hệ thống!";
                            }
                        } else {
                            $error_messages[] = "File $file_name không đúng định dạng. Chỉ cho phép JPG, JPEG, PNG và GIF!";
                        }
                    } else {
                        $error_messages[] = "File $file_name quá lớn. Giới hạn 10MB!";
                    }
                } else {
                    $error_messages[] = "Có lỗi khi upload file $file_name: " . $file_error;
                }
            }
        }
        
        if ($success_count > 0) {
            $message = "Đã upload thành công $success_count hình ảnh.";
            $message_type = "success";
        }
        
        if (!empty($error_messages)) {
            $message .= "<br>Lỗi: " . implode("<br>", $error_messages);
            $message_type = "error";
        }
    }
    
    // Lấy danh sách hình ảnh đã upload
    $images = [];
    $sql_get_images = "SELECT i.*, t.noidung as tieuchi_name 
                       FROM khsanxuat_images i 
                       LEFT JOIN tieuchi_dept t ON i.id_tieuchi = t.id 
                       WHERE i.id_khsanxuat = ? AND i.dept = ?
                       ORDER BY i.upload_date DESC";
    $stmt_images = $connect->prepare($sql_get_images);
    $stmt_images->bind_param("is", $id, $dept);
    $stmt_images->execute();
    $result_images = $stmt_images->get_result();

    while ($row_image = $result_images->fetch_assoc()) {
        $images[] = $row_image;
    }
    
} catch (Exception $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Kiểm tra và tạo bảng lưu thông tin hình ảnh nếu chưa tồn tại
$sql_check_table = "SHOW TABLES LIKE 'khsanxuat_images'";
$result_check_table = $connect->query($sql_check_table);
if ($result_check_table->num_rows == 0) {
    $sql_create_table = "CREATE TABLE khsanxuat_images (
        id INT(11) NOT NULL AUTO_INCREMENT,
        id_khsanxuat INT(11) NOT NULL,
        dept VARCHAR(50) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        id_tieuchi INT(11) NULL,
        upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_id_khsanxuat (id_khsanxuat),
        KEY idx_dept (dept),
        KEY idx_id_tieuchi (id_tieuchi)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!$connect->query($sql_create_table)) {
        echo "Lỗi tạo bảng khsanxuat_images: " . $connect->error;
    }
} else {
    // Kiểm tra xem đã có cột id_tieuchi chưa
    $check_column = $connect->query("SHOW COLUMNS FROM khsanxuat_images LIKE 'id_tieuchi'");
    if ($check_column->num_rows == 0) {
        // Thêm cột id_tieuchi nếu chưa có
        $connect->query("ALTER TABLE khsanxuat_images ADD COLUMN id_tieuchi INT(11) NULL AFTER image_path, ADD INDEX idx_id_tieuchi (id_tieuchi)");
    }
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
    <title>Xử Lý Hình Ảnh - <?php echo htmlspecialchars($style); ?></title>
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
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        h1, h2, h3 {
            color: #1a365d;
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
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #1a365d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
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
        
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .gallery-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        
        .gallery-item .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .gallery-item:hover .overlay {
            opacity: 1;
        }
        
        .gallery-item .overlay a {
            color: white;
            text-decoration: none;
            margin: 0 5px;
            padding: 5px 10px;
            background: rgba(0,0,0,0.5);
            border-radius: 4px;
        }
        
        .no-images {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 4px;
            color: #6c757d;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            padding: 20px;
            box-sizing: border-box;
        }
        
        .modal-content {
            position: relative;
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90vh;
        }
        
        .modal-image {
            width: 100%;
            height: auto;
            max-height: 80vh;
            object-fit: contain;
        }
        
        .modal-info {
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: 10px;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .modal-close:hover {
            color: #bbb;
        }
        
        @media only screen and (max-width: 700px) {
            .modal-content {
                width: 100%;
            }
        }
        
        /* Style cho thông tin chi tiết mã hàng */
        .product-info {
            margin-bottom: 20px;
        }
        
        .product-info-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .product-info-item {
            flex: 1 1 200px;
        }
        
        .product-info-label {
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 5px;
            display: block;
        }
        
        .product-info-value {
            font-size: 16px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        
        @media (max-width: 768px) {
            .product-info-item {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Thanh điều hướng -->
    <div class="navbar">
        <div class="navbar-left">
            <a href="/khsanxuat/index.php"><img width="45px" src="img/logoht.png" /></a>
        </div>
        <div class="navbar-center" style="display: flex; justify-content: center; width: 100%;">
            <h1 style="font-size: 24px; margin: 0;">Xử Lý Hình Ảnh - <?php echo htmlspecialchars($style); ?></h1>
        </div>
    </div>

    <div class="container">
        <a href="indexdept.php?dept=<?php echo urlencode($dept); ?>&id=<?php echo $id; ?>" class="back-link">
            &larr; Quay lại trang chi tiết
        </a>
        
        <div class="card">
            <h2>Thông tin chi tiết mã hàng</h2>
            <div class="product-info">
                <div class="product-info-header">
                    <div class="product-info-item">
                        <span class="product-info-label">Mã hàng (Style)</span>
                        <div class="product-info-value"><?php echo htmlspecialchars($style); ?></div>
                    </div>
                    <div class="product-info-item">
                        <span class="product-info-label">PO</span>
                        <div class="product-info-value"><?php echo htmlspecialchars($po); ?></div>
                    </div>
                    <div class="product-info-item">
                        <span class="product-info-label">Xưởng</span>
                        <div class="product-info-value"><?php echo htmlspecialchars($xuong); ?></div>
                    </div>
                    <div class="product-info-item">
                        <span class="product-info-label">Line</span>
                        <div class="product-info-value"><?php echo htmlspecialchars($line); ?></div>
                    </div>
                    <div class="product-info-item">
                        <span class="product-info-label">Bộ phận</span>
                        <div class="product-info-value"><?php echo htmlspecialchars($dept_names[$dept] ?? $dept); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Upload hình ảnh</h2>
            <form action="" method="post" enctype="multipart/form-data" class="upload-form">
                <div class="form-group">
                    <label for="id_tieuchi" class="form-label">Chọn tiêu chí</label>
                    <select name="id_tieuchi" id="id_tieuchi" class="form-control" required>
                        <option value="">-- Chọn tiêu chí --</option>
                        <?php foreach ($tieuchi_list as $id_tc => $noidung): ?>
                        <option value="<?php echo $id_tc; ?>">
                            <?php echo htmlspecialchars($noidung); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="image_file" class="form-label">Chọn hình ảnh (JPG, JPEG, PNG)</label>
                    <input type="file" name="image_file[]" id="image_file" class="form-control" multiple required accept="image/*">
                </div>
                <button type="submit" class="btn btn-success">Upload hình ảnh</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Hình ảnh đã upload</h2>
            <?php if (empty($images)): ?>
            <div class="no-images">
                <p>Chưa có hình ảnh nào được upload</p>
            </div>
            <?php else: ?>
            <div class="gallery">
                <?php foreach ($images as $image): ?>
                <div class="gallery-item">
                    <img src="<?php echo $image['image_path']; ?>" alt="Hình ảnh <?php echo $style; ?>">
                    <div class="detail-info" style="padding: 8px; background-color: #f8f9fa; font-size: 12px;">
                        <strong>Tiêu chí:</strong> <?php echo htmlspecialchars($image['tieuchi_name'] ?? 'Không xác định'); ?><br>
                        <strong>Ngày upload:</strong> <?php echo date('d/m/Y H:i', strtotime($image['upload_date'])); ?>
                    </div>
                    <div class="overlay">
                        <a href="javascript:void(0)" onclick="openModal('<?php echo $image['image_path']; ?>', '<?php echo htmlspecialchars($image['tieuchi_name'] ?? 'Không xác định'); ?>', '<?php echo date('d/m/Y H:i', strtotime($image['upload_date'])); ?>')">Xem</a>
                        <a href="delete_image.php?id_image=<?php echo $image['id']; ?>&id=<?php echo $id; ?>&dept=<?php echo urlencode($dept); ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')">Xóa</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <span class="modal-close">&times;</span>
        <div class="modal-content">
            <img id="modalImage" class="modal-image" src="" alt="">
            <div id="modalInfo" class="modal-info"></div>
        </div>
    </div>

    <script>
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalInfo = document.getElementById('modalInfo');
    const closeBtn = document.getElementsByClassName('modal-close')[0];

    function openModal(imgSrc, tieuchi, uploadDate) {
        modal.style.display = "block";
        modalImg.src = imgSrc;
        modalInfo.innerHTML = `<strong>Tiêu chí:</strong> ${tieuchi}<br><strong>Ngày upload:</strong> ${uploadDate}`;
    }

    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    modal.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            modal.style.display = "none";
        }
    });
    </script>
</body>
</html>
