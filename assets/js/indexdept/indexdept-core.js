/* ==========================================================
   indexdept-core.js — nền tảng cho trang đánh giá bộ phận.

   Định nghĩa namespace window.IndexDept (viết tắt "ns") mà mọi file
   indexdept-*.js khác dùng để gọi chéo nhau, gom helper DOM/chuỗi/trạng thái,
   phần vỏ dùng chung của modal, và MỘT điểm khởi tạo duy nhất cho cả trang.

   PHẢI nạp TRƯỚC mọi file indexdept-*.js khác (xem indexdept.php).
   ========================================================== */
(function (ns) {

    /* ---------- Modal "Thêm tiêu chí": chỉ đóng/mở, không có logic riêng ---------- */
    function openModal() {
        showElement(document.getElementById('addCriteriaModal'), 'block');
        setTimeout(function() {
            const firstField = document.getElementById('thutu') || document.getElementById('nhom');
            if (firstField) {
                firstField.focus();
            }
        }, 0);
    }

    function closeModal() {
        hideElement(document.getElementById('addCriteriaModal'));
    }

    /* ---------- Vỏ modal dùng chung ---------- */

    // Mỗi file tính năng tự đăng ký hàm đóng modal của mình; core không cần
    // biết trước 5 modal là những modal nào.
    const backdropCloseHandlers = {};

    function registerModalCloseHandler(modalId, handler) {
        backdropCloseHandlers[modalId] = handler;
    }

    function getBackdropCloseHandler(modalId) {
        return backdropCloseHandlers[modalId] || null;
    }

    function handleModalBackdropClick(event) {
        if (event.target !== event.currentTarget) return;

        const closeHandler = getBackdropCloseHandler(event.currentTarget.id);
        if (closeHandler) {
            closeHandler();
        }
    }

    function initializeModalBackdropDismissal() {
        document.querySelectorAll('.indexdept-modern-modal').forEach(function(modal) {
            modal.addEventListener('click', handleModalBackdropClick);
        });
    }

    function syncModernModalOpenState() {
        const hasOpenModernModal = Array.prototype.some.call(
            document.querySelectorAll('.indexdept-modern-modal'),
            function(modal) {
                return !modal.hidden && modal.style.display !== 'none';
            }
        );

        document.body.classList.toggle('indexdept-modal-open', hasOpenModernModal);
    }

    /* ---------- Helper DOM / chuỗi / trạng thái ---------- */
    function showElement(element, displayValue) {
        if (!element) return;
        element.hidden = false;
        element.style.display = element.classList.contains('indexdept-modern-modal') ? 'flex' : (displayValue || 'block');
        syncModernModalOpenState();
    }

    function hideElement(element) {
        if (!element) return;
        element.style.display = 'none';
        element.hidden = true;
        syncModernModalOpenState();
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function(char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function buildLoadingIcon(sizeClass, altText) {
        return '<img src="img/loading.gif" class="loading-icon ' + (sizeClass || 'loading-icon--sm') + '" alt="' + escapeHtml(altText || 'Đang tải') + '">';
    }

    function buildStatusMessage(type, message, options) {
        const settings = options || {};
        const classes = ['status-panel', 'status-panel--' + type];
        if (settings.loading) {
            classes.push('status-panel--loading');
        }

        return '<div class="' + classes.join(' ') + '">' +
            (settings.loading ? buildLoadingIcon(settings.iconSize || 'loading-icon--sm', settings.altText || message) : '') +
            escapeHtml(message) +
            '</div>';
    }

    function setStatusMessage(container, type, message, options) {
        if (!container) return;
        showElement(container, 'block');
        container.innerHTML = buildStatusMessage(type, message, options);
    }

    function clearDeadlineState(dateDisplay) {
        if (!dateDisplay) return;
        dateDisplay.classList.remove('deadline-date--loading', 'deadline-date--success', 'deadline-date--error');
    }

    function setDeadlineState(dateDisplay, state, html) {
        if (!dateDisplay) return;
        clearDeadlineState(dateDisplay);
        if (state) {
            dateDisplay.classList.add('deadline-date--' + state);
        }
        dateDisplay.innerHTML = html;
    }

    function setProgressBarState(progressBar, percent) {
        if (!progressBar) return;
        progressBar.classList.remove('progress-bar--low', 'progress-bar--medium', 'progress-bar--high');

        if (percent < 30) {
            progressBar.classList.add('progress-bar--low');
        } else if (percent < 70) {
            progressBar.classList.add('progress-bar--medium');
        } else {
            progressBar.classList.add('progress-bar--high');
        }

        progressBar.style.width = percent + '%';
        progressBar.setAttribute('data-progress', percent);
        progressBar.textContent = Math.round(percent) + '%';
    }

    function buildTableMessageRow(colspan, type, message) {
        return '<tr><td colspan="' + colspan + '" class="table-message table-message--' + type + '">' + escapeHtml(message) + '</td></tr>';
    }

    function flashRow(row, className, duration) {
        if (!row) return;
        row.classList.add(className);
        setTimeout(function() {
            row.classList.remove(className);
        }, duration || 2000);
    }

    function buildRequiredImageWarning(uploadUrl) {
        const safeUrl = escapeHtml(uploadUrl);
        return '<div class="warning-message__text warning-message__text--error">(*) Tiêu chí này bắt buộc phải có hình ảnh đính kèm</div>' +
            '<div class="warning-message__actions"><a href="' + safeUrl + '" class="warning-message__link"><i class="fas fa-upload icon-inline"></i>Upload ảnh</a></div>';
    }

    function buildImageUploadedMessage(uploadUrl) {
        const safeUrl = escapeHtml(uploadUrl);
        return '<div class="warning-message__text warning-message__text--success"><i class="fas fa-check-circle icon-inline"></i>Đã upload hình ảnh cho tiêu chí này</div>' +
            '<div class="warning-message__actions"><a href="' + safeUrl + '" class="warning-message__link"><i class="fas fa-images icon-inline"></i>Xem/Quản lý hình ảnh</a></div>';
    }

    function getCsrfToken() {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return tokenInput ? tokenInput.value : '';
    }

    /* ---------- Khởi tạo ----------
       Trước đây có 7 listener DOMContentLoaded rải khắp file, thứ tự chạy phụ
       thuộc vị trí dòng. Giờ chỉ còn một listener, gọi các init* theo đúng thứ
       tự thao tác của bản cũ. File nào chưa nạp thì init* của nó vắng mặt và
       được bỏ qua. */
    function runInit() {
        initializeModalBackdropDismissal();

        [
            'initScoreOptions',
            'initDeadline',
            'initStaff',
            'initEvaluation',
            'initMobileCards'
        ].forEach(function (name) {
            if (typeof ns[name] === 'function') {
                ns[name]();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', runInit);

    /* ---------- Công khai ---------- */
    ns.showElement = showElement;
    ns.hideElement = hideElement;
    ns.escapeHtml = escapeHtml;
    ns.buildLoadingIcon = buildLoadingIcon;
    ns.buildStatusMessage = buildStatusMessage;
    ns.setStatusMessage = setStatusMessage;
    ns.clearDeadlineState = clearDeadlineState;
    ns.setDeadlineState = setDeadlineState;
    ns.setProgressBarState = setProgressBarState;
    ns.buildTableMessageRow = buildTableMessageRow;
    ns.flashRow = flashRow;
    ns.buildRequiredImageWarning = buildRequiredImageWarning;
    ns.buildImageUploadedMessage = buildImageUploadedMessage;
    ns.getCsrfToken = getCsrfToken;
    ns.registerModalCloseHandler = registerModalCloseHandler;

    // Gọi từ onclick= trong views/indexdept/partials/modal-add-criteria.php
    // và views/indexdept/partials/action-header.php.
    window.openModal = openModal;
    window.closeModal = closeModal;

    registerModalCloseHandler('addCriteriaModal', closeModal);
})(window.IndexDept = window.IndexDept || {});
