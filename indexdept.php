<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/bootstrap.php';
require_once 'includes/security/csrf-helper.php';
require_once 'includes/indexdept/config.php';
require_once 'includes/indexdept/functions.php';
include BASE_PATH . '/includes/check_tieuchi_image.php';
require_once 'includes/indexdept/score-options.php';

if (!$connect) {
    error_log("indexdept.php: DB connection failed");
    die("Lỗi hệ thống. Vui lòng thử lại sau.");
}

$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!in_array($dept, getValidDepts())) {
    header('Location: index.php');
    exit;
}
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$is_admin = isset($_SESSION['username']) && $_SESSION['username'] === 'admin';
$dept_display_name = getDeptDisplayName($dept, $dept_names);
$image_count = getImageCount($connect, $id, $dept);

try {
    $sql = "SELECT line1, xuong, po, style, qty, ngayin, ngayout, han_xuly, so_ngay_xuly, ngay_tinh_han FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Không tìm thấy dữ liệu");
    }

    $row = $result->fetch_assoc();

    $line = $row['line1'];
    $xuong = $row['xuong'];
    $po = $row['po'];
    $style = $row['style'];
    $qty = $row['qty'];

    $ngayin = new DateTime($row['ngayin']);
    $ngayout = new DateTime($row['ngayout']);
    $ngayin_formatted = $ngayin->format('d/m/Y');
    $ngayout_formatted = $ngayout->format('d/m/Y');

    $so_ngay_xuly = isset($row['so_ngay_xuly']) ? intval($row['so_ngay_xuly']) : 7;

    if (isset($row['han_xuly']) && !empty($row['han_xuly'])) {
        $han_xuly = new DateTime($row['han_xuly']);
        $han_xuly_formatted = $han_xuly->format('d/m/Y');
    } else {
        $han_xuly = calculateDeadline(
            $ngayin,
            $ngayout,
            isset($row['ngay_tinh_han']) ? $row['ngay_tinh_han'] : 'ngay_vao',
            $so_ngay_xuly
        );
        $han_xuly_formatted = $han_xuly->format('d/m/Y');
    }

    // Ngày kế hoạch: kehoach trước 7 ngày, kho trước 14 ngày so với ngày vào
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
    error_log("indexdept.php query error: " . $e->getMessage());
    die("Lỗi hệ thống. Vui lòng thử lại sau.");
}

// Xử lý POST: kiểm tra ảnh bắt buộc trước khi cho cập nhật điểm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_diem'])) {
    $required_criteria = getRequiredImagesCriteria($connect, $dept);

    foreach ($required_criteria as $tieuchi_id) {
        if (isset($_POST['diem'][$tieuchi_id]) && $_POST['diem'][$tieuchi_id] > 0) {
            if (!checkTieuchiHasImage($connect, $id, $tieuchi_id)) {
                $sql_tieuchi = "SELECT thutu, noidung FROM tieuchi_dept WHERE id = ? AND dept = ?";
                $stmt = $connect->prepare($sql_tieuchi);
                $stmt->bind_param("is", $tieuchi_id, $dept);
                $stmt->execute();
                $tieuchi_info = $stmt->get_result()->fetch_assoc();

                $error_message = "Bạn cần đính kèm ảnh cho tiêu chí số " . htmlspecialchars($tieuchi_info['thutu'], ENT_QUOTES, 'UTF-8') .
                               " (" . htmlspecialchars($tieuchi_info['noidung'], ENT_QUOTES, 'UTF-8') . ") trước khi cập nhật điểm đánh giá. " .
                               "<a href='" . BASE_URL . "/pages/image_handler.php?dept=" . urlencode($dept) . "&id=" . intval($id) . "'>Upload hình ảnh</a>";

                unset($_POST['diem'][$tieuchi_id]);
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save"])) {
    $dept = isset($_GET['dept']) ? $_GET['dept'] : (isset($_POST['dept']) ? $_POST['dept'] : 'unknown');
    error_log("indexdept.php: Dept value being sent to save_danhgia_with_log: " . $dept);
}

$missing_files = checkMissingFiles($required_settings_files);
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
    <link rel="stylesheet" href="assets/css/indexdept/indexdept.css?v=<?php echo filemtime('assets/css/indexdept/indexdept.css'); ?>">
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

<?php displayMessages(); ?>
<?php displayMissingFilesWarning($missing_files); ?>

<?php
$dept_js_arg = json_encode((string)$dept, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$xuong_js_arg = json_encode((string)$xuong, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

$header_config = [
    'title' => $dept_display_name,
    'title_short' => 'BỘ PHẬN',
    'logo_path' => 'img/logoht.png',
    'logo_link' => '/trangchu/',
    'show_search' => false,
    'show_mobile_menu' => true,
    'actions' => [],
    'mobile_actions' => [
        [
            'url' => BASE_URL . '/index.php',
            'icon' => 'img/back.png',
            'title' => 'Quay lại',
            'tooltip' => 'Quay lại danh sách'
        ],
        [
            'url' => BASE_URL . '/pages/image_handler.php?id=' . urlencode((string)$id) . '&dept=' . urlencode((string)$dept),
            'icon' => 'img/open.png',
            'title' => 'Hình ảnh',
            'tooltip' => 'Quản lý hình ảnh'
        ],
        [
            'url' => BASE_URL . '/pages/file_templates.php?id=' . urlencode((string)$id) . '&dept=' . urlencode((string)$dept),
            'icon' => 'img/doc.gif',
            'title' => 'Biểu mẫu',
            'tooltip' => 'Biểu mẫu'
        ],
        [
            'title' => 'Thêm tiêu chí',
            'icon_class' => 'fas fa-plus',
            'onclick' => 'openModal()'
        ],
        [
            'title' => 'Cài đặt mặc định',
            'icon_class' => 'fas fa-cog',
            'onclick' => 'openDefaultSettingModal()'
        ],
        [
            'title' => 'Cài mốc điểm',
            'icon_class' => 'fas fa-sliders-h',
            'onclick' => 'openScoreOptionsModal()'
        ],
        [
            'title' => 'Áp dụng giá trị mặc định',
            'icon_class' => 'fas fa-sync-alt',
            'onclick' => 'syncTieuChiWithDefaultSettings(' . $dept_js_arg . ', ' . $xuong_js_arg . ')',
            'class' => 'mobile-nav-item--warning'
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
<script src="assets/js/indexdept/indexdept.js?v=<?php echo filemtime('assets/js/indexdept/indexdept.js'); ?>"></script>
<script src="assets/js/indexdept/owner-multiselect.js?v=<?php echo filemtime('assets/js/indexdept/owner-multiselect.js'); ?>"></script>
<script src="assets/js/header.js?v=<?php echo filemtime('assets/js/header.js'); ?>"></script>
<?php include 'components/back-to-top.php'; ?>
</body>
</html>
