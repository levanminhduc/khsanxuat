<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Thiết lập đường dẫn tới file log
ini_set('error_log', 'C:/xampp/php/logs/php_log.txt');

// Kết nối database
include 'db_connect.php';

// CSRF protection
require_once 'includes/security/csrf-helper.php';

// Thêm vào sau phần kết nối database
include 'check_tieuchi_image.php';
require_once 'includes/indexdept/score-options.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database");
}

// Khởi tạo phiên làm việc nếu chưa có
session_start();

// Lấy thông tin từ URL
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Tạm thời bỏ kiểm tra user
$is_admin = isset($_SESSION['username']) && $_SESSION['username'] === 'admin';

// Ánh xạ tên hiển thị cho từng bộ phận
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

// Lấy tên hiển thị của bộ phận
$dept_display_name = isset($dept_names[$dept]) ? $dept_names[$dept] : 'KHÔNG XÁC ĐỊNH';

// Thêm đoạn code kiểm tra số lượng ảnh trước phần hiển thị bảng
$sql_count_images = "SELECT COUNT(*) as image_count FROM khsanxuat_images WHERE id_khsanxuat = ? AND dept = ?";
$stmt_count = $connect->prepare($sql_count_images);
$stmt_count->bind_param("is", $id, $dept);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$image_count = $result_count->fetch_assoc()['image_count'];

try {
    // Query để lấy dữ liệu từ database dựa trên STT
    $sql = "SELECT line1, xuong, po, style, qty, ngayin, ngayout, han_xuly, so_ngay_xuly FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Không tìm thấy dữ liệu");
    }

    $row = $result->fetch_assoc();

    // Gán giá trị từ database
    $line = $row['line1'];
    $xuong = $row['xuong'];
    $po = $row['po'];
    $style = $row['style'];
    $qty = $row['qty'];

    // Xử lý định dạng ngày
    $ngayin = new DateTime($row['ngayin']);
    $ngayout = new DateTime($row['ngayout']);
    $ngayin_formatted = $ngayin->format('d/m/Y');
    $ngayout_formatted = $ngayout->format('d/m/Y');

    // Lấy số ngày xử lý từ database hoặc mặc định
    $so_ngay_xuly = isset($row['so_ngay_xuly']) ? intval($row['so_ngay_xuly']) : 7;

    // Tính hạn xử lý
    if (isset($row['han_xuly']) && !empty($row['han_xuly'])) {
        $han_xuly = new DateTime($row['han_xuly']);
        $han_xuly_formatted = $han_xuly->format('d/m/Y');
    } else {
        // Kiểm tra phương thức tính hạn xử lý
        if (isset($row['ngay_tinh_han']) && $row['ngay_tinh_han'] == 'ngay_ra') {
            $han_xuly = clone $ngayout;
            $han_xuly->modify("+{$so_ngay_xuly} days");
        } elseif (isset($row['ngay_tinh_han']) && $row['ngay_tinh_han'] == 'ngay_ra_tru') {
            // Trường hợp mới: Ngày ra - Số ngày nhập
            $han_xuly = clone $ngayout;
            $han_xuly->modify("-{$so_ngay_xuly} days");
        } elseif (isset($row['ngay_tinh_han']) && $row['ngay_tinh_han'] == 'ngay_vao_cong') {
            // Trường hợp mới: Ngày vào + Số ngày nhập
            $han_xuly = clone $ngayin;
            $han_xuly->modify("+{$so_ngay_xuly} days");
        } else {
            $han_xuly = clone $ngayin;
            $han_xuly->modify("-{$so_ngay_xuly} days");
        }
        $han_xuly_formatted = $han_xuly->format('d/m/Y');
    }

    // Xử lý ngày kế hoạch nếu là bộ phận kế hoạch
    if ($dept == 'kehoach') {
        $plan_date = clone $ngayin;
        $plan_date->modify('-7 days');
        $plan_date_formatted = $plan_date->format('d/m/Y');
    }
    if ($dept == 'kho') {
        $plan_date = clone $ngayin;
        $plan_date->modify('-14 days');
        $khochuanbi_formatted = $plan_date->format('d/m/Y');
    }
} catch (Exception $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Hiển thị thông báo thành công/lỗi nếu có
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'updated') {
        echo '<div class="success-message">Cập nhật hạn xử lý thành công!</div>';
    } elseif ($_GET['success'] === 'updated_deadline') {
        echo '<div class="success-message">Cập nhật hạn xử lý cho tiêu chí thành công!</div>';
    } else {
        echo '<div class="success-message">Lưu đánh giá thành công!</div>';
    }
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'not_authorized') {
        echo '<div class="error-message">Bạn không có quyền thực hiện thao tác này!</div>';
    } elseif ($_GET['error'] === 'missing_data') {
        echo '<div class="error-message">Thiếu dữ liệu cần thiết!</div>';
    } elseif ($_GET['error'] === 'record_not_found') {
        echo '<div class="error-message">Không tìm thấy bản ghi!</div>';
    } elseif ($_GET['error'] === 'not_updated') {
        echo '<div class="error-message">Cập nhật không thành công!</div>';
    } else {
        echo '<div class="error-message">Có lỗi xảy ra: ' . htmlspecialchars($_GET['error']) . '</div>';
    }
}

// Thêm kiểm tra tồn tại của các file xử lý
$required_files = [
    'save_default_setting.php',
    'save_all_default_settings.php',
    'apply_default_settings.php'
];

$missing_files = [];
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $missing_files[] = $file;
    }
}

if (!empty($missing_files)) {
    echo '<div class="system-message system-message--error">';
    echo '<strong>Cảnh báo:</strong> Không tìm thấy các file sau: ' . implode(', ', $missing_files);
    echo '</div>';
}

// Kiểm tra bảng default_settings
$check_table = $connect->query("SHOW TABLES LIKE 'default_settings'");
if ($check_table->num_rows === 0) {
    // Tạo bảng default_settings
    $sql_create_table = "CREATE TABLE IF NOT EXISTS default_settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        dept VARCHAR(50) NOT NULL,
        xuong VARCHAR(50) NOT NULL DEFAULT '',
        id_tieuchi INT(11) NOT NULL,
        ngay_tinh_han VARCHAR(30) NOT NULL DEFAULT 'ngay_vao',
        so_ngay_xuly INT(11) NOT NULL DEFAULT 7,
        nguoi_chiu_trachnhiem_default INT(11) NULL,
        ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
        ngay_capnhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_dept_tieuchi_xuong (dept, id_tieuchi, xuong)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    if (!$connect->query($sql_create_table)) {
        echo '<div class="system-message system-message--error">';
        echo '<strong>Lỗi:</strong> Không thể tạo bảng default_settings: ' . $connect->error;
        echo '</div>';
    }
}

// Kiểm tra nếu cần thêm cột xuong vào bảng default_settings
$check_xuong_column = $connect->query("SHOW COLUMNS FROM default_settings LIKE 'xuong'");
if ($check_xuong_column->num_rows === 0) {
    // Thêm cột xuong vào bảng default_settings
    $sql_add_xuong = "ALTER TABLE default_settings ADD COLUMN xuong VARCHAR(50) NOT NULL DEFAULT '' AFTER dept";

    if (!$connect->query($sql_add_xuong)) {
        echo '<div class="system-message system-message--error">';
        echo '<strong>Lỗi:</strong> Không thể thêm cột xuong vào bảng default_settings: ' . $connect->error;
        echo '</div>';
    }
}

// Kiểm tra và cập nhật ràng buộc UNIQUE
$check_index = $connect->query("SHOW INDEXES FROM default_settings WHERE Key_name = 'unique_dept_tieuchi'");
if ($check_index->num_rows > 0) {
    // Xóa ràng buộc UNIQUE cũ
    $sql_drop_unique = "ALTER TABLE default_settings DROP INDEX unique_dept_tieuchi";
    if (!$connect->query($sql_drop_unique)) {
        echo '<div class="system-message system-message--error">';
        echo '<strong>Lỗi:</strong> Không thể xóa ràng buộc UNIQUE cũ: ' . $connect->error;
        echo '</div>';
    }
}

// Kiểm tra xem ràng buộc mới đã tồn tại chưa
$check_new_index = $connect->query("SHOW INDEXES FROM default_settings WHERE Key_name = 'unique_dept_tieuchi_xuong'");
if ($check_new_index->num_rows === 0) {
    // Thêm ràng buộc UNIQUE mới
    $sql_add_unique = "ALTER TABLE default_settings ADD UNIQUE KEY unique_dept_tieuchi_xuong (dept, id_tieuchi, xuong)";
    if (!$connect->query($sql_add_unique)) {
        echo '<div class="system-message system-message--error">';
        echo '<strong>Lỗi:</strong> Không thể thêm ràng buộc UNIQUE mới: ' . $connect->error;
        echo '</div>';
    }
}

// Kiểm tra nếu cần thêm cột nguoi_chiu_trachnhiem_default vào bảng default_settings
$check_nguoi_column = $connect->query("SHOW COLUMNS FROM default_settings LIKE 'nguoi_chiu_trachnhiem_default'");
if ($check_nguoi_column->num_rows === 0) {
    // Thêm cột nguoi_chiu_trachnhiem_default vào bảng default_settings
    $sql_add_nguoi = "ALTER TABLE default_settings ADD COLUMN nguoi_chiu_trachnhiem_default INT(11) NULL AFTER so_ngay_xuly";

    if (!$connect->query($sql_add_nguoi)) {
        echo '<div class="system-message system-message--error">';
        echo '<strong>Lỗi:</strong> Không thể thêm cột nguoi_chiu_trachnhiem_default vào bảng default_settings: ' . $connect->error;
        echo '</div>';
    }
}

// Kiểm tra cột ngay_tinh_han trong bảng danhgia_tieuchi
$check_column = $connect->query("SHOW COLUMNS FROM danhgia_tieuchi LIKE 'ngay_tinh_han'");
if ($check_column->num_rows === 0) {
    // Thêm cột ngay_tinh_han vào bảng danhgia_tieuchi
    $sql_add_column = "ALTER TABLE danhgia_tieuchi ADD COLUMN ngay_tinh_han VARCHAR(20) DEFAULT 'ngay_vao' AFTER so_ngay_xuly";

    if (!$connect->query($sql_add_column)) {
        echo '<div class="system-message system-message--error">';
        echo '<strong>Lỗi:</strong> Không thể thêm cột ngay_tinh_han vào bảng danhgia_tieuchi: ' . $connect->error;
        echo '</div>';
    }
}

// Thêm vào phần xử lý form đánh giá, trước khi lưu dữ liệu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_diem'])) {
    // Lấy danh sách tiêu chí bắt buộc hình ảnh
    $required_criteria = getRequiredImagesCriteria($connect, $dept);

    foreach ($required_criteria as $tieuchi_id) {
        if (isset($_POST['diem'][$tieuchi_id]) && $_POST['diem'][$tieuchi_id] > 0) {
            // Kiểm tra xem đã có hình ảnh cho tiêu chí này chưa
            if (!checkTieuchiHasImage($connect, $id, $tieuchi_id)) {
                // Lấy thông tin tiêu chí
                $sql_tieuchi = "SELECT thutu, noidung FROM tieuchi_dept WHERE id = ? AND dept = ?";
                $stmt = $connect->prepare($sql_tieuchi);
                $stmt->bind_param("is", $tieuchi_id, $dept);
                $stmt->execute();
                $tieuchi_info = $stmt->get_result()->fetch_assoc();

                // Nếu chưa có hình ảnh, hiển thị thông báo lỗi
                $error_message = "Bạn cần đính kèm ảnh cho tiêu chí số " . $tieuchi_info['thutu'] .
                               " (" . $tieuchi_info['noidung'] . ") trước khi cập nhật điểm đánh giá. " .
                               "<a href='image_handler.php?dept=" . $dept . "&id=" . $id . "'>Upload hình ảnh</a>";

                // Bỏ qua giá trị điểm cho tiêu chí này để không lưu vào database
                unset($_POST['diem'][$tieuchi_id]);
            }
        }
    }
}

// Trong phần xử lý POST request để lưu đánh giá
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save"])) {
    // Log thông tin dept được gửi đi
    $dept = isset($_GET['dept']) ? $_GET['dept'] : (isset($_POST['dept']) ? $_POST['dept'] : 'unknown');
    error_log("indexdept.php: Dept value being sent to save_danhgia_with_log: " . $dept);

    // ... existing code ...
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kế Hoạch Rải Chuyền</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/header.css?v=<?php echo filemtime('assets/css/header.css'); ?>">
    <link rel="stylesheet" href="assets/css/indexdept/base.css?v=<?php echo filemtime('assets/css/indexdept/base.css'); ?>">
    <link rel="stylesheet" href="assets/css/indexdept/layout.css?v=<?php echo filemtime('assets/css/indexdept/layout.css'); ?>">
    <link rel="stylesheet" href="assets/css/indexdept/components.css?v=<?php echo filemtime('assets/css/indexdept/components.css'); ?>">
    <link rel="stylesheet" href="assets/css/indexdept/responsive.css?v=<?php echo filemtime('assets/css/indexdept/responsive.css'); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            prefix: 'tw-',
            corePlugins: {
                preflight: false
            },
            theme: {
                extend: {
                    boxShadow: {
                        'modal-soft': '0 26px 80px rgba(15, 23, 42, 0.28)'
                    }
                }
            }
        };
    </script>
</head>
<body>
    <!-- Thanh điều hướng -->
<?php
$header_config = [
    'title' => isset($dept_display_name) ? $dept_display_name : 'Chi tiết Bộ phận',
    'title_short' => 'Bộ phận',
    'logo_path' => 'img/logoht.png',
    'logo_link' => '/trangchu/',
    'show_search' => false,
    'show_mobile_menu' => true,
    'actions' => [
        [
            'url' => 'index.php',
            'icon' => 'img/back.png',
            'title' => 'Quay lại',
            'tooltip' => 'Quay lại danh sách'
        ],
        [
            'url' => 'image_handler.php?id=' . urlencode((string)$id) . '&dept=' . urlencode((string)$dept),
            'icon' => 'img/open.png',
            'title' => 'Hình ảnh',
            'tooltip' => 'Quản lý hình ảnh'
        ],
        [
            'url' => 'file_templates.php?id=' . urlencode((string)$id) . '&dept=' . urlencode((string)$dept),
            'icon' => 'img/doc.gif',
            'title' => 'Biểu mẫu',
            'tooltip' => 'Biểu mẫu'
        ]
    ]
];
?>
<?php include 'components/header.php'; ?>

    <?php include 'views/indexdept/page.php'; ?>

<script>
window.INDEXDEPT_BOOTSTRAP = {
    id: <?php echo json_encode((int) $id); ?>,
    dept: <?php echo json_encode($dept); ?>,
    xuong: <?php echo json_encode($xuong); ?>,
    maxPossiblePoints: <?php echo json_encode((float) $max_possible_points); ?>,
    completedTieuchi: <?php echo json_encode((int) $completed_tieuchi); ?>,
    totalTieuchi: <?php echo json_encode((int) $total_tieuchi); ?>,
    requiredCriteria: <?php echo json_encode(getRequiredImagesCriteria($connect, $dept)); ?>,
    autoSelectImage: <?php echo json_encode(isset($_REQUEST['autoselect_image']) && $_REQUEST['autoselect_image'] == 1); ?>,
    autoSelectTieuchiId: <?php echo json_encode(isset($_REQUEST['tieuchi_id']) ? (int) $_REQUEST['tieuchi_id'] : null); ?>
};
</script>
<script src="assets/js/indexdept/indexdept.js"></script>
<script src="assets/js/header.js?v=<?php echo filemtime('assets/js/header.js'); ?>"></script>
</body>
</html>
