        <!-- Modal thêm tiêu chí -->
        <div id="addCriteriaModal" class="modal add-criteria-modal" role="dialog" aria-modal="true" aria-labelledby="add-criteria-title">
            <div class="modal-content add-criteria-modal__content">
                <button type="button" class="close add-criteria-modal__close" onclick="closeModal()" aria-label="Đóng thêm tiêu chí">&times;</button>

                <div class="add-criteria-modal__header">
                    <h3 id="add-criteria-title" class="modal-title add-criteria-modal__title">
                        Thêm tiêu chí mới cho <?php echo htmlspecialchars($dept_display_name); ?>
                    </h3>
                    <p class="add-criteria-modal__subtitle">
                        Tiêu chí mới sẽ được thêm vào danh sách đánh giá của bộ phận hiện tại.
                    </p>
                </div>

                <div class="add-criteria-modal__body">
                    <form action="add_criteria.php" method="POST" class="add-criteria-modal__form">
                        <input type="hidden" name="dept" value="<?php echo htmlspecialchars($dept); ?>">
                        <input type="hidden" name="id_sanxuat" value="<?php echo (int) $id; ?>">

                        <?php if ($dept == 'chuanbi_sanxuat_phong_kt' || $dept == 'kho') : ?>
                        <div class="form-group add-criteria-modal__group">
                            <label for="nhom" class="add-criteria-modal__label">Nhóm</label>
                            <select id="nhom" name="nhom" required class="form-control add-criteria-modal__control">
                                <?php if ($dept == 'chuanbi_sanxuat_phong_kt') : ?>
                                    <option value="Nhóm Nghiệp Vụ">a. Nhóm Nghiệp Vụ</option>
                                    <option value="Nhóm May Mẫu">b. Nhóm May Mẫu</option>
                                    <option value="Nhóm Quy Trình">c. Nhóm Quy Trình Công Nghệ, Thiết Kế Chuyền</option>
                                <?php elseif ($dept == 'kho') : ?>
                                    <option value="Kho Nguyên Liệu">a. Kho Nguyên Liệu</option>
                                    <option value="Kho Phụ Liệu">b. Kho Phụ Liệu</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="form-group add-criteria-modal__group add-criteria-modal__group--sm">
                            <label for="thutu" class="add-criteria-modal__label">Thứ tự</label>
                            <input type="number" id="thutu" name="thutu" required min="1" class="form-control add-criteria-modal__control" placeholder="Ví dụ: 1">
                        </div>

                        <div class="form-group add-criteria-modal__group">
                            <label for="noidung" class="add-criteria-modal__label">Nội dung tiêu chí</label>
                            <textarea id="noidung" name="noidung" required rows="4" class="form-control add-criteria-modal__control add-criteria-modal__textarea" placeholder="Nhập nội dung tiêu chí..."></textarea>
                        </div>

                        <div class="add-criteria-modal__actions">
                            <button type="submit" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--primary add-criteria-modal__btn">Lưu tiêu chí</button>
                            <button type="button" onclick="closeModal()" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--secondary add-criteria-modal__btn">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
