<?php
if (!isset($filter_config) || empty($filter_config)) {
    return;
}

$available_months = $filter_config['available_months'] ?? [];
$selected_month = $filter_config['selected_month'] ?? date('m');
$selected_year = $filter_config['selected_year'] ?? date('Y');
$form_action = $filter_config['form_action'] ?? '';
$extra_params = $filter_config['extra_params'] ?? [];

// Vietnamese month names
$month_names = [
    1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3', 4 => 'Tháng 4',
    5 => 'Tháng 5', 6 => 'Tháng 6', 7 => 'Tháng 7', 8 => 'Tháng 8',
    9 => 'Tháng 9', 10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12'
];
?>

<div class="month-filter">
    <form method="GET" action="<?= htmlspecialchars($form_action) ?>" class="month-filter-form">
        <?php foreach ($extra_params as $key => $value): ?>
            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
        <?php endforeach; ?>

        <select name="month" class="month-select" onchange="this.form.submit()">
            <?php foreach ($available_months as $m): ?>
                <?php
                $month_val = $m['month'];
                $year_val = $m['year'];
                $is_selected = ($month_val == $selected_month && $year_val == $selected_year);
                $label = $month_names[$month_val] . '/' . $year_val;
                ?>
                <option value="<?= $month_val ?>" data-year="<?= $year_val ?>" <?= $is_selected ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="year" id="yearInput" value="<?= $selected_year ?>">
    </form>
</div>

<script>
(function() {
    var monthSelect = document.querySelector('.month-select');
    var yearInput = document.getElementById('yearInput');

    if (monthSelect && yearInput) {
        monthSelect.addEventListener('change', function() {
            var option = this.options[this.selectedIndex];
            yearInput.value = option.dataset.year;
        });
    }
})();
</script>
