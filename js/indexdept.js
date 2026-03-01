let IndexDeptConfig = {
    id: 0,
    dept: '',
    xuong: '',
    completedTieuchi: 0,
    totalTieuchi: 0,
    maxPossiblePoints: 0,
    requiredImageCriteria: []
};

function initIndexDept(config) {
    IndexDeptConfig = { ...IndexDeptConfig, ...config };
    initEventListeners();
    initDiemDropdowns();
    updateTotalPoints();
    checkRequiredImages();
}

function openModal() {
    document.getElementById('addCriteriaModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('addCriteriaModal').style.display = 'none';
}

function openDeadlineModal() {
    document.getElementById('deadlineModal').style.display = 'block';
}

function closeDeadlineModal() {
    document.getElementById('deadlineModal').style.display = 'none';
}

function openDefaultSettingModal() {
    document.getElementById('defaultSettingModal').style.display = 'block';
    document.getElementById('current_dept').value = IndexDeptConfig.dept;
    document.getElementById('selected_xuong').value = IndexDeptConfig.xuong;
    changeSelectedXuong();
}

function closeDefaultSettingModal() {
    document.getElementById('defaultSettingModal').style.display = 'none';
}

function openStaffModal(dept) {
    document.getElementById('current_dept').value = dept;
    document.getElementById('staffModal').style.display = 'block';
    document.getElementById('dept_display_name').textContent = getDeptDisplayName(dept);
    loadStaffList(dept);
}

function closeStaffModal() {
    document.getElementById('staffModal').style.display = 'none';
}

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

function number_format(number, decimals) {
    number = parseFloat(number);
    return isNaN(number) ? "0" : number.toFixed(decimals);
}

function showStatus(elementId, message, type) {
    const statusDiv = document.getElementById(elementId);
    if (!statusDiv) return;

    const colors = {
        loading: { bg: '#d1ecf1', text: '#0c5460' },
        success: { bg: '#d4edda', text: '#155724' },
        error: { bg: '#f8d7da', text: '#721c24' },
        warning: { bg: '#fff3cd', text: '#856404' }
    };
    const color = colors[type] || colors.loading;

    statusDiv.style.display = 'block';
    statusDiv.innerHTML = `<div style="color: ${color.text}; padding: 10px; background-color: ${color.bg}; border-radius: 4px;">${message}</div>`;
}

function hideStatus(elementId, delay = 0) {
    setTimeout(() => {
        const statusDiv = document.getElementById(elementId);
        if (statusDiv) statusDiv.style.display = 'none';
    }, delay);
}

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

    updateTotalPoints();
}

function updateTotalPoints() {
    let totalPoints = 0;
    const maxPoints = IndexDeptConfig.maxPossiblePoints;

    document.querySelectorAll('.diem-dropdown').forEach(select => {
        totalPoints += parseFloat(select.value) || 0;
    });

    const totalPointsElement = document.getElementById('total_points');
    if (totalPointsElement) {
        totalPointsElement.innerHTML = number_format(totalPoints, 1) + '/' + number_format(maxPoints, 1);
    }

    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        const percent = (maxPoints > 0) ? (totalPoints / maxPoints) * 100 : 0;
        progressBar.style.width = percent + '%';
        progressBar.innerHTML = Math.round(percent) + '%';

        if (percent < 30) {
            progressBar.style.backgroundColor = "#F44336";
        } else if (percent < 70) {
            progressBar.style.backgroundColor = "#FFC107";
        } else {
            progressBar.style.backgroundColor = "#4CAF50";
        }
    }
}

function setQuickDays(days) {
    document.getElementById('so_ngay_xuly_chung').value = days;
    document.querySelectorAll('.quick-btn').forEach(btn => {
        btn.classList.toggle('active', btn.textContent.includes(days.toString()));
    });
    changeNgayTinhHan();
}

function changeNgayTinhHan() {
    const ngayTinhHan = document.getElementById('ngay_tinh_han').value;
    const noteElement = document.getElementById('note-ngay-tinh');
    if (!noteElement) return;

    const messages = {
        'ngay_vao': 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày',
        'ngay_vao_cong': 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" + 7 ngày',
        'ngay_ra': 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Ra" + 7 ngày',
        'ngay_ra_tru': 'Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Ra" - 7 ngày'
    };
    noteElement.textContent = messages[ngayTinhHan] || '';
}

function selectAllTieuchi(select) {
    document.querySelectorAll('.tieuchi-checkbox').forEach(checkbox => {
        checkbox.checked = select;
    });
}

function updateDeadline(idSanxuat, idTieuchi, dept) {
    const soNgayXuly = document.getElementById('so_ngay_xuly_' + idTieuchi).value;
    const ngayTinhHan = document.getElementById('ngay_tinh_han_' + idTieuchi).value;
    const dateDisplay = document.getElementById('date_display_' + idTieuchi);
    const originalText = dateDisplay.innerHTML;

    dateDisplay.innerHTML = '<img src="img/loading.gif" style="width: 20px; height: 20px;" alt="Đang cập nhật"> Đang cập nhật...';
    dateDisplay.style.backgroundColor = '#e2f0fd';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_deadline_tieuchi.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4) {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        dateDisplay.innerHTML = response.new_date;
                        dateDisplay.style.backgroundColor = '#d4edda';
                        setTimeout(() => { dateDisplay.style.backgroundColor = ''; }, 2000);
                    } else {
                        dateDisplay.innerHTML = '<span style="color: red;">' + (response.message || 'Lỗi') + '</span>';
                        dateDisplay.style.backgroundColor = '#f8d7da';
                        setTimeout(() => {
                            dateDisplay.innerHTML = originalText;
                            dateDisplay.style.backgroundColor = '';
                        }, 3000);
                    }
                } catch (e) {
                    console.error('Lỗi xử lý JSON:', e);
                    dateDisplay.innerHTML = originalText;
                    dateDisplay.style.backgroundColor = '';
                }
            }
        }
    };
    xhr.send('id_sanxuat=' + idSanxuat + '&id_tieuchi=' + idTieuchi + '&so_ngay_xuly=' + soNgayXuly + '&ngay_tinh_han=' + ngayTinhHan + '&dept=' + dept);
}

function updateDeadlineAll(idSanxuat, dept) {
    const soNgayXulyChung = document.getElementById('so_ngay_xuly_chung').value;
    const ngayTinhHan = document.getElementById('ngay_tinh_han').value;
    const selectedTieuchi = [];

    document.querySelectorAll('.tieuchi-checkbox:checked').forEach(checkbox => {
        selectedTieuchi.push(checkbox.value);
    });

    if (selectedTieuchi.length === 0) {
        alert('Vui lòng chọn ít nhất một tiêu chí để áp dụng cài đặt.');
        return;
    }

    showStatus('update_status', 'Đang cập nhật hạn xử lý cho ' + selectedTieuchi.length + ' tiêu chí...', 'loading');

    selectedTieuchi.forEach(tieuchiId => {
        const dateDisplay = document.getElementById('date_display_' + tieuchiId);
        if (dateDisplay) {
            dateDisplay.innerHTML = '<img src="img/loading.gif" style="width: 16px; height: 16px;" alt="Đang cập nhật">';
            dateDisplay.style.backgroundColor = '#e2f0fd';
        }
    });

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_deadline_all.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    showStatus('update_status', 'Đã cập nhật hạn xử lý cho ' + response.updated_count + ' tiêu chí!', 'success');

                    if (response.updated_items) {
                        response.updated_items.forEach(item => {
                            const dateDisplay = document.getElementById('date_display_' + item.id_tieuchi);
                            if (dateDisplay) {
                                dateDisplay.innerHTML = item.new_date;
                                dateDisplay.style.backgroundColor = '#d4edda';
                                setTimeout(() => { dateDisplay.style.backgroundColor = ''; }, 2000);
                            }
                        });
                    }

                    setTimeout(() => {
                        hideStatus('update_status');
                        closeDeadlineModal();
                    }, 1000);
                } else {
                    showStatus('update_status', 'Lỗi: ' + response.message, 'error');
                }
            } catch (e) {
                console.error('Lỗi xử lý JSON:', e);
                showStatus('update_status', 'Lỗi khi xử lý phản hồi từ máy chủ.', 'error');
            }
        }
    };
    xhr.send('id_sanxuat=' + idSanxuat + '&tieuchi=' + JSON.stringify(selectedTieuchi) + '&so_ngay_xuly=' + soNgayXulyChung + '&ngay_tinh_han=' + ngayTinhHan + '&dept=' + dept);
}

function changeSelectedXuong() {
    const selectedXuong = document.getElementById('selected_xuong').value;
    const displayNameElement = document.getElementById('xuong_display_name');

    if (displayNameElement) {
        displayNameElement.textContent = selectedXuong || 'Tất cả xưởng';
    }

    loadDefaultSettings(document.getElementById('current_dept').value, selectedXuong);
}

function loadDefaultSettings(dept, xuong) {
    const xhr = new XMLHttpRequest();
    const url = 'get_default_settings.php?dept=' + encodeURIComponent(dept) + '&xuong=' + encodeURIComponent(xuong || '');

    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    updateDefaultSettingsUI(response.data);
                }
            } catch (e) {
                console.error('Lỗi:', e);
            }
        }
    };
    xhr.send();
}

function updateDefaultSettingsUI(settings) {
    const rows = document.querySelectorAll('#default_settings_tbody tr[id^="ds_row_"]');
    rows.forEach(row => {
        const id_tieuchi = row.id.replace('ds_row_', '');
        const ngayEl = document.getElementById('ds_ngay_tinh_han_' + id_tieuchi);
        const soNgayEl = document.getElementById('ds_so_ngay_xuly_' + id_tieuchi);
        const nguoiEl = document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi);

        if (ngayEl) ngayEl.value = 'ngay_vao';
        if (soNgayEl) soNgayEl.value = '7';
        if (nguoiEl) nguoiEl.value = '0';
    });

    if (settings && settings.length > 0) {
        settings.forEach(setting => {
            const ngayEl = document.getElementById('ds_ngay_tinh_han_' + setting.id_tieuchi);
            const soNgayEl = document.getElementById('ds_so_ngay_xuly_' + setting.id_tieuchi);
            const nguoiEl = document.getElementById('ds_nguoi_chiu_trachnhiem_' + setting.id_tieuchi);

            if (ngayEl) ngayEl.value = setting.ngay_tinh_han;
            if (soNgayEl) soNgayEl.value = setting.so_ngay_xuly;
            if (nguoiEl) nguoiEl.value = setting.nguoi_chiu_trachnhiem_default || '0';
        });
    }
}

function saveDefaultSetting(id_tieuchi, dept) {
    const ngayTinhHan = document.getElementById('ds_ngay_tinh_han_' + id_tieuchi).value;
    const soNgayXuly = document.getElementById('ds_so_ngay_xuly_' + id_tieuchi).value;
    const nguoiChiuTrachnhiem = document.getElementById('ds_nguoi_chiu_trachnhiem_' + id_tieuchi).value;
    const selectedXuong = document.getElementById('selected_xuong').value;
    const row = document.getElementById('ds_row_' + id_tieuchi);

    showStatus('default_settings_status', 'Đang lưu cài đặt...', 'loading');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_default_setting.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    showStatus('default_settings_status', 'Đã lưu cài đặt mặc định!', 'success');
                    row.style.backgroundColor = '#f8f9fa';
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                        hideStatus('default_settings_status');
                    }, 2000);
                } else {
                    showStatus('default_settings_status', 'Lỗi: ' + response.message, 'error');
                }
            } catch (e) {
                showStatus('default_settings_status', 'Lỗi khi xử lý phản hồi.', 'error');
            }
        }
    };
    xhr.send('id_tieuchi=' + id_tieuchi + '&dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong) +
             '&ngay_tinh_han=' + ngayTinhHan + '&so_ngay_xuly=' + soNgayXuly + '&nguoi_chiu_trachnhiem=' + nguoiChiuTrachnhiem);
}

function saveAllDefaultSettings(dept) {
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

    showStatus('default_settings_status', 'Đang lưu tất cả cài đặt...', 'loading');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_all_default_settings.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    showStatus('default_settings_status', 'Đã lưu tất cả cài đặt mặc định!', 'success');
                    rows.forEach(row => {
                        row.style.backgroundColor = '#f8f9fa';
                        setTimeout(() => { row.style.backgroundColor = ''; }, 2000);
                    });
                    hideStatus('default_settings_status', 3000);
                } else {
                    showStatus('default_settings_status', 'Lỗi: ' + response.message, 'error');
                }
            } catch (e) {
                showStatus('default_settings_status', 'Lỗi khi xử lý phản hồi.', 'error');
            }
        }
    };
    xhr.send('dept=' + dept + '&xuong=' + encodeURIComponent(selectedXuong) + '&settings=' + JSON.stringify(settings));
}

function syncTieuChiWithDefaultSettings(dept, xuong) {
    const xhr = new XMLHttpRequest();
    const url = 'get_default_settings.php?dept=' + encodeURIComponent(dept) + '&xuong=' + encodeURIComponent(xuong || '');

    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success && response.data && response.data.length > 0) {
                    response.data.forEach(setting => {
                        const soNgayEl = document.getElementById('so_ngay_xuly_' + setting.id_tieuchi);
                        const ngayTinhHanEl = document.getElementById('ngay_tinh_han_' + setting.id_tieuchi);

                        if (soNgayEl) soNgayEl.value = setting.so_ngay_xuly;
                        if (ngayTinhHanEl) ngayTinhHanEl.value = setting.ngay_tinh_han;
                    });
                    alert('Đã áp dụng giá trị mặc định cho các tiêu chí!');
                }
            } catch (e) {
                console.error('Lỗi khi đồng bộ dữ liệu:', e);
            }
        }
    };
    xhr.send();
}

function loadStaffList(dept) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_staff_list.php?dept=' + encodeURIComponent(dept), true);
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    let html = '';
                    response.data.forEach((staff, index) => {
                        html += `
                        <tr id="staff_row_${staff.id}">
                            <td>${index + 1}</td>
                            <td><input type="text" id="staff_name_${staff.id}" class="form-control" value="${staff.ten}" style="width: 100%;"></td>
                            <td><input type="text" id="staff_position_${staff.id}" class="form-control" value="${staff.chuc_vu || ''}" style="width: 100%;"></td>
                            <td>
                                <button type="button" onclick="updateStaff(${staff.id})" class="btn-default-setting">Cập nhật</button>
                                <button type="button" onclick="deleteStaff(${staff.id})" class="btn-default-setting" style="background-color: #dc3545;">Xóa</button>
                            </td>
                        </tr>`;
                    });
                    document.getElementById('staff_tbody').innerHTML = html;
                }
            } catch (e) {
                console.error(e);
            }
        }
    };
    xhr.send();
}

function addNewStaff() {
    const staffName = document.getElementById('new_staff_name').value.trim();
    const staffPosition = document.getElementById('new_staff_position').value.trim();
    const dept = document.getElementById('staff_current_dept')?.value || document.getElementById('current_dept').value;

    if (!staffName) {
        alert('Vui lòng nhập tên người chịu trách nhiệm.');
        return;
    }

    showStatus('staff_status', 'Đang thêm người chịu trách nhiệm...', 'loading');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'add_staff.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    showStatus('staff_status', 'Đã thêm người chịu trách nhiệm thành công!', 'success');
                    document.getElementById('new_staff_name').value = '';
                    document.getElementById('new_staff_position').value = '';
                    loadStaffList(dept);
                    hideStatus('staff_status', 3000);
                } else {
                    showStatus('staff_status', 'Lỗi: ' + response.message, 'error');
                }
            } catch (e) {
                showStatus('staff_status', 'Lỗi khi xử lý phản hồi.', 'error');
            }
        }
    };
    xhr.send('ten=' + encodeURIComponent(staffName) + '&chuc_vu=' + encodeURIComponent(staffPosition) + '&phong_ban=' + dept);
}

function updateStaff(staffId) {
    const staffName = document.getElementById('staff_name_' + staffId).value.trim();
    const staffPosition = document.getElementById('staff_position_' + staffId).value.trim();
    const dept = document.getElementById('staff_current_dept')?.value || document.getElementById('current_dept').value;

    if (!staffName) {
        alert('Vui lòng nhập tên người chịu trách nhiệm!');
        return;
    }

    showStatus('staff_status', 'Đang cập nhật thông tin...', 'loading');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_staff.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    showStatus('staff_status', 'Đã cập nhật thông tin thành công!', 'success');
                    const row = document.getElementById('staff_row_' + staffId);
                    if (row) {
                        row.style.backgroundColor = '#d4edda';
                        setTimeout(() => { row.style.backgroundColor = ''; }, 2000);
                    }
                    loadStaffList(dept);
                    hideStatus('staff_status', 3000);
                } else {
                    showStatus('staff_status', 'Lỗi: ' + response.message, 'error');
                }
            } catch (e) {
                showStatus('staff_status', 'Lỗi khi xử lý phản hồi.', 'error');
            }
        }
    };
    xhr.send('action=update&id=' + staffId + '&ten=' + encodeURIComponent(staffName) + '&chuc_vu=' + encodeURIComponent(staffPosition));
}

function deleteStaff(staffId) {
    if (!confirm('Bạn có chắc chắn muốn xóa người chịu trách nhiệm này?')) {
        return;
    }

    const dept = document.getElementById('staff_current_dept')?.value || document.getElementById('current_dept').value;
    showStatus('staff_status', 'Đang xóa người chịu trách nhiệm...', 'loading');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_staff.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    showStatus('staff_status', 'Đã xóa người chịu trách nhiệm thành công!', 'success');
                    loadStaffList(dept);
                    hideStatus('staff_status', 3000);
                } else {
                    showStatus('staff_status', 'Lỗi: ' + response.message, 'error');
                }
            } catch (e) {
                showStatus('staff_status', 'Lỗi khi xử lý phản hồi.', 'error');
            }
        }
    };
    xhr.send('action=delete&id=' + staffId);
}

function isRequiredImageCriteria(tieuchiId) {
    return IndexDeptConfig.requiredImageCriteria.includes(parseInt(tieuchiId));
}

function checkImageForTieuchi(tieuchiId, selectElement) {
    let warningDiv = document.getElementById('warning-tieuchi-' + tieuchiId);

    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'warning-tieuchi-' + tieuchiId;
        warningDiv.className = 'warning-message';
        selectElement.parentNode.appendChild(warningDiv);
    }

    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax_check_tieuchi_image.php?id_khsanxuat=' + IndexDeptConfig.id + '&id_tieuchi=' + tieuchiId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const uploadUrl = 'image_handler.php?id=' + IndexDeptConfig.id + '&dept=' + IndexDeptConfig.dept + '&tieuchi_id=' + tieuchiId;

                    if (!response.has_image) {
                        warningDiv.innerHTML = '<div style="color: red; margin-top: 5px; font-size: 14px;">' +
                            '(*) Tiêu chí này bắt buộc phải có hình ảnh đính kèm</div>' +
                            '<div style="margin-top: 5px;"><a href="' + uploadUrl + '" style="color: blue; text-decoration: underline;"><i class="fas fa-upload" style="margin-right: 5px;"></i>Upload ảnh</a></div>';

                        if (parseFloat(selectElement.value) > 0) {
                            selectElement.value = '0';
                            const label = document.getElementById('checkbox_label_' + tieuchiId);
                            const hiddenField = document.getElementById('da_thuchien_' + tieuchiId);

                            if (label) {
                                label.classList.remove('checked');
                                label.classList.add('unchecked');
                                label.innerHTML = '<span class="checkmark">X</span>';
                            }
                            if (hiddenField) hiddenField.value = '0';
                        }

                        for (let i = 0; i < selectElement.options.length; i++) {
                            const optionValue = parseInt(selectElement.options[i].value);
                            if (optionValue > 0 && optionValue !== 999) {
                                selectElement.options[i].disabled = true;
                            }
                        }
                    } else {
                        warningDiv.innerHTML = '<div style="color: #28a745; margin-top: 5px; font-size: 14px;">' +
                            '<i class="fas fa-check-circle" style="margin-right: 5px;"></i>Đã upload hình ảnh</div>' +
                            '<div style="margin-top: 5px;"><a href="' + uploadUrl + '" style="color: blue; text-decoration: underline;"><i class="fas fa-images" style="margin-right: 5px;"></i>Xem/Quản lý</a></div>';

                        for (let i = 0; i < selectElement.options.length; i++) {
                            selectElement.options[i].disabled = false;
                        }
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }
    };
    xhr.send();
}

function checkRequiredImages() {
    document.querySelectorAll('select[data-tieuchi-id]').forEach(select => {
        const tieuchiId = select.getAttribute('data-tieuchi-id');
        if (isRequiredImageCriteria(tieuchiId)) {
            checkImageForTieuchi(tieuchiId, select);
        }
    });
}

function initEventListeners() {
    // Close modals on outside click
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    };

    // Ngay tinh han change
    const ngayTinhHanEl = document.getElementById('ngay_tinh_han');
    if (ngayTinhHanEl) {
        changeNgayTinhHan();
    }
}

function initDiemDropdowns() {
    document.querySelectorAll('.diem-dropdown').forEach(select => {
        select.addEventListener('change', function() {
            updateStatus(this);
        });

        // Set initial checkbox state
        const diem = parseFloat(select.value);
        if (diem > 0) {
            const tieuchi_id = select.getAttribute('data-tieuchi-id');
            const hiddenField = document.getElementById('da_thuchien_' + tieuchi_id);
            if (hiddenField) hiddenField.value = 1;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.indexDeptConfig !== 'undefined') {
        initIndexDept(window.indexDeptConfig);
    }
});
