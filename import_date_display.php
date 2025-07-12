<?php
/**
 * File này sẽ cập nhật date_display cho các tiêu chí sau khi import dữ liệu mới
 */

// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'contdb.php';
include 'display_deadline.php';

// Thiết lập header để trả về JSON
header('Content-Type: application/json');

// Mảng kết quả
$result = [
    'success' => false,
    'message' => '',
    'updated' => 0,
    'details' => []
];

// Kiểm tra xem có ID đơn hàng được gửi lên không
if (!isset($_POST['id']) && !isset($_GET['id'])) {
    $result['message'] = 'Thiếu ID đơn hàng';
    echo json_encode($result);
    exit;
}

// Lấy ID đơn hàng
$id_sanxuat = isset($_POST['id']) ? intval($_POST['id']) : intval($_GET['id']);

// Kiểm tra hợp lệ
if ($id_sanxuat <= 0) {
    $result['message'] = 'ID đơn hàng không hợp lệ';
    echo json_encode($result);
    exit;
}

try {
    // Ghi log bắt đầu cập nhật
    file_put_contents('import_date_display.log', "[" . date('Y-m-d H:i:s') . "] Bắt đầu cập nhật date_display cho đơn hàng ID: $id_sanxuat\n", FILE_APPEND);
    
    // Cập nhật date_display
    $update_result = updateImportDateDisplay($id_sanxuat, $connect);
    
    // Cập nhật kết quả
    $result['success'] = $update_result['success'];
    $result['message'] = $update_result['message'];
    $result['updated'] = $update_result['updated'];
    $result['details'] = $update_result;
    
    // Nếu thành công, lấy thông tin đơn hàng để hiển thị
    if ($result['success']) {
        $sql = "SELECT stt, po, xuong, ngayin, ngayout, han_xuly, ngay_tinh_han, so_ngay_xuly 
                FROM khsanxuat WHERE stt = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $id_sanxuat);
        $stmt->execute();
        $data = $stmt->get_result();
        
        if ($data->num_rows > 0) {
            $order = $data->fetch_assoc();
            
            // Định dạng lại ngày để hiển thị
            $order['ngayin_display'] = getDateDisplay($order['ngayin']);
            $order['ngayout_display'] = getDateDisplay($order['ngayout']);
            $order['han_xuly_display'] = getDateDisplay($order['han_xuly']);
            
            // Thêm thông tin vào kết quả
            $result['order'] = $order;
            
            // Lấy thông tin tiêu chí
            $sql_tc = "SELECT dt.id, dt.id_tieuchi, dt.han_xuly, dt.so_ngay_xuly, dt.ngay_tinh_han,
                              tc.noidung, tc.dept
                      FROM danhgia_tieuchi dt
                      JOIN tieuchi_dept tc ON dt.id_tieuchi = tc.id
                      WHERE dt.id_sanxuat = ?";
            $stmt_tc = $connect->prepare($sql_tc);
            $stmt_tc->bind_param("i", $id_sanxuat);
            $stmt_tc->execute();
            $data_tc = $stmt_tc->get_result();
            
            $tieuchi_list = [];
            while ($tc = $data_tc->fetch_assoc()) {
                // Định dạng lại ngày hạn xử lý
                $tc['han_xuly_display'] = getDateDisplay($tc['han_xuly']);
                
                // Mô tả cách tính hạn
                $cach_tinh = '';
                switch ($tc['ngay_tinh_han']) {
                    case 'ngay_vao':
                        $cach_tinh = 'Ngày vào - ' . $tc['so_ngay_xuly'] . ' ngày';
                        break;
                    case 'ngay_vao_cong':
                        $cach_tinh = 'Ngày vào + ' . $tc['so_ngay_xuly'] . ' ngày';
                        break;
                    case 'ngay_ra':
                        $cach_tinh = 'Ngày ra';
                        break;
                    case 'ngay_ra_tru':
                        $cach_tinh = 'Ngày ra - ' . $tc['so_ngay_xuly'] . ' ngày';
                        break;
                }
                $tc['cach_tinh'] = $cach_tinh;
                
                $tieuchi_list[] = $tc;
            }
            
            $result['tieuchi'] = $tieuchi_list;
        }
    }
    
    // Ghi log kết thúc cập nhật
    $log_message = "[" . date('Y-m-d H:i:s') . "] Kết thúc cập nhật date_display cho đơn hàng ID: $id_sanxuat. ";
    $log_message .= "Thành công: " . ($result['success'] ? 'Có' : 'Không') . ", ";
    $log_message .= "Số tiêu chí cập nhật: " . $result['updated'] . "\n";
    file_put_contents('import_date_display.log', $log_message, FILE_APPEND);
    
} catch (Exception $e) {
    $result['success'] = false;
    $result['message'] = 'Lỗi: ' . $e->getMessage();
    
    // Ghi log lỗi
    file_put_contents('import_date_display.log', "[" . date('Y-m-d H:i:s') . "] Lỗi cập nhật date_display: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Trả về kết quả
echo json_encode($result);
?> 