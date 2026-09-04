<?php
$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$selected_dept_label = '';
foreach ($chart_departments as $dept_label => $dept_info) {
    if ($dept_info['code'] === $mobile_selected_dept) {
        $selected_dept_label = $dept_label;
        break;
    }
}
$dept_value_class = $selected_dept_label === '' ? ' mobile-dept__dept-value is-placeholder' : ' mobile-dept__dept-value';
$dept_value_text = $selected_dept_label === '' ? 'Chọn bộ phận của bạn…' : $selected_dept_label;
?>
<section class="mobile-dept mobile-dept__bar">
    <div class="mobile-dept__filters">
        <div class="mobile-dept__filters-row mobile-dept__filters-row--1">
            <button type="button" class="mobile-dept__dept-btn" id="mobileDeptBtn" aria-haspopup="dialog" aria-controls="mobileDeptSheet">
                <span class="mobile-dept__dept-label">Bộ phận</span>
                <span class="<?php echo trim($dept_value_class); ?>" id="mobileDeptValue"><?php echo $h($dept_value_text); ?></span>
            </button>
            <div class="mobile-dept__select-wrap">
                <span class="mobile-dept__select-label">Tháng</span>
                <select id="mobileMonthSelect" class="mobile-dept__select" aria-label="Tháng">
                    <?php foreach ($available_months as $month) : ?>
                        <?php
                            $month_name = date('m/Y', mktime(0, 0, 0, (int) $month['month'], 1, (int) $month['year']));
                            $selected = ((int) $month['month'] === (int) $selected_month && (int) $month['year'] === (int) $selected_year) ? 'selected' : '';
                        ?>
                        <option value="<?php echo (int) $month['month']; ?>"
                                data-year="<?php echo (int) $month['year']; ?>"
                                <?php echo $selected; ?>>
                            Tháng <?php echo $h($month_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mobile-dept__filters-row mobile-dept__filters-row--2">
            <div class="mobile-dept__select-wrap">
                <span class="mobile-dept__select-label">Xưởng</span>
                <select id="mobileXuongSelect" class="mobile-dept__select" aria-label="Xưởng">
                    <option value="">Tất cả</option>
                    <?php foreach ($xuong_list as $xuong_name) : ?>
                        <option value="<?php echo $h($xuong_name); ?>"><?php echo $h($xuong_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mobile-dept__select-wrap">
                <span class="mobile-dept__select-label">Line</span>
                <select id="mobileLineSelect" class="mobile-dept__select" aria-label="Line">
                    <option value="">Tất cả</option>
                </select>
            </div>
        </div>
    </div>

    <div class="mobile-dept__toolbar">
        <div class="mobile-dept__heading">
            <h4 class="mobile-dept__title">Đơn hàng</h4>
            <span class="mobile-dept__count" id="mobileDeptCount"></span>
        </div>
        <div class="mobile-dept__toggles">
            <button type="button" class="mobile-dept__toggle" id="mobileChartToggle" aria-pressed="false">
                <span class="mobile-dept__switch" aria-hidden="true"></span>
                Biểu đồ
            </button>
            <button type="button" class="mobile-dept__toggle" id="mobileOnlyOpenToggle" aria-pressed="false">
                <span class="mobile-dept__switch" aria-hidden="true"></span>
                Chưa xong
            </button>
        </div>
    </div>

    <div class="mobile-dept__empty<?php echo $mobile_selected_dept !== '' ? ' is-hidden' : ''; ?>" id="mobileDeptEmpty">
        <p class="mobile-dept__empty-text">Bạn thuộc bộ phận nào?</p>
        <button type="button" class="mobile-dept__empty-btn" id="mobileDeptEmptyBtn">Chọn bộ phận</button>
    </div>

    <div class="mobile-dept__sheet" id="mobileDeptSheet">
        <div class="mobile-dept__overlay" id="mobileDeptOverlay"></div>
        <div class="mobile-dept__sheet-panel" role="dialog" aria-label="Chọn bộ phận" aria-modal="true">
            <div class="mobile-dept__sheet-title">Chọn bộ phận của bạn</div>
            <div class="mobile-dept__sheet-rows">
                <?php foreach ($chart_departments as $dept_label => $dept_info) : ?>
                    <?php
                        $dept_code = $dept_info['code'];
                        $percent = isset($completion_rates[$dept_label]) ? (int) $completion_rates[$dept_label] : 0;
                        $row_selected = $dept_code === $mobile_selected_dept ? ' is-selected' : '';
                    ?>
                    <button type="button"
                            class="mobile-dept__sheet-row<?php echo $row_selected; ?>"
                            data-dept="<?php echo $h($dept_code); ?>">
                        <span class="mobile-dept__sheet-name"><?php echo $h($dept_label); ?></span>
                        <span class="mobile-dept__sheet-bar">
                            <span class="mobile-dept__sheet-bar-fill" style="width: <?php echo $percent; ?>%;"></span>
                        </span>
                        <span class="mobile-dept__sheet-pct"><?php echo $percent; ?>%</span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="mobile-dept mobile-dept__list<?php echo $mobile_selected_dept === '' ? ' is-hidden' : ''; ?>" id="mobileDeptList">
    <?php foreach ($mobile_rows as $card) : ?>
        <?php
            $depts_json = htmlspecialchars(json_encode($card['depts']), ENT_QUOTES, 'UTF-8');
            $ngayin_chip = date('d/m/Y', strtotime($card['ngayin']));
            $qty_chip = number_format($card['qty'], 0, ',', '.');
        ?>
        <a class="mobile-dept__card"
           href="#"
           data-stt="<?php echo (int) $card['stt']; ?>"
           data-xuong="<?php echo $h($card['xuong']); ?>"
           data-line="<?php echo $h($card['line']); ?>"
           data-po="<?php echo $h($card['po']); ?>"
           data-style="<?php echo $h($card['style']); ?>"
           data-incomplete="<?php echo $card['has_incomplete'] ? '1' : '0'; ?>"
           data-all-done="<?php echo $card['all_done'] ? '1' : '0'; ?>"
           data-depts="<?php echo $depts_json; ?>">
            <div class="mobile-dept__row1">
                <div class="mobile-dept__ident">
                    <span class="mobile-dept__ident-item"><span class="mobile-dept__lbl">PO:</span><b><?php echo $h($card['po']); ?></b></span>
                    <span class="mobile-dept__sep">-</span>
                    <span class="mobile-dept__ident-item"><span class="mobile-dept__lbl">Mã Hàng:</span><b><?php echo $h($card['style']); ?></b></span>
                    <?php if ($card['has_incomplete']) : ?>
                        <span class="mobile-dept__warn">⚠️</span>
                    <?php endif; ?>
                </div>
                <span class="mobile-dept__status"></span>
                <span class="mobile-dept__chev">›</span>
            </div>
            <div class="mobile-dept__row2">
                <span class="mobile-dept__chip"><small>Xưởng</small><?php echo $h($card['xuong']); ?></span>
                <span class="mobile-dept__chip"><small>Line</small><?php echo $h($card['line']); ?></span>
                <span class="mobile-dept__chip"><small>SL</small><?php echo $h($qty_chip); ?></span>
                <span class="mobile-dept__chip"><small>Vào</small><?php echo $h($ngayin_chip); ?></span>
            </div>
        </a>
    <?php endforeach; ?>
    <p class="mobile-dept__none is-hidden" id="mobileDeptNone">Không có đơn phù hợp</p>
</section>
