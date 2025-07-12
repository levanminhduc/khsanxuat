<?php
// Script để sửa lỗi trùng lặp JavaScript trong file indexdept.php
$file_path = 'indexdept.php';

// Đọc nội dung file
$content = file_get_contents($file_path);
if ($content === false) {
    die("Không thể đọc file $file_path");
}

// Tạo bản sao lưu
$backup_path = $file_path . '.bak.' . date('Y-m-d-H-i-s');
if (file_put_contents($backup_path, $content) === false) {
    die("Không thể tạo bản sao lưu tại $backup_path");
}
echo "Đã tạo bản sao lưu tại $backup_path\n";

// Tìm các khối script
$pattern = '/<script>(.*?)<\/script>/s';
preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

if (count($matches[0]) < 2) {
    die("Không tìm thấy đủ 2 khối script để sửa.");
}

// Lấy nội dung của các khối script
$script1 = $matches[1][0][0];
$script2 = $matches[1][1][0];

// Tìm và xóa các hàm trùng lặp
$functions_to_check = ['saveDefaultSetting', 'saveAllDefaultSettings'];
$cleaned_script2 = $script2;

foreach ($functions_to_check as $func) {
    // Tìm hàm trong script2
    $func_pattern = '/function\s+' . preg_quote($func, '/') . '\s*\([^)]*\)\s*\{.*?\}/s';
    if (preg_match($func_pattern, $cleaned_script2, $func_match)) {
        echo "Tìm thấy hàm $func trong khối script thứ 2. Đang xóa...\n";
        $cleaned_script2 = str_replace($func_match[0], '// Removed duplicate function ' . $func, $cleaned_script2);
    }
}

// Tạo nội dung mới
$new_content = str_replace($script2, $cleaned_script2, $content);

// Lưu file
if (file_put_contents($file_path, $new_content) === false) {
    die("Không thể ghi file $file_path");
}

echo "Đã xóa các hàm trùng lặp trong file $file_path\n";
?> 