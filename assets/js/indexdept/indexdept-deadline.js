/* ==========================================================
   indexdept-deadline.js — hạn xử lý của tiêu chí và cài đặt mặc định
   theo xưởng/chuyền (modal "Hạn xử lý" + modal "Cài đặt mặc định").
   Nạp SAU indexdept-core.js.
   ========================================================== */
(function (ns) {
    // Helper dùng chung, lấy từ indexdept-core.js (nạp trước file này).
    const showElement = ns.showElement,
          hideElement = ns.hideElement,
          escapeHtml = ns.escapeHtml,
          buildLoadingIcon = ns.buildLoadingIcon,
          setStatusMessage = ns.setStatusMessage,
          clearDeadlineState = ns.clearDeadlineState,
          setDeadlineState = ns.setDeadlineState,
          flashRow = ns.flashRow,
          getCsrfToken = ns.getCsrfToken,
          registerModalCloseHandler = ns.registerModalCloseHandler;
    const INDEXDEPT_BOOTSTRAP = window.INDEXDEPT_BOOTSTRAP || {};

    function openDeadlineModal() {
        showElement(document.getElementById('deadlineModal'), 'block');
        updateDeadlineSelectionSummary();
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
        updateDeadlineSelectionSummary();
    }

    function updateDeadlineSelectionSummary() {
        const summary = document.getElementById('deadline_selected_summary');
        const saveLabel = document.getElementById('deadline_save_label');
        const selectedCount = document.querySelectorAll('.tieuchi-checkbox:checked').length;
        const totalCount = document.querySelectorAll('.tieuchi-checkbox').length;

        if (summary) {
            summary.textContent = selectedCount > 0
                ? selectedCount + '/' + totalCount + ' tiêu chí đã chọn'
                : 'Chưa chọn tiêu chí';
            summary.classList.toggle('deadline-modal__footer-meta--active', selectedCount > 0);
        }

        if (saveLabel) {
            saveLabel.textContent = selectedCount > 0 ? 'Lưu cho ' + selectedCount + ' tiêu chí' : 'Lưu cài đặt';
        }
    }

    // Hàm cập nhật hạn xử lý cho một tiêu chí
    function updateDeadline(idSanxuat, idTieuchi, dept) {
        // Lấy giá trị hiện tại từ ô input do người dùng nhập
        const soNgayXuly = document.getElementById('so_ngay_xuly_' + idTieuchi).value;
        const ngayTinhHan = document.getElementById('ngay_tinh_han_' + idTieuchi).value;
        const dateDisplay = document.getElementById('date_display_' + idTieuchi);
        const originalText = dateDisplay.innerHTML;

        // Hiển thị trạng thái đang cập nhật
        setDeadlineState(dateDisplay, 'loading', buildLoadingIcon('loading-icon--md', 'Đang cập nhật') + ' Đang cập nhật...');

        // Thực hiện cập nhật bằng AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.BASE_URL + '/actions/update_deadline_tieuchi.php', true);
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

                            // Không hỏi lưu vào cài đặt mặc định: người dùng không muốn confirm ở đây
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
        xhr.send('id_sanxuat=' + idSanxuat + '&id_tieuchi=' + idTieuchi + '&so_ngay_xuly=' + soNgayXuly + '&ngay_tinh_han=' + ngayTinhHan + '&dept=' + dept + '&csrf_token=' + encodeURIComponent(getCsrfToken()));
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
            setStatusMessage(updateStatusDiv, 'warning', 'Vui lòng chọn ít nhất một tiêu chí để áp dụng cài đặt.');
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
        xhr.open('POST', window.BASE_URL + '/actions/update_deadline_all.php', true);
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

                            // Không hỏi lưu làm cài đặt mặc định: người dùng không muốn confirm ở đây
                            setTimeout(function() {
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
        xhr.send('id_sanxuat=' + idSanxuat + '&tieuchi=' + encodeURIComponent(JSON.stringify(selectedTieuchi)) + '&so_ngay_xuly=' + soNgayXulyChung + '&ngay_tinh_han=' + ngayTinhHan + '&dept=' + dept + '&csrf_token=' + encodeURIComponent(getCsrfToken()));
    }

    // Hàm mới để tải lại thông tin hạn xử lý hiện tại
    function loadCurrentDeadline(id_tieuchi, id_sanxuat) {
        // Chỉ lấy thông tin hiển thị
        let dateDisplay = document.getElementById('date_display_' + id_tieuchi);
        if (dateDisplay) {
            dateDisplay.innerHTML = '<span class="loading-indicator">Đang tải...</span>';
        }

        fetch(window.BASE_URL + '/api/get_tieuchi_deadline.php?id_tieuchi=' + id_tieuchi + '&id_sanxuat=' + id_sanxuat)
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

    // Hàm thay đổi xưởng được chọn
    function changeSelectedXuong() {
        const selectedXuong = document.getElementById('selected_xuong').value;

        // Đổi xưởng thì line quay về "Tất cả line" và nạp lại danh sách line của xưởng đó
        updateLineOptions(selectedXuong);
        updateXuongDisplayName();
        setLineEditMode(false);

        // Tải lại dữ liệu cài đặt mặc định cho xưởng đã chọn
        loadDefaultSettings(document.getElementById('current_dept').value, selectedXuong, '');
    }

    // Hàm thay đổi line được chọn (override người thực hiện theo Xưởng + Line)
    function changeSelectedLine() {
        const selectedXuong = document.getElementById('selected_xuong').value;
        const selectedLine = document.getElementById('selected_line').value;

        updateXuongDisplayName();
        // Khi chọn line cụ thể: chỉ cột người chỉnh được, ngày + loại tính hạn theo xưởng
        setLineEditMode(selectedLine !== '');

        loadDefaultSettings(document.getElementById('current_dept').value, selectedXuong, selectedLine);
    }

    // Hàm nạp danh sách line theo xưởng vào dropdown (map render sẵn từ PHP)
    function updateLineOptions(xuong) {
        const lineSelect = document.getElementById('selected_line');
        if (!lineSelect) {
            return;
        }

        const map = window.XUONG_LINE_MAP || {};
        const lines = (xuong && map[xuong]) ? map[xuong] : [];

        let html = '<option value="">-- Tất cả line --</option>';
        lines.forEach(function(line) {
            html += '<option value="' + escapeHtml(String(line)) + '">' + escapeHtml(String(line)) + '</option>';
        });

        lineSelect.innerHTML = html;
        lineSelect.value = '';
        lineSelect.disabled = !xuong || lines.length === 0;
    }

    // Hàm cập nhật badge tên xưởng (+ line nếu có) trên header modal
    function updateXuongDisplayName() {
        const xuong = document.getElementById('selected_xuong').value;
        const lineSelect = document.getElementById('selected_line');
        const line = lineSelect ? lineSelect.value : '';

        let text = xuong || 'Tất cả xưởng';
        if (xuong && line) {
            text += ' — Line ' + line;
        }
        document.getElementById('xuong_display_name').textContent = text;
    }

    // Hàm bật/tắt chế độ chỉnh theo line: chỉ cho sửa người, khóa ngày + loại tính hạn
    function setLineEditMode(isLineMode) {
        const rows = document.querySelectorAll('#default_settings_tbody tr[id^="ds_row_"]');
        rows.forEach(function(row) {
            const id_tieuchi = row.id.replace('ds_row_', '');
            const methodSelect = document.getElementById('ds_ngay_tinh_han_' + id_tieuchi);
            const daysInput = document.getElementById('ds_so_ngay_xuly_' + id_tieuchi);
            const ownerSelect = document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi);

            if (methodSelect) {
                methodSelect.disabled = isLineMode;
            }
            if (daysInput) {
                daysInput.disabled = isLineMode;
            }
            if (ownerSelect && ownerSelect.options.length > 0 && ownerSelect.options[0].value === '0') {
                ownerSelect.options[0].textContent = isLineMode
                    ? '-- Theo xưởng (không override) --'
                    : '-- Chọn người chịu trách nhiệm --';
            }
        });
    }

    // Hàm tải dữ liệu cài đặt mặc định theo xưởng (+ line nếu có)
    function loadDefaultSettings(dept, xuong, line) {
        const xhr = new XMLHttpRequest();
        const url = window.BASE_URL + '/api/get_default_settings.php?dept=' + encodeURIComponent(dept) + '&xuong=' + encodeURIComponent(xuong || '') + '&line=' + encodeURIComponent(line || '');

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
        const url = window.BASE_URL + '/api/get_default_settings.php?dept=' + encodeURIComponent(dept) + '&xuong=' + encodeURIComponent(xuong || '');

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
        const selectedLine = document.getElementById('selected_line') ? document.getElementById('selected_line').value : '';
        const statusDiv = document.getElementById('default_settings_status');
        const row = document.getElementById('ds_row_' + id_tieuchi);

        showElement(statusDiv, 'block');
        setStatusMessage(statusDiv, 'info', 'Đang lưu cài đặt...');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.BASE_URL + '/actions/save_default_setting.php', true);
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
        xhr.send('id_tieuchi=' + id_tieuchi + '&dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong) + '&line=' + encodeURIComponent(selectedLine) + '&ngay_tinh_han=' + ngayTinhHan + '&so_ngay_xuly=' + soNgayXuly + '&nguoi_chiu_trachnhiem=' + nguoiChiuTrachnhiem + '&csrf_token=' + encodeURIComponent(getCsrfToken()));
    }

    // Hàm lưu tất cả cài đặt mặc định
    function saveAllDefaultSettings(dept) {
        const statusDiv = document.getElementById('default_settings_status');
        const rows = document.querySelectorAll("#default_settings_tbody tr[id^='ds_row_']");
        const selectedXuong = document.getElementById('selected_xuong').value;
        const selectedLine = document.getElementById('selected_line') ? document.getElementById('selected_line').value : '';
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
        xhr.open('POST', window.BASE_URL + '/actions/save_all_default_settings.php', true);
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
        xhr.send('dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong) + '&line=' + encodeURIComponent(selectedLine) + '&settings=' + JSON.stringify(settings) + '&csrf_token=' + encodeURIComponent(getCsrfToken()));
    }

    /* ---------- Khởi tạo (core gọi) ---------- */
    ns.initDeadline = function () {
        updateDeadlineSelectionSummary();

        document.addEventListener('change', function(event) {
            if (event.target && event.target.classList.contains('tieuchi-checkbox')) {
                updateDeadlineSelectionSummary();
            }
        });

        // Khởi tạo mô tả cho phương thức tính hạn xử lý
        if (document.getElementById('ngay_tinh_han')) {
            changeNgayTinhHan();
        }
    };

    /* ---------- Công khai ---------- */
    registerModalCloseHandler('deadlineModal', closeDeadlineModal);
    registerModalCloseHandler('defaultSettingModal', closeDefaultSettingModal);

    // Gọi từ onclick= / onchange= trong views/indexdept/partials/modal-deadline.php,
    // modal-default-settings.php và evaluation-table.php.
    window.openDeadlineModal = openDeadlineModal;
    window.closeDeadlineModal = closeDeadlineModal;
    window.openDefaultSettingModal = openDefaultSettingModal;
    window.closeDefaultSettingModal = closeDefaultSettingModal;
    window.setQuickDays = setQuickDays;
    window.changeNgayTinhHan = changeNgayTinhHan;
    window.selectAllTieuchi = selectAllTieuchi;
    window.updateDeadline = updateDeadline;
    window.updateDeadlineAll = updateDeadlineAll;
    window.changeSelectedXuong = changeSelectedXuong;
    window.changeSelectedLine = changeSelectedLine;
    window.saveDefaultSetting = saveDefaultSetting;
    window.saveAllDefaultSettings = saveAllDefaultSettings;
    window.syncTieuChiWithDefaultSettings = syncTieuChiWithDefaultSettings;
})(window.IndexDept = window.IndexDept || {});
