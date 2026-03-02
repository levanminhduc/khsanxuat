<?php
if (!isset($table_config) || empty($table_config)) {
    return;
}

$table_id = $table_config['id'] ?? 'dataTable';
$columns = $table_config['columns'] ?? [];
$data = $table_config['data'] ?? [];
$show_checkbox = $table_config['checkbox'] ?? false;
$drag_scroll = $table_config['drag_scroll'] ?? false;
$responsive_hint = $table_config['responsive_hint'] ?? '';
$connect = $table_config['connect'] ?? null;
$dept_names = $table_config['dept_names'] ?? [];

function getTableCellValue($row, $column, $connect, $dept_names)
{
    $key = $column['key'];
    $type = $column['type'] ?? 'text';

    switch ($type) {
        case 'dept_status':
            if (!$connect || !function_exists('checkDeptStatus')) {
                return '-';
            }
            $dept = $column['dept'] ?? $key;
            $completed = checkDeptStatus($connect, $row['stt'], $dept);
            $deadline = function_exists('getEarliestDeadline') ? getEarliestDeadline($connect, $row['stt'], $dept) : null;
            $status_class = $completed ? 'status-completed' : 'status-pending';
            $status_text = $completed ? '✓' : ($deadline ? date('d/m', strtotime($deadline)) : '-');
            return '<span class="' . $status_class . '">' . $status_text . '</span>';

        case 'date':
            return !empty($row[$key]) ? date('d/m/Y', strtotime($row[$key])) : '-';

        case 'link':
            $template = $column['link_template'] ?? '';
            $value = $row[$key] ?? '';
            if (!empty($template) && !empty($value)) {
                $href = str_replace('{' . $key . '}', urlencode($value), $template);
                foreach ($row as $k => $v) {
                    $href = str_replace('{' . $k . '}', urlencode($v ?? ''), $href);
                }
                $class = $column['link_class'] ?? 'style-link';
                return '<a href="' . htmlspecialchars($href) . '" class="' . $class . '">' . htmlspecialchars($value) . '</a>';
            }
            return htmlspecialchars($value);

        case 'number':
            return number_format($row[$key] ?? 0);

        default:
            return htmlspecialchars($row[$key] ?? '');
    }
}
?>

<div class="data-table-container" <?= $drag_scroll ? 'data-drag-scroll="true"' : '' ?>>
    <?php if (!empty($responsive_hint)): ?>
        <div class="scroll-hint"><?= htmlspecialchars($responsive_hint) ?></div>
    <?php endif; ?>

    <table class="data-table" id="<?= htmlspecialchars($table_id) ?>">
        <thead>
            <tr>
                <?php if ($show_checkbox): ?>
                    <th style="width: 30px;"><input type="checkbox" id="selectAll"></th>
                <?php endif; ?>
                <?php foreach ($columns as $col): ?>
                    <th style="<?= isset($col['width']) ? 'width:' . $col['width'] . ';' : '' ?>">
                        <?= htmlspecialchars($col['label']) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="<?= count($columns) + ($show_checkbox ? 1 : 0) ?>">Không có dữ liệu</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data as $index => $row): ?>
                    <tr data-id="<?= $row['stt'] ?? $index ?>">
                        <?php if ($show_checkbox): ?>
                            <td><input type="checkbox" class="row-checkbox" value="<?= $row['stt'] ?? $index ?>"></td>
                        <?php endif; ?>
                        <?php foreach ($columns as $col): ?>
                            <td><?= getTableCellValue($row, $col, $connect, $dept_names) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
