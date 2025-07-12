<?php
// Kết nối database
require "contdb.php";

// Sử dụng thư viện PhpSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

// Lấy thông tin tháng và năm từ tham số URL
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Hàm kiểm tra trạng thái hoàn thành của một bộ phận
function checkDeptStatus($connect, $id_sanxuat, $dept) {
    // Lấy thông tin trạng thái hoàn thành
    $sql = "SELECT completed, completed_date FROM dept_status WHERE id_sanxuat = ? AND dept = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id_sanxuat, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Trả về trạng thái và ngày hoàn thành nếu có
        return [
            'completed' => $row['completed'] == 1,
            'date' => $row['completed_date'] ? date('d/m/Y', strtotime($row['completed_date'])) : null
        ];
    }
    
    // Nếu không tìm thấy dữ liệu
    return [
        'completed' => false,
        'date' => null
    ];
}

// Hàm lấy deadline sớm nhất của bộ phận
function getEarliestDeadline($connect, $id_sanxuat, $dept) {
    // Truy vấn lấy ngày hạn xử lý thấp nhất của các tiêu chí
    $sql = "SELECT MIN(dg.han_xuly) AS earliest_deadline
            FROM danhgia_tieuchi dg
            JOIN tieuchi_dept tc ON dg.id_tieuchi = tc.id
            WHERE dg.id_sanxuat = ? 
            AND tc.dept = ?
            AND dg.han_xuly IS NOT NULL";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id_sanxuat, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['earliest_deadline'];
    }
    
    // Trả về null nếu không có hạn xử lý nào
    return null;
}

// Hàm tính ngày dự kiến cho từng bộ phận dựa vào ngày vào
function calculateExpectedDate($dept, $ngayin) {
    $days_before = 0;
    
    // Xác định số ngày trước ngày vào dựa vào bộ phận
    switch ($dept) {
        case 'kehoach':
            $days_before = 7; // Kế hoạch: 7 ngày trước ngày vào
            break;
        case 'kho':
            $days_before = 14; // Kho: 14 ngày trước ngày vào
            break;
        case 'cat':
            $days_before = 5; // Cắt: 5 ngày trước ngày vào
            break;
        case 'ep_keo':
            $days_before = 4; // Ép keo: 4 ngày trước ngày vào
            break;
        case 'chuanbi_sanxuat_phong_kt':
            $days_before = 6; // Kỹ thuật: 6 ngày trước ngày vào
            break;
        case 'may':
        case 'hoan_thanh':
        case 'co_dien':
        case 'kcs':
        case 'ui_thanh_pham':
        case 'chuyen_may':
        case 'quan_ly_sx':
        case 'quan_ly_cl':
            $days_before = 0; // Các bộ phận khác: ngày vào
            break;
        default:
            $days_before = 0;
    }
    
    // Tính ngày dự kiến
    $expected_date = clone $ngayin;
    if ($days_before > 0) {
        $expected_date->modify("-$days_before days");
    }
    
    return $expected_date->format('d/m');
}

// Tạo một spreadsheet mới
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('KH Rai Chuyen ' . $selected_month . '_' . $selected_year);

// Thêm 2 dòng tiêu đề
// Dòng 1: Tên công ty
$sheet->setCellValue('A1', 'CÔNG TY MAY HOÀ THỌ ĐIỆN BÀN');
$sheet->mergeCells('A1:V1');

// Dòng 2: Tiêu đề báo cáo
$sheet->setCellValue('A2', 'ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT TOÀN NHÀ MÁY THÁNG ' . $selected_month . '/' . $selected_year);
$sheet->mergeCells('A2:V2');

// Style cho tiêu đề
$titleStyle = [
    'font' => [
        'bold' => true,
        'size' => 22,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];
$sheet->getStyle('A1:V1')->applyFromArray($titleStyle);
$sheet->getStyle('A2:V2')->applyFromArray($titleStyle);

// Thiết lập chiều cao cho dòng tiêu đề
$sheet->getRowDimension(1)->setRowHeight(40);
$sheet->getRowDimension(2)->setRowHeight(40);

// Đặt tiêu đề cho các cột (di chuyển xuống dòng 3)
$sheet->setCellValue('A3', 'STT');
$sheet->setCellValue('B3', 'XƯỞNG');
$sheet->setCellValue('C3', 'LINE');
$sheet->setCellValue('D3', 'PO');
$sheet->setCellValue('E3', 'STYLE');
$sheet->setCellValue('F3', 'QTY');
$sheet->setCellValue('G3', 'NGÀY VÀO');
$sheet->setCellValue('H3', 'NGÀY RA');
$sheet->setCellValue('I3', 'KẾ HOẠCH');
$sheet->setCellValue('J3', 'KHO');
$sheet->setCellValue('K3', 'CẮT');
$sheet->setCellValue('L3', 'ÉP KEO');
$sheet->setCellValue('M3', 'KỸ THUẬT');
$sheet->setCellValue('N3', 'MAY');
$sheet->setCellValue('O3', 'HOÀN THÀNH');
$sheet->setCellValue('P3', 'CƠ ĐIỆN');
$sheet->setCellValue('Q3', 'KCS');
$sheet->setCellValue('R3', 'ỦI TP');
$sheet->setCellValue('S3', 'CHUYỀN MAY');
$sheet->setCellValue('T3', 'QL SX');
$sheet->setCellValue('U3', 'QL CL');
$sheet->setCellValue('V3', 'TRẠNG THÁI');

// Style cho header
$headerStyle = [
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => 'E2E8F0',
        ],
    ],
];
$sheet->getStyle('A3:V3')->applyFromArray($headerStyle);

// Danh sách các bộ phận
$departments = [
    'kehoach' => 'I',
    'kho' => 'J',
    'cat' => 'K',
    'ep_keo' => 'L',
    'chuanbi_sanxuat_phong_kt' => 'M',
    'may' => 'N',
    'hoan_thanh' => 'O',
    'co_dien' => 'P',
    'kcs' => 'Q',
    'ui_thanh_pham' => 'R',
    'chuyen_may' => 'S',
    'quan_ly_sx' => 'T',
    'quan_ly_cl' => 'U'
];

// Lấy dữ liệu từ database theo tháng và năm đã chọn
$query = "SELECT * FROM khsanxuat WHERE MONTH(ngayin) = ? AND YEAR(ngayin) = ? ORDER BY xuong ASC, CAST(line1 AS UNSIGNED) ASC, ngayin ASC";
$stmt = $connect->prepare($query);
$stmt->bind_param("ii", $selected_month, $selected_year);
$stmt->execute();
$result = $stmt->get_result();
$row_count = 4; // Bắt đầu từ dòng 4 (do thêm 2 dòng tiêu đề)

while ($row = $result->fetch_assoc()) {
    // Tính ngày kế hoạch và ngày kho
    $ngayin = new DateTime($row['ngayin']);
    
    $kehoach = clone $ngayin;
    $kehoach->modify('-7 days');
    
    $kho = clone $ngayin;
    $kho->modify('-14 days');

    // Kiểm tra trạng thái hoàn thành của tất cả các bộ phận
    $all_complete = true;
    $dept_statuses = [];
    
    foreach ($departments as $dept_code => $column) {
        $status = checkDeptStatus($connect, $row['stt'], $dept_code);
        $dept_statuses[$dept_code] = $status;
        if (!$status['completed']) {
            $all_complete = false;
        }
    }

    // Ghi dữ liệu vào từng ô
    $sheet->setCellValue('A' . $row_count, $row_count - 3);
    $sheet->setCellValue('B' . $row_count, $row['xuong']);
    $sheet->setCellValue('C' . $row_count, $row['line1']);
    $sheet->setCellValue('D' . $row_count, $row['po']);
    $sheet->setCellValue('E' . $row_count, $row['style']);
    $sheet->setCellValue('F' . $row_count, $row['qty']);
    $sheet->setCellValue('G' . $row_count, date('d/m/Y', strtotime($row['ngayin'])));
    $sheet->setCellValue('H' . $row_count, date('d/m/Y', strtotime($row['ngayout'])));
    
    // Thêm trạng thái cho từng bộ phận
    foreach ($departments as $dept_code => $column) {
        $status = $dept_statuses[$dept_code];
        
        // Lấy deadline sớm nhất cho bộ phận này
        $dept_deadline = getEarliestDeadline($connect, $row['stt'], $dept_code);
        
        // Sử dụng deadline hoặc ngày kế hoạch nếu không có deadline
        $deadline_date = $dept_deadline ? date('d/m/Y', strtotime($dept_deadline)) : $kehoach->format('d/m/Y');
        
        // Xác định văn bản trạng thái
        if ($status['completed']) {
            // Đã hoàn thành: hiển thị dấu tích và deadline
            $statusText = '✓ ' . $deadline_date;
        } else {
            // Chưa hoàn thành: hiển thị X và deadline
            $statusText = '✗ ' . $deadline_date;
        }
        
        $sheet->setCellValue($column . $row_count, $statusText);
        
        // Style cho ô trạng thái
        $cellStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'color' => ['rgb' => $status['completed'] ? '008000' : 'FF0000'],
                'bold' => true,
            ],
        ];
        $sheet->getStyle($column . $row_count)->applyFromArray($cellStyle);
    }
    
    // Trạng thái chung
    $sheet->setCellValue('V' . $row_count, $all_complete ? 'Hoàn thành' : 'Đang xử lý');
    
    // Tô màu xanh cho dòng nếu tất cả bộ phận đã hoàn thành
    if ($all_complete) {
        $rowStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E6F4EA', // Màu xanh nhạt
                ],
            ],
        ];
        $sheet->getStyle('A' . $row_count . ':V' . $row_count)->applyFromArray($rowStyle);
    }
    
    $row_count++;
}

// Style cho nội dung
$contentStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];
$sheet->getStyle('A4:V' . ($row_count - 1))->applyFromArray($contentStyle);

// Tự động điều chỉnh độ rộng cột
foreach (range('A', 'V') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Style cho trạng thái chung
$statusStyle = [
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'font' => [
        'bold' => true,
    ],
];
$sheet->getStyle('V4:V' . ($row_count - 1))->applyFromArray($statusStyle);

// Thêm thông tin về tháng xuất dữ liệu
$sheet->setCellValue('A' . ($row_count + 1), 'Dữ liệu xuất theo tháng: ' . $selected_month . '/' . $selected_year);
$sheet->mergeCells('A' . ($row_count + 1) . ':D' . ($row_count + 1));
$sheet->getStyle('A' . ($row_count + 1))->getFont()->setBold(true);

// Tạo tên file với timestamp và thông tin tháng/năm
$filename = 'KH_RaiChuyen_Thang' . $selected_month . '_Nam' . $selected_year . '_' . date('Ymd_His') . '.xlsx';

// Header để download file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Tạo đối tượng Writer để ghi file Excel
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit; 