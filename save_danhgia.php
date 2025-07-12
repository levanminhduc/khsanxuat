<?php
require "contdb.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_sanxuat = $_POST['id_sanxuat'];
    $dept = $_POST['dept'];
    
    // Kiểm tra xem bộ phận có hợp lệ không
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
    
    // Bắt đầu transaction
    $connect->begin_transaction();
    
    try {
        // Lấy danh sách tiêu chí của bộ phận
        $sql_tieuchi = "SELECT id FROM tieuchi_dept WHERE dept = ?";
        $stmt_tieuchi = $connect->prepare($sql_tieuchi);
        $stmt_tieuchi->bind_param("s", $dept);
        $stmt_tieuchi->execute();
        $result_tieuchi = $stmt_tieuchi->get_result();
        
        // Lưu đánh giá mới
        $all_completed = true; // Khởi tạo biến kiểm tra
        
        while ($row_tieuchi = $result_tieuchi->fetch_assoc()) {
            $tieuchi_id = $row_tieuchi['id'];
            $nguoi_thuchien = $_POST["nguoi_thuchien_" . $tieuchi_id];
            $diem_danhgia = $_POST["diem_danhgia_" . $tieuchi_id] ?? 0;
            $da_thuchien = $diem_danhgia > 0 ? 1 : 0;
            $ghichu = $_POST["ghichu_" . $tieuchi_id] ?? '';
            
            // Kiểm tra nếu có tiêu chí nào chưa hoàn thành
            if ($diem_danhgia == 0) {
                $all_completed = false;
            }
            
            // Kiểm tra xem đã có bản ghi đánh giá cho tiêu chí này chưa
            $sql_check = "SELECT han_xuly, so_ngay_xuly FROM danhgia_tieuchi WHERE id_sanxuat = ? AND id_tieuchi = ?";
            $stmt_check = $connect->prepare($sql_check);
            $stmt_check->bind_param("ii", $id_sanxuat, $tieuchi_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                // Đã có bản ghi, lấy thông tin hạn xử lý
                $row_check = $result_check->fetch_assoc();
                $han_xuly = $row_check['han_xuly'];
                $so_ngay_xuly = $row_check['so_ngay_xuly'];
                
                // Cập nhật bản ghi hiện có, giữ nguyên thông tin hạn xử lý
                $sql_update = "UPDATE danhgia_tieuchi 
                              SET nguoi_thuchien = ?, da_thuchien = ?, diem_danhgia = ?, ghichu = ? 
                              WHERE id_sanxuat = ? AND id_tieuchi = ?";
                $stmt_update = $connect->prepare($sql_update);
                $stmt_update->bind_param("iidsii", $nguoi_thuchien, $da_thuchien, $diem_danhgia, $ghichu, $id_sanxuat, $tieuchi_id);
                $stmt_update->execute();
            } else {
                // Chưa có bản ghi, thêm mới
                // Lấy hạn xử lý từ bảng khsanxuat
                $sql_get_deadline = "SELECT ngayin, han_xuly, so_ngay_xuly, ngay_tinh_han FROM khsanxuat WHERE stt = ?";
                $stmt_get_deadline = $connect->prepare($sql_get_deadline);

                if (!$stmt_get_deadline) {
                    throw new Exception("Lỗi chuẩn bị truy vấn lấy thông tin hạn xử lý: " . $connect->error);
                }

                $stmt_get_deadline->bind_param("i", $id_sanxuat);
                $stmt_get_deadline->execute();
                $result_deadline = $stmt_get_deadline->get_result();

                if ($result_deadline->num_rows > 0) {
                    $row_deadline = $result_deadline->fetch_assoc();
                    $ngay_vao = $row_deadline['ngayin'];
                    $han_xuly_chung = $row_deadline['han_xuly'];
                    $so_ngay_xuly_chung = $row_deadline['so_ngay_xuly'] ?? 7; // Mặc định 7 ngày nếu không có
                    $ngay_tinh_han_chung = $row_deadline['ngay_tinh_han'] ?? 'ngay_vao'; // Mặc định 'ngay_vao' nếu không có
                } else {
                    // Nếu không tìm thấy thông tin, sử dụng giá trị mặc định
                    $han_xuly_chung = date('Y-m-d', strtotime('-7 days')); // Mặc định 7 ngày trước
                    $so_ngay_xuly_chung = 7;
                    $ngay_tinh_han_chung = 'ngay_vao';
                }

                // Thêm mới bản ghi với han_xuly, so_ngay_xuly và ngay_tinh_han
                $sql_insert = "INSERT INTO danhgia_tieuchi 
                              (id_sanxuat, id_tieuchi, nguoi_thuchien, da_thuchien, diem_danhgia, ghichu, han_xuly, so_ngay_xuly, ngay_tinh_han) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $connect->prepare($sql_insert);

                if (!$stmt_insert) {
                    throw new Exception("Lỗi chuẩn bị truy vấn thêm mới: " . $connect->error);
                }

                $stmt_insert->bind_param("iiidssiss", $id_sanxuat, $tieuchi_id, $nguoi_thuchien, $da_thuchien, $diem_danhgia, $ghichu, $han_xuly_chung, $so_ngay_xuly_chung, $ngay_tinh_han_chung);

                if (!$stmt_insert->execute()) {
                    throw new Exception("Lỗi thêm mới tiêu chí (ID: $tieuchi_id): " . $connect->error);
                } else {
                    error_log("Đã thêm mới tiêu chí ID: $tieuchi_id với hạn xử lý: $han_xuly_chung, số ngày xử lý: $so_ngay_xuly_chung");
                }
            }
        }
        
        // Cập nhật trạng thái bộ phận dựa trên việc tất cả tiêu chí đã hoàn thành
        if ($all_completed) {
            // Cập nhật hoặc thêm mới status với completed = 1
            $sql_update = "INSERT INTO dept_status (id_sanxuat, dept, completed, completed_date) 
                          VALUES (?, ?, 1, NOW())
                          ON DUPLICATE KEY UPDATE completed = 1, completed_date = NOW()";
            $stmt_update = $connect->prepare($sql_update);
            $stmt_update->bind_param("is", $id_sanxuat, $dept);
            $stmt_update->execute();
        } else {
            // Cập nhật hoặc thêm mới status với completed = 0
            $sql_update = "INSERT INTO dept_status (id_sanxuat, dept, completed, completed_date) 
                          VALUES (?, ?, 0, NULL)
                          ON DUPLICATE KEY UPDATE completed = 0, completed_date = NULL";
            $stmt_update = $connect->prepare($sql_update);
            $stmt_update->bind_param("is", $id_sanxuat, $dept);
            $stmt_update->execute();
        }
        
        // Commit transaction
        $connect->commit();
        
        // Chuyển hướng về trang chính với thông báo thành công
        header("Location: index.php?success=1");
        exit();
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $connect->rollback();
        header("Location: index.php?error=1");
        exit();
    }
} else {
    header("Location: index.php?error=1");
    exit();
}
?> 