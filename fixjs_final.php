<?php
// Script để sửa triệt để lỗi JavaScript trong file indexdept.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Bắt đầu sửa lỗi JavaScript...\n";

// Đọc nội dung file
$file_path = 'indexdept.php';
echo "Đọc file $file_path...\n";
$content = file_get_contents($file_path);
if ($content === false) {
    die("Không thể đọc file $file_path\n");
}

// Tạo bản sao lưu
$backup_path = $file_path . '.bak.' . date('Y-m-d-H-i-s');
if (file_put_contents($backup_path, $content) === false) {
    die("Không thể tạo bản sao lưu tại $backup_path\n");
}
echo "Đã tạo bản sao lưu tại $backup_path\n";

// Tìm tất cả các khối script trong file
echo "Tìm các khối script...\n";
preg_match_all('/<script>(.*?)<\/script>/s', $content, $matches, PREG_OFFSET_CAPTURE);

if (count($matches[0]) < 1) {
    die("Không tìm thấy khối script nào trong file.\n");
}

echo "Tìm thấy " . count($matches[0]) . " khối script.\n";

// Trích xuất tất cả mã JavaScript từ tất cả các khối script
$all_js_code = "";
foreach ($matches[1] as $script) {
    $all_js_code .= $script[0] . "\n\n";
}

echo "Đã trích xuất " . strlen($all_js_code) . " ký tự mã JavaScript.\n";

// Tạo một khối script mới với mã JavaScript đã được sửa
$new_script = "<script>\n";
$new_script .= "// JavaScript được tái cấu trúc và sửa lỗi\n";
$new_script .= "// Được tạo bởi fixjs_final.php ngày " . date('Y-m-d H:i:s') . "\n\n";

// 1. Trích xuất các khai báo biến (var, let, const)
// Biến không hợp lệ sẽ bị loại bỏ
$variable_pattern = '/(var|let|const)\s+([a-zA-Z0-9_$]+)\s*=\s*([^;]*);/';
preg_match_all($variable_pattern, $all_js_code, $variable_matches, PREG_SET_ORDER);

$safe_variables = [];
$variables = [];
foreach ($variable_matches as $var_match) {
    $var_type = trim($var_match[1]);  // var, let, const
    $var_name = trim($var_match[2]);  // tên biến
    $var_value = trim($var_match[3]); // giá trị
    
    // Tránh các biến có chứa tham chiếu đến biến không tồn tại
    $has_undefined_reference = false;
    
    // Lọc biến không sử dụng tham chiếu chưa khởi tạo
    // Ví dụ: const tieuchi_id = element.getAttribute('data-tieuchi-id');
    // 'element' chưa được định nghĩa
    if (preg_match('/\b(element|idTieuchi|id_tieuchi)\b/', $var_value) && 
        !isset($variables['element']) && 
        !isset($variables['idTieuchi']) && 
        !isset($variables['id_tieuchi'])) {
        $has_undefined_reference = true;
    }
    
    // Kiểm tra đặc biệt cho các đoạn PHP
    if (strpos($var_value, '<?php') !== false) {
        // Đảm bảo có dấu đóng PHP
        if (strpos($var_value, '?>') === false) {
            $var_value = str_replace('<?php', '<?php', $var_value);
            $var_value = str_replace(';', '; ?>', $var_value);
        }
    }
    
    if (!$has_undefined_reference) {
        $variables[$var_name] = array(
            'type' => $var_type,
            'name' => $var_name,
            'value' => $var_value
        );
        echo "Tìm thấy biến: $var_name\n";
    } else {
        echo "Bỏ qua biến có tham chiếu không hợp lệ: $var_name\n";
    }
}

// 2. Trích xuất các hàm
$function_pattern = '/function\s+([a-zA-Z0-9_$]+)\s*\([^)]*\)\s*\{(?:[^{}]|(?R))*\}/';
preg_match_all($function_pattern, $all_js_code, $function_matches, PREG_SET_ORDER);

$functions = [];
foreach ($function_matches as $func_match) {
    $func_body = $func_match[0];
    $func_name = $func_match[1];
    
    if (!isset($functions[$func_name])) {
        $functions[$func_name] = $func_body;
        echo "Tìm thấy hàm: $func_name\n";
    } else {
        echo "Tìm thấy hàm trùng lặp: $func_name (bỏ qua)\n";
    }
}

// 3. Trích xuất các event listeners
$event_pattern = '/(document|window)\.addEventListener\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*(?:function\s*\([^)]*\)\s*\{(?:[^{}]|(?R))*\}|[a-zA-Z0-9_$]+)\s*[,)]/';
preg_match_all($event_pattern, $all_js_code, $event_matches, PREG_SET_ORDER);

$events = [];
foreach ($event_matches as $event_match) {
    $event_target = $event_match[1];  // document, window, etc.
    $event_type = $event_match[2];    // DOMContentLoaded, click, etc.
    $event_code = $event_match[0];    // Toàn bộ lệnh addEventListener
    
    echo "Tìm thấy sự kiện: $event_target.addEventListener('$event_type', func...\n";
    $events[] = $event_code;
}

// Tái tạo mã JavaScript từ các phần đã trích xuất
echo "Tái tạo mã JavaScript...\n";

// Thêm các biến vào script mới
if (!empty($variables)) {
    $new_script .= "// Biến JavaScript\n";
    foreach ($variables as $var) {
        $new_script .= "{$var['type']} {$var['name']} = {$var['value']};\n";
    }
    $new_script .= "\n";
}

// Thêm các hàm vào script mới
if (!empty($functions)) {
    $new_script .= "// Các hàm JavaScript\n";
    foreach ($functions as $func_name => $func_code) {
        $new_script .= $func_code . "\n\n";
    }
}

// Thêm các event listeners vào script mới
if (!empty($events)) {
    $new_script .= "// Các event listeners\n";
    foreach ($events as $event_code) {
        $new_script .= $event_code . "\n\n";
    }
}

// Kết thúc script mới
$new_script .= "</script>";

// Xóa tất cả các khối script hiện có
echo "Xóa các khối script cũ...\n";
$new_content = preg_replace('/<script>.*?<\/script>/s', '', $content);

// Chèn khối script mới vào trước thẻ đóng </body>
echo "Chèn khối script mới vào trước thẻ </body>...\n";
$new_content = str_replace('</body>', $new_script . "\n</body>", $new_content);

// Lưu file mới
echo "Lưu file mới...\n";
if (file_put_contents($file_path, $new_content) === false) {
    die("Không thể ghi file $file_path\n");
}

echo "Đã sửa thành công các lỗi JavaScript trong file $file_path\n";
?> 