<?php
// Khởi tạo phiên làm việc
session_start();

// Đảm bảo hiển thị lỗi chi tiết
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kết nối database
include 'contdb.php';

// Kiểm tra kết nối
if (!$connect) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi kết nối database'
    ]);
    exit();
}

// Kiểm tra dữ liệu gửi lên
if (!isset($_GET['id_tieuchi']) || !isset($_GET['id_sanxuat'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Thiếu dữ liệu cần thiết'
    ]);
    exit();
}

$id_tieuchi = intval($_GET['id_tieuchi']);
$id_sanxuat = intval($_GET['id_sanxuat']);

try {
    // Nếu id_tieuchi và id_sanxuat được cung cấp, trả về thông tin hạn xử lý của tiêu chí cụ thể
    $sql_single = "SELECT dt.han_xuly, t.noidung, dt.so_ngay_xuly, dt.ngay_tinh_han 
                 FROM danhgia_tieuchi dt 
                 JOIN tieuchi_dept t ON dt.id_tieuchi = t.id 
                 WHERE dt.id_tieuchi = ? AND dt.id_sanxuat = ?";
                 
    $stmt_single = $connect->prepare($sql_single);
    $stmt_single->bind_param("ii", $id_tieuchi, $id_sanxuat);
    $stmt_single->execute();
    $result_single = $stmt_single->get_result();
    
    if ($result_single->num_rows > 0) {
        $row = $result_single->fetch_assoc();
        // Chuyển đổi định dạng ngày sang d/m/Y cho hiển thị
        if (!empty($row['han_xuly'])) {
            $han_xuly_date = new DateTime($row['han_xuly']);
            $han_xuly_formatted = $han_xuly_date->format('d/m/Y');
        } else {
            $han_xuly_formatted = 'Chưa cài đặt';
        }
        
        echo json_encode([
            'success' => true,
            'deadline' => $han_xuly_formatted,
            'so_ngay_xuly' => $row['so_ngay_xuly'],
            'ngay_tinh_han' => $row['ngay_tinh_han'],
            'id_tieuchi' => $id_tieuchi,
            'id_sanxuat' => $id_sanxuat,
            'raw_deadline' => $row['han_xuly']
        ]);
    } else {
        // Nếu không tìm thấy bản ghi, tìm cài đặt mặc định
        $sql_get_dept = "SELECT dept FROM tieuchi_dept WHERE id = ?";
        $stmt_get_dept = $connect->prepare($sql_get_dept);
        $stmt_get_dept->bind_param("i", $id_tieuchi);
        $stmt_get_dept->execute();
        $result_dept = $stmt_get_dept->get_result();
        
        if ($result_dept->num_rows > 0) {
            $dept_row = $result_dept->fetch_assoc();
            $dept = $dept_row['dept'];
            
            // Lấy thông tin cài đặt mặc định
            $sql_default = "SELECT so_ngay_xuly, ngay_tinh_han 
                          FROM default_settings 
                          WHERE dept = ? AND id_tieuchi = ?";
            
            $stmt_default = $connect->prepare($sql_default);
            $stmt_default->bind_param("si", $dept, $id_tieuchi);
            $stmt_default->execute();
            $result_default = $stmt_default->get_result();
            
            if ($result_default->num_rows > 0) {
                $default_row = $result_default->fetch_assoc();
                
                // Lấy ngày vào và ngày ra để tính hạn xử lý
                $sql_dates = "SELECT ngayin, ngayout FROM khsanxuat WHERE stt = ?";
                $stmt_dates = $connect->prepare($sql_dates);
                $stmt_dates->bind_param("i", $id_sanxuat);
                $stmt_dates->execute();
                $result_dates = $stmt_dates->get_result();
                
                if ($result_dates->num_rows > 0) {
                    $dates_row = $result_dates->fetch_assoc();
                    $ngayin = new DateTime($dates_row['ngayin']);
                    $ngayout = new DateTime($dates_row['ngayout']);
                    $so_ngay_xuly = $default_row['so_ngay_xuly'];
                    $ngay_tinh_han = $default_row['ngay_tinh_han'];
                    $han_xuly = null;
                    
                    // Tính hạn xử lý dựa vào phương thức tính
                    if ($ngay_tinh_han === 'ngay_vao') {
                        $han_xuly = clone $ngayin;
                        $han_xuly->modify("-{$so_ngay_xuly} days");
                    } else if ($ngay_tinh_han === 'ngay_vao_cong') {
                        $han_xuly = clone $ngayin;
                        $han_xuly->modify("+{$so_ngay_xuly} days");
                    } else if ($ngay_tinh_han === 'ngay_ra') {
                        $han_xuly = clone $ngayout;
                        $han_xuly->modify("+{$so_ngay_xuly} days");
                    } else if ($ngay_tinh_han === 'ngay_ra_tru') {
                        $han_xuly = clone $ngayout;
                        $han_xuly->modify("-{$so_ngay_xuly} days");
                    }
                    
                    $han_xuly_formatted = $han_xuly->format('d/m/Y');
                    
                    echo json_encode([
                        'success' => true,
                        'deadline' => $han_xuly_formatted,
                        'so_ngay_xuly' => $so_ngay_xuly,
                        'ngay_tinh_han' => $ngay_tinh_han,
                        'source' => 'default',
                        'message' => 'Hiển thị hạn xử lý mặc định'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin ngày vào/ra'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'deadline' => 'Chưa cài đặt',
                    'message' => 'Không tìm thấy cài đặt mặc định'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy thông tin tiêu chí'
            ]);
        }
    }
} catch (Exception $e) {
    // Trả về lỗi dưới dạng JSON
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 