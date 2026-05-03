<?php
// Kết nối database
include 'contdb.php';

// Mô phỏng dept - thử một giá trị biết trước
$dept = 'hoan_thanh'; // Bạn có thể thay đổi thành các giá trị khác để kiểm tra

echo "Sử dụng giá trị dept = '$dept'\n\n";

// Thực hiện truy vấn tương tự như trong indexdept.php
$sql = "SELECT tc.*, kds.ngay_tinh_han, kds.so_ngay_xuly, kds.nguoi_chiu_trachnhiem_default, nv.ten as ten_nguoi_thuchien 
       FROM tieuchi_dept tc 
       LEFT JOIN khsanxuat_default_settings kds ON tc.id = kds.id_tieuchi AND kds.dept = ?
       LEFT JOIN nhan_vien nv ON kds.nguoi_chiu_trachnhiem_default = nv.id
       WHERE tc.dept = ?
       ORDER BY tc.noidung";

$stmt = $connect->prepare($sql);
$stmt->bind_param("ss", $dept, $dept);
$stmt->execute();
$result = $stmt->get_result();

echo "Số lượng bản ghi trả về: " . $result->num_rows . "\n";

if ($result->num_rows > 0) {
    echo "\nDanh sách bản ghi:\n";
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $count++;
        echo "$count. ID: " . $row['id'] . ", Tiêu chí: " . $row['noidung'];
        echo ", Ngày tính hạn: " . ($row['ngay_tinh_han'] ?? 'NULL');
        echo ", Số ngày xử lý: " . ($row['so_ngay_xuly'] ?? 'NULL');
        echo ", Người chịu trách nhiệm ID: " . ($row['nguoi_chiu_trachnhiem_default'] ?? 'NULL');
        echo ", Tên: " . ($row['ten_nguoi_thuchien'] ?? 'NULL') . "\n";
    }
} else {
    echo "Không tìm thấy bản ghi nào!\n";
    
    // Kiểm tra lý do không có kết quả
    echo "\nKiểm tra tồn tại của bộ phận trong tieuchi_dept:\n";
    $check_sql = "SELECT COUNT(*) as count FROM tieuchi_dept WHERE dept = ?";
    $check_stmt = $connect->prepare($check_sql);
    $check_stmt->bind_param("s", $dept);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    echo "Số tiêu chí thuộc bộ phận '$dept': " . $check_row['count'] . "\n";
}

// Đóng kết nối
$connect->close();
?> 