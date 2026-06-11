// Overlay loading chặn thao tác khi submit form upload (full-page POST).
// Tự bind mọi form[data-loading]. Overlay tự mất khi trang reload/redirect.
(function () {
    function showLoadingOverlay(text) {
        var overlay = document.getElementById('loadingOverlay');
        if (!overlay) return;
        var textEl = overlay.querySelector('.loading-text');
        if (textEl && text) textEl.textContent = text;
        overlay.style.display = 'flex';
    }

    function hideLoadingOverlay() {
        var overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'none';
    }

    window.showLoadingOverlay = showLoadingOverlay;
    window.hideLoadingOverlay = hideLoadingOverlay;

    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('form[data-loading]');
        forms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                // submit chỉ kích hoạt sau khi required + onclick(prepareSubmit) đã pass.
                if (form.__submitting) {
                    e.preventDefault(); // chặn double-submit
                    return;
                }
                form.__submitting = true;
                var text = form.getAttribute('data-loading-text') || 'Đang xử lý...';
                showLoadingOverlay(text);
                var btn = form.querySelector('button[type="submit"]');
                if (btn) btn.disabled = true; // chống click lại; data nằm ở hidden input nên không mất
            });
        });
    });
})();
