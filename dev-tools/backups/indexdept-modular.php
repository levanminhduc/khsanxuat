<?php
/**
 * IndexDept - Department Detail Page (Modular Version)
 * Displays evaluation criteria and settings for a specific department
 *
 * This is a modular refactored version of indexdept.php
 * Test this file before replacing the original
 */

// ===========================================
// 1. Environment & Error Handling
// ===========================================
if (getenv('APP_ENV') === 'development' || !getenv('APP_ENV')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ===========================================
// 2. Includes
// ===========================================
require_once 'db_connect.php';
require_once 'check_tieuchi_image.php';
require_once 'includes/indexdept/config.php';
require_once 'includes/indexdept/functions.php';

// ===========================================
// 3. Database Connection Check
// ===========================================
if (!$connect) {
    die("Lỗi kết nối database");
}

// ===========================================
// 4. Session & Auth
// ===========================================
session_start();

// TODO: Replace with proper authentication
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// ===========================================
// 5. Request Parameters & Validation
// ===========================================
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$allowed_depts = array_keys($dept_names);
if (!in_array($dept, $allowed_depts)) {
    die("Bộ phận không hợp lệ");
}

if ($id <= 0) {
    die("ID không hợp lệ");
}

// ===========================================
// 6. Data Loading
// ===========================================
$dept_display_name = getDeptDisplayName($dept, $dept_names);
$image_count = getImageCount($connect, $id, $dept);
$file_count = getFileCount($connect, $id, $dept);
$product = getProductData($connect, $id);

if (!$product) {
    die("Không tìm thấy dữ liệu");
}

// Extract product data
$line = $product['line1'];
$xuong = $product['xuong'];
$po = $product['po'];
$style = $product['style'];
$qty = $product['qty'];

// Process dates
$ngayin = new DateTime($product['ngayin']);
$ngayout = new DateTime($product['ngayout']);
$ngayin_formatted = $ngayin->format('d/m/Y');
$ngayout_formatted = $ngayout->format('d/m/Y');

// Calculate deadline
$so_ngay_xuly = intval($product['so_ngay_xuly'] ?? 7);
$ngay_tinh_han = $product['ngay_tinh_han'] ?? 'ngay_vao';

if (!empty($product['han_xuly'])) {
    $han_xuly = new DateTime($product['han_xuly']);
    $han_xuly_formatted = $han_xuly->format('d/m/Y');
} else {
    $han_xuly = calculateDeadline($ngayin, $ngayout, $ngay_tinh_han, $so_ngay_xuly);
    $han_xuly_formatted = $han_xuly->format('d/m/Y');
}

// Department-specific dates
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

// ===========================================
// 7. Display Messages & Warnings
// ===========================================
displayMessages();
$missing_files = checkMissingFiles($required_settings_files);
displayMissingFilesWarning($missing_files);

// ===========================================
// 8. Ensure default_settings table exists
// ===========================================
$check_table = $connect->query("SHOW TABLES LIKE 'default_settings'");
if ($check_table->num_rows === 0) {
    $sql_create = "CREATE TABLE IF NOT EXISTS default_settings (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $connect->query($sql_create);
}

// ===========================================
// 9. Load evaluation criteria
// ===========================================
$criteria_result = getEvaluationCriteria($connect, $id, $dept);

// Calculate totals
$total_tieuchi = 0;
$completed_tieuchi = 0;
$total_points = 0;
$max_possible_points = 0;
$criteria_data = [];

while ($row = $criteria_result->fetch_assoc()) {
    $criteria_data[] = $row;
    $total_tieuchi++;
    if ($row['da_thuchien'] == 1) {
        $completed_tieuchi++;
    }
    $diem = floatval($row['diem_danhgia'] ?? 0);
    $total_points += $diem;

    // Calculate max points
    if ($dept == 'kehoach' && ($row['thutu'] == 7 || $row['thutu'] == 8)) {
        $max_possible_points += 1.5;
    } else {
        $max_possible_points += 3;
    }
}

// Get required image criteria
$required_image_criteria = [];
if (function_exists('getRequiredImagesCriteria')) {
    $required_image_criteria = getRequiredImagesCriteria($connect, $dept);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kế Hoạch Rải Chuyền - <?php echo $dept_display_name; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <script src="js/indexdept.js" defer></script>
</head>
<body>
    <?php
    $header_config = [
        'title' => 'Kế Hoạch Rải Chuyền - ' . $dept_display_name,
        'title_short' => $dept_display_name,
        'show_search' => false,
        'show_mobile_menu' => true
    ];
    include 'components/header.php';
    ?>

    <div class="container" style="max-width: 1600px; margin: 20px auto; padding: 20px;">
        <div class="action-buttons" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Thông tin chi tiết - <?php echo $dept_display_name; ?></h2>
            <div>
                <button type="button" class="btn-add-criteria" onclick="openModal()">
                    <i class="fas fa-plus"></i> Thêm tiêu chí
                </button>
                <button type="button" class="btn-add-criteria" onclick="openDefaultSettingModal()">
                    <i class="fas fa-cog"></i> Cài đặt mặc định
                </button>
                <button type="button" class="btn-add-criteria" onclick="syncTieuChiWithDefaultSettings('<?php echo $dept; ?>', '<?php echo $xuong; ?>')" style="background-color: #ffc107; color: #212529;">
                    <i class="fas fa-sync"></i> Áp dụng giá trị mặc định
                </button>
                <a href="index.php" class="btn-back" style="text-decoration: none; margin-left: 10px;">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>

        <!-- Product Info Table -->
        <table class="data-table" style="width: 100%; margin-bottom: 20px; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Xưởng</th>
                    <th>Line</th>
                    <th>PO</th>
                    <th>Style</th>
                    <th>Số lượng</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Xử Lý Hình Ảnh</th>
                    <th>Hồ Sơ SA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($xuong); ?></td>
                    <td><?php echo htmlspecialchars($line); ?></td>
                    <td><?php echo htmlspecialchars($po); ?></td>
                    <td><?php echo htmlspecialchars($style); ?></td>
                    <td><?php echo htmlspecialchars($qty); ?></td>
                    <td><?php echo $ngayin_formatted; ?></td>
                    <td><?php echo $ngayout_formatted; ?></td>
                    <td>
                        <a href="image_handler.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>" class="btn-upload-image">
                            <?php if ($image_count > 0) : ?>
                            <span class="image-count-badge"><?php echo $image_count; ?></span>
                            <?php endif; ?>
                            Xử lý hình ảnh
                        </a>
                    </td>
                    <td>
                        <a href="file_templates.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>" class="btn-upload-file">
                            <?php if ($file_count > 0) : ?>
                            <span class="file-count-badge"><?php echo $file_count; ?></span>
                            <?php endif; ?>
                            Update File
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Include modals -->
        <?php include 'components/indexdept/modal-add-criteria.php'; ?>
        <?php include 'components/indexdept/modal-deadline-settings.php'; ?>
        <?php include 'components/indexdept/modal-default-settings.php'; ?>

        <!-- Evaluation Form -->
        <div class="evaluation-section" style="max-width: 1600px; margin: 0 auto;">
            <!-- Sticky header + progress bar -->
            <div class="progress-header" style="position: sticky; top: 0; z-index: 20; background-color: white; padding: 10px 0; border-bottom: 1px solid #ddd;">
                <h2 style="margin: 0 0 10px 0;">Tiêu chí đánh giá</h2>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span id="progress_text">Tiến độ: <?php echo $completed_tieuchi; ?>/<?php echo $total_tieuchi; ?> tiêu chí</span>
                    <span id="total_points"><?php echo number_format($total_points, 1); ?>/<?php echo number_format($max_possible_points, 1); ?></span>
                </div>
                <div class="progress" style="height: 20px; background-color: #e9ecef; border-radius: 4px; overflow: hidden;">
                    <?php $percent = ($max_possible_points > 0) ? ($total_points / $max_possible_points) * 100 : 0; ?>
                    <div class="progress-bar" style="width: <?php echo $percent; ?>%; background-color: <?php echo ($percent < 30) ? '#F44336' : (($percent < 70) ? '#FFC107' : '#4CAF50'); ?>; height: 100%; text-align: center; color: white; line-height: 20px;">
                        <?php echo round($percent); ?>%
                    </div>
                </div>
            </div>

            <div style="overflow-x: auto;">

            <form action="save_danhgia_with_log.php" method="POST" id="danhgiaForm">
                <input type="hidden" name="id_sanxuat" value="<?php echo $id; ?>">
                <input type="hidden" name="dept" value="<?php echo $dept; ?>">

                <table class="evaluation-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #003366; color: white;">
                            <th style="width: 40px;">STT</th>
                            <th style="width: 360px;">Tiêu chí đánh giá</th>
                            <th style="width: 130px;">
                                Hạn Xử Lý
                                <button type="button" onclick="openDeadlineModal()" class="small-btn" style="margin-left: 5px;">Cài đặt</button>
                            </th>
                            <th style="width: 200px;">Người chịu trách nhiệm</th>
                            <th style="width: 120px;">Điểm đánh giá</th>
                            <th style="width: 80px;">Đã thực hiện</th>
                            <th style="width: 150px;">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $current_nhom = '';
                        foreach ($criteria_data as $row) :
                            // Display group header
                            if ($current_nhom != $row['nhom']) :
                                $current_nhom = $row['nhom'];
                                $nhom_display = getNhomDisplayName($dept, $row['nhom']);
                                if ($nhom_display) :
                        ?>
                        <tr>
                            <td colspan="7" style="background-color: #f3f4f6; color: #1e40af; font-weight: bold; text-align: left; padding: 10px;">
                                <?php echo $nhom_display; ?>
                            </td>
                        </tr>
                        <?php
                                endif;
                            endif;

                            // Calculate deadline for this criteria
                            if (!empty($row['han_xuly'])) {
                                $han_tc = new DateTime($row['han_xuly']);
                                $han_tc_formatted = $han_tc->format('d/m/Y');
                                $tc_so_ngay_xuly = intval($row['so_ngay_xuly'] ?? $so_ngay_xuly);
                                $tc_ngay_tinh_han = $row['ngay_tinh_han'] ?? 'ngay_vao';
                            } else {
                                $han_tc = clone $han_xuly;
                                $han_tc_formatted = $han_xuly_formatted;
                                $tc_so_ngay_xuly = $so_ngay_xuly;
                                $tc_ngay_tinh_han = $ngay_tinh_han;
                            }

                            // Check overdue status
                            $now = new DateTime();
                            $is_overdue = ($han_tc < $now && (!isset($row['diem_danhgia']) || $row['diem_danhgia'] == 0));
                            $deadline_class = $is_overdue ? 'overdue' : '';

                            $diem_danhgia = floatval($row['diem_danhgia'] ?? 0);
                            $da_thuchien = intval($row['da_thuchien'] ?? 0);
                        ?>
                        <tr>
                            <td><?php echo $row['thutu']; ?></td>
                            <td class="text-left"><?php echo htmlspecialchars($row['noidung']); ?></td>
                            <td class="deadline-info">
                                <span class="deadline-date <?php echo $deadline_class; ?>" id="date_display_<?php echo $row['id']; ?>"><?php echo $han_tc_formatted; ?></span>
                                <div class="deadline-form">
                                    <div style="display: flex; align-items: center;">
                                        <input type="number" id="so_ngay_xuly_<?php echo $row['id']; ?>" value="<?php echo $tc_so_ngay_xuly; ?>" min="1" max="30" class="deadline-input">
                                        <button type="button" onclick="updateDeadline(<?php echo $id; ?>, <?php echo $row['id']; ?>, '<?php echo $dept; ?>')" class="deadline-button">Cập nhật</button>
                                    </div>
                                    <div style="display: flex; align-items: center; margin-top: 3px;">
                                        <select id="ngay_tinh_han_<?php echo $row['id']; ?>" class="ngay-tinh-han-select">
                                            <option value="ngay_vao" <?php echo ($tc_ngay_tinh_han == 'ngay_vao') ? 'selected' : ''; ?>>Ngày vào trừ số ngày</option>
                                            <option value="ngay_vao_cong" <?php echo ($tc_ngay_tinh_han == 'ngay_vao_cong') ? 'selected' : ''; ?>>Ngày vào cộng số ngày</option>
                                            <option value="ngay_ra" <?php echo ($tc_ngay_tinh_han == 'ngay_ra') ? 'selected' : ''; ?>>Ngày ra cộng số ngày</option>
                                            <option value="ngay_ra_tru" <?php echo ($tc_ngay_tinh_han == 'ngay_ra_tru') ? 'selected' : ''; ?>>Ngày ra trừ số ngày</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="hidden" name="old_nguoi_thuchien_<?php echo $row['id']; ?>" value="<?php echo $row['nguoi_thuchien'] ?? ''; ?>">
                                <select name="nguoi_thuchien_<?php echo $row['id']; ?>" required class="nguoi-thuchien-select" style="height: 40px; white-space: normal;">
                                    <?php
                                    $staff_list = getStaffByDept($connect, $dept);
                                    if ($staff_list->num_rows > 0) {
                                        while ($staff = $staff_list->fetch_assoc()) {
                                            $selected = ($row['nguoi_thuchien'] == $staff['id']) ? 'selected' : '';
                                            echo "<option value='" . $staff['id'] . "' $selected>" . htmlspecialchars($staff['ten']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="old_diem_<?php echo $row['id']; ?>" value="<?php echo $diem_danhgia; ?>">
                                <select name="diem_danhgia_<?php echo $row['id']; ?>" class="diem-dropdown" data-tieuchi-id="<?php echo $row['id']; ?>" onchange="updateStatus(this)">
                                    <?php if ($dept == 'kehoach' && ($row['thutu'] == 7 || $row['thutu'] == 8)) : ?>
                                        <option value="0" <?php echo ($diem_danhgia == 0) ? 'selected' : ''; ?>>0</option>
                                        <option value="0.5" <?php echo ($diem_danhgia == 0.5) ? 'selected' : ''; ?>>0.5</option>
                                        <option value="1.5" <?php echo ($diem_danhgia == 1.5) ? 'selected' : ''; ?>>1.5</option>
                                    <?php else : ?>
                                        <option value="0" <?php echo ($diem_danhgia == 0) ? 'selected' : ''; ?>>0</option>
                                        <option value="1" <?php echo ($diem_danhgia == 1) ? 'selected' : ''; ?>>1</option>
                                        <option value="3" <?php echo ($diem_danhgia == 3) ? 'selected' : ''; ?>>3</option>
                                    <?php endif; ?>
                                </select>
                            </td>
                            <td>
                                <input type="checkbox" class="checkbox-input" id="checkbox_<?php echo $row['id']; ?>" data-tieuchi-id="<?php echo $row['id']; ?>" <?php echo ($diem_danhgia > 0) ? 'checked' : ''; ?> disabled>
                                <label class="checkbox <?php echo ($diem_danhgia > 0) ? 'checked' : 'unchecked'; ?>" for="checkbox_<?php echo $row['id']; ?>" id="checkbox_label_<?php echo $row['id']; ?>">
                                    <span class="checkmark"><?php echo ($diem_danhgia > 0) ? '✓' : 'X'; ?></span>
                                </label>
                                <input type="hidden" name="da_thuchien_<?php echo $row['id']; ?>" value="<?php echo ($diem_danhgia > 0) ? 1 : 0; ?>" id="da_thuchien_<?php echo $row['id']; ?>">
                            </td>
                            <td>
                                <input type="hidden" name="old_ghichu_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($row['ghichu'] ?? ''); ?>">
                                <textarea name="ghichu_<?php echo $row['id']; ?>" style="width: 120px; height: 100px;" placeholder="Ghi chú"><?php echo htmlspecialchars($row['ghichu'] ?? ''); ?></textarea>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px; text-align: center;">
                    <button type="submit" class="btn-add-criteria" style="padding: 10px 30px; font-size: 16px;">
                        <i class="fas fa-save"></i> Lưu đánh giá
                    </button>
                </div>
            </form>
            </div><!-- End overflow-x wrapper -->
        </div>
    </div>

    <!-- Staff Modal (inline for now) -->
    <div id="staffModal" class="modal">
        <div class="modal-content" style="width: 60%; max-width: 800px;">
            <span class="close" onclick="closeStaffModal()">&times;</span>
            <h3 class="modal-title">Quản lý người chịu trách nhiệm - <span id="dept_display_name"></span></h3>
            <div id="staff_status" style="margin-bottom: 15px; display: none;"></div>
            <div style="margin-bottom: 15px;">
                <h4>Thêm người chịu trách nhiệm mới</h4>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="new_staff_name" class="form-control" placeholder="Tên" style="flex: 3;">
                    <input type="text" id="new_staff_position" class="form-control" placeholder="Chức vụ" style="flex: 2;">
                    <input type="hidden" id="staff_current_dept" value="<?php echo $dept; ?>">
                    <button type="button" onclick="addNewStaff()" class="btn-add-criteria">Thêm</button>
                </div>
            </div>
            <table class="evaluation-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên</th>
                        <th>Chức vụ</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="staff_tbody"></tbody>
            </table>
            <div style="margin-top: 15px; text-align: right;">
                <button type="button" onclick="closeStaffModal()" class="btn-add-criteria" style="background-color: #6c757d;">Đóng</button>
            </div>
        </div>
    </div>

    <!-- Config injection for JavaScript -->
    <script>
    window.indexDeptConfig = {
        id: <?php echo $id; ?>,
        dept: '<?php echo $dept; ?>',
        xuong: '<?php echo htmlspecialchars($xuong); ?>',
        completedTieuchi: <?php echo $completed_tieuchi; ?>,
        totalTieuchi: <?php echo $total_tieuchi; ?>,
        maxPossiblePoints: <?php echo $max_possible_points; ?>,
        requiredImageCriteria: <?php echo json_encode($required_image_criteria); ?>
    };
    </script>

    <?php include 'components/back-to-top.php'; ?>
</body>
</html>
