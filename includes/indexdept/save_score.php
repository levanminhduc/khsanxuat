<?php
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../bootstrap.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once __DIR__ . '/../security/csrf-helper.php';
require_once __DIR__ . '/score-options.php';
require_once BASE_PATH . '/includes/check_tieuchi_image.php';
require_once BASE_PATH . '/includes/indexdept/config.php';
require_once BASE_PATH . '/helpers/activity_logger.php';

requireLogin('json');

/**
 * CSV id ("3,7,12") → chuỗi tên ("Nguyễn A, Trần B") để ghi log.
 * id ép intval trước khi nội suy IN(...) nên an toàn SQL injection.
 */
function resolveNguoiThuchienNames($connect, $csv)
{
    $ids = array_filter(array_map('intval', explode(',', (string) $csv)));
    if (empty($ids)) {
        return '';
    }

    $res = $connect->query("SELECT ten FROM nhan_vien WHERE id IN (" . implode(',', $ids) . ")");
    if (!$res) {
        return '';
    }

    $names = [];
    while ($row = $res->fetch_assoc()) {
        $names[] = $row['ten'];
    }

    return implode(', ', $names);
}

// CSRF validation (không rotate token để các request tiếp theo vẫn dùng được)
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!validateCsrfToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id_sanxuat = isset($_POST['id_sanxuat']) ? intval($_POST['id_sanxuat']) : 0;
$id_tieuchi = isset($_POST['id_tieuchi']) ? intval($_POST['id_tieuchi']) : 0;
$dept = isset($_POST['dept']) ? $_POST['dept'] : '';
$diem_danhgia = isset($_POST['diem_danhgia']) ? $_POST['diem_danhgia'] : null;

if ($id_sanxuat <= 0 || $id_tieuchi <= 0 || empty($dept) || $diem_danhgia === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']);
    exit;
}

if (!in_array($dept, getValidDepts())) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bộ phận không hợp lệ']);
    exit;
}

// Lấy thutu (validate score options) + noidung (ghi log)
$sql_tc = "SELECT thutu, noidung FROM tieuchi_dept WHERE id = ? AND dept = ?";
$stmt_tc = $connect->prepare($sql_tc);
$stmt_tc->bind_param("is", $id_tieuchi, $dept);
$stmt_tc->execute();
$result_tc = $stmt_tc->get_result();

if ($result_tc->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tiêu chí không tồn tại']);
    exit;
}

$tieuchi = $result_tc->fetch_assoc();
$thutu = $tieuchi['thutu'];
$noidung = $tieuchi['noidung'];

// Validate score
$score_options = getScoreOptionsForCriteria($connect, $id_tieuchi, $dept, $thutu);
if (!isScoreAllowed($score_options, $diem_danhgia)) {
    $allowed = getScoreOptionValuesForMessage($score_options);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Điểm không hợp lệ. Mốc hợp lệ: $allowed"]);
    exit;
}

$diem_danhgia = (float) $diem_danhgia;

// Kiểm tra hình ảnh bắt buộc
// Điểm mặc định auto (mốc điểm không có mốc 0) không do user chấm nên miễn kiểm tra ảnh;
// tiêu chí vẫn nằm trong diện bắt buộc ảnh cho mọi mốc điểm còn lại.
$required_image_criteria = array_flip(array_map('intval', getRequiredImagesCriteria($connect, $dept)));
if ($diem_danhgia > 0
    && isset($required_image_criteria[$id_tieuchi])
    && !isAutoDefaultScore($connect, $id_tieuchi, $dept, $thutu, $diem_danhgia)
) {
    if (!checkTieuchiHasImage($connect, $id_sanxuat, $id_tieuchi)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Cần đính kèm hình ảnh cho tiêu chí $thutu trước khi chấm điểm",
            'require_image' => true
        ]);
        exit;
    }
}

$da_thuchien = ($diem_danhgia > 0) ? 1 : 0;

// Lấy record cũ: vừa để biết INSERT hay UPDATE, vừa lấy giá trị trước khi ghi để log
$sql_check = "SELECT diem_danhgia, nguoi_thuchien, ghichu FROM danhgia_tieuchi WHERE id_sanxuat = ? AND id_tieuchi = ?";
$stmt_check = $connect->prepare($sql_check);
$stmt_check->bind_param("ii", $id_sanxuat, $id_tieuchi);
$stmt_check->execute();
$old_row = $stmt_check->get_result()->fetch_assoc();
$exists = $old_row !== null;

if ($exists) {
    $sql = "UPDATE danhgia_tieuchi SET diem_danhgia = ?, da_thuchien = ? WHERE id_sanxuat = ? AND id_tieuchi = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("diii", $diem_danhgia, $da_thuchien, $id_sanxuat, $id_tieuchi);
} else {
    $sql = "INSERT INTO danhgia_tieuchi (id_sanxuat, id_tieuchi, diem_danhgia, da_thuchien) VALUES (?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("iidi", $id_sanxuat, $id_tieuchi, $diem_danhgia, $da_thuchien);
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu dữ liệu']);
    exit;
}

// Cột diem_danhgia là FLOAT NULL: ép kiểu để bản ghi chưa chấm vẫn ghi log ra "điểm 0".
$old_score = $exists ? (float) $old_row['diem_danhgia'] : 0.0;

// Cùng một cờ quyết định 2 việc: có ghi activity_log không, và client có toast "Đã lưu" không.
$changed = ($old_score !== $diem_danhgia);

// Autosave chỉ đổi điểm; người thực hiện/ghi chú giữ nguyên nên cũ & mới dùng chung giá trị.
if ($changed) {
    $nguoi_thuchien = $exists ? $old_row['nguoi_thuchien'] : '';
    $ghichu = $exists ? $old_row['ghichu'] : '';

    $ten_nguoi = resolveNguoiThuchienNames($connect, $nguoi_thuchien);
    if ($ten_nguoi === '') {
        $ten_nguoi = 'Chưa phân công';
    }
    $suffix = ", người thực hiện {$ten_nguoi}" . (empty($ghichu) ? '' : ", ghi chú: " . $ghichu);

    // Format phải khớp parseTieuchiDiff() trong pages/activity_logs.php — đổi ở đây phải sửa cả bên đó.
    $old_value = "tiêu chí {$thutu}: điểm {$old_score}{$suffix}";
    $new_value = "tiêu chí {$thutu}: điểm {$diem_danhgia}{$suffix}";

    $additional_info = [
        'action' => 'update_score',
        'status' => 'success',
        'changes' => "Thay đổi tiêu chí {$thutu}",
        'changed_tieuchi' => [[
            'thutu' => (int) $thutu,
            'noidung' => $noidung
        ]],
        'dept_info' => [
            'dept_code' => $dept,
            'dept_name' => getDeptDisplayName($dept, $dept_names)
        ]
    ];

    // Đánh giá đã lưu xong: lỗi ghi log không được làm hỏng response.
    $logged = getActivityLogger($connect)->logActivity(
        'update_score',
        'tieuchi',
        $id_tieuchi,
        $id_sanxuat,
        $dept,
        $old_value,
        $new_value,
        $additional_info
    );
    if (!$logged) {
        error_log("save_score: ghi activity_logs that bai (id_sanxuat={$id_sanxuat}, id_tieuchi={$id_tieuchi})");
    }
}

echo json_encode(['success' => true, 'changed' => $changed, 'message' => 'Đã lưu', 'score' => $diem_danhgia]);
