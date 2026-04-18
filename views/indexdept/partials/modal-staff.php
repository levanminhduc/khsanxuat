    <!-- Modal Quản lý người thực hiện -->
    <div id="staffModal" class="modal staff-modal" role="dialog" aria-modal="true" aria-labelledby="staff-modal-title">
        <div class="modal-content staff-modal__content">
            <button type="button" class="close staff-modal__close" onclick="closeStaffModal()" aria-label="Đóng quản lý người chịu trách nhiệm">&times;</button>

            <div class="staff-modal__header">
                <h3 id="staff-modal-title" class="modal-title staff-modal__title">
                    Quản lý người chịu trách nhiệm - <span id="dept_display_name"></span>
                </h3>
                <p class="staff-modal__subtitle">
                    Danh sách người chịu trách nhiệm dùng cho cài đặt mặc định hạn xử lý của bộ phận.
                </p>
            </div>

            <div id="staff_status" class="staff-modal__status" style="display: none;"></div>
            <input type="hidden" id="current_staff_dept" value="">

            <div class="staff-modal__body">
                <div class="staff-modal__form-card">
                    <h4 class="staff-modal__form-title">Thêm người chịu trách nhiệm mới</h4>
                    <div class="staff-modal__form-row">
                        <input type="text" id="new_staff_name" class="form-control staff-modal__input" placeholder="Tên người chịu trách nhiệm">
                        <input type="text" id="new_staff_position" class="form-control staff-modal__input" placeholder="Chức vụ (không bắt buộc)">
                        <button type="button" onclick="addNewStaff()" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--primary staff-modal__btn-add">Thêm</button>
                    </div>
                </div>

                <div class="table-container staff-modal__table-wrap">
                    <table class="evaluation-table staff-modal__table">
                        <thead>
                            <tr>
                                <th class="staff-modal__col staff-modal__col--stt">STT</th>
                                <th class="staff-modal__col staff-modal__col--name">Tên</th>
                                <th class="staff-modal__col staff-modal__col--position">Chức vụ</th>
                                <th class="staff-modal__col staff-modal__col--actions">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="staff_tbody">
                            <!-- Danh sách người thực hiện sẽ được load bằng JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="staff-modal__footer">
                    <button type="button" onclick="closeStaffModal()" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--secondary">Đóng</button>
                </div>
            </div>
        </div>
    </div>
