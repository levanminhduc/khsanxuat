<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';
requireFeature('edit_settings', 'redirect');
require_once BASE_PATH . '/includes/security/csrf-helper.php';
require_once BASE_PATH . '/helpers/template_files.php';

// Chỉ nhận POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

verifyCsrfOrDie();

if (!canManageTemplates()) {
    http_response_code(403);
    die('Bạn không có quyền thực hiện thao tác này.');
}

$action     = isset($_POST['action']) ? $_POST['action'] : '';
$id_sanxuat = isset($_POST['id_sanxuat']) ? intval($_POST['id_sanxuat']) : 0;
$dept       = isset($_POST['dept']) ? $_POST['dept'] : '';

if ($id_sanxuat <= 0 || !isValidTemplateDept($dept)) {
    http_response_code(400);
    die('Tham số không hợp lệ.');
}

// Đặt flash + redirect về trang (PRG). $type: 'success' | 'error'.
function flash_redirect($type, $msg, $id_sanxuat, $dept) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: ' . BASE_URL . '/pages/file_templates.php?id=' . $id_sanxuat . '&dept=' . urlencode($dept));
    exit;
}

if ($action === 'add_template') {
    $template_name = trim(isset($_POST['template_name']) ? $_POST['template_name'] : '');
    $template_description = isset($_POST['template_description']) ? $_POST['template_description'] : '';

    if ($template_name === '') {
        flash_redirect('error', 'Vui lòng nhập tên biểu mẫu.', $id_sanxuat, $dept);
    }

    $check = $connect->prepare("SELECT id FROM dept_templates WHERE dept = ? AND template_name = ?");
    $check->bind_param("ss", $dept, $template_name);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        flash_redirect('error', "Biểu mẫu \"$template_name\" đã tồn tại trong bộ phận này.", $id_sanxuat, $dept);
    }

    $ins = $connect->prepare("INSERT INTO dept_templates (dept, template_name, template_description) VALUES (?, ?, ?)");
    $ins->bind_param("sss", $dept, $template_name, $template_description);
    if ($ins->execute()) {
        flash_redirect('success', "Đã thêm biểu mẫu \"$template_name\".", $id_sanxuat, $dept);
    }
    error_log('template_actions add_template: ' . $connect->error);
    flash_redirect('error', 'Lỗi khi thêm biểu mẫu.', $id_sanxuat, $dept);
}

if ($action === 'upload') {
    $id_template = isset($_POST['id_template']) ? intval($_POST['id_template']) : 0;
    if ($id_template <= 0 || !isset($_FILES['template_file'])) {
        flash_redirect('error', 'Vui lòng chọn biểu mẫu và file.', $id_sanxuat, $dept);
    }

    // Lấy style để đặt tên file.
    $stmt_style = $connect->prepare("SELECT style FROM khsanxuat WHERE stt = ?");
    $stmt_style->bind_param("i", $id_sanxuat);
    $stmt_style->execute();
    $row_style = $stmt_style->get_result()->fetch_assoc();
    if (!$row_style) {
        flash_redirect('error', 'Không tìm thấy đơn hàng.', $id_sanxuat, $dept);
    }
    $safe_style = preg_replace('/[^a-zA-Z0-9_]/', '_', $row_style['style']);

    // Thư mục đích (filesystem tuyệt đối qua BASE_PATH). DB lưu đường dẫn tương đối-từ-root.
    $rel_dir = "template_files/$dept/$id_sanxuat/template_$id_template";
    $abs_dir = BASE_PATH . '/' . $rel_dir;
    if (!is_dir($abs_dir) && !mkdir($abs_dir, 0755, true) && !is_dir($abs_dir)) {
        error_log('template_actions upload mkdir failed: ' . $abs_dir);
        flash_redirect('error', 'Không tạo được thư mục lưu file.', $id_sanxuat, $dept);
    }

    $files = $_FILES['template_file'];
    $total = count($files['name']);
    $success_count = 0;
    $errors = [];

    $ins = $connect->prepare("INSERT INTO dept_template_files (id_template, id_khsanxuat, dept, file_path, file_name, file_type, upload_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");

    for ($i = 0; $i < $total; $i++) {
        $orig_name = $files['name'][$i];
        $check = validateTemplateUpload($orig_name, $files['tmp_name'][$i], $files['size'][$i], $files['error'][$i]);
        if (!$check['ok']) {
            $errors[] = htmlspecialchars($orig_name) . ': ' . $check['error'];
            continue;
        }

        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        $new_name = $safe_style . '_' . $dept . '_' . date('YmdHis') . '_' . $i . '.' . $ext;
        $rel_path = $rel_dir . '/' . $new_name;
        $abs_path = $abs_dir . '/' . $new_name;

        if (!move_uploaded_file($files['tmp_name'][$i], $abs_path)) {
            $errors[] = htmlspecialchars($orig_name) . ': lỗi lưu file.';
            continue;
        }

        $ins->bind_param("iissss", $id_template, $id_sanxuat, $dept, $rel_path, $orig_name, $check['file_type']);
        if ($ins->execute()) {
            $success_count++;
        } else {
            @unlink($abs_path);
            error_log('template_actions upload insert: ' . $connect->error);
            $errors[] = htmlspecialchars($orig_name) . ': lỗi ghi DB.';
        }
    }

    if ($success_count > 0 && empty($errors)) {
        flash_redirect('success', "Đã upload $success_count file.", $id_sanxuat, $dept);
    }
    $msg = $success_count > 0 ? "Đã upload $success_count file. " : '';
    $msg .= 'Lỗi: ' . implode(' | ', $errors);
    flash_redirect($success_count > 0 ? 'success' : 'error', $msg, $id_sanxuat, $dept);
}

if ($action === 'delete') {
    $file_id = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;
    if ($file_id <= 0) {
        flash_redirect('error', 'Thiếu mã file.', $id_sanxuat, $dept);
    }

    $sel = $connect->prepare("SELECT file_path FROM dept_template_files WHERE id = ? AND id_khsanxuat = ? AND dept = ?");
    $sel->bind_param("iis", $file_id, $id_sanxuat, $dept);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();
    if (!$row) {
        flash_redirect('error', 'Không tìm thấy file cần xoá.', $id_sanxuat, $dept);
    }

    $abs_path = BASE_PATH . '/' . $row['file_path'];
    if (is_file($abs_path) && !unlink($abs_path)) {
        error_log('template_actions delete unlink failed: ' . $abs_path);
    }

    $del = $connect->prepare("DELETE FROM dept_template_files WHERE id = ?");
    $del->bind_param("i", $file_id);
    if ($del->execute()) {
        flash_redirect('success', 'Đã xoá file.', $id_sanxuat, $dept);
    }
    error_log('template_actions delete: ' . $connect->error);
    flash_redirect('error', 'Lỗi khi xoá file.', $id_sanxuat, $dept);
}

// Action không hợp lệ.
http_response_code(400);
die('Hành động không hợp lệ.');
