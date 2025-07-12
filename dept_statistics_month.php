<?php
require "contdb.php";

// Lấy thông tin tháng từ URL
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Mảng thông tin các bộ phận và yêu cầu điểm
$departments = [
    ['name' => 'BỘ PHẬN KẾ HOẠCH', 'code' => 'kehoach', 'yeucau' => 45, 'color' => '#FF6384'],
    ['name' => 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)', 'code' => 'chuanbi_sanxuat_phong_kt', 'yeucau' => 78, 'color' => '#36A2EB'],
    ['name' => 'KHO NGUYÊN, PHỤ LIỆU', 'code' => 'kho', 'yeucau' => 96, 'color' => '#FFCE56'],
    ['name' => 'BỘ PHẬN CẮT', 'code' => 'cat', 'yeucau' => 27, 'color' => '#4BC0C0'],
    ['name' => 'BỘ PHẬN ÉP KEO', 'code' => 'ep_keo', 'yeucau' => 24, 'color' => '#9966FF'],
    ['name' => 'BỘ PHẬN CƠ ĐIỆN', 'code' => 'co_dien', 'yeucau' => 33, 'color' => '#FF9F40'],
    ['name' => 'BỘ PHẬN CHUYỀN MAY', 'code' => 'chuyen_may', 'yeucau' => 39, 'color' => '#FF6384'],
    ['name' => 'BỘ PHẬN KCS', 'code' => 'kcs', 'yeucau' => 36, 'color' => '#36A2EB'],
    ['name' => 'BỘ PHẬN ỦI THÀNH PHẨM', 'code' => 'ui_thanh_pham', 'yeucau' => 15, 'color' => '#4BC0C0'],
    ['name' => 'BỘ PHẬN HOÀN THÀNH', 'code' => 'hoan_thanh', 'yeucau' => 45, 'color' => '#9966FF']
];

// Hàm kiểm tra trạng thái hoàn thành của một bộ phận
function checkDeptStatus($connect, $id_sanxuat, $dept) {
    $sql = "SELECT completed FROM dept_status WHERE id_sanxuat = ? AND dept = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id_sanxuat, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    $status = $result->fetch_assoc();
    
    return (!$status || $status['completed'] == 0) ? false : true;
}

// Hàm kiểm tra xem tất cả các bộ phận đã hoàn thành chưa
function allDeptsCompleted($connect, $id_sanxuat) {
    $all_depts = ['kehoach', 'chuanbi_sanxuat_phong_kt', 'kho', 'cat', 'ep_keo', 'co_dien', 'chuyen_may', 'kcs', 'ui_thanh_pham', 'hoan_thanh'];
    
    foreach ($all_depts as $dept) {
        if (!checkDeptStatus($connect, $id_sanxuat, $dept)) {
            return false;
        }
    }
    
    return true;
}

// Hàm tính tổng điểm của một bộ phận cho một sản phẩm
function calculateDeptScore($connect, $id_sanxuat, $dept_code) {
    $sql = "SELECT SUM(dg.diem_danhgia) as total_score, COUNT(tc.id) as total_criteria
            FROM tieuchi_dept tc
            LEFT JOIN danhgia_tieuchi dg ON tc.id = dg.id_tieuchi AND dg.id_sanxuat = ?
            WHERE tc.dept = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id_sanxuat, $dept_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    return [
        'score' => $data['total_score'] ?? 0,
        'total_criteria' => $data['total_criteria'] ?? 0,
        'max_score' => $data['total_criteria'] * 3 // Giả sử điểm tối đa cho mỗi tiêu chí là 3
    ];
}

// Lấy danh sách các sản phẩm trong tháng đã chọn
$query = "SELECT * FROM khsanxuat 
          WHERE MONTH(ngayin) = ? AND YEAR(ngayin) = ?
          ORDER BY ngayin ASC";

$stmt = $connect->prepare($query);
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$result = $stmt->get_result();
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Lọc ra những sản phẩm mà tất cả các bộ phận đều đã hoàn thành
$completed_products = [];
foreach ($products as $product) {
    if (allDeptsCompleted($connect, $product['stt'])) {
        $completed_products[] = $product;
    }
}

// Đếm số Mã hàng đã hoàn thành cho từng bộ phận
$dept_completed_counts = [];
foreach ($departments as $dept) {
    $completed_count = 0;
    foreach ($products as $product) {
        if (checkDeptStatus($connect, $product['stt'], $dept['code'])) {
            $completed_count++;
        }
    }
    $dept_completed_counts[$dept['code']] = $completed_count;
}

// Tính điểm trung bình cho mỗi bộ phận
$dept_stats = [];

foreach ($departments as $dept) {
    $total_score = 0;
    $total_max_score = 0;
    $product_count = 0;
    
    foreach ($completed_products as $product) {
        $score_data = calculateDeptScore($connect, $product['stt'], $dept['code']);
        $total_score += $score_data['score'];
        $total_max_score += $score_data['max_score'];
        $product_count++;
    }
    
    $avg_score = $product_count > 0 ? $total_score / $product_count : 0;
    $avg_max_score = $product_count > 0 ? $total_max_score / $product_count : 0;
    $avg_percent = $avg_max_score > 0 ? ($avg_score / $avg_max_score) * 100 : 0;
    
    $dept_stats[] = [
        'name' => $dept['name'],
        'code' => $dept['code'],
        'yeucau' => $dept['yeucau'],
        'avg_score' => $avg_score,
        'avg_max_score' => $avg_max_score,
        'avg_percent' => $avg_percent,
        'color' => $dept['color'],
        'product_count' => $product_count,
        'completed_count' => $dept_completed_counts[$dept['code']]
    ];
}

// Lấy danh sách các tháng có dữ liệu
$months_query = "SELECT DISTINCT MONTH(ngayin) as month, YEAR(ngayin) as year 
                FROM khsanxuat 
                ORDER BY year DESC, month DESC";
$months_result = mysqli_query($connect, $months_query);
$available_months = mysqli_fetch_all($months_result, MYSQLI_ASSOC);

// Tính tổng điểm trung bình của tất cả các bộ phận
$total_avg_score = 0;
$total_yeucau = 0;

foreach ($dept_stats as $dept) {
    $total_avg_score += $dept['avg_score'];
    $total_yeucau += $dept['yeucau'];
}

// Xác định mức độ rủi ro dựa trên tổng điểm trung bình
$risk_level = '';
$risk_color = '';
$risk_message = '';

if ($total_avg_score < 267) {
    $risk_level = 'Rủi ro cao';
    $risk_color = '#ef4444'; // Đỏ
    $risk_message = 'Nhà máy có rủi ro cao ở nhiều bộ phận, không tuân thủ quy trình. Cần khắc phục ngay.';
} elseif ($total_avg_score >= 267 && $total_avg_score <= 338) {
    $risk_level = 'Rủi ro thấp';
    $risk_color = '#f97316'; // Cam
    $risk_message = 'Nhà máy có rủi ro thấp, cần khắc phục sớm.';
} elseif ($total_avg_score >= 339 && $total_avg_score <= 430) {
    $risk_level = 'Đạt yêu cầu';
    $risk_color = '#eab308'; // Vàng
    $risk_message = 'Nhà máy đạt yêu cầu, cần cải thiện thêm.';
} else {
    $risk_level = 'Quản lý tốt';
    $risk_color = '#10b981'; // Xanh lá
    $risk_message = 'Nhà máy quản lý và thực hiện tốt, cần duy trì.';
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thống Kê Điểm Trung Bình Các Bộ Phận</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #1e40af;
            margin-bottom: 10px;
        }
        
        .header h2 {
            color: #4b5563;
            margin-top: 0;
        }
        
        .evaluation-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .evaluation-table th, .evaluation-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        
        .evaluation-table th {
            background-color: #f4f4f4;
            color: #1e40af;
            font-weight: bold;
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
        
        .navbar-center {
            font-weight: bold;
            display: flex;
            justify-content: center;
            width: 100%;
        }
        
        .navbar-center h1 {
            font-size: 24px;
            margin: 0;
            text-align: center;
        }
        
        .navbar-right a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }
        
        .progress-container {
            height: 10px;
            background-color: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s ease;
        }
        
        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
        }
        
        .filter-form label {
            font-weight: bold;
            color: #1f2937;
        }
        
        .filter-form select {
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background-color: white;
        }
        
        .btn-back {
            display: inline-block;
            padding: 8px 16px;
            background-color: #1e40af;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .btn-back:hover {
            background-color: #1c3879;
        }
        
        .summary-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .summary-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #1e40af;
        }
        
        .summary-stats {
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .risk-assessment {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border-left: 5px solid #1e40af;
        }
        
        .risk-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #1e40af;
        }
        
        .risk-level {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .risk-message {
            font-size: 16px;
            color: #4b5563;
        }
        
        .score-high-risk {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
        }
        
        .score-medium-risk {
            background-color: #ffedd5;
            color: #c2410c;
            font-weight: bold;
        }
        
        .score-low-risk {
            background-color: #fef9c3;
            color: #854d0e;
            font-weight: bold;
        }
        
        .score-no-risk {
            background-color: #d1fae5;
            color: #065f46;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <a href="index.php"><img width="45px" src="img/logoht.png" /></a>
        </div>
        <div class="navbar-center">
            <h1 style="font-size: 24px; margin: 0;">THỐNG KÊ ĐIỂM TRUNG BÌNH CÁC BỘ PHẬN</h1>
        </div>
        <!-- <div class="navbar-right">
            <a href="index.php">Trang Chủ</a>
        </div> -->
    </div>

    <div class="container">
        <div class="header">
            <h1>Thống Kê Điểm Trung Bình Các Bộ Phận</h1>
            <h2>Tháng <?php echo $month; ?>/<?php echo $year; ?></h2>
        </div>
        
        <!-- Form lọc -->
        <form class="filter-form" method="GET" action="">
            <label for="month">Chọn tháng:</label>
            <select id="month" name="month" onchange="this.form.submit()">
                <?php foreach ($available_months as $m): ?>
                    <option value="<?php echo $m['month']; ?>" 
                            data-year="<?php echo $m['year']; ?>" 
                            <?php echo ($m['month'] == $month && $m['year'] == $year) ? 'selected' : ''; ?>>
                        Tháng <?php echo $m['month']; ?>/<?php echo $m['year']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="year" id="year" value="<?php echo $year; ?>">
            <script>
                document.getElementById('month').addEventListener('change', function() {
                    var selectedOption = this.options[this.selectedIndex];
                    document.getElementById('year').value = selectedOption.getAttribute('data-year');
                });
            </script>
        </form>
        
        <!-- Thẻ tổng kết -->
        <div class="summary-card">
            <div class="summary-title">Tổng Kết Tháng <?php echo $month; ?>/<?php echo $year; ?></div>
            <div class="summary-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($completed_products); ?></div>
                    <div class="stat-label">Sản phẩm hoàn thành tất cả bộ phận</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: <?php echo $risk_color; ?>;"><?php echo number_format($total_avg_score, 0); ?></div>
                    <div class="stat-label">Tổng điểm trung bình</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($total_yeucau, 0); ?></div>
                    <div class="stat-label">Tổng điểm yêu cầu</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">
                        <?php 
                        $overall_percent = $total_yeucau > 0 ? ($total_avg_score / $total_yeucau) * 100 : 0;
                        echo number_format($overall_percent, 1) . '%'; 
                        ?>
                    </div>
                    <div class="stat-label">Tỷ lệ hoàn thành tổng thể</div>
                </div>
            </div>
        </div>
        
        <!-- Bảng đánh giá -->
        <table class="evaluation-table">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">STT</th>
                    <th style="width: 25%; text-align: left;">BỘ PHẬN THỰC HIỆN</th>
                    <th style="width: 1%; text-align: center;">ĐIỂM YÊU CẦU TRUNG BÌNH</th>
                    <th style="width: 24%; text-align: center;">ĐIỂM TRUNG BÌNH</th>
                    <th style="width: 20%; text-align: center;">TỶ LỆ HOÀN THÀNH</th>
                    <th style="width: 25%; text-align: center;">SỐ MÃ HÀNG HOÀN THÀNH</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stt = 1;
                foreach ($dept_stats as $dept) {
                    // So sánh điểm với điểm yêu cầu
                    $row_style = '';
                    if ($dept['avg_score'] < $dept['yeucau']) {
                        $row_style = ' style="background-color: #ffebee;"';
                    }
                    ?>
                    <tr<?php echo $row_style; ?>>
                        <td style="text-align: center;"><?php echo $stt++; ?></td>
                        <td style="text-align: left;">
                            <a href="dept_statistics.php?dept=<?php echo $dept['code']; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" style="color: inherit; text-decoration: underline;">
                                <?php echo $dept['name']; ?>
                            </a>
                        </td>
                        <td style="text-align: center;"><?php echo number_format($dept['yeucau'], 0); ?></td>
                        <td style="text-align: center;"><?php echo number_format($dept['avg_score'], 1); ?></td>
                        <td style="text-align: center;">
                            <?php 
                            $percent = $dept['avg_percent'];
                            echo '<span style="color: ' . $dept['color'] . '; font-weight: bold;">' . number_format($percent, 1) . '%</span>'; 
                            ?>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?php echo $percent; ?>%; background-color: <?php echo $dept['color']; ?>;"></div>
                            </div>
                        </td>
                        <td style="text-align: center;"><?php echo $dept['completed_count']; ?>/<?php echo count($products); ?></td>
                    </tr>
                    <?php
                }
                
                // Xác định class cho ô tổng điểm trung bình dựa trên mức độ rủi ro
                $total_score_class = '';
                $total_score_color = '';
                if ($total_avg_score < 267) {
                    $total_score_color = '#ef4444'; // Đỏ
                } elseif ($total_avg_score >= 267 && $total_avg_score <= 338) {
                    $total_score_color = '#f97316'; // Cam
                } elseif ($total_avg_score >= 339 && $total_avg_score <= 430) {
                    $total_score_color = '#eab308'; // Vàng
                } else {
                    $total_score_color = '#10b981'; // Xanh lá
                }
                ?>
                <tr class="total-row">
                    <td colspan="2" style="text-align: center;color:rgb(255, 0, 0)">TỔNG ĐIỂM</td>
                    <td style="color:rgb(255, 0, 0);font-weight:bold; text-align: center;"><?php echo number_format($total_yeucau, 0); ?></td>
                    <td style="background-color: <?php echo $total_score_color; ?>; color: white; font-weight:bold; font-size: 16px; text-align: center;"><?php echo number_format($total_avg_score, 0); ?></td>
                    <td style="color:rgb(255, 0, 0);font-weight:bold; text-align: center;">
                        <span style="color: #4BC0C0; font-weight: bold;"><?php echo number_format($overall_percent, 1); ?>%</span>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $overall_percent; ?>%; background-color: #4BC0C0;"></div>
                        </div>
                    </td>
                    <td style="color:rgb(255, 0, 0);font-weight:bold; text-align: center;"><?php echo count($completed_products); ?>/<?php echo count($products); ?></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Đánh giá rủi ro -->
        <div class="risk-assessment" style="border-left-color: <?php echo $risk_color; ?>; margin-top: 30px; margin-bottom: 30px;">
            <div class="risk-title">Đánh Giá Mức Độ Rủi Ro</div>
            <div class="risk-level" style="color: <?php echo $risk_color; ?>;"><?php echo $risk_level; ?></div>
            <div class="risk-message"><?php echo $risk_message; ?></div>
        </div>

        <div class="score-info">
            <p>Căn cứ vào các tiêu chí đánh giá nêu trên, nhà máy sẽ tự đánh giá được Hệ thống sản xuất tại đơn vị mình</p>
            <p>và thiết lập kế hoạch khắc phục, triển khai thực hiện khắc phục.</p>
            <p>Mức độ rủi ro tại Nhà máy về tổ chức - Quản lý hệ thống sản xuất được xác định như sau:</p>
            <p>- Dưới 267 điểm : Nhà máy có rủi ro cao ở nhiều bộ phận, không tuân thủ quy trình Cần khắc phục ngay.</p>
            <p>- Từ 267 -> 338 điểm : NM có rủi ro thấp, cần khắc phục sớm</p>
            <p>- Từ 339 -> 430 điểm: Nhà máy đạt yêu cầu cần cải thiện thêm.</p>
            <p>- Từ 431 điểm trở lên: Nhà máy quản lý và thực hiện tốt, cần duy trì .</p>
        </div>

        <div class="button-group">
            <a href="index.php" class="btn-back">Quay lại</a>
        </div>
    </div>
</body>
</html> 