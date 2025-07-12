<?php
// Bật hiển thị lỗi để dễ debug trong môi trường phát triển
error_reporting(E_ALL);
ini_set('display_errors', 1);

class ActivityLogger {
    private $connect;
    private $user_name;
    private $user_full_name;
    
    public function __construct($connect) {
        $this->connect = $connect;
        $this->initializeUser();
        $this->ensureTableExists();
    }
    
    private function initializeUser() {
        // Lấy thông tin user từ session hoặc hệ thống xác thực
        if (isset($_SESSION['username'])) {
            $this->user_name = $_SESSION['username'];
            
            // Nếu có full_name trong session thì lấy từ đó trước
            if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
                $this->user_full_name = $_SESSION['full_name'];
                error_log("Lấy full_name từ session: " . $this->user_full_name);
            } else {
                // Lấy full_name từ bảng user
                $sql = "SELECT full_name FROM users WHERE username = ?";
                $stmt = $this->connect->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("s", $this->user_name);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($row = $result->fetch_assoc()) {
                        $this->user_full_name = $row['full_name'];
                        error_log("Lấy full_name từ database: " . $this->user_full_name);
                    } else {
                        $this->user_full_name = $this->user_name; // Fallback nếu không tìm thấy
                        error_log("Không tìm thấy full_name, sử dụng username: " . $this->user_name);
                    }
                    $stmt->close();
                } else {
                    $this->user_full_name = $this->user_name; // Fallback nếu có lỗi query
                    error_log("Lỗi khi truy vấn full_name, sử dụng username: " . $this->user_name);
                }
            }
        } else {
            // Kiểm tra các biến session khác có thể chứa thông tin người dùng
            $possible_username_vars = ['user', 'userid', 'user_id', 'login', 'ten_dangnhap'];
            $username_found = false;
            
            foreach ($possible_username_vars as $var) {
                if (isset($_SESSION[$var]) && !empty($_SESSION[$var])) {
                    $this->user_name = $_SESSION[$var];
                    $username_found = true;
                    error_log("Tìm thấy username trong session[{$var}]: " . $this->user_name);
                    
                    // Kiểm tra các biến session chứa tên đầy đủ
                    $fullname_vars = ['fullname', 'full_name', 'ten_day_du', 'tennguoidung', 'ten', 'hoten'];
                    foreach ($fullname_vars as $full_var) {
                        if (isset($_SESSION[$full_var]) && !empty($_SESSION[$full_var])) {
                            $this->user_full_name = $_SESSION[$full_var];
                            error_log("Tìm thấy full_name trong session[{$full_var}]: " . $this->user_full_name);
                            break;
                        }
                    }
                    
                    if (empty($this->user_full_name)) {
                        $this->user_full_name = $this->user_name;
                        error_log("Không tìm thấy full_name trong session, sử dụng username: " . $this->user_name);
                    }
                    
                    break;
                }
            }
            
            // Nếu không tìm thấy thông tin người dùng, sử dụng giá trị mặc định
            if (!$username_found) {
                $this->user_name = 'system';
                $this->user_full_name = 'System User';
                error_log("Không tìm thấy thông tin người dùng trong session, sử dụng giá trị mặc định");
            }
        }
        
        // Debug thông tin người dùng
        error_log("ActivityLogger - Người dùng hiện tại: " . $this->user_name . " / " . $this->user_full_name);
    }
    
    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(50) NOT NULL,
            user_full_name VARCHAR(100) NOT NULL,
            action_type ENUM('update_score', 'update_person', 'update_note', 'update_multiple', 'add_image', 'add_template', 'delete_image', 'delete_template') NOT NULL,
            target_type ENUM('tieuchi', 'image', 'template') NOT NULL,
            target_id INT NOT NULL,
            id_khsanxuat INT NOT NULL,
            dept VARCHAR(50) NOT NULL COMMENT 'Mã bộ phận (kehoach, cat, ep_keo, etc.)',
            old_value TEXT NULL,
            new_value TEXT NULL,
            action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            additional_info JSON NULL,
            INDEX idx_user (user_name),
            INDEX idx_action (action_type),
            INDEX idx_target (target_type, target_id),
            INDEX idx_khsanxuat (id_khsanxuat),
            INDEX idx_dept (dept)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->connect->query($sql);
        
        // Thêm 'update_multiple' vào enum nếu chưa có
        $sql_alter = "ALTER TABLE activity_logs MODIFY COLUMN action_type 
            ENUM('update_score', 'update_person', 'update_note', 'update_multiple', 'add_image', 'add_template', 'delete_image', 'delete_template') NOT NULL";
        $this->connect->query($sql_alter);
    }
    
    public function logActivity($action_type, $target_type, $target_id, $id_khsanxuat, $dept, $old_value = null, $new_value = null, $additional_info = null) {
        // Tạo danh sách bộ phận hợp lệ
        $valid_depts = [
            'kehoach', 'chuanbi_sanxuat_phong_kt', 'kho', 'cat', 
            'ep_keo', 'co_dien', 'chuyen_may', 'kcs', 
            'ui_thanh_pham', 'hoan_thanh'
        ];
        
        // Xử lý giá trị dept
        $dept = trim($dept);
        error_log("[Activity Log] Giá trị dept ban đầu: {$dept}");
        
        // Kiểm tra nếu dept là giá trị hợp lệ
        if (empty($dept) || !in_array($dept, $valid_depts)) {
            error_log("[Activity Log] Dept không hợp lệ, cần tìm giá trị thay thế");
            
            // Nếu có target_id, thử lấy dept từ bảng tieuchi_dept
            if ($target_id > 0) {
                $stmt = $this->connect->prepare("SELECT dept FROM tieuchi_dept WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $target_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $dept = $row['dept'];
                        error_log("[Activity Log] Đã tìm thấy dept từ tieuchi_dept: {$dept}");
                    }
                    $stmt->close();
                }
            }
            
            // Nếu vẫn chưa có, đặt giá trị mặc định
            if (empty($dept) || !in_array($dept, $valid_depts)) {
                // Ưu tiên lấy từ URL referer
                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                if (preg_match('/dept=([^&]+)/', $referer, $matches)) {
                    $dept = urldecode($matches[1]);
                    error_log("[Activity Log] Đã tìm thấy dept từ referer URL: {$dept}");
                } else {
                    // Nếu tất cả thất bại, sử dụng kehoach làm mặc định
                    $dept = 'kehoach';
                    error_log("[Activity Log] Không thể xác định dept, sử dụng giá trị mặc định: {$dept}");
                }
            }
        }
        
        // Đảm bảo dept là một trong những giá trị hợp lệ
        if (!in_array($dept, $valid_depts)) {
            $dept = 'kehoach';
            error_log("[Activity Log] Dept vẫn không hợp lệ sau khi xử lý, sử dụng mặc định: {$dept}");
        }
        
        error_log("[Activity Log] Giá trị dept cuối cùng: {$dept}");
        
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
            error_log("Error preparing statement: " . $this->connect->error);
            return false;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $additional_info_json = $additional_info ? json_encode($additional_info) : null;
        
        $bind_result = $stmt->bind_param(
            "ssssiiisssss",
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
            error_log("Error binding parameters: " . $stmt->error);
            return false;
        }
        
        $execute_result = $stmt->execute();
        if (!$execute_result) {
            error_log("Error executing statement: " . $stmt->error);
        }
        
        return $execute_result;
    }
    
    public function getActivityLogs($filters = []) {
        $where_clauses = [];
        $params = [];
        $types = "";
        
        if (!empty($filters['user_name'])) {
            $where_clauses[] = "user_name = ?";
            $params[] = $filters['user_name'];
            $types .= "s";
        }
        
        if (!empty($filters['action_type'])) {
            $where_clauses[] = "action_type = ?";
            $params[] = $filters['action_type'];
            $types .= "s";
        }
        
        if (!empty($filters['id_khsanxuat'])) {
            $where_clauses[] = "id_khsanxuat = ?";
            $params[] = $filters['id_khsanxuat'];
            $types .= "i";
        }
        
        if (!empty($filters['dept'])) {
            $where_clauses[] = "dept = ?";
            $params[] = $filters['dept'];
            $types .= "s";
        }
        
        $sql = "SELECT * FROM activity_logs";
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $sql .= " ORDER BY action_time DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
            $types .= "i";
        }
        
        $stmt = $this->connect->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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