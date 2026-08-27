<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';
require_once BASE_PATH . '/includes/indexdept/config.php';
require_once BASE_PATH . '/helpers/activity_logger.php';

requireLogin();

$action_types = [
    'update_score' => 'Cập nhật điểm',
    'update_person' => 'Thay đổi người thực hiện',
    'update_note' => 'Cập nhật ghi chú',
    'update_multiple' => 'Cập nhật nhiều thông tin',
    'add_image' => 'Thêm hình ảnh',
    'add_template' => 'Thêm biểu mẫu',
    'delete_image' => 'Xóa hình ảnh',
    'delete_template' => 'Xóa biểu mẫu'
];

$limit_options = [25, 50, 100, 200, 500];
$default_limit = 100;

function activityEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** GET dang mang (?user_name[]=a) lam trim()/preg_match() nem TypeError => coi nhu rong. */
function activityGetParam($key)
{
    return isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : '';
}

function activityBadgeClass($action_type)
{
    if (strpos($action_type, 'update_') === 0) {
        return 'badge-update';
    }
    if (strpos($action_type, 'delete_') === 0) {
        return 'badge-delete';
    }
    return 'badge-add';
}

/**
 * Tách chuỗi log "tiêu chí N: điểm X, người thực hiện Y, ghi chú: Z" (nối bằng " | ")
 * thành map [thutu => [mô tả thay đổi]]. Chỉ giữ phần THỰC SỰ khác giữa cũ/mới.
 * Format chuỗi do actions/save_danhgia_with_log.php sinh ra — đổi format ở đó phải sửa hàm này.
 *
 * @return array<int|string, string[]>
 */
function parseTieuchiDiff($old_value, $new_value)
{
    $old_parts = explode(' | ', (string) $old_value);
    $new_parts = explode(' | ', (string) $new_value);
    $diff = [];

    foreach ($old_parts as $idx => $old_part) {
        if (!isset($new_parts[$idx])) {
            continue;
        }
        $new_part = $new_parts[$idx];

        if (!preg_match('/tiêu chí (\d+)/', $old_part, $matches)) {
            continue;
        }
        $thutu = $matches[1];

        $old_score = preg_match('/điểm (\d+(?:\.\d+)?)/', $old_part, $matches) ? $matches[1] : null;
        $new_score = preg_match('/điểm (\d+(?:\.\d+)?)/', $new_part, $matches) ? $matches[1] : null;

        $old_person = preg_match('/người thực hiện ([^,]+)/', $old_part, $matches) ? $matches[1] : null;
        $new_person = preg_match('/người thực hiện ([^,]+)/', $new_part, $matches) ? $matches[1] : null;

        $old_note = preg_match('/ghi chú: ([^|]+)/', $old_part, $matches) ? trim($matches[1]) : null;
        $new_note = preg_match('/ghi chú: ([^|]+)/', $new_part, $matches) ? trim($matches[1]) : null;

        $score_changed = ($old_score === null || $new_score === null)
            ? $old_score !== $new_score
            : (float) $old_score !== (float) $new_score;

        $changes = [];
        if ($score_changed) {
            $changes[] = "Điểm thay đổi từ {$old_score} --> {$new_score}";
        }
        if ($old_person !== $new_person) {
            $changes[] = "Người thực hiện thay đổi từ \"{$old_person}\" --> \"{$new_person}\"";
        }
        if ($old_note !== $new_note) {
            if (empty($old_note)) {
                $changes[] = "Thêm ghi chú: \"{$new_note}\"";
            } elseif (empty($new_note)) {
                $changes[] = "Xóa ghi chú: \"{$old_note}\"";
            } else {
                $changes[] = "Ghi chú thay đổi từ \"{$old_note}\" --> \"{$new_note}\"";
            }
        }

        if (!empty($changes)) {
            $diff[$thutu] = $changes;
        }
    }

    return $diff;
}

/** Render chi tiết thay đổi của 1 dòng log; trả về chuỗi HTML đã escape. */
function renderActivityDetails(array $activity)
{
    if (empty($activity['additional_info'])) {
        return '';
    }

    $info = json_decode($activity['additional_info'], true);
    if (!is_array($info)) {
        return '';
    }

    $html = '';
    if (isset($info['changes']) && is_scalar($info['changes'])) {
        $html .= '<div><strong>Thay đổi:</strong> ' . activityEscape($info['changes']) . '</div>';
    }

    if (isset($info['changed_tieuchi']) && is_array($info['changed_tieuchi'])) {
        $diff = parseTieuchiDiff($activity['old_value'], $activity['new_value']);
        $items = '';

        foreach ($info['changed_tieuchi'] as $tieuchi) {
            $thutu = isset($tieuchi['thutu']) ? $tieuchi['thutu'] : '';
            if (!isset($diff[$thutu])) {
                continue;
            }

            $items .= '<li>Tiêu chí ' . activityEscape($thutu) . ': '
                . activityEscape(isset($tieuchi['noidung']) ? $tieuchi['noidung'] : '')
                . '<div class="tieuchi-change">';
            foreach ($diff[$thutu] as $change) {
                $items .= '- ' . activityEscape($change) . '<br>';
            }
            $items .= '</div></li>';
        }

        if ($items !== '') {
            $html .= '<ul class="tieuchi-list">' . $items . '</ul>';
        }
    }

    return $html;
}

/** Sinh URL trang $page, giữ nguyên bộ lọc hiện tại (bỏ param rỗng cho URL gọn). */
function activityLogPageUrl(array $query, $page)
{
    $query['page'] = $page;
    $query = array_filter($query, function ($value) {
        return $value !== '' && $value !== null;
    });

    return BASE_URL . '/pages/activity_logs.php?' . http_build_query($query);
}

$valid_depts = getValidDepts();

$filter_action_type = activityGetParam('action_type');
if (!isset($action_types[$filter_action_type])) {
    $filter_action_type = '';
}

$filter_dept = activityGetParam('dept');
if (!in_array($filter_dept, $valid_depts, true)) {
    $filter_dept = '';
}

$filter_id_khsanxuat = max(0, (int) activityGetParam('id_khsanxuat'));

$filter_user_name = mb_substr(trim(activityGetParam('user_name')), 0, 50);

$filter_date_from = activityGetParam('date_from');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date_from)) {
    $filter_date_from = '';
}

$filter_date_to = activityGetParam('date_to');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date_to)) {
    $filter_date_to = '';
}

$limit = (int) activityGetParam('limit');
if (!in_array($limit, $limit_options, true)) {
    $limit = $default_limit;
}

$filters = [];
if ($filter_action_type !== '') {
    $filters['action_type'] = $filter_action_type;
}
if ($filter_dept !== '') {
    $filters['dept'] = $filter_dept;
}
if ($filter_id_khsanxuat > 0) {
    $filters['id_khsanxuat'] = $filter_id_khsanxuat;
}
if ($filter_user_name !== '') {
    $filters['user_name'] = $filter_user_name;
}
if ($filter_date_from !== '') {
    $filters['date_from'] = $filter_date_from;
}
if ($filter_date_to !== '') {
    $filters['date_to'] = $filter_date_to;
}

$logger = getActivityLogger($connect);

$total = $logger->countActivityLogs($filters);
$total_pages = max(1, (int) ceil($total / $limit));
$page = max(1, (int) activityGetParam('page'));
$page = min($page, $total_pages);

$filters['limit'] = $limit;
$filters['offset'] = ($page - 1) * $limit;
$activities = $logger->getActivityLogs($filters);

$page_query = [
    'action_type' => $filter_action_type,
    'dept' => $filter_dept,
    'id_khsanxuat' => $filter_id_khsanxuat > 0 ? $filter_id_khsanxuat : '',
    'user_name' => $filter_user_name,
    'date_from' => $filter_date_from,
    'date_to' => $filter_date_to,
    'limit' => $limit
];

$reset_url = BASE_URL . '/pages/activity_logs.php';

$header_config = [
    'title' => 'LỊCH SỬ HOẠT ĐỘNG',
    'title_short' => 'Lịch Sử',
    'logo_path' => BASE_URL . '/img/logoht.png',
    'logo_link' => BASE_URL . '/index.php',
    'show_search' => false,
    'show_mobile_menu' => true,
    'actions' => [
        [
            'url' => BASE_URL . '/index.php',
            'icon' => BASE_URL . '/img/header/home.png',
            'title' => 'Trang chủ',
            'tooltip' => 'Trang chủ'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử hoạt động</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/activity_logs.css">
</head>
<body>
    <?php include BASE_PATH . '/components/header.php'; ?>

    <div class="container">
        <h1>Lịch sử hoạt động</h1>

        <form class="filter-form" method="GET" id="filter-form" action="<?php echo $reset_url; ?>">
            <label>
                Hoạt động
                <select name="action_type">
                    <option value="">Tất cả hoạt động</option>
                    <?php foreach ($action_types as $key => $label): ?>
                        <option value="<?php echo activityEscape($key); ?>" <?php echo $filter_action_type === $key ? 'selected' : ''; ?>>
                            <?php echo activityEscape($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Bộ phận
                <select name="dept">
                    <option value="">Tất cả bộ phận</option>
                    <?php foreach ($valid_depts as $dept_code): ?>
                        <option value="<?php echo activityEscape($dept_code); ?>" <?php echo $filter_dept === $dept_code ? 'selected' : ''; ?>>
                            <?php echo activityEscape(getDeptDisplayName($dept_code, $dept_names)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Mã hàng
                <input type="number" name="id_khsanxuat" min="1" placeholder="ID mã hàng"
                       value="<?php echo $filter_id_khsanxuat > 0 ? activityEscape($filter_id_khsanxuat) : ''; ?>">
            </label>

            <label>
                Người thực hiện
                <input type="text" name="user_name" maxlength="50" placeholder="Tên đăng nhập"
                       value="<?php echo activityEscape($filter_user_name); ?>">
            </label>

            <label>
                Từ ngày
                <input type="date" name="date_from" value="<?php echo activityEscape($filter_date_from); ?>">
            </label>

            <label>
                Đến ngày
                <input type="date" name="date_to" value="<?php echo activityEscape($filter_date_to); ?>">
            </label>

            <label>
                Số dòng/trang
                <select name="limit">
                    <?php foreach ($limit_options as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $limit === $option ? 'selected' : ''; ?>>
                            <?php echo $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="filter-actions">
                <button type="submit">Lọc</button>
                <a class="btn-reset" href="<?php echo $reset_url; ?>">Xóa bộ lọc</a>
            </div>
        </form>

        <div class="filter-info">
            Tổng <b><?php echo number_format($total, 0, ',', '.'); ?></b> bản ghi — Trang <?php echo $page; ?> / <?php echo $total_pages; ?>
        </div>

        <?php if (empty($activities)): ?>
            <div class="empty-state">
                <p>Không có hoạt động nào khớp bộ lọc.</p>
                <a href="<?php echo $reset_url; ?>">Xóa bộ lọc</a>
            </div>
        <?php else: ?>
            <table id="activity-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Hoạt động</th>
                        <th>Mã hàng</th>
                        <th>Bộ phận</th>
                        <th>Chi tiết thay đổi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td data-label="Thời gian"><?php echo activityEscape(date('d/m/Y H:i:s', strtotime($activity['action_time']))); ?></td>
                            <td data-label="Người thực hiện">
                                <div class="user-info">
                                    <div class="user-name"><?php echo activityEscape($activity['user_name']); ?></div>
                                    <div class="user-full-name"><?php echo activityEscape($activity['user_full_name']); ?></div>
                                </div>
                            </td>
                            <td data-label="Hoạt động">
                                <span class="badge <?php echo activityBadgeClass($activity['action_type']); ?>">
                                    <?php echo activityEscape(isset($action_types[$activity['action_type']]) ? $action_types[$activity['action_type']] : $activity['action_type']); ?>
                                </span>
                            </td>
                            <td data-label="Mã hàng"><?php echo activityEscape($activity['id_khsanxuat']); ?></td>
                            <td data-label="Bộ phận"><?php echo activityEscape(getDeptDisplayName($activity['dept'], $dept_names)); ?></td>
                            <td data-label="Chi tiết"><?php echo renderActivityDetails($activity); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <nav class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo activityEscape(activityLogPageUrl($page_query, $page - 1)); ?>">‹ Trước</a>
                    <?php else: ?>
                        <span class="disabled">‹ Trước</span>
                    <?php endif; ?>

                    <?php
                    $window_start = max(1, $page - 2);
                    $window_end = min($total_pages, $page + 2);

                    if ($window_start > 1) {
                        echo '<a href="' . activityEscape(activityLogPageUrl($page_query, 1)) . '">1</a>';
                        if ($window_start > 2) {
                            echo '<span class="ellipsis">…</span>';
                        }
                    }

                    for ($i = $window_start; $i <= $window_end; $i++) {
                        if ($i === $page) {
                            echo '<span class="current">' . $i . '</span>';
                        } else {
                            echo '<a href="' . activityEscape(activityLogPageUrl($page_query, $i)) . '">' . $i . '</a>';
                        }
                    }

                    if ($window_end < $total_pages) {
                        if ($window_end < $total_pages - 1) {
                            echo '<span class="ellipsis">…</span>';
                        }
                        echo '<a href="' . activityEscape(activityLogPageUrl($page_query, $total_pages)) . '">' . $total_pages . '</a>';
                    }
                    ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo activityEscape(activityLogPageUrl($page_query, $page + 1)); ?>">Sau ›</a>
                    <?php else: ?>
                        <span class="disabled">Sau ›</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/header.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/activity_logs.js"></script>
</body>
</html>
