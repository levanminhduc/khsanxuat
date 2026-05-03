        <!-- Modal thêm tiêu chí -->
        <div id="addCriteriaModal" class="modal add-criteria-modal score-options-modal indexdept-modern-modal tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-4 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="add-criteria-title" hidden>
            <div class="modal-content add-criteria-modal__content score-options-modal__content tw-relative tw-m-0 tw-w-full tw-max-w-2xl tw-overflow-hidden tw-rounded-2xl tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft">
                <button type="button" class="close add-criteria-modal__close tw-absolute tw-right-4 tw-top-4 tw-z-10 tw-inline-flex tw-h-10 tw-w-10 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-slate-200 tw-bg-white/90 tw-text-2xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeModal()" aria-label="Đóng thêm tiêu chí">&times;</button>

                <div class="add-criteria-modal__header score-options-modal__header tw-border-b tw-border-slate-200 tw-bg-slate-50 tw-px-6 tw-py-5">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                        <div class="score-options-modal__heading tw-flex tw-min-w-0 tw-items-start tw-gap-4">
                            <span class="score-options-modal__icon tw-inline-flex tw-h-12 tw-w-12 tw-flex-none tw-items-center tw-justify-center tw-rounded-2xl tw-bg-[#143583] tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                                <i class="fas fa-plus"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <span class="score-options-modal__eyebrow tw-text-xs tw-font-bold tw-uppercase tw-tracking-[0.18em] tw-text-[#143583]">Tiêu chí</span>
                                <h3 id="add-criteria-title" class="modal-title add-criteria-modal__title score-options-modal__title tw-m-0 tw-mt-1 tw-pr-12 tw-text-2xl tw-font-bold tw-leading-tight tw-text-slate-950">
                                    Thêm tiêu chí mới
                                </h3>
                            </div>
                        </div>
                        <span class="score-options-modal__dept tw-mr-12 tw-hidden tw-max-w-xs tw-truncate tw-rounded-full tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-3 tw-py-1 tw-text-sm tw-font-semibold tw-text-slate-700 sm:tw-inline-flex" title="<?php echo htmlspecialchars($dept_display_name); ?>">
                            <?php echo htmlspecialchars($dept_display_name); ?>
                        </span>
                    </div>
                </div>

                <div class="add-criteria-modal__body score-options-modal__body tw-px-6 tw-py-6">
                    <form action="add_criteria.php" method="POST" class="add-criteria-modal__form tw-space-y-5">
                        <input type="hidden" name="dept" value="<?php echo htmlspecialchars($dept); ?>">
                        <input type="hidden" name="id_sanxuat" value="<?php echo (int) $id; ?>">

                        <?php if ($dept == 'chuanbi_sanxuat_phong_kt' || $dept == 'kho') : ?>
                        <div class="form-group add-criteria-modal__group">
                            <label for="nhom" class="add-criteria-modal__label tw-mb-2 tw-block tw-text-sm tw-font-bold tw-text-slate-700">Nhóm</label>
                            <select id="nhom" name="nhom" required class="form-control add-criteria-modal__control tw-h-11 tw-rounded-xl tw-border-slate-300 tw-bg-white tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15">
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

                        <div class="tw-grid tw-grid-cols-1 tw-gap-5 sm:tw-grid-cols-[160px_1fr]">
                            <div class="form-group add-criteria-modal__group add-criteria-modal__group--sm">
                                <label for="thutu" class="add-criteria-modal__label tw-mb-2 tw-block tw-text-sm tw-font-bold tw-text-slate-700">Thứ tự</label>
                                <input type="number" id="thutu" name="thutu" required min="1" class="form-control add-criteria-modal__control tw-h-11 tw-rounded-xl tw-border-slate-300 tw-bg-white tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15" placeholder="1">
                            </div>

                            <div class="form-group add-criteria-modal__group">
                                <label for="noidung" class="add-criteria-modal__label tw-mb-2 tw-block tw-text-sm tw-font-bold tw-text-slate-700">Nội dung tiêu chí</label>
                                <textarea id="noidung" name="noidung" required rows="5" class="form-control add-criteria-modal__control add-criteria-modal__textarea tw-min-h-36 tw-rounded-xl tw-border-slate-300 tw-bg-white tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15" placeholder="Nhập nội dung tiêu chí..."></textarea>
                            </div>
                        </div>

                        <div class="add-criteria-modal__actions tw-flex tw-flex-col-reverse tw-gap-3 tw-border-t tw-border-slate-100 tw-pt-5 sm:tw-flex-row sm:tw-justify-end">
                            <button type="button" onclick="closeModal()" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary add-criteria-modal__btn tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-slate-300 tw-bg-white tw-px-5 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50">Hủy</button>
                            <button type="submit" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--primary add-criteria-modal__btn tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-[#143583] tw-bg-[#143583] tw-px-5 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299]">
                                <i class="fas fa-floppy-disk tw-mr-2" aria-hidden="true"></i>
                                Lưu tiêu chí
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
