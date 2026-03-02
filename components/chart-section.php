<?php
if (!isset($chart_config) || empty($chart_config)) {
    return;
}

$chart_id = $chart_config['id'] ?? 'mainChart';
$chart_title = $chart_config['title'] ?? 'Biểu đồ';
$month = $chart_config['month'] ?? date('m');
$year = $chart_config['year'] ?? date('Y');
$show_evaluation = $chart_config['show_evaluation'] ?? false;
$best = $chart_config['best_performer'] ?? null;
$worst = $chart_config['worst_performer'] ?? null;
?>

<div class="chart-container">
    <h3 class="chart-title"><?= htmlspecialchars($chart_title) ?> - Tháng <?= $month ?>/<?= $year ?></h3>
    <canvas id="<?= htmlspecialchars($chart_id) ?>"></canvas>

    <?php if ($show_evaluation && ($best || $worst)): ?>
        <div class="evaluation-container">
            <?php if ($best): ?>
                <div class="best-performer">
                    <div class="eval-header">
                        <span class="eval-icon success">★</span>
                        <span class="eval-title">Bộ phận tốt nhất</span>
                    </div>
                    <div class="eval-content">
                        <span class="eval-name"><?= htmlspecialchars($best['name']) ?></span>
                        <span class="eval-percent"><?= $best['percent'] ?>%</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($worst): ?>
                <div class="worst-performer">
                    <div class="eval-header">
                        <span class="eval-icon warning">!</span>
                        <span class="eval-title">Bộ phận cần cải thiện</span>
                    </div>
                    <div class="eval-content">
                        <span class="eval-name"><?= htmlspecialchars($worst['name']) ?></span>
                        <span class="eval-percent"><?= $worst['percent'] ?>%</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
