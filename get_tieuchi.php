<?php
include 'db_connect.php';

if ($connect->connect_error) {
    die("Kết nối thất bại: " . $connect->connect_error);
}

$sql = "SELECT id, thutu, nhom, noidung 
        FROM tieuchi_dept 
        WHERE dept = 'chuanbi_sanxuat_phong_kt'
        ORDER BY 
            CASE nhom 
                WHEN 'Nhóm Nghiệp Vụ' THEN 1 
                WHEN 'Nhóm May Mẫu' THEN 2 
                WHEN 'Nhóm Quy Trình' THEN 3 
                ELSE 4 
            END, 
            thutu";

$result = $connect->query($sql);

if ($result) {
    $current_nhom = '';
    while ($row = $result->fetch_assoc()) {
        if ($current_nhom != $row['nhom']) {
            $current_nhom = $row['nhom'];
            echo "\n=== " . ($row['nhom'] ? $row['nhom'] : 'Chưa phân nhóm') . " ===\n";
        }
        echo "ID: " . $row['id'] . "\n";
        echo "STT: " . $row['thutu'] . "\n";
        echo "Nội dung: " . $row['noidung'] . "\n";
        echo "------------------------\n";
    }
    
    if ($result->num_rows == 0) {
        echo "Không tìm thấy tiêu chí nào cho bộ phận chuẩn bị sản xuất.\n";
    }
} else {
    echo "Lỗi truy vấn: " . $connect->error;
}
?> 