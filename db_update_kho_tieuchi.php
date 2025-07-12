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

echo "<h1>Cập nhật nhóm cho tiêu chí kho</h1>";

// Phân nhóm tiêu chí Kho Nguyên Liệu (ID từ 110-127)
$sql_update_nguyenlieu = "UPDATE tieuchi_dept SET nhom = 'Kho Nguyên Liệu' WHERE dept = 'kho' AND id BETWEEN 110 AND 127";
if ($connect->query($sql_update_nguyenlieu)) {
    echo "<p>Đã cập nhật {$connect->affected_rows} tiêu chí cho Kho Nguyên Liệu</p>";
} else {
    echo "<p>Lỗi cập nhật Kho Nguyên Liệu: " . $connect->error . "</p>";
}

// Phân nhóm tiêu chí Kho Phụ Liệu (ID từ 128-137)
$sql_update_phulieu = "UPDATE tieuchi_dept SET nhom = 'Kho Phụ Liệu' WHERE dept = 'kho' AND id BETWEEN 128 AND 137";
if ($connect->query($sql_update_phulieu)) {
    echo "<p>Đã cập nhật {$connect->affected_rows} tiêu chí cho Kho Phụ Liệu</p>";
} else {
    echo "<p>Lỗi cập nhật Kho Phụ Liệu: " . $connect->error . "</p>";
}

// Hiển thị danh sách các tiêu chí sau khi cập nhật
$sql_select = "SELECT id, thutu, noidung, nhom FROM tieuchi_dept WHERE dept = 'kho' ORDER BY 
    CASE nhom 
        WHEN 'Kho Nguyên Liệu' THEN 1
        WHEN 'Kho Phụ Liệu' THEN 2
        ELSE 3 
    END,
    thutu ASC";
$result = $connect->query($sql_select);

if ($result && $result->num_rows > 0) {
    echo "<h2>Danh sách tiêu chí kho sau khi cập nhật:</h2>";
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
echo "<p><a href='image_handler.php?dept=kho&id=8025'>Quay lại trang xử lý hình ảnh</a></p>";
?> 