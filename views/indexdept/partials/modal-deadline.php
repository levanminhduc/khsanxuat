        <!-- Modal cài đặt hạn xử lý -->
        <div id="deadlineModal" class="modal deadline-modal score-options-modal indexdept-modern-modal tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-4 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="deadline-modal-title" hidden>
            <div class="modal-content deadline-modal__content score-options-modal__content tw-relative tw-m-0 tw-w-full tw-max-w-3xl tw-overflow-hidden tw-rounded-2xl tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft">
                <button type="button" class="close deadline-modal__close tw-absolute tw-right-4 tw-top-4 tw-z-10 tw-inline-flex tw-h-10 tw-w-10 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-slate-200 tw-bg-white/90 tw-text-2xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeDeadlineModal()" aria-label="Đóng cài đặt hạn xử lý">&times;</button>

                <div class="deadline-modal__header score-options-modal__header tw-border-b tw-border-slate-200 tw-bg-slate-50 tw-px-6 tw-py-5">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                        <div class="score-options-modal__heading tw-flex tw-min-w-0 tw-items-start tw-gap-4">
                            <span class="score-options-modal__icon tw-inline-flex tw-h-12 tw-w-12 tw-flex-none tw-items-center tw-justify-center tw-rounded-2xl tw-bg-[#143583] tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                                <i class="fas fa-calendar-check"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <span class="score-options-modal__eyebrow tw-text-xs tw-font-bold tw-uppercase tw-tracking-[0.18em] tw-text-[#143583]">Deadline</span>
                                <h3 id="deadline-modal-title" class="modal-title deadline-modal__title score-options-modal__title tw-m-0 tw-mt-1 tw-pr-12 tw-text-2xl tw-font-bold tw-leading-tight tw-text-slate-950">Cài đặt hạn xử lý chung</h3>
                            </div>
                        </div>
                        <span class="score-options-modal__dept tw-mr-12 tw-hidden tw-max-w-xs tw-truncate tw-rounded-full tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-3 tw-py-1 tw-text-sm tw-font-semibold tw-text-slate-700 sm:tw-inline-flex" title="<?php echo htmlspecialchars($dept_display_name); ?>">
                            <?php echo htmlspecialchars($dept_display_name); ?>
                        </span>
                    </div>
                </div>

                <div class="deadline-modal__body score-options-modal__body tw-space-y-5 tw-px-6 tw-py-6">
                    <div class="tw-grid tw-grid-cols-1 tw-gap-4 md:tw-grid-cols-2">
                        <div class="form-group deadline-modal__group tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-white tw-p-4 tw-shadow-sm">
                            <label for="so_ngay_xuly_chung" class="deadline-modal__label tw-mb-2 tw-block tw-text-sm tw-font-bold tw-text-slate-700">Số ngày xử lý</label>
                            <input type="number" id="so_ngay_xuly_chung" value="<?php echo $so_ngay_xuly; ?>" min="1" max="30" required class="deadline-modal__input tw-h-11 tw-w-full tw-rounded-xl tw-border tw-border-slate-300 tw-bg-white tw-px-3 tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-outline-none focus:tw-ring-4 focus:tw-ring-[#143583]/15">
                            <div class="quick-suggestion deadline-modal__quick-suggestion tw-mt-3 tw-flex tw-flex-wrap tw-items-center tw-gap-2">
                                <span class="tw-text-sm tw-font-semibold tw-text-slate-500">Gợi ý</span>
                                <button type="button" onclick="setQuickDays(7)" class="quick-btn tw-inline-flex tw-h-8 tw-items-center tw-rounded-full tw-border tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-px-3 tw-text-xs tw-font-bold tw-text-[#143583] tw-transition hover:tw-bg-[#dbeafe]">7 ngày</button>
                                <button type="button" onclick="setQuickDays(14)" class="quick-btn tw-inline-flex tw-h-8 tw-items-center tw-rounded-full tw-border tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-px-3 tw-text-xs tw-font-bold tw-text-[#143583] tw-transition hover:tw-bg-[#dbeafe]">14 ngày</button>
                            </div>
                            <p class="note deadline-modal__note tw-m-0 tw-mt-3 tw-text-xs tw-leading-5 tw-text-slate-500">Ví dụ: nếu nhập 7, hạn xử lý là ngày vào trừ 7 ngày.</p>
                        </div>

                        <div class="form-group deadline-modal__group tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-white tw-p-4 tw-shadow-sm">
                            <label for="ngay_tinh_han" class="deadline-modal__label tw-mb-2 tw-block tw-text-sm tw-font-bold tw-text-slate-700">Ngày tính hạn</label>
                            <select id="ngay_tinh_han" onchange="changeNgayTinhHan()" class="deadline-modal__select tw-h-11 tw-w-full tw-rounded-xl tw-border tw-border-slate-300 tw-bg-white tw-px-3 tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-outline-none focus:tw-ring-4 focus:tw-ring-[#143583]/15">
                                <option value="ngay_vao" selected>Ngày vào trừ số ngày</option>
                                <option value="ngay_vao_cong">Ngày vào cộng số ngày</option>
                                <option value="ngay_ra">Ngày ra cộng số ngày</option>
                                <option value="ngay_ra_tru">Ngày ra trừ số ngày</option>
                            </select>
                            <p class="note-ngay-tinh deadline-modal__note deadline-modal__note--method tw-m-0 tw-mt-3 tw-text-xs tw-leading-5 tw-text-slate-500" id="note-ngay-tinh">Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày</p>
                        </div>
                    </div>

                    <div class="form-group deadline-modal__group tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-slate-50 tw-p-4">
                        <div class="tw-mb-3 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-3">
                            <label class="deadline-modal__label tw-m-0 tw-text-sm tw-font-bold tw-text-slate-800">Áp dụng cho tiêu chí</label>
                            <div class="deadline-modal__bulk-actions tw-flex tw-gap-2">
                                <button type="button" onclick="selectAllTieuchi(true)" class="small-btn tw-inline-flex tw-h-8 tw-items-center tw-rounded-lg tw-border tw-border-slate-300 tw-bg-white tw-px-3 tw-text-xs tw-font-bold tw-text-slate-700 tw-shadow-sm hover:tw-bg-slate-100">Chọn tất cả</button>
                                <button type="button" onclick="selectAllTieuchi(false)" class="small-btn tw-inline-flex tw-h-8 tw-items-center tw-rounded-lg tw-border tw-border-slate-300 tw-bg-white tw-px-3 tw-text-xs tw-font-bold tw-text-slate-700 tw-shadow-sm hover:tw-bg-slate-100">Bỏ chọn</button>
                            </div>
                        </div>
                        <div id="tieuchi_list" class="deadline-modal__criteria-list tw-max-h-64 tw-space-y-2 tw-overflow-y-auto tw-rounded-xl tw-border tw-border-slate-200 tw-bg-white tw-p-3">
                            <?php
                            $sql_tieuchi = "SELECT id, thutu, noidung FROM tieuchi_dept WHERE dept = ?";
                            $stmt_tieuchi = $connect->prepare($sql_tieuchi);
                            $stmt_tieuchi->bind_param("s", $dept);
                            $stmt_tieuchi->execute();
                            $result_tieuchi = $stmt_tieuchi->get_result();

                            while ($tieuchi = $result_tieuchi->fetch_assoc()) {
                                $criteria_text = (string) $tieuchi['noidung'];
                                if (function_exists('mb_substr') && function_exists('mb_strlen')) {
                                    $criteria_preview = mb_substr($criteria_text, 0, 70, 'UTF-8') . (mb_strlen($criteria_text, 'UTF-8') > 70 ? '...' : '');
                                } else {
                                    $criteria_preview = $criteria_text;
                                }
                                echo '<div class="deadline-modal__criteria-item tw-flex tw-items-start tw-gap-3 tw-rounded-xl tw-border tw-border-transparent tw-p-3 tw-transition hover:tw-border-[#b8cdf4] hover:tw-bg-[#eef5ff]/70">';
                                echo '<input type="checkbox" id="tieuchi_' . (int) $tieuchi['id'] . '" class="tieuchi-checkbox tw-mt-0.5 tw-h-4 tw-w-4 tw-rounded tw-border-slate-300 tw-text-[#143583] focus:tw-ring-[#143583]" value="' . (int) $tieuchi['id'] . '">';
                                echo '<label class="deadline-modal__criteria-label tw-block tw-cursor-pointer tw-text-sm tw-leading-5 tw-text-slate-700" for="tieuchi_' . (int) $tieuchi['id'] . '"><strong class="tw-text-slate-950">' . (int) $tieuchi['thutu'] . '.</strong> ' . htmlspecialchars($criteria_preview) . '</label>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <div id="update_status" class="deadline-modal__update-status" hidden></div>

                    <div class="form-group deadline-modal__group deadline-modal__group--actions tw-border-t tw-border-slate-100 tw-pt-5">
                        <div class="deadline-modal__actions tw-flex tw-flex-col-reverse tw-gap-3 sm:tw-flex-row sm:tw-justify-end">
                            <button type="button" onclick="closeDeadlineModal()" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary deadline-modal__action-btn deadline-modal__action-btn--secondary tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-slate-300 tw-bg-white tw-px-5 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50">Hủy</button>
                            <button type="button" onclick="updateDeadlineAll(<?php echo (int) $id; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--primary deadline-modal__action-btn tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-[#143583] tw-bg-[#143583] tw-px-5 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299]">
                                <i class="fas fa-check tw-mr-2" aria-hidden="true"></i>
                                Lưu cài đặt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
