<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

include 'db_connect.php';
require_once 'includes/security/csrf-helper.php';
require_once 'includes/indexdept/score-options.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function jsonResponse($success, $message, array $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

function assertScoreOptionsTableExists($connect) {
    if (!scoreOptionsTableExists($connect)) {
        throw new Exception('Bảng tieuchi_score_options chưa tồn tại');
    }
}

function getCriteriaForScoreOptions($connect, $id_tieuchi, $dept) {
    $sql = "SELECT id, dept, thutu, noidung FROM tieuchi_dept WHERE id = ? AND dept = ?";
    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Không thể chuẩn bị truy vấn tiêu chí: ' . $connect->error);
    }

    $stmt->bind_param("is", $id_tieuchi, $dept);
    $stmt->execute();
    $criteria = $stmt->get_result()->fetch_assoc();

    if (!$criteria) {
        throw new Exception('Tiêu chí không thuộc bộ phận hiện tại');
    }

    return $criteria;
}

function parseScoreValuesInput($scores_input) {
    $scores_input = trim((string) $scores_input);

    if ($scores_input === '') {
        throw new Exception('Vui lòng nhập ít nhất một mốc điểm');
    }

    $parts = preg_split('/[,\s;]+/', $scores_input);
    $scores = [];

    foreach ($parts as $part) {
        $part = trim($part);

        if ($part === '') {
            continue;
        }

        if (!is_numeric($part)) {
            throw new Exception("Mốc điểm không hợp lệ: {$part}");
        }

        $score = (float) $part;

        if ($score < 0 || $score > 999.99) {
            throw new Exception('Mốc điểm phải nằm trong khoảng 0 đến 999.99');
        }

        $scores[formatScoreOptionValue($score)] = $score;
    }

    uasort($scores, function($left, $right) {
        if ($left == $right) {
            return 0;
        }

        return ($left < $right) ? -1 : 1;
    });

    if (empty($scores)) {
        throw new Exception('Vui lòng nhập ít nhất một mốc điểm');
    }

    if (count($scores) > 12) {
        throw new Exception('Mỗi tiêu chí chỉ nên có tối đa 12 mốc điểm');
    }

    return array_values($scores);
}

function replaceScoreOptions($connect, $id_tieuchi, array $scores) {
    $delete_sql = "DELETE FROM tieuchi_score_options WHERE id_tieuchi = ?";
    $delete_stmt = $connect->prepare($delete_sql);

    if (!$delete_stmt) {
        throw new Exception('Không thể chuẩn bị xóa mốc điểm: ' . $connect->error);
    }

    $delete_stmt->bind_param("i", $id_tieuchi);
    $delete_stmt->execute();

    $insert_sql = "INSERT INTO tieuchi_score_options (id_tieuchi, score_value, label, sort_order)
                   VALUES (?, ?, ?, ?)";
    $insert_stmt = $connect->prepare($insert_sql);

    if (!$insert_stmt) {
        throw new Exception('Không thể chuẩn bị lưu mốc điểm: ' . $connect->error);
    }

    $sort_order = 1;
    foreach ($scores as $score) {
        $label = formatScoreOptionValue($score);
        $insert_stmt->bind_param("idsi", $id_tieuchi, $score, $label, $sort_order);
        $insert_stmt->execute();
        $sort_order++;
    }
}

function resetScoreOptions($connect, $id_tieuchi) {
    $sql = "DELETE FROM tieuchi_score_options WHERE id_tieuchi = ?";
    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Không thể chuẩn bị reset mốc điểm: ' . $connect->error);
    }

    $stmt->bind_param("i", $id_tieuchi);
    $stmt->execute();
}

function buildScoreOptionsPayloadFromValues(array $criteria, array $scores, $configured) {
    $values = [];

    foreach ($scores as $score) {
        $values[] = formatScoreOptionValue($score);
    }

    return [
        'id_tieuchi' => (int) $criteria['id'],
        'scores' => implode(', ', $values),
        'configured' => (bool) $configured
    ];
}

function buildDefaultScoreOptionsPayload(array $criteria, $dept) {
    $options = getLegacyScoreOptions($dept, (int) $criteria['thutu']);
    $values = array_column($options, 'value');

    return [
        'id_tieuchi' => (int) $criteria['id'],
        'scores' => implode(', ', $values),
        'configured' => false
    ];
}

if (!$connect) {
    jsonResponse(false, 'Lỗi kết nối database');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Phương thức không hợp lệ');
}

$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!validateCsrfToken($csrf_token)) {
    http_response_code(403);
    jsonResponse(false, 'CSRF token không hợp lệ');
}

$dept = isset($_POST['dept']) ? trim($_POST['dept']) : '';
$action = isset($_POST['action']) ? trim($_POST['action']) : 'save';
$transaction_started = false;

if ($dept === '') {
    jsonResponse(false, 'Thiếu thông tin bộ phận');
}

try {
    assertScoreOptionsTableExists($connect);

    if ($action === 'bulk_save') {
        $settings = isset($_POST['settings']) ? json_decode($_POST['settings'], true) : null;

        if (!is_array($settings) || empty($settings)) {
            throw new Exception('Thiếu danh sách mốc điểm cần lưu');
        }

        $connect->begin_transaction();
        $transaction_started = true;
        $saved_items = [];

        foreach ($settings as $setting) {
            $id_tieuchi = isset($setting['id_tieuchi']) ? (int) $setting['id_tieuchi'] : 0;
            $scores_input = isset($setting['scores']) ? $setting['scores'] : '';
            $criteria = getCriteriaForScoreOptions($connect, $id_tieuchi, $dept);
            $scores = parseScoreValuesInput($scores_input);

            replaceScoreOptions($connect, $id_tieuchi, $scores);
            $saved_items[] = buildScoreOptionsPayloadFromValues($criteria, $scores, true);
        }

        $connect->commit();
        $transaction_started = false;

        jsonResponse(true, 'Đã lưu tất cả mốc điểm', [
            'items' => $saved_items
        ]);
    }

    $id_tieuchi = isset($_POST['id_tieuchi']) ? (int) $_POST['id_tieuchi'] : 0;
    $criteria = getCriteriaForScoreOptions($connect, $id_tieuchi, $dept);

    if ($action === 'reset') {
        resetScoreOptions($connect, $id_tieuchi);
        jsonResponse(true, 'Đã dùng mốc điểm mặc định', buildDefaultScoreOptionsPayload($criteria, $dept));
    }

    $scores_input = isset($_POST['scores']) ? $_POST['scores'] : '';
    $scores = parseScoreValuesInput($scores_input);
    $connect->begin_transaction();
    $transaction_started = true;
    replaceScoreOptions($connect, $id_tieuchi, $scores);
    $connect->commit();
    $transaction_started = false;

    jsonResponse(true, 'Đã lưu mốc điểm', buildScoreOptionsPayloadFromValues($criteria, $scores, true));
} catch (Exception $e) {
    if ($transaction_started) {
        try {
            $connect->rollback();
        } catch (Throwable $rollback_error) {
            error_log('Rollback failed in save_score_options.php: ' . $rollback_error->getMessage());
        }
    }

    jsonResponse(false, $e->getMessage());
}
