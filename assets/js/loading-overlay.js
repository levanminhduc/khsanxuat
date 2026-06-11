// Shared blocking-loading overlay. Three declarative modes:
//   form[data-loading]            -> show on submit (POST full-page; clears on reload)
//   a[data-loading-download]      -> show on click; clear via fileDownloadToken cookie poll
//   a[data-loading-nav]           -> show on click; clears when next page loads
// Public API: window.LoadingOverlay.show(text) / .hide()
(function () {
    function overlay() { return document.getElementById('loadingOverlay'); }

    function show(text) {
        var el = overlay();
        if (!el) return;
        var textEl = el.querySelector('.loading-text');
        if (textEl && text) textEl.textContent = text;
        el.style.display = 'flex';
        el.setAttribute('aria-hidden', 'false');
    }

    function hide() {
        var el = overlay();
        if (!el) return;
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
    }

    window.LoadingOverlay = { show: show, hide: hide };
    window.showLoadingOverlay = show; // backward-compat
    window.hideLoadingOverlay = hide; // backward-compat

    function getCookie(name) {
        var parts = document.cookie.split('; ');
        for (var i = 0; i < parts.length; i++) {
            var kv = parts[i].split('=');
            if (kv[0] === name) return decodeURIComponent(kv[1] || '');
        }
        return null;
    }

    function deleteCookie(name) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    }

    function bindForms() {
        document.querySelectorAll('form[data-loading]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                // Tôn trọng handler chạy trước (vd onsubmit="return confirm(...)"):
                // nếu submit đã bị huỷ thì không hiện overlay (tránh kẹt overlay khi user bấm Cancel).
                if (e.defaultPrevented) { return; }
                if (form.__submitting) { e.preventDefault(); return; }
                form.__submitting = true;
                show(form.getAttribute('data-loading-text') || 'Đang xử lý...');
                var btn = form.querySelector('button[type="submit"]');
                if (btn) btn.disabled = true; // data is in hidden inputs, not the button
            });
        });
    }

    function bindDownloads() {
        document.querySelectorAll('a[data-loading-download]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var token = String(Date.now()) + String(Math.floor(Math.random() * 1e6));
                var url = link.href + (link.href.indexOf('?') === -1 ? '?' : '&') + 'download_token=' + token;
                show(link.getAttribute('data-loading-text') || 'Đang chuẩn bị tải xuống...');

                var started = Date.now();
                var timer = setInterval(function () {
                    if (getCookie('fileDownloadToken') === token) {
                        clearInterval(timer);
                        deleteCookie('fileDownloadToken');
                        hide();
                    } else if (Date.now() - started > 60000) { // 60s safety net
                        clearInterval(timer);
                        hide();
                    }
                }, 500);

                window.location.href = url; // triggers the download
            });
        });
    }

    function bindNav() {
        document.querySelectorAll('a[data-loading-nav]').forEach(function (link) {
            link.addEventListener('click', function () {
                show(link.getAttribute('data-loading-text') || 'Đang tải trang...');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindForms();
        bindDownloads();
        bindNav();
    });

    // bfcache: hide overlay if user navigates back to a cached page.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) hide();
    });
})();
