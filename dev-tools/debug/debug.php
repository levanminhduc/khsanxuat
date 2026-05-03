<?php
// Đảm bảo hiển thị lỗi chi tiết
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kết nối database
include 'contdb.php';

echo "<h1>Debug Database</h1>";

// Kiểm tra kết nối
if (!isset($connect) || $connect === null) {
    die("<p style='color:red'>Lỗi kết nối database</p>");
}

echo "<h2>Thông tin kết nối</h2>";
echo "<p>Host: " . $connect->host_info . "</p>";

// Kiểm tra cấu trúc bảng danhgia_tieuchi
echo "<h2>Cấu trúc bảng danhgia_tieuchi</h2>";
try {
    $result = $connect->query("SHOW COLUMNS FROM danhgia_tieuchi");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ? $row['Default'] : 'NULL') . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color:red'>Không thể lấy cấu trúc bảng danhgia_tieuchi: " . $connect->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}

// Kiểm tra dữ liệu trong bảng danhgia_tieuchi
echo "<h2>Dữ liệu bảng danhgia_tieuchi (10 dòng đầu tiên)</h2>";
try {
    $result = $connect->query("SELECT * FROM danhgia_tieuchi LIMIT 10");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        
        // Hiển thị header
        $fields = $result->fetch_fields();
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // Hiển thị dữ liệu
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value === null ? 'NULL' : $value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color:orange'>Không có dữ liệu trong bảng danhgia_tieuchi hoặc lỗi truy vấn: " . $connect->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}

// Kiểm tra log file
echo "<h2>Log file gần đây</h2>";
if (file_exists('update_deadline_debug.log')) {
    echo "<pre>";
    $log_content = file_get_contents('update_deadline_debug.log');
    
    // Hiển thị 20 dòng cuối cùng
    $lines = explode("\n", $log_content);
    $lines = array_slice($lines, max(0, count($lines) - 20));
    
    foreach ($lines as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:orange'>Không tìm thấy file log.</p>";
}
?> 