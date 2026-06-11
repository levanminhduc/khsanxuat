<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
require_once __DIR__ . '/../bootstrap.php';

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
    $sql_tieuchi = "SELECT id, thutu, noidung, nhom FROM tieuchi_dept WHERE dept = ? ORDER BY
        CASE
            WHEN dept = 'kho' THEN
                CASE nhom
                    WHEN 'Kho Nguyên Liệu' THEN 1
                    WHEN 'Kho Phụ Liệu' THEN 2
                    ELSE 3
                END
            WHEN dept = 'chuanbi_sanxuat_phong_kt' THEN
                CASE nhom
                    WHEN 'Nhóm Nghiệp Vụ' THEN 1
                    WHEN 'Nhóm May Mẫu' THEN 2
                    WHEN 'Nhóm Quy Trình' THEN 3
                    ELSE 4
                END
            ELSE 0
        END,
        thutu ASC";
    $stmt_tieuchi = $connect->prepare($sql_tieuchi);
    $stmt_tieuchi->bind_param("s", $dept);
    $stmt_tieuchi->execute();
    $result_tieuchi = $stmt_tieuchi->get_result();

    $tieuchi_list = [];
    while ($row_tieuchi = $result_tieuchi->fetch_assoc()) {
        // Thêm thông tin nhóm vào nội dung để người dùng dễ phân biệt
        $nhom_prefix = '';
        if (($dept === 'kho' || $dept === 'chuanbi_sanxuat_phong_kt') && !empty($row_tieuchi['nhom'])) {
            $nhom_prefix = '[' . $row_tieuchi['nhom'] . '] ';
        }
        $tieuchi_list[$row_tieuchi['id']] = $nhom_prefix . $row_tieuchi['thutu'] . '. ' . $row_tieuchi['noidung'];
    }

    // Xử lý upload hình ảnh
    $message = '';
    $message_type = '';

    // Kiểm tra thông báo từ delete_image.php
    if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
        $message = "Đã xóa hình ảnh thành công.";

        // Thêm thông báo nếu có reset điểm đánh giá
        if (isset($_GET['score_reset']) && isset($_GET['tieuchi_reset'])) {
            $tieuchi_reset = intval($_GET['tieuchi_reset']);
            $tieuchi_name = '';

            // Tìm tên tiêu chí từ danh sách
            foreach ($tieuchi_list as $tc_id => $tc_name) {
                if ($tc_id == $tieuchi_reset) {
                    $tieuchi_name = $tc_name;
                    break;
                }
            }

            $message .= "<br><strong>Lưu ý:</strong> Điểm đánh giá của tiêu chí " .
                       htmlspecialchars($tieuchi_name) .
                       " đã được đặt lại về 0 vì đây là tiêu chí bắt buộc phải có hình ảnh.";
        }

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

    // Lấy thông tin điểm đánh giá và người thực hiện hiện tại
    $tieuchi_data = [];
    $sql_get_danhgia = "SELECT dt.id_tieuchi, dt.diem_danhgia, dt.nguoi_thuchien, dt.ghichu, dt.da_thuchien
                        FROM danhgia_tieuchi dt
                        WHERE dt.id_sanxuat = ? AND id_tieuchi IN (SELECT id FROM tieuchi_dept WHERE dept = ?)";
    $stmt_danhgia = $connect->prepare($sql_get_danhgia);
    $stmt_danhgia->bind_param("is", $id, $dept);
    $stmt_danhgia->execute();
    $result_danhgia = $stmt_danhgia->get_result();

    while ($row_danhgia = $result_danhgia->fetch_assoc()) {
        $tieuchi_data[$row_danhgia['id_tieuchi']] = $row_danhgia;
    }

    // Lấy danh sách nhân viên cho dropdown
    $nhan_vien = [];
    $sql_nhanvien = "SELECT id, ten FROM nhan_vien WHERE phong_ban = ? AND active = 1 ORDER BY ten";
    $stmt_nhanvien = $connect->prepare($sql_nhanvien);
    $stmt_nhanvien->bind_param("s", $dept);
    $stmt_nhanvien->execute();
    $result_nhanvien = $stmt_nhanvien->get_result();

    while ($row_nhanvien = $result_nhanvien->fetch_assoc()) {
        $nhan_vien[] = $row_nhanvien;
    }

    // Xử lý form đánh giá điểm nếu có
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_score'])) {
        // Bắt đầu transaction
        $connect->begin_transaction();

        try {
            $tieuchi_id = isset($_POST['tieuchi_id']) ? intval($_POST['tieuchi_id']) : 0;
            $diem_danhgia = isset($_POST['diem_danhgia']) ? $_POST['diem_danhgia'] : 0;
            $nguoi_thuchien = isset($_POST['nguoi_thuchien']) ? $_POST['nguoi_thuchien'] : '';
            $ghichu = isset($_POST['ghichu']) ? $_POST['ghichu'] : '';
            $da_thuchien = $diem_danhgia > 0 ? 1 : 0;

            if ($tieuchi_id <= 0) {
                throw new Exception("Tiêu chí không hợp lệ");
            }

            // Kiểm tra xem đã có bản ghi đánh giá cho tiêu chí này chưa
            $sql_check = "SELECT han_xuly, so_ngay_xuly, ngay_tinh_han FROM danhgia_tieuchi WHERE id_sanxuat = ? AND id_tieuchi = ?";
            $stmt_check = $connect->prepare($sql_check);
            $stmt_check->bind_param("ii", $id, $tieuchi_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // Đã có bản ghi, lấy thông tin hạn xử lý để giữ nguyên
                $row_check = $result_check->fetch_assoc();

                // Cập nhật bản ghi hiện có
                $sql_update = "UPDATE danhgia_tieuchi
                              SET nguoi_thuchien = ?, da_thuchien = ?, diem_danhgia = ?, ghichu = ?
                              WHERE id_sanxuat = ? AND id_tieuchi = ?";
                $stmt_update = $connect->prepare($sql_update);
                $stmt_update->bind_param("iidsii", $nguoi_thuchien, $da_thuchien, $diem_danhgia, $ghichu, $id, $tieuchi_id);
                $stmt_update->execute();
            } else {
                // Chưa có bản ghi, lấy thông tin deadline từ khsanxuat
                $sql_get_deadline = "SELECT ngayin, han_xuly, so_ngay_xuly, ngay_tinh_han FROM khsanxuat WHERE stt = ?";
                $stmt_get_deadline = $connect->prepare($sql_get_deadline);
                $stmt_get_deadline->bind_param("i", $id);
                $stmt_get_deadline->execute();
                $result_deadline = $stmt_get_deadline->get_result();

                if ($result_deadline->num_rows > 0) {
                    $row_deadline = $result_deadline->fetch_assoc();
                    $han_xuly_chung = $row_deadline['han_xuly'];
                    $so_ngay_xuly_chung = $row_deadline['so_ngay_xuly'] ?? 7;
                    $ngay_tinh_han_chung = $row_deadline['ngay_tinh_han'] ?? 'ngay_vao';
                } else {
                    $han_xuly_chung = date('Y-m-d', strtotime('-7 days'));
                    $so_ngay_xuly_chung = 7;
                    $ngay_tinh_han_chung = 'ngay_vao';
                }

                // Thêm mới bản ghi
                $sql_insert = "INSERT INTO danhgia_tieuchi
                              (id_sanxuat, id_tieuchi, nguoi_thuchien, da_thuchien, diem_danhgia, ghichu, han_xuly, so_ngay_xuly, ngay_tinh_han)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $connect->prepare($sql_insert);
                $stmt_insert->bind_param("iiidssiss", $id, $tieuchi_id, $nguoi_thuchien, $da_thuchien, $diem_danhgia, $ghichu, $han_xuly_chung, $so_ngay_xuly_chung, $ngay_tinh_han_chung);
                $stmt_insert->execute();
            }

            // Cập nhật lại tieuchi_data sau khi lưu
            $tieuchi_data[$tieuchi_id] = [
                'id_tieuchi' => $tieuchi_id,
                'diem_danhgia' => $diem_danhgia,
                'nguoi_thuchien' => $nguoi_thuchien,
                'ghichu' => $ghichu,
                'da_thuchien' => $da_thuchien
            ];

            $connect->commit();

            $message = "Đã lưu điểm đánh giá thành công";
            $message_type = "success";

        } catch (Exception $e) {
            $connect->rollback();
            $message = "Lỗi: " . $e->getMessage();
            $message_type = "error";
        }
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">

    <!-- Thêm CSS Lightbox từ CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/image_handler.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/loading-overlay.css">
</head>
<body>
    <?php
    $header_config = [
        'title' => 'Xử Lý Hình Ảnh',
        'title_short' => 'Hình Ảnh',
        'logo_path' => BASE_URL . '/img/logoht.png',
        'logo_link' => '/trangchu/',
        'show_search' => false,
        'show_mobile_menu' => true,
        'actions' => []
    ];
    ?>
    <?php include BASE_PATH . '/components/header.php'; ?>

    <div class="container">
        <a href="<?php echo BASE_URL; ?>/indexdept.php?dept=<?php echo urlencode($dept); ?>&id=<?php echo $id; ?>" class="back-link">
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

        <div class="card">
            <h2>Upload hình ảnh</h2>
            <form action="" method="post" enctype="multipart/form-data" class="upload-form" data-loading data-loading-text="Đang tải ảnh lên...">
                <div class="form-group">
                    <label for="id_tieuchi" class="form-label">Chọn tiêu chí</label>
                    <select name="id_tieuchi" id="id_tieuchi" class="form-control" required>
                        <option value="">-- Chọn tiêu chí --</option>
                        <?php
                        // Lấy giá trị tiêu chí được truyền qua URL (nếu có)
                        $selected_tieuchi = isset($_GET['tieuchi_id']) ? intval($_GET['tieuchi_id']) : 0;

                        // Tổ chức tiêu chí theo nhóm
                        $tieuchi_nhom = [];
                        foreach ($tieuchi_list as $id_tc => $noidung) {
                            if (strpos($noidung, '[Kho Nguyên Liệu]') === 0) {
                                $tieuchi_nhom['Kho Nguyên Liệu'][$id_tc] = str_replace('[Kho Nguyên Liệu] ', '', $noidung);
                            } elseif (strpos($noidung, '[Kho Phụ Liệu]') === 0) {
                                $tieuchi_nhom['Kho Phụ Liệu'][$id_tc] = str_replace('[Kho Phụ Liệu] ', '', $noidung);
                            } elseif (strpos($noidung, '[Nhóm Nghiệp Vụ]') === 0) {
                                $tieuchi_nhom['Nhóm Nghiệp Vụ'][$id_tc] = str_replace('[Nhóm Nghiệp Vụ] ', '', $noidung);
                            } elseif (strpos($noidung, '[Nhóm May Mẫu]') === 0) {
                                $tieuchi_nhom['Nhóm May Mẫu'][$id_tc] = str_replace('[Nhóm May Mẫu] ', '', $noidung);
                            } elseif (strpos($noidung, '[Nhóm Quy Trình]') === 0) {
                                $tieuchi_nhom['Nhóm Quy Trình'][$id_tc] = str_replace('[Nhóm Quy Trình] ', '', $noidung);
                            } else {
                                $tieuchi_nhom['Khác'][$id_tc] = $noidung;
                            }
                        }

                        // Hiển thị tiêu chí theo nhóm
                        foreach ($tieuchi_nhom as $nhom => $tieuchis) {
                            if ($nhom !== 'Khác' && count($tieuchis) > 0) {
                                echo "<optgroup label=\"{$nhom}\">";
                                foreach ($tieuchis as $id_tc => $noidung) {
                                    $selected = $id_tc == $selected_tieuchi ? 'selected' : '';
                                    echo "<option value=\"{$id_tc}\" {$selected}>" . htmlspecialchars($noidung) . "</option>";
                                }
                                echo "</optgroup>";
                            }
                        }

                        // Hiển thị các tiêu chí khác (nếu có)
                        if (isset($tieuchi_nhom['Khác']) && count($tieuchi_nhom['Khác']) > 0) {
                            if (count($tieuchi_nhom) > 1) {
                                echo "<optgroup label=\"Tiêu chí khác\">";
                            }
                            foreach ($tieuchi_nhom['Khác'] as $id_tc => $noidung) {
                                $selected = $id_tc == $selected_tieuchi ? 'selected' : '';
                                echo "<option value=\"{$id_tc}\" {$selected}>" . htmlspecialchars($noidung) . "</option>";
                            }
                            if (count($tieuchi_nhom) > 1) {
                                echo "</optgroup>";
                            }
                        }
                        ?>
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
            <h2>Đánh giá điểm tiêu chí</h2>
            <form action="" method="post" class="rating-form" data-loading data-loading-text="Đang lưu điểm đánh giá...">
                <div class="form-group">
                    <label for="tieuchi_id" class="form-label">Chọn tiêu chí</label>
                    <select name="tieuchi_id" id="tieuchi_id" class="form-control" required onchange="loadTieuchiData()">
                        <option value="">-- Chọn tiêu chí --</option>
                        <?php
                        // Hiển thị tiêu chí theo nhóm tương tự như dropdown upload
                        foreach ($tieuchi_nhom as $nhom => $tieuchis) {
                            if ($nhom !== 'Khác' && count($tieuchis) > 0) {
                                echo "<optgroup label=\"{$nhom}\">";
                                foreach ($tieuchis as $id_tc => $noidung) {
                                    $selected = $id_tc == $selected_tieuchi ? 'selected' : '';
                                    echo "<option value=\"{$id_tc}\" {$selected} data-has-score=\"" . (isset($tieuchi_data[$id_tc]) ? '1' : '0') . "\">" . htmlspecialchars($noidung) . "</option>";
                                }
                                echo "</optgroup>";
                            }
                        }

                        if (isset($tieuchi_nhom['Khác']) && count($tieuchi_nhom['Khác']) > 0) {
                            if (count($tieuchi_nhom) > 1) {
                                echo "<optgroup label=\"Tiêu chí khác\">";
                            }
                            foreach ($tieuchi_nhom['Khác'] as $id_tc => $noidung) {
                                $selected = $id_tc == $selected_tieuchi ? 'selected' : '';
                                echo "<option value=\"{$id_tc}\" {$selected} data-has-score=\"" . (isset($tieuchi_data[$id_tc]) ? '1' : '0') . "\">" . htmlspecialchars($noidung) . "</option>";
                            }
                            if (count($tieuchi_nhom) > 1) {
                                echo "</optgroup>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nguoi_thuchien" class="form-label">Người chịu trách nhiệm</label>
                    <select name="nguoi_thuchien" id="nguoi_thuchien" class="form-control" required>
                        <option value="">-- Chọn người thực hiện --</option>
                        <?php
                        if (count($nhan_vien) > 0) {
                            foreach ($nhan_vien as $nv) {
                                echo "<option value=\"" . $nv['id'] . "\">" . htmlspecialchars($nv['ten']) . "</option>";
                            }
                        } else {
                            // Dùng danh sách mặc định nếu không có dữ liệu
                            $nguoi_thuchien_default = ($dept == 'kehoach')
                                ? ['Nguyễn Văn A', 'Trần Thị B']
                                : ['Phạm Văn X', 'Lê Thị Y'];

                            foreach ($nguoi_thuchien_default as $nguoi) {
                                echo "<option value=\"" . htmlspecialchars($nguoi) . "\">" . htmlspecialchars($nguoi) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="diem_danhgia" class="form-label">Điểm đánh giá</label>
                    <select name="diem_danhgia" id="diem_danhgia" class="form-control" required>
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="3">3</option>
                    </select>
                    <div id="special_points_container" style="display:none; margin-top: 5px;">
                        <select name="diem_danhgia_special" id="diem_danhgia_special" class="form-control">
                            <option value="0">0</option>
                            <option value="0.5">0.5</option>
                            <option value="1.5">1.5</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ghichu" class="form-label">Ghi chú</label>
                    <textarea name="ghichu" id="ghichu" class="form-control" rows="3"></textarea>
                </div>
                <input type="hidden" name="save_score" value="1">
                <button type="submit" class="btn btn-success" onclick="return prepareSubmit()">Lưu điểm đánh giá</button>
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
                    <a href="<?php echo $image['image_path']; ?>" data-lightbox="gallery"
                       data-title="<strong>Tiêu chí:</strong> <?php echo htmlspecialchars($image['tieuchi_name'] ?? 'Không xác định'); ?><br><strong>Ngày upload:</strong> <?php echo date('d/m/Y H:i', strtotime($image['upload_date'])); ?>">
                        <img src="<?php echo $image['image_path']; ?>" alt="Hình ảnh <?php echo $style; ?>" class="gallery-img">
                    </a>
                    <div class="detail-info" style="padding: 8px; background-color: #f8f9fa; font-size: 12px;">
                        <strong>Tiêu chí:</strong> <?php echo htmlspecialchars($image['tieuchi_name'] ?? 'Không xác định'); ?><br>
                        <strong>Ngày upload:</strong> <?php echo date('d/m/Y H:i', strtotime($image['upload_date'])); ?>
                        <?php if (isset($image['id_tieuchi']) && isset($tieuchi_data[$image['id_tieuchi']])): ?>
                        <br><strong>Điểm đánh giá:</strong> <?php echo $tieuchi_data[$image['id_tieuchi']]['diem_danhgia']; ?>
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo ($tieuchi_data[$image['id_tieuchi']]['diem_danhgia'] > 0) ? '#4caf50' : '#f44336'; ?>; margin-left: 5px;"></span>
                        <?php endif; ?>
                    </div>
                    <div class="overlay">
                        <a href="<?php echo $image['image_path']; ?>" data-lightbox="gallery-list"
                           data-title="<strong>Tiêu chí:</strong> <?php echo htmlspecialchars($image['tieuchi_name'] ?? 'Không xác định'); ?><br><strong>Ngày upload:</strong> <?php echo date('d/m/Y H:i', strtotime($image['upload_date'])); ?>">
                           Xem
                        </a>
                        <a href="<?php echo BASE_URL; ?>/actions/delete_image.php?id_image=<?php echo $image['id']; ?>&id=<?php echo $id; ?>&dept=<?php echo urlencode($dept); ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')">Xóa</a>
                        <?php if (isset($image['id_tieuchi'])): ?>
                        <a href="javascript:void(0)" onclick="editScore(<?php echo $image['id_tieuchi']; ?>)">Đánh giá</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Thêm jQuery từ CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Overlay loading chặn thao tác khi upload/lưu -->
    <?php include BASE_PATH . '/components/loading-overlay.php'; ?>

    <!-- Thêm script Lightbox từ CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

    <script>
    window.IMAGE_HANDLER_BOOTSTRAP = {
        tieuchiData: <?php echo json_encode($tieuchi_data); ?>,
        isKeHoach: <?php echo $dept === 'kehoach' ? 'true' : 'false'; ?>,
        scoreReset: <?php
            if (isset($_GET['score_reset']) && isset($_GET['tieuchi_reset'])) {
                echo json_encode([
                    'tieuchi_reset' => intval($_GET['tieuchi_reset']),
                    'id' => $id,
                    'dept' => $dept,
                ]);
            } else {
                echo 'null';
            }
        ?>
    };
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/image_handler.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/loading-overlay.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/header.js"></script>
</body>
</html>
