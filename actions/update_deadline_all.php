<?php
// Khởi tạo phiên làm việc
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'update_deadline_debug.log');

// Kết nối database
require_once __DIR__ . '/../bootstrap.php';

require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

requireFeature('edit_settings', 'json');

// CSRF: khong rotate token de cac AJAX tiep theo tren cung trang van hop le
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!validateCsrfToken($csrf_token)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
    exit;
}

header('Content-Type: application/json');

if (!$connect) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit();
}

// Đảm bảo cột lưu hạn xử lý tồn tại (giống update_deadline_tieuchi.php)
try {
    $check_column = $connect->query("SHOW COLUMNS FROM danhgia_tieuchi LIKE 'so_ngay_xuly'");
    if ($check_column->num_rows == 0) {
        $connect->query("ALTER TABLE danhgia_tieuchi ADD COLUMN so_ngay_xuly INT NULL AFTER han_xuly");
    }
    $check_ngay_tinh_han = $connect->query("SHOW COLUMNS FROM danhgia_tieuchi LIKE 'ngay_tinh_han'");
    if ($check_ngay_tinh_han->num_rows == 0) {
        $connect->query("ALTER TABLE danhgia_tieuchi ADD COLUMN ngay_tinh_han VARCHAR(20) DEFAULT 'ngay_vao' AFTER so_ngay_xuly");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kiểm tra/thêm cột: ' . $e->getMessage()]);
    exit();
}

// Kiểm tra dữ liệu gửi lên
if (!isset($_POST['id_sanxuat']) || !isset($_POST['tieuchi']) || !isset($_POST['so_ngay_xuly'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu cần thiết']);
    exit();
}

$id_sanxuat = intval($_POST['id_sanxuat']);
$so_ngay_xuly = intval($_POST['so_ngay_xuly']);
$dept = $_POST['dept'];
$ngay_tinh_han = isset($_POST['ngay_tinh_han']) ? $_POST['ngay_tinh_han'] : 'ngay_vao';

// Danh sách tiêu chí được chọn gửi lên dưới dạng JSON
$tieuchi_list = json_decode($_POST['tieuchi'], true);
if (!is_array($tieuchi_list) || count($tieuchi_list) === 0) {
    echo json_encode(['success' => false, 'message' => 'Chưa chọn tiêu chí để áp dụng']);
    exit();
}

// Giới hạn số ngày xử lý từ 1-30
if ($so_ngay_xuly < 1) $so_ngay_xuly = 1;
if ($so_ngay_xuly > 30) $so_ngay_xuly = 30;

// Lấy ngày vào / ngày ra của đơn để tính hạn xử lý
$sql = "SELECT ngayin, ngayout FROM khsanxuat WHERE stt = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $id_sanxuat);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy bản ghi']);
    exit();
}

$row = $result->fetch_assoc();
$ngayin = new DateTime($row['ngayin']);
$ngayout = new DateTime($row['ngayout']);

// Tính hạn xử lý dựa vào loại ngày được chọn
$han_xuly = null;
if ($ngay_tinh_han === 'ngay_vao') {
    $han_xuly = clone $ngayin;
    $han_xuly->modify("-{$so_ngay_xuly} days");
} else if ($ngay_tinh_han === 'ngay_vao_cong') {
    $han_xuly = clone $ngayin;
    $han_xuly->modify("+{$so_ngay_xuly} days");
} else if ($ngay_tinh_han === 'ngay_ra') {
    $han_xuly = clone $ngayout;
    $han_xuly->modify("+{$so_ngay_xuly} days");
} else if ($ngay_tinh_han === 'ngay_ra_tru') {
    $han_xuly = clone $ngayout;
    $han_xuly->modify("-{$so_ngay_xuly} days");
} else {
    // Loại ngày tính hạn không hợp lệ
    echo json_encode(['success' => false, 'message' => 'Loại ngày tính hạn không hợp lệ']);
    exit();
}

$han_xuly_formatted = $han_xuly->format('Y-m-d');
$han_xuly_display = $han_xuly->format('d/m/Y');

try {
    $connect->begin_transaction();

    $updated_items = [];

    foreach ($tieuchi_list as $id_tieuchi_raw) {
        $id_tieuchi = intval($id_tieuchi_raw);
        if ($id_tieuchi <= 0) {
            continue;
        }

        // Kiểm tra đã có bản ghi đánh giá tiêu chí chưa
        $sql_check = "SELECT id FROM danhgia_tieuchi WHERE id_sanxuat = ? AND id_tieuchi = ?";
        $stmt_check = $connect->prepare($sql_check);
        $stmt_check->bind_param("ii", $id_sanxuat, $id_tieuchi);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $sql_update = "UPDATE danhgia_tieuchi SET han_xuly = ?, so_ngay_xuly = ?, ngay_tinh_han = ? WHERE id_sanxuat = ? AND id_tieuchi = ?";
            $stmt_update = $connect->prepare($sql_update);
            $stmt_update->bind_param("sisii", $han_xuly_formatted, $so_ngay_xuly, $ngay_tinh_han, $id_sanxuat, $id_tieuchi);
            if (!$stmt_update->execute()) {
                throw new Exception("Lỗi khi cập nhật tiêu chí $id_tieuchi: " . $stmt_update->error);
            }
        } else {
            // Lấy người thực hiện mặc định (nếu có) cho tiêu chí này
            $sql_nguoi_thuchien = "SELECT nguoi_chiu_trachnhiem_default FROM khsanxuat_default_settings WHERE id_tieuchi = ? AND dept = ?";
            $stmt_nguoi_thuchien = $connect->prepare($sql_nguoi_thuchien);
            $stmt_nguoi_thuchien->bind_param("is", $id_tieuchi, $dept);
            $stmt_nguoi_thuchien->execute();
            $result_nguoi_thuchien = $stmt_nguoi_thuchien->get_result();

            $nguoi_thuchien = null;
            if ($result_nguoi_thuchien->num_rows > 0) {
                $row_nguoi = $result_nguoi_thuchien->fetch_assoc();
                $nguoi_thuchien = $row_nguoi['nguoi_chiu_trachnhiem_default'];
            }

            if ($nguoi_thuchien) {
                $sql_insert = "INSERT INTO danhgia_tieuchi (id_sanxuat, id_tieuchi, han_xuly, so_ngay_xuly, ngay_tinh_han, nguoi_thuchien) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert = $connect->prepare($sql_insert);
                $stmt_insert->bind_param("iisisi", $id_sanxuat, $id_tieuchi, $han_xuly_formatted, $so_ngay_xuly, $ngay_tinh_han, $nguoi_thuchien);
            } else {
                $sql_insert = "INSERT INTO danhgia_tieuchi (id_sanxuat, id_tieuchi, han_xuly, so_ngay_xuly, ngay_tinh_han) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = $connect->prepare($sql_insert);
                $stmt_insert->bind_param("iisis", $id_sanxuat, $id_tieuchi, $han_xuly_formatted, $so_ngay_xuly, $ngay_tinh_han);
            }

            if (!$stmt_insert->execute()) {
                throw new Exception("Lỗi khi thêm mới tiêu chí $id_tieuchi: " . $stmt_insert->error);
            }
        }

        $updated_items[] = [
            'id_tieuchi' => $id_tieuchi,
            'new_date'   => $han_xuly_display
        ];
    }

    $connect->commit();

    echo json_encode([
        'success'       => true,
        'message'       => 'Cập nhật hạn xử lý thành công',
        'updated_count' => count($updated_items),
        'updated_items' => $updated_items,
        'so_ngay_xuly'  => $so_ngay_xuly,
        'ngay_tinh_han' => $ngay_tinh_han
    ]);
    exit();

} catch (Exception $e) {
    if ($connect->ping()) {
        $connect->rollback();
    }
    file_put_contents('update_deadline_debug.log', "Lỗi batch: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
