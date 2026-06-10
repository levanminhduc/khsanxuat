<?php
// Helper dùng chung cho chức năng Quản lý biểu mẫu (pages/file_templates.php + actions/template_actions.php).

// Nhãn hiển thị bộ phận — GIỮ NGUYÊN nhãn chữ thường mà file_templates đang dùng (không đổi giao diện).
// KHÁC với bản chữ HOA ở includes/indexdept/config.php — KHÔNG dùng chung để tránh đổi UI.
$template_dept_names = [
    'kehoach'                  => 'Kế Hoạch',
    'cat'                      => 'Cắt',
    'ep_keo'                   => 'Ép Keo',
    'chuanbi_sanxuat_phong_kt' => 'Phòng Kỹ Thuật',
    'may'                      => 'May',
    'hoan_thanh'               => 'Hoàn Thành',
    'co_dien'                  => 'Cơ Điện',
    'kcs'                      => 'KCS',
    'ui_thanh_pham'            => 'Ủi Thành Phẩm',
    'chuyen_may'               => 'Chuyền May',
    'kho'                      => 'Kho Nguyên, Phụ Liệu',
    'quan_ly_cl'               => 'Quản Lý Chất Lượng',
    'quan_ly_sx'               => 'Quản Lý Sản Xuất',
];

// Khe phân quyền — CHỖ CẮM RBAC tương lai (xem docs/requirements/rbac-shared-roles.md).
// Hiện trả true (giữ hành vi cũ $is_admin = true). Sau đổi ruột hàm này trỏ sang bảng RBAC,
// KHÔNG phải sửa lại các trang gọi nó.
function canManageTemplates() {
    return true;
}

// Whitelist dept — chặn path traversal khi ghép template_files/{$dept}/...
function isValidTemplateDept($dept) {
    global $template_dept_names;
    return isset($template_dept_names[$dept]);
}

// Kiểm tra 1 file upload. Trả ['ok'=>bool, 'file_type'=>string, 'error'=>string].
// file_type: 'image' | 'pdf' | 'excel' | 'word'.
function validateTemplateUpload($name, $tmp_name, $size, $error) {
    $result = ['ok' => false, 'file_type' => '', 'error' => ''];

    if ($error !== UPLOAD_ERR_OK) {
        $result['error'] = "Lỗi upload (mã $error).";
        return $result;
    }

    if ($size > 31457280) { // 30MB = 30*1024*1024
        $result['error'] = "File quá lớn. Giới hạn 30MB.";
        return $result;
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'webp', 'pdf', 'xls', 'xlsx', 'doc', 'docx'];
    if (!in_array($ext, $allowed_exts, true)) {
        $result['error'] = "Định dạng không cho phép. Chỉ nhận ảnh, PDF, Excel, Word.";
        return $result;
    }

    // MIME thật. Nới: doc/xls cũ cho octet-stream; xlsx/docx (zip-based) cho application/zip.
    $mime_map = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'bmp'  => ['image/bmp', 'image/x-ms-bmp'],
        'tif'  => ['image/tiff'],
        'tiff' => ['image/tiff'],
        'webp' => ['image/webp'],
        'pdf'  => ['application/pdf'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    if (!in_array($real_mime, $mime_map[$ext], true)) {
        $result['error'] = "Nội dung file không khớp đuôi .$ext (phát hiện: $real_mime).";
        return $result;
    }

    $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'webp'];
    if (in_array($ext, $image_exts, true)) {
        if (getimagesize($tmp_name) === false) {
            $result['error'] = "File ảnh hỏng hoặc không hợp lệ.";
            return $result;
        }
        $result['file_type'] = 'image';
    } elseif ($ext === 'pdf') {
        $result['file_type'] = 'pdf';
    } elseif (in_array($ext, ['xls', 'xlsx'], true)) {
        $result['file_type'] = 'excel';
    } else {
        $result['file_type'] = 'word';
    }

    $result['ok'] = true;
    return $result;
}
