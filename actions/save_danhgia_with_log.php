<?php
// Production: disable error display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Kết nối database và logger
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/helpers/activity_logger.php';
require_once BASE_PATH . '/includes/check_tieuchi_image.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once BASE_PATH . '/includes/indexdept/score-options.php';
require_once BASE_PATH . '/includes/indexdept/config.php';

// Khởi tạo phiên làm việc
session_start();

requireLogin();

// Validate CSRF token
verifyCsrfOrDie();

// Log hoat dong cho moi user da login (audit khong gate theo role)
$can_log = isLoggedIn();

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database");
}

// Lấy logger
$logger = getActivityLogger($connect);

/**
 * CSV id ("3,7,12") → chuỗi tên ("Nguyễn A, Trần B") để ghi log.
 * id ép intval trước khi nội suy IN(...) nên an toàn SQL injection.
 */
function resolveNamesFromCsv($connect, $csv)
{
    $ids = array_filter(array_map('intval', explode(',', (string) $csv)));
    if (empty($ids)) {
        return '';
    }
    $in = implode(',', $ids);
    $res = $connect->query("SELECT ten FROM nhan_vien WHERE id IN ($in)");
    $names = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $names[] = $r['ten'];
        }
    }
    return implode(', ', $names);
}

/**
 * Chuẩn hoá chuỗi CSV id (loại trùng/rỗng, sort tăng dần) để so sánh nhất quán.
 */
function normalizeCsvIds($csv)
{
    $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $csv)))));
    sort($ids);
    return implode(',', $ids);
}


// dept + id_sanxuat lấy từ form POST. Reject nếu thiếu/không hợp lệ thay vì đoán bừa (tránh ghi nhầm bộ phận).
$dept = isset($_POST['dept']) ? $_POST['dept'] : '';
if (empty($dept) || !in_array($dept, getValidDepts())) {
    die("Bộ phận không hợp lệ");
}

$id_sanxuat = isset($_POST['id_sanxuat']) ? intval($_POST['id_sanxuat']) : 0;
if ($id_sanxuat <= 0) {
    die("Thiếu thông tin cần thiết");
}

$required_image_criteria = array_flip(array_map('intval', getRequiredImagesCriteria($connect, $dept)));

// Lấy dữ liệu cũ để so sánh
$old_data = [];
$sql_old = "SELECT dt.id_tieuchi, dt.diem_danhgia, dt.nguoi_thuchien, dt.ghichu 
            FROM danhgia_tieuchi dt 
            WHERE dt.id_sanxuat = ?";
$stmt_old = $connect->prepare($sql_old);
$stmt_old->bind_param("i", $id_sanxuat);
$stmt_old->execute();
$result_old = $stmt_old->get_result();

while ($row = $result_old->fetch_assoc()) {
    $old_data[$row['id_tieuchi']] = $row;
}

// Bắt đầu transaction
$connect->begin_transaction();

try {
    // Khởi tạo mảng để lưu tất cả thay đổi
    $all_changes = [];
    $all_old_values = [];
    $all_new_values = [];
    $changed_tieuchi_info = [];

    // Lưu đánh giá cho từng tiêu chí
    $sql = "SELECT id, thutu, noidung FROM tieuchi_dept WHERE dept = ? ORDER BY thutu";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $id_tieuchi = $row['id'];
        $thutu = $row['thutu'];
        $noidung = $row['noidung'];
        
        // Kiểm tra các thay đổi
        $diem_danhgia = isset($_POST['diem_danhgia_' . $id_tieuchi]) ? $_POST['diem_danhgia_' . $id_tieuchi] : null;
        // Người chịu trách nhiệm: nhiều người dạng mảng checkbox → CSV id.
        // Marker present để phân biệt "đã submit (kể cả bỏ hết)" với "không submit dòng này".
        if (isset($_POST['nguoi_thuchien_present_' . $id_tieuchi])) {
            $nguoi_ids = $_POST['nguoi_thuchien_' . $id_tieuchi] ?? [];
            $nguoi_ids = array_values(array_unique(array_filter(array_map('intval', (array) $nguoi_ids))));
            sort($nguoi_ids);
            $nguoi_thuchien = implode(',', $nguoi_ids); // '' nếu bỏ chọn hết → xoá hết
        } else {
            $nguoi_thuchien = null; // không submit → giữ giá trị cũ qua COALESCE
        }
        $ghichu = isset($_POST['ghichu_' . $id_tieuchi]) ? $_POST['ghichu_' . $id_tieuchi] : null;

        if ($diem_danhgia !== null) {
            $score_options = getScoreOptionsForCriteria($connect, $id_tieuchi, $dept, $thutu);

            if (!isScoreAllowed($score_options, $diem_danhgia)) {
                $allowed_scores = getScoreOptionValuesForMessage($score_options);
                throw new Exception("Điểm đánh giá không hợp lệ cho tiêu chí {$thutu}. Mốc hợp lệ: {$allowed_scores}");
            }

            $diem_danhgia = (float) $diem_danhgia;

            if (
                $diem_danhgia > 0
                && isset($required_image_criteria[(int) $id_tieuchi])
                && !checkTieuchiHasImage($connect, $id_sanxuat, (int) $id_tieuchi)
            ) {
                throw new Exception("Bạn cần đính kèm hình ảnh cho tiêu chí {$thutu} trước khi cập nhật điểm đánh giá.");
            }
        }
        $da_thuchien = ($diem_danhgia !== null && $diem_danhgia > 0) ? 1 : 0;
        
        // Kiểm tra xem có dữ liệu cũ không
        $has_old_data = isset($old_data[$id_tieuchi]);
        
        // Cập nhật hoặc thêm mới đánh giá
        if ($diem_danhgia !== null || $nguoi_thuchien !== null || $ghichu !== null) {
            if ($has_old_data) {
                $sql_update = "UPDATE danhgia_tieuchi SET 
                              diem_danhgia = COALESCE(?, diem_danhgia),
                              da_thuchien = ?,
                              nguoi_thuchien = COALESCE(?, nguoi_thuchien),
                              ghichu = COALESCE(?, ghichu)
                              WHERE id_sanxuat = ? AND id_tieuchi = ?";
                $stmt_update = $connect->prepare($sql_update);
                $stmt_update->bind_param("dissii", $diem_danhgia, $da_thuchien, $nguoi_thuchien, $ghichu, $id_sanxuat, $id_tieuchi);
            } else {
                $sql_insert = "INSERT INTO danhgia_tieuchi (id_sanxuat, id_tieuchi, diem_danhgia, da_thuchien, nguoi_thuchien, ghichu) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert = $connect->prepare($sql_insert);
                $stmt_insert->bind_param("iidiss", $id_sanxuat, $id_tieuchi, $diem_danhgia, $da_thuchien, $nguoi_thuchien, $ghichu);
            }
            
            // Thực thi câu lệnh SQL
            $stmt_to_execute = $has_old_data ? $stmt_update : $stmt_insert;
            if (!$stmt_to_execute->execute()) {
                throw new Exception("Lỗi khi " . ($has_old_data ? "cập nhật" : "thêm mới") . " đánh giá: " . $stmt_to_execute->error);
            }

            // Thu thập thay đổi nếu có dữ liệu cũ
            if ($has_old_data) {
                $old_score = $old_data[$id_tieuchi]['diem_danhgia'];
                $old_person = $old_data[$id_tieuchi]['nguoi_thuchien'];
                $old_note = $old_data[$id_tieuchi]['ghichu'];
                
                if ($diem_danhgia !== null && $diem_danhgia != $old_score) {
                    // Lấy tên người thực hiện (hỗ trợ nhiều người dạng CSV)
                    $old_name = resolveNamesFromCsv($connect, $old_person);
                    if ($old_name === '') {
                        $old_name = 'Chưa phân công';
                    }
                    $new_name = resolveNamesFromCsv($connect, $nguoi_thuchien);
                    if ($new_name === '') {
                        $new_name = 'Chưa phân công';
                    }

                    $all_changes[] = "tiêu chí {$thutu}";
                    $all_old_values[] = "tiêu chí {$thutu}: điểm {$old_score}, người thực hiện {$old_name}" . 
                                       (empty($old_note) ? "" : ", ghi chú: " . $old_note);
                    $all_new_values[] = "tiêu chí {$thutu}: điểm {$diem_danhgia}, người thực hiện {$new_name}" . 
                                       (empty($ghichu) ? "" : ", ghi chú: " . $ghichu);
                    $changed_tieuchi_info[] = [
                        'thutu' => $thutu,
                        'noidung' => $noidung
                    ];
                }
                // Kiểm tra nếu chỉ có người thực hiện thay đổi (so sánh CSV đã chuẩn hoá)
                else if ($nguoi_thuchien !== null && normalizeCsvIds($nguoi_thuchien) != normalizeCsvIds($old_person)) {
                    // Lấy tên người thực hiện cũ & mới (hỗ trợ nhiều người dạng CSV)
                    $old_name = resolveNamesFromCsv($connect, $old_person);
                    if ($old_name === '') {
                        $old_name = 'Chưa phân công';
                    }
                    $new_name = resolveNamesFromCsv($connect, $nguoi_thuchien);
                    if ($new_name === '') {
                        $new_name = 'Chưa phân công';
                    }

                    $all_changes[] = "tiêu chí {$thutu} (người thực hiện)";
                    $all_old_values[] = "tiêu chí {$thutu}: điểm {$old_score}, người thực hiện {$old_name}" . 
                                       (empty($old_note) ? "" : ", ghi chú: " . $old_note);
                    $all_new_values[] = "tiêu chí {$thutu}: điểm {$old_score}, người thực hiện {$new_name}" . 
                                       (empty($ghichu) ? "" : ", ghi chú: " . $ghichu);
                    $changed_tieuchi_info[] = [
                        'thutu' => $thutu,
                        'noidung' => $noidung
                    ];
                }
                // Kiểm tra nếu chỉ có ghi chú thay đổi
                else if ($ghichu != $old_note) {
                    $old_name = resolveNamesFromCsv($connect, $old_person);
                    if ($old_name === '') {
                        $old_name = 'Chưa phân công';
                    }

                    $all_changes[] = "tiêu chí {$thutu} (ghi chú)";
                    $all_old_values[] = "tiêu chí {$thutu}: điểm {$old_score}, người thực hiện {$old_name}" . 
                                       (empty($old_note) ? "" : ", ghi chú: " . $old_note);
                    $all_new_values[] = "tiêu chí {$thutu}: điểm {$old_score}, người thực hiện {$old_name}" . 
                                       (empty($ghichu) ? "" : ", ghi chú: " . $ghichu);
                    $changed_tieuchi_info[] = [
                        'thutu' => $thutu,
                        'noidung' => $noidung
                    ];
                }
            }
        }
    }
    
    // Ghi log một lần duy nhất nếu có thay đổi
    if (!empty($all_changes) && $can_log) {
        $change_description = "Thay đổi " . implode(", ", $all_changes);
        $old_value = implode(" | ", $all_old_values);
        $new_value = implode(" | ", $all_new_values);
        
        try {
            $additional_info = [
                'action' => 'update_multiple',
                'status' => 'success',
                'changes' => $change_description,
                'changed_tieuchi' => $changed_tieuchi_info,
                'dept_info' => [
                    'dept_code' => $dept,
                    'dept_name' => $dept_names[$dept] ?? $dept
                ]
            ];
            
            // Thêm ghi chú nếu có
            $ghichu_values = array_filter($_POST, function($key) {
                return strpos($key, 'ghichu_') === 0 && !empty($_POST[$key]);
            }, ARRAY_FILTER_USE_KEY);
            
            if (!empty($ghichu_values)) {
                // Tách ghi chú riêng cho từng tiêu chí
                $note_entries = [];
                foreach ($ghichu_values as $key => $value) {
                    $id_tc = str_replace('ghichu_', '', $key);
                    // Lấy thông tin tiêu chí
                    $tc_info = $connect->prepare("SELECT thutu, noidung FROM tieuchi_dept WHERE id = ?");
                    $tc_info->bind_param("i", $id_tc);
                    $tc_info->execute();
                    $tc_result = $tc_info->get_result()->fetch_assoc();
                    $thutu_tc = $tc_result['thutu'] ?? $id_tc;
                    $noidung_tc = $tc_result['noidung'] ?? '';
                    
                    $note_entries[] = [
                        'id_tieuchi' => (int)$id_tc,
                        'thutu' => (int)$thutu_tc,
                        'noidung' => $noidung_tc,
                        'ghichu' => trim($value)
                    ];
                }
                
                // Thêm thông tin ghi chú vào additional_info
                $additional_info['note'] = "Có " . count($note_entries) . " ghi chú được cập nhật";
                $additional_info['note_entries'] = $note_entries;
            }
            
            $log_result = $logger->logActivity(
                'update_multiple',
                'tieuchi',
                0, // target_id = 0 vì là nhiều tiêu chí
                $id_sanxuat,
                $dept, // Truyền dept đã được kiểm tra
                $old_value,
                $new_value,
                $additional_info
            );
            if (!$log_result) {
                throw new Exception("Lỗi khi ghi log hoạt động");
            }
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
            throw $e;
        }
    }

    // Cập nhật trạng thái hoàn thành
    $sql_check = "SELECT COUNT(*) as total, COUNT(CASE WHEN diem_danhgia > 0 THEN 1 END) as completed 
                  FROM danhgia_tieuchi dt 
                  JOIN tieuchi_dept td ON dt.id_tieuchi = td.id 
                  WHERE dt.id_sanxuat = ? AND td.dept = ?";
    $stmt_check = $connect->prepare($sql_check);
    $stmt_check->bind_param("is", $id_sanxuat, $dept);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    
    $is_completed = ($result_check['total'] > 0 && $result_check['total'] == $result_check['completed']);
    
    // Cập nhật trạng thái
    $sql_status = "INSERT INTO dept_status (id_sanxuat, dept, completed) 
                   VALUES (?, ?, ?) 
                   ON DUPLICATE KEY UPDATE completed = ?";
    $stmt_status = $connect->prepare($sql_status);
    $completed = $is_completed ? 1 : 0;
    $stmt_status->bind_param("isii", $id_sanxuat, $dept, $completed, $completed);
    $stmt_status->execute();
    
    // Commit transaction
    $connect->commit();
    
    // Chuyển hướng về trang đánh giá với thông báo thành công
    header("Location: " . BASE_URL . "/indexdept.php?dept=" . urlencode($dept) . "&id=" . $id_sanxuat . "&success=1");
    exit;
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $connect->rollback();
    
    // Chuyển hướng về trang đánh giá với thông báo lỗi
    header("Location: " . BASE_URL . "/indexdept.php?dept=" . urlencode($dept) . "&id=" . $id_sanxuat . "&error=" . urlencode($e->getMessage()));
    exit;
}
