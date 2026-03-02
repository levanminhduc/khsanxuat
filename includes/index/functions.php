<?php
/**
 * Index Page Functions
 * Helper functions for index.php
 */

/**
 * Check department completion status
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
 * Get earliest deadline for a product and department
 */
function getEarliestDeadline($connect, $id_sanxuat, $dept)
{
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

    return null;
}

/**
 * Check if style has incomplete criteria
 */
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

/**
 * Get available months from database
 */
function getAvailableMonths($connect)
{
    $sql = "SELECT DISTINCT MONTH(ngayin) as month, YEAR(ngayin) as year
            FROM khsanxuat
            ORDER BY year DESC, month DESC";
    $result = mysqli_query($connect, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Build search condition for SQL query
 */
function buildSearchCondition($search_value, $search_type)
{
    if (empty($search_value) || strtolower($search_value) === "all") {
        return ['condition' => '', 'params' => []];
    }

    $field_map = [
        'xuong' => 'xuong',
        'line' => 'line1',
        'po' => 'po',
        'style' => 'style',
        'model' => 'model'
    ];

    $field = $field_map[$search_type] ?? 'xuong';

    return [
        'condition' => " AND $field LIKE ?",
        'params' => ["%$search_value%"]
    ];
}

/**
 * Get production data with filters
 */
function getProductionData($connect, $month, $year, $search_value = '', $search_type = 'xuong')
{
    $search = buildSearchCondition($search_value, $search_type);

    $sql = "SELECT * FROM khsanxuat
            WHERE MONTH(ngayin) = ? AND YEAR(ngayin) = ?" . $search['condition'] . "
            ORDER BY xuong ASC, CAST(line1 AS UNSIGNED) ASC, ngayin ASC";

    $stmt = $connect->prepare($sql);

    if (!empty($search['params'])) {
        $stmt->bind_param("iis", $month, $year, $search['params'][0]);
    } else {
        $stmt->bind_param("ii", $month, $year);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

/**
 * Calculate completion stats for all departments
 */
function calculateStats($connect, $rows)
{
    $total_tasks = count($rows);
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

    return [
        'total_tasks' => $total_tasks,
        'completed_tasks' => $completed_tasks,
        'completed_kehoach' => $completed_kehoach,
        'completed_kho' => $completed_kho,
        'completion_percent' => $completion_percent,
        'kehoach_percent' => $kehoach_percent,
        'kho_percent' => $kho_percent
    ];
}

/**
 * Calculate department chart data
 */
function calculateDeptChartData($connect, $rows, $departments)
{
    $total_tasks = count($rows);
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

    return [
        'labels' => array_keys($dept_stats),
        'data' => array_values($dept_stats),
        'colors' => $dept_colors
    ];
}

/**
 * Find best and worst performing departments
 */
function findBestWorstDepts($dept_stats)
{
    if (empty($dept_stats)) {
        return ['best' => null, 'worst' => null];
    }

    $best_dept = array_search(max($dept_stats), $dept_stats);
    $worst_dept = array_search(min($dept_stats), $dept_stats);

    return [
        'best' => ['name' => $best_dept, 'percent' => $dept_stats[$best_dept]],
        'worst' => ['name' => $worst_dept, 'percent' => $dept_stats[$worst_dept]]
    ];
}

/**
 * Check if all departments are completed for a row
 */
function checkAllDeptsCompleted($connect, $stt)
{
    $all_depts = [
        'kehoach', 'chuanbi_sanxuat_phong_kt', 'kho', 'cat',
        'ep_keo', 'co_dien', 'chuyen_may', 'kcs', 'ui_thanh_pham', 'hoan_thanh'
    ];

    foreach ($all_depts as $dept) {
        if (!checkDeptStatus($connect, $stt, $dept)) {
            return false;
        }
    }
    return true;
}

/**
 * Format date for display
 */
function formatDateVN($date_string)
{
    if (empty($date_string)) {
        return '';
    }
    $date = new DateTime($date_string);
    return $date->format('d/m/Y');
}

/**
 * Calculate deadline date based on ngayin/ngayout
 */
function calculateDeadlineDate($ngayin, $days_before = 7)
{
    $date = new DateTime($ngayin);
    $date->modify("-{$days_before} days");
    return $date->format('d/m/Y');
}
