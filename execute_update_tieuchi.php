<?php
include 'db_connect.php';

// Đọc nội dung file SQL
$sql_content = file_get_contents('update_chuanbi_tieuchi_correct.sql');

// Tách các câu lệnh SQL
$sql_statements = explode(';', $sql_content);

// Thực thi từng câu lệnh
$success = true;
$error_messages = [];

foreach ($sql_statements as $sql) {
    $sql = trim($sql);
    if (empty($sql)) continue;
    
    if (!$connect->query($sql)) {
        $success = false;
        $error_messages[] = "Lỗi khi thực thi câu lệnh: " . $connect->error;
    }
}

// Hiển thị kết quả
if ($success) {
    echo "Đã cập nhật thành công các tiêu chí cho bộ phận chuẩn bị sản xuất!";
} else {
    echo "Có lỗi xảy ra:<br>";
    foreach ($error_messages as $error) {
        echo "- " . $error . "<br>";
    }
}
?> 