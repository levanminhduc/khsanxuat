(function () {
    const INDEXDEPT_BOOTSTRAP = window.INDEXDEPT_BOOTSTRAP || {};
    let scoreOptionsDirty = false;

    function buildCheckImageUrl(tieuchiId) {
        const id = encodeURIComponent(INDEXDEPT_BOOTSTRAP.id || '');
        return 'ajax_check_tieuchi_image.php?id_khsanxuat=' + id + '&id_tieuchi=' + encodeURIComponent(tieuchiId);
    }

    function buildAutoSelectImageUrl(tieuchiId) {
        const id = encodeURIComponent(INDEXDEPT_BOOTSTRAP.id || '');
        const dept = encodeURIComponent(INDEXDEPT_BOOTSTRAP.dept || '');
        return 'indexdept.php?dept=' + dept + '&id=' + id + '&autoselect_image=1&tieuchi_id=' + encodeURIComponent(tieuchiId);
    }


    // JavaScript được tái cấu trúc và sửa lỗi
    // Được chỉnh sửa ngày 

    // Các hàm cơ bản cho modal
    function openModal() {
        showElement(document.getElementById('addCriteriaModal'), 'block');
    }

    function closeModal() {
        hideElement(document.getElementById('addCriteriaModal'));
    }

    function openDeadlineModal() {
        showElement(document.getElementById('deadlineModal'), 'block');
    }

    function closeDeadlineModal() {
        hideElement(document.getElementById('deadlineModal'));
    }

    function openDefaultSettingModal() {
        showElement(document.getElementById('defaultSettingModal'), 'block');
        document.getElementById('current_dept').value = (INDEXDEPT_BOOTSTRAP.dept || '');
        document.getElementById('selected_xuong').value = (INDEXDEPT_BOOTSTRAP.xuong || '');
        changeSelectedXuong();
    }

    function closeDefaultSettingModal() {
        hideElement(document.getElementById('defaultSettingModal'));
    }

    function openScoreOptionsModal() {
        showElement(document.getElementById('scoreOptionsModal'), 'block');
    }

    function closeScoreOptionsModal() {
        hideElement(document.getElementById('scoreOptionsModal'));
        if (scoreOptionsDirty) {
            window.location.reload();
        }
    }

    function closeStaffModal() {
        hideElement(document.getElementById('staffModal'));
    }

    function showElement(element, displayValue) {
        if (!element) return;
        element.hidden = false;
        element.style.display = element.classList.contains('indexdept-modern-modal') ? 'flex' : (displayValue || 'block');
    }

    function hideElement(element) {
        if (!element) return;
        element.style.display = 'none';
        element.hidden = true;
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
        xhr.open('POST', 'save_score_options.php', true);
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

    document.addEventListener('DOMContentLoaded', function() {
        initializeScoreEditors();

        document.addEventListener('click', function(event) {
            const removeButton = event.target.closest('[data-score-remove]');
            if (!removeButton) return;

            removeScoreChip(
                removeButton.getAttribute('data-tieuchi-id'),
                removeButton.getAttribute('data-score-value')
            );
        });
    });


    // Hàm cập nhật trạng thái checkbox khi thay đổi điểm
    function updateStatus(element) {
        if (!element) return;

        const tieuchi_id = element.getAttribute('data-tieuchi-id');
        const checkbox = document.getElementById('checkbox_' + tieuchi_id);
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

    // Hàm thiết lập ngày nhanh
    function setQuickDays(days) {
        document.getElementById('so_ngay_xuly_chung').value = days;
        const quickButtons = document.querySelectorAll('.quick-btn');
        quickButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.includes(days.toString())) {
                btn.classList.add('active');
            }
        });

        changeNgayTinhHan();
    }

    // Hàm thay đổi mô tả ngày tính hạn
    function changeNgayTinhHan() {
        const ngayTinhHan = document.getElementById('ngay_tinh_han').value;
        const noteElement = document.getElementById('note-ngay-tinh');

        switch(ngayTinhHan) {
            case 'ngay_vao':
                noteElement.textContent = 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày';
                break;
            case 'ngay_vao_cong':
                noteElement.textContent = 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" + 7 ngày';
                break;
            case 'ngay_ra':
                noteElement.textContent = 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Ra" + 7 ngày';
                break;
            case 'ngay_ra_tru':
                noteElement.textContent = 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Ra" - 7 ngày';
                break;
        }
    }

    // Hàm chọn tất cả tiêu chí
    function selectAllTieuchi(select) {
        const checkboxes = document.querySelectorAll('.tieuchi-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = select;
        });
    }

    // Hàm cập nhật hạn xử lý cho một tiêu chí
    function updateDeadline(idSanxuat, idTieuchi, dept) {
        // Lấy giá trị hiện tại từ ô input do người dùng nhập
        const soNgayXuly = document.getElementById('so_ngay_xuly_' + idTieuchi).value;
        const ngayTinhHan = document.getElementById('ngay_tinh_han_' + idTieuchi).value;
        const dateDisplay = document.getElementById('date_display_' + idTieuchi);
        const originalText = dateDisplay.innerHTML;

        // Lấy giá trị xưởng hiện tại (nếu có)
        const currentXuong = (INDEXDEPT_BOOTSTRAP.xuong || '');

        // Hiển thị trạng thái đang cập nhật
        setDeadlineState(dateDisplay, 'loading', buildLoadingIcon('loading-icon--md', 'Đang cập nhật') + ' Đang cập nhật...');

        // Thực hiện cập nhật bằng AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_deadline_tieuchi.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            // Cập nhật hiển thị ngày deadline
                            setDeadlineState(dateDisplay, 'success', response.new_date);
                            dateDisplay.classList.add('update-success');

                            // Giữ nguyên giá trị ô input mà người dùng đã nhập, không dùng giá trị từ server
                            document.getElementById('so_ngay_xuly_' + idTieuchi).value = soNgayXuly;
                            document.getElementById('ngay_tinh_han_' + idTieuchi).value = ngayTinhHan;

                            setTimeout(function() {
                                dateDisplay.classList.remove('update-success');
                                clearDeadlineState(dateDisplay);
                            }, 2000);

                            // Comment phần confirm này lại vì người dùng không muốn nó xuất hiện
                            /*if (confirm('Bạn có muốn lưu số ngày này vào cài đặt mặc định cho xưởng này không?')) {
                                saveDefaultSetting(idTieuchi, dept);
                            }*/
                        } else {
                            // Hiển thị lỗi trong khung deadline
                            setDeadlineState(dateDisplay, 'error', '<span class="text-danger">' + escapeHtml(response.message || 'Lỗi không xác định') + '</span>');
                            setTimeout(function() {
                                dateDisplay.innerHTML = originalText;
                                clearDeadlineState(dateDisplay);
                            }, 3000);
                        }
                    } catch (e) {
                        console.error('Lỗi xử lý JSON:', e);
                        setDeadlineState(dateDisplay, 'error', '<span class="text-danger">Lỗi xử lý dữ liệu</span>');
                        setTimeout(function() {
                            dateDisplay.innerHTML = originalText;
                            clearDeadlineState(dateDisplay);
                        }, 3000);
                    }
                } else {
                    setDeadlineState(dateDisplay, 'error', '<span class="text-danger">Lỗi kết nối máy chủ</span>');
                    setTimeout(function() {
                        dateDisplay.innerHTML = originalText;
                        clearDeadlineState(dateDisplay);
                    }, 3000);
                }
            }
        };
        xhr.send('id_sanxuat=' + idSanxuat + '&id_tieuchi=' + idTieuchi + '&so_ngay_xuly=' + soNgayXuly + '&ngay_tinh_han=' + ngayTinhHan + '&dept=' + dept);
    }

    // Hàm cập nhật hạn xử lý cho nhiều tiêu chí
    function updateDeadlineAll(idSanxuat, dept) {
        const soNgayXulyChung = document.getElementById('so_ngay_xuly_chung').value;
        const ngayTinhHan = document.getElementById('ngay_tinh_han').value;
        const updateStatusDiv = document.getElementById('update_status');
        const selectedTieuchi = [];

        // Lấy danh sách tiêu chí được chọn
        document.querySelectorAll('.tieuchi-checkbox:checked').forEach(checkbox => {
            selectedTieuchi.push(checkbox.value);
        });

        if (selectedTieuchi.length === 0) {
            alert('Vui lòng chọn ít nhất một tiêu chí để áp dụng cài đặt.');
            return;
        }

        setStatusMessage(updateStatusDiv, 'info', 'Đang cập nhật hạn xử lý cho ' + selectedTieuchi.length + ' tiêu chí...', { loading: true, iconSize: 'loading-icon--md', altText: 'Đang cập nhật' });

        // Tạo hiệu ứng loading cho các tiêu chí đang được cập nhật
        selectedTieuchi.forEach(tieuchiId => {
            const dateDisplay = document.getElementById('date_display_' + tieuchiId);
            if (dateDisplay) {
                setDeadlineState(dateDisplay, 'loading', buildLoadingIcon('loading-icon--sm', 'Đang cập nhật'));
            }
        });

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_deadline_all.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            setStatusMessage(updateStatusDiv, 'success', 'Đã cập nhật hạn xử lý cho ' + response.updated_count + ' tiêu chí!');

                            // Cập nhật hiển thị trên giao diện
                            if (response.updated_items) {
                                response.updated_items.forEach(item => {
                                    const dateDisplay = document.getElementById('date_display_' + item.id_tieuchi);
                                    if (dateDisplay) {
                                        setDeadlineState(dateDisplay, 'success', item.new_date);
                                        dateDisplay.classList.add('update-success');

                                        // Cập nhật giá trị trong input
                                        const soNgayInput = document.getElementById('so_ngay_xuly_' + item.id_tieuchi);
                                        if (soNgayInput) {
                                            soNgayInput.value = soNgayXulyChung;
                                        }

                                        // Cập nhật select ngày tính hạn
                                        const ngayTinhHanSelect = document.getElementById('ngay_tinh_han_' + item.id_tieuchi);
                                        if (ngayTinhHanSelect) {
                                            ngayTinhHanSelect.value = ngayTinhHan;
                                        }

                                        setTimeout(function() {
                                            dateDisplay.classList.remove('update-success');
                                            clearDeadlineState(dateDisplay);
                                        }, 2000);
                                    }
                                });
                            }

                            // Hiển thị thông báo hỏi về việc lưu cài đặt mặc định
                            setTimeout(function() {
                                // Comment phần confirm này lại vì người dùng không muốn nó xuất hiện
                                /*if (confirm('Bạn có muốn lưu các cài đặt này làm mặc định cho tất cả tiêu chí không?')) {
                                    // Gọi hàm lưu tất cả cài đặt mặc định
                                    saveAllDefaultSettings(dept);
                                } else {
                                    hideElement(updateStatusDiv);
                                    closeDeadlineModal();
                                }*/

                                // Chỉ đóng modal sau khi hoàn thành
                                hideElement(updateStatusDiv);
                                closeDeadlineModal();
                            }, 1000);
                        } else {
                            setStatusMessage(updateStatusDiv, 'error', 'Lỗi: ' + response.message);

                            // Khôi phục trạng thái ban đầu cho các tiêu chí
                            selectedTieuchi.forEach(tieuchiId => {
                                const dateDisplay = document.getElementById('date_display_' + tieuchiId);
                                if (dateDisplay) {
                                    clearDeadlineState(dateDisplay);
                                    // Tải lại dữ liệu hiện tại từ cơ sở dữ liệu
                                    loadCurrentDeadline(tieuchiId, idSanxuat);
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Lỗi xử lý JSON:', e);
                        setStatusMessage(updateStatusDiv, 'error', 'Lỗi khi xử lý phản hồi từ máy chủ.');

                        // Khôi phục trạng thái ban đầu cho các tiêu chí
                        selectedTieuchi.forEach(tieuchiId => {
                            const dateDisplay = document.getElementById('date_display_' + tieuchiId);
                            if (dateDisplay) {
                                clearDeadlineState(dateDisplay);
                                loadCurrentDeadline(tieuchiId, idSanxuat);
                            }
                        });
                    }
                } else {
                    setStatusMessage(updateStatusDiv, 'error', 'Có lỗi xảy ra. Vui lòng thử lại sau.');

                    // Khôi phục trạng thái ban đầu cho các tiêu chí
                    selectedTieuchi.forEach(tieuchiId => {
                        const dateDisplay = document.getElementById('date_display_' + tieuchiId);
                        if (dateDisplay) {
                            clearDeadlineState(dateDisplay);
                            loadCurrentDeadline(tieuchiId, idSanxuat);
                        }
                    });
                }
            }
        };
        xhr.send('id_sanxuat=' + idSanxuat + '&tieuchi=' + JSON.stringify(selectedTieuchi) + '&so_ngay_xuly=' + soNgayXulyChung + '&ngay_tinh_han=' + ngayTinhHan + '&dept=' + dept);
    }

    // Hàm mới để tải lại thông tin hạn xử lý hiện tại
    function loadCurrentDeadline(id_tieuchi, id_sanxuat) {
        // Chỉ lấy thông tin hiển thị
        let dateDisplay = document.getElementById('date_display_' + id_tieuchi);
        if (dateDisplay) {
            dateDisplay.innerHTML = '<span class="loading-indicator">Đang tải...</span>';
        }

        fetch('get_tieuchi_deadline.php?id_tieuchi=' + id_tieuchi + '&id_sanxuat=' + id_sanxuat)
        .then(response => response.json())
        .then(data => {
            console.log("Dữ liệu nhận từ server:", data);
            if (dateDisplay) {
                if (data.success) {
                    // Chỉ cập nhật phần hiển thị deadline, không cập nhật các input field
                    dateDisplay.innerHTML = data.deadline;

                    // Không cập nhật giá trị các trường input để giữ nguyên giá trị người dùng đã nhập
                } else {
                    dateDisplay.innerHTML = 'Chưa thiết lập';
                }
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải thông tin deadline:', error);
            if (dateDisplay) {
                dateDisplay.innerHTML = 'Lỗi tải dữ liệu';
            }
        });
    }

    // Hàm mở modal quản lý người thực hiện
    function openStaffModal(dept) {
        document.getElementById('current_staff_dept').value = dept;
        showElement(document.getElementById('staffModal'), 'block');
        document.getElementById('dept_display_name').textContent = getDeptDisplayName(dept);

        loadStaffList(dept);
    }

    // Hàm lấy tên hiển thị của bộ phận
    function getDeptDisplayName(dept) {
        const deptNames = {
            'kehoach': 'BỘ PHẬN KẾ HOẠCH',
            'chuanbi_sanxuat_phong_kt': 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)',
            'kho': 'KHO NGUYÊN, PHỤ LIỆU',
            'cat': 'BỘ PHẬN CẮT',
            'ep_keo': 'BỘ PHẬN ÉP KEO',
            'co_dien': 'BỘ PHẬN CƠ ĐIỆN',
            'chuyen_may': 'BỘ PHẬN CHUYỀN MAY',
            'kcs': 'BỘ PHẬN KCS',
            'ui_thanh_pham': 'BỘ PHẬN ỦI THÀNH PHẨM',
            'hoan_thanh': 'BỘ PHẬN HOÀN THÀNH'
        };

        return deptNames[dept] || 'KHÔNG XÁC ĐỊNH';
    }

    // Hàm tải danh sách người thực hiện
    function loadStaffList(dept) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_staff_list.php?dept=' + encodeURIComponent(dept), true);
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            const staffList = response.data;
                            let html = '';

                            if (!staffList.length) {
                                html = buildTableMessageRow(4, 'muted', 'Chưa có người chịu trách nhiệm nào cho bộ phận này.');
                            }

                            staffList.forEach(function(staff, index) {
                                html += `
                                <tr id="staff_row_${staff.id}" class="tw-bg-white tw-transition hover:tw-bg-[#eef5ff]/60">
                                    <td class="tw-whitespace-nowrap tw-px-4 tw-py-3 tw-font-bold tw-text-slate-700">${index + 1}</td>
                                    <td class="tw-px-4 tw-py-3"><input type="text" id="staff_name_${staff.id}" class="form-control staff-modal__input tw-h-10 tw-rounded-lg tw-border-slate-300 tw-bg-white tw-text-sm tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15" value="${escapeHtml(staff.ten)}"></td>
                                    <td class="tw-px-4 tw-py-3"><input type="text" id="staff_position_${staff.id}" class="form-control staff-modal__input tw-h-10 tw-rounded-lg tw-border-slate-300 tw-bg-white tw-text-sm tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15" value="${escapeHtml(staff.chuc_vu || '')}"></td>
                                    <td class="tw-px-4 tw-py-3">
                                        <div class="tw-flex tw-flex-wrap tw-gap-2">
                                            <button type="button" onclick="updateStaff(${staff.id})" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary staff-modal__action-btn tw-inline-flex tw-h-9 tw-items-center tw-justify-center tw-rounded-lg tw-border tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-px-3 tw-text-xs tw-font-bold tw-text-[#143583] tw-shadow-sm tw-transition hover:tw-bg-[#dbeafe]">Cập nhật</button>
                                            <button type="button" onclick="deleteStaff(${staff.id})" class="btn-add-criteria default-settings-modal__btn staff-modal__action-btn staff-modal__action-btn--danger tw-inline-flex tw-h-9 tw-items-center tw-justify-center tw-rounded-lg tw-border tw-border-rose-200 tw-bg-rose-50 tw-px-3 tw-text-xs tw-font-bold tw-text-rose-800 tw-shadow-sm tw-transition hover:tw-bg-rose-100">Xóa</button>
                                        </div>
                                    </td>
                                </tr>`;
                            });

                            document.getElementById('staff_tbody').innerHTML = html;
                        } else {
                            document.getElementById('staff_tbody').innerHTML = buildTableMessageRow(4, 'error', 'Lỗi: ' + response.message);
                        }
                    } catch (e) {
                        console.error(e);
                        document.getElementById('staff_tbody').innerHTML = buildTableMessageRow(4, 'error', 'Lỗi khi xử lý phản hồi từ máy chủ.');
                    }
                } else {
                    document.getElementById('staff_tbody').innerHTML = buildTableMessageRow(4, 'error', 'Lỗi khi tải danh sách người thực hiện.');
                }
            }
        };
        xhr.send();
    }

    // Hàm thêm người thực hiện mới
    function addNewStaff() {
        const staffName = document.getElementById('new_staff_name').value.trim();
        const staffPosition = document.getElementById('new_staff_position').value.trim();
        const dept = document.getElementById('current_staff_dept').value;
        const statusDiv = document.getElementById('staff_status');

        if (!staffName) {
            alert('Vui lòng nhập tên người chịu trách nhiệm.');
            return;
        }

        showElement(statusDiv, 'block');
        setStatusMessage(statusDiv, 'info', 'Đang thêm người chịu trách nhiệm...');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'add_staff.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            setStatusMessage(statusDiv, 'success', 'Đã thêm người chịu trách nhiệm thành công!');
                            document.getElementById('new_staff_name').value = '';
                            document.getElementById('new_staff_position').value = '';
                            loadStaffList(dept);

                            setTimeout(function() {
                                hideElement(statusDiv);
                            }, 3000);
                        } else {
                            setStatusMessage(statusDiv, 'error', 'Lỗi: ' + response.message);
                        }
                    } catch (e) {
                        console.error(e);
                        setStatusMessage(statusDiv, 'error', 'Lỗi khi xử lý phản hồi từ máy chủ.');
                    }
                } else {
                    setStatusMessage(statusDiv, 'error', 'Có lỗi xảy ra. Vui lòng thử lại sau.');
                }
            }
        };
        xhr.send('ten=' + encodeURIComponent(staffName) + '&chuc_vu=' + encodeURIComponent(staffPosition) + '&phong_ban=' + dept);
    }

    // Khởi tạo các sự kiện khi trang đã load xong
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo các select điểm đánh giá
        const diemSelects = document.querySelectorAll('.diem-dropdown');
        diemSelects.forEach(select => {
            select.addEventListener('change', function() {
                updateStatus(this);
            });
        });

        // Khởi tạo mô tả cho phương thức tính hạn xử lý
        if (document.getElementById('ngay_tinh_han')) {
            changeNgayTinhHan();
        }

        // Khởi tạo giá trị completedCount và totalCriteria
        let completedCount = (INDEXDEPT_BOOTSTRAP.completedTieuchi || 0);
        const totalCriteria = (INDEXDEPT_BOOTSTRAP.totalTieuchi || 0);
        let totalPoints = 0;
        const maxPoints = (INDEXDEPT_BOOTSTRAP.maxPossiblePoints || 0);

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
    });

    // Hàm cập nhật thông tin người thực hiện
    function updateStaff(staffId) {
        const staffName = document.getElementById('staff_name_' + staffId).value.trim();
        const staffPosition = document.getElementById('staff_position_' + staffId).value.trim();
        const dept = document.getElementById('current_staff_dept').value;
        const statusDiv = document.getElementById('staff_status');

        if (!staffName) {
            alert('Vui lòng nhập tên người chịu trách nhiệm!');
            return;
        }

        showElement(statusDiv, 'block');
        setStatusMessage(statusDiv, 'info', 'Đang cập nhật thông tin...');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'manage_staff.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            // Hiển thị thông báo thành công
                            setStatusMessage(statusDiv, 'success', 'Đã cập nhật thông tin thành công!');

                            // Highlight dòng vừa cập nhật
                            const row = document.getElementById('staff_row_' + staffId);
                            if (row) {
                                flashRow(row, 'row-flash--success');
                            }

                            // Cập nhật danh sách nhân viên để đảm bảo các thay đổi được hiển thị
                            loadStaffList(dept);

                            // Ẩn thông báo sau 3 giây
                            setTimeout(function() {
                                hideElement(statusDiv);
                            }, 3000);
                        } else {
                            // Hiển thị thông báo lỗi
                            setStatusMessage(statusDiv, 'error', 'Lỗi: ' + response.message);
                        }
                    } catch (e) {
                        console.error(e);
                        setStatusMessage(statusDiv, 'error', 'Lỗi khi xử lý phản hồi từ máy chủ.');
                    }
                } else {
                    setStatusMessage(statusDiv, 'error', 'Có lỗi xảy ra. Vui lòng thử lại sau.');
                }
            }
        };
        xhr.send('action=update&id=' + encodeURIComponent(staffId) + '&ten=' + encodeURIComponent(staffName) + '&chuc_vu=' + encodeURIComponent(staffPosition));
    }

    // Hàm xóa người thực hiện
    function deleteStaff(staffId) {
        if (!confirm('Bạn có chắc chắn muốn xóa người chịu trách nhiệm này?')) {
            return;
        }

        const dept = document.getElementById('current_staff_dept').value;
        const statusDiv = document.getElementById('staff_status');

        showElement(statusDiv, 'block');
        setStatusMessage(statusDiv, 'info', 'Đang xóa người chịu trách nhiệm...');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'manage_staff.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            // Hiển thị thông báo thành công
                            setStatusMessage(statusDiv, 'success', 'Đã xóa người chịu trách nhiệm thành công!');

                            // Tải lại danh sách
                            loadStaffList(dept);

                            // Ẩn thông báo sau 3 giây
                            setTimeout(function() {
                                hideElement(statusDiv);
                            }, 3000);
                        } else {
                            // Hiển thị thông báo lỗi
                            setStatusMessage(statusDiv, 'error', 'Lỗi: ' + response.message);
                        }
                    } catch (e) {
                        console.error(e);
                        setStatusMessage(statusDiv, 'error', 'Lỗi khi xử lý phản hồi từ máy chủ.');
                    }
                } else {
                    setStatusMessage(statusDiv, 'error', 'Có lỗi xảy ra. Vui lòng thử lại sau.');
                }
            }
        };
        xhr.send('action=delete&id=' + encodeURIComponent(staffId));
    }

    // Hàm đóng modal quản lý người thực hiện


    // Hàm thay đổi xưởng được chọn
    function changeSelectedXuong() {
        const selectedXuong = document.getElementById('selected_xuong').value;
        const displayNameElement = document.getElementById('xuong_display_name');

        if (selectedXuong) {
            displayNameElement.textContent = selectedXuong;
        } else {
            displayNameElement.textContent = 'Tất cả xưởng';
        }

        // Tải lại dữ liệu cài đặt mặc định cho xưởng đã chọn
        loadDefaultSettings(document.getElementById('current_dept').value, selectedXuong);
    }

    // Hàm tải dữ liệu cài đặt mặc định theo xưởng
    function loadDefaultSettings(dept, xuong) {
        const xhr = new XMLHttpRequest();
        const url = 'get_default_settings.php?dept=' + encodeURIComponent(dept) + '&xuong=' + encodeURIComponent(xuong || '');

        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            updateDefaultSettingsUI(response.data);
                        } else {
                            alert('Lỗi: ' + response.message);
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Lỗi khi xử lý phản hồi từ máy chủ.');
                    }
                } else {
                    alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
                }
            }
        };
        xhr.send();
    }

    // Hàm cập nhật UI với dữ liệu cài đặt mặc định
    function updateDefaultSettingsUI(settings) {
        // Reset về giá trị mặc định trước
        const rows = document.querySelectorAll('#default_settings_tbody tr[id^="ds_row_"]');
        rows.forEach(row => {
            const id_tieuchi = row.id.replace('ds_row_', '');

            // Đặt giá trị mặc định
            document.getElementById('ds_ngay_tinh_han_' + id_tieuchi).value = 'ngay_vao';
            document.getElementById('ds_so_ngay_xuly_' + id_tieuchi).value = '7';
            document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi).value = '0';
        });

        // Cập nhật giá trị từ settings
        if (settings && settings.length > 0) {
            settings.forEach(setting => {
                const ngayTinhHanElement = document.getElementById('ds_ngay_tinh_han_' + setting.id_tieuchi);
                const soNgayXulyElement = document.getElementById('ds_so_ngay_xuly_' + setting.id_tieuchi);
                const nguoiChiuTrachNhiemElement = document.getElementById('ds_nguoi_chiu_trachnhiem_' + setting.id_tieuchi);

                if (ngayTinhHanElement) ngayTinhHanElement.value = setting.ngay_tinh_han;
                if (soNgayXulyElement) soNgayXulyElement.value = setting.so_ngay_xuly;
                if (nguoiChiuTrachNhiemElement) nguoiChiuTrachNhiemElement.value = setting.nguoi_chiu_trachnhiem_default || '0';
            });
        }
    }

    // Hàm đồng bộ số ngày xử lý từ cài đặt mặc định vào các ô nhập ngày
    function syncTieuChiWithDefaultSettings(dept, xuong) {
        const xhr = new XMLHttpRequest();
        const url = 'get_default_settings.php?dept=' + encodeURIComponent(dept) + '&xuong=' + encodeURIComponent(xuong || '');

        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success && response.data && response.data.length > 0) {
                        // Cập nhật số ngày xử lý cho các ô nhập
                        response.data.forEach(setting => {
                            const soNgayXulyElement = document.getElementById('so_ngay_xuly_' + setting.id_tieuchi);
                            const ngayTinhHanElement = document.getElementById('ngay_tinh_han_' + setting.id_tieuchi);

                            if (soNgayXulyElement) {
                                soNgayXulyElement.value = setting.so_ngay_xuly;
                            }

                            if (ngayTinhHanElement) {
                                ngayTinhHanElement.value = setting.ngay_tinh_han;
                            }
                        });
                    }
                } catch (e) {
                    console.error('Lỗi khi đồng bộ dữ liệu:', e);
                }
            }
        };
        xhr.send();
    }

    // Hàm lưu cài đặt mặc định cho một tiêu chí
    function saveDefaultSetting(id_tieuchi, dept) {
        const ngayTinhHan = document.getElementById('ds_ngay_tinh_han_' + id_tieuchi).value;
        const soNgayXuly = document.getElementById('ds_so_ngay_xuly_' + id_tieuchi).value;
        const nguoiChiuTrachnhiem = document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi).value;
        const selectedXuong = document.getElementById('selected_xuong').value;
        const statusDiv = document.getElementById('default_settings_status');
        const row = document.getElementById('ds_row_' + id_tieuchi);

        showElement(statusDiv, 'block');
        setStatusMessage(statusDiv, 'info', 'Đang lưu cài đặt...');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'save_default_setting.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            setStatusMessage(statusDiv, 'success', 'Đã lưu cài đặt mặc định!');
                            flashRow(row, 'row-flash--muted');
                            setTimeout(function() {
                                hideElement(statusDiv);
                            }, 2000);
                        } else {
                            setStatusMessage(statusDiv, 'error', 'Lỗi: ' + response.message);
                        }
                    } catch (e) {
                        console.error(e);
                        setStatusMessage(statusDiv, 'error', 'Lỗi khi xử lý phản hồi từ máy chủ.');
                    }
                } else {
                    setStatusMessage(statusDiv, 'error', 'Có lỗi xảy ra. Vui lòng thử lại sau.');
                }
            }
        };
        xhr.send('id_tieuchi=' + id_tieuchi + '&dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong) + '&ngay_tinh_han=' + ngayTinhHan + '&so_ngay_xuly=' + soNgayXuly + '&nguoi_chiu_trachnhiem=' + nguoiChiuTrachnhiem);
    }

    // Hàm lưu tất cả cài đặt mặc định
    function saveAllDefaultSettings(dept) {
        const statusDiv = document.getElementById('default_settings_status');
        const rows = document.querySelectorAll("#default_settings_tbody tr[id^='ds_row_']");
        const selectedXuong = document.getElementById('selected_xuong').value;
        const settings = [];

        rows.forEach(row => {
            const id_tieuchi = row.id.replace('ds_row_', '');
            const ngayTinhHan = document.getElementById('ds_ngay_tinh_han_' + id_tieuchi)?.value;
            const soNgayXuly = document.getElementById('ds_so_ngay_xuly_' + id_tieuchi)?.value;
            const nguoiChiuTrachnhiem = document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi)?.value;

            if (ngayTinhHan && soNgayXuly) {
                settings.push({
                    id_tieuchi: id_tieuchi,
                    ngay_tinh_han: ngayTinhHan,
                    so_ngay_xuly: soNgayXuly,
                    nguoi_chiu_trachnhiem: nguoiChiuTrachnhiem || 0
                });
            }
        });

        if (settings.length === 0) {
            alert('Không tìm thấy cài đặt nào để lưu.');
            return;
        }

        showElement(statusDiv, 'block');
        setStatusMessage(statusDiv, 'info', 'Đang lưu tất cả cài đặt...');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'save_all_default_settings.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            setStatusMessage(statusDiv, 'success', 'Đã lưu tất cả cài đặt mặc định!');
                            rows.forEach(row => {
                                flashRow(row, 'row-flash--muted');
                            });

                            setTimeout(function() {
                                hideElement(statusDiv);
                            }, 3000);
                        } else {
                            setStatusMessage(statusDiv, 'error', 'Lỗi: ' + response.message);
                        }
                    } catch (e) {
                        console.error(e);
                        setStatusMessage(statusDiv, 'error', 'Lỗi khi xử lý phản hồi từ máy chủ.');
                    }
                } else {
                    setStatusMessage(statusDiv, 'error', 'Có lỗi xảy ra. Vui lòng thử lại sau.');
                }
            }
        };
        xhr.send('dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong) + '&settings=' + JSON.stringify(settings));
    }

    // Hàm mở modal cài đặt mặc định


    // Hàm áp dụng cài đặt mặc định cho một tiêu chí
    function applyDefaultSetting(id_tieuchi, dept) {
        const selectedXuong = document.getElementById('selected_xuong').value;
        const statusDiv = document.getElementById('default_settings_status');
        const row = document.getElementById('ds_row_' + id_tieuchi);

        showElement(statusDiv, 'block');
        setStatusMessage(statusDiv, 'info', 'Đang áp dụng cài đặt mặc định...');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'apply_default_setting.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            setStatusMessage(statusDiv, 'success', 'Đã áp dụng cài đặt mặc định thành công!');
                            flashRow(row, 'row-flash--success');
                            setTimeout(function() {
                                hideElement(statusDiv);
                            }, 2000);
                        } else {
                            setStatusMessage(statusDiv, 'error', 'Lỗi: ' + response.message);
                        }
                    } catch (e) {
                        console.error(e);
                        setStatusMessage(statusDiv, 'error', 'Lỗi khi xử lý phản hồi từ máy chủ.');
                    }
                } else {
                    setStatusMessage(statusDiv, 'error', 'Có lỗi xảy ra. Vui lòng thử lại sau.');
                }
            }
        };
        xhr.send('id_tieuchi=' + id_tieuchi + '&dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong));
    }

    // Hàm áp dụng tất cả cài đặt mặc định
    function applyAllDefaultSettings(dept) {
        const selectedXuong = document.getElementById('selected_xuong').value;
        const statusDiv = document.getElementById('default_settings_status');
        const tableBody = document.getElementById('default_settings_tbody');

        if (!confirm('Bạn có chắc chắn muốn áp dụng tất cả cài đặt mặc định cho ' +
                    (selectedXuong ? 'xưởng ' + selectedXuong : 'tất cả xưởng') + ' không?')) {
            return;
        }

        showElement(statusDiv, 'block');
        setStatusMessage(statusDiv, 'info', 'Đang áp dụng tất cả cài đặt mặc định...');

        const rows = tableBody.querySelectorAll('tr[id^="ds_row_"]');
        let completedCount = 0;
        let errorCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const id_tieuchi = row.id.replace('ds_row_', '');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'apply_default_setting.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (this.readyState === 4) {
                    if (this.status === 200) {
                        try {
                            const response = JSON.parse(this.responseText);
                            if (response.success) {
                                completedCount++;
                                flashRow(row, 'row-flash--success');
                            } else {
                                errorCount++;
                                flashRow(row, 'row-flash--error');
                            }

                            // Kiểm tra nếu đã hoàn thành tất cả
                            if (completedCount + errorCount === rows.length) {
                                if (errorCount === 0) {
                                    setStatusMessage(statusDiv, 'success', 'Đã áp dụng tất cả cài đặt mặc định thành công!');
                                } else {
                                    setStatusMessage(statusDiv, 'warning', 'Đã áp dụng ' + completedCount + '/' + rows.length + ' cài đặt mặc định. Có ' + errorCount + ' lỗi.');
                                }

                                setTimeout(function() {
                                    hideElement(statusDiv);
                                }, 3000);
                            }
                        } catch (e) {
                            console.error(e);
                            errorCount++;
                        }
                    } else {
                        errorCount++;
                    }
                }
            };
            xhr.send('id_tieuchi=' + id_tieuchi + '&dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong));
        }
    }

    // Thêm gọi hàm đồng bộ khi trang được tải
    document.addEventListener('DOMContentLoaded', function() {
        // Comment dòng này lại để không tự động đồng bộ khi load trang
        // syncTieuChiWithDefaultSettings((INDEXDEPT_BOOTSTRAP.dept || ''), (INDEXDEPT_BOOTSTRAP.xuong || ''));
    });

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
                            const checkbox = document.getElementById('checkbox_' + tieuchiId);
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

    // Thêm đoạn code khởi tạo khi trang load
    document.addEventListener('DOMContentLoaded', function() {
        // Kiểm tra tất cả các tiêu chí bắt buộc hình ảnh khi load trang
        const allSelects = document.querySelectorAll('select[data-tieuchi-id]');
        allSelects.forEach(select => {
            const tieuchiId = select.getAttribute('data-tieuchi-id');
            if (isRequiredImageCriteria(tieuchiId)) {
                checkImageForTieuchi(tieuchiId, select);
            }
        });
    });

    // Thêm hàm kiểm tra tiêu chí bắt buộc hình ảnh
    function isRequiredImageCriteria(tieuchiId) {
        const requiredCriteria = (INDEXDEPT_BOOTSTRAP.requiredCriteria || []);
        return requiredCriteria.includes(parseInt(tieuchiId));
    }



    document.addEventListener('DOMContentLoaded', function() {
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
    });



    // Tìm form đánh giá và các xử lý khác như hiện tại...

    // Thêm code kiểm tra localStorage để cập nhật UI nếu có reset điểm đánh giá
    document.addEventListener('DOMContentLoaded', function() {
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
    });


    // Expose handlers for legacy inline attributes
    window.openModal = typeof openModal === 'function' ? openModal : window.openModal;
    window.closeModal = typeof closeModal === 'function' ? closeModal : window.closeModal;
    window.openDeadlineModal = typeof openDeadlineModal === 'function' ? openDeadlineModal : window.openDeadlineModal;
    window.closeDeadlineModal = typeof closeDeadlineModal === 'function' ? closeDeadlineModal : window.closeDeadlineModal;
    window.openDefaultSettingModal = typeof openDefaultSettingModal === 'function' ? openDefaultSettingModal : window.openDefaultSettingModal;
    window.closeDefaultSettingModal = typeof closeDefaultSettingModal === 'function' ? closeDefaultSettingModal : window.closeDefaultSettingModal;
    window.openScoreOptionsModal = typeof openScoreOptionsModal === 'function' ? openScoreOptionsModal : window.openScoreOptionsModal;
    window.closeScoreOptionsModal = typeof closeScoreOptionsModal === 'function' ? closeScoreOptionsModal : window.closeScoreOptionsModal;
    window.saveScoreOptions = typeof saveScoreOptions === 'function' ? saveScoreOptions : window.saveScoreOptions;
    window.resetScoreOptions = typeof resetScoreOptions === 'function' ? resetScoreOptions : window.resetScoreOptions;
    window.saveAllScoreOptions = typeof saveAllScoreOptions === 'function' ? saveAllScoreOptions : window.saveAllScoreOptions;
    window.addScoreChip = typeof addScoreChip === 'function' ? addScoreChip : window.addScoreChip;
    window.removeScoreChip = typeof removeScoreChip === 'function' ? removeScoreChip : window.removeScoreChip;
    window.handleScoreEntryKeydown = typeof handleScoreEntryKeydown === 'function' ? handleScoreEntryKeydown : window.handleScoreEntryKeydown;
    window.closeStaffModal = typeof closeStaffModal === 'function' ? closeStaffModal : window.closeStaffModal;
    window.setQuickDays = typeof setQuickDays === 'function' ? setQuickDays : window.setQuickDays;
    window.changeNgayTinhHan = typeof changeNgayTinhHan === 'function' ? changeNgayTinhHan : window.changeNgayTinhHan;
    window.selectAllTieuchi = typeof selectAllTieuchi === 'function' ? selectAllTieuchi : window.selectAllTieuchi;
    window.updateDeadlineAll = typeof updateDeadlineAll === 'function' ? updateDeadlineAll : window.updateDeadlineAll;
    window.changeSelectedXuong = typeof changeSelectedXuong === 'function' ? changeSelectedXuong : window.changeSelectedXuong;
    window.saveAllDefaultSettings = typeof saveAllDefaultSettings === 'function' ? saveAllDefaultSettings : window.saveAllDefaultSettings;
    window.openStaffModal = typeof openStaffModal === 'function' ? openStaffModal : window.openStaffModal;
    window.saveDefaultSetting = typeof saveDefaultSetting === 'function' ? saveDefaultSetting : window.saveDefaultSetting;
    window.updateDeadline = typeof updateDeadline === 'function' ? updateDeadline : window.updateDeadline;
    window.updateStatus = typeof updateStatus === 'function' ? updateStatus : window.updateStatus;
    window.addNewStaff = typeof addNewStaff === 'function' ? addNewStaff : window.addNewStaff;
    window.updateStaff = typeof updateStaff === 'function' ? updateStaff : window.updateStaff;
    window.deleteStaff = typeof deleteStaff === 'function' ? deleteStaff : window.deleteStaff;
    window.syncTieuChiWithDefaultSettings = typeof syncTieuChiWithDefaultSettings === 'function' ? syncTieuChiWithDefaultSettings : window.syncTieuChiWithDefaultSettings;

    // Auto-redirect to image handler when requested via query string
    if (INDEXDEPT_BOOTSTRAP.autoSelectImage && INDEXDEPT_BOOTSTRAP.autoSelectTieuchiId) {
        const autoSelectTieuchiId = INDEXDEPT_BOOTSTRAP.autoSelectTieuchiId;
        document.addEventListener('DOMContentLoaded', function() {
            window.location.href = 'image_handler.php?id=' + encodeURIComponent(INDEXDEPT_BOOTSTRAP.id || '') + '&dept=' + encodeURIComponent(INDEXDEPT_BOOTSTRAP.dept || '') + '&tieuchi_id=' + encodeURIComponent(autoSelectTieuchiId);
        });
    }
})();

