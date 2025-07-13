<?php
// Kết nối cơ sở dữ liệu sử dụng mysqli
require "contdb.php"; // Đảm bảo rằng bạn đã kết nối với cơ sở dữ liệu qua contdb.php

/**
 * Kiểm tra trạng thái hoàn thành của một bộ phận
 */
function checkDeptStatus($connect, $id_sanxuat, $dept)
{
    $sql = "SELECT completed FROM dept_status WHERE id_sanxuat = ? AND dept = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $id_sanxuat, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    $status = $result->fetch_assoc();

    return (!$status || $status['completed'] == 0) ? false : true;
}

/**
 * Lấy ngày hạn xử lý thấp nhất của tiêu chí cho một sản phẩm và bộ phận cụ thể
 * @param mysqli $connect Kết nối CSDL
 * @param int $id_sanxuat ID của sản phẩm
 * @param string $dept Mã bộ phận
 * @return string|null Ngày hạn xử lý thấp nhất dạng Y-m-d hoặc null nếu không có
 */
function getEarliestDeadline($connect, $id_sanxuat, $dept)
{
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

// Lấy tháng được chọn từ dropdown
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Lấy danh sách các tháng có dữ liệu
$months_query = "SELECT DISTINCT MONTH(ngayin) as month, YEAR(ngayin) as year 
                FROM khsanxuat 
                ORDER BY year DESC, month DESC";
$months_result = mysqli_query($connect, $months_query);
$available_months = mysqli_fetch_all($months_result, MYSQLI_ASSOC);

// Xử lý tìm kiếm
$search_condition = "";
$search_params = [];

if (isset($_GET['search_value']) && !empty($_GET['search_value'])) {
    $search_value = $_GET['search_value'];
    $search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'xuong';

    // Kiểm tra nếu tìm kiếm là "all" (không phân biệt hoa thường)
    if (strtolower($search_value) !== "all") {
        // Xác định trường cần tìm kiếm
        switch ($search_type) {
            case 'xuong':
                $search_condition = " AND xuong LIKE ?";
                break;
            case 'line':
                $search_condition = " AND line1 LIKE ?";
                break;
            case 'po':
                $search_condition = " AND po LIKE ?";
                break;
            case 'style':
                $search_condition = " AND style LIKE ?";
                break;
            case 'model':
                $search_condition = " AND model LIKE ?";
                break;
        }
        $search_params[] = "%$search_value%";
    }
}

// Xây dựng câu truy vấn SQL
$sql = "SELECT * FROM khsanxuat WHERE MONTH(ngayin) = ? AND YEAR(ngayin) = ?$search_condition ORDER BY xuong ASC, CAST(line1 AS UNSIGNED) ASC, ngayin ASC";
$stmt = $connect->prepare($sql);

// Bind các tham số
if (!empty($search_params)) {
    $stmt->bind_param("iis", $selected_month, $selected_year, $search_params[0]);
} else {
    $stmt->bind_param("ii", $selected_month, $selected_year);
}

$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$total_tasks = count($rows);

// Tính toán thống kê
$completed_tasks = 0;
$completed_kehoach = 0;
$completed_kho = 0;

foreach ($rows as $row) {
    $kehoach_done = checkDeptStatus($connect, $row['stt'], 'kehoach');
    $kho_done = checkDeptStatus($connect, $row['stt'], 'kho');

    if ($kehoach_done && $kho_done) {
        $completed_tasks++;
    }
    if ($kehoach_done) {
        $completed_kehoach++;
    }
    if ($kho_done) {
        $completed_kho++;
    }
}

$completion_percent = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
$kehoach_percent = $total_tasks > 0 ? round(($completed_kehoach / $total_tasks) * 100) : 0;
$kho_percent = $total_tasks > 0 ? round(($completed_kho / $total_tasks) * 100) : 0;

// Tính toán thống kê cho các bộ phận
$dept_stats = [
    'Kế Hoạch' => $kehoach_percent,
    'Chuẩn Bị SX' => $completion_percent - $kehoach_percent,
    'Kho' => $kho_percent,
    'Cắt' => 0,
    'Ép Keo' => 0,
    'Cơ Điện' => 0,
    'Chuyền May' => 0,
    'KCS' => 0,
    'Ủi TP' => 0,
    'Hoàn Thành' => 0
];

// Lấy danh sách các bộ phận
$departments = [
    'cat' => 'Cắt',
    'ep_keo' => 'Ép Keo',
    'co_dien' => 'Cơ Điện',
    'chuyen_may' => 'Chuyền May',
    'kcs' => 'KCS',
    'ui_thanh_pham' => 'Ủi TP',
    'hoan_thanh' => 'Hoàn Thành'
];

foreach ($departments as $dept_code => $dept_name) {
    $dept_stats[$dept_name] = 0;
}

// Tính toán thống kê cho các bộ phận
foreach ($rows as $row) {
    foreach ($departments as $dept_code => $dept_name) {
        $dept_stats[$dept_name] += checkDeptStatus($connect, $row['stt'], $dept_code) ? 1 : 0;
    }
}

// Tính toán phần trăm thống kê cho các bộ phận
foreach ($dept_stats as $dept => $completed) {
    $dept_stats[$dept] = $total_tasks > 0 ? round(($completed / $total_tasks) * 100) : 0;
}

// Hàm kiểm tra xem Style có tiêu chí chưa hoàn thành không
function hasIncompleteCriteria($connect, $style, $stt = null)
{
    $sql = "SELECT COUNT(*) as count
            FROM khsanxuat kh
            JOIN danhgia_tieuchi dg ON kh.stt = dg.id_sanxuat
            JOIN tieuchi_dept tc ON dg.id_tieuchi = tc.id
            WHERE kh.style = ?";

    if ($stt !== null) {
        $sql .= " AND kh.stt = ?";
    }

    $sql .= " AND (dg.diem_danhgia = 0 OR dg.diem_danhgia IS NULL)";

    $stmt = $connect->prepare($sql);

    if ($stt !== null) {
        $stmt->bind_param("si", $style, $stt);
    } else {
        $stmt->bind_param("s", $style);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return ($row['count'] > 0);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT NHÀ MÁY</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="styleindex.css">
    <!-- Thêm thư viện Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    /* Điều chỉnh body và html để tránh tràn nội dung */
    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
    }
    
    /* Điều chỉnh container để tăng bề ngang */
    .container {
        width: 100%;
        max-width: 100%;
        padding: 15px;
        margin: 0;
        box-sizing: border-box;
        overflow-x: visible; /* Thay đổi từ hidden sang visible để cho phép nội dung mở rộng */
    }
    
    /* Thiết lập cơ bản cho navbar */
    .navbar {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        position: sticky;
        top: 0;
        z-index: 50;
        width: 100%;
        box-sizing: border-box;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Phần logo */
    .navbar-left {
        display: flex;
        align-items: center;
        margin-right: 1rem;
    }

    .navbar-left img {
        width: 45px;
        height: auto;
        transition: transform 0.2s ease;
        border-radius: 6px;
    }

    .navbar-left img:hover {
        transform: scale(1.05);
    }

    /* Phần tiêu đề */
    .navbar-center {
        flex: 1;
        display: flex;
        justify-content: center;
    }

    .navbar-center h1 {
        color: white;
        font-size: 1.5rem;
        margin: 0;
        text-align: center;
        font-weight: 600;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    /* Phần các nút bên phải */
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Ô tìm kiếm */
    .search-container {
        position: relative;
    }

    .search-form {
        display: flex;
        align-items: center;
    }

    .search-form input[type="text"] {
        padding: 0.5rem 0.75rem;
        border: none;
        border-radius: 4px;
        font-size: 0.875rem;
        width: 160px;
        background-color: rgba(255, 255, 255, 0.9);
        transition: all 0.2s ease;
        outline: none;
    }

    .search-form input[type="text"]:focus {
        background-color: white;
        box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.5);
    }

    .search-button {
        background: none;
        border: none;
        cursor: pointer;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem;
        opacity: 0.9;
        transition: opacity 0.2s ease;
    }

    .search-button:hover {
        opacity: 1;
    }

    /* Nút chức năng trong navbar */
    .navbar-right a {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .navbar-right img {
        width: 40px;
        height: auto;
        transition: transform 0.2s ease;
    }

    .navbar-right img:hover {
        transform: scale(1.1);
    }

    /* Nút toggle menu */
    .navbar-toggle {
        display: none;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 8px;
        color: white;
        z-index: 20;
        transition: all 0.3s ease;
    }

    .navbar-toggle:focus {
        outline: none;
    }

    .hamburger-icon {
        display: block;
        position: relative;
        width: 24px;
        height: 18px;
    }

    .hamburger-icon span {
        display: block;
        position: absolute;
        height: 2px;
        width: 100%;
        background: white;
        border-radius: 2px;
        opacity: 1;
        left: 0;
        transform: rotate(0deg);
        transition: .25s ease-in-out;
    }

    .hamburger-icon span:nth-child(1) {
        top: 0px;
    }

    .hamburger-icon span:nth-child(2) {
        top: 8px;
    }

    .hamburger-icon span:nth-child(3) {
        top: 16px;
    }
    
    /* Thêm media query để đảm bảo vị trí hamburger icon */
    @media screen and (max-width: 429px) {
        .hamburger-icon {
            position: absolute;
            right: 15px; /* Điều chỉnh khoảng cách từ bên phải */
            top: 15px; /* Điều chỉnh vị trí từ trên xuống nếu cần */
        }
    }
    
    /* Khi menu đang mở */
    .is-active span:nth-child(1) {
        top: 8px;
        transform: rotate(45deg);
    }

    .is-active span:nth-child(2) {
        opacity: 0;
        width: 0%;
    }

    .is-active span:nth-child(3) {
        top: 8px;
        transform: rotate(-45deg);
    }

    /* Container cho dropdown menu */
    .navbar-dropdown {
        display: none;
        position: fixed;
        top: -100%;
        left: 0;
        right: 0;
        background: #002952;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        padding: 1rem;
        z-index: 40;
        transition: top 0.3s ease;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        overflow-y: auto;
        max-height: 80vh;
    }

    .navbar-dropdown.is-open {
        top: 56px; /* Điều chỉnh theo chiều cao của navbar */
    }

    /* Phần tìm kiếm trong dropdown */
    .dropdown-search-container {
        margin-bottom: 1rem;
        background: rgba(255, 255, 255, 0.05);
        padding: 0.75rem;
        border-radius: 8px;
    }

    .dropdown-search-container .search-form {
        width: 100%;
    }

    .dropdown-search-container .search-form input[type="text"] {
        width: 100% !important;
        padding: 0.75rem;
        border-radius: 4px;
        background-color: rgba(255, 255, 255, 0.9);
        font-size: 16px;
    }

    /* Style cho các nút điều hướng trong dropdown */
    .dropdown-nav-items {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }

    .dropdown-nav-item {
        display: flex;
        align-items: center;
        padding: 0.875rem;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 8px;
        color: white;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .dropdown-nav-item:hover, .dropdown-nav-item:active {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    .dropdown-nav-item img {
        width: 24px;
        height: 24px;
        margin-right: 0.75rem;
    }

    /* Responsive cho desktop lớn */
    @media (min-width: 1200px) {
        .navbar {
            padding: 0.75rem 2.5rem;
        }
        
        .navbar-center h1 {
            font-size: 1.75rem;
        }
        
        .search-form input[type="text"] {
            width: 200px;
        }
    }

    /* Responsive cho desktop */
    @media (min-width: 992px) and (max-width: 1199.98px) {
        .navbar {
            padding: 0.75rem 1.5rem;
        }
        
        .navbar-center h1 {
            font-size: 1.5rem;
        }
    }

    /* Responsive cho tablet */
    @media (min-width: 768px) and (max-width: 991.98px) {
        .navbar {
            padding: 0.625rem 1rem;
        }
        
        .navbar-center h1 {
            font-size: 1.25rem;
        }
        
        .search-form input[type="text"] {
            width: 140px;
        }
        
        .navbar-right img {
            width: 35px;
        }
    }

    /* Responsive cho mobile */
    @media (max-width: 767.98px) {
        .navbar {
            padding: 0.5rem 0.75rem;
            justify-content: space-between;
        }
        
        .navbar-left {
            margin-right: 0.5rem;
        }
        
        .navbar-left img {
            width: 35px;
        }
        
        .navbar-center h1 {
            font-size: 1rem;
            text-align: left;
        }
        
        .navbar-right {
            display: none;
        }
        
        .navbar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .navbar-dropdown.is-open {
            display: block;
        }
        
        /* Đảm bảo grid vẫn hoạt động tốt trên điện thoại */
        .dropdown-nav-items {
            grid-template-columns: 1fr 1fr;
        }
        
        .dropdown-nav-item {
            padding: 0.75rem;
        }
        
        .dropdown-nav-item img {
            width: 20px;
            height: 20px;
        }
    }

    /* Responsive cho mobile nhỏ */
    @media (max-width: 479.98px) {
        .navbar-left img {
            width: 30px;
        }
        
        .navbar-center h1 {
            font-size: 0.875rem;
        }
        
        .dropdown-nav-items {
            grid-template-columns: 1fr;
        }
        
        .dropdown-nav-item {
            padding: 0.625rem;
        }
    }
    
    /* Điều chỉnh navbar để mở rộng toàn màn hình */
    .navbar {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        padding: 10px 15px;
        background-color: #003366 !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        position: sticky;
        top: 0;
        z-index: 1000;
        width: 100%;
        box-sizing: border-box;
    }
    
    .navbar-left {
        flex: 0 0 auto;
        margin-right: 15px;
        display: flex;
        align-items: center;
    }
    
    .navbar-center {
        flex: 1 1 auto;
        text-align: center;
    }
    
    .navbar-center h1 {
        color: white !important;
        font-size: 24px;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: flex-end;
    }
    
    /* Điều chỉnh ô tìm kiếm */
    .search-container {
        display: flex;
        align-items: center;
    }
    
    .search-form {
        display: flex;
        align-items: center;
    }
    
    .search-form input[type="text"] {
        padding: 8px 10px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        width: 140px;
    }
    
    .search-button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        color: white;
    }
    
    /* Responsive cho tablet */
    @media screen and (max-width: 992px) {
        .navbar-center h1 {
            font-size: 20px;
        }
        
        .search-form input[type="text"] {
            width: 120px;
        }
    }
    
    /* Responsive cho điện thoại */
    @media screen and (max-width: 768px) {
        .navbar {
            padding: 8px 10px;
        }
        
        .navbar-left img {
            width: 35px !important;
            height: auto !important;
            vertical-align: middle;
        }
        
        .navbar-center h1 {
            font-size: 16px !important;
            text-align: left;
        }
        
        .search-form input[type="text"] {
            width: 100px !important;
            font-size: 13px !important;
            padding: 6px 8px !important;
        }
        
        .navbar-right img {
            width: 28px !important;
            height: auto !important;
        }
    }
    
    /* Điện thoại nhỏ */
    @media screen and (max-width: 480px) {
        .navbar {
            padding: 5px 8px;
        }
        
        .navbar-left img {
            width: 28px !important;
        }
        
        .navbar-center h1 {
            font-size: 0.75rem;
        }
        
        .search-form input[type="text"] {
            width: 60px;
            padding: 0.25rem 0.375rem;
        }
        
        .search-form input[type="text"]::placeholder {
            opacity: 0.7;
            font-size: 0.675rem;
        }
    }
    
    /* Điều chỉnh bảng dữ liệu */
    .data-table-container {
        width: 100%;
        overflow-x: auto;
        margin-bottom: 20px;
        box-sizing: border-box;
    }
    
    .data-table {
        width: 100%;
        min-width: 1200px; /* Đặt chiều rộng tối thiểu cho bảng */
        margin-top: 20px;
    }
    
    /* Điều chỉnh các thẻ thống kê */
    .stats-container {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between; /* Thay đổi từ center sang space-between */
        gap: 20px; /* Tăng khoảng cách giữa các thẻ */
        margin-bottom: 25px;
        padding: 10px;
        box-sizing: border-box;
    }
    
    .stat-card {
        flex: 1 1 350px; /* Tăng kích thước từ 300px lên 350px */
        max-width: 350px; /* Tăng kích thước từ 300px lên 350px */
        min-width: 300px; /* Tăng kích thước từ 250px lên 300px */
        margin: 0;
        box-sizing: border-box;
    }
    
    /* Điều chỉnh biểu đồ */
    .chart-container {
        width: 100%;
        min-height: 400px; /* Tăng chiều cao tối thiểu */
        box-sizing: border-box;
        padding: 20px;
        margin: 20px 0;
        overflow: visible; /* Cho phép nội dung mở rộng */
    }
    
    /* Điều chỉnh phần đánh giá */
    .evaluation-container {
        width: 100%;
        box-sizing: border-box;
        display: flex;
        flex-wrap: wrap;
        gap: 30px; /* Tăng khoảng cách */
        padding: 20px;
    }
    
    .best-performer, .worst-performer {
        flex: 1 1 350px; /* Tăng kích thước từ 300px lên 350px */
        min-width: 300px; /* Tăng kích thước từ 250px lên 300px */
        box-sizing: border-box;
    }
    
    /* Responsive cho màn hình lớn */
    @media screen and (min-width: 1400px) {
        .container {
            padding: 20px;
        }
        
        .data-table {
            min-width: 1400px; /* Tăng chiều rộng tối thiểu trên màn hình lớn */
        }
        
        .stat-card {
            flex: 1 1 400px; /* Tăng kích thước trên màn hình lớn */
            max-width: 400px;
        }
        
        .chart-container {
            min-height: 500px; /* Tăng chiều cao trên màn hình lớn */
        }
    }
    
    /* Responsive cho điện thoại */
    @media screen and (max-width: 768px) {
        .navbar {
            padding: 10px;
        }
        
        .navbar-center {
            order: 3;
            flex: 1 0 100%;
            margin-top: 10px;
        }
        
        .navbar-center h1 {
            font-size: 18px !important;
        }
        
        .navbar-right {
            flex: 1;
            justify-content: flex-end;
        }
        
        .search-form input[type="text"] {
            width: 120px !important;
            font-size: 13px !important;
            padding: 6px 8px !important;
        }
        
        .navbar-right img {
            width: 32px !important;
            height: auto !important;
        }
        
        /* Điều chỉnh thẻ thống kê trên mobile */
        .stats-container {
            flex-direction: column;
            align-items: center;
        }
        
        .stat-card {
            width: 100%;
            max-width: 100%;
            margin-bottom: 10px;
        }
        
        /* Điều chỉnh phần đánh giá trên mobile */
        .evaluation-container {
            flex-direction: column;
        }
        
        .best-performer, .worst-performer {
            width: 100%;
            max-width: 100%;
        }
    }
    
    @media screen and (max-width: 480px) {
        .container {
            padding: 10px;
        }
        
        .navbar-center h1 {
            font-size: 16px !important;
        }
        
        .search-form input[type="text"] {
            width: 100px !important;
            font-size: 12px !important;
            padding: 5px !important;
        }
        
        .navbar-right img {
            width: 28px !important;
        }
        
        .navbar-left img {
            width: 32px !important;
        }
    }

    .stats-container {
        margin-bottom: 25px;
        padding: 10px;
        display: flex;
        justify-content: center;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        width: 300px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
        transition: transform 0.2s;
    }

    .container {
        background-color:rgb(255, 255, 255);
        width: 98%;
        max-width: none;
        margin: 0 auto;
        padding: 20px;
    }

    .data-table {
        width: 100%;
        margin-top: 20px;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
    }

    .stat-card.total::before { background: #3b82f6; }
    .stat-card.completed::before { background: #10b981; }
    .stat-card.kehoach::before { background: #f59e0b; }
    .stat-card.kho::before { background: #6366f1; }

    .stat-title {
        color: #64748b;
        font-size: 0.875rem;
        margin-bottom: 8px;
    }

    .stat-value {
        color: #1e293b;
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .stat-percent {
        color: #64748b;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .progress-bar {
        width: 100%;
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        margin-top: 8px;
        overflow: hidden;
    }

    .progress-value {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }

    .total .progress-value { background: #3b82f6; }
    .completed .progress-value { background: #10b981; }
    .kehoach .progress-value { background: #f59e0b; }
    .kho .progress-value { background: #6366f1; }

    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin: 20px 0 120px 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 100%;
        height: auto;
        min-height: 400px;
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
    }

    .evaluation-container {
        position: relative;
        margin-top: 20px;
        padding: 20px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        background: white;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .best-performer, .worst-performer {
        flex: 1;
        min-width: 300px;
        padding: 15px;
        border-radius: 8px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .best-performer {
        border-left: 4px solid #10b981;
        background-color: rgba(16, 185, 129, 0.05);
    }

    .worst-performer {
        border-left: 4px solid #ef4444;
        background-color: rgba(239, 68, 68, 0.05);
    }

    .best-performer:hover, .worst-performer:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .eval-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 20px;
        color: white;
        margin-right: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Thêm màu nền cho biểu tượng */
    .eval-icon.success {
        background-color: #10b981; /* Màu xanh lá */
    }
    
    .eval-icon.warning {
        background-color: #ef4444; /* Màu đỏ */
    }

    .eval-content h4 {
        margin: 0 0 10px 0;
        color: #1e293b;
        font-size: 16px;
        font-weight: bold;
    }

    .eval-content p {
        margin: 0 0 5px 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
    }

    .alert-message {
        margin-top: 10px;
        padding: 8px 12px;
        background: #fff5f5;
        border: 1px solid #feb2b2;
        border-radius: 6px;
        color: #e53e3e;
        font-size: 13px;
    }

    /* Style cho liên kết Style */
    .style-link {
        color: #1e40af;
        text-decoration: none;
        font-weight: bold;
        transition: color 0.2s;
    }

    .style-link:hover {
        color: #2563eb;
        text-decoration: underline;
    }

    /* Thêm biểu tượng cảnh báo cho Style có tiêu chí chưa hoàn thành */
    .style-link.has-incomplete::after {
        content: " ⚠️";
        color: #F44336;
    }

    /* CSS cho responsive trên điện thoại */
    @media screen and (max-width: 428px) {
        body {
            font-size: 14px;
            padding: 0;
            margin: 0;
        }
        
        .container {
            width: 100%;
            padding: 10px;
            margin: 0;
            overflow-x: hidden;
        }
        
        h1, h2, h3 {
            font-size: 18px;
            text-align: center;
        }
        
        /* Điều chỉnh thanh tìm kiếm */
        .search-container {
            flex-direction: column;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .search-container input, 
        .search-container select, 
        .search-container button {
            width: 100%;
            margin: 5px 0;
            padding: 10px;
        }
        
        /* Điều chỉnh thẻ thống kê */
        .stats-container {
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .stat-card {
            width: 100%;
            margin-bottom: 10px;
        }
        
        /* Điều chỉnh biểu đồ */
        .chart-container {
            height: 300px;
            margin-bottom: 20px;
        }
        
        /* Làm cho bảng có thể cuộn ngang */
        .data-table-container {
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            margin: 0 !important;
            padding: 0 !important;
            border-radius: 0;
        }
        
        .data-table {
            min-width: 800px; /* Đảm bảo bảng không bị co lại quá nhỏ */
        }
        
        .data-table th, 
        .data-table td {
            padding: 8px 5px;
            font-size: 12px;
        }
        
        /* Điều chỉnh dropdown tháng */
        #month-selector {
            width: 100%;
            margin: 10px 0;
        }
        
        /* Điều chỉnh nút */
        button, 
        input[type="submit"] {
            padding: 10px;
            font-size: 14px;
            width: 100%;
            margin: 5px 0;
        }
        
        /* Thêm hướng dẫn cuộn ngang */
        .scroll-hint {
            display: block;
            text-align: center;
            background-color: #fff9c4;
            color: #333;
            font-weight: bold;
            padding: 8px;
            margin: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            left: 0;
            width: 100%;
            z-index: 2;
        }
    }
    
    /* CSS cho màn hình xoay ngang */
    @media screen and (max-width: 926px) and (orientation: landscape) {
        .stats-container {
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .stat-card {
            width: 45%;
            margin: 5px;
        }
        
        .chart-container {
            height: 200px;
        }
    }

    /* Sửa lỗi bảng dữ liệu bị tràn bề ngang */
    .data-table-container {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 20px;
        position: relative;
        box-sizing: border-box;
    }
    
    /* Thêm CSS cho bảng */
    .data-table {
        min-width: 1000px; /* Đủ rộng để hiển thị tất cả cột */
        border-collapse: collapse;
        table-layout: fixed; /* Giúp kiểm soát chiều rộng tốt hơn */
    }
    
    /* Đảm bảo văn bản trong các ô không bị tràn */
    .data-table th,
    .data-table td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Đảm bảo tất cả tiêu đề cột có cùng màu nền */
    .data-table thead th {
        background-color: rgb(0, 91, 136) !important;
        color: white !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 50 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
    }

    /* Chỉ cố định cột checkbox khi cuộn ngang */
    .data-table th:first-child,
    .data-table td:first-child {
        position: sticky !important;
        left: 0 !important;
        z-index: 100 !important;
    }

    /* Đảm bảo cột checkbox header có màu giống các tiêu đề khác */
    .data-table th:first-child {
        background-color: rgb(0, 91, 136) !important;
        z-index: 200 !important; /* Cao hơn để hiển thị đúng khi cố định cả ngang và dọc */
    }

    /* Đảm bảo các ô dữ liệu trong cột checkbox có màu nền trắng */
    .data-table td:first-child {
        background-color: white !important;
    }

    /* Bỏ khóa cuộn ngang cho cột STT và Xưởng */
    .data-table th:nth-child(2),
    .data-table td:nth-child(2),
    .data-table th:nth-child(3),
    .data-table td:nth-child(3) {
        position: static !important;
        left: auto !important;
    }

    /* Giữ màu sắc cho liên kết trong tiêu đề */
    .data-table th a {
        color: white !important;
        text-decoration: none !important;
    }

    .data-table th a:hover {
        text-decoration: underline !important;
    }

    /* CSS để cố định dòng tiêu đề khi cuộn dọc */
    .data-table-container {
        max-height: 70vh; /* Giới hạn chiều cao để tạo thanh cuộn dọc */
        overflow-y: auto; /* Cho phép cuộn dọc */
    }

    /* Cố định tiêu đề theo chiều dọc */
    .data-table thead tr {
        position: sticky !important;
        top: 0 !important;
        z-index: 100 !important;
    }

    .data-table thead th {
        position: sticky !important;
        top: 0 !important;
        background-color: rgb(0, 91, 136) !important;
        color: white !important;
        z-index: 100 !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }

    /* Đảm bảo cột checkbox header vẫn cố định theo cả hai chiều */
    .data-table th:first-child {
        position: sticky !important;
        left: 0 !important;
        top: 0 !important;
        z-index: 200 !important; /* Cao hơn các header khác */
        background-color: rgb(0, 91, 136) !important;
    }

    /* Đảm bảo các ô dữ liệu trong cột checkbox có màu nền */
    .data-table td:first-child {
        position: sticky !important;
        left: 0 !important;
        background-color: white !important;
        z-index: 90 !important;
        box-shadow: 2px 0 4px rgba(0,0,0,0.05) !important;
    }

    /* CSS cho responsive navbar - giữ nguyên cấu trúc và màu sắc */
    @media screen and (max-width: 768px) {
        /* Điều chỉnh navbar để hiển thị tốt hơn trên thiết bị di động */
        .navbar {
            flex-wrap: wrap;
            padding: 8px 5px;
        }
        
        .navbar-left {
            flex: 0 0 auto;
        }
        
        .navbar-center {
            flex: 1 0 100%;
            order: 3;
            margin-top: 8px;
        }
        
        .navbar-center h1 {
            font-size: 18px !important;
        }
        
        .navbar-right {
            flex: 1;
            justify-content: flex-end;
        }
        
        /* Điều chỉnh ô tìm kiếm */
        .search-container {
            position: static !important;
            top: 0 !important;
        }
        
        .search-form input[type="text"] {
            width: 120px !important;
            font-size: 13px !important;
        }
        
        /* Điều chỉnh kích thước biểu tượng */
        .navbar-right img {
            width: 32px !important;
            height: auto !important;
        }
    }
    
    @media screen and (max-width: 480px) {
        .navbar-center h1 {
            font-size: 16px !important;
        }
        
        .search-form input[type="text"] {
            width: 100px !important;
            font-size: 12px !important;
            padding: 4px !important;
        }
        
        .navbar-right img {
            width: 28px !important;
        }
        
        .navbar-left img {
            width: 36px !important;
        }
    }
    
    /* Thêm hiệu ứng hover cho các nút */
    .navbar-right a:hover img, .navbar-left a:hover img {
        opacity: 0.8;
        transform: scale(1.05);
        transition: all 0.2s ease;
    }
    
    /* Đảm bảo navbar luôn ở trên cùng khi cuộn */
    .navbar {
        position: sticky;
        top: 0;
        z-index: 1000;
        background-color: #1e293b !important; /* Màu xanh dương đậm */
        box-shadow: 0 2px 4px rgba(9, 13, 219, 0.2);
    }
    
    /* Điều chỉnh màu chữ tiêu đề để phù hợp với nền xanh */
    .navbar-center h1 {
        color: white !important;
    }
    
    /* Điều chỉnh màu sắc cho ô tìm kiếm */
    .search-form input[type="text"] {
        border: 1px solid #4d8bd9 !important;
        background-color: rgba(255, 255, 255, 0.9) !important;
    }
    
    /* Điều chỉnh màu cho biểu tượng tìm kiếm */
    .search-button i {
        color: white !important;
    }
    
    /* Thêm hiệu ứng hover cho các nút trên nền xanh */
    .navbar-right a:hover img, .navbar-left a:hover img {
        opacity: 0.9;
        transform: scale(1.05);
        transition: all 0.2s ease;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 5px;
    }
    
    /* Điều chỉnh màu cho các phần tử khác trong navbar nếu cần */
    .navbar-right, .navbar-left {
        color: white !important;
    }

    /* CSS cho navbar giống dept_statistics.php */
    .navbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding: 10px 15px;
        background-color: #003366 !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .navbar-left {
        flex: 0 0 auto;
        margin-right: 15px;
    }
    
    .navbar-center {
        flex: 1 1 auto;
        text-align: center;
    }
    
    .navbar-center h1 {
        color: white !important;
        font-size: 24px;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    /* Điều chỉnh ô tìm kiếm */
    .search-container {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        top: 0;
    }
    
    .search-form {
        display: flex;
        align-items: center;
    }
    
    .search-form input[type="text"] {
        padding: 8px 10px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        width: 140px;
        background-color: rgba(255, 255, 255, 0.9);
    }
    
    .search-button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        color: white;
    }
    
    /* Điều chỉnh container chính */
    .container {
        /* max-width: 1200px; */
        margin: 0 auto;
        padding: 15px;
        background-color: #ffffff;
    }
    
    /* Responsive cho điện thoại */
    @media screen and (max-width: 768px) {
        .navbar {
            padding: 10px;
        }
        
        .navbar-center {
            order: 3;
            flex: 1 0 100%;
            margin-top: 10px;
        }
        
        .navbar-center h1 {
            font-size: 18px !important;
        }
        
        .navbar-right {
            flex: 1;
            justify-content: flex-end;
        }
        
        .search-form input[type="text"] {
            width: 120px !important;
            font-size: 13px !important;
            padding: 6px 8px !important;
        }
        
        .navbar-right img {
            width: 32px !important;
            height: auto !important;
        }
        
        /* Điều chỉnh bảng dữ liệu */
        .data-table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }
        
        .data-table {
            min-width: 800px; /* Đảm bảo bảng không bị co lại quá nhỏ */
        }
        
        /* Điều chỉnh biểu đồ */
        .chart-container {
            padding: 15px;
            margin: 15px 0;
            min-height: 300px;
        }
        
        /* Điều chỉnh thẻ thống kê */
        .stats-container {
            flex-direction: column;
            align-items: center;
        }
        
        .stat-card {
            width: 100%;
            margin-bottom: 10px;
        }
    }
    
    @media screen and (max-width: 480px) {
        .navbar-center h1 {
            font-size: 16px !important;
        }
        
        .search-form input[type="text"] {
            width: 100px !important;
            font-size: 12px !important;
            padding: 5px !important;
        }
        
        .navbar-right img {
            width: 28px !important;
        }
        
        .navbar-left img {
            width: 32px !important;
        }
        
        /* Điều chỉnh kích thước font và padding cho các phần tử khác */
        .stat-title {
            font-size: 0.75rem;
        }
        
        .stat-value {
            font-size: 1.25rem;
        }
        
        .evaluation-container {
            padding: 10px;
        }
        
        .best-performer, .worst-performer {
            padding: 10px;
            min-width: 100%;
        }
    }

    /* Điều chỉnh thanh điều hướng cho điện thoại */
    @media screen and (max-width: 768px) {
        /* Điều chỉnh navbar */
        .navbar {
            padding: 8px 10px;
            flex-wrap: wrap;
        }
        
        /* Logo nhỏ hơn */
        .navbar-left img {
            width: 35px !important;
            height: auto !important;
        }
        
        /* Tiêu đề nhỏ hơn và xuống dòng */
        .navbar-center {
            order: 3;
            flex: 1 0 100%;
            margin-top: 8px;
        }
        
        .navbar-center h1 {
            font-size: 16px !important;
            white-space: normal;
            line-height: 1.2;
        }
        
        /* Điều chỉnh các nút bên phải */
        .navbar-right {
            flex: 1;
            justify-content: flex-end;
            gap: 5px;
        }
        
        .navbar-right img {
            width: 28px !important;
            height: auto !important;
        }
        
        /* Điều chỉnh ô tìm kiếm */
        .search-container {
            position: static !important;
            top: 0 !important;
        }
        
        .search-form input[type="text"] {
            width: 100px !important;
            font-size: 12px !important;
            padding: 5px !important;
            height: auto !important;
        }
        
        .search-button {
            padding: 3px !important;
        }
    }
    
    /* Điều chỉnh thêm cho màn hình rất nhỏ */
    @media screen and (max-width: 480px) {
        .navbar {
            padding: 5px 8px;
        }
        
        .navbar-left img {
            width: 30px !important;
        }
        
        .navbar-center h1 {
            font-size: 14px !important;
        }
        
        .navbar-right img {
            width: 24px !important;
        }
        
        .search-form input[type="text"] {
            width: 80px !important;
            font-size: 11px !important;
            padding: 4px !important;
        }
        
        /* Ẩn placeholder trên màn hình rất nhỏ */
        .search-form input[type="text"]::placeholder {
            opacity: 0;
        }
    }
    
    /* Thêm CSS để cải thiện hiển thị trên điện thoại */
    @media screen and (max-width: 768px) {
        /* Thêm nút menu cho điện thoại */
        .mobile-menu-button {
            display: block;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
        }
        
        /* Điều chỉnh container chính */
        .container {
            padding: 10px;
        }
        
        /* Điều chỉnh các phần tử trong container */
        .stats-container {
            padding: 5px;
            gap: 10px;
        }
        
        .stat-card {
            min-width: 100%;
            margin-bottom: 10px;
        }
        
        /* Điều chỉnh biểu đồ */
        .chart-container {
            padding: 10px;
            margin: 10px 0;
            min-height: 300px;
        }
    }

    /* Điều chỉnh navbar cho điện thoại */
    @media screen and (max-width: 768px) {
        /* Đảm bảo navbar hiển thị trên một hàng */
        .navbar {
            padding: 8px 10px;
            justify-content: space-between;
            flex-wrap: nowrap;
            gap: 5px;
        }
        
        /* Điều chỉnh logo */
        .navbar-left {
            flex: 0 0 auto;
            margin-right: 5px;
        }
        
        .navbar-left img {
            width: 32px !important;
            height: auto !important;
            vertical-align: middle;
        }
        
        /* Điều chỉnh tiêu đề */
        .navbar-center {
            flex: 0 1 auto;
            max-width: 40%;
            text-align: left;
            margin: 0;
        }
        
        .navbar-center h1 {
            font-size: 14px !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }
        
        /* Điều chỉnh phần bên phải */
        .navbar-right {
            flex: 0 0 auto;
            gap: 5px;
        }
        
        /* Điều chỉnh ô tìm kiếm */
        .search-container {
            position: relative;
        }
        
        .search-form input[type="text"] {
            width: 80px !important;
            font-size: 12px !important;
            padding: 5px !important;
            height: 28px !important;
        }
        
        .navbar-right img {
            width: 24px !important;
            height: auto !important;
        }
    }
    
    /* Điều chỉnh thêm cho màn hình rất nhỏ */
    @media screen and (max-width: 480px) {
        .navbar-center h1 {
            font-size: 12px !important;
            max-width: 100%;
        }
        
        .search-form input[type="text"] {
            width: 60px !important;
        }
        
        .search-form input[type="text"]::placeholder {
            font-size: 10px;
        }
    }

    /* CSS cho nút toggle menu */
    .navbar-toggle {
        display: none;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 6px;
        margin-left: 10px;
        color: white;
        z-index: 20;
        transition: transform 0.3s ease;
    }
    
    .navbar-toggle:focus {
        outline: none;
    }
    
    .hamburger-icon {
        display: block;
        position: relative;
        width: 24px;
        height: 18px;
    }
    
    .hamburger-icon span {
        display: block;
        position: absolute;
        height: 2px;
        width: 100%;
        background: white;
        border-radius: 2px;
        opacity: 1;
        left: 0;
        transform: rotate(0deg);
        transition: .25s ease-in-out;
    }
    
    .hamburger-icon span:nth-child(1) {
        top: 0px;
    }
    
    .hamburger-icon span:nth-child(2) {
        top: 8px;
    }
    
    .hamburger-icon span:nth-child(3) {
        top: 16px;
    }
    
    /* Thêm media query để đảm bảo vị trí hamburger icon */
    @media screen and (max-width: 429px) {
        .hamburger-icon {
            position: absolute;
            right: 15px; /* Điều chỉnh khoảng cách từ bên phải */
            top: 15px; /* Điều chỉnh vị trí từ trên xuống nếu cần */
        }
    }
    
    /* Khi menu đang mở */
    .is-active span:nth-child(1) {
        top: 8px;
        transform: rotate(45deg);
    }
    
    .is-active span:nth-child(2) {
        opacity: 0;
        width: 0%;
    }
    
    .is-active span:nth-child(3) {
        top: 8px;
        transform: rotate(-45deg);
    }
    
    /* Container cho dropdown menu */
    .navbar-dropdown {
        display: none;
        background: #002952;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        position: absolute;
        top: 100%;
        right: 0;
        left: 0;
        padding: 15px;
        z-index: 10;
        transform-origin: top;
        transform: scaleY(0);
        transition: transform 0.3s ease;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .navbar-dropdown.is-open {
        transform: scaleY(1);
    }
    
    /* Điều chỉnh hiển thị cho mobile */
    @media (max-width: 768px) {
        .navbar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            order: 3;
        }
        
        .navbar-right {
            display: none; /* Ẩn các nút khi ở chế độ mobile */
        }
        
        .navbar-dropdown {
            display: block; /* Hiển thị container cho dropdown */
        }
        
        /* Điều chỉnh navbar cho layout mobile */
        .navbar {
            padding: 8px 12px;
            justify-content: space-between;
        }
        
        .navbar-center {
            flex: 1;
            justify-content: center;
        }
        
        /* Phong cách cho các mục trong dropdown */
        .dropdown-search-container {
            margin-bottom: 15px;
        }
        
        .dropdown-search-container .search-form {
            width: 100%;
        }
        
        .dropdown-search-container .search-form input[type="text"] {
            width: 100% !important;
            padding: 8px 12px;
            font-size: 14px;
            border-radius: 6px;
        }
        
        .dropdown-nav-items {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .dropdown-nav-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: white;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        
        .dropdown-nav-item:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .dropdown-nav-item img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }
    }
    </style>
        <style>
        /* Chỉnh sửa navbar và tiêu đề */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            position: relative;
        }
        
        .navbar-left {
            flex: 0 0 auto;
        }
        
        .navbar-center {
            flex: 1 1 auto;
            text-align: center;
            padding: 0 10px;
        }
        
        .navbar-center h1 {
            margin: 0;
            font-size: clamp(16px, 5vw, 24px);
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.2;
        }
        
        .navbar-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 100;
        }
        
        /* Media queries cho các kích thước màn hình khác nhau */
        @media screen and (max-width: 768px) {
            .navbar-center {
                max-width: 60%;
                margin: 0 auto;
            }
        }
        
        @media screen and (max-width: 429px) {
            .navbar {
                flex-wrap: wrap;
                justify-content: space-between; /* Thay đổi từ center sang space-between */
            }
            
            .navbar-left {
                margin-right: 0; /* Loại bỏ margin-right */
                flex: 0 0 auto; /* Đảm bảo logo không bị co/giãn */
                align-self: flex-start; /* Luôn bắt đầu từ bên trái */
                position: relative; /* Thêm vị trí cho logo */
                left: 0; /* Đảm bảo logo luôn ở cạnh trái */
            }
            
            .navbar-center {
                order: 3;
                width: 100%;
                max-width: 100%;
                margin-top: 5px;
            }
            
            .navbar-toggle {
                position: absolute;
                right: 15px;
                top: 15px; /* Điều chỉnh vị trí nút hamburger */
            }
        }
        .search-container {
                    margin: 0 10px;
                }
                .search-group {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    background: rgba(255, 255, 255, 0.9);
                    border-radius: 4px;
                    padding: 2px;
                }
                .search-select {
                    padding: 6px;
                    border: none;
                    border-radius: 4px;
                    background: #f0f0f0;
                    color: #333;
                    font-size: 14px;
                }
                .search-input {
                    padding: 6px 10px;
                    border: none;
                    border-radius: 4px;
                    font-size: 14px;
                    flex: 1;
                    min-width: 150px;
                }
                .search-button {
                    background: none;
                    border: none;
                    cursor: pointer;
                    padding: 6px 10px;
                    color: #333;
                }
                .search-button:hover {
                    background: #f0f0f0;
                    border-radius: 4px;
                }
                @media screen and (max-width: 768px) {
                    .search-group {
                        max-width: 300px;
                    }
                    .search-select {
                        font-size: 12px;
                        padding: 4px;
                    }
                    .search-input {
                        font-size: 12px;
                        padding: 4px 8px;
                        min-width: 100px;
                    }
                    .search-button {
                        padding: 4px 8px;
                    }
                }
    </style>
    <style>
        /* CSS cho mobile */
        @media (max-width: 768px) {
            .dropdown-search-container {
                padding: 10px;
                width: 100%;
            }
            
            .mobile-search-group {
                display: flex;
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            
            .mobile-search-select {
                width: 100%;
                padding: 10px;
                font-size: 14px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background-color: white;
            }
            
            .mobile-search-input {
                width: 100%;
                padding: 10px;
                font-size: 14px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            .mobile-search-button {
                width: 100%;
                padding: 10px;
                font-size: 14px;
                background-color: #003366;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
        }
    </style>
</head>
<body>
    <!-- Thanh điều hướng -->
    <div class="navbar">
        <div class="navbar-left">
            <a href="/trangchu/"><img src="img/logoht.png" alt="Logo"></a>
        </div>
        
        <div class="navbar-center">
            <h1>ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT NHÀ MÁY</h1>
        </div>
        
        <!-- Nút hamburger cho mobile -->
        <button class="navbar-toggle" id="navbar-toggle" aria-label="Menu">
            <div class="hamburger-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
        
        <!-- Menu cho desktop -->
        <div class="navbar-right">
            <div class="search-container">
                <form class="search-form" action="" method="GET">
                    <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                    <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                    <div class="search-group">
                        <select name="search_type" class="search-select">
                            <option value="xuong" <?php echo (!isset($_GET['search_type']) || $_GET['search_type'] == 'xuong') ? 'selected' : ''; ?>>Xưởng</option>
                            <option value="line" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'line') ? 'selected' : ''; ?>>Line</option>
                            <option value="po" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'po') ? 'selected' : ''; ?>>PO</option>
                            <option value="style" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'style') ? 'selected' : ''; ?>>Style</option>
                            <option value="model" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'model') ? 'selected' : ''; ?>>Model</option>
                        </select>
                        <input type="text" name="search_value" placeholder="Nhập từ khóa tìm kiếm..." 
                               value="<?php echo isset($_GET['search_value']) ? htmlspecialchars($_GET['search_value']) : ''; ?>"
                               class="search-input">
                        <button type="submit" class="search-button">🔍</button>
                    </div>
                </form>
            </div>
            <a href="dept_statistics_month.php" title="Thống kê"><img src="img/thongke.png" alt="Thống kê"></a>
            <a href="import.php" title="Nhập dữ liệu"><img src="img/add.png" alt="Nhập dữ liệu"></a>
            <a href="export.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" title="Xuất dữ liệu"><img src="img/export.jpg" alt="Xuất dữ liệu"></a>
        </div>
    </div>

    <!-- Dropdown menu cho mobile -->
    <div class="navbar-dropdown" id="navbar-dropdown">
        <div class="dropdown-search-container">
            <form class="search-form" action="" method="GET">
                <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                <div class="mobile-search-group">
                    <select name="search_type" class="mobile-search-select">
                        <option value="xuong" <?php echo (!isset($_GET['search_type']) || $_GET['search_type'] == 'xuong') ? 'selected' : ''; ?>>Xưởng</option>
                        <option value="line" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'line') ? 'selected' : ''; ?>>Line</option>
                        <option value="po" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'po') ? 'selected' : ''; ?>>PO</option>
                        <option value="style" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'style') ? 'selected' : ''; ?>>Style</option>
                        <option value="model" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'model') ? 'selected' : ''; ?>>Model</option>
                    </select>
                    <input type="text" name="search_value" placeholder="Nhập từ khóa tìm kiếm..." 
                           value="<?php echo isset($_GET['search_value']) ? htmlspecialchars($_GET['search_value']) : ''; ?>"
                           class="mobile-search-input">
                    <button type="submit" class="mobile-search-button">🔍</button>
                </div>
            </form>
        </div>
        
        <div class="dropdown-nav-items">
            <!-- <a href="index.php" class="dropdown-nav-item">
                <img src="img/home.png" alt="Trang chủ">
                Trang chủ
            </a> -->
            <a href="dept_statistics_month.php" class="dropdown-nav-item">
                <img src="img/thongke.png" alt="Thống kê">
                Thống kê
            </a>
            <a href="import.php" class="dropdown-nav-item">
                <img src="img/add.png" alt="Nhập dữ liệu">
                Nhập dữ liệu
            </a>
            <a href="export.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" class="dropdown-nav-item">
                <img src="img/export.jpg" alt="Xuất dữ liệu">
                Xuất dữ liệu
            </a>
            <!-- <a href="chart.php" class="dropdown-nav-item">
                <img src="img/chart.png" alt="Biểu đồ">
                Biểu đồ
            </a> -->
            <!-- <a href="settings.php" class="dropdown-nav-item">
                <img src="img/setting.png" alt="Cài đặt">
                Cài đặt
            </a> -->
        </div>
    </div>

    <!-- JavaScript để xử lý đóng/mở menu -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo các biến
        const navbarToggle = document.getElementById('navbar-toggle');
        const navbarDropdown = document.getElementById('navbar-dropdown');
        const title = document.querySelector('.navbar-center h1');
        const searchInputs = document.querySelectorAll('.search-form input[type="text"]');
        let originalTitle = '';
        
        // Lưu tiêu đề gốc
        if (title) {
            originalTitle = title.textContent;
            title.setAttribute('data-original-text', originalTitle);
        }
        
        // Hàm điều chỉnh giao diện theo kích thước màn hình
        function adjustForScreenSize() {
            const isMobile = window.innerWidth < 768;
            
            // Điều chỉnh tiêu đề
            if (title) {
                if (isMobile) {
                    title.textContent = 'ĐÁNH GIÁ HỆ THỐNG';
                    
                    // Thêm sự kiện click để hiện tiêu đề đầy đủ
                    if (!title.hasAttribute('data-tooltip-added')) {
                        title.style.cursor = 'pointer';
                        title.addEventListener('click', function() {
                            alert(this.getAttribute('data-original-text'));
                        });
                        title.setAttribute('data-tooltip-added', 'true');
                    }
                } else {
                    title.textContent = originalTitle;
                }
            }
            
            // Điều chỉnh placeholder cho ô tìm kiếm
            searchInputs.forEach(input => {
                input.placeholder = isMobile ? 'Tìm kiếm...' : 'Tìm kiếm theo xưởng...';
            });
            
            // Đóng dropdown khi chuyển sang desktop
            if (!isMobile && navbarDropdown.classList.contains('is-open')) {
                navbarToggle.classList.remove('is-active');
                navbarDropdown.classList.remove('is-open');
            }
        }
        
        // Xử lý click vào nút toggle
        if (navbarToggle && navbarDropdown) {
            navbarToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                navbarToggle.classList.toggle('is-active');
                navbarDropdown.classList.toggle('is-open');
                
                // Điều chỉnh hiển thị của dropdown
                if (navbarDropdown.classList.contains('is-open')) {
                    navbarDropdown.style.display = 'block';
                } else {
                    setTimeout(() => {
                        navbarDropdown.style.display = 'none';
                    }, 300);
                }
            });
            
            // Đóng dropdown khi click ra ngoài
            document.addEventListener('click', function(e) {
                if (!navbarDropdown.contains(e.target) && !navbarToggle.contains(e.target)) {
                    if (navbarDropdown.classList.contains('is-open')) {
                        navbarToggle.classList.remove('is-active');
                        navbarDropdown.classList.remove('is-open');
                        
                        setTimeout(() => {
                            navbarDropdown.style.display = 'none';
                        }, 300);
                    }
                }
            });
            
            // Ngăn đóng dropdown khi click vào nội dung bên trong
            navbarDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Thêm hiệu ứng ripple cho các nút
        const buttons = document.querySelectorAll('.navbar-right a, .dropdown-nav-item');
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            
            button.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });
        
        // Gọi hàm khi tải trang và khi thay đổi kích thước
        adjustForScreenSize();
        window.addEventListener('resize', adjustForScreenSize);
    });
    </script>

    <!-- Form nhập dữ liệu từ Excel -->
    <div class="container">
    <h3DANH SÁCH MÃ HÀNG SẢN XUẤT TRONG THÁNG</h3>

    <!-- Thêm biểu đồ cột mới -->
    <div class="chart-container">
        <canvas id="departmentChart"></canvas>
    </div>

    <div class="evaluation-container">
        <?php
        // Tính toán tỷ lệ hoàn thành cho từng bộ phận từ biểu đồ
        $chart_departments = [
            'Kế Hoạch' => ['code' => 'kehoach', 'color' => '#FF6384'],
            'Kỹ Thuật' => ['code' => 'chuanbi_sanxuat_phong_kt', 'color' => '#36A2EB'],
            'Kho' => ['code' => 'kho', 'color' => '#FFCE56'],
            'Cắt' => ['code' => 'cat', 'color' => '#4BC0C0'],
            'Ép Keo' => ['code' => 'ep_keo', 'color' => '#9966FF'],
            'Cơ Điện' => ['code' => 'co_dien', 'color' => '#FF9F40'],
            'Chuyền May' => ['code' => 'chuyen_may', 'color' => '#FF6384'],
            'KCS' => ['code' => 'kcs', 'color' => '#36A2EB'],
            'Ủi TP' => ['code' => 'ui_thanh_pham', 'color' => '#4BC0C0'],
            'Hoàn Thành' => ['code' => 'hoan_thanh', 'color' => '#9966FF']
        ];

        $completion_rates = [];
        foreach ($chart_departments as $dept_name => $info) {
            $completed = 0;
            foreach ($rows as $row) {
                if (checkDeptStatus($connect, $row['stt'], $info['code'])) {
                    $completed++;
                }
            }
            $completion_rates[$dept_name] = $total_tasks > 0 ? round(($completed / $total_tasks) * 100) : 0;
        }

        // Tìm tỷ lệ cao nhất và thấp nhất
        $max_percent = max($completion_rates);
        $min_percent = min($completion_rates);

        // Tìm tất cả bộ phận có tỷ lệ cao nhất
        $best_depts = array_filter($completion_rates, function ($percent) use ($max_percent) {
            return $percent == $max_percent;
        });
        $best_dept_names = array_keys($best_depts);

        // Tìm tất cả bộ phận có tỷ lệ thấp nhất
        $worst_depts = array_filter($completion_rates, function ($percent) use ($min_percent) {
            return $percent == $min_percent;
        });
        $worst_dept_names = array_keys($worst_depts);

        // Xác định các điều kiện hiển thị
        $show_best = $max_percent > 0; // Chỉ hiển thị "tốt nhất" nếu có ít nhất một bộ phận hoàn thành
        $show_worst = !($min_percent > 0 && count($worst_depts) == count($completion_rates)); // Không hiển thị "cần cải thiện" nếu tất cả bộ phận có cùng tỷ lệ > 0
        ?>
        
        <?php if ($show_best) : ?>
        <div class="best-performer">
            <div class="eval-icon success">✓</div>
            <div class="eval-content">
                <h4><?php echo count($best_dept_names) > 1 ? 'Các bộ phận hoạt động tốt nhất:' : 'Bộ phận hoạt động tốt nhất:'; ?></h4>
                <p>
                    <?php foreach ($best_dept_names as $index => $dept) : ?>
                        <strong>
                            <a href="dept_statistics.php?dept=<?php echo $chart_departments[$dept]['code']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: underline;">
                                <?php echo $dept; ?>
                            </a>
                        </strong>
                        <?php if ($index < count($best_dept_names) - 1) : ?>
                            <?php echo $index == count($best_dept_names) - 2 ? ' và ' : ', '; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    đạt <strong><?php echo $max_percent; ?>%</strong> tiến độ
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($show_worst) : ?>
        <div class="worst-performer">
            <div class="eval-icon warning">!</div>
            <div class="eval-content">
                <h4><?php echo count($worst_dept_names) > 1 ? 'Các bộ phận cần cải thiện:' : 'Bộ phận cần cải thiện:'; ?></h4>
                <p>
                    <?php foreach ($worst_dept_names as $index => $dept) : ?>
                        <strong>
                            <a href="dept_statistics.php?dept=<?php echo $chart_departments[$dept]['code']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: underline;">
                                <?php echo $dept; ?>
                            </a>
                        </strong>
                        <?php if ($index < count($worst_dept_names) - 1) : ?>
                            <?php echo $index == count($worst_dept_names) - 2 ? ' và ' : ', '; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    chỉ đạt <strong><?php echo $min_percent; ?>%</strong> tiến độ
                </p>
                <?php if ($min_percent < 50) : ?>
                    <div class="alert-message">
                        ⚠️ Cảnh báo: Tiến độ chuẩn bị thấp, cần có biện pháp cải thiện ngay!
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php
    if (isset($_GET['success']) && $_GET['success'] == 1) {
        if (isset($_GET['action']) && $_GET['action'] == 'delete') {
            echo '<div class="success-message">Xóa dữ liệu thành công!</div>';
        } else {
            echo '<div class="success-message">Lưu đánh giá thành công!</div>';
        }
    }
    if (isset($_GET['error']) && $_GET['error'] == 1) {
        if (isset($_GET['action']) && $_GET['action'] == 'delete') {
            $error_message = isset($_GET['message']) ? $_GET['message'] : 'Có lỗi xảy ra khi xóa dữ liệu!';
        } else {
            $error_message = 'Có lỗi xảy ra khi lưu đánh giá!';
        }
        echo '<div class="error-message">' . htmlspecialchars($error_message) . '</div>';
    }
    ?>
    <form id="deleteForm" action="delete_rows.php" method="post">
        <div class="action-container">
            <div class="action-buttons">
                <button type="submit" class="btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa các dòng đã chọn?')">
                    Xóa dòng đã chọn
                </button>
            </div>
            <div class="month-selector">
                <div class="select-wrapper">
                    <i class="calendar-icon">📅</i>
                    <select name="month" id="month-select" onchange="changeMonth(this)">
                        <?php foreach ($available_months as $month) : ?>
                            <?php
                                $month_name = date('m/Y', mktime(0, 0, 0, $month['month'], 1, $month['year']));
                                $selected = ($month['month'] == $selected_month && $month['year'] == $selected_year) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $month['month']; ?>" 
                                    data-year="<?php echo $month['year']; ?>"
                                    <?php echo $selected; ?>>
                                Tháng <?php echo $month_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <style>
        .action-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .month-selector {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 5px 10px;
        }

        .month-form {
            display: flex;
            align-items: center;
        }

        .select-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .calendar-icon {
            font-size: 16px;
        }

        #month-select {
            padding: 4px 30px 4px 8px;
            border: none;
            font-size: 14px;
            color: #1e293b;
            background-color: transparent;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 5px center;
            min-width: 140px;
        }

        #month-select:focus {
            outline: none;
        }

        #month-select option {
            padding: 8px;
        }
        </style>

        <div class="data-table-container">
            <div class="scroll-hint">Vuốt sang trái/phải để xem toàn bộ bảng</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" id="select-all"></th>
                        <th style="width: 40px;">STT</th>
                        <th style="width: 70px;">Xưởng</th>
                        <th style="width: 45px;">LINE</th>
                        <th style="width: 100px;">P/o no.</th>
                        <th style="width: 150px;">Style</th>
                        <th style="width: 80px;">Model</th>
                        <th style="width: 60px;">Qty</th>
                        <th style="width: 90px;">In</th>
                        <th style="width: 90px;">Out</th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=kehoach&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Kế Hoạch
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=chuanbi_sanxuat_phong_kt&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Kỹ Thuật
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=kho&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Kho
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=cat&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Cắt
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=ep_keo&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Ép Keo
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=co_dien&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Cơ Điện
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=chuyen_may&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Chuyền May
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=kcs&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                KCS
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=ui_thanh_pham&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Ủi TP
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=hoan_thanh&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Hoàn Thành
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
        <?php
        $stt = 1;
        foreach ($rows as $row) {
            $ngayin_formatted = date('d/m/Y', strtotime($row['ngayin']));
            $ngayout_formatted = date('d/m/Y', strtotime($row['ngayout']));

            // Lấy hạn xử lý thấp nhất của bộ phận kế hoạch
            $kehoach_deadline = getEarliestDeadline($connect, $row['stt'], 'kehoach');

            // Nếu không có hạn xử lý, sử dụng cách tính mặc định
            if (!$kehoach_deadline) {
                $ngayin = new DateTime($row['ngayin']);
                $kehoach = clone $ngayin;
                $kehoach->modify('-7 days');
                $kehoach_formatted = $kehoach->format('d/m/Y');
            } else {
                $kehoach_formatted = date('d/m/Y', strtotime($kehoach_deadline));
            }

            // Lấy hạn xử lý thấp nhất của bộ phận kho
            $kho_deadline = getEarliestDeadline($connect, $row['stt'], 'kho');

            // Nếu không có hạn xử lý, sử dụng cách tính mặc định
            if (!$kho_deadline) {
                $ngayin = new DateTime($row['ngayin']);
                $kho = clone $ngayin;
                $kho->modify('-14 days');
                $kho_formatted = $kho->format('d/m/Y');
            } else {
                $kho_formatted = date('d/m/Y', strtotime($kho_deadline));
            }

            // Kiểm tra trạng thái hoàn thành của tất cả bộ phận
            $kehoach_completed = checkDeptStatus($connect, $row['stt'], 'kehoach');
            $chuanbi_completed = checkDeptStatus($connect, $row['stt'], 'chuanbi_sanxuat_phong_kt');
            $kho_completed = checkDeptStatus($connect, $row['stt'], 'kho');
            $cat_completed = checkDeptStatus($connect, $row['stt'], 'cat');
            $epkeo_completed = checkDeptStatus($connect, $row['stt'], 'ep_keo');
            $codien_completed = checkDeptStatus($connect, $row['stt'], 'co_dien');
            $chuyenmay_completed = checkDeptStatus($connect, $row['stt'], 'chuyen_may');
            $kcs_completed = checkDeptStatus($connect, $row['stt'], 'kcs');
            $ui_completed = checkDeptStatus($connect, $row['stt'], 'ui_thanh_pham');
            $hoanthanh_completed = checkDeptStatus($connect, $row['stt'], 'hoan_thanh');

            // Chỉ đổi màu xanh khi tất cả bộ phận đều hoàn thành
            $all_completed = $kehoach_completed && $chuanbi_completed && $kho_completed &&
                            $cat_completed && $epkeo_completed && $codien_completed &&
                            $chuyenmay_completed && $kcs_completed && $ui_completed &&
                            $hoanthanh_completed;

            $row_class = $all_completed ? 'style="background-color:rgba(107, 243, 141, 0.95);"' : '';

            echo "<tr {$row_class}>";
            echo "<td><input type='checkbox' name='selected_rows[]' value='{$row['stt']}'></td>";
            echo "<td><a href='danhgia_hethong.php?id={$row['stt']}' style='color: inherit; text-decoration: underline;'>{$stt}</a></td>";
            echo "<td><a href='factory_templates.php?xuong=" . urlencode($row['xuong']) . "&month=" . $selected_month . "&year=" . $selected_year . "' style='color: inherit; text-decoration: underline;'>{$row['xuong']}</a></td>";
            echo "<td>{$row['line1']}</td>";
            echo "<td>{$row['po']}</td>";
            echo "<td>
                    <a href='incomplete_criteria.php?style=" . urlencode($row['style']) . "&stt=" . $row['stt'] . "' 
                       class='style-link" . (hasIncompleteCriteria($connect, $row['style'], $row['stt']) ? " has-incomplete" : "") . "' 
                       title='Xem tiêu chí chưa hoàn thành'>
                        " . htmlspecialchars($row['style']) . "
                    </a>
                </td>";
            echo "<td>" . htmlspecialchars($row['model']) . "</td>";
            echo "<td class='text-center'>{$row['qty']}</td>";
            echo "<td><a href='edit_date_clone.php?id={$row['stt']}' title='Chỉnh sửa ngày in' style='color:inherit; text-decoration:underline;'>{$ngayin_formatted}</a></td>";
            echo "<td>{$ngayout_formatted}</td>";

            // Kế hoạch
            echo "<td>";
            if (!$kehoach_completed) {
                echo "<div style='display: flex; align-items: center; justify-content: center; gap: 5px;'>";
                echo "<div style='width: 20px; height: 20px; background: #ef4444; border-radius: 4px; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;'>X</div>";
                echo "<a href='indexdept.php?dept=kehoach&id={$row['stt']}'>{$kehoach_formatted}</a>";
                echo "</div>";
            } else {
                echo "<div style='display: flex; align-items: center; justify-content: center; gap: 5px;'>";
                echo "<div style='width: 20px; height: 20px; background: #10b981; border-radius: 4px; color: white; display: flex; align-items: center; justify-content: center;'>✓</div>";
                echo "<a href='indexdept.php?dept=kehoach&id={$row['stt']}'>{$kehoach_formatted}</a>";
                echo "</div>";
            }
            echo "</td>";

            // Chuẩn bị SX
            echo "<td>";
            // Lấy hạn xử lý thấp nhất của bộ phận chuẩn bị SX
            $chuanbi_deadline = getEarliestDeadline($connect, $row['stt'], 'chuanbi_sanxuat_phong_kt');

            // Nếu không có hạn xử lý, sử dụng ngày của kehoach
            $chuanbi_formatted_date = !$chuanbi_deadline ? $kehoach_formatted : date('d/m/Y', strtotime($chuanbi_deadline));

            if (!$chuanbi_completed) {
                echo "<div style='display: flex; align-items: center; justify-content: center; gap: 5px;'>";
                echo "<div style='width: 20px; height: 20px; background: #ef4444; border-radius: 4px; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;'>X</div>";
                echo "<a href='indexdept.php?dept=chuanbi_sanxuat_phong_kt&id={$row['stt']}'>{$chuanbi_formatted_date}</a>";
                echo "</div>";
            } else {
                echo "<div style='display: flex; align-items: center; justify-content: center; gap: 5px;'>";
                echo "<div style='width: 20px; height: 20px; background: #10b981; border-radius: 4px; color: white; display: flex; align-items: center; justify-content: center;'>✓</div>";
                echo "<a href='indexdept.php?dept=chuanbi_sanxuat_phong_kt&id={$row['stt']}'>{$chuanbi_formatted_date}</a>";
                echo "</div>";
            }
            echo "</td>";

            // Kho
            echo "<td>";
            if (!$kho_completed) {
                echo "<div style='display: flex; align-items: center; justify-content: center; gap: 5px;'>";
                echo "<div style='width: 20px; height: 20px; background: #ef4444; border-radius: 4px; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;'>X</div>";
                echo "<a href='indexdept.php?dept=kho&id={$row['stt']}'>{$kho_formatted}</a>";
                echo "</div>";
            } else {
                echo "<div style='display: flex; align-items: center; justify-content: center; gap: 5px;'>";
                echo "<div style='width: 20px; height: 20px; background: #10b981; border-radius: 4px; color: white; display: flex; align-items: center; justify-content: center;'>✓</div>";
                echo "<a href='indexdept.php?dept=kho&id={$row['stt']}'>{$kho_formatted}</a>";
                echo "</div>";
            }
            echo "</td>";

            // Các bộ phận còn lại
            $departments = [
                'cat' => 'Cắt',
                'ep_keo' => 'Ép Keo',
                'co_dien' => 'Cơ Điện',
                'chuyen_may' => 'Chuyền May',
                'kcs' => 'KCS',
                'ui_thanh_pham' => 'Ủi TP',
                'hoan_thanh' => 'Hoàn Thành'
            ];

            foreach ($departments as $dept_code => $dept_name) {
                $dept_completed = checkDeptStatus($connect, $row['stt'], $dept_code);

                // Lấy hạn xử lý thấp nhất của bộ phận
                $dept_deadline = getEarliestDeadline($connect, $row['stt'], $dept_code);

                // Nếu không có hạn xử lý, sử dụng ngày của kehoach
                $dept_formatted_date = !$dept_deadline ? $kehoach_formatted : date('d/m/Y', strtotime($dept_deadline));

                echo "<td>";
                if (!$dept_completed) {
                    echo "<div style='display: flex; align-items: center; justify-content: center; gap: 5px;'>";
                    echo "<div style='width: 20px; height: 20px; background: #ef4444; border-radius: 4px; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;'>X</div>";
                    echo "<a href='indexdept.php?dept={$dept_code}&id={$row['stt']}'>{$dept_formatted_date}</a>";
                    echo "</div>";
                } else {
                    echo "<div style='display: flex; align-items: center; justify-content: center; gap: 5px;'>";
                    echo "<div style='width: 20px; height: 20px; background: #10b981; border-radius: 4px; color: white; display: flex; align-items: center; justify-content: center;'>✓</div>";
                    echo "<a href='indexdept.php?dept={$dept_code}&id={$row['stt']}'>{$dept_formatted_date}</a>";
                    echo "</div>";
                }
                echo "</td>";
            }

            echo "</tr>";
            $stt++;
        }
        ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
// // Comment đoạn code chọn tất cả
document.getElementById('select-all').addEventListener('change', function() {
    var checkboxes = document.getElementsByName('selected_rows[]');
    for (var checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
});

// Thêm code xử lý highlight dòng được chọn
document.addEventListener('DOMContentLoaded', function() {
    var checkboxes = document.getElementsByName('selected_rows[]');
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var row = this.closest('tr');
            if (this.checked) {
                row.classList.add('selected-row');
            } else {
                row.classList.remove('selected-row');
            }
        });
    });
});

// Hàm xử lý thay đổi tháng
function changeMonth(select) {
    var selectedOption = select.options[select.selectedIndex];
    var month = select.value;
    var year = selectedOption.getAttribute('data-year');
    
    // Lấy tham số tìm kiếm hiện tại nếu có
    var searchParams = new URLSearchParams(window.location.search);
    var searchXuong = searchParams.get('search_xuong');
    
    var url = 'index.php?month=' + month + '&year=' + year;
    if (searchXuong) {
        url += '&search_xuong=' + encodeURIComponent(searchXuong);
    }
    
    window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function() {
    // Tính toán phần trăm hoàn thành cho từng bộ phận
    <?php
    $departments = [
        'Kế Hoạch' => ['code' => 'kehoach', 'color' => '#FF6384'],
        'Kỹ Thuật' => ['code' => 'chuanbi_sanxuat_phong_kt', 'color' => '#36A2EB'],
        'Kho' => ['code' => 'kho', 'color' => '#FFCE56'],
        'Cắt' => ['code' => 'cat', 'color' => '#4BC0C0'],
        'Ép Keo' => ['code' => 'ep_keo', 'color' => '#9966FF'],
        'Cơ Điện' => ['code' => 'co_dien', 'color' => '#FF9F40'],
        'Chuyền May' => ['code' => 'chuyen_may', 'color' => '#FF6384'],
        'KCS' => ['code' => 'kcs', 'color' => '#36A2EB'],
        'Ủi TP' => ['code' => 'ui_thanh_pham', 'color' => '#4BC0C0'],
        'Hoàn Thành' => ['code' => 'hoan_thanh', 'color' => '#9966FF']
    ];

    $dept_stats = [];
    $dept_colors = [];
    foreach ($departments as $label => $info) {
        $completed = 0;
        foreach ($rows as $row) {
            if (checkDeptStatus($connect, $row['stt'], $info['code'])) {
                $completed++;
            }
        }
        $percent = $total_tasks > 0 ? round(($completed / $total_tasks) * 100) : 0;
        $dept_stats[$label] = $percent;
        $dept_colors[] = $info['color'];
    }
    ?>

    const ctx = document.getElementById('departmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($dept_stats)); ?>,
            datasets: [{
                label: 'Tỷ lệ hoàn thành (%)',
                data: <?php echo json_encode(array_values($dept_stats)); ?>,
                backgroundColor: <?php echo json_encode($dept_colors); ?>,
                borderRadius: 8,
                maxBarThickness: 50,
                borderColor: 'rgba(255, 255, 255, 0.8)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 20,
                    right: 20,
                    bottom: 20,
                    left: 20
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Tỷ lệ hoàn thành của các bộ phận - Tháng ' + <?php echo $selected_month; ?> + '/' + <?php echo $selected_year; ?>,
                    font: {
                        size: window.innerWidth <= 428 ? 16 : 45,
                        weight: 'bold',
                        family: "'Segoe UI', 'Arial', sans-serif"
                    },
                    padding: window.innerWidth <= 428 ? 10 : 20,
                    color: 'rgb(226, 2, 2)'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + '%';
                        }
                    },
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#333',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 6,
                    usePointStyle: true
                }
            },
            onClick: function(e, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const deptName = this.data.labels[index];
                    const deptCode = getDeptCode(deptName);
                    if (deptCode) {
                        window.location.href = 'dept_statistics.php?dept=' + deptCode + '&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>';
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        },
                        font: {
                            size: window.innerWidth <= 428 ? 10 : 12
                        },
                        color: '#666'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: window.innerWidth <= 428 ? 8 : 12
                        },
                        color: '#666',
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            hover: {
                mode: 'index',
                intersect: false
            }
        }
    });
});

// Hàm chuyển đổi tên bộ phận sang mã bộ phận
function getDeptCode(deptName) {
    const deptMap = {
        'Kế Hoạch': 'kehoach',
        'Kỹ Thuật': 'chuanbi_sanxuat_phong_kt',
        'Kho': 'kho',
        'Cắt': 'cat',
        'Ép Keo': 'ep_keo',
        'Cơ Điện': 'co_dien',
        'Chuyền May': 'chuyen_may',
        'KCS': 'kcs',
        'Ủi TP': 'ui_thanh_pham',
        'Hoàn Thành': 'hoan_thanh'
    };
    return deptMap[deptName];
}

// Thêm tính năng cuộn ngang bảng dữ liệu bằng chuột (drag-to-scroll)
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.querySelector('.data-table-container');
    if (!tableContainer) return;
    
    let isDragging = false;
    let startX;
    let scrollLeft;
    
    // Thêm cursor để thông báo người dùng có thể kéo
    tableContainer.style.cursor = 'grab';
    
    // Bắt sự kiện khi bấm chuột
    tableContainer.addEventListener('mousedown', function(e) {
        // Chỉ áp dụng cho chuột trái và không phải click vào link hay button
        if (e.button !== 0 || e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || 
            e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || 
            e.target.tagName === 'CHECKBOX') return;
        
        isDragging = true;
        tableContainer.style.cursor = 'grabbing';
        tableContainer.style.userSelect = 'none';
        
        // Ghi nhớ vị trí bắt đầu
        startX = e.pageX - tableContainer.offsetLeft;
        scrollLeft = tableContainer.scrollLeft;
        
        // Ngăn chặn hành vi mặc định như chọn text
        e.preventDefault();
    });
    
    // Bắt sự kiện di chuyển chuột
    tableContainer.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        
        // Tính toán vị trí mới
        const x = e.pageX - tableContainer.offsetLeft;
        const walk = (x - startX) * 2; // Nhân 2 để cuộn nhanh hơn
        
        // Cuộn bảng
        tableContainer.scrollLeft = scrollLeft - walk;
    });
    
    // Bắt sự kiện khi thả chuột
    function endDrag() {
        if (!isDragging) return;
        
        isDragging = false;
        tableContainer.style.cursor = 'grab';
        tableContainer.style.removeProperty('user-select');
    }
    
    // Thêm các event listener cho thả chuột và rời khỏi container
    tableContainer.addEventListener('mouseup', endDrag);
    tableContainer.addEventListener('mouseleave', endDrag);
    
    // Thêm gợi ý về tính năng kéo để cuộn
    const hint = document.createElement('div');
    hint.className = 'drag-hint';
    hint.textContent = 'Kéo để cuộn bảng ↔️';
    tableContainer.appendChild(hint);
    
    // Ẩn gợi ý sau 5 giây
    setTimeout(function() {
        hint.style.opacity = '0';
    }, 5000);
});

// Thêm vào phần Chart.js để bảo đảm responsive
document.addEventListener('DOMContentLoaded', function() {
    // Điều chỉnh chiều cao của chart-container để phù hợp với nội dung
    function adjustChartContainerHeight() {
        const chartContainer = document.querySelector('.chart-container');
        const evaluationContainer = document.querySelector('.evaluation-container');
        
        if (chartContainer && evaluationContainer) {
            // Đảm bảo container có đủ chiều cao
            if (window.innerWidth <= 768) {
                chartContainer.style.marginBottom = '20px';
            } else {
                chartContainer.style.marginBottom = '20px';
            }
        }
    }
    
    // Gọi hàm khi trang tải và khi thay đổi kích thước
    adjustChartContainerHeight();
    window.addEventListener('resize', adjustChartContainerHeight);
});

// Thêm tính năng cuộn theo checkbox
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.querySelector('.data-table-container');
    if (!tableContainer) return;
    
    // Lấy tất cả các checkbox trong bảng (trừ checkbox "chọn tất cả")
    const checkboxes = document.querySelectorAll('.data-table tbody input[type="checkbox"]');
    const selectAllCheckbox = document.getElementById('select-all');
    
    // Biến theo dõi số lượng checkbox đã chọn
    let selectedCount = 0;
    
    // Xử lý sự kiện khi thay đổi trạng thái checkbox
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Cập nhật số lượng checkbox đã chọn
            selectedCount = document.querySelectorAll('.data-table tbody input[type="checkbox"]:checked').length;
            
            // Cuộn bảng dựa vào số lượng checkbox đã chọn
            if (selectedCount === 1) {
                // Nếu chỉ có 1 checkbox được chọn, cuộn sang phải
                scrollToRight();
            } else if (selectedCount >= 2) {
                // Nếu có từ 2 checkbox trở lên, cuộn sang trái
                scrollToLeft();
            } else if (selectedCount === 0) {
                // Nếu không còn checkbox nào được chọn, cuộn sang trái
                scrollToLeft();
            }
        });
    });
    
    // Xử lý checkbox "chọn tất cả"
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            // Nếu "chọn tất cả" được tích, cuộn sang trái
            if (this.checked) {
                scrollToLeft();
            } else {
                // Nếu bỏ chọn "chọn tất cả", cũng cuộn sang trái
                scrollToLeft();
            }
        });
    }
    
    // Hàm cuộn sang phải
    function scrollToRight() {
        const scrollWidth = tableContainer.scrollWidth;
        tableContainer.scrollTo({
            left: scrollWidth,
            behavior: 'smooth'
        });
    }
    
    // Hàm cuộn sang trái
    function scrollToLeft() {
        tableContainer.scrollTo({
            left: 0,
            behavior: 'smooth'
        });
    }
    
    // Thêm hiệu ứng khi checkbox được chọn
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('tr');
            if (this.checked) {
                row.style.backgroundColor = '#f0f9ff'; // Màu nền khi được chọn
            } else {
                row.style.backgroundColor = ''; // Trở về màu nền mặc định
            }
        });
    });
});

// JavaScript để cải thiện trải nghiệm trên thiết bị di động
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra nếu đang xem trên thiết bị di động
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Thêm meta viewport để đảm bảo hiển thị đúng
        const metaViewport = document.querySelector('meta[name="viewport"]');
        if (metaViewport) {
            metaViewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
        }
        
        // Thêm touch events cho các nút để cải thiện phản hồi
        const navButtons = document.querySelectorAll('.navbar a');
        navButtons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            
            button.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });
        
        // Điều chỉnh placeholder của ô tìm kiếm để ngắn gọn hơn
        const searchInput = document.querySelector('.search-form input[type="text"]');
        if (searchInput && searchInput.placeholder === 'Tìm kiếm theo xưởng...') {
            searchInput.placeholder = 'Tìm kiếm...';
        }
    }
});

// Thêm JavaScript để cải thiện trải nghiệm trên điện thoại
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra nếu đang xem trên thiết bị di động
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Điều chỉnh tiêu đề nếu quá dài
        const title = document.querySelector('.navbar-center h1');
        if (title && title.textContent.length > 30) {
            // Rút gọn tiêu đề trên điện thoại
            const originalText = title.textContent;
            title.setAttribute('data-original-text', originalText);
            title.textContent = 'ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT';
            
            // Thêm tooltip để hiển thị tiêu đề đầy đủ khi nhấn vào
            title.style.cursor = 'pointer';
            title.addEventListener('click', function() {
                alert(this.getAttribute('data-original-text'));
            });
        }
        
        // Điều chỉnh placeholder của ô tìm kiếm
        const searchInput = document.querySelector('.search-form input[type="text"]');
        if (searchInput) {
            searchInput.placeholder = 'Tìm...';
        }
    }
});

// Thêm JavaScript để rút gọn tiêu đề trên điện thoại
document.addEventListener('DOMContentLoaded', function() {
    function adjustNavbarForMobile() {
        const isMobile = window.innerWidth <= 768;
        const title = document.querySelector('.navbar-center h1');
        
        if (isMobile && title) {
            // Lưu tiêu đề gốc nếu chưa lưu
            if (!title.getAttribute('data-original-text')) {
                title.setAttribute('data-original-text', title.textContent);
            }
            
            // Rút gọn tiêu đề
            title.textContent = 'ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT';
            
            // Thêm tooltip
            title.style.cursor = 'pointer';
            if (!title.hasAttribute('data-tooltip-added')) {
                title.addEventListener('click', function() {
                    alert(this.getAttribute('data-original-text'));
                });
                title.setAttribute('data-tooltip-added', 'true');
            }
        } else if (!isMobile && title && title.getAttribute('data-original-text')) {
            // Khôi phục tiêu đề gốc trên màn hình lớn
            title.textContent = title.getAttribute('data-original-text');
        }
    }
    
    // Gọi hàm khi tải trang và khi thay đổi kích thước màn hình
    adjustNavbarForMobile();
    window.addEventListener('resize', adjustNavbarForMobile);
});

// Cải thiện trải nghiệm người dùng trên thiết bị di động
document.addEventListener('DOMContentLoaded', function() {
    function adjustForDeviceSize() {
        const viewport = window.innerWidth;
        const navbar = document.querySelector('.navbar');
        const title = document.querySelector('.navbar-center h1');
        const searchInput = document.querySelector('.search-form input[type="text"]');
        
        // Lưu trữ tiêu đề gốc nếu chưa lưu
        if (title && !title.hasAttribute('data-original-text')) {
            title.setAttribute('data-original-text', title.textContent);
        }
        
        if (viewport <= 768) {
            // Điều chỉnh cho thiết bị di động
            if (title) {
                title.textContent = 'ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT';
                title.style.cursor = 'pointer';
                
                // Thêm tooltip chỉ khi chưa có
                if (!title.hasAttribute('data-tooltip-added')) {
                    title.addEventListener('click', function() {
                        alert(this.getAttribute('data-original-text'));
                    });
                    title.setAttribute('data-tooltip-added', 'true');
                }
            }
            
            if (searchInput) {
                searchInput.placeholder = 'Tìm...';
            }
        } else {
            // Khôi phục trên thiết bị lớn hơn
            if (title && title.hasAttribute('data-original-text')) {
                title.textContent = title.getAttribute('data-original-text');
            }
            
            if (searchInput) {
                searchInput.placeholder = 'Tìm kiếm theo xưởng...';
            }
        }
        
        // Thêm hiệu ứng ripple cho các nút trên thiết bị cảm ứng
        const buttons = document.querySelectorAll('.navbar-right img, .navbar-left img');
        buttons.forEach(button => {
            if (!button.hasAttribute('data-ripple-added')) {
                button.addEventListener('touchstart', function() {
                    this.style.opacity = '0.7';
                });
                
                button.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });
                
                button.setAttribute('data-ripple-added', 'true');
            }
        });
    }
    
    // Gọi hàm khi tải trang và khi thay đổi kích thước
    adjustForDeviceSize();
    window.addEventListener('resize', adjustForDeviceSize);
});
</script>

</body>
</html>

