<?php
// Kết nối database
include 'contdb.php';

// Kiểm tra cấu trúc bảng tieuchi_dept
$result = $connect->query("DESCRIBE tieuchi_dept");
echo "Cấu trúc bảng tieuchi_dept:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

// Lấy một bản ghi mẫu
echo "\nBản ghi mẫu từ bảng tieuchi_dept:\n";
$result = $connect->query("SELECT * FROM tieuchi_dept LIMIT 1");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    foreach ($row as $key => $value) {
        echo "$key: $value\n";
    }
}

// Đóng kết nối
$connect->close();
?> 