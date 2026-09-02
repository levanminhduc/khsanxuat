        <!-- Modal thêm tiêu chí -->
        <div id="addCriteriaModal" class="modal add-criteria-modal score-options-modal indexdept-modern-modal tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-3 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="add-criteria-title" hidden>
            <div class="modal-content score-options-modal__content tw-relative tw-m-0 tw-flex tw-w-full tw-max-w-xl tw-flex-col tw-overflow-hidden tw-rounded-lg tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft tw-max-h-[88vh] max1024:tw-max-h-[92vh] max768:tw-max-h-[94vh]">
                <button type="button" class="close tw-absolute tw-right-3 tw-top-3 tw-z-10 tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-white/90 tw-text-xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:-tw-translate-y-px hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeModal()" aria-label="Đóng thêm tiêu chí">&times;</button>

                <div class=" score-options-modal__header tw-border-b tw-border-slate-200 tw-bg-slate-50 tw-bg-gradient-to-b tw-from-white tw-from-0% tw-to-[#f8fbff] tw-to-100% tw-px-4 tw-py-3">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                        <div class="score-options-modal__heading tw-flex tw-min-w-0 tw-items-start tw-gap-3">
                            <span class="score-options-modal__icon tw-inline-flex tw-h-9 tw-w-9 tw-flex-none tw-items-center tw-justify-center tw-rounded-md tw-bg-[#143583] tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                                <i class="fas fa-plus"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <span class="score-options-modal__eyebrow tw-text-xs tw-font-bold tw-text-[#143583]">Tiêu chí</span>
                                <h3 id="add-criteria-title" class="modal-title score-options-modal__title tw-m-0 tw-pr-10 tw-text-left tw-text-xl tw-font-bold tw-leading-tight tw-text-slate-950">
                                    Thêm tiêu chí mới
                                </h3>
                            </div>
                        </div>
                        <span class="score-options-modal__dept tw-mr-10 tw-hidden tw-max-w-xs tw-truncate tw-rounded-md tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-slate-700 sm:tw-inline-flex" title="<?php echo htmlspecialchars($dept_display_name); ?>">
                            <?php echo htmlspecialchars($dept_display_name); ?>
                        </span>
                    </div>
                </div>

                <div class=" score-options-modal__body tw-bg-slate-50 tw-px-4 tw-py-4">
                    <form action="<?php echo BASE_URL; ?>/actions/add_criteria.php" method="POST" class=" tw-mt-0 tw-flex tw-flex-col tw-gap-[14px] tw-space-y-3 tw-text-left">
                        <?php echo getCsrfInput(); ?>
                        <input type="hidden" name="dept" value="<?php echo htmlspecialchars($dept); ?>">
                        <input type="hidden" name="id_sanxuat" value="<?php echo (int) $id; ?>">

                        <?php if ($dept == 'chuanbi_sanxuat_phong_kt' || $dept == 'kho') : ?>
                        <div class="form-group tw-mb-0">
                            <label for="nhom" class=" tw-mb-1 tw-block tw-text-xs tw-font-bold tw-text-slate-700">Nhóm</label>
                            <select id="nhom" name="nhom" required class="form-control tw-h-9 tw-min-h-[34px] tw-rounded-md tw-border-slate-300 tw-bg-white tw-text-[13px] tw-leading-[1.28] tw-text-slate-900 tw-shadow-sm tw-transition-[border-color,box-shadow] tw-duration-[.18s] tw-ease-[ease] focus:tw-border-[#143583] focus:[outline:none] focus:tw-ring-2 focus:tw-ring-[#143583]/15">
                                <?php echo getNhomOptionsHtml($dept); ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="tw-grid tw-grid-cols-1 tw-gap-3 sm:tw-grid-cols-[120px_1fr]">
                            <div class="form-group tw-mb-0 tw-max-w-[160px] max768:tw-max-w-full">
                                <label for="thutu" class=" tw-mb-1 tw-block tw-text-xs tw-font-bold tw-text-slate-700">Thứ tự</label>
                                <input type="number" id="thutu" name="thutu" required min="1" class="form-control tw-h-9 tw-min-h-[34px] tw-rounded-md tw-border-slate-300 tw-bg-white tw-text-[13px] tw-leading-[1.28] tw-text-slate-900 tw-shadow-sm tw-transition-[border-color,box-shadow] tw-duration-[.18s] tw-ease-[ease] focus:tw-border-[#143583] focus:[outline:none] focus:tw-ring-2 focus:tw-ring-[#143583]/15" placeholder="1">
                            </div>

                            <div class="form-group tw-mb-0">
                                <label for="noidung" class=" tw-mb-1 tw-block tw-text-xs tw-font-bold tw-text-slate-700">Nội dung tiêu chí</label>
                                <textarea id="noidung" name="noidung" required rows="4" class="form-control tw-min-h-28 tw-rounded-md tw-border-slate-300 tw-bg-white tw-text-[13px] tw-leading-[1.28] tw-text-slate-900 tw-shadow-sm tw-transition-[border-color,box-shadow] tw-duration-[.18s] tw-ease-[ease] focus:tw-border-[#143583] focus:[outline:none] focus:tw-ring-2 focus:tw-ring-[#143583]/15" placeholder="Nhập nội dung tiêu chí..."></textarea>
                            </div>
                        </div>

                        <div class=" tw-flex tw-flex-col-reverse tw-gap-2 tw-border-t [border-top-style:solid] tw-border-slate-100 tw-pt-3 sm:tw-flex-row sm:tw-justify-end max768:tw-w-full max768:tw-flex-row max768:tw-items-center max768:tw-justify-between">
                            <button type="button" onclick="closeModal()" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary tw-inline-flex tw-h-9 tw-min-w-[102px] tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-slate-300 tw-bg-white tw-px-3 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50 max768:tw-min-w-[92px] max768:tw-flex-none max768:tw-whitespace-nowrap">Hủy</button>
                            <button type="submit" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--primary tw-inline-flex tw-h-9 tw-min-w-[102px] tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-[#143583] tw-bg-[#143583] tw-px-3 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299] max768:tw-max-w-[calc(100%-108px)] max768:tw-min-w-[min(220px,calc(100%-108px))] max768:tw-flex-none max768:tw-whitespace-nowrap">
                                <i class="fas fa-floppy-disk tw-mr-2" aria-hidden="true"></i>
                                Lưu tiêu chí
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
