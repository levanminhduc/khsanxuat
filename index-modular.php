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

$chart_departments = [
    'Kế Hoạch' => ['code' => 'kehoach', 'color' => '#FF6384'],
    'Kỹ Thuật' => ['code' => 'chuanbi_sanxuat_phong_kt', 'color' => '#36A2EB'],
    'Kho' => ['code' => 'kho', 'color' => '#FFCE56'],
    'Cắt' => ['code' => 'cat', 'color' => '#4BC0C0']
];

$dept_stats = [];
$dept_colors = [];
$completion_rates = [];
foreach ($chart_departments as $dept_name => $info) {
    $completed = 0;
    foreach ($rows as $row) {
        if (checkDeptStatus($connect, $row['stt'], $info['code'])) {
            $completed++;
        }
    }
    $percent = $total_tasks > 0 ? round(($completed / $total_tasks) * 100) : 0;
    $dept_stats[$dept_name] = $percent;
    $dept_colors[] = $info['color'];
    $completion_rates[$dept_name] = $percent;
}

$max_percent = !empty($completion_rates) ? max($completion_rates) : 0;
$min_percent = !empty($completion_rates) ? min($completion_rates) : 0;

$best_depts = array_filter($completion_rates, function ($percent) use ($max_percent) {
    return $percent == $max_percent;
});
$best_dept_names = array_keys($best_depts);

$worst_depts = array_filter($completion_rates, function ($percent) use ($min_percent) {
    return $percent == $min_percent;
});
$worst_dept_names = array_keys($worst_depts);

$show_best = $max_percent > 0;
$show_worst = !($min_percent > 0 && count($worst_depts) == count($completion_rates));
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
</head>
<body>

<?php
$header_config = [
    'title' => 'ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT NHÀ MÁY',
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
        ['url' => 'dept_statistics_month.php', 'icon' => 'img/thongke.png', 'title' => 'Thống kê', 'tooltip' => 'Xem thống kê'],
        ['url' => 'import.php', 'icon' => 'img/add.png', 'title' => 'Nhập dữ liệu', 'tooltip' => 'Nhập dữ liệu mới'],
        ['url' => 'export.php?month=' . $selected_month . '&year=' . $selected_year, 'icon' => 'img/export.jpg', 'title' => 'Xuất dữ liệu', 'tooltip' => 'Xuất dữ liệu']
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
        <?php if ($show_best) : ?>
        <div class="best-performer">
            <div class="eval-icon success">✓</div>
            <div class="eval-content">
                <h4><?= count($best_dept_names) > 1 ? 'Các bộ phận hoạt động tốt nhất:' : 'Bộ phận hoạt động tốt nhất:' ?></h4>
                <p>
                    <?php foreach ($best_dept_names as $index => $dept) : ?>
                        <strong>
                            <a href="dept_statistics.php?dept=<?= $chart_departments[$dept]['code'] ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>" style="color: inherit; text-decoration: underline;">
                                <?= $dept ?>
                            </a>
                        </strong>
                        <?php if ($index < count($best_dept_names) - 1) : ?>
                            <?= $index == count($best_dept_names) - 2 ? ' và ' : ', ' ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    đạt <strong><?= $max_percent ?>%</strong> tiến độ
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($show_worst) : ?>
        <div class="worst-performer">
            <div class="eval-icon warning">!</div>
            <div class="eval-content">
                <h4><?= count($worst_dept_names) > 1 ? 'Các bộ phận cần cải thiện:' : 'Bộ phận cần cải thiện:' ?></h4>
                <p>
                    <?php foreach ($worst_dept_names as $index => $dept) : ?>
                        <strong>
                            <a href="dept_statistics.php?dept=<?= $chart_departments[$dept]['code'] ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>" style="color: inherit; text-decoration: underline;">
                                <?= $dept ?>
                            </a>
                        </strong>
                        <?php if ($index < count($worst_dept_names) - 1) : ?>
                            <?= $index == count($worst_dept_names) - 2 ? ' và ' : ', ' ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    chỉ đạt <strong><?= $min_percent ?>%</strong> tiến độ
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
        $error_message = (isset($_GET['action']) && $_GET['action'] == 'delete')
            ? (isset($_GET['message']) ? $_GET['message'] : 'Có lỗi xảy ra khi xóa dữ liệu!')
            : 'Có lỗi xảy ra khi lưu đánh giá!';
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
                            <option value="<?= $month['month'] ?>"
                                    data-year="<?= $month['year'] ?>"
                                    <?= $selected ?>>
                                Tháng <?= $month_name ?>
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
                        <?php foreach ($chart_departments as $dept_name => $info) : ?>
                        <th style="width: 110px;">
                            <a href="dept_statistics.php?dept=<?= $info['code'] ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>" style="color: inherit; text-decoration: none;">
                                <?= $dept_name ?>
                            </a>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $stt = 1;
                foreach ($rows as $row) {
                    $ngayin_formatted = date('d/m/Y', strtotime($row['ngayin']));
                    $ngayout_formatted = date('d/m/Y', strtotime($row['ngayout']));

                    $all_completed = true;
                    foreach ($chart_departments as $dept_name => $info) {
                        if (!checkDeptStatus($connect, $row['stt'], $info['code'])) {
                            $all_completed = false;
                            break;
                        }
                    }

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

                    foreach ($chart_departments as $dept_name => $info) {
                        $dept_code = $info['code'];
                        $dept_completed = checkDeptStatus($connect, $row['stt'], $dept_code);
                        $dept_deadline = getEarliestDeadline($connect, $row['stt'], $dept_code);

                        if (!$dept_deadline) {
                            $ngayin_dt = new DateTime($row['ngayin']);
                            if ($dept_code === 'kehoach') {
                                $ngayin_dt->modify('-7 days');
                            } elseif ($dept_code === 'kho') {
                                $ngayin_dt->modify('-14 days');
                            }
                            $dept_formatted_date = $ngayin_dt->format('d/m/Y');
                        } else {
                            $dept_formatted_date = date('d/m/Y', strtotime($dept_deadline));
                        }

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
    selectedMonth: <?= (int)$selected_month ?>,
    selectedYear: <?= (int)$selected_year ?>,
    deptStats: {
        labels: <?= json_encode(array_keys($dept_stats)) ?>,
        data: <?= json_encode(array_values($dept_stats)) ?>,
        colors: <?= json_encode($dept_colors) ?>
    },
    chartDepartments: <?= json_encode($chart_departments) ?>
};
</script>
<script src="assets/js/index.js"></script>
<script src="assets/js/header.js"></script>

<?php include 'components/back-to-top.php'; ?>

</body>
</html>
