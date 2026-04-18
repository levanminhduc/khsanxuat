<?php


require_once 'contdb.php';
require_once 'includes/index/config.php';
require_once 'includes/index/functions.php';

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$available_months = getAvailableMonths($connect);
$rows = getProductionData($connect, $selected_month, $selected_year,
    isset($_GET['search_value']) ? $_GET['search_value'] : '',
    isset($_GET['search_type']) ? $_GET['search_type'] : 'xuong'
);
$total_tasks = count($rows);

$stats = calculateStats($connect, $rows);
$completed_tasks = $stats['completed_tasks'];
$completed_kehoach = $stats['completed_kehoach'];
$completed_kho = $stats['completed_kho'];
$completion_percent = $stats['completion_percent'];
$kehoach_percent = $stats['kehoach_percent'];
$kho_percent = $stats['kho_percent'];

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

foreach ($rows as $row) {
    foreach ($departments as $dept_code => $dept_name) {
        $dept_stats[$dept_name] += checkDeptStatus($connect, $row['stt'], $dept_code) ? 1 : 0;
    }
}

foreach ($dept_stats as $dept => $completed) {
    $dept_stats[$dept] = $total_tasks > 0 ? round(($completed / $total_tasks) * 100) : 0;
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
    
    <link rel="stylesheet" href="assets/css/header.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    

    
    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
    }

    
    .container {
        width: 100%;
        max-width: 100%;
        padding: 15px;
        margin: 0;
        box-sizing: border-box;
        overflow-x: visible; 
    }

    
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

    
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    
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

    
    @media screen and (max-width: 429px) {
        .hamburger-icon {
            position: absolute;
            right: 15px; 
            top: 15px; 
        }
    }

    
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

    
    @media (max-width: 768px) {
        .navbar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            order: 3;
        }

        .navbar-right {
            display: none; 
        }

        .navbar-dropdown {
            display: block; 
        }

        
        .navbar {
            padding: 8px 12px;
            justify-content: space-between;
        }

        .navbar-center {
            flex: 1;
            justify-content: center;
        }

        
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

        
        @media screen and (max-width: 768px) {
            .navbar-center {
                max-width: 60%;
                margin: 0 auto;
            }
        }

        @media screen and (max-width: 429px) {
            .navbar {
                flex-wrap: wrap;
                justify-content: space-between; 
            }

            .navbar-left {
                margin-right: 0; 
                flex: 0 0 auto; 
                align-self: flex-start; 
                position: relative; 
                left: 0; 
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
                top: 15px; 
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

        
        @media screen and (max-width: 480px) {
            .stat-card {
                flex: 1 1 100%;
                min-width: 100%;
                max-width: 100%;
            }

            .best-performer, .worst-performer {
                flex: 1 1 100%;
                min-width: 100%;
            }

            .evaluation-container {
                gap: 15px;
                padding: 10px;
            }

            .chart-container {
                margin: 10px 0;
                padding: 10px;
            }
        }

        
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

        .btn-delete {
            background-color: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-delete:hover {
            background-color: #dc2626;
        }
    </style>
</head>
<body>
    <?php

    $header_config = [
        'title' => 'ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT NHÀ MÁY',
        'title_short' => 'ĐÁNH GIÁ SẢN XUẤT',
        'logo_path' => 'img/logoht.png',
        'logo_link' => '/trangchu/',
        'show_search' => true,
        'show_mobile_menu' => true,
        'search_params' => [
            'action' => 'index.php',
            'month' => $selected_month,
            'year' => $selected_year,
            'search_type' => isset($_GET['search_type']) ? $_GET['search_type'] : 'xuong',
            'search_value' => isset($_GET['search_value']) ? $_GET['search_value'] : ''
        ],
        'actions' => [
            [
                'url' => 'dept_statistics_month.php',
                'icon' => 'img/thongke.png',
                'title' => 'Thống kê',
                'tooltip' => 'Xem thống kê'
            ],
            [
                'url' => 'import.php',
                'icon' => 'img/add.png',
                'title' => 'Nhập dữ liệu',
                'tooltip' => 'Nhập dữ liệu mới'
            ],
            [
                'url' => 'export.php?month=' . $selected_month . '&year=' . $selected_year,
                'icon' => 'img/export.jpg',
                'title' => 'Xuất dữ liệu',
                'tooltip' => 'Xuất dữ liệu'
            ]
        ]
    ];
    include 'components/header.php';
    ?>

    
    

    
    <div class="container">
    <h3>DANH SÁCH MÃ HÀNG SẢN XUẤT TRONG THÁNG</h3>

    
    <div class="chart-container">
        <canvas id="departmentChart"></canvas>
    </div>

    <div class="evaluation-container">
        <?php

        $chart_departments = [
            'Kế Hoạch' => ['code' => 'kehoach', 'color' => '#FF6384'],
            'Kỹ Thuật' => ['code' => 'chuanbi_sanxuat_phong_kt', 'color' => '#36A2EB'],
            'Kho' => ['code' => 'kho', 'color' => '#FFCE56'],
            'Cắt' => ['code' => 'cat', 'color' => '#4BC0C0'],
            'Cơ Điện' => ['code' => 'co_dien', 'color' => '#9966FF']

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

        $max_percent = max($completion_rates);
        $min_percent = min($completion_rates);

        $best_depts = array_filter($completion_rates, function ($percent) use ($max_percent) {
            return $percent == $max_percent;
        });
        $best_dept_names = array_keys($best_depts);

        $worst_depts = array_filter($completion_rates, function ($percent) use ($min_percent) {
            return $percent == $min_percent;
        });
        $worst_dept_names = array_keys($worst_depts);

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
                            <a href="dept_statistics.php?dept=co_dien&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Cơ Điện
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

            $kehoach_deadline = getEarliestDeadline($connect, $row['stt'], 'kehoach');

            if (!$kehoach_deadline) {
                $ngayin = new DateTime($row['ngayin']);
                $kehoach = clone $ngayin;
                $kehoach->modify('-7 days');
                $kehoach_formatted = $kehoach->format('d/m/Y');
            } else {
                $kehoach_formatted = date('d/m/Y', strtotime($kehoach_deadline));
            }

            $kho_deadline = getEarliestDeadline($connect, $row['stt'], 'kho');

            if (!$kho_deadline) {
                $ngayin = new DateTime($row['ngayin']);
                $kho = clone $ngayin;
                $kho->modify('-14 days');
                $kho_formatted = $kho->format('d/m/Y');
            } else {
                $kho_formatted = date('d/m/Y', strtotime($kho_deadline));
            }

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

            echo "<td>";

            $chuanbi_deadline = getEarliestDeadline($connect, $row['stt'], 'chuanbi_sanxuat_phong_kt');

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

            $departments = [
                'cat' => 'Cắt',
                'co_dien' => 'Cơ Điện'

            ];

            foreach ($departments as $dept_code => $dept_name) {
                $dept_completed = checkDeptStatus($connect, $row['stt'], $dept_code);

                $dept_deadline = getEarliestDeadline($connect, $row['stt'], $dept_code);

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

document.getElementById('select-all').addEventListener('change', function() {
    var checkboxes = document.getElementsByName('selected_rows[]');
    for (var checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
});

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

function changeMonth(select) {
    var selectedOption = select.options[select.selectedIndex];
    var month = select.value;
    var year = selectedOption.getAttribute('data-year');

    var searchParams = new URLSearchParams(window.location.search);
    var searchXuong = searchParams.get('search_xuong');

    var url = 'index.php?month=' + month + '&year=' + year;
    if (searchXuong) {
        url += '&search_xuong=' + encodeURIComponent(searchXuong);
    }

    window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function() {

    <?php
    $departments = [
        'Kế Hoạch' => ['code' => 'kehoach', 'color' => '#FF6384'],
        'Kỹ Thuật' => ['code' => 'chuanbi_sanxuat_phong_kt', 'color' => '#36A2EB'],
        'Kho' => ['code' => 'kho', 'color' => '#FFCE56'],
        'Cắt' => ['code' => 'cat', 'color' => '#4BC0C0'],
        'Cơ Điện' => ['code' => 'co_dien', 'color' => '#9966FF']

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

document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.querySelector('.data-table-container');
    if (!tableContainer) return;

    let isDragging = false;
    let startX;
    let scrollLeft;

    tableContainer.style.cursor = 'grab';

    tableContainer.addEventListener('mousedown', function(e) {

        if (e.button !== 0 || e.target.tagName === 'A' || e.target.tagName === 'BUTTON' ||
            e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' ||
            e.target.tagName === 'CHECKBOX') return;

        isDragging = true;
        tableContainer.style.cursor = 'grabbing';
        tableContainer.style.userSelect = 'none';

        startX = e.pageX - tableContainer.offsetLeft;
        scrollLeft = tableContainer.scrollLeft;

        e.preventDefault();
    });

    tableContainer.addEventListener('mousemove', function(e) {
        if (!isDragging) return;

        const x = e.pageX - tableContainer.offsetLeft;
        const walk = (x - startX) * 2; // Nhân 2 để cuộn nhanh hơn

        tableContainer.scrollLeft = scrollLeft - walk;
    });

    function endDrag() {
        if (!isDragging) return;

        isDragging = false;
        tableContainer.style.cursor = 'grab';
        tableContainer.style.removeProperty('user-select');
    }

    tableContainer.addEventListener('mouseup', endDrag);
    tableContainer.addEventListener('mouseleave', endDrag);

    const hint = document.createElement('div');
    hint.className = 'drag-hint';
    hint.textContent = 'Kéo để cuộn bảng ↔️';
    tableContainer.appendChild(hint);

    setTimeout(function() {
        hint.style.opacity = '0';
    }, 5000);
});

document.addEventListener('DOMContentLoaded', function() {

    function adjustChartContainerHeight() {
        const chartContainer = document.querySelector('.chart-container');
        const evaluationContainer = document.querySelector('.evaluation-container');

        if (chartContainer && evaluationContainer) {

            if (window.innerWidth <= 768) {
                chartContainer.style.marginBottom = '20px';
            } else {
                chartContainer.style.marginBottom = '20px';
            }
        }
    }

    adjustChartContainerHeight();
    window.addEventListener('resize', adjustChartContainerHeight);
});

document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.querySelector('.data-table-container');
    if (!tableContainer) return;

    const checkboxes = document.querySelectorAll('.data-table tbody input[type="checkbox"]');
    const selectAllCheckbox = document.getElementById('select-all');

    let selectedCount = 0;

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {

            selectedCount = document.querySelectorAll('.data-table tbody input[type="checkbox"]:checked').length;

            if (selectedCount === 1) {

                scrollToRight();
            } else if (selectedCount >= 2) {

                scrollToLeft();
            } else if (selectedCount === 0) {

                scrollToLeft();
            }
        });
    });

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {

            if (this.checked) {
                scrollToLeft();
            } else {

                scrollToLeft();
            }
        });
    }

    function scrollToRight() {
        const scrollWidth = tableContainer.scrollWidth;
        tableContainer.scrollTo({
            left: scrollWidth,
            behavior: 'smooth'
        });
    }

    function scrollToLeft() {
        tableContainer.scrollTo({
            left: 0,
            behavior: 'smooth'
        });
    }

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
</script>


<script src="assets/js/header.js"></script>


<?php include 'components/back-to-top.php'; ?>

</body>
</html>
