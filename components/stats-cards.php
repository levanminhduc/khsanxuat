<?php
if (!isset($stats_config) || empty($stats_config)) {
    return;
}
?>

<div class="stats-container">
    <?php foreach ($stats_config as $card): ?>
        <div class="stat-card <?= $card['type'] ?? 'total' ?>">
            <div class="stat-title"><?= htmlspecialchars($card['title']) ?></div>
            <div class="stat-value"><?= htmlspecialchars($card['value']) ?></div>
            <?php if (isset($card['percent']) && $card['percent'] !== null): ?>
                <div class="stat-percent">(<?= $card['percent'] ?>%)</div>
            <?php endif; ?>
            <?php if (isset($card['progress']) && $card['progress'] !== null): ?>
                <div class="stat-progress">
                    <div class="progress-bar" style="width: <?= $card['progress'] ?>%"></div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
