<?php
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Kết nối database
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database");
}

// Khởi tạo phiên làm việc nếu chưa có
session_start();

// Lấy thông tin từ URL
$xuong = isset($_GET['xuong']) ? $_GET['xuong'] : '';
$id_sanxuat = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Thêm biến cho tháng và năm
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

if (empty($xuong)) {
    die("Thiếu thông tin Xưởng");
}

// Lấy thông tin xưởng và mã hàng
$factory_info = [];
$product_info = [];

if ($id_sanxuat > 0) {
    // Lấy thông tin mã hàng cụ thể
    $sql_product = "SELECT * FROM khsanxuat WHERE stt = ?";
    $stmt_product = $connect->prepare($sql_product);
    $stmt_product->bind_param("i", $id_sanxuat);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    
    if ($result_product->num_rows > 0) {
        $product_info = $result_product->fetch_assoc();
    }
}

// Lấy danh sách các mã hàng của xưởng
$products = [];
// Cập nhật câu truy vấn SQL để lọc theo tháng và năm
$sql_products = "SELECT stt, style, po, line1, qty, ngayin, ngayout FROM khsanxuat 
                WHERE xuong = ? AND MONTH(ngayin) = ? AND YEAR(ngayin) = ? 
                ORDER BY CAST(line1 AS UNSIGNED) ASC, ngayin ASC";
$stmt_products = $connect->prepare($sql_products);
$stmt_products->bind_param("sii", $xuong, $selected_month, $selected_year);
$stmt_products->execute();
$result_products = $stmt_products->get_result();

while ($row_product = $result_products->fetch_assoc()) {
    // Đếm tổng số file cho mã hàng này
    $sql_file_count = "SELECT COUNT(*) as count FROM dept_template_files WHERE id_khsanxuat = ?";
    $stmt_file_count = $connect->prepare($sql_file_count);
    $stmt_file_count->bind_param("i", $row_product['stt']);
    $stmt_file_count->execute();
    $result_file_count = $stmt_file_count->get_result();
    $file_count = $result_file_count->fetch_assoc()['count'];
    
    $row_product['file_count'] = $file_count;
    $products[] = $row_product;
}

// Thêm mảng chuyển đổi tên bộ phận
$dept_names = array(
    'kehoach' => 'Kế Hoạch',
    'cat' => 'Cắt',
    'ep_keo' => 'Ép Keo',
    'chuanbi_sanxuat_phong_kt' => 'Phòng Kỹ Thuật',
    'may' => 'May',
    'hoan_thanh' => 'Hoàn Thành',
    'co_dien' => 'Cơ Điện',
    'kcs' => 'KCS',
    'ui_thanh_pham' => 'Ủi Thành Phẩm',
    'chuyen_may' => 'Chuyền May',
    'kho' => 'Kho Nguyên, Phụ Liệu',
    'quan_ly_cl' => 'Quản Lý Chất Lượng',
    'quan_ly_sx' => 'Quản Lý Sản Xuất'
);

// Lấy danh sách các bộ phận có biểu mẫu
$departments = [];
$sql_depts = "SELECT DISTINCT dept FROM dept_templates";
$result_depts = $connect->query($sql_depts);

if ($result_depts && $result_depts->num_rows > 0) {
    while ($row_dept = $result_depts->fetch_assoc()) {
        $dept = $row_dept['dept'];
        $dept_name = $dept_names[$dept] ?? $dept;
        $departments[$dept] = $dept_name;
    }
}

// Lấy danh sách tất cả biểu mẫu
$all_templates = [];
$sql_templates = "SELECT * FROM dept_templates ORDER BY dept, id";
$result_templates = $connect->query($sql_templates);

if ($result_templates && $result_templates->num_rows > 0) {
    while ($row_template = $result_templates->fetch_assoc()) {
        $dept = $row_template['dept'];
        
        if (!isset($all_templates[$dept])) {
            $all_templates[$dept] = [];
        }
        
        $all_templates[$dept][] = $row_template;
    }
}

// Nếu có id_sanxuat, lấy thông tin chi tiết về biểu mẫu cho mã hàng đó
$product_templates = [];
if ($id_sanxuat > 0) {
    foreach ($departments as $dept_code => $dept_name) {
        $dept_templates = [];
        
        // Lấy tất cả biểu mẫu của bộ phận
        if (isset($all_templates[$dept_code])) {
            foreach ($all_templates[$dept_code] as $template) {
                $template_id = $template['id'];
                
                // Đếm số file của template cho mã hàng hiện tại
                $sql_count = "SELECT COUNT(*) as count FROM dept_template_files 
                            WHERE id_template = ? AND id_khsanxuat = ?";
                $stmt_count = $connect->prepare($sql_count);
                $stmt_count->bind_param("ii", $template_id, $id_sanxuat);
                $stmt_count->execute();
                $result_count = $stmt_count->get_result();
                $files_count = $result_count->fetch_assoc()['count'];
                
                $template['files_count'] = $files_count;
                $dept_templates[] = $template;
            }
        }
        
        if (!empty($dept_templates)) {
            $product_templates[$dept_code] = [
                'name' => $dept_name,
                'templates' => $dept_templates
            ];
        }
    }
    
    // Sắp xếp lại thứ tự các bộ phận theo yêu cầu
    $dept_order = [
        'kehoach' => 1,               // Kế Hoạch
        'chuanbi_sanxuat_phong_kt' => 2, // Kỹ Thuật
        'kho' => 3,                  // Nguyên Phụ Liệu
        'cat' => 4,                  // Khâu Cắt
        'may' => 5,                  // Khâu May
        'kcs' => 6,                 // KCS 
        'hoan_thanh' => 7,           // Khâu Hoàn Thành
        'ep_keo' => 8,               // Ép Keo 
        'co_dien' => 9,              // Cơ Điện 
        'chuyen_may' => 10,           // Chuyền May
        'ui_thanh_pham' => 11,       // Ủi Thành Phẩm 
        'quan_ly_cl' => 12,          // Quản Lý Chất Lượng
        'quan_ly_sx' => 13           // Quản Lý Sản Xuất 
    ];
    
    // Hàm so sánh để sắp xếp
    uksort($product_templates, function($a, $b) use ($dept_order) {
        $order_a = isset($dept_order[$a]) ? $dept_order[$a] : 999;
        $order_b = isset($dept_order[$b]) ? $dept_order[$b] : 999;
        return $order_a - $order_b;
    });
}

// Đếm tổng số biểu mẫu và file
$total_templates = 0;
$total_files = 0;

if ($id_sanxuat > 0) {
    // Đếm tổng số biểu mẫu có file cho mã hàng
    $sql_template_count = "SELECT COUNT(DISTINCT id_template) as count FROM dept_template_files WHERE id_khsanxuat = ?";
    $stmt_template_count = $connect->prepare($sql_template_count);
    $stmt_template_count->bind_param("i", $id_sanxuat);
    $stmt_template_count->execute();
    $result_template_count = $stmt_template_count->get_result();
    $templates_with_files = $result_template_count->fetch_assoc()['count'];
    
    // Đếm tổng số file cho mã hàng
    $sql_file_count = "SELECT COUNT(*) as count FROM dept_template_files WHERE id_khsanxuat = ?";
    $stmt_file_count = $connect->prepare($sql_file_count);
    $stmt_file_count->bind_param("i", $id_sanxuat);
    $stmt_file_count->execute();
    $result_file_count = $stmt_file_count->get_result();
    $total_files = $result_file_count->fetch_assoc()['count'];
    
    // Đếm tổng số biểu mẫu (kể cả không có file)
    foreach ($product_templates as $dept_templates) {
        $total_templates += count($dept_templates['templates']);
    }
}

// Lấy danh sách các tháng có dữ liệu để hiển thị bộ lọc
$months_query = "SELECT DISTINCT MONTH(ngayin) as month, YEAR(ngayin) as year 
               FROM khsanxuat 
               WHERE xuong = ?
               ORDER BY year DESC, month DESC";
$stmt_months = $connect->prepare($months_query);
$stmt_months->bind_param("s", $xuong);
$stmt_months->execute();
$result_months = $stmt_months->get_result();
$available_months = [];
while ($month_row = $result_months->fetch_assoc()) {
    $available_months[] = $month_row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biểu Mẫu Xưởng <?php echo htmlspecialchars($xuong); ?> - Tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .navbar {
            display: flex;
            background-color: #1a365d;
            color: white;
            padding: 10px 20px;
            align-items: center;
        }
        
        .navbar-left {
            margin-right: 20px;
        }
        
        .navbar-center {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        h1, h2, h3 {
            color: #1a365d;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #1a365d;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: bold;
            background-color: #1a365d;
            color: white;
        }
        
        .badge-info {
            background-color: #17a2b8;
        }
        
        .badge-success {
            background-color: #28a745;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }
        
        .badge-danger {
            background-color: #dc3545;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #1a365d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: #0d2240;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-info {
            background-color: #17a2b8;
        }
        
        .btn-info:hover {
            background-color: #138496;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .info-table th, .info-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        
        .info-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .info-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .accordion {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .accordion-header {
            background-color: #f8f9fa;
            padding: 15px;
            cursor: pointer;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .accordion-header:hover {
            background-color: #e9ecef;
        }
        
        .accordion-content {
            padding: 0;
            display: none;
        }
        
        .accordion-content.active {
            display: block;
        }
        
        .toggle-icon {
            transition: transform 0.3s;
        }
        
        .accordion-header.active .toggle-icon {
            transform: rotate(180deg);
        }
        
        .summary-card {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-item {
            flex: 1 1 200px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #1a365d;
            margin: 10px 0;
        }
        
        .summary-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .product-info {
            margin-bottom: 20px;
        }
        
        .product-info-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .product-info-item {
            flex: 1 1 200px;
        }
        
        .product-info-label {
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 5px;
            display: block;
        }
        
        .product-info-value {
            font-size: 16px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        
        .template-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .template-table th, .template-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .template-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .template-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .no-templates {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 4px;
            color: #6c757d;
        }
        
        /* Cải thiện cho điện thoại */
        @media only screen and (max-width: 767px) {
            .container {
                padding: 0 10px;
            }
            
            .navbar-center h1 {
                font-size: 18px !important;
            }
            
            /* Tăng kích thước nút cho dễ chạm */
            .btn, .btn-sm {
                padding: 10px 16px;
                font-size: 16px;
                margin-bottom: 5px;
                display: inline-block;
                width: auto;
            }
            
            /* Điều chỉnh kích thước bảng và chữ */
            .template-table,
            .info-table {
                font-size: 14px;
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            /* Thay đổi cách hiển thị bảng trên mobile */
            .info-table.mobile-responsive {
                display: block;
                width: 100%;
            }
            
            .info-table.mobile-responsive thead {
                display: none;
            }
            
            .info-table.mobile-responsive tbody,
            .info-table.mobile-responsive tr {
                display: block;
                width: 100%;
            }
            
            .info-table.mobile-responsive td {
                display: flex;
                padding: 8px 10px;
                text-align: left;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #eee;
            }
            
            .info-table.mobile-responsive td::before {
                content: attr(data-label);
                font-weight: bold;
                width: 40%;
                margin-right: 10px;
            }
            
            .info-table.mobile-responsive tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            
            /* Điều chỉnh card cho dễ đọc trên mobile */
            .summary-item {
                flex: 1 1 100%;
            }
            
            .product-info-item {
                flex: 1 1 100%;
            }
            
            .template-table th, .template-table td,
            .info-table th, .info-table td {
                padding: 8px 10px;
            }
            
            .badge {
                font-size: 12px;
                padding: 4px 8px;
            }
            
            /* Tối ưu hiển thị accordion trên mobile */
            .accordion-header {
                padding: 12px;
            }
            
            /* Mở rộng filter box cho mobile */
            .filter-box {
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .filter-box input, 
            .filter-box select {
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 16px; /* Tăng font size trên mobile */
            }
            
            /* Nút tìm kiếm trên mobile */
            .search-btn {
                padding: 10px;
                background-color: #1a365d;
                color: white;
                border: none;
                border-radius: 4px;
                width: 100%;
                font-size: 16px;
                margin-top: 5px;
            }
            
            .month-selector {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .month-selector select {
                width: 100%;
                padding: 10px;
                font-size: 16px;
            }
        }
        
        /* Thêm CSS cho bộ chọn tháng */
        .month-selector {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }
        
        .month-selector label {
            font-weight: bold;
            margin-right: 5px;
            font-size: 15px;
        }
        
        .month-selector select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: white;
            font-size: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .month-selector select:focus {
            outline: none;
            border-color: #1a365d;
            box-shadow: 0 0 0 3px rgba(26, 54, 93, 0.2);
        }
        
        .month-selector .btn {
            padding: 10px 18px;
        }
        
        /* Nâng cấp phần tìm kiếm */
        .filter-box {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        .filter-box input, 
        .filter-box select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            flex: 1;
            min-width: 200px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .filter-box input:focus,
        .filter-box select:focus {
            outline: none;
            border-color: #1a365d;
            box-shadow: 0 0 0 3px rgba(26, 54, 93, 0.2);
        }
        
        .filter-box input::placeholder {
            color: #aaa;
        }
        
        .search-btn {
            padding: 12px 20px;
            background-color: #1a365d;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            min-width: 140px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .search-btn:hover {
            background-color: #0d2240;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .search-btn i {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <!-- Thanh điều hướng -->
<?php
$header_config = [
    'title' => 'Biểu Mẫu Xưởng ' . htmlspecialchars($xuong) . ' - Tháng ' . $selected_month . '/' . $selected_year,
    'title_short' => 'Biểu mẫu',
    'logo_path' => 'img/logoht.png',
    'logo_link' => '/trangchu/',
    'show_search' => false,
    'show_mobile_menu' => true,
    'actions' => []
];
?>
<?php include 'components/header.php'; ?>
    
    <div class="container">
        <a href="index.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" class="back-link">
            &larr; Quay lại trang chủ
        </a>
        
        <div class="card">
            <h2>Thông tin Xưởng</h2>
            
            <!-- Thêm bộ chọn tháng -->
            <div class="month-selector">
                <form action="" method="get" id="monthForm">
                    <input type="hidden" name="xuong" value="<?php echo htmlspecialchars($xuong); ?>">
                    <?php if ($id_sanxuat > 0): ?>
                    <input type="hidden" name="id" value="<?php echo $id_sanxuat; ?>">
                    <?php endif; ?>
                    
                    <label for="month_select">Chọn tháng:</label>
                    <select id="month_select" name="month" onchange="document.getElementById('monthForm').submit();">
                        <?php foreach ($available_months as $month): ?>
                        <option value="<?php echo $month['month']; ?>" 
                                <?php if ($month['month'] == $selected_month && $month['year'] == $selected_year) echo 'selected'; ?>
                                data-year="<?php echo $month['year']; ?>">
                            Tháng <?php echo $month['month']; ?>/<?php echo $month['year']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="year" id="year_input" value="<?php echo $selected_year; ?>">
                </form>
            </div>
            
            <div class="summary-card">
                <div class="summary-item">
                    <div class="summary-value"><?php echo count($products); ?></div>
                    <div class="summary-label">Tổng số mã hàng</div>
                </div>
                
                <?php if ($id_sanxuat > 0): ?>
                <div class="summary-item">
                    <div class="summary-value"><?php echo $total_templates; ?></div>
                    <div class="summary-label">Số biểu mẫu</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo $total_files; ?></div>
                    <div class="summary-label">Số file đính kèm</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Nếu có id_sanxuat, hiển thị thông tin chi tiết mã hàng -->
        <?php if ($id_sanxuat > 0 && !empty($product_info)): ?>
        <div class="card">
            <h2>Thông tin chi tiết mã hàng</h2>
            <div class="product-info">
                <div class="product-info-header">
                    <div class="product-info-item">
                        <span class="product-info-label">Line</span>
                        <div class="product-info-value"><?php echo htmlspecialchars($product_info['line1']); ?></div>
                    </div>
                    <div class="product-info-item">
                        <span class="product-info-label">PO</span>
                        <div class="product-info-value"><?php echo htmlspecialchars($product_info['po']); ?></div>
                    </div>
                    <div class="product-info-item">
                        <span class="product-info-label">Mã hàng (Style)</span>
                        <div class="product-info-value"><?php echo htmlspecialchars($product_info['style']); ?></div>
                    </div>
                    <div class="product-info-item">
                        <span class="product-info-label">Số lượng (Qty)</span>
                        <div class="product-info-value"><?php echo number_format($product_info['qty']); ?></div>
                    </div>
                    <div class="product-info-item">
                        <span class="product-info-label">Xưởng</span>
                        <div class="product-info-value"><?php echo htmlspecialchars($product_info['xuong']); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($total_files > 0): ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="download_all_files.php?id=<?php echo $id_sanxuat; ?>&dept=all" class="btn btn-success">
                    <i class="fas fa-cloud-download-alt"></i> Tải xuống tất cả files (<?php echo $total_files; ?> files)
                </a>
            </div>
            <?php endif; ?>
            
            <h3 style="margin-top: 30px;">Danh sách biểu mẫu</h3>
            
            <?php if (empty($product_templates)): ?>
            <div class="no-templates">
                <p>Không có biểu mẫu nào cho mã hàng này.</p>
            </div>
            <?php else: ?>
                <?php foreach ($product_templates as $dept_code => $dept_data): ?>
                <div class="accordion">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span><?php echo htmlspecialchars($dept_data['name']); ?></span>
                        <div>
                            <span class="badge"><?php echo count($dept_data['templates']); ?> biểu mẫu</span>
                            <?php
                            $dept_files = 0;
                            foreach ($dept_data['templates'] as $template) {
                                $dept_files += $template['files_count'];
                            }
                            ?>
                            <span class="badge badge-info"><?php echo $dept_files; ?> files</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <table class="template-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>Tên Biểu Mẫu</th>
                                    <th>Mô tả</th>
                                    <th style="width: 100px;">Số Files</th>
                                    <th style="width: 150px;">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dept_data['templates'] as $template): ?>
                                <tr>
                                    <td><?php echo $template['id']; ?></td>
                                    <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                                    <td><?php echo htmlspecialchars($template['template_description']); ?></td>
                                    <td>
                                        <?php if ($template['files_count'] > 0): ?>
                                        <span class="badge badge-success"><?php echo $template['files_count']; ?> files</span>
                                        <?php else: ?>
                                        <span class="badge badge-warning">0 files</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="file_templates.php?id=<?php echo $id_sanxuat; ?>&dept=<?php echo $dept_code; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Quản lý Files
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Danh sách các mã hàng của xưởng -->
        <div class="card">
            <h2>Danh sách mã hàng của Xưởng <?php echo htmlspecialchars($xuong); ?> (Tháng <?php echo $selected_month; ?>/<?php echo $selected_year; ?>)</h2>
            
            <?php if (empty($products)): ?>
            <div class="no-templates">
                <p>Không có mã hàng nào cho xưởng này.</p>
            </div>
            <?php else: ?>
            <!-- Thêm filter box để lọc dữ liệu -->
            <div class="filter-box">
                <input type="text" id="searchInput" placeholder="Nhập từ khóa tìm kiếm..." class="form-control">
                <select id="filterField" class="form-control">
                    <option value="all">Tất cả trường</option>
                    <option value="line">Line</option>
                    <option value="po">PO</option>
                    <option value="style">Mã hàng</option>
                </select>
                <button class="search-btn" onclick="filterTable()"><i class="fas fa-search"></i> Tìm kiếm</button>
            </div>
            
            <table class="info-table mobile-responsive" id="productTable">
                <thead>
                    <tr>
                        <th>Line</th>
                        <th>PO</th>
                        <th>Mã hàng</th>
                        <th>Số lượng</th>
                        <th>Ngày Vào</th>
                        <th>Ngày Ra</th>
                        <th style="width: 100px;">Files</th>
                        <th style="width: 150px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td data-label="Line"><?php echo htmlspecialchars($product['line1']); ?></td>
                        <td data-label="PO"><?php echo htmlspecialchars($product['po']); ?></td>
                        <td data-label="Mã hàng"><?php echo htmlspecialchars($product['style']); ?></td>
                        <td data-label="Số lượng"><?php echo number_format($product['qty']); ?></td>
                        <td data-label="Ngày Vào"><?php echo date('d/m/Y', strtotime($product['ngayin'])); ?></td>
                        <td data-label="Ngày Ra"><?php echo date('d/m/Y', strtotime($product['ngayout'])); ?></td>
                        <td data-label="Files">
                            <?php if ($product['file_count'] > 0): ?>
                            <span class="badge badge-success"><?php echo $product['file_count']; ?> files</span>
                            <?php else: ?>
                            <span class="badge badge-warning">0 files</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Thao tác">
                            <a href="factory_templates.php?xuong=<?php echo urlencode($xuong); ?>&id=<?php echo $product['stt']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Xem biểu mẫu
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function toggleAccordion(element) {
            const accordionContent = element.nextElementSibling;
            const isActive = element.classList.contains('active');
            
            // Đóng tất cả các accordion trước
            document.querySelectorAll('.accordion-header').forEach(header => {
                header.classList.remove('active');
                header.nextElementSibling.classList.remove('active');
            });
            
            // Mở/đóng accordion hiện tại
            if (!isActive) {
                element.classList.add('active');
                accordionContent.classList.add('active');
            }
        }
        
        // Mở accordion đầu tiên mặc định
        document.addEventListener('DOMContentLoaded', function() {
            const firstAccordion = document.querySelector('.accordion-header');
            if (firstAccordion) {
                toggleAccordion(firstAccordion);
            }
        });
        
        // Thêm function tìm kiếm và lọc bảng
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const filterField = document.getElementById('filterField').value;
            const table = document.getElementById('productTable');
            const tr = table.getElementsByTagName('tr');
            
            let colIndex = -1;
            switch(filterField) {
                case 'line': colIndex = 0; break;
                case 'po': colIndex = 1; break;
                case 'style': colIndex = 2; break;
                default: colIndex = -1; // all fields
            }
            
            // Loop through all table rows, and hide those who don't match the search query
            for (let i = 1; i < tr.length; i++) {
                let found = false;
                
                if (colIndex >= 0) {
                    // Search specific column
                    const td = tr[i].getElementsByTagName('td')[colIndex];
                    if (td) {
                        const txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                        }
                    }
                } else {
                    // Search all columns
                    const tds = tr[i].getElementsByTagName('td');
                    for (let j = 0; j < tds.length; j++) {
                        const td = tds[j];
                        if (td) {
                            const txtValue = td.textContent || td.innerText;
                            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                found = true;
                                break;
                            }
                        }
                    }
                }
                
                if (found) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
        
        // Tự động tìm kiếm khi nhập
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', filterTable);
            }
        });
        
        // Thêm script để đồng bộ year từ select option vào hidden input
        document.addEventListener('DOMContentLoaded', function() {
            const monthSelect = document.getElementById('month_select');
            const yearInput = document.getElementById('year_input');
            
            if (monthSelect && yearInput) {
                monthSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const year = selectedOption.getAttribute('data-year');
                    yearInput.value = year;
                });
            }
        });
    </script>
<script src="assets/js/header.js"></script>
</body>
</html>