<?php
include 'db_connect.php';

// Kiểm tra bảng có tồn tại không
$check_table = $connect->query("SHOW TABLES LIKE 'required_images_criteria'");
if ($check_table->num_rows == 0) {
    die("Bảng required_images_criteria chưa được tạo");
}

// Lấy danh sách tiêu chí bắt buộc hình ảnh
$sql = "SELECT r.*, t.noidung, t.thutu 
        FROM required_images_criteria r
        LEFT JOIN tieuchi_dept t ON r.id_tieuchi = t.id
        ORDER BY r.dept, t.thutu";

$result = $connect->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        echo "<h2>Danh sách tiêu chí bắt buộc hình ảnh:</h2>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Bộ phận</th><th>ID Tiêu chí</th><th>Thứ tự</th><th>Nội dung</th><th>Ngày tạo</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['dept'] . "</td>";
            echo "<td>" . $row['id_tieuchi'] . "</td>";
            echo "<td>" . ($row['thutu'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['noidung'] ?? 'Không tìm thấy tiêu chí') . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "Chưa có tiêu chí bắt buộc hình ảnh nào được thêm vào.";
    }
} else {
    echo "Lỗi truy vấn: " . $connect->error;
}
?> 