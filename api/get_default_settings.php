<?php
// VERSION: 2026-03-01-v2 - Fixed GROUP BY
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
require_once __DIR__ . '/../bootstrap.php';

// Kiểm tra kết nối
if (!$connect) {
    die(json_encode([
        'success' => false,
        'message' => 'Lỗi kết nối database'
    ]));
}

// Lấy thông tin từ request
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$xuong = isset($_GET['xuong']) ? $_GET['xuong'] : '';
$line = isset($_GET['line']) ? $_GET['line'] : '';

if (empty($dept)) {
    die(json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin bộ phận'
    ]));
}

try {
    // Nếu có xưởng thì lấy theo xưởng cụ thể, nếu không thì lấy cài đặt mặc định cho tất cả xưởng
    if (!empty($xuong)) {
        // Lấy dữ liệu ưu tiên theo thứ tự:
        // 1. Cài đặt cho xưởng cụ thể (nếu có)
        // 2. Cài đặt mặc định cho tất cả xưởng (nếu không có cài đặt cho xưởng cụ thể)
        $sql = "SELECT ds.* FROM default_settings ds
                INNER JOIN (
                    SELECT id_tieuchi, MAX(CASE WHEN xuong = ? THEN 1 ELSE 0 END) as has_xuong
                    FROM default_settings
                    WHERE dept = ? AND (xuong = ? OR xuong = '')
                    GROUP BY id_tieuchi
                ) priority ON ds.id_tieuchi = priority.id_tieuchi
                WHERE ds.dept = ?
                  AND ((priority.has_xuong = 1 AND ds.xuong = ?) OR (priority.has_xuong = 0 AND ds.xuong = ''))
                ORDER BY ds.id_tieuchi";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssss", $xuong, $dept, $xuong, $dept, $xuong);
    } else {
        // Chỉ lấy cài đặt mặc định cho tất cả xưởng
        $sql = "SELECT * FROM default_settings WHERE dept = ? AND xuong = ''";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $dept);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[] = $row;
    }

    // Chọn line cụ thể: cột người trả về override theo (xuong, line);
    // không có override thì trả 0 để UI hiển thị "-- Theo xưởng --".
    if ($line !== '' && $xuong !== '') {
        $overrides = [];
        $sql_line = "SELECT id_tieuchi, nguoi_id FROM default_nguoi_line WHERE dept = ? AND xuong = ? AND line = ?";
        $stmt_line = $connect->prepare($sql_line);
        $stmt_line->bind_param("sss", $dept, $xuong, $line);
        $stmt_line->execute();
        $result_line = $stmt_line->get_result();
        while ($row_line = $result_line->fetch_assoc()) {
            $overrides[$row_line['id_tieuchi']] = $row_line['nguoi_id'];
        }

        foreach ($settings as &$setting) {
            $setting['nguoi_chiu_trachnhiem_default'] = isset($overrides[$setting['id_tieuchi']])
                ? $overrides[$setting['id_tieuchi']]
                : 0;
            unset($overrides[$setting['id_tieuchi']]);
        }
        unset($setting);

        // Tiêu chí có override nhưng chưa có dòng default_settings vẫn phải hiển thị người
        foreach ($overrides as $id_tieuchi => $nguoi_id) {
            $settings[] = [
                'id_tieuchi' => $id_tieuchi,
                'ngay_tinh_han' => 'ngay_vao',
                'so_ngay_xuly' => 7,
                'nguoi_chiu_trachnhiem_default' => $nguoi_id
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $settings
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn: ' . $e->getMessage()
    ]);
} 