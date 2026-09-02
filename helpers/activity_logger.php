<?php
// Nap o TOP-LEVEL scope: config.php khai bao $dept_names/$dept_nhom_config o scope goi,
// getValidDepts() doc chung qua `global`. Neu require trong than 1 function thi bien
// thanh local => getValidDepts() tra mang rong.
require_once BASE_PATH . '/includes/indexdept/config.php';

class ActivityLogger {
    /** Gop truot: lan sua cach lan truoc <= 30 phut thi coi la cung mot dot lam viec. */
    const MERGE_GAP_SECONDS = 1800;

    /** Tran mot dot: du sua lien tuc khong nghi, 1 dong log khong om qua 2 gio. */
    const MERGE_SESSION_MAX_SECONDS = 7200;

    /** Chi gop thao tac cham tieu chi; them/xoa hinh anh & bieu mau luon la dong rieng. */
    const MERGEABLE_ACTIONS = ['update_score', 'update_person', 'update_note', 'update_multiple'];

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

        if ($this->tryMergeIntoRecent($action_type, $target_type, $target_id, $id_khsanxuat, $dept, $old_value, $new_value, $additional_info)) {
            return true;
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

    /**
     * Gop thay doi moi vao dong log gan nhat cua cung (don, bo phan, nguoi dung)
     * neu dong do con trong cua so gop. Tra ve true khi da gop xong (khoi INSERT).
     *
     * KHONG dung transaction: actions/save_danhgia_with_log.php goi logActivity()
     * ben trong transaction cua no, mo transaction long nhau se implicit-commit
     * transaction ngoai. Thay bang compare-and-swap tren new_value — CAS truot thi
     * tra false de ghi dong moi, khong bao gio lam mat mot dong log.
     */
    private function tryMergeIntoRecent($action_type, $target_type, $target_id, $id_khsanxuat, $dept, $old_value, $new_value, $additional_info) {
        if ($target_type !== 'tieuchi' || !in_array($action_type, self::MERGEABLE_ACTIONS, true)) {
            return false;
        }
        if ((int) $id_khsanxuat <= 0 || $dept === '') {
            return false;
        }
        // 'system' la fallback khi chua login: gop se tron thao tac cua nhieu nguoi khac nhau.
        if ($this->user_name === 'system') {
            return false;
        }

        $incoming = $this->splitPartsByThutu($old_value, $new_value);
        if ($incoming === null) {
            return false;
        }

        // CAS truot nghia la co request khac vua ghi dung dong do: doc lai 1 lan roi thoi.
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $row = $this->findMergeTarget($id_khsanxuat, $dept);
            if ($row === null) {
                return false;
            }

            $merged = $this->buildMergedRow($row, $incoming, $action_type, $target_id, $additional_info);
            if ($merged === null) {
                return false;
            }

            if ($this->updateMergedRow($row, $merged)) {
                return true;
            }
        }

        return false;
    }

    private function findMergeTarget($id_khsanxuat, $dept) {
        $sql = "SELECT id, action_type, old_value, new_value, additional_info, action_time,
                       UNIX_TIMESTAMP(action_time) AS action_ts,
                       UNIX_TIMESTAMP() AS now_ts
                FROM activity_logs
                WHERE id_khsanxuat = ?
                  AND dept = ?
                  AND user_name = ?
                  AND target_type = 'tieuchi'
                  AND action_time >= NOW() - INTERVAL ? SECOND
                ORDER BY action_time DESC, id DESC
                LIMIT 1";

        $stmt = $this->connect->prepare($sql);
        if (!$stmt) {
            error_log('ActivityLogger::findMergeTarget prepare: ' . $this->connect->error);
            return null;
        }

        $gap = self::MERGE_GAP_SECONDS;
        $stmt->bind_param("issi", $id_khsanxuat, $dept, $this->user_name, $gap);
        if (!$stmt->execute()) {
            error_log('ActivityLogger::findMergeTarget execute: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row === null || !in_array($row['action_type'], self::MERGEABLE_ACTIONS, true)) {
            return null;
        }

        return $row;
    }

    /**
     * Dung noi dung dong log sau khi gop. Tra null khi khong gop duoc (chuoi cu sai dang
     * hoac dot da vuot tran thoi gian) de goi ghi dong moi.
     */
    private function buildMergedRow(array $row, array $incoming, $action_type, $target_id, $additional_info) {
        $existing = $this->splitPartsByThutu($row['old_value'], $row['new_value']);
        if ($existing === null) {
            return null;
        }

        $info = json_decode((string) $row['additional_info'], true);
        if (!is_array($info)) {
            $info = [];
        }

        // Dong chua tung gop thi moc bat dau dot chinh la thoi diem no duoc ghi.
        $bat_dau_ts = isset($info['bat_dau_ts']) ? (int) $info['bat_dau_ts'] : (int) $row['action_ts'];
        if ((int) $row['now_ts'] - $bat_dau_ts > self::MERGE_SESSION_MAX_SECONDS) {
            return null;
        }

        $so_lan = isset($info['so_lan']) && is_array($info['so_lan']) ? $info['so_lan'] : [];

        // Dong goc duoc ghi bang INSERT nen chua co so_lan: cac tieu chi da nam san trong
        // do deu la "sua 1 lan", khong seed thi chung bi thieu han khoi so_lan.
        foreach ($existing as $thutu => $unused) {
            if (!isset($so_lan[$thutu])) {
                $so_lan[$thutu] = 1;
            }
        }

        $merged = $existing;
        foreach ($incoming as $thutu => $parts) {
            if (isset($merged[$thutu])) {
                // Giu moc DAU cua dot, chi thay moc CUOI: old_value van la gia tri truoc khi dot bat dau.
                $merged[$thutu]['new'] = $parts['new'];
                $so_lan[$thutu] = (isset($so_lan[$thutu]) ? (int) $so_lan[$thutu] : 1) + 1;
            } else {
                $merged[$thutu] = $parts;
                $so_lan[$thutu] = 1;
            }
        }
        ksort($merged);
        ksort($so_lan);

        $tieuchi_map = [];
        foreach ([$info, is_array($additional_info) ? $additional_info : []] as $source) {
            if (!isset($source['changed_tieuchi']) || !is_array($source['changed_tieuchi'])) {
                continue;
            }
            foreach ($source['changed_tieuchi'] as $tieuchi) {
                if (!isset($tieuchi['thutu'])) {
                    continue;
                }
                $tieuchi_map[(int) $tieuchi['thutu']] = $tieuchi;
            }
        }
        ksort($tieuchi_map);

        $new_action_type = $row['action_type'] === $action_type ? $action_type : 'update_multiple';

        // Khoa la cua dong cu duoc giu, khoa cua lan ghi moi de len; khoa tong hop tinh lai ben duoi.
        $info = array_merge($info, is_array($additional_info) ? $additional_info : []);
        $info['action'] = $new_action_type;
        $info['changes'] = 'Thay đổi tiêu chí ' . implode(', ', array_keys($merged));
        $info['changed_tieuchi'] = array_values($tieuchi_map);
        $info['so_lan'] = $so_lan;
        $info['bat_dau'] = $row['action_time'];
        $info['bat_dau_ts'] = $bat_dau_ts;

        return [
            'action_type' => $new_action_type,
            // Dong gop nhieu tieu chi khong con tro toi 1 tieu chi cu the — cung quy uoc
            // voi actions/save_danhgia_with_log.php (target_id = 0).
            'target_id' => count($merged) > 1 ? 0 : (int) $target_id,
            'old_value' => implode(' | ', array_column($merged, 'old')),
            'new_value' => implode(' | ', array_column($merged, 'new')),
            'additional_info' => json_encode($info, JSON_UNESCAPED_UNICODE)
        ];
    }

    private function updateMergedRow(array $row, array $merged) {
        // WHERE new_value = ? la compare-and-swap: neu request khac da doi dong nay sau
        // luc doc thi affected_rows = 0 va ta doc lai thay vi ghi de len thay doi cua ho.
        $sql = "UPDATE activity_logs
                SET action_type = ?, target_id = ?, old_value = ?, new_value = ?,
                    additional_info = ?, action_time = NOW()
                WHERE id = ? AND new_value = ?";

        $stmt = $this->connect->prepare($sql);
        if (!$stmt) {
            error_log('ActivityLogger::updateMergedRow prepare: ' . $this->connect->error);
            return false;
        }

        $stmt->bind_param(
            "sisssis",
            $merged['action_type'],
            $merged['target_id'],
            $merged['old_value'],
            $merged['new_value'],
            $merged['additional_info'],
            $row['id'],
            $row['new_value']
        );

        if (!$stmt->execute()) {
            error_log('ActivityLogger::updateMergedRow execute: ' . $stmt->error);
            $stmt->close();
            return false;
        }

        $updated = $stmt->affected_rows > 0;
        $stmt->close();

        return $updated;
    }

    /**
     * Tach "tiêu chí N: ... | tiêu chí M: ..." thanh map [thutu => ['old' =>, 'new' =>]].
     * Tra null khi chuoi khong dung dang de goi bo qua viec gop (ghi dong moi cho an toan).
     * parseTieuchiDiff() trong pages/activity_logs.php ghep old/new THEO VI TRI nen 2 chuoi
     * bat buoc cung so phan tu va cung thu tu — vi vay phai ksort() khi dung lai ca hai.
     */
    private function splitPartsByThutu($old_value, $new_value) {
        $old_parts = explode(' | ', (string) $old_value);
        $new_parts = explode(' | ', (string) $new_value);
        if (count($old_parts) !== count($new_parts)) {
            return null;
        }

        $map = [];
        foreach ($old_parts as $idx => $old_part) {
            if (!preg_match('/tiêu chí (\d+)/', $old_part, $matches)) {
                return null;
            }
            $thutu = (int) $matches[1];
            if (isset($map[$thutu])) {
                return null;
            }
            $map[$thutu] = ['old' => $old_part, 'new' => $new_parts[$idx]];
        }

        return $map;
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
