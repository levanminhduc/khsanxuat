/* ==========================================================
   indexdept-score-options.js — modal cấu hình mốc điểm của từng tiêu chí.
   Nạp SAU indexdept-core.js.
   ========================================================== */
(function (ns) {
    // Helper dùng chung, lấy từ indexdept-core.js (nạp trước file này).
    const showElement = ns.showElement,
          hideElement = ns.hideElement,
          escapeHtml = ns.escapeHtml,
          setStatusMessage = ns.setStatusMessage,
          flashRow = ns.flashRow,
          getCsrfToken = ns.getCsrfToken,
          registerModalCloseHandler = ns.registerModalCloseHandler;

    // Đã sửa mốc điểm thì đóng modal phải reload để bảng đánh giá lấy giá trị mới.
    let scoreOptionsDirty = false;

    function openScoreOptionsModal() {
        showElement(document.getElementById('scoreOptionsModal'), 'block');
    }

    function closeScoreOptionsModal() {
        hideElement(document.getElementById('scoreOptionsModal'));
        if (scoreOptionsDirty) {
            window.location.reload();
        }
    }

    function formatScoreValue(value) {
        const number = parseFloat(value);
        if (Number.isNaN(number)) {
            return String(value).trim();
        }

        return String(parseFloat(number.toFixed(2)));
    }

    function parseScoreValues(value, options) {
        const settings = options || {};
        const parts = String(value || '').trim().split(/[,\s;]+/).filter(Boolean);
        const unique = {};

        if (!parts.length) {
            return settings.allowEmpty ? { values: [] } : { error: 'Vui lòng nhập ít nhất một mốc điểm' };
        }

        for (let i = 0; i < parts.length; i++) {
            const part = parts[i];
            const number = Number(part);

            if (!Number.isFinite(number)) {
                return { error: 'Mốc điểm không hợp lệ: ' + part };
            }

            if (number < 0 || number > 999.99) {
                return { error: 'Mốc điểm phải nằm trong khoảng 0 đến 999.99' };
            }

            unique[formatScoreValue(number)] = number;
        }

        const values = Object.keys(unique).sort(function(left, right) {
            return parseFloat(left) - parseFloat(right);
        });

        if (values.length > 12) {
            return { error: 'Mỗi tiêu chí chỉ nên có tối đa 12 mốc điểm' };
        }

        return { values: values };
    }

    function normalizeScoreInput(value) {
        const parsed = parseScoreValues(value, { allowEmpty: true });
        return parsed.error ? String(value || '').trim() : parsed.values.join(', ');
    }

    function setScoreOptionsStatus(type, message) {
        setStatusMessage(document.getElementById('score_options_status'), type, message);
    }

    function getScoreEditorElements(idTieuchi) {
        return {
            row: document.getElementById('score_row_' + idTieuchi),
            input: document.getElementById('score_values_' + idTieuchi),
            entry: document.getElementById('score_entry_' + idTieuchi),
            chipList: document.getElementById('score_chips_' + idTieuchi)
        };
    }

    function buildScoreChip(idTieuchi, scoreValue) {
        return '<span class="score-options-modal__chip">' +
            '<span class="score-options-modal__chip-value">' + escapeHtml(scoreValue) + '</span>' +
            '<button type="button" class="score-options-modal__chip-remove" data-score-remove data-tieuchi-id="' + escapeHtml(idTieuchi) + '" data-score-value="' + escapeHtml(scoreValue) + '" aria-label="Xóa mốc điểm ' + escapeHtml(scoreValue) + '">' +
            '<i class="fas fa-times" aria-hidden="true"></i>' +
            '</button>' +
            '</span>';
    }

    function updateScoreChangedSummary() {
        const summary = document.getElementById('score_options_changed_summary');
        const saveAllLabel = document.getElementById('score_options_save_all_label');
        const rows = document.querySelectorAll('#score_options_tbody tr[data-tieuchi-id]');
        let changedCount = 0;

        rows.forEach(function(row) {
            const idTieuchi = row.getAttribute('data-tieuchi-id');
            const input = document.getElementById('score_values_' + idTieuchi);
            if (!input) return;

            const changed = normalizeScoreInput(input.value) !== normalizeScoreInput(row.getAttribute('data-original-scores') || '');
            row.classList.toggle('score-options-modal__row--dirty', changed);
            if (changed) {
                changedCount++;
            }
        });

        if (summary) {
            summary.textContent = changedCount > 0 ? changedCount + ' dòng đã thay đổi' : 'Chưa có thay đổi';
            summary.classList.toggle('score-options-modal__footer-meta--dirty', changedCount > 0);
        }

        if (saveAllLabel) {
            saveAllLabel.textContent = changedCount > 0 ? 'Lưu ' + changedCount + ' dòng thay đổi' : 'Lưu tất cả mốc điểm';
        }
    }

    function syncScoreEditor(idTieuchi, options) {
        const settings = options || {};
        const elements = getScoreEditorElements(idTieuchi);
        if (!elements.input || !elements.chipList) return;

        const parsed = parseScoreValues(elements.input.value, { allowEmpty: true });
        const values = parsed.error ? [] : parsed.values;
        elements.input.value = values.join(', ');

        if (!values.length) {
            elements.chipList.innerHTML = '<span class="score-options-modal__empty-chip">Chưa có mốc</span>';
        } else {
            elements.chipList.innerHTML = values.map(function(scoreValue) {
                return buildScoreChip(idTieuchi, scoreValue);
            }).join('');
        }

        if (settings.clearEntry && elements.entry) {
            elements.entry.value = '';
        }

        updateScoreChangedSummary();
    }

    function addScoreChip(idTieuchi) {
        const elements = getScoreEditorElements(idTieuchi);
        if (!elements.input || !elements.entry) return false;

        const entryValue = elements.entry.value.trim();
        if (entryValue === '') {
            setScoreOptionsStatus('warning', 'Nhập mốc điểm cần thêm');
            return false;
        }

        const mergedValue = [elements.input.value, entryValue].filter(Boolean).join(', ');
        const parsed = parseScoreValues(mergedValue);

        if (parsed.error) {
            setScoreOptionsStatus('error', parsed.error);
            return false;
        }

        elements.input.value = parsed.values.join(', ');
        syncScoreEditor(idTieuchi, { clearEntry: true });
        elements.entry.focus();
        return true;
    }

    function removeScoreChip(idTieuchi, scoreValue) {
        const elements = getScoreEditorElements(idTieuchi);
        if (!elements.input) return;

        const parsed = parseScoreValues(elements.input.value, { allowEmpty: true });
        const values = parsed.error ? [] : parsed.values.filter(function(value) {
            return value !== formatScoreValue(scoreValue);
        });

        elements.input.value = values.join(', ');
        syncScoreEditor(idTieuchi);
    }

    function handleScoreEntryKeydown(event, idTieuchi) {
        if (event.key === 'Enter' || event.key === ',' || event.key === ';') {
            event.preventDefault();
            addScoreChip(idTieuchi);
            return;
        }

        if (event.key === 'Backspace' && event.target.value === '') {
            const elements = getScoreEditorElements(idTieuchi);
            if (!elements.input) return;

            const parsed = parseScoreValues(elements.input.value, { allowEmpty: true });
            if (!parsed.error && parsed.values.length) {
                event.preventDefault();
                parsed.values.pop();
                elements.input.value = parsed.values.join(', ');
                syncScoreEditor(idTieuchi);
            }
        }
    }

    function flushScoreEntry(idTieuchi) {
        const elements = getScoreEditorElements(idTieuchi);
        if (!elements.input) return false;

        if (elements.entry && elements.entry.value.trim() !== '') {
            return addScoreChip(idTieuchi);
        }

        const parsed = parseScoreValues(elements.input.value);
        if (parsed.error) {
            setScoreOptionsStatus('error', parsed.error);
            return false;
        }

        elements.input.value = parsed.values.join(', ');
        syncScoreEditor(idTieuchi);
        return true;
    }

    function initializeScoreEditors() {
        document.querySelectorAll('#score_options_tbody tr[data-tieuchi-id]').forEach(function(row) {
            syncScoreEditor(row.getAttribute('data-tieuchi-id'));
        });
    }

    function setScoreRowState(idTieuchi, configured, scores) {
        const row = document.getElementById('score_row_' + idTieuchi);
        const input = document.getElementById('score_values_' + idTieuchi);
        const badge = document.getElementById('score_badge_' + idTieuchi);

        if (input && typeof scores === 'string') {
            input.value = scores;
        }

        if (row && typeof scores === 'string') {
            row.setAttribute('data-original-scores', normalizeScoreInput(scores));
        }

        if (typeof scores === 'string') {
            syncScoreEditor(idTieuchi);
        }

        if (badge) {
            badge.classList.toggle('score-options-modal__badge--custom', !!configured);
            badge.classList.toggle('score-options-modal__badge--default', !configured);
            badge.classList.remove('tw-border-[#b8cdf4]', 'tw-bg-[#eef5ff]', 'tw-text-[#143583]', 'tw-border-slate-200', 'tw-bg-slate-50', 'tw-text-slate-700');
            (configured
                ? ['tw-border-[#b8cdf4]', 'tw-bg-[#eef5ff]', 'tw-text-[#143583]']
                : ['tw-border-slate-200', 'tw-bg-slate-50', 'tw-text-slate-700']
            ).forEach(function(className) {
                badge.classList.add(className);
            });
            badge.textContent = configured ? 'Tùy chỉnh' : 'Mặc định';
        }

        if (row) {
            flashRow(row, configured ? 'row-flash--success' : 'row-flash--muted');
        }

        updateScoreChangedSummary();
    }

    function sendScoreOptionsRequest(payload, onSuccess) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.BASE_URL + '/actions/save_score_options.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) {
                return;
            }

            try {
                const response = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && response.success) {
                    onSuccess(response);
                    return;
                }

                setScoreOptionsStatus('error', response.message || 'Không thể lưu mốc điểm');
            } catch (error) {
                setScoreOptionsStatus('error', 'Không thể xử lý phản hồi từ máy chủ');
            }
        };

        const body = new URLSearchParams(payload);
        body.set('csrf_token', getCsrfToken());
        xhr.send(body.toString());
    }

    function saveScoreOptions(idTieuchi, dept) {
        const input = document.getElementById('score_values_' + idTieuchi);
        if (!input) return;

        if (!flushScoreEntry(idTieuchi)) {
            return;
        }

        setScoreOptionsStatus('info', 'Đang lưu mốc điểm...');
        sendScoreOptionsRequest({
            action: 'save',
            dept: dept,
            id_tieuchi: idTieuchi,
            scores: input.value
        }, function(response) {
            scoreOptionsDirty = true;
            setScoreRowState(response.id_tieuchi, response.configured, response.scores);
            setScoreOptionsStatus('success', response.message);
        });
    }

    function resetScoreOptions(idTieuchi, dept) {
        setScoreOptionsStatus('info', 'Đang chuyển về mặc định...');
        sendScoreOptionsRequest({
            action: 'reset',
            dept: dept,
            id_tieuchi: idTieuchi
        }, function(response) {
            scoreOptionsDirty = true;
            setScoreRowState(response.id_tieuchi, response.configured, response.scores);
            setScoreOptionsStatus('success', response.message);
        });
    }

    function saveAllScoreOptions(dept) {
        const rows = document.querySelectorAll('#score_options_tbody tr[data-tieuchi-id]');
        const settings = [];
        let hasInvalidRow = false;

        rows.forEach(function(row) {
            const idTieuchi = row.getAttribute('data-tieuchi-id');
            const input = document.getElementById('score_values_' + idTieuchi);
            if (!input) return;

            if (!flushScoreEntry(idTieuchi)) {
                hasInvalidRow = true;
                return;
            }

            const currentScores = normalizeScoreInput(input.value);
            const originalScores = normalizeScoreInput(row.getAttribute('data-original-scores') || '');

            if (currentScores !== originalScores) {
                settings.push({
                    id_tieuchi: parseInt(idTieuchi, 10),
                    scores: input.value
                });
            }
        });

        if (hasInvalidRow) {
            return;
        }

        if (!settings.length) {
            setScoreOptionsStatus('warning', 'Không có mốc điểm nào thay đổi');
            return;
        }

        setScoreOptionsStatus('info', 'Đang lưu ' + settings.length + ' dòng mốc điểm...');
        sendScoreOptionsRequest({
            action: 'bulk_save',
            dept: dept,
            settings: JSON.stringify(settings)
        }, function(response) {
            scoreOptionsDirty = true;
            (response.items || []).forEach(function(item) {
                setScoreRowState(item.id_tieuchi, item.configured, item.scores);
            });
            setScoreOptionsStatus('success', response.message);
            updateScoreChangedSummary();
        });
    }

    // Banner "Lưu đánh giá thành công!" do server render tĩnh nên không tự mất;
    // tự ẩn (fade) sau AUTO_HIDE_SUCCESS_MS để không che nội dung.

    /* ---------- Khởi tạo (core gọi) ---------- */
    ns.initScoreOptions = function () {
        initializeScoreEditors();

        document.addEventListener('click', function(event) {
            const removeButton = event.target.closest('[data-score-remove]');
            if (!removeButton) return;

            removeScoreChip(
                removeButton.getAttribute('data-tieuchi-id'),
                removeButton.getAttribute('data-score-value')
            );
        });
    };

    /* ---------- Công khai ---------- */
    registerModalCloseHandler('scoreOptionsModal', closeScoreOptionsModal);

    // Gọi từ onclick= / onkeydown= trong views/indexdept/partials/modal-score-options.php
    // và views/indexdept/partials/action-header.php.
    window.openScoreOptionsModal = openScoreOptionsModal;
    window.closeScoreOptionsModal = closeScoreOptionsModal;
    window.saveScoreOptions = saveScoreOptions;
    window.resetScoreOptions = resetScoreOptions;
    window.saveAllScoreOptions = saveAllScoreOptions;
    window.addScoreChip = addScoreChip;
    window.removeScoreChip = removeScoreChip;
    window.handleScoreEntryKeydown = handleScoreEntryKeydown;
})(window.IndexDept = window.IndexDept || {});
