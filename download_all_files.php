<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kết nối database
include 'db_connect.php';

// Khởi tạo phiên làm việc nếu chưa có
session_start();

// Lấy thông tin từ URL
$id_sanxuat = isset($_GET['id']) ? intval($_GET['id']) : 0;
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';

if ($id_sanxuat <= 0 || empty($dept)) {
    die("Thiếu thông tin cần thiết");
}

// Hàm xóa thư mục và tất cả nội dung bên trong
function removeDir($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
            if (is_dir($dir . "/" . $object)) {
                removeDir($dir . "/" . $object);
            } else {
                unlink($dir . "/" . $object);
            }
        }
    }
    
    rmdir($dir);
}

try {
    // Tạo thư mục tạm nếu chưa tồn tại
    $temp_base_dir = 'template_files/temp';
    if (!is_dir($temp_base_dir)) {
        mkdir($temp_base_dir, 0777, true);
    }
    
    // Tạo thư mục tạm dành riêng cho lần tải này
    $unique_folder = uniqid('download_', true);
    $temp_dir = $temp_base_dir . '/' . $unique_folder;
    mkdir($temp_dir, 0777);
    
    // Tạo tên file zip và đường dẫn
    $sql_product = "SELECT style, po FROM khsanxuat WHERE stt = ?";
    $stmt_product = $connect->prepare($sql_product);
    $stmt_product->bind_param("i", $id_sanxuat);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    
    if ($result_product->num_rows === 0) {
        removeDir($temp_dir);
        die("Không tìm thấy mã hàng");
    }
    
    $product = $result_product->fetch_assoc();
    $style = $product['style'];
    $po = $product['po'];
    
    // Tạo tên file zip với thông tin mã hàng, PO và bộ phận
    $safe_style = preg_replace('/[^\p{L}\p{N}\s\-\.\_]/u', '_', $style);
    $safe_po = preg_replace('/[^\p{L}\p{N}\s\-\.\_]/u', '_', $po);
    $safe_dept = preg_replace('/[^\p{L}\p{N}\s\-\.\_]/u', '_', $dept);
    
    $zip_filename = $safe_style . '_' . $safe_po . '_' . $safe_dept . '_files.zip';
    $zip_path = $temp_base_dir . '/' . $zip_filename;
    
    // Lấy danh sách các file đã upload
    $sql_files = "SELECT f.*, t.template_name
                 FROM dept_template_files f
                 JOIN dept_templates t ON f.id_template = t.id
                 WHERE f.id_khsanxuat = ? AND f.dept = ?
                 ORDER BY f.upload_date DESC";
    $stmt_files = $connect->prepare($sql_files);
    $stmt_files->bind_param("is", $id_sanxuat, $dept);
    $stmt_files->execute();
    $result_files = $stmt_files->get_result();
    
    if ($result_files->num_rows === 0) {
        removeDir($temp_dir);
        die("Không có file nào để tải xuống");
    }
    
    // Danh sách các file đã sao chép
    $copied_files = array();
    
    // Lưu các file vào thư mục tạm
    while ($file = $result_files->fetch_assoc()) {
        // Đường dẫn file gốc
        $source_path = $file['file_path'];
        
        // Chỉ xử lý nếu file tồn tại
        if (file_exists($source_path) && is_file($source_path)) {
            // Sử dụng tên file gốc
            $original_filename = $file['file_name'];
            
            // Làm sạch tên file để tránh lỗi
            $clean_filename = preg_replace('/[^\p{L}\p{N}\s\-\.\_]/u', '_', $original_filename);
            
            // Tạo đường dẫn đầy đủ trong thư mục tạm
            $temp_path = $temp_dir . '/' . $clean_filename;
            
            // Xử lý trùng lặp tên file
            $counter = 1;
            while (file_exists($temp_path)) {
                $name = pathinfo($clean_filename, PATHINFO_FILENAME);
                $ext = pathinfo($clean_filename, PATHINFO_EXTENSION);
                $clean_filename = $name . '_' . $counter . '.' . $ext;
                $temp_path = $temp_dir . '/' . $clean_filename;
                $counter++;
            }
            
            // Sao chép file
            if (copy($source_path, $temp_path)) {
                $copied_files[] = array(
                    'path' => $temp_path,
                    'name' => $clean_filename
                );
            }
        }
    }
    
    // Kiểm tra số lượng file đã sao chép
    if (count($copied_files) === 0) {
        removeDir($temp_dir);
        die("Không thể sao chép file nào để nén");
    }
    
    // Tạo file ZIP với phương thức thứ nhất - ZipArchive
    $success = false;
    
    // Thử phương pháp 1: Sử dụng ZipArchive
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
            // Thêm file vào ZIP
            foreach ($copied_files as $file) {
                $zip->addFile($file['path'], $file['name']);
            }
            $zip->close();
            
            // Kiểm tra file ZIP
            if (file_exists($zip_path) && filesize($zip_path) > 0) {
                $success = true;
            }
        }
    }
    
    // Nếu phương pháp 1 thất bại, thử phương pháp 2: sử dụng PclZip
    if (!$success && file_exists('pclzip.lib.php')) {
        include_once('pclzip.lib.php');
        
        $file_list = array();
        foreach ($copied_files as $file) {
            $file_list[] = array(
                'filename' => $file['name'],
                'filepath' => $file['path']
            );
        }
        
        $pclzip = new PclZip($zip_path);
        $v_list = $pclzip->create($file_list);
        
        if ($v_list != 0) {
            $success = true;
        }
    }
    
    // Nếu cả hai phương pháp đều thất bại, thử phương pháp 3: sử dụng shell command
    if (!$success) {
        // Lưu danh sách file vào một file tạm
        $file_list_path = $temp_dir . '/file_list.txt';
        $current_dir = getcwd();
        
        // Thay đổi thư mục làm việc để đơn giản hóa đường dẫn
        chdir($temp_dir);
        
        // Tạo danh sách file để nén
        $file_list = '';
        foreach ($copied_files as $file) {
            $rel_path = basename($file['path']);
            $file_list .= '"' . $rel_path . '" ';
        }
        
        // Nén file bằng lệnh shell tùy thuộc vào hệ điều hành
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            if (class_exists('COM')) {
                $shell = new COM('Shell.Application');
                $zip_file = $shell->NameSpace(realpath($zip_path));
                
                // Thêm từng file vào ZIP
                foreach ($copied_files as $file) {
                    $rel_path = basename($file['path']);
                    $zip_file->CopyHere(realpath($rel_path));
                    // Đợi một chút để quá trình nén hoàn tất
                    sleep(1);
                }
                $success = true;
            }
        } else {
            // Linux/Unix
            $cmd = "zip -j \"" . realpath($zip_path) . "\" " . $file_list;
            exec($cmd, $output, $return_var);
            
            if ($return_var === 0) {
                $success = true;
            }
        }
        
        // Trở về thư mục làm việc ban đầu
        chdir($current_dir);
    }
    
    // Kiểm tra xem có thành công không
    if (!$success || !file_exists($zip_path) || filesize($zip_path) === 0) {
        removeDir($temp_dir);
        if (file_exists($zip_path)) {
            unlink($zip_path);
        }
        die("Không thể tạo file ZIP. Vui lòng thử lại sau.");
    }
    
    // Thiết lập header cho tải xuống
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($zip_path));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Đọc và gửi file
    ob_end_clean(); // Xóa bất kỳ output nào trước đây
    flush(); // Đảm bảo header đã được gửi đi
    
    readfile($zip_path);
    
    // Đợi một chút để đảm bảo file đã được gửi đi
    sleep(1);
    
    // Xóa thư mục tạm và file ZIP sau khi hoàn tất
    // Xóa riêng lẻ sau khi đã đọc file xong
    if (file_exists($zip_path)) {
        unlink($zip_path);
    }
    
    // Sau đó mới xóa thư mục tạm
    removeDir($temp_dir);
    
    exit;
} catch (Exception $e) {
    // Xử lý lỗi
    // Đảm bảo dọn dẹp tài nguyên nếu có lỗi
    if (isset($temp_dir) && is_dir($temp_dir)) {
        removeDir($temp_dir);
    }
    
    if (isset($zip_path) && file_exists($zip_path)) {
        unlink($zip_path);
    }
    
    die("Lỗi: " . $e->getMessage());
} 