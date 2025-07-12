<?php
// Kết nối database
require "contdb.php";

echo "<h1>Cập nhật cấu trúc bảng khsanxuat</h1>";

// Kiểm tra xem trường ngay_tinh_han đã tồn tại trong bảng khsanxuat chưa
$result = mysqli_query($connect, "SHOW COLUMNS FROM khsanxuat LIKE 'ngay_tinh_han'");
$exists = (mysqli_num_rows($result) > 0);

if (!$exists) {
    // Thêm trường ngay_tinh_han vào bảng khsanxuat nếu chưa tồn tại
    $sql = "ALTER TABLE khsanxuat ADD COLUMN ngay_tinh_han VARCHAR(30) DEFAULT 'ngay_vao' AFTER han_xuly";
    
    if (mysqli_query($connect, $sql)) {
        echo "<p style='color: green;'>✅ Đã thêm trường ngay_tinh_han vào bảng khsanxuat thành công.</p>";
    } else {
        echo "<p style='color: red;'>❌ Lỗi khi thêm trường: " . mysqli_error($connect) . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Trường ngay_tinh_han đã tồn tại trong bảng khsanxuat.</p>";
}

// Kiểm tra cấu trúc bảng khsanxuat sau khi cập nhật
echo "<h2>Cấu trúc bảng khsanxuat sau khi cập nhật</h2>";
$result = mysqli_query($connect, "DESCRIBE khsanxuat");

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
    echo "<p>Không thể lấy thông tin của bảng khsanxuat: " . mysqli_error($connect) . "</p>";
}

// Đóng kết nối
mysqli_close($connect);
?> 