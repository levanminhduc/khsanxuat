<?php
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../security/csrf-helper.php';
require_once __DIR__ . '/score-options.php';
require_once BASE_PATH . '/includes/check_tieuchi_image.php';
require_once BASE_PATH . '/includes/indexdept/config.php';

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

// Placeholder for next chunk
// Lấy thutu của tiêu chí để validate score options
$sql_tc = "SELECT thutu FROM tieuchi_dept WHERE id = ? AND dept = ?";
$stmt_tc = $connect->prepare($sql_tc);
$stmt_tc->bind_param("is", $id_tieuchi, $dept);
$stmt_tc->execute();
$result_tc = $stmt_tc->get_result();

if ($result_tc->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tiêu chí không tồn tại']);
    exit;
}

$thutu = $result_tc->fetch_assoc()['thutu'];

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
$required_image_criteria = array_flip(array_map('intval', getRequiredImagesCriteria($connect, $dept)));
if ($diem_danhgia > 0 && isset($required_image_criteria[$id_tieuchi])) {
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

// Kiểm tra record đã tồn tại chưa
$sql_check = "SELECT id FROM danhgia_tieuchi WHERE id_sanxuat = ? AND id_tieuchi = ?";
$stmt_check = $connect->prepare($sql_check);
$stmt_check->bind_param("ii", $id_sanxuat, $id_tieuchi);
$stmt_check->execute();
$exists = $stmt_check->get_result()->num_rows > 0;

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

echo json_encode(['success' => true, 'message' => 'Đã lưu', 'score' => $diem_danhgia]);
