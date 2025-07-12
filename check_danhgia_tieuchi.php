<?php
// Kết nối database
require "contdb.php";

// Kiểm tra cấu trúc bảng danhgia_tieuchi
echo "<h1>Cấu trúc bảng danhgia_tieuchi</h1>";
$result = mysqli_query($connect, "DESCRIBE danhgia_tieuchi");

if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Không thể lấy thông tin của bảng danhgia_tieuchi: " . mysqli_error($connect) . "</p>";
}

// Đóng kết nối
mysqli_close($connect);
?> 