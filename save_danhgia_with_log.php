<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kết nối database và logger
include 'db_connect.php';
include 'activity_logger.php';

// Khởi tạo phiên làm việc
session_start();

// Kiểm tra quyền ghi log (không cần kiểm tra đăng nhập vì đã login rồi)
$can_log = false;

// Tạm thời set $can_log = true để test
$can_log = true;
error_log("Force can_log = true for testing");

// Kiểm tra bảng activity_logs có tồn tại không
$check_table = $connect->query("SHOW TABLES LIKE 'activity_logs'");
if ($check_table->num_rows === 0) {
    error_log("Table activity_logs does not exist!");
} else {
    error_log("Table activity_logs exists");
}

if (isset($_SESSION['user_role'])) {
    $user_role = $_SESSION['user_role'];
    error_log("User role: " . $user_role);
    // Chỉ cho phép admin và manager ghi log
    if ($user_role == 'admin' || $user_role == 'manager') {
        $can_log = true;
        error_log("Can log: true from role");
    }
} else {
    error_log("No user_role in session");
}

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database");
}

// Lấy logger
$logger = getActivityLogger($connect);

// Đọc giá trị dept từ URL
$dept = isset($_GET['dept']) ? $_GET['dept'] : (isset($_POST['dept']) ? $_POST['dept'] : '');
// Thiết lập danh sách các giá trị dept hợp lệ
$dept_names = [
    'kehoach' => 'Phòng Kế Hoạch',
    'chuanbi_sanxuat_phong_kt' => 'Chuẩn Bị Sản Xuất - Phòng KT',
    'kho' => 'Kho',
    'cat' => 'Cắt',
    'ep_keo' => 'Ép Keo',
    'co_dien' => 'Cơ Điện',
    'chuyen_may' => 'Chuyền May',
    'kcs' => 'KCS',
    'ui_thanh_pham' => 'Ủi Thành Phẩm',
    'hoan_thanh' => 'Hoàn Thành'
];

// Kiểm tra và log giá trị dept ban đầu
error_log("save_danhgia_with_log: Initial dept value: " . $dept);

// Nếu không có dept từ form hoặc URL, thử lấy từ HTTP_REFERER
if (empty($dept) && isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    if (preg_match('/dept=([^&]+)/', $referer, $matches)) {
        $dept = urldecode($matches[1]);
        error_log("save_danhgia_with_log: Found dept from referer: " . $dept);
    }
}

// Đảm bảo dept là giá trị hợp lệ
$valid_depts = array_keys($dept_names);
if (empty($dept) || !in_array($dept, $valid_depts)) {
    // Sử dụng giá trị mặc định nếu không hợp lệ
    $dept = 'kehoach';
    error_log("save_danhgia_with_log: Using default dept: " . $dept);
}

// Lấy giá trị id_sanxuat từ POST hoặc GET
$id_sanxuat = isset($_POST['id_sanxuat']) ? intval($_POST['id_sanxuat']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
error_log("save_danhgia_with_log: id_sanxuat = " . $id_sanxuat);

if ($id_sanxuat <= 0 || empty($dept)) {
    die("Thiếu thông tin cần thiết");
}

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
        $nguoi_thuchien = isset($_POST['nguoi_thuchien_' . $id_tieuchi]) ? $_POST['nguoi_thuchien_' . $id_tieuchi] : null;
        $ghichu = isset($_POST['ghichu_' . $id_tieuchi]) ? $_POST['ghichu_' . $id_tieuchi] : null;
        
        // Kiểm tra xem có dữ liệu cũ không
        $has_old_data = isset($old_data[$id_tieuchi]);
        
        // Cập nhật hoặc thêm mới đánh giá
        if ($diem_danhgia !== null || $nguoi_thuchien !== null || $ghichu !== null) {
            if ($has_old_data) {
                $sql_update = "UPDATE danhgia_tieuchi SET 
                              diem_danhgia = COALESCE(?, diem_danhgia),
                              nguoi_thuchien = COALESCE(?, nguoi_thuchien),
                              ghichu = COALESCE(?, ghichu)
                              WHERE id_sanxuat = ? AND id_tieuchi = ?";
                $stmt_update = $connect->prepare($sql_update);
                $stmt_update->bind_param("disii", $diem_danhgia, $nguoi_thuchien, $ghichu, $id_sanxuat, $id_tieuchi);
            } else {
                $sql_insert = "INSERT INTO danhgia_tieuchi (id_sanxuat, id_tieuchi, diem_danhgia, nguoi_thuchien, ghichu) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = $connect->prepare($sql_insert);
                $stmt_insert->bind_param("iidis", $id_sanxuat, $id_tieuchi, $diem_danhgia, $nguoi_thuchien, $ghichu);
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
                    // Lấy tên người thực hiện
                    $sql_old_name = "SELECT ten FROM nhan_vien WHERE id = ?";
                    $stmt_old = $connect->prepare($sql_old_name);
                    $stmt_old->bind_param("i", $old_person);
                    $stmt_old->execute();
                    $old_name = $stmt_old->get_result()->fetch_assoc()['ten'] ?? $old_person;
                    
                    $sql_new_name = "SELECT ten FROM nhan_vien WHERE id = ?";
                    $stmt_new = $connect->prepare($sql_new_name);
                    $stmt_new->bind_param("i", $nguoi_thuchien);
                    $stmt_new->execute();
                    $new_name = $stmt_new->get_result()->fetch_assoc()['ten'] ?? $nguoi_thuchien;

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
                // Kiểm tra nếu chỉ có người thực hiện thay đổi
                else if ($nguoi_thuchien !== null && $nguoi_thuchien != $old_person) {
                    // Lấy tên người thực hiện cũ
                    $sql_old_name = "SELECT ten FROM nhan_vien WHERE id = ?";
                    $stmt_old = $connect->prepare($sql_old_name);
                    $stmt_old->bind_param("i", $old_person);
                    $stmt_old->execute();
                    $old_name = $stmt_old->get_result()->fetch_assoc()['ten'] ?? $old_person;
                    
                    // Lấy tên người thực hiện mới
                    $sql_new_name = "SELECT ten FROM nhan_vien WHERE id = ?";
                    $stmt_new = $connect->prepare($sql_new_name);
                    $stmt_new->bind_param("i", $nguoi_thuchien);
                    $stmt_new->execute();
                    $new_name = $stmt_new->get_result()->fetch_assoc()['ten'] ?? $nguoi_thuchien;

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
                    $sql_old_name = "SELECT ten FROM nhan_vien WHERE id = ?";
                    $stmt_old = $connect->prepare($sql_old_name);
                    $stmt_old->bind_param("i", $old_person);
                    $stmt_old->execute();
                    $old_name = $stmt_old->get_result()->fetch_assoc()['ten'] ?? $old_person;
                    
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
            error_log("Logging activity with department: " . $dept);
            
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
    header("Location: indexdept.php?dept=" . urlencode($dept) . "&id=" . $id_sanxuat . "&success=1");
    exit;
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $connect->rollback();
    
    // Chuyển hướng về trang đánh giá với thông báo lỗi
    header("Location: indexdept.php?dept=" . urlencode($dept) . "&id=" . $id_sanxuat . "&error=" . urlencode($e->getMessage()));
    exit;
}

// Bắt đầu xử lý POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $id_sanxuat = isset($_POST['id_sanxuat']) ? intval($_POST['id_sanxuat']) : 0;
    
    // Khởi tạo biến để theo dõi thay đổi
    $changes_made = false;
    
    // Kiểm tra và xử lý từng tiêu chí
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'score_') === 0) {
            $id_tieuchi = intval(str_replace('score_', '', $key));
            $score = intval($value);
            $personInCharge = isset($_POST['person_' . $id_tieuchi]) ? trim($_POST['person_' . $id_tieuchi]) : '';
            $note = isset($_POST['note_' . $id_tieuchi]) ? trim($_POST['note_' . $id_tieuchi]) : '';
            
            // Lấy dữ liệu hiện tại từ DB
            $stmt = $connect->prepare("SELECT * FROM danhgia_tieuchi WHERE id_tieuchi = ? AND id_khsanxuat = ?");
            $stmt->bind_param("ii", $id_tieuchi, $id_sanxuat);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Đã có đánh giá, cập nhật
                $existing = $result->fetch_assoc();
                $old_score = $existing['diem'];
                $old_personInCharge = $existing['nguoi_chiu_trach_nhiem'];
                $old_note = $existing['note'];
                
                $has_changes = ($old_score != $score || $old_personInCharge != $personInCharge || $old_note != $note);
                
                if ($has_changes) {
                    $changes_made = true;
                    
                    // Cập nhật bản ghi
                    $update_stmt = $connect->prepare("UPDATE danhgia_tieuchi SET diem = ?, nguoi_chiu_trach_nhiem = ?, note = ? WHERE id_tieuchi = ? AND id_khsanxuat = ?");
                    $update_stmt->bind_param("issii", $score, $personInCharge, $note, $id_tieuchi, $id_sanxuat);
                    $update_stmt->execute();
                    
                    // Lấy thông tin tiêu chí
                    $get_criteria = $connect->prepare("SELECT tieuchi.ten_tieuchi, tieuchi_dept.dept FROM tieuchi 
                                                      JOIN tieuchi_dept ON tieuchi.id = tieuchi_dept.id
                                                      WHERE tieuchi.id = ?");
                    $get_criteria->bind_param("i", $id_tieuchi);
                    $get_criteria->execute();
                    $criteria_result = $get_criteria->get_result();
                    $criteria_info = $criteria_result->fetch_assoc();
                    $criteria_name = $criteria_info ? $criteria_info['ten_tieuchi'] : "Tiêu chí #".$id_tieuchi;
                    
                    // Ghi log cho từng thay đổi
                    if ($old_score != $score) {
                        $action_type = 'update_score';
                        $old_value = $old_score;
                        $new_value = $score;
                        $additional_info = [
                            'criteria_id' => $id_tieuchi,
                            'criteria_name' => $criteria_name,
                            'dept_info' => [
                                'dept_code' => $dept,
                                'dept_name' => $dept_names[$dept] ?? $dept
                            ]
                        ];
                        
                        if (!$logger->logActivity($action_type, 'danhgia_tieuchi', $id_tieuchi, $id_sanxuat, $dept, $old_value, $new_value, $additional_info)) {
                            error_log("Failed to log activity for score update on criteria $id_tieuchi");
                        }
                    }
                    
                    if ($old_personInCharge != $personInCharge) {
                        $action_type = 'update_person';
                        $old_value = $old_personInCharge;
                        $new_value = $personInCharge;
                        $additional_info = [
                            'criteria_id' => $id_tieuchi,
                            'criteria_name' => $criteria_name,
                            'dept_info' => [
                                'dept_code' => $dept,
                                'dept_name' => $dept_names[$dept] ?? $dept
                            ]
                        ];
                        
                        if (!$logger->logActivity($action_type, 'danhgia_tieuchi', $id_tieuchi, $id_sanxuat, $dept, $old_value, $new_value, $additional_info)) {
                            error_log("Failed to log activity for person update on criteria $id_tieuchi");
                        }
                    }
                    
                    if ($old_note != $note) {
                        $action_type = 'update_note';
                        $old_value = $old_note;
                        $new_value = $note;
                        $additional_info = [
                            'criteria_id' => $id_tieuchi,
                            'criteria_name' => $criteria_name,
                            'dept_info' => [
                                'dept_code' => $dept,
                                'dept_name' => $dept_names[$dept] ?? $dept
                            ]
                        ];
                        
                        if (!$logger->logActivity($action_type, 'danhgia_tieuchi', $id_tieuchi, $id_sanxuat, $dept, $old_value, $new_value, $additional_info)) {
                            error_log("Failed to log activity for note update on criteria $id_tieuchi");
                        }
                    }
                }
            } else {
                // Chưa có đánh giá, thêm mới
                $changes_made = true;
                
                $insert_stmt = $connect->prepare("INSERT INTO danhgia_tieuchi (id_tieuchi, id_khsanxuat, diem, nguoi_chiu_trach_nhiem, note) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("iiiss", $id_tieuchi, $id_sanxuat, $score, $personInCharge, $note);
                $insert_stmt->execute();
                
                // Lấy thông tin tiêu chí
                $get_criteria = $connect->prepare("SELECT tieuchi.ten_tieuchi, tieuchi_dept.dept FROM tieuchi 
                                                  JOIN tieuchi_dept ON tieuchi.id = tieuchi_dept.id
                                                  WHERE tieuchi.id = ?");
                $get_criteria->bind_param("i", $id_tieuchi);
                $get_criteria->execute();
                $criteria_result = $get_criteria->get_result();
                $criteria_info = $criteria_result->fetch_assoc();
                $criteria_name = $criteria_info ? $criteria_info['ten_tieuchi'] : "Tiêu chí #".$id_tieuchi;
                
                // Ghi log thêm mới
                $action_type = 'update_multiple';
                $old_value = "Chưa có dữ liệu";
                $new_value = "Điểm: $score, Người chịu trách nhiệm: $personInCharge, Ghi chú: $note";
                $additional_info = [
                    'criteria_id' => $id_tieuchi,
                    'criteria_name' => $criteria_name,
                    'dept_info' => [
                        'dept_code' => $dept,
                        'dept_name' => $dept_names[$dept] ?? $dept
                    ],
                    'fields' => [
                        'score' => $score,
                        'person' => $personInCharge,
                        'note' => $note
                    ]
                ];
                
                if (!$logger->logActivity($action_type, 'danhgia_tieuchi', $id_tieuchi, $id_sanxuat, $dept, $old_value, $new_value, $additional_info)) {
                    error_log("Failed to log activity for new criteria evaluation $id_tieuchi");
                }
            }
        }
    }
    
    // Redirect để tránh gửi lại form khi refresh
    $redirect_url = "indexdept.php?id={$id_sanxuat}&dept={$dept}";
    if ($changes_made) {
        $redirect_url .= "&success=1";
    }
    header("Location: $redirect_url");
    exit;
}

// Redirect về trang chính nếu không phải là POST request
header("Location: index.php");
exit; 