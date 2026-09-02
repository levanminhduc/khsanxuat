/* ==========================================================
   indexdept-staff.js — modal quản lý người chịu trách nhiệm của bộ phận.
   Nạp SAU indexdept-core.js.
   ========================================================== */
(function (ns) {
    // Helper dùng chung, lấy từ indexdept-core.js (nạp trước file này).
    const showElement = ns.showElement,
          hideElement = ns.hideElement,
          escapeHtml = ns.escapeHtml,
          setStatusMessage = ns.setStatusMessage,
          buildTableMessageRow = ns.buildTableMessageRow,
          flashRow = ns.flashRow,
          getCsrfToken = ns.getCsrfToken,
          registerModalCloseHandler = ns.registerModalCloseHandler;
    const INDEXDEPT_BOOTSTRAP = window.INDEXDEPT_BOOTSTRAP || {};

    function closeStaffModal() {
        hideElement(document.getElementById('staffModal'));
    }

    // Hàm mở modal quản lý người thực hiện
    function openStaffModal(dept) {
        document.getElementById('current_staff_dept').value = dept;
        showElement(document.getElementById('staffModal'), 'block');
        document.getElementById('dept_display_name').textContent = (INDEXDEPT_BOOTSTRAP.deptDisplayName || '');

        loadStaffList(dept);
        setTimeout(function() {
            const nameField = document.getElementById('new_staff_name');
            if (nameField) {
                nameField.focus();
            }
        }, 0);
    }

    // Hàm tải danh sách người thực hiện
    function loadStaffList(dept) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', window.BASE_URL + '/api/get_staff_list.php?dept=' + encodeURIComponent(dept), true);
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            const staffList = response.data;
                            let html = '';
                            const countLabel = document.getElementById('staff_count_label');

                            if (!staffList.length) {
                                html = buildTableMessageRow(4, 'muted', 'Chưa có người chịu trách nhiệm nào cho bộ phận này.');
                            }

                            if (countLabel) {
                                countLabel.textContent = staffList.length + ' người chịu trách nhiệm';
                            }

                            staffList.forEach(function(staff, index) {
                                html += `
                                <tr id="staff_row_${staff.id}" class="staff-modal__row tw-bg-white tw-transition hover:tw-bg-[#eef5ff]/60">
                                    <td class="staff-modal__index-cell tw-whitespace-nowrap tw-border tw-border-solid tw-border-[#ddd] tw-px-2 tw-py-2">${index + 1}</td>
                                    <td class="tw-border tw-border-solid tw-border-[#ddd] tw-px-2 tw-py-2"><input type="text" id="staff_name_${staff.id}" class="form-control staff-modal__input tw-h-8 tw-rounded-md tw-border-slate-300 tw-bg-white tw-text-sm tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-2 focus:tw-ring-[#143583]/15" value="${escapeHtml(staff.ten)}"></td>
                                    <td class="tw-border tw-border-solid tw-border-[#ddd] tw-px-2 tw-py-2"><input type="text" id="staff_position_${staff.id}" class="form-control staff-modal__input tw-h-8 tw-rounded-md tw-border-slate-300 tw-bg-white tw-text-sm tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-2 focus:tw-ring-[#143583]/15" value="${escapeHtml(staff.chuc_vu || '')}"></td>
                                    <td class="tw-border tw-border-solid tw-border-[#ddd] tw-px-2 tw-py-2">
                                        <div class="staff-modal__action-list">
                                            <button type="button" onclick="updateStaff(${staff.id})" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary staff-modal__action-btn tw-inline-flex tw-h-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-px-2 tw-text-xs tw-font-bold tw-text-[#143583] tw-shadow-sm tw-transition hover:tw-bg-[#dbeafe]">
                                                <i class="fas fa-floppy-disk" aria-hidden="true"></i>
                                                Cập nhật
                                            </button>
                                            <button type="button" onclick="deleteStaff(${staff.id})" class="btn-add-criteria default-settings-modal__btn staff-modal__action-btn staff-modal__action-btn--danger tw-inline-flex tw-h-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-rose-200 tw-bg-rose-50 tw-px-2 tw-text-xs tw-font-bold tw-text-rose-800 tw-shadow-sm tw-transition hover:tw-bg-rose-100">
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                                Xóa
                                            </button>
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
        xhr.open('POST', window.BASE_URL + '/actions/add_staff.php', true);
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
        xhr.send('ten=' + encodeURIComponent(staffName) + '&chuc_vu=' + encodeURIComponent(staffPosition) + '&phong_ban=' + dept + '&csrf_token=' + encodeURIComponent(getCsrfToken()));
    }

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
        xhr.open('POST', window.BASE_URL + '/pages/manage_staff.php', true);
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
        xhr.open('POST', window.BASE_URL + '/pages/manage_staff.php', true);
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

    /* ---------- Công khai ---------- */
    registerModalCloseHandler('staffModal', closeStaffModal);

    // Gọi từ onclick= trong views/indexdept/partials/modal-staff.php,
    // modal-default-settings.php và từ HTML dựng trong loadStaffList().
    window.openStaffModal = openStaffModal;
    window.closeStaffModal = closeStaffModal;
    window.addNewStaff = addNewStaff;
    window.updateStaff = updateStaff;
    window.deleteStaff = deleteStaff;
})(window.IndexDept = window.IndexDept || {});
