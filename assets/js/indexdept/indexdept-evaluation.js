/* ==========================================================
   indexdept-evaluation.js — bảng đánh giá: chấm điểm, tổng điểm, kiểm tra
   hình ảnh bắt buộc, tự động lưu, và giao diện thẻ ở mobile.
   Nạp SAU indexdept-core.js.
   ========================================================== */
(function (ns) {
    // Helper dùng chung, lấy từ indexdept-core.js (nạp trước file này).
    const hideElement = ns.hideElement,
          setProgressBarState = ns.setProgressBarState,
          buildRequiredImageWarning = ns.buildRequiredImageWarning,
          buildImageUploadedMessage = ns.buildImageUploadedMessage;
    const INDEXDEPT_BOOTSTRAP = window.INDEXDEPT_BOOTSTRAP || {};

    function buildCheckImageUrl(tieuchiId) {
        const id = encodeURIComponent(INDEXDEPT_BOOTSTRAP.id || '');
        return window.BASE_URL + '/api/ajax_check_tieuchi_image.php?id_khsanxuat=' + id + '&id_tieuchi=' + encodeURIComponent(tieuchiId);
    }

    function buildAutoSelectImageUrl(tieuchiId) {
        const id = encodeURIComponent(INDEXDEPT_BOOTSTRAP.id || '');
        const dept = encodeURIComponent(INDEXDEPT_BOOTSTRAP.dept || '');
        return 'indexdept.php?dept=' + dept + '&id=' + id + '&autoselect_image=1&tieuchi_id=' + encodeURIComponent(tieuchiId);
    }

    // tự ẩn (fade) sau AUTO_HIDE_SUCCESS_MS để không che nội dung.
    const AUTO_HIDE_SUCCESS_MS = 3000;
    function autoHideSuccessMessage() {
        const banner = document.querySelector('.success-message');
        if (!banner) return;
        setTimeout(function () {
            banner.style.transition = 'opacity 0.4s ease';
            banner.style.opacity = '0';
            setTimeout(function () { banner.remove(); }, 400);
        }, AUTO_HIDE_SUCCESS_MS);
    }


    // Hàm cập nhật trạng thái checkbox khi thay đổi điểm
    function updateStatus(element) {
        if (!element) return;

        const tieuchi_id = element.getAttribute('data-tieuchi-id');
        const hiddenField = document.getElementById('da_thuchien_' + tieuchi_id);
        const label = document.getElementById('checkbox_label_' + tieuchi_id);
        const diem = parseFloat(element.value);

        if (diem > 0) {
            label.classList.remove('unchecked');
            label.classList.add('checked');
            label.innerHTML = '<span class="checkmark">✓</span>';
            hiddenField.value = 1;
        } else {
            label.classList.remove('checked');
            label.classList.add('unchecked');
            label.innerHTML = '<span class="checkmark">X</span>';
            hiddenField.value = 0;
        }

        // Cập nhật tổng điểm sau khi thay đổi điểm
        updateTotalPoints();

        // Auto-save điểm
        window._autoSaveScore(tieuchi_id, element.value);
    }

    // Hàm cập nhật tổng điểm
    function updateTotalPoints() {
        let totalPoints = 0;
        const maxPoints = (INDEXDEPT_BOOTSTRAP.maxPossiblePoints || 0);

        // Tính tổng điểm từ tất cả các dropdown
        document.querySelectorAll('.diem-dropdown').forEach(function(select) {
            totalPoints += parseFloat(select.value);
        });

        // Cập nhật hiển thị tổng điểm
        const totalPointsElement = document.getElementById('total_points');
        totalPointsElement.innerHTML = number_format(totalPoints, 1) + '/' + number_format(maxPoints, 1);

        // Cập nhật thanh tiến trình
        const percent = (maxPoints > 0) ? (totalPoints / maxPoints) * 100 : 0;
        const progressBar = document.querySelector('.progress-bar');
        setProgressBarState(progressBar, percent);
    }

    // Hàm định dạng số với số thập phân
    function number_format(number, decimals) {
        // Đảm bảo number là số
        number = parseFloat(number);
        if (isNaN(number)) {
            return "0";
        }

        // Định dạng số với số thập phân
        return number.toFixed(decimals);
    }

    // Hàm kiểm tra có hình ảnh cho tiêu chí hay không
    function checkImageForTieuchi(tieuchiId, selectElement) {
        console.log("Kiểm tra hình ảnh cho tiêu chí: " + tieuchiId);
        var warningDiv = document.getElementById('warning-tieuchi-' + tieuchiId);

        if (!warningDiv) {
            warningDiv = document.createElement('div');
            warningDiv.id = 'warning-tieuchi-' + tieuchiId;
            warningDiv.className = 'warning-message';
            selectElement.parentNode.appendChild(warningDiv);
        }

        // AJAX kiểm tra xem tiêu chí này đã có hình ảnh chưa
        var xhr = new XMLHttpRequest();
        xhr.open('GET', buildCheckImageUrl(tieuchiId), true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);

                if (response.success) {
                    if (!response.has_image) {
                        // Tạo URL cho liên kết upload hình ảnh sử dụng tham số tự động chọn tiêu chí
                        var uploadUrl = buildAutoSelectImageUrl(tieuchiId);

                        // Hiển thị cảnh báo và liên kết để upload hình ảnh
                        warningDiv.innerHTML = buildRequiredImageWarning(uploadUrl);

                        // Nếu có điểm > 0, reset về 0
                        if (parseFloat(selectElement.value) > 0) {
                            selectElement.value = '0';

                            // Cập nhật trạng thái checkbox thành X đỏ
                            const label = document.getElementById('checkbox_label_' + tieuchiId);
                            const hiddenField = document.getElementById('da_thuchien_' + tieuchiId);

                            if (label) {
                                label.classList.remove('checked');
                                label.classList.add('unchecked');
                                label.innerHTML = '<span class="checkmark">X</span>';
                            }
                            if (hiddenField) {
                                hiddenField.value = '0';
                            }
                        }

                        // Disable các lựa chọn có giá trị lớn hơn 0 (trừ khi là 999)
                        for (var i = 0; i < selectElement.options.length; i++) {
                            var optionValue = parseFloat(selectElement.options[i].value);
                            if (optionValue > 0 && optionValue !== 999) {
                                selectElement.options[i].disabled = true;
                            }
                        }
                    } else {
                        // Đã có hình ảnh, hiển thị thông báo thành công và enable tất cả lựa chọn
                        var uploadUrl = buildAutoSelectImageUrl(tieuchiId);
                        warningDiv.innerHTML = buildImageUploadedMessage(uploadUrl);

                        for (var i = 0; i < selectElement.options.length; i++) {
                            selectElement.options[i].disabled = false;
                        }
                    }
                }
            }
        };
        xhr.send();
    }

    // Thêm hàm kiểm tra tiêu chí bắt buộc hình ảnh
    function isRequiredImageCriteria(tieuchiId) {
        const requiredCriteria = (INDEXDEPT_BOOTSTRAP.requiredCriteria || []);
        return requiredCriteria.includes(parseInt(tieuchiId));
    }

    /* ---------- Khởi tạo (core gọi) ---------- */
    ns.initEvaluation = function () {
        autoHideSuccessMessage();

        // Khởi tạo các select điểm đánh giá
        const diemSelects = document.querySelectorAll('.diem-dropdown');
        diemSelects.forEach(select => {
            select.addEventListener('change', function() {
                updateStatus(this);
            });
        });

        // Cập nhật trạng thái checkbox ban đầu
        document.querySelectorAll('.diem-dropdown').forEach(element => {
            const diem = parseFloat(element.value);
            if (diem > 0) {
                const tieuchi_id = element.getAttribute('data-tieuchi-id');
                const hiddenField = document.getElementById('da_thuchien_' + tieuchi_id);
                if (hiddenField) {
                    hiddenField.value = 1;
                }
            }
        });

        // Cập nhật tổng điểm khi trang được tải
        document.querySelectorAll('.progress-bar[data-progress]').forEach(function(bar) {
            const percent = parseFloat(bar.getAttribute('data-progress') || '0');
            setProgressBarState(bar, percent);
        });
        updateTotalPoints();

        ['update_status', 'default_settings_status', 'staff_status'].forEach(function(id) {
            const element = document.getElementById(id);
            if (element && element.hidden) {
                hideElement(element);
            }
        });

        // Kiểm tra tiêu chí 131 (tiêu chí số 5 của Kho Phụ Liệu)
        if ((INDEXDEPT_BOOTSTRAP.dept || '') === 'kho') {
        // Lấy select box cho tiêu chí 131 - sửa lại selector cho chính xác
        const select131 = document.querySelector('select[data-tieuchi-id="131"]');

        if (select131) {
            // Thêm event listener để kiểm tra mỗi khi thay đổi giá trị
            select131.addEventListener('change', function() {
                const selectedValue = parseFloat(this.value);
                if (selectedValue > 0) {
                    checkImageForTieuchi(131, this);
                }
            });

            // Kiểm tra ngay khi trang load
            checkImageForTieuchi(131, select131);

            // Thêm ghi chú cho tiêu chí này
            const tieuchiRow = select131.closest('tr');
            if (tieuchiRow) {
                const noteColumn = tieuchiRow.querySelector('td:last-child');
                if (noteColumn) {
                    /*
                    const noteText = document.createElement('div');
                    noteText.className = 'image-required-warning';
                    noteText.innerHTML = `
                        <div class="image-required-warning">
                            <strong class="image-required-warning__title">(*) Tiêu chí này bắt buộc phải có hình ảnh đính kèm</strong>
                        </div>
                        <a href="#"
                           class="upload-image-btn">
                            <i class="fas fa-upload icon-inline"></i>
                            Upload hình ảnh
                        </a>
                    `;

                    // Thêm style cho nút upload
                    noteColumn.prepend(noteText);
                    */
                }
            }
        }
        }

        // Kiểm tra tất cả các tiêu chí bắt buộc hình ảnh khi load trang
        const allSelects = document.querySelectorAll('select[data-tieuchi-id]');
        allSelects.forEach(select => {
            const tieuchiId = select.getAttribute('data-tieuchi-id');
            if (isRequiredImageCriteria(tieuchiId)) {
                checkImageForTieuchi(tieuchiId, select);
            }
        });

        // Tìm form đánh giá
        const danhgiaForm = document.querySelector('form[name="danhgia_form"]');

        if (danhgiaForm) {
            danhgiaForm.addEventListener('submit', function(e) {
                // Lấy danh sách tiêu chí bắt buộc hình ảnh từ PHP đã tạo ở trên
                const requiredCriteria = (INDEXDEPT_BOOTSTRAP.requiredCriteria || []);
                let hasError = false;
                let firstErrorTieuchiId = null;

                // Kiểm tra từng tiêu chí bắt buộc hình ảnh
                for (let i = 0; i < requiredCriteria.length; i++) {
                    const tieuchiId = requiredCriteria[i];
                    const diemInput = document.querySelector('input[name="diem[' + tieuchiId + ']"]');
                    const diemSelect = document.querySelector('select[data-tieuchi-id="' + tieuchiId + '"]');

                    // Lấy giá trị điểm, ưu tiên từ input (nếu có), nếu không thì từ select
                    let diemValue = 0;
                    if (diemInput) {
                        diemValue = parseFloat(diemInput.value) || 0;
                    } else if (diemSelect) {
                        diemValue = parseFloat(diemSelect.value) || 0;
                    }

                    // Chỉ kiểm tra tiêu chí được đánh giá (điểm > 0)
                    if (diemValue > 0 && diemValue !== 999) {
                        // Kiểm tra AJAX xem có hình ảnh chưa (sử dụng AJAX đồng bộ để đảm bảo kiểm tra xong trước khi tiếp tục)
                        const xhr = new XMLHttpRequest();
                        xhr.open('GET', buildCheckImageUrl(tieuchiId), false);
                        xhr.send();

                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success && !response.has_image) {
                                hasError = true;
                                if (!firstErrorTieuchiId) {
                                    firstErrorTieuchiId = tieuchiId;
                                }
                            }
                        } catch (error) {
                            console.error('Lỗi khi kiểm tra hình ảnh cho tiêu chí ' + tieuchiId + ':', error);
                        }
                    }
                }

                // Nếu có lỗi, hiển thị thông báo và chuyển hướng đến trang upload hình ảnh
                if (hasError && firstErrorTieuchiId) {
                    e.preventDefault(); // Ngăn form submit
                    // Tạo URL với tham số tự động chọn tiêu chí
                    const uploadUrl = buildAutoSelectImageUrl(firstErrorTieuchiId);
                    alert('Bạn cần đính kèm hình ảnh cho tiêu chí ID ' + firstErrorTieuchiId + ' trước khi cập nhật điểm đánh giá.');
                    window.location.href = uploadUrl;
                    return false;
                }
            });
        }

        const resetTimestamp = localStorage.getItem('reset_timestamp');
        const tieuchiReset = localStorage.getItem('tieuchi_score_reset');
        const khsanxuatId = localStorage.getItem('khsanxuat_id');
        const deptStored = localStorage.getItem('dept');

        // Chỉ xử lý nếu có dữ liệu và là đúng trang hiện tại
        if (resetTimestamp && tieuchiReset && khsanxuatId && deptStored) {
            const currentId = String(INDEXDEPT_BOOTSTRAP.id || '');
            const currentDept = (INDEXDEPT_BOOTSTRAP.dept || '');

            // Kiểm tra xem đây có phải là đúng trang indexdept.php đang hiển thị sản phẩm bị reset không
            if (khsanxuatId === currentId && deptStored === currentDept) {
                // Kiểm tra xem reset có xảy ra trong vòng 10 phút không
                const now = Date.now();
                const resetTime = parseInt(resetTimestamp);

                if ((now - resetTime) < 600000) { // 10 phút = 600000 ms
                    // Tìm và cập nhật trạng thái UI cho tiêu chí bị reset
                    const diemSelect = document.querySelector('select[data-tieuchi-id="' + tieuchiReset + '"]');

                    if (diemSelect) {
                        // Reset giá trị điểm về 0
                        diemSelect.value = '0';

                        // Cập nhật checkbox thành X đỏ
                        const label = document.getElementById('checkbox_label_' + tieuchiReset);
                        const hiddenField = document.getElementById('da_thuchien_' + tieuchiReset);

                        if (label) {
                            label.classList.remove('checked');
                            label.classList.add('unchecked');
                            label.innerHTML = '<span class="checkmark">X</span>';
                        }

                        if (hiddenField) {
                            hiddenField.value = '0';
                        }

                        // Kiểm tra lại hình ảnh cho tiêu chí này
                        if (isRequiredImageCriteria(tieuchiReset)) {
                            checkImageForTieuchi(tieuchiReset, diemSelect);
                        }

                        // Cập nhật tổng điểm nếu có hàm này
                        if (typeof updateTotalPoints === 'function') {
                            updateTotalPoints();
                        }

                        // Hiển thị thông báo cho người dùng
                        alert('Điểm đánh giá của tiêu chí ID ' + tieuchiReset + ' đã được reset về 0 do không còn hình ảnh đính kèm.');

                        // Xóa dữ liệu localStorage sau khi đã xử lý
                        localStorage.removeItem('tieuchi_score_reset');
                        localStorage.removeItem('khsanxuat_id');
                        localStorage.removeItem('dept');
                        localStorage.removeItem('reset_timestamp');
                    }
                } else {
                    // Xóa dữ liệu localStorage nếu đã quá 10 phút
                    localStorage.removeItem('tieuchi_score_reset');
                    localStorage.removeItem('khsanxuat_id');
                    localStorage.removeItem('dept');
                    localStorage.removeItem('reset_timestamp');
                }
            }
        }

        // Bản cũ đăng ký một listener DOMContentLoaded lồng bên trong; ở đây
        // init đã chạy trong DOMContentLoaded nên gọi thẳng.
        // Auto-redirect to image handler when requested via query string
        if (INDEXDEPT_BOOTSTRAP.autoSelectImage && INDEXDEPT_BOOTSTRAP.autoSelectTieuchiId) {
            const autoSelectTieuchiId = INDEXDEPT_BOOTSTRAP.autoSelectTieuchiId;
                window.location.href = window.BASE_URL + '/pages/image_handler.php?id=' + encodeURIComponent(INDEXDEPT_BOOTSTRAP.id || '') + '&dept=' + encodeURIComponent(INDEXDEPT_BOOTSTRAP.dept || '') + '&tieuchi_id=' + encodeURIComponent(autoSelectTieuchiId);
        }
    };

    /* ---------- Công khai ---------- */
    // Gọi từ onchange= của select điểm trong views/indexdept/partials/evaluation-table.php.
    window.updateStatus = updateStatus;
})(window.IndexDept = window.IndexDept || {});

/* ---------- Toàn cục: gọi từ onclick= và từ save_score.php ---------- */
// Toggle hiển thị/ẩn tiêu chí đã hoàn thành
function toggleCompletedRows() {
    const table = document.querySelector('#danhgiaForm .evaluation-table');
    const btn = document.getElementById('toggleCompletedRows');
    const label = document.getElementById('toggleCompletedLabel');

    if (!table) return;

    const isShowing = table.classList.toggle('show-completed');
    btn.classList.toggle('active', isShowing);
    label.textContent = isShowing ? 'Ẩn đã hoàn thành' : 'Hiện đã hoàn thành';
}

// Auto-save score khi user chọn điểm
window._autoSaveScore = function(id_tieuchi, diem) {
    var form = document.getElementById('danhgiaForm');
    if (!form) return;
    var csrfToken = form.querySelector('input[name="csrf_token"]').value;
    var body = new URLSearchParams({
        id_sanxuat: INDEXDEPT_BOOTSTRAP.id,
        id_tieuchi: id_tieuchi,
        dept: INDEXDEPT_BOOTSTRAP.dept,
        diem_danhgia: diem,
        csrf_token: csrfToken
    });

    fetch(window.BASE_URL + '/includes/indexdept/save_score.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body.toString()
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            window._showAutoSaveToast('Đã lưu');
        } else {
            window._showAutoSaveToast(data.message || 'Lỗi lưu', true);
            if (data.require_image) {
                window.location.href = window.BASE_URL + '/pages/image_handler.php?id=' + INDEXDEPT_BOOTSTRAP.id +
                    '&dept=' + INDEXDEPT_BOOTSTRAP.dept +
                    '&tieuchi_id=' + id_tieuchi;
            }
        }
    })
    .catch(function() {
        window._showAutoSaveToast('Lỗi kết nối', true);
    });
};

window._showAutoSaveToast = function(msg, isError) {
    var existing = document.getElementById('autosave-toast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'autosave-toast';
    toast.className = 'autosave-toast' + (isError ? ' autosave-toast--error' : '');
    toast.textContent = msg;
    document.body.appendChild(toast);

    setTimeout(function() { toast.classList.add('autosave-toast--visible'); }, 10);
    setTimeout(function() {
        toast.classList.remove('autosave-toast--visible');
        setTimeout(function() { toast.remove(); }, 300);
    }, 2000);
};

/* ============================================================
   Mobile card list: segmented điểm, thu gọn/mở lại, dirty state
   ============================================================ */
(function (ns) {
    'use strict';

    var ownerSnapshot = {};

    function getForm() {
        return document.getElementById('danhgiaForm');
    }

    function currentOwnerKey(tr) {
        var ids = [];
        tr.querySelectorAll('.owner-select__panel input[type="checkbox"]:checked').forEach(function (cb) {
            ids.push(cb.value);
        });
        return ids.sort().join(',');
    }

    function snapshotOwners() {
        var form = getForm();
        if (!form) return;
        form.querySelectorAll('tr[data-tieuchi-row]').forEach(function (tr) {
            ownerSnapshot[tr.getAttribute('data-tieuchi-row')] = currentOwnerKey(tr);
        });
    }

    function normalizeNewlines(str) {
        return String(str || '').replace(/\r\n?/g, '\n');
    }

    function refreshDirtyState() {
        var form = getForm();
        if (!form) return;

        var dirty = 0;
        form.querySelectorAll('tr[data-tieuchi-row]').forEach(function (tr) {
            var id = tr.getAttribute('data-tieuchi-row');
            var note = tr.querySelector('textarea[name="ghichu_' + id + '"]');
            var oldNote = tr.querySelector('input[name="old_ghichu_' + id + '"]');
            var isDirty = false;

            // textarea.value đã normalize về LF, còn hidden input giữ nguyên CRLF từ DB
            if (note && oldNote && normalizeNewlines(note.value) !== normalizeNewlines(oldNote.value)) {
                isDirty = true;
            }
            if (!isDirty && ownerSnapshot[id] !== undefined && currentOwnerKey(tr) !== ownerSnapshot[id]) {
                isDirty = true;
            }
            if (isDirty) dirty++;
        });

        document.body.classList.toggle('has-dirty', dirty > 0);
        var label = document.getElementById('savePillLabel');
        if (label) {
            label.textContent = dirty > 0 ? ('Lưu ' + dirty + ' thay đổi') : 'Lưu thay đổi';
        }
    }

    // Strip đầu trang không biết tổng điểm (render trước bảng) nên lấy lại từ tfoot
    function syncOrderStrip() {
        var totalEl = document.getElementById('total_points');
        var scoreEl = document.getElementById('orderStripScore');
        if (!totalEl || !scoreEl) return;

        var text = (totalEl.textContent || '').trim();
        scoreEl.textContent = text;

        var parts = text.split('/');
        var current = parseFloat(parts[0]) || 0;
        var max = parseFloat(parts[1]) || 0;
        var percent = max > 0 ? (current / max) * 100 : 0;

        var progressEl = document.getElementById('orderStripProgress');
        if (progressEl) {
            progressEl.style.width = percent.toFixed(1) + '%';
        }
    }

    function updateHiddenCount() {
        var form = getForm();
        var badge = document.getElementById('hiddenRowsCount');
        if (!form || !badge) return;
        badge.textContent = form.querySelectorAll('tr[data-has-default="1"]').length;
    }

    // Đếm tiêu chí đã chấm đang gom sau dòng "Đã chấm n" (loại đạt mặc định có nút riêng)
    function updateScoredCount() {
        var form = getForm();
        var toggle = document.getElementById('scoredToggle');
        var countEl = document.getElementById('scoredToggleCount');
        if (!form || !toggle || !countEl) return;
        var n = form.querySelectorAll('tr.is-scored:not(.row-completed)').length;
        countEl.textContent = n;
        toggle.classList.toggle('is-empty', n === 0);
    }

    function syncScoreUi(select) {
        var id = select.getAttribute('data-tieuchi-id');
        var value = parseFloat(select.value);
        var optionLabel = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text.trim() : '';

        var badge = document.getElementById('tc_score_badge_' + id);
        if (badge) badge.textContent = optionLabel;

        var tr = document.querySelector('tr[data-tieuchi-row="' + id + '"]');
        if (tr) {
            tr.classList.toggle('is-scored', value > 0);
            tr.classList.toggle('is-overdue', value <= 0 && tr.getAttribute('data-overdue') === '1');
            // Vừa chấm xong thì giữ thẻ mở để user thấy ngay kết quả
            tr.classList.toggle('is-expanded', value > 0);
        }

        var seg = document.querySelector('.score-seg[data-tieuchi-id="' + id + '"]');
        if (seg) {
            seg.querySelectorAll('.score-seg__opt').forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-value') === select.value);
            });
        }

        syncOrderStrip();
        updateScoredCount();
        syncGroupHeaders();
    }

    // Ẩn header nhóm khi mọi dòng trong nhóm đang bị gom/ẩn (chỉ có tác dụng ở mobile qua CSS)
    function syncGroupHeaders() {
        var form = getForm();
        if (!form) return;
        var rows = form.querySelectorAll('.evaluation-table tbody > tr');
        var header = null;
        var hasVisible = false;
        var flush = function () {
            if (header) header.classList.toggle('is-empty-group', !hasVisible);
        };
        rows.forEach(function (tr) {
            if (tr.querySelector('.evaluation-group-row')) {
                flush();
                header = tr;
                hasVisible = false;
                return;
            }
            if (!hasVisible && tr.hasAttribute('data-tieuchi-row') && getComputedStyle(tr).display !== 'none') {
                hasVisible = true;
            }
        });
        flush();
    }

    window.toggleScoredRows = function () {
        var form = getForm();
        var table = form ? form.querySelector('.evaluation-table') : null;
        var toggle = document.getElementById('scoredToggle');
        var action = document.getElementById('scoredToggleAction');
        if (!table || !toggle) return;
        var isOpen = table.classList.toggle('show-scored');
        toggle.classList.toggle('is-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (action) action.textContent = isOpen ? 'Ẩn' : 'Hiện';
        syncGroupHeaders();
    };

    var originalToggleCompletedRows = window.toggleCompletedRows;
    if (typeof originalToggleCompletedRows === 'function') {
        window.toggleCompletedRows = function () {
            originalToggleCompletedRows.apply(this, arguments);
            syncGroupHeaders();
        };
    }

    /* ---------- Khởi tạo (core gọi) ---------- */
    ns.initMobileCards = function () {
        var form = getForm();
        if (!form) return;

        snapshotOwners();
        refreshDirtyState();
        updateHiddenCount();
        updateScoredCount();
        syncOrderStrip();
        syncGroupHeaders();

        var tbody = form.querySelector('.evaluation-table tbody');
        if (!tbody) return;

        // Segmented control chỉ điều khiển select gốc, select vẫn là nguồn duy nhất gửi form
        tbody.addEventListener('click', function (event) {
            // Chip hạn xử lý mở/đóng form chỉnh hạn của chính dòng đó (mobile)
            var deadlineChip = event.target.closest('.deadline-date');
            if (deadlineChip) {
                var deadlineRow = deadlineChip.closest('tr[data-tieuchi-row]');
                if (deadlineRow) deadlineRow.classList.toggle('is-deadline-open');
                return;
            }

            // Nút "Ghi chú" mở/đóng ô ghi chú của chính dòng đó (mobile)
            var noteToggle = event.target.closest('[data-note-toggle]');
            if (noteToggle) {
                var noteRow = noteToggle.closest('tr[data-tieuchi-row]');
                if (noteRow) {
                    var noteOpen = noteRow.classList.toggle('is-note-open');
                    noteToggle.setAttribute('aria-expanded', noteOpen ? 'true' : 'false');
                    if (noteOpen) {
                        var noteArea = noteRow.querySelector('.evaluation-note-textarea');
                        if (noteArea) noteArea.focus();
                    }
                }
                return;
            }

            var opt = event.target.closest('.score-seg__opt');
            if (opt) {
                var seg = opt.closest('.score-seg');
                var select = form.querySelector('.diem-dropdown[data-tieuchi-id="' + seg.getAttribute('data-tieuchi-id') + '"]');
                if (select && select.value !== opt.getAttribute('data-value')) {
                    select.value = opt.getAttribute('data-value');
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
                return;
            }

            // Chạm vào dòng gọn để mở lại thành thẻ đầy đủ
            if (event.target.closest('select, textarea, button, input, label, a, .owner-select')) return;
            var tr = event.target.closest('tr.is-scored');
            if (tr) tr.classList.toggle('is-expanded');
        });

        tbody.addEventListener('input', function (event) {
            if (event.target.classList.contains('evaluation-note-textarea')) {
                var noteTr = event.target.closest('tr[data-tieuchi-row]');
                var noteFlat = noteTr ? document.getElementById('tc_note_flat_' + noteTr.getAttribute('data-tieuchi-row')) : null;
                if (noteFlat) noteFlat.textContent = event.target.value.trim();
                refreshDirtyState();
            }
        });

        tbody.addEventListener('change', function (event) {
            var target = event.target;
            if (target.classList.contains('diem-dropdown')) {
                syncScoreUi(target);
                return;
            }
            if (target.type === 'checkbox' && target.name.indexOf('nguoi_thuchien_') === 0) {
                var tr = target.closest('tr[data-tieuchi-row]');
                if (tr) {
                    var flat = document.getElementById('owner_flat_' + tr.getAttribute('data-tieuchi-row'));
                    if (flat) {
                        var names = [];
                        tr.querySelectorAll('.owner-select__panel input[type="checkbox"]:checked').forEach(function (cb) {
                            var nameEl = cb.parentElement.querySelector('.owner-checklist__name');
                            if (nameEl) names.push(nameEl.textContent.trim());
                        });
                        flat.textContent = names.join(' · ');
                    }
                }
                refreshDirtyState();
            }
        });
    };

    // Lối vào dự phòng từ menu mobile khi pill Lưu không hiển thị
    window.submitDanhgiaForm = function () {
        var form = getForm();
        if (!form) return;
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    };
})(window.IndexDept = window.IndexDept || {});
