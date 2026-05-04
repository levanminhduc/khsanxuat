<?php
$config = $back_to_top_config ?? [];
$threshold = $config['threshold'] ?? 300;
$position = ($config['position'] ?? 'right') === 'left' ? 'left: 30px;' : 'right: 30px;';
$color = $config['color'] ?? '#003366';
?>
<button id="backToTopBtn" type="button" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Về đầu trang" aria-label="Về đầu trang" style="
    display: none;
    position: fixed;
    bottom: 30px;
    <?php echo $position; ?>
    z-index: 999;
    width: 50px;
    height: 50px;
    border: none;
    border-radius: 50%;
    background-color: <?php echo htmlspecialchars($color); ?>;
    color: white;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    transition: opacity 0.3s, transform 0.3s;
">
    <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="display: block;">
        <path d="M12 5L5 12M12 5L19 12M12 5V19" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</button>
<script>
(function() {
    var btn = document.getElementById('backToTopBtn');
    if (!btn) return;
    var threshold = <?php echo intval($threshold); ?>;
    window.addEventListener('scroll', function() {
        btn.style.display = window.scrollY > threshold ? 'flex' : 'none';
    });
    btn.addEventListener('mouseenter', function() { this.style.transform = 'scale(1.1)'; });
    btn.addEventListener('mouseleave', function() { this.style.transform = 'scale(1)'; });
})();
</script>
