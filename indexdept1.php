<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Thiết lập đường dẫn tới file log
ini_set('error_log', 'C:/xampp/php/logs/php_log.txt');

// Kết nối database
include 'db_connect.php';

// Thêm vào sau phần kết nối database
include 'check_tieuchi_image.php';

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
// $is_admin = isset($_SESSION['username']) && $_SESSION['username'] === 'admin';
$is_admin = true; // Để test, tạm thời coi như tất cả người dùng là admin

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
    if(isset($row['han_xuly']) && !empty($row['han_xuly'])) {
        $han_xuly = new DateTime($row['han_xuly']);
        $han_xuly_formatted = $han_xuly->format('d/m/Y');
    } else {
        // Kiểm tra phương thức tính hạn xử lý
        if (isset($row['ngay_tinh_han']) && $row['ngay_tinh_han'] == 'ngay_ra') {
            $han_xuly = clone $ngayout;
            $han_xuly->modify("+{$so_ngay_xuly} days");
        } else if (isset($row['ngay_tinh_han']) && $row['ngay_tinh_han'] == 'ngay_ra_tru') {
            // Trường hợp mới: Ngày ra - Số ngày nhập
            $han_xuly = clone $ngayout;
            $han_xuly->modify("-{$so_ngay_xuly} days");
        } else if (isset($row['ngay_tinh_han']) && $row['ngay_tinh_han'] == 'ngay_vao_cong') {
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
    } else if ($_GET['success'] === 'updated_deadline') {
        echo '<div class="success-message">Cập nhật hạn xử lý cho tiêu chí thành công!</div>';
    } else {
        echo '<div class="success-message">Lưu đánh giá thành công!</div>';
    }
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'not_authorized') {
        echo '<div class="error-message">Bạn không có quyền thực hiện thao tác này!</div>';
    } else if ($_GET['error'] === 'missing_data') {
        echo '<div class="error-message">Thiếu dữ liệu cần thiết!</div>';
    } else if ($_GET['error'] === 'record_not_found') {
        echo '<div class="error-message">Không tìm thấy bản ghi!</div>';
    } else if ($_GET['error'] === 'not_updated') {
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
    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">';
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
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">';
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
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">';
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
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">';
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
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">';
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
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">';
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
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">';
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
    <title>Kế Hoạch Rải Chuyền</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/header.css">
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
    'actions' => []
];
?>
<?php include 'components/header.php'; ?>

    <div class="container">
        <?php if (isset($_REQUEST['autoselect_image']) && $_REQUEST['autoselect_image'] == 1 && isset($_REQUEST['tieuchi_id'])): ?>
        <script>
            // Biến lưu ID tiêu chí cần tự động chọn từ URL
            var autoSelectTieuchiId = <?php echo intval($_REQUEST['tieuchi_id']); ?>;
            
            // Sẽ được sử dụng sau khi trang đã tải xong
            document.addEventListener('DOMContentLoaded', function() {
                // Tự động chuyển đến trang upload hình ảnh với tiêu chí được chọn
                window.location.href = 'image_handler.php?id=<?php echo $id; ?>&dept=<?php echo urlencode($dept); ?>&tieuchi_id=' + autoSelectTieuchiId;
            });
        </script>
        <?php endif; ?>
        <div class="action-buttons">
            <h2>Thông tin chi tiết - <?php echo $dept_display_name; ?></h2>
            <div>
                <button type="button" class="btn-add-criteria" onclick="openModal()">Thêm tiêu chí</button>
                <button type="button" class="btn-add-criteria" onclick="openDefaultSettingModal()">Cài đặt mặc định</button>
                <button type="button" class="btn-add-criteria" onclick="syncTieuChiWithDefaultSettings('<?php echo $dept; ?>', '<?php echo $xuong; ?>')" style="background-color: #ffc107; color: #212529;">Áp dụng giá trị mặc định</button>
            </div>
        </div>

        <!-- Modal thêm tiêu chí -->
        <div id="addCriteriaModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h3 class="modal-title">Thêm tiêu chí mới cho <?php echo $dept_display_name; ?></h3>
                <form action="add_criteria.php" method="POST">
                    <input type="hidden" name="dept" value="<?php echo $dept; ?>">
                    <input type="hidden" name="id_sanxuat" value="<?php echo $id; ?>">
                    <?php if ($dept == 'chuanbi_sanxuat_phong_kt' || $dept == 'kho'): ?>
                    <div class="form-group">
                        <label for="nhom">Nhóm:</label>
                        <select id="nhom" name="nhom" required class="form-control">
                            <?php if ($dept == 'chuanbi_sanxuat_phong_kt'): ?>
                                <option value="Nhóm Nghiệp Vụ">a. Nhóm Nghiệp Vụ</option>
                                <option value="Nhóm May Mẫu">b. Nhóm May Mẫu</option>
                                <option value="Nhóm Quy Trình">c. Nhóm Quy Trình Công Nghệ, Thiết Kế Chuyền</option>
                            <?php elseif ($dept == 'kho'): ?>
                                <option value="Kho Nguyên Liệu">a. Kho Nguyên Liệu</option>
                                <option value="Kho Phụ Liệu">b. Kho Phụ Liệu</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="thutu">Thứ tự:</label>
                        <input type="number" id="thutu" name="thutu" required min="1">
                    </div>
                    <div class="form-group">
                        <label for="noidung">Nội dung tiêu chí:</label>
                        <textarea id="noidung" name="noidung" required rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-add-criteria">Lưu tiêu chí</button>
                        <button type="button" onclick="closeModal()" class="btn-back">Hủy</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal cài đặt hạn xử lý -->
        <div id="deadlineModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeDeadlineModal()">&times;</span>
                <h3 class="modal-title">Cài đặt hạn xử lý chung cho tất cả tiêu chí</h3>
                <div class="form-group">
                    <label for="so_ngay_xuly_chung">Số ngày cần trừ từ ngày vào:</label>
                    <input type="number" id="so_ngay_xuly_chung" value="<?php echo $so_ngay_xuly; ?>" min="1" max="30" required>
                    <!-- Thêm các nút gợi ý nhập nhanh -->
                    <div class="quick-suggestion">
                        <span>Gợi ý: </span>
                        <button type="button" onclick="setQuickDays(7)" class="quick-btn">7 ngày</button>
                        <button type="button" onclick="setQuickDays(14)" class="quick-btn">14 ngày</button>
                    </div>
                    <p class="note">Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày</p>
                </div>
                
                <!-- Thêm phần chọn ngày tính hạn xử lý -->
                <div class="form-group">
                    <label for="ngay_tinh_han">Chọn ngày tính hạn xử lý:</label>
                    <select id="ngay_tinh_han" onchange="changeNgayTinhHan()">
                        <option value="ngay_vao" selected>Ngày vào trừ số ngày</option>
                        <option value="ngay_vao_cong">Ngày vào cộng số ngày</option>
                        <option value="ngay_ra">Ngày ra cộng số ngày</option>
                        <option value="ngay_ra_tru">Ngày ra trừ số ngày</option>
                    </select>
                    <p class="note-ngay-tinh" id="note-ngay-tinh">Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày</p>
                </div>
                
                <!-- Thêm phần chọn tiêu chí áp dụng -->
                <div class="form-group">
                    <label>Áp dụng cho tiêu chí:</label>
                    <div id="tieuchi_list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
                        <?php
                        // Lấy danh sách các tiêu chí để hiển thị checkbox
                        $sql_tieuchi = "SELECT id, thutu, noidung FROM tieuchi_dept WHERE dept = ?";
                        $stmt_tieuchi = $connect->prepare($sql_tieuchi);
                        $stmt_tieuchi->bind_param("s", $dept);
                        $stmt_tieuchi->execute();
                        $result_tieuchi = $stmt_tieuchi->get_result();
                        
                        while ($tieuchi = $result_tieuchi->fetch_assoc()) {
                            echo '<div style="margin-bottom: 5px;">';
                            echo '<input type="checkbox" id="tieuchi_' . $tieuchi['id'] . '" class="tieuchi-checkbox" value="' . $tieuchi['id'] . '">';
                            echo '<label for="tieuchi_' . $tieuchi['id'] . '"> ' . $tieuchi['thutu'] . '. ' . substr($tieuchi['noidung'], 0, 50) . (strlen($tieuchi['noidung']) > 50 ? '...' : '') . '</label>';
                            echo '</div>';
                        }
                        ?>
                        <div style="margin-top: 10px;">
                            <button type="button" onclick="selectAllTieuchi(true)" class="small-btn">Chọn tất cả</button>
                            <button type="button" onclick="selectAllTieuchi(false)" class="small-btn">Bỏ chọn tất cả</button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="button" onclick="updateDeadlineAll(<?php echo $id; ?>, '<?php echo $dept; ?>')" class="btn-add-criteria">Lưu cài đặt</button>
                    <button type="button" onclick="closeDeadlineModal()" class="btn-add-criteria">Hủy</button>
                </div>
                <!-- Thêm div để hiển thị trạng thái -->
                <div id="update_status" style="margin-top: 10px; text-align: center; display: none;"></div>
            </div>
        </div>

        <!-- Modal cài đặt hạn xử lý mặc định -->
        <div id="defaultSettingModal" class="modal">
            <div class="modal-content" style="width: 70%; max-width: 900px; max-height: 80vh; overflow: hidden; margin: 5% auto;">
                <span class="close" onclick="closeDefaultSettingModal()">&times;</span>
                <h3 class="modal-title">Cài đặt hạn xử lý mặc định cho <?php echo $dept_display_name; ?> - <span id="xuong_display_name">Tất cả xưởng</span></h3>
                <p style="color: #666; margin-bottom: 15px;">Các cài đặt này sẽ được áp dụng tự động cho tất cả đơn hàng mới được import vào hệ thống.</p>
                
                <div id="default_settings_status" style="margin-bottom: 15px; display: none;"></div>
                <input type="hidden" id="current_dept" value="<?php echo $dept; ?>">
                
                <div style="display: flex; flex-direction: column; height: calc(80vh - 150px);">
                    <div style="margin-bottom: 15px; display: flex; justify-content: space-between; position: sticky; top: 0; background-color: white; padding: 10px 0; z-index: 100;">
                        <div>
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <label for="selected_xuong" style="margin-right: 10px; font-weight: bold;">Chọn Xưởng:</label>
                                <select id="selected_xuong" class="form-control" onchange="changeSelectedXuong()" style="width: 200px;">
                                    <?php
                                    // Lấy danh sách xưởng từ bảng khsanxuat
                                    $sql_xuong = "SELECT DISTINCT xuong FROM khsanxuat WHERE xuong != '' ORDER BY xuong";
                                    $result_xuong = $connect->query($sql_xuong);
                                    
                                    echo '<option value="">-- Tất cả xưởng --</option>';
                                    if ($result_xuong && $result_xuong->num_rows > 0) {
                                        while($row_xuong = $result_xuong->fetch_assoc()) {
                                            $selected = ($row_xuong['xuong'] == $xuong) ? 'selected' : '';
                                            echo '<option value="' . $row_xuong['xuong'] . '" ' . $selected . '>' . $row_xuong['xuong'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="button" onclick="saveAllDefaultSettings('<?php echo $dept; ?>')" class="btn-add-criteria">Lưu tất cả cài đặt</button>
                            <button type="button" onclick="openStaffModal('<?php echo $dept; ?>')" class="btn-add-criteria" style="background-color: #17a2b8; margin-left: 10px;">Quản lý người thực hiện</button>
                        </div>
                        <button type="button" onclick="closeDefaultSettingModal()" class="btn-add-criteria" style="background-color: #6c757d;">Đóng</button>
                    </div>
                    
                    <div class="table-container" style="flex: 1; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                        <table class="evaluation-table" style="width: 100%;">
                            <thead style="position: sticky; top: 0; background-color: #003366; z-index: 10;">
                                <tr>
                                    <th style="width: 5%; color: white;">STT</th>
                                    <th style="width: 30%; color: white;">Tiêu chí đánh giá</th>
                                    <th style="width: 15%; color: white;">Loại tính hạn</th>
                                    <th style="width: 10%; color: white;">Số ngày</th>
                                    <th style="width: 20%; color: white;">Người chịu trách nhiệm</th>
                                    <th style="width: 20%; color: white;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="default_settings_tbody">
                                <?php
                                // Lấy danh sách tiêu chí
                                $sql = "SELECT tc.*, kds.ngay_tinh_han, kds.so_ngay_xuly, kds.nguoi_chiu_trachnhiem_default, nv.ten as ten_nguoi_thuchien 
                                       FROM tieuchi_dept tc 
                                       LEFT JOIN khsanxuat_default_settings kds ON tc.id = kds.id_tieuchi AND kds.dept = ?
                                       LEFT JOIN nhan_vien nv ON kds.nguoi_chiu_trachnhiem_default = nv.id
                                       WHERE tc.dept = ?
                                       ORDER BY 
                                           CASE tc.nhom 
                                               WHEN 'Nhóm Nghiệp Vụ' THEN 1 
                                               WHEN 'Nhóm May Mẫu' THEN 2 
                                               WHEN 'Nhóm Quy Trình' THEN 3
                                               WHEN 'Kho Nguyên Liệu' THEN 1
                                               WHEN 'Kho Phụ Liệu' THEN 2
                                               ELSE 4 
                                           END,
                                           tc.thutu";
                                
                                $stmt = $connect->prepare($sql);
                                $stmt->bind_param("ss", $dept, $dept);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                $current_nhom = '';
                                
                                while ($row = $result->fetch_assoc()) {
                                    if ($current_nhom != $row['nhom']) {
                                        $current_nhom = $row['nhom'];
                                        
                                        // Hiển thị tên nhóm
                                        $nhom_display = '';
                                        if ($dept == 'chuanbi_sanxuat_phong_kt') {
                                            switch ($row['nhom']) {
                                                case 'Nhóm Nghiệp Vụ':
                                                    $nhom_display = 'a. Nhóm Nghiệp Vụ';
                                                    break;
                                                case 'Nhóm May Mẫu':
                                                    $nhom_display = 'b. Nhóm May Mẫu';
                                                    break;
                                                case 'Nhóm Quy Trình':
                                                    $nhom_display = 'c. Nhóm Quy Trình Công Nghệ, Thiết Kế Chuyền';
                                                    break;
                                            }
                                        } elseif ($dept == 'kho') {
                                            switch ($row['nhom']) {
                                                case 'Kho Nguyên Liệu':
                                                    $nhom_display = 'a. Kho Nguyên Liệu';
                                                    break;
                                                case 'Kho Phụ Liệu':
                                                    $nhom_display = 'b. Kho Phụ Liệu';
                                                    break;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td colspan="5" style="background-color: #f3f4f6; color: #1e40af; font-weight: bold; text-align: left; padding: 10px;">
                                                <?php echo $nhom_display; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    
                                    // Giá trị mặc định
                                    $ngay_tinh_han = isset($row['ngay_tinh_han']) ? $row['ngay_tinh_han'] : 'ngay_vao';
                                    $so_ngay_xuly = isset($row['so_ngay_xuly']) ? intval($row['so_ngay_xuly']) : 7;
                                    $nguoi_chiu_trachnhiem_default = isset($row['nguoi_chiu_trachnhiem_default']) ? intval($row['nguoi_chiu_trachnhiem_default']) : 0;
                                    ?>
                                    <tr id="ds_row_<?php echo $row['id']; ?>">
                                        <td><?php echo $row['thutu']; ?></td>
                                        <td class="text-left"><?php echo htmlspecialchars($row['noidung']); ?></td>
                                        <td>
                                            <select id="ds_ngay_tinh_han_<?php echo $row['id']; ?>" class="form-control" style="width: 100%;">
                                                <option value="ngay_vao" <?php echo ($ngay_tinh_han == 'ngay_vao') ? 'selected' : ''; ?>>Ngày vào trừ số ngày</option>
                                                <option value="ngay_vao_cong" <?php echo ($ngay_tinh_han == 'ngay_vao_cong') ? 'selected' : ''; ?>>Ngày vào cộng số ngày</option>
                                                <option value="ngay_ra" <?php echo ($ngay_tinh_han == 'ngay_ra') ? 'selected' : ''; ?>>Ngày ra cộng số ngày</option>
                                                <option value="ngay_ra_tru" <?php echo ($ngay_tinh_han == 'ngay_ra_tru') ? 'selected' : ''; ?>>Ngày ra trừ số ngày</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" id="ds_so_ngay_xuly_<?php echo $row['id']; ?>" class="form-control" style="width: 100%;" value="<?php echo $so_ngay_xuly; ?>" min="0" max="365">
                                        </td>
                                        <td>
                                            <select id="ds_nguoi_chiu_trachnhiem_<?php echo $row['id']; ?>" class="form-control" style="width: 100%;">
                                                <option value="0">-- Chọn người chịu trách nhiệm --</option>
                                                <?php
                                                // Lấy danh sách người thuộc bộ phận
                                                // Lấy danh sách người thực hiện thuộc bộ phận
                                                $sql_staff = "SELECT id, ten FROM nhan_vien WHERE phong_ban = ? AND active = 1 ORDER BY ten";
                                                $stmt_staff = $connect->prepare($sql_staff);
                                                $stmt_staff->bind_param("s", $dept);
                                                $stmt_staff->execute();
                                                $result_staff = $stmt_staff->get_result();
                                                
                                                while ($staff = $result_staff->fetch_assoc()) {
                                                    $selected = ($nguoi_chiu_trachnhiem_default == $staff['id']) ? 'selected' : '';
                                                    echo '<option value="'.$staff['id'].'" '.$selected.'>'.$staff['ten'].'</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" onclick="saveDefaultSetting(<?php echo $row['id']; ?>, '<?php echo $dept; ?>')" class="btn-default-setting">Lưu cài đặt</button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 15px; display: flex; justify-content: space-between; position: sticky; bottom: 0; background-color: white; padding: 10px 0; z-index: 100;">
                        <button type="button" onclick="saveAllDefaultSettings('<?php echo $dept; ?>')" class="btn-add-criteria">Lưu tất cả cài đặt</button>
                        <button type="button" onclick="closeDefaultSettingModal()" class="btn-add-criteria" style="background-color: #6c757d;">Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        <table class="data-table">
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
                            <?php if ($image_count > 0): ?>
                            <i class="fas fa-exclamation-triangle warning-icon"></i>
                            <span class="image-count-badge"><?php echo $image_count; ?></span>
                            <?php endif; ?>
                            Xử lý hình ảnh
                        </a>
                    </td>
                    <td>
                        <a href="file_templates.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>" class="btn-upload-file">
                            <?php 
                            // Kiểm tra số file đã upload
                            $file_count = 0;
                            // Kiểm tra nếu bảng tồn tại trước khi thực hiện truy vấn
                            $check_table_exists = $connect->query("SHOW TABLES LIKE 'dept_template_files'");
                            if ($check_table_exists->num_rows > 0) {
                                $sql_count_files = "SELECT COUNT(*) as file_count FROM dept_template_files WHERE id_khsanxuat = ? AND dept = ?";
                                $stmt_count_files = $connect->prepare($sql_count_files);
                                $stmt_count_files->bind_param("is", $id, $dept);
                                $stmt_count_files->execute();
                                $result_count_files = $stmt_count_files->get_result();
                                $file_count = $result_count_files->fetch_assoc()['file_count'];
                            }
                            
                            if ($file_count > 0): 
                            ?>
                            <i class="fas fa-exclamation-triangle warning-icon"></i>
                            <span class="file-count-badge"><?php echo $file_count; ?></span>
                            <?php endif; ?>
                            Update File
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Thêm vào phần hiển thị form, trước khi hiển thị các tiêu chí -->
        <?php if (isset($error_message)): ?>
        <div class="alert alert-error" style="padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; background-color: #f8d7da;">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- <div class="evaluation-section" style="max-width: 1200px; margin: 0 auto; overflow-x: auto;"> -->
        <div class="evaluation-section" style="max-width: 1600px; margin: 0 auto; overflow-x: auto;">
            <h2>Tiêu chí đánh giá</h2>
            <form action="save_danhgia_with_log.php" method="POST" id="danhgiaForm">
                <input type="hidden" name="id_sanxuat" value="<?php echo $id; ?>">
                <input type="hidden" name="dept" value="<?php echo $dept; ?>">

                <table class="evaluation-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">STT</th>
                            <th style="width: 360px;" class="resizable">Tiêu chí đánh giá</th>
                            <th style="width: 130px;" class="resizable">
                                Hạn Xử Lý
                                <span class="tooltip-icon" title="Thời hạn đã được tính toán tự động">ⓘ</span>
                                <?php /* if($is_admin): */ ?>
                                <button type="button" onclick="openDeadlineModal()" class="small-btn">Cài đặt</button>
                                <?php /* endif; */ ?>
                            </th>
                            <th style="width: 200px;" class="resizable">Người chịu trách nhiệm</th>
                            <th style="width: 120px;" class="resizable">Điểm đánh giá</th>
                            <th style="width: 80px;">Đã thực hiện</th>
                            <th style="width: 150px;" class="resizable">Ghi chú</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php
                        // Lấy danh sách tiêu chí và trạng thái đánh giá
                        $sql = "SELECT tc.*, dg.nguoi_thuchien, dg.da_thuchien, dg.diem_danhgia, dg.ghichu, dg.han_xuly, dg.so_ngay_xuly, dg.ngay_tinh_han 
                               FROM tieuchi_dept tc 
                               LEFT JOIN danhgia_tieuchi dg ON tc.id = dg.id_tieuchi 
                                    AND dg.id_sanxuat = ?
                               WHERE tc.dept = ?
                               ORDER BY 
                                   CASE tc.nhom 
                                       WHEN 'Nhóm Nghiệp Vụ' THEN 1 
                                       WHEN 'Nhóm May Mẫu' THEN 2 
                                       WHEN 'Nhóm Quy Trình' THEN 3
                                       WHEN 'Kho Nguyên Liệu' THEN 1
                                       WHEN 'Kho Phụ Liệu' THEN 2
                                       ELSE 4 
                                   END,
                                   tc.thutu";
                        
                        $stmt = $connect->prepare($sql);
                        $stmt->bind_param("is", $id, $dept);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        $total_tieuchi = 0;
                        $completed_tieuchi = 0;
                        $current_nhom = '';
                        $total_points = 0; // Biến lưu tổng điểm
                        $max_possible_points = 0; // Biến lưu tổng điểm tối đa có thể đạt được
                        
                        while ($row = $result->fetch_assoc()) {
                            if ($current_nhom != $row['nhom']) {
                                $current_nhom = $row['nhom'];
                                
                                // Hiển thị tên nhóm
                                $nhom_display = '';
                                if ($dept == 'chuanbi_sanxuat_phong_kt') {
                                    switch ($row['nhom']) {
                                        case 'Nhóm Nghiệp Vụ':
                                            $nhom_display = 'a. Nhóm Nghiệp Vụ';
                                            break;
                                        case 'Nhóm May Mẫu':
                                            $nhom_display = 'b. Nhóm May Mẫu';
                                            break;
                                        case 'Nhóm Quy Trình':
                                            $nhom_display = 'c. Nhóm Quy Trình Công Nghệ, Thiết Kế Chuyền';
                                            break;
                                    }
                                } elseif ($dept == 'kho') {
                                    switch ($row['nhom']) {
                                        case 'Kho Nguyên Liệu':
                                            $nhom_display = 'a. Kho Nguyên Liệu';
                                            break;
                                        case 'Kho Phụ Liệu':
                                            $nhom_display = 'b. Kho Phụ Liệu';
                                            break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td colspan="7" style="background-color: #f3f4f6; color: #1e40af; font-weight: bold; text-align: left; padding: 10px;">
                                        <?php echo $nhom_display; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            $total_tieuchi++;
                            if ($row['da_thuchien'] == 1) {
                                $completed_tieuchi++;
                            }
                            
                            // Cộng điểm vào tổng
                            $diem_hien_tai = isset($row['diem_danhgia']) ? floatval($row['diem_danhgia']) : 0;
                            $total_points += $diem_hien_tai;
                            
                            // Tính điểm tối đa tùy theo loại tiêu chí
                            if ($dept == 'kehoach' && ($row['thutu'] == 7 || $row['thutu'] == 8)) {
                                $max_possible_points += 1.5; // Điểm tối đa cho tiêu chí 7 và 8 của bộ phận kế hoạch là 1.5
                            } else {
                                $max_possible_points += 3; // Điểm tối đa cho các tiêu chí khác là 3
                            }
                            
                            // Xử lý hạn xử lý cho từng tiêu chí
                            if(isset($row['han_xuly']) && !empty($row['han_xuly'])) {
                                // Sử dụng hạn xử lý riêng của tiêu chí nếu có
                                $han_tc = new DateTime($row['han_xuly']);
                                $han_tc_formatted = $han_tc->format('d/m/Y');
                                $tc_so_ngay_xuly = isset($row['so_ngay_xuly']) ? intval($row['so_ngay_xuly']) : $so_ngay_xuly;
                                $tc_ngay_tinh_han = isset($row['ngay_tinh_han']) ? $row['ngay_tinh_han'] : 'ngay_vao';
                            } else {
                                // Nếu chưa có hạn xử lý riêng, sử dụng hạn xử lý chung
                                $han_tc = clone $han_xuly;
                                $han_tc_formatted = $han_xuly_formatted;
                                $tc_so_ngay_xuly = $so_ngay_xuly;
                                $tc_ngay_tinh_han = 'ngay_vao'; // Giá trị mặc định
                            }
                            
                            // Kiểm tra nếu đã quá hạn (chỉ hiển thị cảnh báo nếu chưa hoàn thành)
                            $now = new DateTime();
                            // Chỉ hiển thị màu đỏ khi: 1) Đã quá hạn VÀ 2) Chưa hoàn thành (dấu X đỏ)
                            $is_overdue = ($han_tc < $now && (!isset($row['diem_danhgia']) || $row['diem_danhgia'] == 0));
                            $deadline_class = $is_overdue ? 'overdue' : '';
                            
                            // Biến kiểm tra tiêu chí đã hoàn thành hay chưa (để hiển thị khác nhau)
                            $is_completed = (!empty($row['diem_danhgia']) && $row['diem_danhgia'] > 0);
                            ?>
                            <tr>
                                <td><?php echo $row['thutu']; ?></td>
                                <td class="text-left;"><?php echo htmlspecialchars($row['noidung']); ?></td>
                                <td class="deadline-info">
                                    <span class="deadline-date <?php echo $deadline_class; ?>" id="date_display_<?php echo $row['id']; ?>"><?php echo $han_tc_formatted; ?></span>
                                    <?php /* if($is_admin): */ ?>
                                    <div class="deadline-form">
                                        <div style="display: flex; align-items: center;">
                                            <input type="number" id="so_ngay_xuly_<?php echo $row['id']; ?>" value="<?php echo isset($row['so_ngay_xuly']) ? $row['so_ngay_xuly'] : $tc_so_ngay_xuly; ?>" min="1" max="30" class="deadline-input">
                                            <button type="button" onclick="updateDeadline(<?php echo $id; ?>, <?php echo $row['id']; ?>, '<?php echo $dept; ?>')" class="deadline-button">Cập nhật</button>
                                        </div>
                                        <!-- Thêm select để chọn ngày tính hạn xử lý cho từng tiêu chí -->
                                        <div style="display: flex; align-items: center; margin-top: 3px;">
                                            <select id="ngay_tinh_han_<?php echo $row['id']; ?>" class="ngay-tinh-han-select">
                                                <option value="ngay_vao" <?php echo ($tc_ngay_tinh_han == 'ngay_vao') ? 'selected' : ''; ?>>Ngày vào trừ số ngày</option>
                                                <option value="ngay_vao_cong" <?php echo ($tc_ngay_tinh_han == 'ngay_vao_cong') ? 'selected' : ''; ?>>Ngày vào cộng số ngày</option>
                                                <option value="ngay_ra" <?php echo ($tc_ngay_tinh_han == 'ngay_ra') ? 'selected' : ''; ?>>Ngày ra cộng số ngày</option>
                                                <option value="ngay_ra_tru" <?php echo ($tc_ngay_tinh_han == 'ngay_ra_tru') ? 'selected' : ''; ?>>Ngày ra trừ số ngày</option>
                                            </select>
                                        </div>
                                    </div>
                                    <?php /* endif; */ ?>
                                </td>
                                <td>
                                    <!-- Thêm hidden input để lưu giá trị gốc của người thực hiện -->
                                    <input type="hidden" name="old_nguoi_thuchien_<?php echo $row['id']; ?>" 
                                           value="<?php echo $row['nguoi_thuchien']; ?>">
                                    <select name="nguoi_thuchien_<?php echo $row['id']; ?>" required class="nguoi-thuchien-select">
                                        <?php
                                        // Lấy danh sách người thực hiện từ cơ sở dữ liệu
                                        $sql_staff = "SELECT id, ten FROM nhan_vien WHERE phong_ban = ? AND active = 1 ORDER BY ten";
                                        $stmt_staff = $connect->prepare($sql_staff);
                                        $stmt_staff->bind_param("s", $dept);
                                        $stmt_staff->execute();
                                        $result_staff = $stmt_staff->get_result();
                                        
                                        if ($result_staff->num_rows > 0) {
                                            while ($staff = $result_staff->fetch_assoc()) {
                                                $selected = ($row['nguoi_thuchien'] == $staff['id']) ? 'selected' : '';
                                                echo "<option value='".$staff['id']."' $selected>".htmlspecialchars($staff['ten'])."</option>";
                                            }
                                        } else {
                                            // Dùng danh sách mặc định nếu không có dữ liệu
                                            $nguoi_thuchien = ($dept == 'kehoach') 
                                                ? ['Nguyễn Văn A', 'Trần Thị B'] 
                                                : ['Phạm Văn X', 'Lê Thị Y'];
                                            
                                            foreach ($nguoi_thuchien as $nguoi) {
                                                $selected = ($row['nguoi_thuchien'] == $nguoi) ? 'selected' : '';
                                                echo "<option value='".htmlspecialchars($nguoi)."' $selected>".htmlspecialchars($nguoi)."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <!-- Thêm hidden input để lưu giá trị gốc của điểm -->
                                    <input type="hidden" name="old_diem_<?php echo $row['id']; ?>" 
                                           value="<?php echo isset($row['diem_danhgia']) ? $row['diem_danhgia'] : '0'; ?>">
                                    <select name="diem_danhgia_<?php echo $row['id']; ?>" 
                                            class="diem-dropdown"
                                            data-tieuchi-id="<?php echo $row['id']; ?>"
                                            onchange="updateStatus(this)">
                                        <?php if ($dept == 'kehoach' && ($row['thutu'] == 7 || $row['thutu'] == 8)): ?>
                                            <!-- Mức điểm đặc biệt cho tiêu chí 7 và 8 của Kế Hoạch -->
                                            <option value="0" <?php echo (!isset($row['diem_danhgia']) || $row['diem_danhgia'] === null || $row['diem_danhgia'] == 0) ? 'selected' : ''; ?>>0</option>
                                            <option value="0.5" <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] == 0.5) ? 'selected' : ''; ?>>0.5</option>
                                            <option value="1.5" <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] == 1.5) ? 'selected' : ''; ?>>1.5</option>
                                        <?php else: ?>
                                            <!-- Mức điểm mặc định cho các tiêu chí khác -->
                                            <option value="0" <?php echo (!isset($row['diem_danhgia']) || $row['diem_danhgia'] === null || $row['diem_danhgia'] == 0) ? 'selected' : ''; ?>>0</option>
                                            <option value="1" <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] == 1) ? 'selected' : ''; ?>>1</option>
                                            <option value="3" <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] == 3) ? 'selected' : ''; ?>>3</option>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="checkbox" 
                                           class="checkbox-input" 
                                           id="checkbox_<?php echo $row['id']; ?>"
                                           data-tieuchi-id="<?php echo $row['id']; ?>"
                                           <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] > 0) ? 'checked' : ''; ?>
                                           disabled>
                                    <label class="checkbox <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] > 0) ? 'checked' : 'unchecked'; ?>" 
                                           for="checkbox_<?php echo $row['id']; ?>"
                                           id="checkbox_label_<?php echo $row['id']; ?>">
                                        <span class="checkmark"><?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] > 0) ? '✓' : 'X'; ?></span>
                                    </label>
                                    <input type="hidden" name="da_thuchien_<?php echo $row['id']; ?>" 
                                           value="<?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] > 0) ? 1 : 0; ?>" 
                                           id="da_thuchien_<?php echo $row['id']; ?>">
                                </td>
                                <td>
                                    <!-- Thêm hidden input để lưu giá trị gốc của ghi chú -->
                                    <input type="hidden" name="old_ghichu_<?php echo $row['id']; ?>" 
                                           value="<?php echo htmlspecialchars($row['ghichu'] ?? ''); ?>">
                                    <textarea name="ghichu_<?php echo $row['id']; ?>" 
                                              style="width: 120px; height: 100px;"
                                              placeholder="Ghi chú"><?php echo htmlspecialchars($row['ghichu'] ?? ''); ?></textarea>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold;">Tổng điểm đánh giá:</td>
                            <td style="font-weight: bold; text-align: right;" id="total_points">
                                <?php echo number_format($total_points, 1); ?>/<?php echo number_format($max_possible_points, 1); ?>
                            </td>
                            <td colspan="3">
                                <div class="progress-bar-container" style="width: 100%; background-color: #eee; height: 20px; border-radius: 10px; overflow: hidden;">
                                    <?php 
                                    $percent = ($max_possible_points > 0) ? ($total_points / $max_possible_points) * 100 : 0;
                                    $bar_color = "#4CAF50"; // Màu xanh lá mặc định
                                    
                                    // Thay đổi màu sắc dựa vào phần trăm hoàn thành
                                    if ($percent < 30) {
                                        $bar_color = "#F44336"; // Đỏ
                                    } else if ($percent < 70) {
                                        $bar_color = "#FFC107"; // Vàng
                                    }
                                    ?>
                                    <div class="progress-bar" style="width: <?php echo $percent; ?>%; background-color: <?php echo $bar_color; ?>; height: 100%; text-align: center; line-height: 20px; color: white; font-weight: bold;">
                                        <?php echo round($percent); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div class="button-group">
                    <button type="submit" class="btn-save">Lưu đánh giá</button>
                    <a href="index.php" class="btn-back">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>



<style>
.diem-select {
    width: 85px;
    padding: 3px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    font-size: 13px;
    height: 30px;
}

.nguoi-thuchien-select {
    width: 160px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
    height: 60px;
}

select[name^="nguoi_thuchien_"] {
    width: 160px;
    padding: 3px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
    height: 30px;
}

.checkbox {
    display: inline-block;
    width: 25px;
    height: 25px;
    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    position: relative;
    cursor: default;
    text-align: center;
    line-height: 25px;
}

.checkbox.checked {
    background-color: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

.checkbox.unchecked {
    background-color: #F44336;
    border-color: #F44336;
    color: white;
}

.checkmark {
    font-weight: bold;
    font-size: 14px;
}

.checkbox-input {
    display: none;
}

.small-btn {
    font-size: 9px;
    padding: 1px 3px;
}

@media screen and (max-width: 1200px) {
    .evaluation-table th, .evaluation-table td {
        font-size: 13px;
    }
    
    select[name^="nguoi_thuchien_"] {
        width: 140px;
        padding: 2px;
        font-size: 12px;
        height: 28px;
    }
    
    .diem-select {
        width: 80px;
        height: 28px;
    }
}

/* Các style cho modal cài đặt mặc định */
.btn-default-setting {
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 5px 10px;
    cursor: pointer;
    font-size: 12px;
    transition: background-color 0.3s;
}

.btn-default-setting:hover {
    background-color: #2980b9;
}

/* Style cho form trong modal */
.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 14px;
}

/* Style cho danh sách cài đặt */
#default_settings_tbody td {
    padding: 10px;
    vertical-align: middle;
}

#default_settings_tbody .text-left {
    text-align: left;
}

/* Style cho thông báo trạng thái */
#default_settings_status {
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}

/* Thêm CSS cho modal cài đặt mặc định */
.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    border-radius: 8px;
    position: relative;
}

.table-container {
    max-height: calc(80vh - 200px);
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
}

.table-container thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    box-shadow: 0 1px 0 rgba(0,0,0,0.1);
}

/* Style cho danh sách cài đặt */
#default_settings_tbody td {
    padding: 10px;
    vertical-align: middle;
}

#default_settings_tbody .text-left {
    text-align: left;
}

/* Style cho thông báo trạng thái */
#default_settings_status {
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}
    
    /* Nút Xử lý hình ảnh */
    .btn-upload-image {
        display: inline-flex;
        align-items: center;
        padding: 0px 0px;
        background-color: #1976d2;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 12px;
        transition: all 0.3s ease;
        margin-left: 0px;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .btn-upload-image:hover {
        background-color: #1565c0;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        transform: translateY(-1px);
        color: white;
        text-decoration: none;
    }
    
    .btn-upload-image:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .image-count-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        font-weight: bold;
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.2);
            opacity: 0.8;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .warning-icon {
        color: #ffc107;
        animation: blink 1.5s infinite;
    }

    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    /* Nút Update File */
    .btn-upload-file {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: #0275d8;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 6px 12px;
        font-size: 14px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-upload-file:hover {
        background-color: #025aa5;
        text-decoration: none;
        color: white;
    }

    .file-count-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        font-weight: bold;
        animation: pulse 1.5s infinite;
    }

    .container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
    }

    /* CSS cho các phần tử khác */
    .btn-upload-image {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        background-color: #28a745;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s ease;
        margin-left: 10px;
    }

    .btn-upload-image:hover {
        background-color: #218838;
        color: white;
        text-decoration: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .alert {
        padding: 8px 12px;
        border-radius: 4px;
        margin-top: 5px;
        font-size: 13px;
        display: block;
        width: 52px;
    }

    .alert-warning {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
    }

    .warning-message {
        margin-top: 5px;
    }
    /* CSS cho các phần tử khác tiếp theo */
</style>

    <!-- Modal Quản lý người thực hiện -->
    <div id="staffModal" class="modal">
        <div class="modal-content" style="width: 60%; max-width: 800px; max-height: 80vh; overflow: hidden; margin: 5% auto;">
            <span class="close" onclick="closeStaffModal()">&times;</span>
            <h3 class="modal-title">Quản lý người chịu trách nhiệm - <span id="dept_display_name"></span></h3>
            
            <div id="staff_status" style="margin-bottom: 15px; display: none;"></div>
            
            <div style="display: flex; flex-direction: column; height: calc(80vh - 150px);">
                <div style="margin-bottom: 15px;">
                    <h4>Thêm người chịu trách nhiệm mới</h4>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="text" id="new_staff_name" class="form-control" placeholder="Tên người chịu trách nhiệm" style="flex: 3;">
                        <input type="text" id="new_staff_position" class="form-control" placeholder="Chức vụ (không bắt buộc)" style="flex: 2;">
                        <input type="hidden" id="current_dept" value="">
                        <button type="button" onclick="addNewStaff()" class="btn-add-criteria" style="flex: 1;">Thêm</button>
                    </div>
                </div>
                
                <div class="table-container" style="flex: 1; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                    <table class="evaluation-table" style="width: 100%;">
                        <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                            <tr>
                                <th style="width: 5%;">STT</th>
                                <th style="width: 40%;">Tên</th>
                                <th style="width: 30%;">Chức vụ</th>
                                <th style="width: 25%;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="staff_tbody">
                            <!-- Danh sách người thực hiện sẽ được load bằng JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 15px; display: flex; justify-content: flex-end;">
                    <button type="button" onclick="closeStaffModal()" class="btn-add-criteria" style="background-color: #6c757d;">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    
    


<script>
// JavaScript được tái cấu trúc và sửa lỗi
// Được chỉnh sửa ngày <?php echo date('Y-m-d H:i:s'); ?>

// Các hàm cơ bản cho modal
function openModal() {
    document.getElementById('addCriteriaModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('addCriteriaModal').style.display = 'none';
}

function openDeadlineModal() {
    document.getElementById('deadlineModal').style.display = 'block';
}

function closeDeadlineModal() {
    document.getElementById('deadlineModal').style.display = 'none';
}

function openDefaultSettingModal() {
    document.getElementById('defaultSettingModal').style.display = 'block';
    document.getElementById('current_dept').value = '<?php echo $dept; ?>';
    document.getElementById('selected_xuong').value = '<?php echo $xuong; ?>';
    changeSelectedXuong();
}

function closeDefaultSettingModal() {
    document.getElementById('defaultSettingModal').style.display = 'none';
}

function closeStaffModal() {
    document.getElementById('staffModal').style.display = 'none';
}

// Hàm cập nhật trạng thái checkbox khi thay đổi điểm
function updateStatus(element) {
    if (!element) return;
    
    const tieuchi_id = element.getAttribute('data-tieuchi-id');
    const checkbox = document.getElementById('checkbox_' + tieuchi_id);
    const hiddenField = document.getElementById('da_thuchien_' + tieuchi_id);
    const label = document.getElementById('checkbox_label_' + tieuchi_id);
    const diem = parseFloat(element.value);
    
    if (diem > 0) {
        label.classList.remove('unchecked');
        label.classList.add('checked');
        label.innerHTML = '<span class="checkmark">✓</span>';
        hiddenField.value = 1;
    } else {
        label.classList.remove('checked');
        label.classList.add('unchecked');
        label.innerHTML = '<span class="checkmark">X</span>';
        hiddenField.value = 0;
    }
    
    // Cập nhật tổng điểm sau khi thay đổi điểm
    updateTotalPoints();
}

// Hàm cập nhật tổng điểm
function updateTotalPoints() {
    let totalPoints = 0;
    const maxPoints = <?php echo $max_possible_points; ?>;
    
    // Tính tổng điểm từ tất cả các dropdown
    document.querySelectorAll('.diem-dropdown').forEach(function(select) {
        totalPoints += parseFloat(select.value);
    });
    
    // Cập nhật hiển thị tổng điểm
    const totalPointsElement = document.getElementById('total_points');
    totalPointsElement.innerHTML = number_format(totalPoints, 1) + '/' + number_format(maxPoints, 1);
    
    // Cập nhật thanh tiến trình
    const percent = (maxPoints > 0) ? (totalPoints / maxPoints) * 100 : 0;
    const progressBar = document.querySelector('.progress-bar');
    progressBar.style.width = percent + '%';
    progressBar.innerHTML = Math.round(percent) + '%';
    
    // Thay đổi màu sắc dựa vào phần trăm hoàn thành
    if (percent < 30) {
        progressBar.style.backgroundColor = "#F44336"; // Đỏ
    } else if (percent < 70) {
        progressBar.style.backgroundColor = "#FFC107"; // Vàng
    } else {
        progressBar.style.backgroundColor = "#4CAF50"; // Xanh lá
    }
}

// Hàm định dạng số với số thập phân
function number_format(number, decimals) {
    // Đảm bảo number là số
    number = parseFloat(number);
    if (isNaN(number)) {
        return "0";
    }
    
    // Định dạng số với số thập phân
    return number.toFixed(decimals);
}

// Hàm thiết lập ngày nhanh
function setQuickDays(days) {
    document.getElementById('so_ngay_xuly_chung').value = days;
    const quickButtons = document.querySelectorAll('.quick-btn');
    quickButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.includes(days.toString())) {
            btn.classList.add('active');
        }
    });
    
    changeNgayTinhHan();
}

// Hàm thay đổi mô tả ngày tính hạn
function changeNgayTinhHan() {
    const ngayTinhHan = document.getElementById('ngay_tinh_han').value;
    const noteElement = document.getElementById('note-ngay-tinh');
    
    switch(ngayTinhHan) {
        case 'ngay_vao':
            noteElement.textContent = 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày';
            break;
        case 'ngay_vao_cong':
            noteElement.textContent = 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" + 7 ngày';
            break;
        case 'ngay_ra':
            noteElement.textContent = 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Ra" + 7 ngày';
            break;
        case 'ngay_ra_tru':
            noteElement.textContent = 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Ra" - 7 ngày';
            break;
    }
}

// Hàm chọn tất cả tiêu chí
function selectAllTieuchi(select) {
    const checkboxes = document.querySelectorAll('.tieuchi-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = select;
    });
}

// Hàm cập nhật hạn xử lý cho một tiêu chí
function updateDeadline(idSanxuat, idTieuchi, dept) {
    // Lấy giá trị hiện tại từ ô input do người dùng nhập
    const soNgayXuly = document.getElementById('so_ngay_xuly_' + idTieuchi).value;
    const ngayTinhHan = document.getElementById('ngay_tinh_han_' + idTieuchi).value;
    const dateDisplay = document.getElementById('date_display_' + idTieuchi);
    const originalText = dateDisplay.innerHTML;
    
    // Lấy giá trị xưởng hiện tại (nếu có)
    const currentXuong = '<?php echo $xuong; ?>';
    
    // Hiển thị trạng thái đang cập nhật
    dateDisplay.innerHTML = '<img src="img/loading.gif" style="width: 20px; height: 20px;" alt="Đang cập nhật"> Đang cập nhật...';
    dateDisplay.style.backgroundColor = '#e2f0fd';
    
    // Thực hiện cập nhật bằng AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_deadline_tieuchi.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Cập nhật hiển thị ngày deadline
                        dateDisplay.innerHTML = response.new_date;
                        dateDisplay.classList.add('update-success');
                        // Hiệu ứng flash cho thanh deadline
                        dateDisplay.style.backgroundColor = '#d4edda';
                        
                        // Giữ nguyên giá trị ô input mà người dùng đã nhập, không dùng giá trị từ server
                        document.getElementById('so_ngay_xuly_' + idTieuchi).value = soNgayXuly;
                        document.getElementById('ngay_tinh_han_' + idTieuchi).value = ngayTinhHan;
                        
                        setTimeout(function() {
                            dateDisplay.classList.remove('update-success');
                            dateDisplay.style.backgroundColor = '';
                        }, 2000);
                        
                        // Comment phần confirm này lại vì người dùng không muốn nó xuất hiện
                        /*if (confirm('Bạn có muốn lưu số ngày này vào cài đặt mặc định cho xưởng này không?')) {
                            saveDefaultSetting(idTieuchi, dept);
                        }*/
                    } else {
                        // Hiển thị lỗi trong khung deadline
                        dateDisplay.innerHTML = '<span style="color: red;">' + (response.message || 'Lỗi không xác định') + '</span>';
                        dateDisplay.style.backgroundColor = '#f8d7da';
                        setTimeout(function() {
                            dateDisplay.innerHTML = originalText;
                            dateDisplay.style.backgroundColor = '';
                        }, 3000);
                    }
                } catch (e) {
                    console.error('Lỗi xử lý JSON:', e);
                    dateDisplay.innerHTML = '<span style="color: red;">Lỗi xử lý dữ liệu</span>';
                    dateDisplay.style.backgroundColor = '#f8d7da';
                    setTimeout(function() {
                        dateDisplay.innerHTML = originalText;
                        dateDisplay.style.backgroundColor = '';
                    }, 3000);
                }
            } else {
                dateDisplay.innerHTML = '<span style="color: red;">Lỗi kết nối máy chủ</span>';
                dateDisplay.style.backgroundColor = '#f8d7da';
                setTimeout(function() {
                    dateDisplay.innerHTML = originalText;
                    dateDisplay.style.backgroundColor = '';
                }, 3000);
            }
        }
    };
    xhr.send('id_sanxuat=' + idSanxuat + '&id_tieuchi=' + idTieuchi + '&so_ngay_xuly=' + soNgayXuly + '&ngay_tinh_han=' + ngayTinhHan + '&dept=' + dept);
}

// Hàm cập nhật hạn xử lý cho nhiều tiêu chí
function updateDeadlineAll(idSanxuat, dept) {
    const soNgayXulyChung = document.getElementById('so_ngay_xuly_chung').value;
    const ngayTinhHan = document.getElementById('ngay_tinh_han').value;
    const updateStatusDiv = document.getElementById('update_status');
    const selectedTieuchi = [];
    
    // Lấy danh sách tiêu chí được chọn
    document.querySelectorAll('.tieuchi-checkbox:checked').forEach(checkbox => {
        selectedTieuchi.push(checkbox.value);
    });
    
    if (selectedTieuchi.length === 0) {
        alert('Vui lòng chọn ít nhất một tiêu chí để áp dụng cài đặt.');
        return;
    }
    
    updateStatusDiv.style.display = 'block';
    updateStatusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px; display: flex; align-items: center;"><img src="img/loading.gif" style="width: 20px; height: 20px; margin-right: 10px;" alt="Đang cập nhật"> Đang cập nhật hạn xử lý cho ' + selectedTieuchi.length + ' tiêu chí...</div>';
    
    // Tạo hiệu ứng loading cho các tiêu chí đang được cập nhật
    selectedTieuchi.forEach(tieuchiId => {
        const dateDisplay = document.getElementById('date_display_' + tieuchiId);
        if (dateDisplay) {
            dateDisplay.innerHTML = '<img src="img/loading.gif" style="width: 16px; height: 16px;" alt="Đang cập nhật">';
            dateDisplay.style.backgroundColor = '#e2f0fd';
        }
    });
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_deadline_all.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        updateStatusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã cập nhật hạn xử lý cho ' + response.updated_count + ' tiêu chí!</div>';
                        
                        // Cập nhật hiển thị trên giao diện
                        if (response.updated_items) {
                            response.updated_items.forEach(item => {
                                const dateDisplay = document.getElementById('date_display_' + item.id_tieuchi);
                                if (dateDisplay) {
                                    dateDisplay.innerHTML = item.new_date;
                                    dateDisplay.classList.add('update-success');
                                    dateDisplay.style.backgroundColor = '#d4edda';
                                    
                                    // Cập nhật giá trị trong input
                                    const soNgayInput = document.getElementById('so_ngay_xuly_' + item.id_tieuchi);
                                    if (soNgayInput) {
                                        soNgayInput.value = soNgayXulyChung;
                                    }
                                    
                                    // Cập nhật select ngày tính hạn
                                    const ngayTinhHanSelect = document.getElementById('ngay_tinh_han_' + item.id_tieuchi);
                                    if (ngayTinhHanSelect) {
                                        ngayTinhHanSelect.value = ngayTinhHan;
                                    }
                                    
                                    setTimeout(function() {
                                        dateDisplay.classList.remove('update-success');
                                        dateDisplay.style.backgroundColor = '';
                                    }, 2000);
                                }
                            });
                        }
                        
                        // Hiển thị thông báo hỏi về việc lưu cài đặt mặc định
                        setTimeout(function() {
                            // Comment phần confirm này lại vì người dùng không muốn nó xuất hiện
                            /*if (confirm('Bạn có muốn lưu các cài đặt này làm mặc định cho tất cả tiêu chí không?')) {
                                // Gọi hàm lưu tất cả cài đặt mặc định
                                saveAllDefaultSettings(dept);
                            } else {
                                updateStatusDiv.style.display = 'none';
                                closeDeadlineModal();
                            }*/
                            
                            // Chỉ đóng modal sau khi hoàn thành
                            updateStatusDiv.style.display = 'none';
                            closeDeadlineModal();
                        }, 1000);
                    } else {
                        updateStatusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                        
                        // Khôi phục trạng thái ban đầu cho các tiêu chí
                        selectedTieuchi.forEach(tieuchiId => {
                            const dateDisplay = document.getElementById('date_display_' + tieuchiId);
                            if (dateDisplay) {
                                dateDisplay.style.backgroundColor = '';
                                // Tải lại dữ liệu hiện tại từ cơ sở dữ liệu
                                loadCurrentDeadline(tieuchiId, idSanxuat);
                            }
                        });
                    }
                } catch (e) {
                    console.error('Lỗi xử lý JSON:', e);
                    updateStatusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                    
                    // Khôi phục trạng thái ban đầu cho các tiêu chí
                    selectedTieuchi.forEach(tieuchiId => {
                        const dateDisplay = document.getElementById('date_display_' + tieuchiId);
                        if (dateDisplay) {
                            dateDisplay.style.backgroundColor = '';
                            loadCurrentDeadline(tieuchiId, idSanxuat);
                        }
                    });
                }
            } else {
                updateStatusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
                
                // Khôi phục trạng thái ban đầu cho các tiêu chí
                selectedTieuchi.forEach(tieuchiId => {
                    const dateDisplay = document.getElementById('date_display_' + tieuchiId);
                    if (dateDisplay) {
                        dateDisplay.style.backgroundColor = '';
                        loadCurrentDeadline(tieuchiId, idSanxuat);
                    }
                });
            }
        }
    };
    xhr.send('id_sanxuat=' + idSanxuat + '&tieuchi=' + JSON.stringify(selectedTieuchi) + '&so_ngay_xuly=' + soNgayXulyChung + '&ngay_tinh_han=' + ngayTinhHan + '&dept=' + dept);
}

// Hàm mới để tải lại thông tin hạn xử lý hiện tại
function loadCurrentDeadline(id_tieuchi, id_sanxuat) {
    // Chỉ lấy thông tin hiển thị
    let dateDisplay = document.getElementById('date_display_' + id_tieuchi);
    if (dateDisplay) {
        dateDisplay.innerHTML = '<span class="loading-indicator">Đang tải...</span>';
    }
    
    fetch('get_tieuchi_deadline.php?id_tieuchi=' + id_tieuchi + '&id_sanxuat=' + id_sanxuat)
    .then(response => response.json())
    .then(data => {
        console.log("Dữ liệu nhận từ server:", data);
        if (dateDisplay) {
            if (data.success) {
                // Chỉ cập nhật phần hiển thị deadline, không cập nhật các input field
                dateDisplay.innerHTML = data.deadline;
                
                // Không cập nhật giá trị các trường input để giữ nguyên giá trị người dùng đã nhập
            } else {
                dateDisplay.innerHTML = 'Chưa thiết lập';
            }
        }
    })
    .catch(error => {
        console.error('Lỗi khi tải thông tin deadline:', error);
        if (dateDisplay) {
            dateDisplay.innerHTML = 'Lỗi tải dữ liệu';
        }
    });
}

// Hàm lưu cài đặt mặc định cho một tiêu chí
function saveDefaultSetting(id_tieuchi, dept) {
    const ngayTinhHan = document.getElementById('ds_ngay_tinh_han_' + id_tieuchi).value;
    const soNgayXuly = document.getElementById('ds_so_ngay_xuly_' + id_tieuchi).value;
    const nguoiChiuTrachnhiem = document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi).value;
    const statusDiv = document.getElementById('default_settings_status');
    const row = document.getElementById('ds_row_' + id_tieuchi);
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang lưu cài đặt...</div>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_default_setting.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã lưu cài đặt mặc định!</div>';
                        row.style.backgroundColor = '#f8f9fa';
                        setTimeout(function() {
                            row.style.backgroundColor = '';
                            statusDiv.style.display = 'none';
                        }, 2000);
                    } else {
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                    }
                } catch (e) {
                    console.error(e);
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                }
            } else {
                statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
            }
        }
    };
    xhr.send('id_tieuchi=' + id_tieuchi + '&dept=' + dept + '&ngay_tinh_han=' + ngayTinhHan + '&so_ngay_xuly=' + soNgayXuly + '&nguoi_chiu_trachnhiem=' + nguoiChiuTrachnhiem);
}

// Hàm lưu tất cả cài đặt mặc định
function saveAllDefaultSettings(dept) {
    const statusDiv = document.getElementById('default_settings_status');
    const rows = document.querySelectorAll("#default_settings_tbody tr[id^='ds_row_']");
    const selectedXuong = document.getElementById('selected_xuong').value;
    const settings = [];
    
    rows.forEach(row => {
        const id_tieuchi = row.id.replace('ds_row_', '');
        const ngayTinhHan = document.getElementById('ds_ngay_tinh_han_' + id_tieuchi)?.value;
        const soNgayXuly = document.getElementById('ds_so_ngay_xuly_' + id_tieuchi)?.value;
        const nguoiChiuTrachnhiem = document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi)?.value;
        
        if (ngayTinhHan && soNgayXuly) {
            settings.push({
                id_tieuchi: id_tieuchi,
                ngay_tinh_han: ngayTinhHan,
                so_ngay_xuly: soNgayXuly,
                nguoi_chiu_trachnhiem: nguoiChiuTrachnhiem || 0
            });
        }
    });
    
    if (settings.length === 0) {
        alert('Không tìm thấy cài đặt nào để lưu.');
        return;
    }
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang lưu tất cả cài đặt...</div>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_all_default_settings.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã lưu tất cả cài đặt mặc định!</div>';
                        rows.forEach(row => {
                            row.style.backgroundColor = '#f8f9fa';
                            setTimeout(function() {
                                row.style.backgroundColor = '';
                            }, 2000);
                        });
                        
                        setTimeout(function() {
                            statusDiv.style.display = 'none';
                        }, 3000);
                    } else {
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                    }
                } catch (e) {
                    console.error(e);
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                }
            } else {
                statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
            }
        }
    };
    xhr.send('dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong) + '&settings=' + JSON.stringify(settings));
}

// Hàm mở modal quản lý người thực hiện
function openStaffModal(dept) {
    document.getElementById('current_dept').value = dept;
    document.getElementById('staffModal').style.display = 'block';
    document.getElementById('dept_display_name').textContent = getDeptDisplayName(dept);
    
    loadStaffList(dept);
}

// Hàm lấy tên hiển thị của bộ phận
function getDeptDisplayName(dept) {
    const deptNames = {
        'kehoach': 'BỘ PHẬN KẾ HOẠCH',
        'chuanbi_sanxuat_phong_kt': 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)',
        'kho': 'KHO NGUYÊN, PHỤ LIỆU',
        'cat': 'BỘ PHẬN CẮT',
        'ep_keo': 'BỘ PHẬN ÉP KEO',
        'co_dien': 'BỘ PHẬN CƠ ĐIỆN',
        'chuyen_may': 'BỘ PHẬN CHUYỀN MAY',
        'kcs': 'BỘ PHẬN KCS',
        'ui_thanh_pham': 'BỘ PHẬN ỦI THÀNH PHẨM',
        'hoan_thanh': 'BỘ PHẬN HOÀN THÀNH'
    };
    
    return deptNames[dept] || 'KHÔNG XÁC ĐỊNH';
}

// Hàm tải danh sách người thực hiện
function loadStaffList(dept) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_staff_list.php?dept=' + encodeURIComponent(dept), true);
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        const staffList = response.data;
                        let html = '';
                        
                        staffList.forEach(function(staff, index) {
                            html += `
                            <tr id="staff_row_${staff.id}">
                                <td>${index + 1}</td>
                                <td><input type="text" id="staff_name_${staff.id}" class="form-control" value="${staff.ten}" style="width: 100%;"></td>
                                <td><input type="text" id="staff_position_${staff.id}" class="form-control" value="${staff.chuc_vu || ''}" style="width: 100%;"></td>
                                <td>
                                    <button type="button" onclick="updateStaff(${staff.id})" class="btn-default-setting">Cập nhật</button>
                                    <button type="button" onclick="deleteStaff(${staff.id})" class="btn-default-setting" style="background-color: #dc3545;">Xóa</button>
                                </td>
                            </tr>`;
                        });
                        
                        document.getElementById('staff_tbody').innerHTML = html;
                    } else {
                        document.getElementById('staff_tbody').innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Lỗi: ' + response.message + '</td></tr>';
                    }
                } catch (e) {
                    console.error(e);
                    document.getElementById('staff_tbody').innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Lỗi khi xử lý phản hồi từ máy chủ.</td></tr>';
                }
            } else {
                document.getElementById('staff_tbody').innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Lỗi khi tải danh sách người thực hiện.</td></tr>';
            }
        }
    };
    xhr.send();
}

// Hàm thêm người thực hiện mới
function addNewStaff() {
    const staffName = document.getElementById('new_staff_name').value.trim();
    const staffPosition = document.getElementById('new_staff_position').value.trim();
    const dept = document.getElementById('current_dept').value;
    const statusDiv = document.getElementById('staff_status');
    
    if (!staffName) {
        alert('Vui lòng nhập tên người chịu trách nhiệm.');
        return;
    }
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang thêm người chịu trách nhiệm...</div>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'add_staff.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã thêm người chịu trách nhiệm thành công!</div>';
                        document.getElementById('new_staff_name').value = '';
                        document.getElementById('new_staff_position').value = '';
                        loadStaffList(dept);
                        
                        setTimeout(function() {
                            statusDiv.style.display = 'none';
                        }, 3000);
                    } else {
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                    }
                } catch (e) {
                    console.error(e);
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                }
            } else {
                statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
            }
        }
    };
    xhr.send('ten=' + encodeURIComponent(staffName) + '&chuc_vu=' + encodeURIComponent(staffPosition) + '&phong_ban=' + dept);
}

// Khởi tạo các sự kiện khi trang đã load xong
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo các select điểm đánh giá
    const diemSelects = document.querySelectorAll('.diem-dropdown');
    diemSelects.forEach(select => {
        select.addEventListener('change', function() {
            updateStatus(this);
        });
    });
    
    // Khởi tạo mô tả cho phương thức tính hạn xử lý
    if (document.getElementById('ngay_tinh_han')) {
        changeNgayTinhHan();
    }
    
    // Khởi tạo giá trị completedCount và totalCriteria
    let completedCount = <?php echo $completed_tieuchi; ?>;
    const totalCriteria = <?php echo $total_tieuchi; ?>;
    let totalPoints = 0;
    const maxPoints = <?php echo $max_possible_points; ?>;
    
    // Cập nhật trạng thái checkbox ban đầu
    document.querySelectorAll('.diem-dropdown').forEach(element => {
        const diem = parseFloat(element.value);
        if (diem > 0) {
            const tieuchi_id = element.getAttribute('data-tieuchi-id');
            const hiddenField = document.getElementById('da_thuchien_' + tieuchi_id);
            if (hiddenField) {
                hiddenField.value = 1;
            }
        }
    });
    
    // Cập nhật tổng điểm khi trang được tải
    updateTotalPoints();
    
    // Kiểm tra tiêu chí 131 (tiêu chí số 5 của Kho Phụ Liệu)
    <?php if ($dept == 'kho'): ?>
    // Lấy select box cho tiêu chí 131 - sửa lại selector cho chính xác
    const select131 = document.querySelector('select[data-tieuchi-id="131"]');
    
    if (select131) {
        // Thêm event listener để kiểm tra mỗi khi thay đổi giá trị
        select131.addEventListener('change', function() {
            const selectedValue = parseFloat(this.value);
            if (selectedValue > 0) {
                checkImageForTieuchi(131, this);
            }
        });
        
        // Kiểm tra ngay khi trang load
        checkImageForTieuchi(131, select131);
        
        // Thêm ghi chú cho tiêu chí này
        const tieuchiRow = select131.closest('tr');
        if (tieuchiRow) {
            const noteColumn = tieuchiRow.querySelector('td:last-child');
            if (noteColumn) {
                /*
                const noteText = document.createElement('div');
                noteText.className = 'image-required-warning';
                noteText.innerHTML = `
                    <div style="margin-bottom: 8px;">
                        <strong style="color:rgb(38, 0, 255); font-size: 12px;">(*) Tiêu chí này bắt buộc phải có hình ảnh đính kèm</strong>
                    </div>
                    <a href="image_handler.php?dept=<?php echo $dept; ?>&id=<?php echo $id; ?>" 
                       class="upload-image-btn">
                        <i class="fas fa-upload" style="margin-right: 5px;"></i>
                        Upload hình ảnh
                    </a>
                `;
                
                // Thêm style cho nút upload
                noteColumn.prepend(noteText);
                */
                
                // Thêm style cho nút upload
                const style = document.createElement('style');
                style.textContent = `
                    .upload-image-btn {
                        display: inline-flex;
                        align-items: center;
                        padding: 6px 12px;
                        background-color: #1976d2;
                        color: white;
                        text-decoration: none;
                        border-radius: 4px;
                        font-size: 12px;
                        transition: all 0.3s ease;
                        border: none;
                        cursor: pointer;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    
                    .upload-image-btn:hover {
                        background-color: #1565c0;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                        transform: translateY(-1px);
                    }
                    
                    .upload-image-btn:active {
                        transform: translateY(0);
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    
                    .image-required-warning {
                        margin-bottom: 5px;
                        padding: 8px;
                        background-color: #fff3f3;
                        border-radius: 4px;
                        border: 1px solid #ffcdd2;
                    }
                `;
                document.head.appendChild(style);
            }
        }
    }
    <?php endif; ?>
});

// Hàm cập nhật thông tin người thực hiện
function updateStaff(staffId) {
    const staffName = document.getElementById('staff_name_' + staffId).value.trim();
    const staffPosition = document.getElementById('staff_position_' + staffId).value.trim();
    const dept = document.getElementById('current_dept').value;
    const statusDiv = document.getElementById('staff_status');
    
    if (!staffName) {
        alert('Vui lòng nhập tên người chịu trách nhiệm!');
        return;
    }
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang cập nhật thông tin...</div>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_staff.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Hiển thị thông báo thành công
                        statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã cập nhật thông tin thành công!</div>';
                        
                        // Highlight dòng vừa cập nhật
                        const row = document.getElementById('staff_row_' + staffId);
                        if (row) {
                            row.style.backgroundColor = '#d4edda';
                            setTimeout(function() {
                                row.style.backgroundColor = '';
                            }, 2000);
                        }
                        
                        // Cập nhật danh sách nhân viên để đảm bảo các thay đổi được hiển thị
                        loadStaffList(dept);
                        
                        // Ẩn thông báo sau 3 giây
                        setTimeout(function() {
                            statusDiv.style.display = 'none';
                        }, 3000);
                    } else {
                        // Hiển thị thông báo lỗi
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                    }
                } catch (e) {
                    console.error(e);
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                }
            } else {
                statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
            }
        }
    };
    xhr.send('action=update&id=' + encodeURIComponent(staffId) + '&ten=' + encodeURIComponent(staffName) + '&chuc_vu=' + encodeURIComponent(staffPosition));
}

// Hàm xóa người thực hiện
function deleteStaff(staffId) {
    if (!confirm('Bạn có chắc chắn muốn xóa người chịu trách nhiệm này?')) {
        return;
    }
    
    const dept = document.getElementById('current_dept').value;
    const statusDiv = document.getElementById('staff_status');
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang xóa người chịu trách nhiệm...</div>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_staff.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Hiển thị thông báo thành công
                        statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã xóa người chịu trách nhiệm thành công!</div>';
                        
                        // Tải lại danh sách
                        loadStaffList(dept);
                        
                        // Ẩn thông báo sau 3 giây
                        setTimeout(function() {
                            statusDiv.style.display = 'none';
                        }, 3000);
                    } else {
                        // Hiển thị thông báo lỗi
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                    }
                } catch (e) {
                    console.error(e);
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                }
            } else {
                statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
            }
        }
    };
    xhr.send('action=delete&id=' + encodeURIComponent(staffId));
}

// Hàm đóng modal quản lý người thực hiện
function closeStaffModal() {
    document.getElementById('staffModal').style.display = 'none';
}

// Hàm thay đổi xưởng được chọn
function changeSelectedXuong() {
    const selectedXuong = document.getElementById('selected_xuong').value;
    const displayNameElement = document.getElementById('xuong_display_name');
    
    if (selectedXuong) {
        displayNameElement.textContent = selectedXuong;
    } else {
        displayNameElement.textContent = 'Tất cả xưởng';
    }
    
    // Tải lại dữ liệu cài đặt mặc định cho xưởng đã chọn
    loadDefaultSettings(document.getElementById('current_dept').value, selectedXuong);
}

// Hàm tải dữ liệu cài đặt mặc định theo xưởng
function loadDefaultSettings(dept, xuong) {
    const xhr = new XMLHttpRequest();
    const url = 'get_default_settings.php?dept=' + encodeURIComponent(dept) + '&xuong=' + encodeURIComponent(xuong || '');
    
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        updateDefaultSettingsUI(response.data);
                    } else {
                        alert('Lỗi: ' + response.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Lỗi khi xử lý phản hồi từ máy chủ.');
                }
            } else {
                alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
            }
        }
    };
    xhr.send();
}

// Hàm cập nhật UI với dữ liệu cài đặt mặc định
function updateDefaultSettingsUI(settings) {
    // Reset về giá trị mặc định trước
    const rows = document.querySelectorAll('#default_settings_tbody tr[id^="ds_row_"]');
    rows.forEach(row => {
        const id_tieuchi = row.id.replace('ds_row_', '');
        
        // Đặt giá trị mặc định
        document.getElementById('ds_ngay_tinh_han_' + id_tieuchi).value = 'ngay_vao';
        document.getElementById('ds_so_ngay_xuly_' + id_tieuchi).value = '7';
        document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi).value = '0';
    });
    
    // Cập nhật giá trị từ settings
    if (settings && settings.length > 0) {
        settings.forEach(setting => {
            const ngayTinhHanElement = document.getElementById('ds_ngay_tinh_han_' + setting.id_tieuchi);
            const soNgayXulyElement = document.getElementById('ds_so_ngay_xuly_' + setting.id_tieuchi);
            const nguoiChiuTrachNhiemElement = document.getElementById('ds_nguoi_chiu_trachnhiem_' + setting.id_tieuchi);
            
            if (ngayTinhHanElement) ngayTinhHanElement.value = setting.ngay_tinh_han;
            if (soNgayXulyElement) soNgayXulyElement.value = setting.so_ngay_xuly;
            if (nguoiChiuTrachNhiemElement) nguoiChiuTrachNhiemElement.value = setting.nguoi_chiu_trachnhiem_default || '0';
        });
    }
}

// Hàm đồng bộ số ngày xử lý từ cài đặt mặc định vào các ô nhập ngày
function syncTieuChiWithDefaultSettings(dept, xuong) {
    const xhr = new XMLHttpRequest();
    const url = 'get_default_settings.php?dept=' + encodeURIComponent(dept) + '&xuong=' + encodeURIComponent(xuong || '');
    
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success && response.data && response.data.length > 0) {
                    // Cập nhật số ngày xử lý cho các ô nhập
                    response.data.forEach(setting => {
                        const soNgayXulyElement = document.getElementById('so_ngay_xuly_' + setting.id_tieuchi);
                        const ngayTinhHanElement = document.getElementById('ngay_tinh_han_' + setting.id_tieuchi);
                        
                        if (soNgayXulyElement) {
                            soNgayXulyElement.value = setting.so_ngay_xuly;
                        }
                        
                        if (ngayTinhHanElement) {
                            ngayTinhHanElement.value = setting.ngay_tinh_han;
                        }
                    });
                }
            } catch (e) {
                console.error('Lỗi khi đồng bộ dữ liệu:', e);
            }
        }
    };
    xhr.send();
}

// Hàm lưu cài đặt mặc định cho một tiêu chí
function saveDefaultSetting(id_tieuchi, dept) {
    const ngayTinhHan = document.getElementById('ds_ngay_tinh_han_' + id_tieuchi).value;
    const soNgayXuly = document.getElementById('ds_so_ngay_xuly_' + id_tieuchi).value;
    const nguoiChiuTrachnhiem = document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi).value;
    const selectedXuong = document.getElementById('selected_xuong').value;
    const statusDiv = document.getElementById('default_settings_status');
    const row = document.getElementById('ds_row_' + id_tieuchi);
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang lưu cài đặt...</div>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_default_setting.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã lưu cài đặt mặc định!</div>';
                        row.style.backgroundColor = '#f8f9fa';
                        setTimeout(function() {
                            row.style.backgroundColor = '';
                            statusDiv.style.display = 'none';
                        }, 2000);
                    } else {
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                    }
                } catch (e) {
                    console.error(e);
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                }
            } else {
                statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
            }
        }
    };
    xhr.send('id_tieuchi=' + id_tieuchi + '&dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong) + '&ngay_tinh_han=' + ngayTinhHan + '&so_ngay_xuly=' + soNgayXuly + '&nguoi_chiu_trachnhiem=' + nguoiChiuTrachnhiem);
}

// Hàm lưu tất cả cài đặt mặc định
function saveAllDefaultSettings(dept) {
    const statusDiv = document.getElementById('default_settings_status');
    const rows = document.querySelectorAll("#default_settings_tbody tr[id^='ds_row_']");
    const selectedXuong = document.getElementById('selected_xuong').value;
    const settings = [];
    
    rows.forEach(row => {
        const id_tieuchi = row.id.replace('ds_row_', '');
        const ngayTinhHan = document.getElementById('ds_ngay_tinh_han_' + id_tieuchi)?.value;
        const soNgayXuly = document.getElementById('ds_so_ngay_xuly_' + id_tieuchi)?.value;
        const nguoiChiuTrachnhiem = document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi)?.value;
        
        if (ngayTinhHan && soNgayXuly) {
            settings.push({
                id_tieuchi: id_tieuchi,
                ngay_tinh_han: ngayTinhHan,
                so_ngay_xuly: soNgayXuly,
                nguoi_chiu_trachnhiem: nguoiChiuTrachnhiem || 0
            });
        }
    });
    
    if (settings.length === 0) {
        alert('Không tìm thấy cài đặt nào để lưu.');
        return;
    }
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang lưu tất cả cài đặt...</div>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_all_default_settings.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã lưu tất cả cài đặt mặc định!</div>';
                        rows.forEach(row => {
                            row.style.backgroundColor = '#f8f9fa';
                            setTimeout(function() {
                                row.style.backgroundColor = '';
                            }, 2000);
                        });
                        
                        setTimeout(function() {
                            statusDiv.style.display = 'none';
                        }, 3000);
                    } else {
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                    }
                } catch (e) {
                    console.error(e);
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                }
            } else {
                statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
            }
        }
    };
    xhr.send('dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong) + '&settings=' + JSON.stringify(settings));
}

// Hàm mở modal cài đặt mặc định
function openDefaultSettingModal() {
    document.getElementById('defaultSettingModal').style.display = 'block';
    document.getElementById('current_dept').value = '<?php echo $dept; ?>';
    document.getElementById('selected_xuong').value = '<?php echo $xuong; ?>';
    changeSelectedXuong();
}

// Hàm áp dụng cài đặt mặc định cho một tiêu chí
function applyDefaultSetting(id_tieuchi, dept) {
    const selectedXuong = document.getElementById('selected_xuong').value;
    const statusDiv = document.getElementById('default_settings_status');
    const row = document.getElementById('ds_row_' + id_tieuchi);
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang áp dụng cài đặt mặc định...</div>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'apply_default_setting.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã áp dụng cài đặt mặc định thành công!</div>';
                        row.style.backgroundColor = '#d4edda';
                        setTimeout(function() {
                            row.style.backgroundColor = '';
                            statusDiv.style.display = 'none';
                        }, 2000);
                    } else {
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                    }
                } catch (e) {
                    console.error(e);
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                }
            } else {
                statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
            }
        }
    };
    xhr.send('id_tieuchi=' + id_tieuchi + '&dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong));
}

// Hàm áp dụng tất cả cài đặt mặc định
function applyAllDefaultSettings(dept) {
    const selectedXuong = document.getElementById('selected_xuong').value;
    const statusDiv = document.getElementById('default_settings_status');
    const tableBody = document.getElementById('default_settings_tbody');
    
    if (!confirm('Bạn có chắc chắn muốn áp dụng tất cả cài đặt mặc định cho ' + 
                (selectedXuong ? 'xưởng ' + selectedXuong : 'tất cả xưởng') + ' không?')) {
        return;
    }
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang áp dụng tất cả cài đặt mặc định...</div>';
    
    const rows = tableBody.querySelectorAll('tr[id^="ds_row_"]');
    let completedCount = 0;
    let errorCount = 0;
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const id_tieuchi = row.id.replace('ds_row_', '');
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'apply_default_setting.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            completedCount++;
                            row.style.backgroundColor = '#d4edda';
                            setTimeout(function() {
                                row.style.backgroundColor = '';
                            }, 2000);
                        } else {
                            errorCount++;
                            row.style.backgroundColor = '#f8d7da';
                            setTimeout(function() {
                                row.style.backgroundColor = '';
                            }, 2000);
                        }
                        
                        // Kiểm tra nếu đã hoàn thành tất cả
                        if (completedCount + errorCount === rows.length) {
                            if (errorCount === 0) {
                                statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã áp dụng tất cả cài đặt mặc định thành công!</div>';
                            } else {
                                statusDiv.innerHTML = '<div style="color: #856404; padding: 10px; background-color: #fff3cd; border-radius: 4px;">Đã áp dụng ' + completedCount + '/' + rows.length + ' cài đặt mặc định. Có ' + errorCount + ' lỗi.</div>';
                            }
                            
                            setTimeout(function() {
                                statusDiv.style.display = 'none';
                            }, 3000);
                        }
                    } catch (e) {
                        console.error(e);
                        errorCount++;
                    }
                } else {
                    errorCount++;
                }
            }
        };
        xhr.send('id_tieuchi=' + id_tieuchi + '&dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong));
    }
}

// Thêm gọi hàm đồng bộ khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    // Comment dòng này lại để không tự động đồng bộ khi load trang
    // syncTieuChiWithDefaultSettings('<?php echo $dept; ?>', '<?php echo $xuong; ?>');
});

// Hàm kiểm tra có hình ảnh cho tiêu chí hay không
function checkImageForTieuchi(tieuchiId, selectElement) {
    console.log("Kiểm tra hình ảnh cho tiêu chí: " + tieuchiId);
    var warningDiv = document.getElementById('warning-tieuchi-' + tieuchiId);
    
    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'warning-tieuchi-' + tieuchiId;
        warningDiv.className = 'warning-message';
        selectElement.parentNode.appendChild(warningDiv);
    }
    
    // AJAX kiểm tra xem tiêu chí này đã có hình ảnh chưa
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax_check_tieuchi_image.php?id_khsanxuat=<?php echo $id; ?>&id_tieuchi=' + tieuchiId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            
            if (response.success) {
                if (!response.has_image) {
                    // Tạo URL cho liên kết upload hình ảnh sử dụng tham số tự động chọn tiêu chí
                    var uploadUrl = '<?php echo createAutoSelectImageURL($id, $dept, "'+tieuchiId+'"); ?>';
                    
                    // Hiển thị cảnh báo và liên kết để upload hình ảnh
                    warningDiv.innerHTML = '<div style="color: red; margin-top: 5px; font-size: 14px;">' +
                        '(*) Tiêu chí này bắt buộc phải có hình ảnh đính kèm' +
                        '</div>' +
                        '<div style="margin-top: 5px;"><a href="' + uploadUrl + '" style="color: blue; text-decoration: underline;"><i class="fas fa-upload" style="margin-right: 5px;"></i>Upload ảnh</a></div>';
                    
                    // Nếu có điểm > 0, reset về 0
                    if (parseFloat(selectElement.value) > 0) {
                        selectElement.value = '0';
                        
                        // Cập nhật trạng thái checkbox thành X đỏ
                        const checkbox = document.getElementById('checkbox_' + tieuchiId);
                        const label = document.getElementById('checkbox_label_' + tieuchiId);
                        const hiddenField = document.getElementById('da_thuchien_' + tieuchiId);
                        
                        if (label) {
                            label.classList.remove('checked');
                            label.classList.add('unchecked');
                            label.innerHTML = '<span class="checkmark">X</span>';
                        }
                        if (hiddenField) {
                            hiddenField.value = '0';
                        }
                    }
                    
                    // Disable các lựa chọn có giá trị lớn hơn 0 (trừ khi là 999)
                    for (var i = 0; i < selectElement.options.length; i++) {
                        var optionValue = parseInt(selectElement.options[i].value);
                        if (optionValue > 0 && optionValue !== 999) {
                            selectElement.options[i].disabled = true;
                        }
                    }
                } else {
                    // Đã có hình ảnh, hiển thị thông báo thành công và enable tất cả lựa chọn
                    var uploadUrl = '<?php echo createAutoSelectImageURL($id, $dept, "'+tieuchiId+'"); ?>';
                    warningDiv.innerHTML = '<div style="color: #28a745; margin-top: 5px; font-size: 14px;">' +
                        '<i class="fas fa-check-circle" style="margin-right: 5px;"></i>Đã upload hình ảnh cho tiêu chí này' +
                        '</div>' +
                        '<div style="margin-top: 5px;"><a href="' + uploadUrl + '" style="color: blue; text-decoration: underline;"><i class="fas fa-images" style="margin-right: 5px;"></i>Xem/Quản lý hình ảnh</a></div>';
                    
                    for (var i = 0; i < selectElement.options.length; i++) {
                        selectElement.options[i].disabled = false;
                    }
                }
            }
        }
    };
    xhr.send();
}

// Thêm đoạn code khởi tạo khi trang load
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra tất cả các tiêu chí bắt buộc hình ảnh khi load trang
    const allSelects = document.querySelectorAll('select[data-tieuchi-id]');
    allSelects.forEach(select => {
        const tieuchiId = select.getAttribute('data-tieuchi-id');
        if (isRequiredImageCriteria(tieuchiId)) {
            checkImageForTieuchi(tieuchiId, select);
        }
    });
});

// Thêm hàm kiểm tra tiêu chí bắt buộc hình ảnh
function isRequiredImageCriteria(tieuchiId) {
    const requiredCriteria = <?php echo json_encode(getRequiredImagesCriteria($connect, $dept)); ?>;
    return requiredCriteria.includes(parseInt(tieuchiId));
}
</script>
</body>
</html>

<!-- Thêm vào cuối file, trước đóng </body> -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tìm form đánh giá
    const danhgiaForm = document.querySelector('form[name="danhgia_form"]');
    
    if (danhgiaForm) {
        danhgiaForm.addEventListener('submit', function(e) {
            // Lấy danh sách tiêu chí bắt buộc hình ảnh từ PHP đã tạo ở trên
            const requiredCriteria = <?php echo json_encode(getRequiredImagesCriteria($connect, $dept)); ?>;
            let hasError = false;
            let firstErrorTieuchiId = null;
            
            // Kiểm tra từng tiêu chí bắt buộc hình ảnh
            for (let i = 0; i < requiredCriteria.length; i++) {
                const tieuchiId = requiredCriteria[i];
                const diemInput = document.querySelector('input[name="diem[' + tieuchiId + ']"]');
                const diemSelect = document.querySelector('select[data-tieuchi-id="' + tieuchiId + '"]');
                
                // Lấy giá trị điểm, ưu tiên từ input (nếu có), nếu không thì từ select
                let diemValue = 0;
                if (diemInput) {
                    diemValue = parseFloat(diemInput.value) || 0;
                } else if (diemSelect) {
                    diemValue = parseFloat(diemSelect.value) || 0;
                }
                
                // Chỉ kiểm tra tiêu chí được đánh giá (điểm > 0)
                if (diemValue > 0 && diemValue !== 999) {
                    // Kiểm tra AJAX xem có hình ảnh chưa (sử dụng AJAX đồng bộ để đảm bảo kiểm tra xong trước khi tiếp tục)
                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', 'ajax_check_tieuchi_image.php?id_khsanxuat=<?php echo $id; ?>&id_tieuchi=' + tieuchiId, false);
                    xhr.send();
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success && !response.has_image) {
                            hasError = true;
                            if (!firstErrorTieuchiId) {
                                firstErrorTieuchiId = tieuchiId;
                            }
                        }
                    } catch (error) {
                        console.error('Lỗi khi kiểm tra hình ảnh cho tiêu chí ' + tieuchiId + ':', error);
                    }
                }
            }
            
            // Nếu có lỗi, hiển thị thông báo và chuyển hướng đến trang upload hình ảnh
            if (hasError && firstErrorTieuchiId) {
                e.preventDefault(); // Ngăn form submit
                // Tạo URL với tham số tự động chọn tiêu chí
                const uploadUrl = '<?php echo createAutoSelectImageURL($id, $dept, "'+firstErrorTieuchiId+'"); ?>';
                alert('Bạn cần đính kèm hình ảnh cho tiêu chí ID ' + firstErrorTieuchiId + ' trước khi cập nhật điểm đánh giá.');
                window.location.href = uploadUrl;
                return false;
            }
        });
    }
});
</script>
<script src="assets/js/header.js"></script>
</body>
</html>