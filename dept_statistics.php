<?php
require "contdb.php";

// Lấy thông tin bộ phận và tháng từ URL
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Ánh xạ mã bộ phận sang tên hiển thị
$dept_names = [
    'kehoach' => 'BỘ PHẬN KẾ HOẠCH',
    'chuanbi_sanxuat_phong_kt' => 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)',
    'kho' => 'KHO NGUYÊN, PHỤ LIỆU',
    'cat' => 'BỘ PHẬN CẮT',
    'ep_keo' => 'BỘ PHẬN ÉP KEO',
    'co_dien' => 'BỘ PHẬN CƠ ĐIỆN',
    'chuyen_may' => 'BỘ PHẬN CHUYỀN MAY',
    'kcs' => 'BỘ PHẬN KCS',
    'ui_thanh_pham' => 'BỘ PHẬN ỦI THÀNH PHẨM',
    'hoan_thanh' => 'BỘ PHẬN HOÀN THÀNH'
];

// Màu sắc cho từng bộ phận
$dept_colors = [
    'kehoach' => '#f59e0b',
    'chuanbi_sanxuat_phong_kt' => '#8b5cf6',
    'kho' => '#06b6d4',
    'cat' => '#10b981',
    'ep_keo' => '#f97316',
    'co_dien' => '#6366f1',
    'chuyen_may' => '#ec4899',
    'kcs' => '#14b8a6',
    'ui_thanh_pham' => '#8b5cf6',
    'hoan_thanh' => '#ef4444'
];

// Mảng thông tin các bộ phận và yêu cầu điểm
$departments_info = [
    'kehoach' => ['name' => 'BỘ PHẬN KẾ HOẠCH', 'yeucau' => 45],
    'chuanbi_sanxuat_phong_kt' => ['name' => 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)', 'yeucau' => 78],
    'kho' => ['name' => 'KHO NGUYÊN, PHỤ LIỆU', 'yeucau' => 96],
    'cat' => ['name' => 'BỘ PHẬN CẮT', 'yeucau' => 27],
    'ep_keo' => ['name' => 'BỘ PHẬN ÉP KEO', 'yeucau' => 24],
    'co_dien' => ['name' => 'BỘ PHẬN CƠ ĐIỆN', 'yeucau' => 33],
    'chuyen_may' => ['name' => 'BỘ PHẬN CHUYỀN MAY', 'yeucau' => 39],
    'kcs' => ['name' => 'BỘ PHẬN KCS', 'yeucau' => 36],
    'ui_thanh_pham' => ['name' => 'BỘ PHẬN ỦI THÀNH PHẨM', 'yeucau' => 15],
    'hoan_thanh' => ['name' => 'BỘ PHẬN HOÀN THÀNH', 'yeucau' => 45]
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

// Hàm tính tổng điểm của một bộ phận cho một sản phẩm
function calculateDeptScore($connect, $id_sanxuat, $dept) {
    $sql = "SELECT SUM(dg.diem_danhgia) as total_score, COUNT(tc.id) as total_criteria
            FROM tieuchi_dept tc
            LEFT JOIN danhgia_tieuchi dg ON tc.id = dg.id_tieuchi AND dg.id_sanxuat = ?
            WHERE tc.dept = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id_sanxuat, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    return [
        'score' => $data['total_score'] ?? 0,
        'total_criteria' => $data['total_criteria'] ?? 0,
        'max_score' => $data['total_criteria'] * 3 // Giả sử điểm tối đa cho mỗi tiêu chí là 3
    ];
}

// Lấy danh sách các Mã hàng đã hoàn thành trong tháng
$query = "SELECT kh.* 
          FROM khsanxuat kh
          JOIN dept_status ds ON kh.stt = ds.id_sanxuat
          WHERE MONTH(kh.ngayin) = ? AND YEAR(kh.ngayin) = ?
          AND ds.dept = ? AND ds.completed = 1
          ORDER BY kh.ngayin ASC";

$stmt = $connect->prepare($query);
$stmt->bind_param("iis", $month, $year, $dept);
$stmt->execute();
$result = $stmt->get_result();
$completed_products = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Tính điểm trung bình cho bộ phận
$total_score = 0;
$total_max_score = 0;
$product_scores = [];

foreach ($completed_products as $product) {
    $score_data = calculateDeptScore($connect, $product['stt'], $dept);
    $score_percent = $score_data['max_score'] > 0 ? ($score_data['score'] / $score_data['max_score']) * 100 : 0;
    
    $product_scores[] = [
        'stt' => $product['stt'],
        'xuong' => $product['xuong'],
        'line' => $product['line1'],
        'po' => $product['po'],
        'style' => $product['style'],
        'qty' => $product['qty'],
        'ngayin' => $product['ngayin'],
        'ngayout' => $product['ngayout'],
        'score' => $score_data['score'],
        'max_score' => $score_data['max_score'],
        'percent' => $score_percent
    ];
    
    $total_score += $score_data['score'];
    $total_max_score += $score_data['max_score'];
}

$avg_percent = $total_max_score > 0 ? ($total_score / $total_max_score) * 100 : 0;
$dept_display_name = $dept_names[$dept] ?? 'Không xác định';
$dept_color = $dept_colors[$dept] ?? '#333333';
$dept_yeucau = $departments_info[$dept]['yeucau'] ?? 0;

// Lấy danh sách các tháng có dữ liệu
$months_query = "SELECT DISTINCT MONTH(ngayin) as month, YEAR(ngayin) as year 
                FROM khsanxuat 
                ORDER BY year DESC, month DESC";
$months_result = mysqli_query($connect, $months_query);
$available_months = mysqli_fetch_all($months_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê Điểm Trung Bình - <?php echo $dept_display_name; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* CSS từ danhgia_hethong.php để tối ưu hiển thị trên điện thoại */
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
            display: flex;
            justify-content: center;
            width: 100%;
        }
        
        .navbar-center h1 {
            font-size: 24px;
            margin: 0;
            text-align: center;
            color: white;
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
            background-color: #003366;
            color: white;
        }
        
        /* Responsive cho điện thoại */
        @media screen and (max-width: 768px) {
            .navbar {
                padding: 10px;
                flex-wrap: nowrap;
                justify-content: flex-start;
                align-items: center;
            }
            
            .navbar-left {
                flex: 0 0 auto;
                margin-right: 10px;
            }
            
            .navbar-left img {
                width: 35px !important;
                height: auto !important;
                vertical-align: middle;
            }
            
            .navbar-center {
                order: 0;
                flex: 1 1 auto;
                margin-top: 0;
                display: flex;
                justify-content: flex-start;
            }
            
            .navbar-center h1 {
                font-size: 16px !important;
                white-space: normal;
                line-height: 1.2;
                text-align: left;
                margin: 0;
            }
            
            .container {
                padding: 10px;
                margin: 10px auto;
            }
            
            /* Làm cho bảng có thể cuộn ngang */
            table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .evaluation-table th, .evaluation-table td {
                padding: 6px 4px;
                font-size: 13px;
            }
        }
        
        /* Điều chỉnh thêm cho màn hình rất nhỏ */
        @media screen and (max-width: 480px) {
            .navbar-center h1 {
                font-size: 14px !important;
            }
            
            .navbar-left img {
                width: 30px !important;
            }
            
            .evaluation-table th, .evaluation-table td {
                padding: 4px 2px;
                font-size: 12px;
            }
            
            .btn-back {
                padding: 6px 12px;
                font-size: 13px;
            }
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
            color: <?php echo $dept_color; ?>;
            margin-top: 0;
        }
        
        .summary-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
            border-left: 5px solid <?php echo $dept_color; ?>;
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
        
        .progress-container {
            height: 10px;
            background-color: #e5e7eb;
            border-radius: 5px;
            margin-top: 15px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s ease;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .products-table th, .products-table td {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: left;
        }
        
        .products-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #1f2937;
        }
        
        .products-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .products-table tr:hover {
            background-color: #f0f7ff;
        }
        
        .score-cell {
            text-align: center;
            font-weight: bold;
        }
        
        .percent-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
            font-weight: bold;
            text-align: center;
            min-width: 60px;
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
        
        .filter-form button {
            padding: 8px 16px;
            background-color: #1e40af;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-form button:hover {
            background-color: #1c3879;
        }
    </style>
</head>
<body>
    <!-- Thanh điều hướng -->
    <div class="navbar">
        <div class="navbar-left">
            <a href="index.php"><img width="45px" src="img/logoht.png" /></a>
        </div>
        <div class="navbar-center" style="display: flex; justify-content: center; width: 100%;">
            <h1 style="font-size: 35px; margin: 0;">ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT NHÀ MÁY</h1>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>THỐNG KÊ ĐIỂM TRUNG BÌNH THEO BỘ PHẬN</h1>
            <h2><?php echo $dept_display_name; ?> - Tháng <?php echo $month; ?>/<?php echo $year; ?></h2>
        </div>
        
        <!-- Form lọc -->
        <form class="filter-form" method="GET" action="">
            <input type="hidden" name="dept" value="<?php echo $dept; ?>">
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
            <div class="summary-title">Tổng Kết Điểm Trung Bình</div>
            <div class="summary-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($completed_products); ?></div>
                    <div class="stat-label">Mã hàng đã hoàn thành</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($total_score, 0); ?>/<?php echo number_format($total_max_score, 0); ?></div>
                    <div class="stat-label">Tổng điểm đạt được</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($avg_percent, 1); ?>%</div>
                    <div class="stat-label">Tỷ lệ Điểm đánh giá</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $dept_yeucau; ?></div>
                    <div class="stat-label">Điểm yêu cầu</div>
                </div>
            </div>
            <div class="progress-container">
                <div class="progress-bar" style="width: <?php echo $avg_percent; ?>%; background-color: <?php echo $dept_color; ?>;"></div>
            </div>
        </div>
        
        <!-- Bảng chi tiết sản phẩm -->
        <?php if (count($completed_products) > 0): ?>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Xưởng</th>
                        <th>LINE</th>
                        <th>P/o no.</th>
                        <th>Style</th>
                        <th>Qty</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Điểm Đạt Được</th>
                        <th>Tỷ Lệ Điểm Đạt Được</th>
                        <th>Chi Tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($product_scores as $index => $product): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($product['xuong'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($product['line']); ?></td>
                            <td><?php echo htmlspecialchars($product['po']); ?></td>
                            <td><?php echo htmlspecialchars($product['style']); ?></td>
                            <td><?php echo htmlspecialchars($product['qty'] ?? ''); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($product['ngayin'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($product['ngayout'])); ?></td>
                            <td class="score-cell"><?php echo $product['score']; ?>/<?php echo $product['max_score']; ?></td>
                            <td class="score-cell">
                                <?php 
                                $percent = $product['percent'];
                                $color = '#ef4444'; // Đỏ cho < 50%
                                if ($percent >= 80) {
                                    $color = '#10b981'; // Xanh lá cho >= 80%
                                } elseif ($percent >= 50) {
                                    $color = '#f59e0b'; // Cam cho >= 50%
                                }
                                ?>
                                <span class="percent-badge" style="background-color: <?php echo $color; ?>">
                                    <?php echo number_format($percent, 1); ?>%
                                </span>
                            </td>
                            <td class="score-cell">
                                <a href="indexdept.php?dept=<?php echo $dept; ?>&id=<?php echo $product['stt']; ?>" class="btn-view">Xem</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; background-color: #f9fafb; border-radius: 8px;">
                <p style="font-size: 18px; color: #6b7280;">Không có sản phẩm nào đã hoàn thành trong tháng <?php echo $month; ?>/<?php echo $year; ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 