<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database");
}

echo "<h1>Cập nhật nhóm cho tiêu chí Phòng Kỹ Thuật</h1>";

// Phân nhóm tiêu chí Nhóm Nghiệp Vụ (ID từ 46-60)
$sql_update_nghiepvu = "UPDATE tieuchi_dept SET nhom = 'Nhóm Nghiệp Vụ' WHERE dept = 'chuanbi_sanxuat_phong_kt' AND id BETWEEN 46 AND 60";
if ($connect->query($sql_update_nghiepvu)) {
    echo "<p>Đã cập nhật {$connect->affected_rows} tiêu chí cho Nhóm Nghiệp Vụ</p>";
} else {
    echo "<p>Lỗi cập nhật Nhóm Nghiệp Vụ: " . $connect->error . "</p>";
}

// Phân nhóm tiêu chí Nhóm May Mẫu (ID từ 61-75)
$sql_update_maymau = "UPDATE tieuchi_dept SET nhom = 'Nhóm May Mẫu' WHERE dept = 'chuanbi_sanxuat_phong_kt' AND id BETWEEN 61 AND 75";
if ($connect->query($sql_update_maymau)) {
    echo "<p>Đã cập nhật {$connect->affected_rows} tiêu chí cho Nhóm May Mẫu</p>";
} else {
    echo "<p>Lỗi cập nhật Nhóm May Mẫu: " . $connect->error . "</p>";
}

// Phân nhóm tiêu chí Nhóm Quy Trình (ID từ 76-90)
$sql_update_quytrinh = "UPDATE tieuchi_dept SET nhom = 'Nhóm Quy Trình' WHERE dept = 'chuanbi_sanxuat_phong_kt' AND id BETWEEN 76 AND 90";
if ($connect->query($sql_update_quytrinh)) {
    echo "<p>Đã cập nhật {$connect->affected_rows} tiêu chí cho Nhóm Quy Trình</p>";
} else {
    echo "<p>Lỗi cập nhật Nhóm Quy Trình: " . $connect->error . "</p>";
}

// Hiển thị danh sách các tiêu chí sau khi cập nhật
$sql_select = "SELECT id, thutu, noidung, nhom FROM tieuchi_dept WHERE dept = 'chuanbi_sanxuat_phong_kt' ORDER BY 
    CASE nhom 
        WHEN 'Nhóm Nghiệp Vụ' THEN 1
        WHEN 'Nhóm May Mẫu' THEN 2
        WHEN 'Nhóm Quy Trình' THEN 3
        ELSE 4 
    END,
    thutu ASC";
$result = $connect->query($sql_select);

if ($result && $result->num_rows > 0) {
    echo "<h2>Danh sách tiêu chí Phòng Kỹ Thuật sau khi cập nhật:</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Thứ tự</th><th>Nhóm</th><th>Nội dung</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['thutu']}</td>";
        echo "<td>{$row['nhom']}</td>";
        echo "<td>{$row['noidung']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không tìm thấy tiêu chí nào hoặc lỗi truy vấn: " . $connect->error . "</p>";
}

$connect->close();
echo "<p><a href='image_handler.php?dept=chuanbi_sanxuat_phong_kt&id=1'>Quay lại trang xử lý hình ảnh</a></p>";
?> 