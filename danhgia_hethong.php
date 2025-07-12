<?php
require "contdb.php";

$id_sanxuat = isset($_GET['id']) ? $_GET['id'] : 0;

// Mảng thông tin các bộ phận và yêu cầu điểm
$departments = [
    ['name' => 'BỘ PHẬN KẾ HOẠCH', 'yeucau' => 45],
    ['name' => 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)', 'yeucau' => 78],
    ['name' => 'KHO NGUYÊN, PHỤ LIỆU', 'yeucau' => 96],
    ['name' => 'BỘ PHẬN CẮT', 'yeucau' => 27],
    ['name' => 'BỘ PHẬN ÉP KEO', 'yeucau' => 24],
    ['name' => 'BỘ PHẬN CƠ ĐIỆN', 'yeucau' => 33],
    ['name' => 'BỘ PHẬN CHUYỀN MAY', 'yeucau' => 39],
    ['name' => 'BỘ PHẬN KCS', 'yeucau' => 36],
    ['name' => 'BỘ PHẬN ỦI THÀNH PHẨM', 'yeucau' => 15],
    ['name' => 'BỘ PHẬN HOÀN THÀNH', 'yeucau' => 45]
];

// Hàm tính tổng điểm của một bộ phận
function calculateDeptScore($connect, $id_sanxuat, $dept) {
    // Chuyển đổi tên bộ phận thành mã bộ phận trong database
    $dept_code = '';
    switch ($dept) {
        case 'BỘ PHẬN KẾ HOẠCH':
            $dept_code = 'kehoach';
            break;
        case 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)':
            $dept_code = 'chuanbi_sanxuat_phong_kt';
            break;
        case 'KHO NGUYÊN, PHỤ LIỆU':
            $dept_code = 'kho';
            break;
        case 'BỘ PHẬN CẮT':
            $dept_code = 'cat';
            break;
        case 'BỘ PHẬN ÉP KEO':
            $dept_code = 'ep_keo';
            break;
        case 'BỘ PHẬN CƠ ĐIỆN':
            $dept_code = 'co_dien';
            break;
        case 'BỘ PHẬN CHUYỀN MAY':
            $dept_code = 'chuyen_may';
            break;
        case 'BỘ PHẬN KCS':
            $dept_code = 'kcs';
            break;
        case 'BỘ PHẬN ỦI THÀNH PHẨM':
            $dept_code = 'ui_thanh_pham';
            break;
        case 'BỘ PHẬN HOÀN THÀNH':
            $dept_code = 'hoan_thanh';
            break;
    }

    $sql = "SELECT SUM(dg.diem_danhgia) as total_score
            FROM danhgia_tieuchi dg
            JOIN tieuchi_dept td ON dg.id_tieuchi = td.id
            WHERE dg.id_sanxuat = ? AND td.dept = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id_sanxuat, $dept_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total_score'] ?? 0;
}

// Lấy thông tin sản xuất
$sql = "SELECT * FROM khsanxuat WHERE stt = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $id_sanxuat);
$stmt->execute();
$result = $stmt->get_result();
$sanxuat = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đánh Giá Hệ Thống Sản Xuất</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .evaluation-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .evaluation-table th, .evaluation-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .evaluation-table th {
            background-color: #f4f4f4;
        }
        .score-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .score-info p {
            margin: 5px 0;
            color: #dc3545;
        }
        .warning-row {
            background-color: #ffebee;
        }
        .warning-row td {
            color: #c62828;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .navbar-center {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        .navbar-center h1 {
            font-size: 24px;
            margin: 0;
            text-align: center;
        }
        .style-info {
            background-color: #f0f7ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #1e40af;
        }
        
        .style-info h2 {
            margin-top: 0;
            color: #1e40af;
        }
        
        .navbar {
            background-color: #003366;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-left a {
            color: white;
            text-decoration: none;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .btn-back {
            display: inline-block;
            padding: 8px 16px;
            background-color: #1e40af;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .btn-back:hover {
            background-color: #1c3879;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <a href="index.php"><img width="45px" src="img/logoht.png" /></a>
        </div>
        <div class="navbar-center">
            <h1 style="font-size: 24px; margin: 0;">TỔNG ĐIỂM ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT</h1>
        </div>
        <div class="navbar-right">
            <a href="index.php" class="btn-back">Trang Chủ</a>
        </div>
    </div>

    <div class="container">
        <?php if ($sanxuat): ?>
        <!-- Thông tin sản phẩm -->
        <div class="style-info">
            <h2>Style: <?php echo htmlspecialchars($sanxuat['style']); ?> (STT: <?php echo $sanxuat['stt']; ?>)</h2>
            <p><strong>PO:</strong> <?php echo htmlspecialchars($sanxuat['po']); ?></p>
            <p><strong>Line:</strong> <?php echo htmlspecialchars($sanxuat['line1']); ?></p>
            <p><strong>Xưởng:</strong> <?php echo htmlspecialchars($sanxuat['xuong']); ?></p>
            <p><strong>Ngày vào:</strong> <?php echo date('d/m/Y', strtotime($sanxuat['ngayin'])); ?></p>
            <p><strong>Ngày ra:</strong> <?php echo date('d/m/Y', strtotime($sanxuat['ngayout'])); ?></p>
        </div>
        <?php endif; ?>

        <table class="evaluation-table">
            <thead>
                <tr>
                    <th style="color: #1e40af">STT</th>
                    <th style="color: #1e40af">BỘ PHẬN THỰC HIỆN</th>
                    <th style="color: #1e40af">YÊU CẦU</th>
                    <th style="color: #1e40af">ĐIỂM</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stt = 1;
                $total_yeucau = 0;
                $total_score = 0;

                foreach ($departments as $dept) {
                    $score = calculateDeptScore($connect, $id_sanxuat, $dept['name']);
                    if ($score === null) $score = 0; // Đảm bảo score là 0 nếu null
                    $total_yeucau += $dept['yeucau'];
                    $total_score += $score;
                    
                    // So sánh điểm với điểm yêu cầu
                    $row_style = '';
                    if ($score < $dept['yeucau']) {
                        $row_style = ' style="background-color: #ffebee;"';
                    }
                    ?>
                    <tr<?php echo $row_style; ?>>
                        <td><?php echo $stt++; ?></td>
                        <td style="text-align: left"><?php echo $dept['name']; ?></td>
                        <td><?php echo number_format($dept['yeucau'], 0); ?></td>
                        <td><?php echo number_format($score, 0); ?></td>
                    </tr>
                    <?php
                }
                ?>
                <tr class="total-row">
                    <td colspan="2" style="text-align: center;color:rgb(255, 0, 0)">TỔNG ĐIỂM</td>
                    <td style="color:rgb(255, 0, 0);font-weight:bold"><?php echo number_format($total_yeucau, 0); ?></td>
                    <td style="color:rgb(255, 0, 0);font-weight:bold"><?php echo number_format($total_score, 0); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="score-info">
            <p>Căn cứ vào các tiêu chí đánh giá nêu trên, nhà máy sẽ tự đánh giá được Hệ thống sản xuất tại đơn vị mình</p>
            <p>và thiết lập kế hoạch khắc phục, triển khai thực hiện khắc phục.</p>
            <p>Mức độ rủi ro tại Nhà máy về tổ chức - Quản lý hệ thống sản xuất được xác định như sau:</p>
            <p>- Dưới 267 điểm : Nhà máy có rủi ro cao ở nhiều bộ phận, không tuân thủ quy trình Cần khắc phục ngay.</p>
            <p>- Từ 267 -> 338 điểm : NM có rủi ro thấp, cần khắc phục sớm</p>
            <p>- Từ 339 -> 430 điểm: Nhà máy đạt yêu cầu cần cải thiện thêm.</p>
            <p>- Từ 431 điểm trở lên: Nhà máy quản lý và thực hiện tốt, cần duy trì .</p>
        </div>
    </div>
</body>
</html> 