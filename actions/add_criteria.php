<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';

requireFeature('edit_settings', 'redirect');
verifyCsrfOrDie();

require_once BASE_PATH . '/includes/indexdept/config.php';
require_once BASE_PATH . '/includes/indexdept/functions.php'; // setIndexdeptFlash()

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dept = $_POST['dept'];
    $thutu = $_POST['thutu'];
    $noidung = $_POST['noidung'];
    $nhom = isset($_POST['nhom']) ? $_POST['nhom'] : '';
    $id_sanxuat = isset($_POST['id_sanxuat']) ? $_POST['id_sanxuat'] : 0;
    
    // Kiểm tra bộ phận hợp lệ
    if (!in_array($dept, getValidDepts())) {
        die("Bộ phận không hợp lệ");
    }

    // Kiểm tra nhóm hợp lệ cho các bộ phận có nhóm
    if ($dept == 'chuanbi_sanxuat_phong_kt' || $dept == 'kho') {
        $valid_groups = getValidNhomForDept($dept);

        if (!in_array($nhom, $valid_groups)) {
            die("Nhóm không hợp lệ");
        }
    }

    try {
        // Kiểm tra xem thứ tự đã tồn tại trong nhóm chưa
        if ($dept == 'chuanbi_sanxuat_phong_kt' || $dept == 'kho') {
            $check_sql = "SELECT COUNT(*) as count FROM tieuchi_dept WHERE dept = ? AND thutu = ? AND nhom = ?";
            $check_stmt = $connect->prepare($check_sql);
            $check_stmt->bind_param("sis", $dept, $thutu, $nhom);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                die("Thứ tự này đã tồn tại trong nhóm");
            }
        } else {
            $check_sql = "SELECT COUNT(*) as count FROM tieuchi_dept WHERE dept = ? AND thutu = ?";
            $check_stmt = $connect->prepare($check_sql);
            $check_stmt->bind_param("si", $dept, $thutu);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                die("Thứ tự này đã tồn tại");
            }
        }

        // Thêm tiêu chí mới
        $sql = "INSERT INTO tieuchi_dept (dept, thutu, noidung, nhom) VALUES (?, ?, ?, ?)";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("siss", $dept, $thutu, $noidung, $nhom);
        
        if ($stmt->execute()) {
            // Thêm tiêu chí luôn là thay đổi thật. Thông báo qua session flash chứ không
            // gắn vào URL, để F5/back không hiện lại thông báo của lần thêm trước.
            setIndexdeptFlash('success', 'Đã thêm tiêu chí');
            header("Location: " . BASE_URL . "/indexdept.php?dept=" . $dept . "&id=" . $id_sanxuat);
            exit();
        } else {
            throw new Exception("Lỗi khi thêm tiêu chí");
        }
    } catch (Exception $e) {
        // Chuyển hướng về trang trước với thông báo lỗi và giữ nguyên id_sanxuat
        setIndexdeptFlash('error', $e->getMessage());
        header("Location: " . BASE_URL . "/indexdept.php?dept=" . $dept . "&id=" . $id_sanxuat);
        exit();
    }
} else {
    // Nếu không phải POST request, chuyển về trang chính
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
?> 