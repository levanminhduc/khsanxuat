<?php
/**
 * File này chứa các hàm hiển thị hạn xử lý tiêu chí
 */

/**
 * Hiển thị hạn xử lý dưới dạng badge có màu tùy theo trạng thái
 * 
 * @param string $han_xuly - Ngày hạn xử lý định dạng Y-m-d
 * @param bool $is_custom - Cờ đánh dấu có phải cài đặt tùy chỉnh hay không
 * @param string $ngay_tinh_han - Loại tính hạn (ngay_vao, ngay_vao_cong, ngay_ra, ngay_ra_tru)
 * @param int $so_ngay_xuly - Số ngày xử lý
 * @return string - HTML badge hiển thị hạn xử lý
 */
function displayDeadlineBadge($han_xuly, $is_custom = false, $ngay_tinh_han = '', $so_ngay_xuly = 0) {
    if (empty($han_xuly)) {
        return '<span class="badge badge-deadline-none" data-toggle="tooltip" data-placement="top" data-html="true" title="<b>Chưa thiết lập</b>"><i class="fas fa-question-circle"></i> Chưa thiết lập</span>';
    }
    
    // Format lại date_display
    $date_display = $han_xuly;
    try {
        $han_xuly_date = new DateTime($han_xuly);
        $date_display = $han_xuly_date->format('d/m/Y');
    } catch (Exception $e) {
        // Nếu không convert được, giữ nguyên giá trị
    }
    
    // So sánh ngày
    $today = new DateTime();
    $han_xuly_date = new DateTime($han_xuly);
    $interval = $today->diff($han_xuly_date);
    $days = $interval->days;
    $is_past = $interval->invert == 1; // Đã quá hạn
    
    $badge_class = '';
    $badge_text = '';
    $icon = '';
    
    if ($is_past) {
        // Quá hạn
        $badge_class = 'badge-deadline-danger';
        $badge_text = 'Quá hạn ' . $days . ' ngày';
        $icon = '<i class="fas fa-exclamation-circle"></i> ';
    } else {
        // Chưa đến hạn
        if ($days < 2) {
            $badge_class = 'badge-deadline-warning';
            $badge_text = 'Còn ' . $days . ' ngày';
            $icon = '<i class="fas fa-exclamation-triangle"></i> ';
        } else {
            $badge_class = 'badge-deadline-ok';
            $badge_text = 'Còn ' . $days . ' ngày';
            $icon = '<i class="fas fa-check-circle"></i> ';
        }
    }
    
    // Nếu là cài đặt tùy chỉnh, thêm biểu tượng
    $custom_icon = $is_custom ? '<i class="fas fa-cog"></i> ' : '';
    
    // Mô tả cách tính hạn xử lý
    $cach_tinh = '';
    
    // Kiểm tra thông tin cách tính hạn
    if (!empty($ngay_tinh_han)) {
        if ($ngay_tinh_han == 'ngay_vao') {
            // Ngày vào (trừ số ngày)
            if ($so_ngay_xuly > 0) {
                $cach_tinh = 'Ngày vào - ' . $so_ngay_xuly . ' ngày';
            } else {
                $cach_tinh = 'Ngày vào';
            }
        } else if ($ngay_tinh_han == 'ngay_vao_cong') {
            $cach_tinh = 'Ngày vào + ' . $so_ngay_xuly . ' ngày';
        } else if ($ngay_tinh_han == 'ngay_ra') {
            $cach_tinh = 'Ngày ra';
        } else if ($ngay_tinh_han == 'ngay_ra_tru') {
            $cach_tinh = 'Ngày ra - ' . $so_ngay_xuly . ' ngày';
        }
    }
    
    // Hiển thị thông tin chi tiết về cách tính hạn
    $ngay_tinh_info = '';
    if (!empty($cach_tinh)) {
        $ngay_tinh_info = '<b>Cách tính:</b> ' . $cach_tinh;
    }
    
    // Sử dụng tooltip thay vì title attribute để hiển thị thông tin chi tiết
    $tooltip_content = "<b>Hạn xử lý:</b> {$date_display}<br>{$ngay_tinh_info}";
    
    return '<span class="badge ' . $badge_class . '" data-toggle="tooltip" data-placement="top" data-html="true" title="' . $tooltip_content . '">' 
            . $custom_icon . $icon . $badge_text . '</span>';
}

/**
 * Lấy thông tin hạn xử lý của một tiêu chí
 * 
 * @param int $id_sanxuat - ID của đơn hàng
 * @param int $id_tieuchi - ID của tiêu chí
 * @param mysqli $connect - Kết nối database
 * @return array - Thông tin hạn xử lý
 */
function getDeadlineInfo($id_sanxuat, $id_tieuchi, $connect) {
    // Lấy thông tin tiêu chí
    $sql = "SELECT dt.han_xuly, dt.so_ngay_xuly, dt.ngay_tinh_han, 
                   dt.id_sanxuat, dt.da_thuchien,
                   tc.noidung, tc.dept
            FROM danhgia_tieuchi dt 
            JOIN tieuchi_dept tc ON dt.id_tieuchi = tc.id
            WHERE dt.id_sanxuat = ? AND dt.id_tieuchi = ?";
    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        error_log("Lỗi chuẩn bị truy vấn: " . $connect->error);
        return ['success' => false, 'message' => 'Lỗi truy vấn'];
    }
    
    $stmt->bind_param("ii", $id_sanxuat, $id_tieuchi);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Format lại ngày để hiển thị
        $date_display = $row['han_xuly'];
        try {
            if (!empty($row['han_xuly'])) {
                $han_xuly_date = new DateTime($row['han_xuly']);
                $date_display = $han_xuly_date->format('d/m/Y');
            }
        } catch (Exception $e) {
            // Nếu không convert được, giữ nguyên giá trị
        }
        
        return [
            'success' => true,
            'han_xuly' => $row['han_xuly'],
            'date_display' => $date_display,
            'so_ngay_xuly' => $row['so_ngay_xuly'] ?? 7,
            'ngay_tinh_han' => $row['ngay_tinh_han'] ?? 'ngay_vao_cong',
            'noidung' => $row['noidung'],
            'dept' => $row['dept'],
            'da_thuchien' => $row['da_thuchien'] == 1
        ];
    } else {
        // Không tìm thấy thông tin, lấy thông tin mặc định từ khsanxuat
        $sql = "SELECT kh.ngayin, kh.ngayout, kh.so_ngay_xuly, kh.han_xuly, kh.ngay_tinh_han,
                       tc.noidung, tc.dept
                FROM khsanxuat kh, tieuchi_dept tc
                WHERE kh.stt = ? AND tc.id = ?";
        $stmt = $connect->prepare($sql);
        
        if (!$stmt) {
            error_log("Lỗi chuẩn bị truy vấn: " . $connect->error);
            return ['success' => false, 'message' => 'Lỗi truy vấn'];
        }
        
        $stmt->bind_param("ii", $id_sanxuat, $id_tieuchi);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Tạo thông tin mặc định
            $so_ngay_xuly = $row['so_ngay_xuly'] ?? 7;
            $ngay_tinh_han = $row['ngay_tinh_han'] ?? 'ngay_vao_cong';
            
            // Tính ngày hạn xử lý dựa trên cài đặt
            $han_xuly = calculateDeadline($row['ngayin'], $row['ngayout'], $ngay_tinh_han, $so_ngay_xuly);
            
            // Format lại ngày để hiển thị
            $date_display = $han_xuly;
            try {
                if (!empty($han_xuly)) {
                    $han_xuly_date = new DateTime($han_xuly);
                    $date_display = $han_xuly_date->format('d/m/Y');
                }
            } catch (Exception $e) {
                // Nếu không convert được, giữ nguyên giá trị
            }
            
            return [
                'success' => true,
                'han_xuly' => $han_xuly,
                'date_display' => $date_display,
                'so_ngay_xuly' => $so_ngay_xuly,
                'ngay_tinh_han' => $ngay_tinh_han,
                'noidung' => $row['noidung'],
                'dept' => $row['dept'],
                'da_thuchien' => false // Mặc định là chưa thực hiện
            ];
        }
        
        return ['success' => false, 'message' => 'Không tìm thấy thông tin'];
    }
}

/**
 * Hiển thị hạn xử lý của một tiêu chí trong một đơn hàng
 * 
 * @param int $id_sanxuat - ID đơn hàng
 * @param int $id_tieuchi - ID tiêu chí
 * @param mysqli $connect - Kết nối database
 * @return string - HTML badge hiển thị hạn xử lý
 */
function displayTieuchiDeadline($id_sanxuat, $id_tieuchi, $connect) {
    $deadline_info = getDeadlineInfo($id_sanxuat, $id_tieuchi, $connect);
    
    return displayDeadlineBadge(
        $deadline_info['han_xuly'],
        $deadline_info['is_custom'],
        $deadline_info['ngay_tinh_han'],
        $deadline_info['so_ngay_xuly']
    );
}

/**
 * Hiển thị form chọn ngày tính hạn
 *
 * @param string $selected_value - Giá trị đã chọn
 * @param string $field_name - Tên trường form
 * @param string $css_class - CSS class bổ sung
 * @return string - HTML select box
 */
function displayNgayTinhHanSelect($selected_value = 'ngay_vao', $field_name = 'ngay_tinh_han', $css_class = '') {
    $options = [
        'ngay_vao' => 'Ngày vào',
        'ngay_vao_cong' => 'Ngày vào + số ngày',
        'ngay_ra' => 'Ngày ra',
        'ngay_ra_tru' => 'Ngày ra - số ngày'
    ];
    
    $html = '<select name="' . $field_name . '" class="form-control ' . $css_class . '">';
    
    foreach ($options as $value => $label) {
        $selected = ($selected_value == $value) ? 'selected' : '';
        $html .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
    }
    
    $html .= '</select>';
    
    return $html;
}

/**
 * Hiển thị form chọn số ngày xử lý
 *
 * @param int $selected_value - Giá trị đã chọn
 * @param string $field_name - Tên trường form
 * @param string $css_class - CSS class bổ sung
 * @return string - HTML input number
 */
function displaySoNgayXulyInput($selected_value = 7, $field_name = 'so_ngay_xuly', $css_class = '') {
    $html = '<div class="input-group">';
    $html .= '<input type="number" name="' . $field_name . '" class="form-control ' . $css_class . '" min="1" max="30" value="' . $selected_value . '">';
    $html .= '<div class="input-group-append">';
    $html .= '<span class="input-group-text">ngày</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Tính toán hạn xử lý dựa trên các tham số
 * 
 * @param string $ngay_vao - Ngày vào
 * @param string $ngay_ra - Ngày ra
 * @param string $ngay_tinh_han - Loại tính hạn
 * @param int $so_ngay_xuly - Số ngày xử lý
 * @return string - Ngày hạn xử lý định dạng Y-m-d
 */
function calculateDeadline($ngay_vao, $ngay_ra, $ngay_tinh_han, $so_ngay_xuly) {
    // Nếu không có ngày vào, trả về null
    if (empty($ngay_vao)) {
        return null;
    }
    
    // Khởi tạo biến ngày hạn
    $deadline = null;
    
    // Chuyển đổi ngày vào thành đối tượng DateTime
    try {
        $ngay_vao_date = new DateTime($ngay_vao);
    } catch (Exception $e) {
        return null;
    }
    
    // Chuyển đổi ngày ra thành đối tượng DateTime nếu có
    $ngay_ra_date = null;
    if (!empty($ngay_ra)) {
        try {
            $ngay_ra_date = new DateTime($ngay_ra);
        } catch (Exception $e) {
            // Không làm gì, sử dụng ngày vào
        }
    }
    
    // Tính toán hạn xử lý dựa trên loại tính hạn
    switch ($ngay_tinh_han) {
        case 'ngay_vao':
            // Ngày vào (trừ số ngày nếu có)
            $deadline = clone $ngay_vao_date;
            if ($so_ngay_xuly > 0) {
                $deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
            }
            break;
            
        case 'ngay_vao_cong':
            // Ngày vào cộng số ngày
            $deadline = clone $ngay_vao_date;
            $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            break;
            
        case 'ngay_ra':
            // Ngày ra
            if ($ngay_ra_date) {
                $deadline = clone $ngay_ra_date;
            } else {
                // Nếu không có ngày ra, sử dụng ngày vào cộng 7 ngày
                $deadline = clone $ngay_vao_date;
                $deadline->add(new DateInterval('P7D'));
            }
            break;
            
        case 'ngay_ra_tru':
            // Ngày ra trừ số ngày
            if ($ngay_ra_date) {
                $deadline = clone $ngay_ra_date;
                $deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
            } else {
                // Nếu không có ngày ra, sử dụng ngày vào cộng 7 ngày trừ số ngày
                $deadline = clone $ngay_vao_date;
                $deadline->add(new DateInterval('P7D'));
                $deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
            }
            break;
            
        default:
            // Mặc định là ngày vào cộng số ngày
            $deadline = clone $ngay_vao_date;
            $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            break;
    }
    
    // Trả về ngày hạn xử lý định dạng Y-m-d
    return $deadline ? $deadline->format('Y-m-d') : null;
}

/**
 * Định dạng ngày theo định dạng hiển thị của Việt Nam (d/m/Y)
 *
 * @param string $date - Ngày cần định dạng (định dạng Y-m-d hoặc datetime)
 * @return string - Ngày đã định dạng (d/m/Y)
 */
function formatDateDisplay($date) {
    if (empty($date)) {
        return '';
    }
    
    try {
        $date_obj = new DateTime($date);
        return $date_obj->format('d/m/Y');
    } catch (Exception $e) {
        // Nếu có lỗi, trả về chuỗi gốc
        return $date;
    }
}

/**
 * Hiển thị thông tin hạn xử lý tiêu chí dưới dạng HTML
 *
 * @param array $deadline_info - Thông tin hạn xử lý
 * @return string - HTML để hiển thị thông tin hạn xử lý
 */
function displayDeadlineInfo($deadline_info) {
    if (!$deadline_info['success']) {
        return '<div class="alert alert-warning">Không thể lấy thông tin hạn xử lý</div>';
    }
    
    $html = '<div class="deadline-info">';
    
    // Hiển thị badge hạn xử lý
    $html .= '<div class="deadline-badge mb-2">';
    $html .= displayDeadlineBadge(
        $deadline_info['han_xuly'],
        true,
        $deadline_info['ngay_tinh_han'],
        $deadline_info['so_ngay_xuly']
    );
    $html .= '</div>';
    
    // Hiển thị thông tin chi tiết
    $html .= '<div class="deadline-details">';
    $html .= '<p><strong>Ngày hiển thị:</strong> <span class="date_display">' . ($deadline_info['date_display'] ?? '') . '</span></p>';
    
    // Mô tả cách tính hạn
    $cach_tinh = '';
    switch ($deadline_info['ngay_tinh_han']) {
        case 'ngay_vao':
            $cach_tinh = 'Ngày vào - ' . $deadline_info['so_ngay_xuly'] . ' ngày';
            break;
        case 'ngay_vao_cong':
            $cach_tinh = 'Ngày vào + ' . $deadline_info['so_ngay_xuly'] . ' ngày';
            break;
        case 'ngay_ra':
            $cach_tinh = 'Ngày ra';
            break;
        case 'ngay_ra_tru':
            $cach_tinh = 'Ngày ra - ' . $deadline_info['so_ngay_xuly'] . ' ngày';
            break;
    }
    
    $html .= '<p><strong>Cách tính:</strong> ' . $cach_tinh . '</p>';
    $html .= '</div>'; // .deadline-details
    
    $html .= '</div>'; // .deadline-info
    
    return $html;
}

/**
 * Cập nhật date_display cho dữ liệu đã import
 * 
 * @param int $id_sanxuat - ID đơn hàng
 * @param mysqli $connect - Kết nối database
 * @return array - Kết quả cập nhật
 */
function updateImportDateDisplay($id_sanxuat, $connect) {
    // Mảng kết quả
    $result = [
        'success' => true,
        'message' => 'Cập nhật thành công',
        'updated' => 0,
        'errors' => []
    ];
    
    try {
        // Lấy thông tin đơn hàng
        $sql_order = "SELECT ngayin, ngayout, xuong FROM khsanxuat WHERE stt = ?";
        $stmt_order = $connect->prepare($sql_order);
        
        if (!$stmt_order) {
            throw new Exception("Lỗi chuẩn bị truy vấn: " . $connect->error);
        }
        
        $stmt_order->bind_param("i", $id_sanxuat);
        $stmt_order->execute();
        $order_data = $stmt_order->get_result();
        
        if ($order_data->num_rows === 0) {
            throw new Exception("Không tìm thấy đơn hàng với ID: $id_sanxuat");
        }
        
        $order = $order_data->fetch_assoc();
        $ngayin = $order['ngayin'];
        $ngayout = $order['ngayout'];
        $xuong = $order['xuong'];
        
        // Lấy danh sách tiêu chí đã import
        $sql_tc = "SELECT dt.id, dt.id_tieuchi, dt.han_xuly, dt.so_ngay_xuly, dt.ngay_tinh_han, tc.dept
                   FROM danhgia_tieuchi dt
                   JOIN tieuchi_dept tc ON dt.id_tieuchi = tc.id
                   WHERE dt.id_sanxuat = ?";
        $stmt_tc = $connect->prepare($sql_tc);
        
        if (!$stmt_tc) {
            throw new Exception("Lỗi chuẩn bị truy vấn tiêu chí: " . $connect->error);
        }
        
        $stmt_tc->bind_param("i", $id_sanxuat);
        $stmt_tc->execute();
        $tc_data = $stmt_tc->get_result();
        
        // Chuẩn bị SQL để cập nhật hạn xử lý
        $sql_update = "UPDATE danhgia_tieuchi SET 
                       han_xuly = ?, 
                       so_ngay_xuly = ?, 
                       ngay_tinh_han = ? 
                       WHERE id = ?";
        $stmt_update = $connect->prepare($sql_update);
        
        if (!$stmt_update) {
            throw new Exception("Lỗi chuẩn bị câu lệnh cập nhật: " . $connect->error);
        }
        
        // Nếu không có tiêu chí, trả về kết quả thành công nhưng không cập nhật gì
        if ($tc_data->num_rows === 0) {
            $result['message'] = "Không có tiêu chí nào để cập nhật date_display";
            return $result;
        }
        
        // Xử lý từng tiêu chí
        while ($tc = $tc_data->fetch_assoc()) {
            // Nếu tiêu chí đã có hạn xử lý, kiểm tra và định dạng lại
            if (!empty($tc['han_xuly'])) {
                $han_xuly = $tc['han_xuly'];
                $so_ngay_xuly = $tc['so_ngay_xuly'] ?: 7;
                $ngay_tinh_han = $tc['ngay_tinh_han'] ?: 'ngay_vao';
                
                // Tính lại hạn xử lý nếu cần
                $calculated_deadline = calculateDeadline($ngayin, $ngayout, $ngay_tinh_han, $so_ngay_xuly);
                
                // Nếu hạn xử lý đã tính khác với hạn xử lý hiện tại, cập nhật
                if ($calculated_deadline !== $han_xuly) {
                    $stmt_update->bind_param("sisi", $calculated_deadline, $so_ngay_xuly, $ngay_tinh_han, $tc['id']);
                    $stmt_update->execute();
                    
                    if ($stmt_update->affected_rows > 0) {
                        $result['updated']++;
                    }
                }
            } else {
                // Tiêu chí chưa có hạn xử lý, lấy giá trị mặc định
                try {
                    // Lấy cài đặt mặc định từ bảng default_settings
                    $dept = $tc['dept'];
                    $sql_default = "SELECT ngay_tinh_han, so_ngay_xuly FROM default_settings 
                                   WHERE dept = ? AND (xuong = ? OR xuong = '') 
                                   ORDER BY CASE WHEN xuong = ? THEN 0 ELSE 1 END
                                   LIMIT 1";
                    $stmt_default = $connect->prepare($sql_default);
                    $stmt_default->bind_param("sss", $dept, $xuong, $xuong);
                    $stmt_default->execute();
                    $default_data = $stmt_default->get_result();
                    
                    // Lấy giá trị mặc định
                    $ngay_tinh_han = 'ngay_vao_cong';
                    $so_ngay_xuly = 7;
                    
                    if ($default_data->num_rows > 0) {
                        $default = $default_data->fetch_assoc();
                        $ngay_tinh_han = $default['ngay_tinh_han'];
                        $so_ngay_xuly = $default['so_ngay_xuly'];
                    }
                    
                    // Tính toán hạn xử lý
                    $han_xuly = calculateDeadline($ngayin, $ngayout, $ngay_tinh_han, $so_ngay_xuly);
                    
                    // Cập nhật hạn xử lý
                    if ($han_xuly) {
                        $stmt_update->bind_param("sisi", $han_xuly, $so_ngay_xuly, $ngay_tinh_han, $tc['id']);
                        $stmt_update->execute();
                        
                        if ($stmt_update->affected_rows > 0) {
                            $result['updated']++;
                        }
                    }
                } catch (Exception $e) {
                    $result['errors'][] = "Lỗi tiêu chí " . $tc['id_tieuchi'] . ": " . $e->getMessage();
                }
            }
        }
        
    } catch (Exception $e) {
        $result['success'] = false;
        $result['message'] = "Lỗi: " . $e->getMessage();
    }
    
    return $result;
}

/**
 * Lấy date_display từ một ngày theo định dạng cho trước
 * 
 * @param string $date - Ngày cần định dạng
 * @param string $format - Định dạng đầu ra (mặc định d/m/Y)
 * @return string - Ngày đã định dạng
 */
function getDateDisplay($date, $format = 'd/m/Y') {
    if (empty($date)) {
        return '';
    }
    
    try {
        $date_obj = new DateTime($date);
        return $date_obj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}
?> 