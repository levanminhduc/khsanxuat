<?php
require "contdb.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dept = $_POST['dept'];
    $thutu = $_POST['thutu'];
    $noidung = $_POST['noidung'];
    $nhom = isset($_POST['nhom']) ? $_POST['nhom'] : '';
    $id_sanxuat = isset($_POST['id_sanxuat']) ? $_POST['id_sanxuat'] : 0;
    
    // Kiểm tra bộ phận hợp lệ
    $valid_departments = [
        'kehoach',
        'chuanbi_sanxuat_phong_kt',
        'kho',
        'cat',
        'ep_keo',
        'co_dien',
        'chuyen_may',
        'kcs',
        'ui_thanh_pham',
        'hoan_thanh'
    ];

    if (!in_array($dept, $valid_departments)) {
        die("Bộ phận không hợp lệ");
    }

    // Kiểm tra nhóm hợp lệ cho các bộ phận có nhóm
    if ($dept == 'chuanbi_sanxuat_phong_kt' || $dept == 'kho') {
        $valid_groups = [];
        if ($dept == 'chuanbi_sanxuat_phong_kt') {
            $valid_groups = ['Nhóm Nghiệp Vụ', 'Nhóm May Mẫu', 'Nhóm Quy Trình'];
        } elseif ($dept == 'kho') {
            $valid_groups = ['Kho Nguyên Liệu', 'Kho Phụ Liệu'];
        }

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
            // Chuyển hướng về trang trước với thông báo thành công và giữ nguyên id_sanxuat
            header("Location: indexdept.php?dept=" . $dept . "&id=" . $id_sanxuat . "&success=1");
            exit();
        } else {
            throw new Exception("Lỗi khi thêm tiêu chí");
        }
    } catch (Exception $e) {
        // Chuyển hướng về trang trước với thông báo lỗi và giữ nguyên id_sanxuat
        header("Location: indexdept.php?dept=" . $dept . "&id=" . $id_sanxuat . "&error=1&message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Nếu không phải POST request, chuyển về trang chính
    header("Location: index.php");
    exit();
}
?> 