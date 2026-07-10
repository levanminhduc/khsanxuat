<?php

session_start();
require_once __DIR__ . '/bootstrap.php';
require_once 'includes/index/config.php';
require_once 'includes/index/functions.php';
require_once 'includes/security/csrf-helper.php';

$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = intval(date('m'));
}
if ($selected_year < 2000 || $selected_year > 2100) {
    $selected_year = intval(date('Y'));
}

$available_months = getAvailableMonths($connect);
$rows = getProductionData($connect, $selected_month, $selected_year,
    isset($_GET['search_value']) ? $_GET['search_value'] : '',
    isset($_GET['search_type']) ? $_GET['search_type'] : 'xuong'
);
$total_tasks = count($rows);
$status_map = batchLoadDeptStatus($connect, $rows);
$deadline_map = batchLoadDeadlines($connect, $rows);

$stats = calculateStats($connect, $rows);
$completed_tasks = $stats['completed_tasks'];
$completed_kehoach = $stats['completed_kehoach'];
$completed_kho = $stats['completed_kho'];
$completion_percent = $stats['completion_percent'];
$kehoach_percent = $stats['kehoach_percent'];
$kho_percent = $stats['kho_percent'];

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT NHÀ MÁY</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="styleindex.css?v=<?php echo filemtime('styleindex.css'); ?>">
    
    <link rel="stylesheet" href="assets/css/header.css?v=<?php echo filemtime('assets/css/header.css'); ?>">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/index/index.css?v=<?php echo filemtime('assets/css/index/index.css'); ?>">
    <link rel="stylesheet" href="assets/css/loading-overlay.css">
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
            'search_value' => isset($_GET['search_value']) ? $_GET['search_value'] : '',
            'placeholder_suggestions' => [
                'Nhập từ khóa tìm kiếm...',
                'Tìm xưởng...',
                'Tìm line...',
                'Tìm PO...',
                'Tìm style...',
                'Tìm model...'
            ]
        ],
        'actions' => [
            [
                'url' => 'dept_statistics_month.php',
                'icon' => 'img/header/chart.png',
                'title' => 'Thống kê',
                'tooltip' => 'Xem thống kê'
            ],
            [
                'url' => 'import.php',
                'icon' => 'img/header/plus.png',
                'title' => 'Nhập dữ liệu',
                'tooltip' => 'Nhập dữ liệu mới'
            ],
            [
                'url' => 'pages/export.php?month=' . $selected_month . '&year=' . $selected_year,
                'icon' => 'img/header/download.png',
                'title' => 'Xuất dữ liệu',
                'tooltip' => 'Xuất dữ liệu',
                'download' => true,
                'loading_text' => 'Đang xuất Excel...'
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
            'Kho' => ['code' => 'kho', 'color' => '#FFCE56'],
            'Cắt' => ['code' => 'cat', 'color' => '#4BC0C0'],
            'Trung Tâm BTP' => ['code' => 'trung_tam_btp', 'color' => '#8B5CF6'],
            'Kỹ Thuật' => ['code' => 'chuanbi_sanxuat_phong_kt', 'color' => '#36A2EB'],
            'Cơ Điện' => ['code' => 'co_dien', 'color' => '#9966FF'],
            'Chuyền May' => ['code' => 'chuyen_may', 'color' => '#14B8A6'],
            'KCS' => ['code' => 'kcs', 'color' => '#F97316']

        ];

        $completion_rates = [];
        foreach ($chart_departments as $dept_name => $info) {
            $completed = 0;
            foreach ($rows as $row) {
                if (getDeptStatusFromCache($status_map, $row['stt'], $info['code'])) {
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
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=<?php echo $chart_departments[$dept]['code']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: underline;">
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
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=<?php echo $chart_departments[$dept]['code']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: underline;">
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
    <form id="deleteForm" action="<?php echo BASE_URL; ?>/actions/delete_rows.php" method="post">
        <?php echo getCsrfInput(); ?>
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
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=kehoach&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Kế Hoạch
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=kho&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Kho
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=cat&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Cắt
                            </a>
                        </th>
                        <th style="width: 130px;">
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=trung_tam_btp&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Trung Tâm BTP
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=chuanbi_sanxuat_phong_kt&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Kỹ Thuật
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=co_dien&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Cơ Điện
                            </a>
                        </th>
                        <th style="width: 120px;">
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=chuyen_may&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                Chuyền May
                            </a>
                        </th>
                        <th style="width: 110px;">
                            <a href="<?php echo BASE_URL; ?>/pages/dept_statistics.php?dept=kcs&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" style="color: inherit; text-decoration: none;">
                                KCS
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

            $kehoach_deadline = getDeadlineFromCache($deadline_map, $row['stt'], 'kehoach');

            if (!$kehoach_deadline) {
                $ngayin = new DateTime($row['ngayin']);
                $kehoach = clone $ngayin;
                $kehoach->modify('-7 days');
                $kehoach_formatted = $kehoach->format('d/m/Y');
            } else {
                $kehoach_formatted = date('d/m/Y', strtotime($kehoach_deadline));
            }

            $kho_deadline = getDeadlineFromCache($deadline_map, $row['stt'], 'kho');

            if (!$kho_deadline) {
                $ngayin = new DateTime($row['ngayin']);
                $kho = clone $ngayin;
                $kho->modify('-14 days');
                $kho_formatted = $kho->format('d/m/Y');
            } else {
                $kho_formatted = date('d/m/Y', strtotime($kho_deadline));
            }

            $kehoach_completed = getDeptStatusFromCache($status_map, $row['stt'], 'kehoach');
            $chuanbi_completed = getDeptStatusFromCache($status_map, $row['stt'], 'chuanbi_sanxuat_phong_kt');
            $kho_completed = getDeptStatusFromCache($status_map, $row['stt'], 'kho');
            $cat_completed = getDeptStatusFromCache($status_map, $row['stt'], 'cat');
            $epkeo_completed = getDeptStatusFromCache($status_map, $row['stt'], 'ep_keo');
            $codien_completed = getDeptStatusFromCache($status_map, $row['stt'], 'co_dien');
            $chuyenmay_completed = getDeptStatusFromCache($status_map, $row['stt'], 'chuyen_may');
            $kcs_completed = getDeptStatusFromCache($status_map, $row['stt'], 'kcs');
            $ui_completed = getDeptStatusFromCache($status_map, $row['stt'], 'ui_thanh_pham');
            $hoanthanh_completed = getDeptStatusFromCache($status_map, $row['stt'], 'hoan_thanh');

            $all_completed = $kehoach_completed && $chuanbi_completed && $kho_completed &&
                            $cat_completed && $epkeo_completed && $codien_completed &&
                            $chuyenmay_completed && $kcs_completed && $ui_completed &&
                            $hoanthanh_completed;

            $row_class = $all_completed ? 'style="background-color:rgba(107, 243, 141, 0.95);"' : '';

            echo "<tr {$row_class}>";
            echo "<td><input type='checkbox' name='selected_rows[]' value='" . intval($row['stt']) . "'></td>";
            echo "<td><a href='" . BASE_URL . "/pages/danhgia_hethong.php?id=" . intval($row['stt']) . "' style='color: inherit; text-decoration: underline;'>{$stt}</a></td>";
            echo "<td><a href='" . BASE_URL . "/pages/factory_templates.php?xuong=" . urlencode($row['xuong']) . "&month=" . $selected_month . "&year=" . $selected_year . "' style='color: inherit; text-decoration: underline;'>" . htmlspecialchars($row['xuong'], ENT_QUOTES, 'UTF-8') . "</a></td>";
            echo "<td>" . htmlspecialchars($row['line1'], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($row['po'], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>
                    <a href='" . BASE_URL . "/pages/incomplete_criteria.php?style=" . urlencode($row['style']) . "&stt=" . intval($row['stt']) . "'
                       class='style-link" . (hasIncompleteCriteria($connect, $row['style'], $row['stt']) ? " has-incomplete" : "") . "'
                       title='Xem tiêu chí chưa hoàn thành'>
                        " . htmlspecialchars($row['style'], ENT_QUOTES, 'UTF-8') . "
                    </a>
                </td>";
            echo "<td>" . htmlspecialchars($row['model'], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td class='text-center'>" . intval($row['qty']) . "</td>";
            echo "<td><a href='" . BASE_URL . "/pages/edit_date_clone.php?id=" . intval($row['stt']) . "' title='Chỉnh sửa ngày in' style='color:inherit; text-decoration:underline;'>" . htmlspecialchars($ngayin_formatted, ENT_QUOTES, 'UTF-8') . "</a></td>";
            echo "<td>" . htmlspecialchars($ngayout_formatted, ENT_QUOTES, 'UTF-8') . "</td>";

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
                'trung_tam_btp' => 'Trung Tâm BTP',
                'chuanbi_sanxuat_phong_kt' => 'Kỹ Thuật',
                'co_dien' => 'Cơ Điện',
                'chuyen_may' => 'Chuyền May',
                'kcs' => 'KCS'

            ];

            foreach ($departments as $dept_code => $dept_name) {
                $dept_completed = getDeptStatusFromCache($status_map, $row['stt'], $dept_code);

                $dept_deadline = getDeadlineFromCache($deadline_map, $row['stt'], $dept_code);

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
window.indexConfig = {
    selectedMonth: <?php echo $selected_month; ?>,
    selectedYear: <?php echo $selected_year; ?>,
    deptStats: {
        labels: <?php echo json_encode(array_keys($completion_rates)); ?>,
        data: <?php echo json_encode(array_values($completion_rates)); ?>,
        colors: <?php echo json_encode(array_map(function($info) { return $info['color']; }, $chart_departments)); ?>
    },
    chartDepartments: <?php echo json_encode(array_combine(
        array_keys($chart_departments),
        array_map(function($info) { return ['code' => $info['code']]; }, $chart_departments)
    )); ?>
};
</script>
<script src="assets/js/index.js?v=<?php echo filemtime('assets/js/index.js'); ?>"></script>


<script src="assets/js/header.js?v=<?php echo filemtime('assets/js/header.js'); ?>"></script>


<?php include 'components/back-to-top.php'; ?>

<?php include 'components/loading-overlay.php'; ?>
<script src="assets/js/loading-overlay.js"></script>

</body>
</html>
