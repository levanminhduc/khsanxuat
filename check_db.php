<?php
// Kết nối database
include 'contdb.php';

// Kiểm tra bảng
$result = $connect->query("SHOW TABLES LIKE 'khsanxuat_default_settings'");
echo "Bảng khsanxuat_default_settings " . ($result->num_rows > 0 ? "tồn tại" : "không tồn tại") . "\n";

if ($result->num_rows > 0) {
    // Kiểm tra cấu trúc
    $result = $connect->query("DESCRIBE khsanxuat_default_settings");
    echo "\nCấu trúc bảng khsanxuat_default_settings:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    // Kiểm tra số lượng bản ghi
    $result = $connect->query("SELECT COUNT(*) as count FROM khsanxuat_default_settings");
    $row = $result->fetch_assoc();
    echo "\nSố lượng bản ghi: " . $row['count'] . "\n";
    
    // Hiển thị 5 bản ghi đầu tiên
    $result = $connect->query("SELECT * FROM khsanxuat_default_settings LIMIT 5");
    if ($result->num_rows > 0) {
        echo "\n5 bản ghi đầu tiên:\n";
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . ", Dept: " . $row['dept'] . ", ID Tiêu chí: " . $row['id_tieuchi'];
            echo ", Người chịu trách nhiệm: " . ($row['nguoi_chiu_trachnhiem_default'] ?? 'NULL') . "\n";
        }
    } else {
        echo "\nKhông có bản ghi nào\n";
    }
} else {
    // Kiểm tra bảng default_settings cũ
    $result = $connect->query("SHOW TABLES LIKE 'default_settings'");
    echo "\nBảng default_settings " . ($result->num_rows > 0 ? "tồn tại" : "không tồn tại") . "\n";
    
    if ($result->num_rows > 0) {
        $result = $connect->query("SELECT COUNT(*) as count FROM default_settings");
        $row = $result->fetch_assoc();
        echo "Số lượng bản ghi trong bảng default_settings: " . $row['count'] . "\n";
    }
}

// Kiểm tra bảng tieuchi_dept
$result = $connect->query("SHOW TABLES LIKE 'tieuchi_dept'");
echo "\nBảng tieuchi_dept " . ($result->num_rows > 0 ? "tồn tại" : "không tồn tại") . "\n";

if ($result->num_rows > 0) {
    $result = $connect->query("SELECT COUNT(*) as count FROM tieuchi_dept");
    $row = $result->fetch_assoc();
    echo "Số lượng bản ghi trong bảng tieuchi_dept: " . $row['count'] . "\n";
}

// Kiểm tra giá trị của biến $dept trong indexdept.php
echo "\nKiểm tra giá trị của biến \$dept trong indexdept.php...\n";
$file_content = file_get_contents('indexdept.php');
if (preg_match('/\$dept\s*=\s*[\'"]([^\'"]+)[\'"]/', $file_content, $matches)) {
    echo "Biến \$dept được gán giá trị: " . $matches[1] . "\n";
} else if (preg_match('/\$dept\s*=\s*\$_GET\[[\'"]([^\'"]+)[\'"]\]/', $file_content, $matches)) {
    echo "Biến \$dept lấy từ tham số GET: " . $matches[1] . "\n";
} else {
    echo "Không tìm thấy cách gán giá trị cho biến \$dept\n";
}

// Đóng kết nối
$connect->close();
?> 