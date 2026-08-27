<?php
// Nap o TOP-LEVEL scope: config.php khai bao $dept_names/$dept_nhom_config o scope goi,
// getValidDepts() doc chung qua `global`. Neu require trong than 1 function thi bien
// thanh local => getValidDepts() tra mang rong.
require_once BASE_PATH . '/includes/indexdept/config.php';

class ActivityLogger {
    private $connect;
    private $user_name;
    private $user_full_name;

    public function __construct($connect) {
        $this->connect = $connect;
        $this->initializeUser();
    }

    private function initializeUser() {
        // Session da duoc khsxSyncSessionContext() dung lai moi request
        // (includes/security/session_context.php); chua login thi 2 key nay bi unset.
        $this->user_name = !empty($_SESSION['username']) ? $_SESSION['username'] : 'system';
        $this->user_full_name = !empty($_SESSION['full_name']) ? $_SESSION['full_name'] : $this->user_name;
    }

    public function logActivity($action_type, $target_type, $target_id, $id_khsanxuat, $dept, $old_value = null, $new_value = null, $additional_info = null) {
        $valid_depts = getValidDepts();
        $dept = trim((string) $dept);

        if ((empty($dept) || !in_array($dept, $valid_depts)) && $target_id > 0) {
            $stmt = $this->connect->prepare("SELECT dept FROM tieuchi_dept WHERE id = ?");
            if (!$stmt) {
                error_log('ActivityLogger::logActivity prepare tieuchi_dept: ' . $this->connect->error);
            } else {
                $stmt->bind_param("i", $target_id);
                if (!$stmt->execute()) {
                    error_log('ActivityLogger::logActivity execute tieuchi_dept: ' . $stmt->error);
                } elseif ($row = $stmt->get_result()->fetch_assoc()) {
                    $dept = $row['dept'];
                }
                $stmt->close();
            }
        }

        // Cot dept la VARCHAR chu khong phai ENUM: ghi nguyen gia tri tho thay vi ep ve
        // mot bo phan mac dinh — doan sai bo phan lam hong du lieu audit vinh vien,
        // con gia tri la thi van truy nguoc duoc nguon.
        if (empty($dept) || !in_array($dept, $valid_depts)) {
            error_log("ActivityLogger: dept khong hop le '{$dept}' (id_khsanxuat={$id_khsanxuat}, target_id={$target_id})");
        }

        $sql = "INSERT INTO activity_logs (
            user_name,
            user_full_name,
            action_type,
            target_type,
            target_id,
            id_khsanxuat,
            dept,
            old_value,
            new_value,
            ip_address,
            user_agent,
            additional_info
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->connect->prepare($sql);
        if (!$stmt) {
            error_log('ActivityLogger::logActivity prepare INSERT: ' . $this->connect->error);
            return false;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $additional_info_json = $additional_info ? json_encode($additional_info, JSON_UNESCAPED_UNICODE) : null;

        $bind_result = $stmt->bind_param(
            "ssssiissssss",
            $this->user_name,
            $this->user_full_name,
            $action_type,
            $target_type,
            $target_id,
            $id_khsanxuat,
            $dept,
            $old_value,
            $new_value,
            $ip,
            $user_agent,
            $additional_info_json
        );

        if (!$bind_result) {
            error_log('ActivityLogger::logActivity bind_param: ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $execute_result = $stmt->execute();
        if (!$execute_result) {
            error_log('ActivityLogger::logActivity execute: ' . $stmt->error);
        }
        $stmt->close();

        return $execute_result;
    }

    public function getActivityLogs($filters = []) {
        list($where_sql, $params, $types) = $this->buildWhere($filters);

        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 100;
        $limit = max(1, min(1000, $limit));
        $offset = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;

        // Them id DESC de phan trang on dinh khi nhieu dong trung giay.
        $sql = "SELECT id, user_name, user_full_name, action_type, target_type, target_id,
                       id_khsanxuat, dept, old_value, new_value, action_time, additional_info
                FROM activity_logs" . $where_sql . "
                ORDER BY action_time DESC, id DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->connect->prepare($sql);
        if (!$stmt) {
            error_log('ActivityLogger::getActivityLogs prepare: ' . $this->connect->error);
            return [];
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            error_log('ActivityLogger::getActivityLogs execute: ' . $stmt->error);
            $stmt->close();
            return [];
        }
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function countActivityLogs($filters = []) {
        list($where_sql, $params, $types) = $this->buildWhere($filters);

        $sql = "SELECT COUNT(*) FROM activity_logs" . $where_sql;

        $stmt = $this->connect->prepare($sql);
        if (!$stmt) {
            error_log('ActivityLogger::countActivityLogs prepare: ' . $this->connect->error);
            return 0;
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            error_log('ActivityLogger::countActivityLogs execute: ' . $stmt->error);
            $stmt->close();
            return 0;
        }
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();

        return (int) ($row[0] ?? 0);
    }

    /**
     * Dung menh de WHERE dung chung cho getActivityLogs() va countActivityLogs().
     * Bo qua limit/offset — 2 key do chi co nghia voi getActivityLogs().
     *
     * @return array{0:string,1:array,2:string} [$where_sql, $params, $types]
     */
    private function buildWhere(array $filters) {
        $where_clauses = [];
        $params = [];
        $types = "";

        $user_name = isset($filters['user_name']) ? trim((string) $filters['user_name']) : '';
        if ($user_name !== '') {
            $where_clauses[] = "user_name = ?";
            $params[] = $user_name;
            $types .= "s";
        }

        $action_type = isset($filters['action_type']) ? trim((string) $filters['action_type']) : '';
        if ($action_type !== '') {
            $where_clauses[] = "action_type = ?";
            $params[] = $action_type;
            $types .= "s";
        }

        $id_khsanxuat = isset($filters['id_khsanxuat']) ? (int) $filters['id_khsanxuat'] : 0;
        if ($id_khsanxuat > 0) {
            $where_clauses[] = "id_khsanxuat = ?";
            $params[] = $id_khsanxuat;
            $types .= "i";
        }

        $dept = isset($filters['dept']) ? trim((string) $filters['dept']) : '';
        if ($dept !== '') {
            $where_clauses[] = "dept = ?";
            $params[] = $dept;
            $types .= "s";
        }

        $date_from = isset($filters['date_from']) ? trim((string) $filters['date_from']) : '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
            $where_clauses[] = "action_time >= ?";
            $params[] = $date_from . ' 00:00:00';
            $types .= "s";
        }

        $date_to = isset($filters['date_to']) ? trim((string) $filters['date_to']) : '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
            $where_clauses[] = "action_time <= ?";
            $params[] = $date_to . ' 23:59:59';
            $types .= "s";
        }

        $where_sql = empty($where_clauses) ? '' : ' WHERE ' . implode(' AND ', $where_clauses);

        return [$where_sql, $params, $types];
    }
}

// Hàm helper để tạo instance của ActivityLogger
function getActivityLogger($connect) {
    static $logger = null;
    if ($logger === null) {
        $logger = new ActivityLogger($connect);
    }
    return $logger;
}
