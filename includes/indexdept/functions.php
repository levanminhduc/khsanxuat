<?php

function calculateDeadline($ngayin, $ngayout, $ngay_tinh_han, $so_ngay_xuly) {
    switch ($ngay_tinh_han) {
        case 'ngay_ra':
            $han_xuly = clone $ngayout;
            $han_xuly->modify("+{$so_ngay_xuly} days");
            break;
        case 'ngay_ra_tru':
            $han_xuly = clone $ngayout;
            $han_xuly->modify("-{$so_ngay_xuly} days");
            break;
        case 'ngay_vao_cong':
            $han_xuly = clone $ngayin;
            $han_xuly->modify("+{$so_ngay_xuly} days");
            break;
        default: // ngay_vao
            $han_xuly = clone $ngayin;
            $han_xuly->modify("-{$so_ngay_xuly} days");
    }
    return $han_xuly;
}

function getImageCount($connect, $id, $dept) {
    $sql = "SELECT COUNT(*) as image_count FROM khsanxuat_images WHERE id_khsanxuat = ? AND dept = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['image_count'];
}

function getFileCount($connect, $id, $dept) {
    $check_table = $connect->query("SHOW TABLES LIKE 'dept_template_files'");
    if ($check_table->num_rows === 0) {
        return 0;
    }

    $sql = "SELECT COUNT(*) as file_count FROM dept_template_files WHERE id_khsanxuat = ? AND dept = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['file_count'];
}

function getProductData($connect, $id) {
    $sql = "SELECT line1, xuong, po, style, qty, ngayin, ngayout, han_xuly, so_ngay_xuly, ngay_tinh_han
            FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }
    return $result->fetch_assoc();
}

function getEvaluationCriteria($connect, $id, $dept) {
    $sql = "SELECT tc.*, dg.nguoi_thuchien, dg.da_thuchien, dg.diem_danhgia,
                   dg.ghichu, dg.han_xuly, dg.so_ngay_xuly, dg.ngay_tinh_han
            FROM tieuchi_dept tc
            LEFT JOIN danhgia_tieuchi dg ON tc.id = dg.id_tieuchi AND dg.id_sanxuat = ?
            WHERE tc.dept = ?
            ORDER BY
                CASE tc.nhom
                    WHEN 'Nhóm Nghiệp Vụ' THEN 1
                    WHEN 'Nhóm May Mẫu' THEN 2
                    WHEN 'Nhóm Quy Trình' THEN 3
                    WHEN 'Kho Nguyên Liệu' THEN 1
                    WHEN 'Kho Phụ Liệu' THEN 2
                    ELSE 4
                END, tc.thutu";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id, $dept);
    $stmt->execute();
    return $stmt->get_result();
}

function getCriteriaList($connect, $dept) {
    $sql = "SELECT id, thutu, noidung FROM tieuchi_dept WHERE dept = ? ORDER BY thutu";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $dept);
    $stmt->execute();
    return $stmt->get_result();
}

function getStaffByDept($connect, $dept) {
    $sql = "SELECT id, ten FROM nhan_vien WHERE phong_ban = ? AND active = 1 ORDER BY ten";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $dept);
    $stmt->execute();
    return $stmt->get_result();
}

function getXuongList($connect) {
    $sql = "SELECT DISTINCT xuong FROM khsanxuat WHERE xuong != '' ORDER BY xuong";
    return $connect->query($sql);
}

function displayMessages() {
    if (isset($_GET['success'])) {
        $messages = [
            'updated' => 'Cập nhật hạn xử lý thành công!',
            'updated_deadline' => 'Cập nhật hạn xử lý cho tiêu chí thành công!'
        ];
        $msg = $messages[$_GET['success']] ?? 'Lưu đánh giá thành công!';
        echo '<div class="success-message">' . $msg . '</div>';
    }

    if (isset($_GET['error'])) {
        $errors = [
            'not_authorized' => 'Bạn không có quyền thực hiện thao tác này!',
            'missing_data' => 'Thiếu dữ liệu cần thiết!',
            'record_not_found' => 'Không tìm thấy bản ghi!',
            'not_updated' => 'Cập nhật không thành công!'
        ];
        $msg = $errors[$_GET['error']] ?? 'Có lỗi xảy ra: ' . htmlspecialchars($_GET['error']);
        echo '<div class="error-message">' . $msg . '</div>';
    }
}

function checkMissingFiles($required_files) {
    $missing = [];
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            $missing[] = $file;
        }
    }
    return $missing;
}

function displayMissingFilesWarning($missing_files) {
    if (!empty($missing_files)) {
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">';
        echo '<strong>Cảnh báo:</strong> Không tìm thấy các file sau: ' . implode(', ', $missing_files);
        echo '</div>';
    }
}
