<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'update_deadline_debug.log');

// Ghi log cho mục đích gỡ lỗi
error_log('[' . date('Y-m-d H:i:s') . '] Nhận yêu cầu cập nhật hạn xử lý chung');
error_log('[POST Data] ' . print_r($_POST, true));

// Kiểm tra xem người dùng đã đăng nhập chưa
/* Comment lại để test code
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Trả về phản hồi JSON với thông báo lỗi
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập. Vui lòng đăng nhập để sử dụng chức năng này.']);
    exit;
}
*/

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['id_sanxuat']) || !isset($_POST['dept']) || !isset($_POST['so_ngay_xuly'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết.']);
    error_log('Thiếu thông tin cần thiết cho yêu cầu cập nhật.');
    exit;
}

$id_sanxuat = $_POST['id_sanxuat'];
$dept = $_POST['dept'];
$so_ngay_xuly = intval($_POST['so_ngay_xuly']);
$ngay_tinh_han = isset($_POST['ngay_tinh_han']) ? $_POST['ngay_tinh_han'] : 'ngay_vao';

// Lấy danh sách ID tiêu chí được chọn
$tieuchi_ids = [];
if (isset($_POST['tieuchi_ids']) && !empty($_POST['tieuchi_ids'])) {
    try {
        $tieuchi_ids = json_decode($_POST['tieuchi_ids'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Lỗi định dạng JSON: " . json_last_error_msg());
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi xử lý danh sách tiêu chí: ' . $e->getMessage()]);
        error_log('Lỗi xử lý JSON tiêu chí: ' . $e->getMessage());
        exit;
    }
}

// Kiểm tra giá trị hợp lệ
if ($so_ngay_xuly <= 0 || $so_ngay_xuly > 30) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Số ngày phải từ 1 đến 30.']);
    error_log('Số ngày xử lý không hợp lệ: ' . $so_ngay_xuly);
    exit;
}

// Kết nối đến cơ sở dữ liệu
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối đến cơ sở dữ liệu.']);
    error_log('Lỗi kết nối: ' . mysqli_connect_error());
    exit;
}

try {
    // Bắt đầu transaction
    mysqli_begin_transaction($connect);
    error_log('Bắt đầu transaction.');

    // Lấy thông tin sản xuất để tính hạn xử lý
    $sql_get_info = "SELECT ngayin, ngayout FROM khsanxuat WHERE stt = ?";
    $stmt_get_info = mysqli_prepare($connect, $sql_get_info);
    
    if (!$stmt_get_info) {
        throw new Exception("Lỗi khi chuẩn bị truy vấn lấy thông tin: " . mysqli_error($connect));
    }
    
    mysqli_stmt_bind_param($stmt_get_info, "i", $id_sanxuat);
    mysqli_stmt_execute($stmt_get_info);
    mysqli_stmt_store_result($stmt_get_info);
    
    if (mysqli_stmt_num_rows($stmt_get_info) == 0) {
        throw new Exception("Không tìm thấy thông tin sản xuất với ID: " . $id_sanxuat);
    }
    
    mysqli_stmt_bind_result($stmt_get_info, $ngay_vao, $ngay_ra);
    mysqli_stmt_fetch($stmt_get_info);
    mysqli_stmt_close($stmt_get_info);
    
    // Tính ngày hạn xử lý mới dựa vào loại ngày được chọn
    if ($ngay_tinh_han === 'ngay_vao') {
        // Hạn xử lý = Ngày vào - Số ngày
        $ngay_obj = new DateTime($ngay_vao);
        $ngay_deadline = clone $ngay_obj;
        $ngay_deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
        error_log("Sử dụng ngày vào ($ngay_vao) để tính hạn xử lý: Ngày vào - $so_ngay_xuly ngày");
    } else if ($ngay_tinh_han === 'ngay_vao_cong') {
        // Hạn xử lý = Ngày vào + Số ngày
        $ngay_obj = new DateTime($ngay_vao);
        $ngay_deadline = clone $ngay_obj;
        $ngay_deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
        error_log("Sử dụng ngày vào ($ngay_vao) để tính hạn xử lý: Ngày vào + $so_ngay_xuly ngày");
    } else if ($ngay_tinh_han === 'ngay_ra') {
        // Hạn xử lý = Ngày ra + Số ngày
        $ngay_obj = new DateTime($ngay_ra);
        $ngay_deadline = clone $ngay_obj;
        $ngay_deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
        error_log("Sử dụng ngày ra ($ngay_ra) để tính hạn xử lý: Ngày ra + $so_ngay_xuly ngày");
    } else if ($ngay_tinh_han === 'ngay_ra_tru') {
        // Hạn xử lý = Ngày ra - Số ngày
        $ngay_obj = new DateTime($ngay_ra);
        $ngay_deadline = clone $ngay_obj;
        $ngay_deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
        error_log("Sử dụng ngày ra ($ngay_ra) để tính hạn xử lý: Ngày ra - $so_ngay_xuly ngày");
    }
    
    $han_moi = $ngay_deadline->format('Y-m-d');
    $han_moi_display = $ngay_deadline->format('d/m/Y');
    
    error_log("Ngày tính: " . $ngay_obj->format('Y-m-d') . ", Số ngày xử lý: $so_ngay_xuly, Hạn mới: $han_moi");

    // Lưu số ngày xử lý vào bảng khsanxuat
    $sql_update_so_ngay = "UPDATE khsanxuat SET so_ngay_xuly = ? WHERE stt = ?";
    $stmt_update_so_ngay = mysqli_prepare($connect, $sql_update_so_ngay);
    
    if (!$stmt_update_so_ngay) {
        throw new Exception("Lỗi khi chuẩn bị truy vấn cập nhật số ngày: " . mysqli_error($connect));
    }
    
    mysqli_stmt_bind_param($stmt_update_so_ngay, "ii", $so_ngay_xuly, $id_sanxuat);
    mysqli_stmt_execute($stmt_update_so_ngay);
    
    if (mysqli_stmt_affected_rows($stmt_update_so_ngay) == 0) {
        error_log("Không có hàng nào được cập nhật trong bảng khsanxuat.");
    }
    
    mysqli_stmt_close($stmt_update_so_ngay);

    // Cập nhật deadline cho các tiêu chí được chọn
    $affected_rows = 0;
    
    // Kiểm tra cấu trúc bảng và thêm cột nếu cần
    $check_column = mysqli_query($connect, "SHOW COLUMNS FROM danhgia_tieuchi LIKE 'ngay_tinh_han'");
    if (mysqli_num_rows($check_column) == 0) {
        mysqli_query($connect, "ALTER TABLE danhgia_tieuchi ADD COLUMN ngay_tinh_han VARCHAR(20) DEFAULT 'ngay_vao' AFTER so_ngay_xuly");
        error_log("Đã thêm cột ngay_tinh_han vào bảng danhgia_tieuchi");
    }
    
    if (empty($tieuchi_ids)) {
        // Nếu không có tiêu chí nào được chọn, cập nhật theo bộ phận
        // Đầu tiên lấy danh sách tất cả tiêu chí của bộ phận
        $sql_get_tieuchi = "SELECT id FROM tieuchi_dept WHERE dept = ?";
        $stmt_get_tieuchi = mysqli_prepare($connect, $sql_get_tieuchi);
        
        if (!$stmt_get_tieuchi) {
            throw new Exception("Lỗi khi chuẩn bị truy vấn lấy danh sách tiêu chí: " . mysqli_error($connect));
        }
        
        mysqli_stmt_bind_param($stmt_get_tieuchi, "s", $dept);
        mysqli_stmt_execute($stmt_get_tieuchi);
        mysqli_stmt_store_result($stmt_get_tieuchi);
        
        if (mysqli_stmt_num_rows($stmt_get_tieuchi) == 0) {
            error_log("Không tìm thấy tiêu chí nào cho bộ phận: $dept");
        } else {
            // Bind result vào biến
            mysqli_stmt_bind_result($stmt_get_tieuchi, $tieuchi_id);
            
            // Chuẩn bị câu lệnh UPDATE
            $sql_update_deadline = "UPDATE danhgia_tieuchi SET han_xuly = ?, so_ngay_xuly = ?, ngay_tinh_han = ? WHERE id_sanxuat = ? AND id_tieuchi = ?";
            $stmt_update_deadline = mysqli_prepare($connect, $sql_update_deadline);
            
            if (!$stmt_update_deadline) {
                throw new Exception("Lỗi khi chuẩn bị truy vấn cập nhật hạn xử lý: " . mysqli_error($connect));
            }
            
            // Lặp qua từng tiêu chí và cập nhật
            while (mysqli_stmt_fetch($stmt_get_tieuchi)) {
                mysqli_stmt_bind_param($stmt_update_deadline, "sssii", $han_moi, $so_ngay_xuly, $ngay_tinh_han, $id_sanxuat, $tieuchi_id);
                mysqli_stmt_execute($stmt_update_deadline);
                $affected_rows += mysqli_stmt_affected_rows($stmt_update_deadline);
            }
            
            mysqli_stmt_close($stmt_update_deadline);
            error_log("Đã cập nhật $affected_rows tiêu chí trong bảng danhgia_tieuchi");
        }
        
        mysqli_stmt_close($stmt_get_tieuchi);
    } else {
        // Cập nhật chỉ các tiêu chí được chọn
        error_log("Cập nhật " . count($tieuchi_ids) . " tiêu chí được chọn");
        
        $sql_update_deadline = "UPDATE danhgia_tieuchi SET han_xuly = ?, so_ngay_xuly = ?, ngay_tinh_han = ? WHERE id_sanxuat = ? AND id_tieuchi = ?";
        $stmt_update_deadline = mysqli_prepare($connect, $sql_update_deadline);
        
        if (!$stmt_update_deadline) {
            throw new Exception("Lỗi khi chuẩn bị truy vấn cập nhật hạn xử lý: " . mysqli_error($connect));
        }
        
        foreach ($tieuchi_ids as $tieuchi_id) {
            // Kiểm tra xem đã có bản ghi trong danhgia_tieuchi chưa
            $sql_check = "SELECT id FROM danhgia_tieuchi WHERE id_sanxuat = ? AND id_tieuchi = ?";
            $stmt_check = mysqli_prepare($connect, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "ii", $id_sanxuat, $tieuchi_id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                // Cập nhật bản ghi hiện có
                mysqli_stmt_bind_param($stmt_update_deadline, "sssis", $han_moi, $so_ngay_xuly, $ngay_tinh_han, $id_sanxuat, $tieuchi_id);
                mysqli_stmt_execute($stmt_update_deadline);
                $affected_rows += mysqli_stmt_affected_rows($stmt_update_deadline);
            } else {
                // Tạo bản ghi mới
                $sql_insert = "INSERT INTO danhgia_tieuchi (id_sanxuat, id_tieuchi, han_xuly, so_ngay_xuly, ngay_tinh_han) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = mysqli_prepare($connect, $sql_insert);
                mysqli_stmt_bind_param($stmt_insert, "iisis", $id_sanxuat, $tieuchi_id, $han_moi, $so_ngay_xuly, $ngay_tinh_han);
                mysqli_stmt_execute($stmt_insert);
                $affected_rows += mysqli_stmt_affected_rows($stmt_insert);
                mysqli_stmt_close($stmt_insert);
            }
            
            mysqli_stmt_close($stmt_check);
        }
        
        mysqli_stmt_close($stmt_update_deadline);
        error_log("Đã cập nhật $affected_rows tiêu chí đã chọn");
    }
    
    // Cập nhật thông tin han_xuly, so_ngay_xuly và ngay_tinh_han trong bảng khsanxuat
    // Đây là cập nhật hạn chung cho tất cả tiêu chí của đơn hàng này
    $sql_update_khsanxuat = "UPDATE khsanxuat 
                         SET han_xuly = ?, 
                             so_ngay_xuly = ?,
                             ngay_tinh_han = ?
                         WHERE stt = ?";
    $stmt_khsanxuat = mysqli_prepare($connect, $sql_update_khsanxuat);

    if (!$stmt_khsanxuat) {
        $error_message = "Lỗi chuẩn bị câu lệnh cập nhật khsanxuat: " . mysqli_error($connect);
        error_log($error_message);
        echo json_encode(['success' => false, 'message' => $error_message]);
        mysqli_rollback($connect);
        exit();
    }

    mysqli_stmt_bind_param($stmt_khsanxuat, "sisi", $han_moi, $so_ngay_xuly, $ngay_tinh_han, $id_sanxuat);

    if (!mysqli_stmt_execute($stmt_khsanxuat)) {
        $error_message = "Lỗi khi cập nhật thông tin hạn xử lý vào bảng khsanxuat: " . mysqli_stmt_error($stmt_khsanxuat);
        error_log($error_message);
        echo json_encode(['success' => false, 'message' => $error_message]);
        mysqli_rollback($connect);
        exit();
    }

    error_log("Đã cập nhật hạn xử lý cho sản xuất ID $id_sanxuat: $han_moi (ngày tính: $ngay_tinh_han, số ngày: $so_ngay_xuly)");
    mysqli_stmt_close($stmt_khsanxuat);

    // Hoàn thành transaction
    mysqli_commit($connect);
    error_log('Transaction đã được commit.');

    // Trả về phản hồi thành công
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Cập nhật hạn xử lý thành công.',
        'new_date' => $han_moi_display,
        'so_ngay_xuly' => $so_ngay_xuly,
        'affected_rows' => $affected_rows,
        'ngay_tinh_han' => $ngay_tinh_han
    ]);

} catch (Exception $e) {
    // Nếu có lỗi, rollback transaction
    mysqli_rollback($connect);
    error_log('Lỗi, đã rollback transaction: ' . $e->getMessage());
    
    // Trả về phản hồi lỗi
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}

// Đóng kết nối
mysqli_close($connect);
?> 