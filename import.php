<?php
// Kết nối cơ sở dữ liệu sử dụng mysqli
require "contdb.php"; // Đảm bảo rằng bạn đã kết nối với cơ sở dữ liệu qua contdb.php

/**
 * Hàm chuyển đổi định dạng ngày từ d/m/Y sang Y-m-d
 */
function formatDate($date)
{
    if (empty($date)) {
        return null;
    }

    // Nếu là số serial của Excel
    if (is_numeric($date)) {
        $unix_date = ($date - 25569) * 86400;
        return date('Y-m-d', $unix_date);
    }

    // Chuyển đổi từ d/m/Y sang Y-m-d
    $parts = explode('/', $date);
    if (count($parts) === 3) {
        // Đảm bảo năm có 4 chữ số
        if (strlen($parts[2]) == 2) {
            $parts[2] = '20' . $parts[2];
        }
        return sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
    }

    return null;
}

/**
 * Hàm cập nhật lại tất cả hạn xử lý sau khi import
 * @param array $imported_ids Mảng các ID đơn hàng đã import
 * @param mysqli $connect Kết nối database
 * @return array Kết quả cập nhật
 */
function updateAllDeadlinesAfterImport($imported_ids, $connect)
{
    $result = [
        'success' => true,
        'updated_orders' => 0,
        'updated_criteria' => 0,
        'errors' => []
    ];

    if (empty($imported_ids)) {
        $result['success'] = false;
        $result['message'] = 'Không có ID đơn hàng nào để cập nhật';
        return $result;
    }

    // Ghi log bắt đầu cập nhật
    $log_file = 'logs/date_display_update.log';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Bắt đầu cập nhật hạn xử lý sau khi import.\n", FILE_APPEND);

    // Đảm bảo các hàm từ display_deadline.php được load
    if (!function_exists('getDeadlineInfo') || !function_exists('calculateDeadline')) {
        include_once 'display_deadline.php';
    }

    foreach ($imported_ids as $id_sanxuat) {
        try {
            // 1. Lấy thông tin đơn hàng (ngày vào, ngày ra, xưởng)
            $sql_order = "SELECT stt, xuong, ngayin, ngayout FROM khsanxuat WHERE stt = ?";
            $stmt_order = $connect->prepare($sql_order);
            $stmt_order->bind_param("i", $id_sanxuat);
            $stmt_order->execute();
            $order_result = $stmt_order->get_result();

            if ($order_result->num_rows > 0) {
                $order = $order_result->fetch_assoc();

                // 2. Lấy danh sách tất cả các tiêu chí của đơn hàng này
                $sql_criteria = "SELECT dt.id, dt.id_tieuchi, dt.ngay_tinh_han, dt.so_ngay_xuly, tc.dept 
                                FROM danhgia_tieuchi dt 
                                JOIN tieuchi_dept tc ON dt.id_tieuchi = tc.id 
                                WHERE dt.id_sanxuat = ?";
                $stmt_criteria = $connect->prepare($sql_criteria);
                $stmt_criteria->bind_param("i", $id_sanxuat);
                $stmt_criteria->execute();
                $criteria_result = $stmt_criteria->get_result();

                $updated_criteria_count = 0;

                // 3. Nếu đã có các tiêu chí
                if ($criteria_result->num_rows > 0) {
                    while ($criterion = $criteria_result->fetch_assoc()) {
                        $dept = $criterion['dept'];
                        $id_tieuchi = $criterion['id_tieuchi'];
                        $id_danhgia = $criterion['id'];

                        // Lấy thiết lập deadline từ default_settings
                        $deadline_info = getDeadlineInfo($id_sanxuat, $id_tieuchi, $connect);
                        if ($deadline_info) {
                            $ngay_tinh_han = $deadline_info['ngay_tinh_han'];
                            $so_ngay_xuly = $deadline_info['so_ngay_xuly'];

                            // Tính toán hạn xử lý dựa trên thiết lập
                            $han_xuly = calculateDeadline($order['ngayin'], $order['ngayout'], $ngay_tinh_han, $so_ngay_xuly);

                            if ($han_xuly) {
                                // Cập nhật hạn xử lý cho tiêu chí
                                $update_sql = "UPDATE danhgia_tieuchi SET 
                                              han_xuly = ?,
                                              ngay_tinh_han = ?,
                                              so_ngay_xuly = ?
                                              WHERE id = ?";
                                $update_stmt = $connect->prepare($update_sql);
                                $update_stmt->bind_param("ssii", $han_xuly, $ngay_tinh_han, $so_ngay_xuly, $id_danhgia);
                                $update_stmt->execute();

                                if ($update_stmt->affected_rows > 0) {
                                    $updated_criteria_count++;
                                }
                            }
                        }
                    }
                }
                // 4. Nếu chưa có tiêu chí, tạo các tiêu chí mới từ cài đặt mặc định
                else {
                    // Lấy danh sách tiêu chí mặc định cho các bộ phận
                    $default_sql = "SELECT tc.id, tc.dept, tc.noidung 
                                   FROM tieuchi_dept tc 
                                   WHERE tc.active = 1";
                    $default_result = $connect->query($default_sql);

                    if ($default_result && $default_result->num_rows > 0) {
                        while ($default_criterion = $default_result->fetch_assoc()) {
                            $dept = $default_criterion['dept'];
                            $id_tieuchi = $default_criterion['id'];

                            // Lấy thiết lập deadline từ default_settings
                            $deadline_info = getDeadlineInfo($id_sanxuat, $id_tieuchi, $connect);
                            if ($deadline_info) {
                                $ngay_tinh_han = $deadline_info['ngay_tinh_han'];
                                $so_ngay_xuly = $deadline_info['so_ngay_xuly'];

                                // Tính toán hạn xử lý dựa trên thiết lập
                                $han_xuly = calculateDeadline($order['ngayin'], $order['ngayout'], $ngay_tinh_han, $so_ngay_xuly);

                                if ($han_xuly) {
                                    // Tạo bản ghi đánh giá tiêu chí mới
                                    $insert_sql = "INSERT INTO danhgia_tieuchi 
                                                 (id_sanxuat, id_tieuchi, han_xuly, ngay_tinh_han, so_ngay_xuly, da_thuchien, diem_danhgia)
                                                 VALUES (?, ?, ?, ?, ?, 0, 0)";
                                    $insert_stmt = $connect->prepare($insert_sql);
                                    $insert_stmt->bind_param("iissi", $id_sanxuat, $id_tieuchi, $han_xuly, $ngay_tinh_han, $so_ngay_xuly);
                                    $insert_stmt->execute();

                                    if ($insert_stmt->affected_rows > 0) {
                                        $updated_criteria_count++;
                                    }
                                }
                            }
                        }
                    }
                }

                if ($updated_criteria_count > 0) {
                    $result['updated_orders']++;
                    $result['updated_criteria'] += $updated_criteria_count;

                    // Ghi log thành công
                    $log_message = "[" . date('Y-m-d H:i:s') . "] Đã cập nhật hạn xử lý cho đơn hàng ID: $id_sanxuat. ";
                    $log_message .= "Số tiêu chí cập nhật: $updated_criteria_count\n";
                    file_put_contents($log_file, $log_message, FILE_APPEND);

                    // Cập nhật hạn xử lý cho đơn hàng
                    $get_deadline_sql = "SELECT ngay_tinh_han, so_ngay_xuly, han_xuly 
                                       FROM danhgia_tieuchi 
                                       WHERE id_sanxuat = ? AND id_tieuchi IN (
                                         SELECT id FROM tieuchi_dept WHERE dept = 'kehoach'
                                       ) LIMIT 1";
                    $get_deadline_stmt = $connect->prepare($get_deadline_sql);
                    $get_deadline_stmt->bind_param("i", $id_sanxuat);
                    $get_deadline_stmt->execute();
                    $get_deadline_result = $get_deadline_stmt->get_result();

                    if ($get_deadline_result->num_rows > 0) {
                        $deadline_row = $get_deadline_result->fetch_assoc();
                        $ngay_tinh_han = $deadline_row['ngay_tinh_han'];
                        $so_ngay_xuly = $deadline_row['so_ngay_xuly'];
                        $han_xuly = $deadline_row['han_xuly'];

                        // Cập nhật hạn xử lý cho đơn hàng từ tiêu chí kế hoạch
                        $update_order_sql = "UPDATE khsanxuat SET 
                                          han_xuly = ?, 
                                          ngay_tinh_han = ?, 
                                          so_ngay_xuly = ? 
                                          WHERE stt = ?";
                        $update_order_stmt = $connect->prepare($update_order_sql);
                        $update_order_stmt->bind_param("ssii", $han_xuly, $ngay_tinh_han, $so_ngay_xuly, $id_sanxuat);
                        $update_order_stmt->execute();
                    }
                }
            }
        } catch (Exception $e) {
            $result['errors'][] = "Lỗi cập nhật đơn hàng ID $id_sanxuat: " . $e->getMessage();

            // Ghi log lỗi
            $error_log = "[" . date('Y-m-d H:i:s') . "] Lỗi cập nhật hạn xử lý cho đơn hàng ID: $id_sanxuat. ";
            $error_log .= "Lỗi: " . $e->getMessage() . "\n";
            file_put_contents($log_file, $error_log, FILE_APPEND);
        }
    }

    // Ghi log kết thúc cập nhật
    $end_log = "[" . date('Y-m-d H:i:s') . "] Hoàn tất cập nhật hạn xử lý sau khi import. ";
    $end_log .= "Đã cập nhật {$result['updated_orders']}/" . count($imported_ids) . " đơn hàng, ";
    $end_log .= "tổng cộng {$result['updated_criteria']} tiêu chí.\n";
    file_put_contents($log_file, $end_log, FILE_APPEND);

    return $result;
}

/**
 * Hàm kiểm tra dữ liệu trùng lặp
 * @param array $data Dữ liệu cần kiểm tra
 * @param mysqli $connect Kết nối database
 * @return bool|int Trả về false nếu không trùng, hoặc ID của bản ghi trùng
 */
function checkDuplicate($data, $connect)
{
    // Chuẩn bị câu truy vấn SQL để kiểm tra trùng lặp
    $query = "SELECT stt FROM khsanxuat WHERE 
              xuong = ? AND 
              line1 = ? AND 
              po = ? AND 
              style = ? AND
              model = ? AND
              qty = ?";

    // Tham số cơ bản
    $params = [
        $data['xuong'],
        $data['line'],
        $data['po'],
        $data['style'],
        $data['model'],
        $data['qty']
    ];

    $types = "ssssss"; // String types for the parameters

    // Thêm điều kiện cho ngày nếu có
    if (!empty($data['ngayin'])) {
        $query .= " AND ngayin = ?";
        $params[] = $data['ngayin'];
        $types .= "s";
    }

    if (!empty($data['ngayout'])) {
        $query .= " AND ngayout = ?";
        $params[] = $data['ngayout'];
        $types .= "s";
    }

    // Chuẩn bị và thực thi truy vấn
    $stmt = $connect->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['stt']; // Trả về ID của bản ghi trùng lặp
        }
    }

    return false; // Không tìm thấy bản ghi trùng lặp
}

/**
 * Hàm xử lý một lô dữ liệu và import vào database
 * @param array $batch_data Mảng dữ liệu cần import
 * @param mysqli $connect Kết nối database
 * @return array Kết quả import
 */
function processBatch($batch_data, $connect)
{
    $result = [
        'success' => true,
        'imported_ids' => [],
        'success_messages' => [],
        'errors' => [],
        'duplicates' => [] // Thêm mảng để lưu các bản ghi trùng lặp
    ];

    if (empty($batch_data)) {
        return $result;
    }

    // Bắt đầu transaction cho lô này
    $connect->begin_transaction();

    try {
        // Truy vấn chính xác bảng default_settings
        $sql_settings = "SELECT * FROM default_settings WHERE dept = 'all' ORDER BY id DESC LIMIT 1";
        $result_settings = $connect->query($sql_settings);

        if ($result_settings && $result_settings->num_rows > 0) {
            $settings = $result_settings->fetch_assoc();
            $ngay_tinh_han_default = $settings['ngay_tinh_han'];
            $so_ngay_xuly_default = $settings['so_ngay_xuly'];

            error_log("Lấy giá trị từ default_settings: ngay_tinh_han = $ngay_tinh_han_default, so_ngay_xuly = $so_ngay_xuly_default");
        } else {
            $ngay_tinh_han_default = 'ngay_vao_cong'; // Giá trị mặc định nếu không tìm thấy cài đặt
            $so_ngay_xuly_default = 7;

            error_log("Không tìm thấy cài đặt mặc định, sử dụng giá trị mặc định: ngay_tinh_han = $ngay_tinh_han_default, so_ngay_xuly = $so_ngay_xuly_default");
        }

        // Cập nhật câu lệnh INSERT để thêm cả so_ngay_xuly
        $stmt = $connect->prepare("INSERT INTO khsanxuat (line1, xuong, po, style, model, qty, ngayin, ngayout, ngay_tinh_han, so_ngay_xuly) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($batch_data as $row) {
            // Kiểm tra trùng lặp trước khi thêm
            $duplicate_id = checkDuplicate($row, $connect);

            if ($duplicate_id !== false) {
                // Bản ghi đã tồn tại, thêm vào danh sách trùng lặp
                $result['duplicates'][] = "PO: {$row['po']}, Style: {$row['style']} - Đã tồn tại (ID: $duplicate_id)";
                continue; // Bỏ qua bản ghi này
            }

            $stmt->bind_param(
                "sssssssssi",
                $row['line'],
                $row['xuong'],
                $row['po'],
                $row['style'],
                $row['model'],
                $row['qty'],
                $row['ngayin'],
                $row['ngayout'],
                $ngay_tinh_han_default,
                $so_ngay_xuly_default
            );

            if ($stmt->execute()) {
                $new_id = $connect->insert_id; // Lấy ID mới nhất vừa được thêm vào
                $result['imported_ids'][] = $new_id;
                $result['success_messages'][] = "Đã thêm PO: {$row['po']} (ID: $new_id)";

                // Ghi log chi tiết
                error_log("Import thành công PO: {$row['po']} với ID: $new_id, ngay_tinh_han: $ngay_tinh_han_default, so_ngay_xuly: $so_ngay_xuly_default");
            } else {
                $result['errors'][] = "Lỗi khi thêm PO: {$row['po']} - " . $stmt->error;
                error_log("Lỗi khi thêm PO: {$row['po']} - " . $stmt->error);
            }
        }

        // Commit transaction cho lô này
        $connect->commit();
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $connect->rollback();
        $result['success'] = false;
        $result['errors'][] = "Lỗi trong quá trình import lô dữ liệu: " . $e->getMessage();
        error_log("Lỗi trong quá trình import lô dữ liệu: " . $e->getMessage());
    }

    return $result;
}

/**
 * Hàm xử lý các cài đặt mặc định cho các ID đã import
 * @param array $batch_ids Mảng các ID đơn hàng đã import trong lô
 * @param array $success Mảng thông báo thành công
 * @param array $errors Mảng thông báo lỗi
 * @return array [success, errors]
 */
function processDefaultSettings($batch_ids, $success, $errors)
{
    global $connect;

    if (empty($batch_ids)) {
        return ['success' => $success, 'errors' => $errors];
    }

                    // Debug: In ra thông tin các ID đã import
    error_log('IDs đã import trong lô: ' . implode(', ', $batch_ids));

                    // Include file xử lý cài đặt mặc định
    try {
        include_once 'apply_default_settings.php';
        include_once 'display_deadline.php'; // Include file tính toán hạn xử lý

        // Debug - kiểm tra xem hàm có tồn tại không
        if (!function_exists('applyDefaultSettings')) {
            $errors[] = "Lỗi: Không tìm thấy hàm applyDefaultSettings()";
            error_log('Không tìm thấy hàm applyDefaultSettings()');
        } else {
            error_log('Đã tìm thấy hàm applyDefaultSettings()');
            foreach ($batch_ids as $id_sanxuat) {
                try {
                    // Debug: In ra ID đang xử lý
                    error_log('Đang xử lý ID: ' . $id_sanxuat);

                    // Gọi hàm áp dụng cài đặt mặc định
                    $result = applyDefaultSettings($id_sanxuat);

                    // Debug: In ra kết quả
                    error_log('Kết quả áp dụng cài đặt mặc định: ' . json_encode($result));

                    if ($result['success']) {
                        $success[] = "Đã áp dụng {$result['count']} cài đặt mặc định cho đơn hàng ID: $id_sanxuat";

                        // Lấy thông tin đơn hàng
                        $get_order_sql = "SELECT xuong, ngayin, ngayout FROM khsanxuat WHERE stt = ?";
                        $get_order_stmt = $connect->prepare($get_order_sql);
                        $get_order_stmt->bind_param("i", $id_sanxuat);
                        $get_order_stmt->execute();
                        $order_result = $get_order_stmt->get_result();

                        if ($order_result->num_rows > 0) {
                            $order = $order_result->fetch_assoc();
                            $xuong = $order['xuong'];
                            $ngayin = $order['ngayin'];
                            $ngayout = $order['ngayout'];

                            // Lấy cài đặt mặc định từ bảng default_settings cho bộ phận kehoach
                            $settings_sql = "SELECT ngay_tinh_han, so_ngay_xuly FROM default_settings 
                                                           WHERE dept = 'kehoach' AND (xuong = ? OR xuong = '') 
                                                           ORDER BY CASE WHEN xuong = ? THEN 0 ELSE 1 END
                                                           LIMIT 1";
                            $settings_stmt = $connect->prepare($settings_sql);
                            $settings_stmt->bind_param("ss", $xuong, $xuong);
                            $settings_stmt->execute();
                            $settings_result = $settings_stmt->get_result();

                            $ngay_tinh_han = 'ngay_vao_cong'; // Mặc định
                            $so_ngay_xuly = 7; // Mặc định

                            // Nếu có cài đặt mặc định, sử dụng cài đặt đó
                            if ($settings_result->num_rows > 0) {
                                $settings = $settings_result->fetch_assoc();
                                $ngay_tinh_han = $settings['ngay_tinh_han'];
                                $so_ngay_xuly = $settings['so_ngay_xuly'];
                                error_log("Đã tìm thấy cài đặt mặc định: $ngay_tinh_han, $so_ngay_xuly");
                            } else {
                                error_log("Không tìm thấy cài đặt mặc định cho xưởng $xuong, sử dụng mặc định: $ngay_tinh_han, $so_ngay_xuly");
                            }

                            // Tính toán hạn xử lý
                            if (function_exists('calculateDeadline')) {
                                $han_xuly = calculateDeadline($ngayin, $ngayout, $ngay_tinh_han, $so_ngay_xuly);
                                error_log("Đã tính hạn xử lý: $han_xuly cho ID $id_sanxuat");

                                // Cập nhật hạn xử lý cho đơn hàng
                                if ($han_xuly) {
                                    $update_han_sql = "UPDATE khsanxuat SET 
                                                                     han_xuly = ?, 
                                                                     ngay_tinh_han = ?, 
                                                                     so_ngay_xuly = ? 
                                                                     WHERE stt = ?";
                                    $update_han_stmt = $connect->prepare($update_han_sql);
                                    $update_han_stmt->bind_param("ssii", $han_xuly, $ngay_tinh_han, $so_ngay_xuly, $id_sanxuat);
                                    $update_han_stmt->execute();
                                    $update_han_result = $update_han_stmt->affected_rows;
                                    error_log("Cập nhật hạn xử lý cho ID $id_sanxuat: $update_han_result hàng bị ảnh hưởng");
                                } else {
                                    error_log("Không thể tính hạn xử lý cho ID $id_sanxuat");

                                    // Sử dụng cách tính mặc định nếu không thể tính toán
                                    $update_han_sql = "UPDATE khsanxuat SET han_xuly = DATE_ADD(ngayin, INTERVAL 7 DAY) WHERE stt = ? AND (han_xuly IS NULL OR han_xuly = '')";
                                    $update_han_stmt = $connect->prepare($update_han_sql);
                                    $update_han_stmt->bind_param("i", $id_sanxuat);
                                    $update_han_stmt->execute();
                                    error_log("Cập nhật hạn xử lý mặc định cho ID $id_sanxuat");
                                }
                            } else {
                                error_log("Không tìm thấy hàm calculateDeadline(), sử dụng hạn xử lý mặc định");
                                // Sử dụng cách tính mặc định
                                $update_han_sql = "UPDATE khsanxuat SET han_xuly = DATE_ADD(ngayin, INTERVAL 7 DAY) WHERE stt = ? AND (han_xuly IS NULL OR han_xuly = '')";
                                $update_han_stmt = $connect->prepare($update_han_sql);
                                $update_han_stmt->bind_param("i", $id_sanxuat);
                                $update_han_stmt->execute();
                            }
                        } else {
                            error_log("Không tìm thấy đơn hàng với ID $id_sanxuat");
                        }
                    } else {
                        $errors[] = "Lỗi khi áp dụng cài đặt mặc định cho đơn hàng ID: $id_sanxuat - " . $result['message'];
                        error_log("Lỗi khi áp dụng cài đặt mặc định: " . $result['message']);
                    }
                } catch (Exception $e) {
                    $errors[] = "Lỗi ngoại lệ khi áp dụng cài đặt mặc định: " . $e->getMessage();
                    error_log('Lỗi ngoại lệ: ' . $e->getMessage());
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = "Lỗi khi include file apply_default_settings.php: " . $e->getMessage();
        error_log('Lỗi include: ' . $e->getMessage());
    }

    return ['success' => $success, 'errors' => $errors];
}

// Xử lý file Excel khi tải lên và ghi vào cơ sở dữ liệu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    require_once 'vendor/autoload.php'; // Sử dụng thư viện PhpSpreadsheet
    $inputFileName = $_FILES['excel_file']['tmp_name'];
    $errors = [];
    $success = [];
    $imported_ids = []; // Mảng lưu tất cả các ID đã import
    $batch_size = 50; // Kích thước của mỗi lô dữ liệu
    $current_batch = []; // Lô dữ liệu hiện tại

    // Thêm đoạn code tăng giới hạn thời gian thực thi
    set_time_limit(300); // Tăng lên 5 phút thay vì 2 phút mặc định

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $data = [];

        // Lấy dòng đầu tiên làm header
        $headerRow = $sheet->getRowIterator()->current();
        $cellIterator = $headerRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $headers = [];
        $columnIndexes = [];

        // Xác định vị trí các cột cần thiết
        foreach ($cellIterator as $cell) {
            $cellValue = $cell->getValue();
            $header = trim(strtoupper($cellValue === null ? '' : $cellValue)); // Chuyển tên cột về chữ hoa
            $headers[$cell->getColumn()] = $header;

            // Lưu vị trí của các cột cần thiết
            switch ($header) {
                case 'XUONG':
                case 'XƯỞNG':
                    $columnIndexes['xuong'] = $cell->getColumn();
                    break;
                case 'LINE':
                case 'CHUYỀN':
                    $columnIndexes['line'] = $cell->getColumn();
                    break;
                case 'PO':
                case 'P/O':
                case 'P/O NO.':
                    $columnIndexes['po'] = $cell->getColumn();
                    break;
                case 'STYLE':
                case 'STYLE NO.':
                    $columnIndexes['style'] = $cell->getColumn();
                    break;
                case 'MODEL':
                case 'Model':
                    $columnIndexes['model'] = $cell->getColumn();
                    break;
                case 'QTY':
                case 'Qty':
                case 'SỐ LƯỢNG':
                case 'QUANTITY':
                    $columnIndexes['qty'] = $cell->getColumn();
                    break;
                case 'NGAY VAO':
                case 'NGÀY IN':
                case 'IN':
                    $columnIndexes['ngayin'] = $cell->getColumn();
                    break;
                case 'NGAY RA':
                case 'NGÀY OUT':
                case 'OUT':
                    $columnIndexes['ngayout'] = $cell->getColumn();
                    break;
            }
        }

        // Kiểm tra xem có đủ các cột cần thiết không
        $required_columns = ['xuong', 'line', 'po', 'style', 'qty', 'ngayin', 'ngayout'];
        $missing_columns = array_diff($required_columns, array_keys($columnIndexes));

        if (!empty($missing_columns)) {
            throw new Exception("Thiếu các cột bắt buộc: " . implode(", ", $missing_columns));
        }

        // Đọc dữ liệu từ các dòng tiếp theo
        $rowIndex = 2; // Bắt đầu từ dòng 2
        foreach ($sheet->getRowIterator() as $row) {
            if ($rowIndex === 1) {
                $rowIndex++;
                continue;
            }

            // Kiểm tra xem dòng có dữ liệu không
            $hasData = false;
            foreach ($columnIndexes as $column) {
                $cellValue = $sheet->getCell($column . $rowIndex)->getValue();
                $cellValue = is_null($cellValue) ? '' : trim((string)$cellValue);
                if (!empty($cellValue)) {
                    $hasData = true;
                    break;
                }
            }

            // Bỏ qua dòng nếu không có dữ liệu
            if (!$hasData) {
                $rowIndex++;
                continue;
            }

            // Lấy dữ liệu từ các cột đã xác định
            $line = isset($columnIndexes['line']) ?
                (is_null($sheet->getCell($columnIndexes['line'] . $rowIndex)->getValue()) ?
                '' : trim((string)$sheet->getCell($columnIndexes['line'] . $rowIndex)->getValue())) : '';

            $xuong = isset($columnIndexes['xuong']) ?
                (is_null($sheet->getCell($columnIndexes['xuong'] . $rowIndex)->getValue()) ?
                '' : trim((string)$sheet->getCell($columnIndexes['xuong'] . $rowIndex)->getValue())) : '';

            $po = isset($columnIndexes['po']) ?
                (is_null($sheet->getCell($columnIndexes['po'] . $rowIndex)->getValue()) ?
                '' : trim((string)$sheet->getCell($columnIndexes['po'] . $rowIndex)->getValue())) : '';

            $style = isset($columnIndexes['style']) ?
                (is_null($sheet->getCell($columnIndexes['style'] . $rowIndex)->getValue()) ?
                '' : trim((string)$sheet->getCell($columnIndexes['style'] . $rowIndex)->getValue())) : '';

            $qty = isset($columnIndexes['qty']) ?
                (is_null($sheet->getCell($columnIndexes['qty'] . $rowIndex)->getValue()) ?
                '' : trim((string)$sheet->getCell($columnIndexes['qty'] . $rowIndex)->getValue())) : '';

            $ngayin_raw = $sheet->getCell($columnIndexes['ngayin'] . $rowIndex)->getValue();
            $ngayout_raw = $sheet->getCell($columnIndexes['ngayout'] . $rowIndex)->getValue();

            // Kiểm tra dữ liệu trống
            if (empty($line) || empty($xuong) || empty($po) || empty($style) || empty($qty)) {
                $errors[] = "Dòng $rowIndex: Thiếu thông tin bắt buộc";
                $rowIndex++;
                continue;
            }

            // Chuyển đổi định dạng ngày
            $ngayin = formatDate($ngayin_raw);
            $ngayout = formatDate($ngayout_raw);

            // Kiểm tra ngày hợp lệ
            if (!$ngayin) {
                $errors[] = "Dòng $rowIndex: Ngày vào không đúng định dạng (ngày/tháng/năm)";
                $rowIndex++;
                continue;
            }

            if (!$ngayout) {
                $errors[] = "Dòng $rowIndex: Ngày ra không đúng định dạng (ngày/tháng/năm)";
                $rowIndex++;
                continue;
            }

            // Thêm vào mảng dữ liệu của lô hiện tại
            $current_batch[] = [
                'line' => $line,
                'xuong' => $xuong,
                'po' => $po,
                'style' => $style,
                'model' => isset($columnIndexes['model']) ?
                    (is_null($sheet->getCell($columnIndexes['model'] . $rowIndex)->getValue()) ?
                    '' : trim((string)$sheet->getCell($columnIndexes['model'] . $rowIndex)->getValue())) : '',
                'qty' => $qty,
                'ngayin' => $ngayin,
                'ngayout' => $ngayout
            ];

            // Xử lý lô dữ liệu nếu đã đủ kích thước batch hoặc đã hết dữ liệu
            if (count($current_batch) >= $batch_size) {
                $batch_result = processBatch($current_batch, $connect);

                if ($batch_result['success']) {
                    $imported_ids = array_merge($imported_ids, $batch_result['imported_ids']);
                    $success = array_merge($success, $batch_result['success_messages']);

                    // Thêm thông báo về các bản ghi trùng lặp vào danh sách errors
                    if (!empty($batch_result['duplicates'])) {
                        foreach ($batch_result['duplicates'] as $duplicate) {
                            $errors[] = "Bỏ qua bản ghi trùng lặp: " . $duplicate;
                        }
                    }

                    // Xử lý cài đặt mặc định cho lô hiện tại
                    $default_settings_result = processDefaultSettings($batch_result['imported_ids'], $success, $errors);
                    $success = $default_settings_result['success'];
                    $errors = $default_settings_result['errors'];
                } else {
                    $errors = array_merge($errors, $batch_result['errors']);
                }

                // Làm mới lô hiện tại
                $current_batch = [];
            }

            $rowIndex++;
        }

        // Xử lý lô cuối cùng nếu còn dữ liệu
        if (!empty($current_batch)) {
            $batch_result = processBatch($current_batch, $connect);

            if ($batch_result['success']) {
                $imported_ids = array_merge($imported_ids, $batch_result['imported_ids']);
                $success = array_merge($success, $batch_result['success_messages']);

                // Thêm thông báo về các bản ghi trùng lặp vào danh sách errors
                if (!empty($batch_result['duplicates'])) {
                    foreach ($batch_result['duplicates'] as $duplicate) {
                        $errors[] = "Bỏ qua bản ghi trùng lặp: " . $duplicate;
                    }
                }

                // Xử lý cài đặt mặc định cho lô cuối cùng
                $default_settings_result = processDefaultSettings($batch_result['imported_ids'], $success, $errors);
                $success = $default_settings_result['success'];
                $errors = $default_settings_result['errors'];
            } else {
                $errors = array_merge($errors, $batch_result['errors']);
            }
        }

        // Cập nhật date_display sau khi import dữ liệu
        if (!empty($imported_ids)) {
                error_log("Đã commit transaction sau khi import dữ liệu thành công.");

                // Đảm bảo bao gồm file display_deadline.php
                include_once 'display_deadline.php';

                // Đoạn code để cập nhật date_display sau khi import dữ liệu
                    // Load file display_deadline.php nếu chưa được load
            if (!function_exists('updateImportDateDisplay')) {
                include_once 'display_deadline.php';
            }

                    // Đảm bảo biến $message đã được khởi tạo
            if (!isset($message)) {
                $message = "";
            }

                    // Cập nhật tất cả hạn xử lý sau khi import
                    $update_result = updateAllDeadlinesAfterImport($imported_ids, $connect);

            if ($update_result['success']) {
                // $message .= "<br><strong>Đã cập nhật hạn xử lý cho {$update_result['updated_orders']}/" . count($imported_ids) . " đơn hàng, ";
                // $message .= "tổng cộng {$update_result['updated_criteria']} tiêu chí.</strong>";
            } else {
                $message .= "<br><strong style='color: red;'>Lỗi cập nhật hạn xử lý: " . ($update_result['message'] ?? 'Không xác định') . "</strong>";
            }

                    // Log bắt đầu quá trình cập nhật date_display
                    $log_file = 'logs/date_display_update.log';
                    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Bắt đầu cập nhật date_display sau khi import dữ liệu.\n", FILE_APPEND);

                    // Cập nhật date_display cho từng đơn hàng đã import
                    $updated_count = 0;
                    $total_updated_tieuchi = 0;
                    $total_orders = count($imported_ids);

            // Thêm tối ưu trong xử lý cập nhật date_display
            $date_display_batch_size = 10; // Giảm kích thước lô xuống 10 để xử lý nhanh hơn
            $date_display_batches = array_chunk($imported_ids, $date_display_batch_size);

            foreach ($date_display_batches as $date_display_batch) {
                foreach ($date_display_batch as $id_sanxuat) {
                        // Gọi hàm updateImportDateDisplay từ display_deadline.php
                        $result = updateImportDateDisplay($id_sanxuat, $connect);

                    if ($result['success']) {
                        $updated_count++;
                        $total_updated_tieuchi += $result['updated'];

                        // Ghi log thành công
                        $success_log = "[" . date('Y-m-d H:i:s') . "] Đã cập nhật date_display cho đơn hàng ID: $id_sanxuat. ";
                        $success_log .= "Số tiêu chí cập nhật: " . $result['updated'] . "\n";
                        file_put_contents($log_file, $success_log, FILE_APPEND);
                    } else {
                        // Ghi log lỗi
                        $error_log = "[" . date('Y-m-d H:i:s') . "] Lỗi cập nhật date_display cho đơn hàng ID: $id_sanxuat. ";
                        $error_log .= "Lỗi: " . $result['message'] . "\n";
                        file_put_contents($log_file, $error_log, FILE_APPEND);
                    }
                }

                // Tạm dừng ngắn để giảm tải server
                usleep(100000); // 100ms
            }

                    // Kết thúc ghi log
                    $end_log = "[" . date('Y-m-d H:i:s') . "] Hoàn tất cập nhật date_display sau khi import. ";
                    $end_log .= "Đã cập nhật $updated_count/$total_orders đơn hàng, tổng cộng $total_updated_tieuchi tiêu chí.\n";
                    file_put_contents($log_file, $end_log, FILE_APPEND);

                    // Thêm thông báo thành công vào message
            // $message .= "<br><strong>Đã cập nhật date_display cho $updated_count/$total_orders đơn hàng, tổng cộng $total_updated_tieuchi tiêu chí.</strong>";
        }
    } catch (Exception $e) {
        $errors[] = "Lỗi xử lý file: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Import Dữ Liệu</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <style>
        .error-container, .success-container {
            margin: 20px;
            padding: 15px;
            border-radius: 8px;
        }
        .error-container {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
        }
        .success-container {
            background-color: #dcfce7;
            border: 1px solid #22c55e;
        }
        .error-message {
            color: #dc2626;
            margin: 5px 0;
        }
        .success-message {
            color: #16a34a;
            margin: 5px 0;
        }
        .guide-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .guide-section h4 {
            color: #1e293b;
            margin-bottom: 15px;
        }
        .guide-section ul {
            list-style-type: none;
            padding: 0;
        }
        .guide-section li {
            margin: 10px 0;
            padding-left: 20px;
            position: relative;
        }
        .guide-section li:before {
            content: "•";
            color: #2563eb;
            font-weight: bold;
            position: absolute;
            left: 0;
        }
        .popup-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
            overflow: auto;
        }
        .popup-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 8px;
            text-align: center;
        }
        .close-btn {
            display: block;
            width: 100px;
            margin: 15px auto 0;
            padding: 8px;
            background-color: #22c55e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        /* Hiệu ứng loading */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .spinner {
            border: 6px solid #f3f3f3;
            border-top: 6px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        .loading-text {
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Thêm CSS cho responsive */
        @media screen and (max-width: 428px) {
            body {
                font-size: 14px;
            }
            
            .container {
                width: 100%;
                padding: 10px;
                margin: 0;
            }
            
            h3 {
                font-size: 18px;
                text-align: center;
            }
            
            form {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            input[type="file"] {
                width: 100%;
                margin-bottom: 10px;
            }
            
            button[type="submit"] {
                width: 100%;
                padding: 12px;
                font-size: 16px;
            }
            
            .guide-section {
                overflow-x: auto;
                padding: 10px;
            }
            
            table {
                min-width: 600px;
            }
            
            .popup-content {
                width: 90%;
                margin: 30% auto;
                padding: 15px;
            }
            
            .loading-text {
                font-size: 16px;
            }
            
            .navbar {
                padding: 5px;
                flex-direction: column;
                align-items: center;
            }
            
            .navbar-left {
                margin-bottom: 5px;
            }
            
            .error-container, .success-container {
                margin: 10px;
                padding: 10px;
            }
        }
        
        /* Thêm CSS cho màn hình xoay ngang trên điện thoại */
        @media screen and (max-width: 926px) and (orientation: landscape) {
            .container {
                padding: 5px;
            }
            
            .navbar {
                flex-direction: row;
                justify-content: space-between;
            }
            
            .popup-content {
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>
    <!-- Thanh điều hướng - Shared Header Component -->
    <?php
    $header_config = [
        'title' => 'Import Dữ Liệu',
        'title_short' => 'Import',
        'logo_path' => 'img/logoht.png',
        'logo_link' => '/khsanxuat/index.php',
        'show_search' => false,
        'show_mobile_menu' => true,
        'actions' => []
    ];
    include 'components/header.php';
    ?>

    <!-- Hiển thị thông báo -->
    <?php if (!empty($errors)) : ?>
        <div class="error-container">
            <h4>Có lỗi xảy ra:</h4>
            <?php foreach ($errors as $error) : ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)) : ?>
        <div class="success-container">
            <h4>Import thành công:</h4>
            <?php foreach ($success as $msg) : ?>
                <div class="success-message"><?php echo $msg; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Form nhập dữ liệu từ Excel -->
    <div class="container">
        <h3>NHẬP DỮ LIỆU TỪ FILE EXCEL</h3>
        <form action="import.php" method="post" enctype="multipart/form-data">
            <input type="file" name="excel_file" accept=".xls,.xlsx,.csv" required>
            <button type="submit">Tải lên</button>
        </form>
        
        <div class="guide-section">
            <h4>Hướng dẫn:</h4>
            <ul>
                <li>File Excel phải có các cột sau (khuyến nghị theo thứ tự):</li>
                <li style="margin-left: 20px;">- XƯỞNG hoặc XUONG</li>
                <li style="margin-left: 20px;">- LINE hoặc CHUYỀN</li>
                <li style="margin-left: 20px;">- PO hoặc P/O hoặc P/O NO.</li>
                <li style="margin-left: 20px;">- STYLE hoặc STYLE NO.</li>
                <li style="margin-left: 20px;">- QTY hoặc SỐ LƯỢNG</li>
                <li style="margin-left: 20px;">- NGÀY VÀO hoặc IN</li>
                <li style="margin-left: 20px;">- NGÀY RA hoặc OUT</li>
                <li>Định dạng ngày phải là: ngày/tháng/năm (ví dụ: 25/12/2023)</li>
                <li>Tất cả các cột không được để trống</li>
                <li>Dòng đầu tiên phải là tên cột</li>
                <li>Các cột khác trong file Excel sẽ được bỏ qua</li>
            </ul>

            <h4>Ví dụ file Excel mẫu:</h4>
            <table style="width: 100%; margin-top: 10px; border-collapse: collapse; border: 1px solid #e2e8f0;">
                <thead>
                    <tr style="background-color: #f8fafc;">
                        <th style="padding: 8px; border: 1px solid #e2e8f0;">XƯỞNG</th>
                        <th style="padding: 8px; border: 1px solid #e2e8f0;">LINE</th>
                        <th style="padding: 8px; border: 1px solid #e2e8f0;">PO</th>
                        <th style="padding: 8px; border: 1px solid #e2e8f0;">STYLE</th>
                        <th style="padding: 8px; border: 1px solid #e2e8f0;">QTY</th>
                        <th style="padding: 8px; border: 1px solid #e2e8f0;">NGÀY VÀO</th>
                        <th style="padding: 8px; border: 1px solid #e2e8f0;">NGÀY RA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">X1</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">L1</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">PO001</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">ST001</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">100</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">25/12/2023</td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;">30/12/2023</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- <div class="import-instructions">
            <h3>Hướng dẫn import dữ liệu:</h3>
            <p>File Excel cần có các cột sau:</p>
            <ul>
                <li>Xưởng</li>
                <li>LINE</li>
                <li>P/o no.</li>
                <li>Style</li>
                <li>Model</li>
                <li>Qty</li>
                <li>In (Ngày nhập)</li>
                <li>Out (Ngày xuất)</li>
            </ul>
            <p>Định dạng ngày tháng: DD/MM/YYYY</p>
        </div> -->
        </div>

    <!-- Thêm modal thông báo vào cuối body -->
    <div id="successModal" class="popup-modal">
        <div class="popup-content">
            <h4>Tải lên thành công!</h4>
            <p id="modalMessage"></p>
            <button class="close-btn" onclick="closeModal()">Đóng</button>
    </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <div class="loading-text">Đang xử lý file Excel...</div>
    </div>

    <script>
    function showSuccessModal(message) {
        document.getElementById('modalMessage').innerHTML = message;
        document.getElementById('successModal').style.display = 'block';
        hideLoading(); // Ẩn loading khi hiển thị modal thành công
    }

    function closeModal() {
        document.getElementById('successModal').style.display = 'none';
    }

    function showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }

    // Thêm event listener cho form submit
    document.addEventListener('DOMContentLoaded', function() {
        const importForm = document.querySelector('form[action="import.php"]');
        if (importForm) {
            importForm.addEventListener('submit', function() {
                const fileInput = document.querySelector('input[name="excel_file"]');
                if (fileInput && fileInput.files.length > 0) {
                    showLoading();
                }
            });
        }
    });

    <?php if (!empty($imported_ids)) : ?>
        showSuccessModal("Đã tải lên thành công <strong><?php echo count($imported_ids); ?></strong> đơn hàng!<br><?php echo $message ?? ''; ?>");
    <?php endif; ?>
    </script>
    <script src="assets/js/header.js"></script>
</body>
</html>
