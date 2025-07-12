<?php
// Script để sửa triệt để lỗi JavaScript trong file indexdept.php

// Đọc nội dung file
$file_path = 'indexdept.php';
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

// Tìm tất cả các khối script trong file
preg_match_all('/<script>(.*?)<\/script>/s', $content, $matches, PREG_OFFSET_CAPTURE);

if (count($matches[0]) < 1) {
    die("Không tìm thấy khối script nào trong file.");
}

echo "Tìm thấy " . count($matches[0]) . " khối script.\n";

// Lưu tất cả các hàm JavaScript đã tìm thấy và các biến/mã khởi tạo
$all_functions = [];
$initialization_code = "";
$function_pattern = '/function\s+([a-zA-Z0-9_]+)\s*\([^)]*\)\s*\{(?>[^{}]+|(?R))*\}/s';

// Mặc định lấy nội dung từ khối script đầu tiên
$all_script_content = $matches[1][0][0];

// Nếu có nhiều khối script, kết hợp lại
if (count($matches[1]) > 1) {
    for ($i = 1; $i < count($matches[1]); $i++) {
        $all_script_content .= "\n\n" . $matches[1][$i][0];
    }
}

// Tìm tất cả các hàm
preg_match_all($function_pattern, $all_script_content, $function_matches, PREG_SET_ORDER);

// Tạo một bản sao của nội dung script
$remaining_content = $all_script_content;

// Trích xuất các hàm và xóa chúng khỏi nội dung còn lại
foreach ($function_matches as $function) {
    $function_name = preg_replace('/function\s+([a-zA-Z0-9_]+).*$/s', '$1', $function[0]);
    $function_body = $function[0];
    
    if (!isset($all_functions[$function_name])) {
        $all_functions[$function_name] = $function_body;
        echo "Tìm thấy hàm: $function_name\n";
    } else {
        echo "Tìm thấy hàm trùng lặp: $function_name\n";
    }
    
    // Xóa hàm khỏi nội dung còn lại
    $remaining_content = str_replace($function_body, '', $remaining_content);
}

// Làm sạch các dòng trống liên tiếp trong phần còn lại
$remaining_content = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $remaining_content);
$initialization_code = trim($remaining_content);

// Tạo một khối script mới
$new_script = "<script>\n";
$new_script .= "// Global JavaScript được tạo lại để loại bỏ lỗi cú pháp\n";
$new_script .= "// Được tạo bởi fixjs_complete.php ngày " . date('Y-m-d H:i:s') . "\n\n";

// Thêm mã khởi tạo (biến, các câu lệnh không phải hàm)
if (!empty($initialization_code)) {
    $new_script .= "// Biến và mã khởi tạo\n";
    $new_script .= $initialization_code . "\n\n";
}

// Thêm các hàm không trùng lặp
$new_script .= "// Các hàm JavaScript\n";
foreach ($all_functions as $function_name => $function_body) {
    $new_script .= $function_body . "\n\n";
}

$new_script .= "</script>";

// Xóa tất cả các khối script hiện có
$new_content = preg_replace('/<script>.*?<\/script>/s', '', $content);

// Chèn khối script mới vào trước thẻ đóng </body>
$new_content = str_replace('</body>', $new_script . "\n</body>", $new_content);

// Lưu file mới
if (file_put_contents($file_path, $new_content) === false) {
    die("Không thể ghi file $file_path");
}

echo "Đã sửa thành công các lỗi JavaScript trong file $file_path\n";
?> 