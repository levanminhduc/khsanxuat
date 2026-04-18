(function () {
    const INDEXDEPT_BOOTSTRAP = window.INDEXDEPT_BOOTSTRAP || {};

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
        document.getElementById('current_dept').value = (INDEXDEPT_BOOTSTRAP.dept || '');
        document.getElementById('selected_xuong').value = (INDEXDEPT_BOOTSTRAP.xuong || '');
        changeSelectedXuong();
    }

    function closeDefaultSettingModal() {
        document.getElementById('defaultSettingModal').style.display = 'none';
    }

    function closeStaffModal() {
        document.getElementById('staffModal').style.display = 'none';
    }

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
        progressBar.style.width = percent + '%';
        progressBar.innerHTML = Math.round(percent) + '%';

        // Thay đổi màu sắc dựa vào phần trăm hoàn thành
        if (percent < 30) {
            progressBar.style.backgroundColor = "#F44336"; // Đỏ
        } else if (percent < 70) {
            progressBar.style.backgroundColor = "#FFC107"; // Vàng
        } else {
            progressBar.style.backgroundColor = "#4CAF50"; // Xanh lá
        }
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
        dateDisplay.innerHTML = '<img src="img/loading.gif" style="width: 20px; height: 20px;" alt="Đang cập nhật"> Đang cập nhật...';
        dateDisplay.style.backgroundColor = '#e2f0fd';

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
                            dateDisplay.innerHTML = response.new_date;
                            dateDisplay.classList.add('update-success');
                            // Hiệu ứng flash cho thanh deadline
                            dateDisplay.style.backgroundColor = '#d4edda';

                            // Giữ nguyên giá trị ô input mà người dùng đã nhập, không dùng giá trị từ server
                            document.getElementById('so_ngay_xuly_' + idTieuchi).value = soNgayXuly;
                            document.getElementById('ngay_tinh_han_' + idTieuchi).value = ngayTinhHan;

                            setTimeout(function() {
                                dateDisplay.classList.remove('update-success');
                                dateDisplay.style.backgroundColor = '';
                            }, 2000);

                            // Comment phần confirm này lại vì người dùng không muốn nó xuất hiện
                            /*if (confirm('Bạn có muốn lưu số ngày này vào cài đặt mặc định cho xưởng này không?')) {
                                saveDefaultSetting(idTieuchi, dept);
                            }*/
                        } else {
                            // Hiển thị lỗi trong khung deadline
                            dateDisplay.innerHTML = '<span style="color: red;">' + (response.message || 'Lỗi không xác định') + '</span>';
                            dateDisplay.style.backgroundColor = '#f8d7da';
                            setTimeout(function() {
                                dateDisplay.innerHTML = originalText;
                                dateDisplay.style.backgroundColor = '';
                            }, 3000);
                        }
                    } catch (e) {
                        console.error('Lỗi xử lý JSON:', e);
                        dateDisplay.innerHTML = '<span style="color: red;">Lỗi xử lý dữ liệu</span>';
                        dateDisplay.style.backgroundColor = '#f8d7da';
                        setTimeout(function() {
                            dateDisplay.innerHTML = originalText;
                            dateDisplay.style.backgroundColor = '';
                        }, 3000);
                    }
                } else {
                    dateDisplay.innerHTML = '<span style="color: red;">Lỗi kết nối máy chủ</span>';
                    dateDisplay.style.backgroundColor = '#f8d7da';
                    setTimeout(function() {
                        dateDisplay.innerHTML = originalText;
                        dateDisplay.style.backgroundColor = '';
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

        updateStatusDiv.style.display = 'block';
        updateStatusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px; display: flex; align-items: center;"><img src="img/loading.gif" style="width: 20px; height: 20px; margin-right: 10px;" alt="Đang cập nhật"> Đang cập nhật hạn xử lý cho ' + selectedTieuchi.length + ' tiêu chí...</div>';

        // Tạo hiệu ứng loading cho các tiêu chí đang được cập nhật
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
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            updateStatusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã cập nhật hạn xử lý cho ' + response.updated_count + ' tiêu chí!</div>';

                            // Cập nhật hiển thị trên giao diện
                            if (response.updated_items) {
                                response.updated_items.forEach(item => {
                                    const dateDisplay = document.getElementById('date_display_' + item.id_tieuchi);
                                    if (dateDisplay) {
                                        dateDisplay.innerHTML = item.new_date;
                                        dateDisplay.classList.add('update-success');
                                        dateDisplay.style.backgroundColor = '#d4edda';

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
                                            dateDisplay.style.backgroundColor = '';
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
                                    updateStatusDiv.style.display = 'none';
                                    closeDeadlineModal();
                                }*/

                                // Chỉ đóng modal sau khi hoàn thành
                                updateStatusDiv.style.display = 'none';
                                closeDeadlineModal();
                            }, 1000);
                        } else {
                            updateStatusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';

                            // Khôi phục trạng thái ban đầu cho các tiêu chí
                            selectedTieuchi.forEach(tieuchiId => {
                                const dateDisplay = document.getElementById('date_display_' + tieuchiId);
                                if (dateDisplay) {
                                    dateDisplay.style.backgroundColor = '';
                                    // Tải lại dữ liệu hiện tại từ cơ sở dữ liệu
                                    loadCurrentDeadline(tieuchiId, idSanxuat);
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Lỗi xử lý JSON:', e);
                        updateStatusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';

                        // Khôi phục trạng thái ban đầu cho các tiêu chí
                        selectedTieuchi.forEach(tieuchiId => {
                            const dateDisplay = document.getElementById('date_display_' + tieuchiId);
                            if (dateDisplay) {
                                dateDisplay.style.backgroundColor = '';
                                loadCurrentDeadline(tieuchiId, idSanxuat);
                            }
                        });
                    }
                } else {
                    updateStatusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';

                    // Khôi phục trạng thái ban đầu cho các tiêu chí
                    selectedTieuchi.forEach(tieuchiId => {
                        const dateDisplay = document.getElementById('date_display_' + tieuchiId);
                        if (dateDisplay) {
                            dateDisplay.style.backgroundColor = '';
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
        document.getElementById('staffModal').style.display = 'block';
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
                                html = '<tr><td colspan="4" style="text-align: center; color: #64748b;">Chưa có người chịu trách nhiệm nào cho bộ phận này.</td></tr>';
                            }

                            staffList.forEach(function(staff, index) {
                                html += `
                                <tr id="staff_row_${staff.id}">
                                    <td>${index + 1}</td>
                                    <td><input type="text" id="staff_name_${staff.id}" class="form-control staff-modal__input" value="${staff.ten}"></td>
                                    <td><input type="text" id="staff_position_${staff.id}" class="form-control staff-modal__input" value="${staff.chuc_vu || ''}"></td>
                                    <td>
                                        <button type="button" onclick="updateStaff(${staff.id})" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--primary staff-modal__action-btn">Cập nhật</button>
                                        <button type="button" onclick="deleteStaff(${staff.id})" class="btn-add-criteria default-settings-modal__btn staff-modal__action-btn staff-modal__action-btn--danger">Xóa</button>
                                    </td>
                                </tr>`;
                            });

                            document.getElementById('staff_tbody').innerHTML = html;
                        } else {
                            document.getElementById('staff_tbody').innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Lỗi: ' + response.message + '</td></tr>';
                        }
                    } catch (e) {
                        console.error(e);
                        document.getElementById('staff_tbody').innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Lỗi khi xử lý phản hồi từ máy chủ.</td></tr>';
                    }
                } else {
                    document.getElementById('staff_tbody').innerHTML = '<tr><td colspan="4" style="text-align: center; color: red;">Lỗi khi tải danh sách người thực hiện.</td></tr>';
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

        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang thêm người chịu trách nhiệm...</div>';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'add_staff.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã thêm người chịu trách nhiệm thành công!</div>';
                            document.getElementById('new_staff_name').value = '';
                            document.getElementById('new_staff_position').value = '';
                            loadStaffList(dept);

                            setTimeout(function() {
                                statusDiv.style.display = 'none';
                            }, 3000);
                        } else {
                            statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                        }
                    } catch (e) {
                        console.error(e);
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                    }
                } else {
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
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
        updateTotalPoints();

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
                        <div style="margin-bottom: 8px;">
                            <strong style="color:rgb(38, 0, 255); font-size: 12px;">(*) Tiêu chí này bắt buộc phải có hình ảnh đính kèm</strong>
                        </div>
                        <a href="#"
                           class="upload-image-btn">
                            <i class="fas fa-upload" style="margin-right: 5px;"></i>
                            Upload hình ảnh
                        </a>
                    `;

                    // Thêm style cho nút upload
                    noteColumn.prepend(noteText);
                    */

                    // Thêm style cho nút upload
                    const style = document.createElement('style');
                    style.textContent = `
                        .upload-image-btn {
                            display: inline-flex;
                            align-items: center;
                            padding: 6px 12px;
                            background-color: #1976d2;
                            color: white;
                            text-decoration: none;
                            border-radius: 4px;
                            font-size: 12px;
                            transition: all 0.3s ease;
                            border: none;
                            cursor: pointer;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }

                        .upload-image-btn:hover {
                            background-color: #1565c0;
                            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                            transform: translateY(-1px);
                        }

                        .upload-image-btn:active {
                            transform: translateY(0);
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }

                        .image-required-warning {
                            margin-bottom: 5px;
                            padding: 8px;
                            background-color: #fff3f3;
                            border-radius: 4px;
                            border: 1px solid #ffcdd2;
                        }
                    `;
                    document.head.appendChild(style);
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

        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang cập nhật thông tin...</div>';

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
                            statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã cập nhật thông tin thành công!</div>';

                            // Highlight dòng vừa cập nhật
                            const row = document.getElementById('staff_row_' + staffId);
                            if (row) {
                                row.style.backgroundColor = '#d4edda';
                                setTimeout(function() {
                                    row.style.backgroundColor = '';
                                }, 2000);
                            }

                            // Cập nhật danh sách nhân viên để đảm bảo các thay đổi được hiển thị
                            loadStaffList(dept);

                            // Ẩn thông báo sau 3 giây
                            setTimeout(function() {
                                statusDiv.style.display = 'none';
                            }, 3000);
                        } else {
                            // Hiển thị thông báo lỗi
                            statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                        }
                    } catch (e) {
                        console.error(e);
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                    }
                } else {
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
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

        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang xóa người chịu trách nhiệm...</div>';

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
                            statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã xóa người chịu trách nhiệm thành công!</div>';

                            // Tải lại danh sách
                            loadStaffList(dept);

                            // Ẩn thông báo sau 3 giây
                            setTimeout(function() {
                                statusDiv.style.display = 'none';
                            }, 3000);
                        } else {
                            // Hiển thị thông báo lỗi
                            statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                        }
                    } catch (e) {
                        console.error(e);
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                    }
                } else {
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
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

        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang lưu cài đặt...</div>';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'save_default_setting.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã lưu cài đặt mặc định!</div>';
                            row.style.backgroundColor = '#f8f9fa';
                            setTimeout(function() {
                                row.style.backgroundColor = '';
                                statusDiv.style.display = 'none';
                            }, 2000);
                        } else {
                            statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                        }
                    } catch (e) {
                        console.error(e);
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                    }
                } else {
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
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

        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang lưu tất cả cài đặt...</div>';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'save_all_default_settings.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã lưu tất cả cài đặt mặc định!</div>';
                            rows.forEach(row => {
                                row.style.backgroundColor = '#f8f9fa';
                                setTimeout(function() {
                                    row.style.backgroundColor = '';
                                }, 2000);
                            });

                            setTimeout(function() {
                                statusDiv.style.display = 'none';
                            }, 3000);
                        } else {
                            statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                        }
                    } catch (e) {
                        console.error(e);
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                    }
                } else {
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
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

        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang áp dụng cài đặt mặc định...</div>';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'apply_default_setting.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã áp dụng cài đặt mặc định thành công!</div>';
                            row.style.backgroundColor = '#d4edda';
                            setTimeout(function() {
                                row.style.backgroundColor = '';
                                statusDiv.style.display = 'none';
                            }, 2000);
                        } else {
                            statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi: ' + response.message + '</div>';
                        }
                    } catch (e) {
                        console.error(e);
                        statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Lỗi khi xử lý phản hồi từ máy chủ.</div>';
                    }
                } else {
                    statusDiv.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border-radius: 4px;">Có lỗi xảy ra. Vui lòng thử lại sau.</div>';
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

        statusDiv.style.display = 'block';
        statusDiv.innerHTML = '<div style="color: #0c5460; padding: 10px; background-color: #d1ecf1; border-radius: 4px;">Đang áp dụng tất cả cài đặt mặc định...</div>';

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
                                row.style.backgroundColor = '#d4edda';
                                setTimeout(function() {
                                    row.style.backgroundColor = '';
                                }, 2000);
                            } else {
                                errorCount++;
                                row.style.backgroundColor = '#f8d7da';
                                setTimeout(function() {
                                    row.style.backgroundColor = '';
                                }, 2000);
                            }

                            // Kiểm tra nếu đã hoàn thành tất cả
                            if (completedCount + errorCount === rows.length) {
                                if (errorCount === 0) {
                                    statusDiv.innerHTML = '<div style="color: #155724; padding: 10px; background-color: #d4edda; border-radius: 4px;">Đã áp dụng tất cả cài đặt mặc định thành công!</div>';
                                } else {
                                    statusDiv.innerHTML = '<div style="color: #856404; padding: 10px; background-color: #fff3cd; border-radius: 4px;">Đã áp dụng ' + completedCount + '/' + rows.length + ' cài đặt mặc định. Có ' + errorCount + ' lỗi.</div>';
                                }

                                setTimeout(function() {
                                    statusDiv.style.display = 'none';
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
                        warningDiv.innerHTML = '<div style="color: red; margin-top: 5px; font-size: 14px;">' +
                            '(*) Tiêu chí này bắt buộc phải có hình ảnh đính kèm' +
                            '</div>' +
                            '<div style="margin-top: 5px;"><a href="' + uploadUrl + '" style="color: blue; text-decoration: underline;"><i class="fas fa-upload" style="margin-right: 5px;"></i>Upload ảnh</a></div>';

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
                            var optionValue = parseInt(selectElement.options[i].value);
                            if (optionValue > 0 && optionValue !== 999) {
                                selectElement.options[i].disabled = true;
                            }
                        }
                    } else {
                        // Đã có hình ảnh, hiển thị thông báo thành công và enable tất cả lựa chọn
                        var uploadUrl = buildAutoSelectImageUrl(tieuchiId);
                        warningDiv.innerHTML = '<div style="color: #28a745; margin-top: 5px; font-size: 14px;">' +
                            '<i class="fas fa-check-circle" style="margin-right: 5px;"></i>Đã upload hình ảnh cho tiêu chí này' +
                            '</div>' +
                            '<div style="margin-top: 5px;"><a href="' + uploadUrl + '" style="color: blue; text-decoration: underline;"><i class="fas fa-images" style="margin-right: 5px;"></i>Xem/Quản lý hình ảnh</a></div>';

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
    window.syncTieuChiWithDefaultSettings = typeof syncTieuChiWithDefaultSettings === 'function' ? syncTieuChiWithDefaultSettings : window.syncTieuChiWithDefaultSettings;

    // Auto-redirect to image handler when requested via query string
    if (INDEXDEPT_BOOTSTRAP.autoSelectImage && INDEXDEPT_BOOTSTRAP.autoSelectTieuchiId) {
        const autoSelectTieuchiId = INDEXDEPT_BOOTSTRAP.autoSelectTieuchiId;
        document.addEventListener('DOMContentLoaded', function() {
            window.location.href = 'image_handler.php?id=' + encodeURIComponent(INDEXDEPT_BOOTSTRAP.id || '') + '&dept=' + encodeURIComponent(INDEXDEPT_BOOTSTRAP.dept || '') + '&tieuchi_id=' + encodeURIComponent(autoSelectTieuchiId);
        });
    }
})();
