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
            ORDER BY " . getNhomOrderByCase() . ", tc.thutu";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id, $dept);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Thu thập + tính toán dữ liệu bảng đánh giá, đã sắp xếp sẵn để render.
 *
 * Tách khỏi phần render vì 2 khoá ưu tiên (quá hạn / đã chấm) chỉ tồn tại ở tầng PHP:
 * hạn xử lý của tiêu chí có thể NULL rồi fallback về $han_xuly (do calculateDeadline()
 * tính), còn điểm hiệu lực phụ thuộc getDefaultScoreValueForCriteria(). SQL không biết
 * hai giá trị này nên không sắp xếp được bằng ORDER BY.
 *
 * @param DateTime $han_xuly           Hạn chung của đơn, dùng khi tiêu chí chưa có hạn riêng.
 * @param string   $han_xuly_formatted Chuỗi d/m/Y tương ứng $han_xuly.
 * @return array{rows: array, staff_list: array, total_tieuchi: int, completed_tieuchi: int,
 *               total_points: float, max_possible_points: float}
 */
function fetchEvaluationRows($connect, $id, $dept, $han_xuly, $han_xuly_formatted, $so_ngay_xuly) {
    $result = getEvaluationCriteria($connect, $id, $dept);

    $staff_list = [];
    $result_staff = getStaffByDept($connect, $dept);
    while ($staff_row = $result_staff->fetch_assoc()) {
        $staff_list[] = $staff_row;
    }

    $total_tieuchi = 0;
    $completed_tieuchi = 0;
    $total_points = 0;
    $max_possible_points = 0;

    $rows_data = [];
    $nhom_order_map = []; // giữ đúng thứ tự nhóm do getNhomOrderByCase() trả về
    $now = new DateTime();

    while ($row = $result->fetch_assoc()) {
        $total_tieuchi++;

        if (!array_key_exists((string) $row['nhom'], $nhom_order_map)) {
            $nhom_order_map[(string) $row['nhom']] = count($nhom_order_map);
        }

        $score_options = getScoreOptionsForCriteria($connect, $row['id'], $dept, $row['thutu']);
        $default_score_value = getDefaultScoreValueForCriteria($connect, $row['id'], $dept, $row['thutu']);
        $has_saved_score = isset($row['diem_danhgia']) && $row['diem_danhgia'] !== null;
        $effective_score = $has_saved_score ? floatval($row['diem_danhgia']) : floatval($default_score_value);

        if ($effective_score > 0) {
            $completed_tieuchi++;
        }

        $total_points += $effective_score;
        $max_possible_points += getMaxScoreFromOptions($score_options);

        if (isset($row['han_xuly']) && !empty($row['han_xuly'])) {
            $han_tc = new DateTime($row['han_xuly']);
            $han_tc_formatted = $han_tc->format('d/m/Y');
            $tc_so_ngay_xuly = isset($row['so_ngay_xuly']) ? intval($row['so_ngay_xuly']) : $so_ngay_xuly;
            $tc_ngay_tinh_han = isset($row['ngay_tinh_han']) ? $row['ngay_tinh_han'] : 'ngay_vao';
        } else {
            $han_tc = clone $han_xuly;
            $han_tc_formatted = $han_xuly_formatted;
            $tc_so_ngay_xuly = $so_ngay_xuly;
            $tc_ngay_tinh_han = 'ngay_vao';
        }

        $is_completed = ($effective_score > 0);
        // Chỉ báo quá hạn khi: đã qua hạn VÀ chưa hoàn thành
        $is_overdue = ($han_tc < $now && !$is_completed);
        // Ẩn tiêu chí có default score >= 1 (set mặc định sẵn, không phải user chọn)
        $has_high_default = (floatval($default_score_value) >= 1);

        if ($is_overdue) {
            $priority = 0;
        } elseif (!$is_completed) {
            $priority = 1;
        } else {
            $priority = 2;
        }

        $rows_data[] = [
            'row' => $row,
            'nhom' => (string) $row['nhom'],
            'nhom_order' => $nhom_order_map[(string) $row['nhom']],
            'thutu' => intval($row['thutu']),
            'priority' => $priority,
            'score_options' => $score_options,
            'effective_score' => $effective_score,
            'han_tc_formatted' => $han_tc_formatted,
            'tc_so_ngay_xuly' => $tc_so_ngay_xuly,
            'tc_ngay_tinh_han' => $tc_ngay_tinh_han,
            'is_overdue' => $is_overdue,
            'is_completed' => $is_completed,
            'has_high_default' => $has_high_default,
            'default_score_value' => $default_score_value,
        ];
    }

    // usort của PHP 7.4 không ổn định nên phải tie-break bằng thutu
    usort($rows_data, function ($a, $b) {
        if ($a['nhom_order'] !== $b['nhom_order']) {
            return $a['nhom_order'] - $b['nhom_order'];
        }
        if ($a['priority'] !== $b['priority']) {
            return $a['priority'] - $b['priority'];
        }
        return $a['thutu'] - $b['thutu'];
    });

    return [
        'rows' => $rows_data,
        'staff_list' => $staff_list,
        'total_tieuchi' => $total_tieuchi,
        'completed_tieuchi' => $completed_tieuchi,
        'total_points' => $total_points,
        'max_possible_points' => $max_possible_points,
    ];
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

/**
 * Flash message của trang indexdept: action đặt trước khi redirect, trang đọc 1 lần rồi xoá.
 *
 * Dùng session chứ KHÔNG dùng query param (?success=): param nằm lại trên URL nên F5,
 * back/forward hay mở lại link từ lịch sử đều hiện lại thông báo của lần lưu cũ.
 * Action chỉ gọi hàm này khi dữ liệu thực sự đổi — không đổi thì im lặng.
 *
 * $type: 'success' | 'error'
 */
function setIndexdeptFlash($type, $msg) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['indexdept_flash'] = ['type' => $type, 'msg' => $msg];
}

/** Đọc flash và xoá khỏi session. Trả null khi không có. */
function takeIndexdeptFlash() {
    if (empty($_SESSION['indexdept_flash'])) {
        return null;
    }
    $flash = $_SESSION['indexdept_flash'];
    unset($_SESSION['indexdept_flash']);
    return $flash;
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
