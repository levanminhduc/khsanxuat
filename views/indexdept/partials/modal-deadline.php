        <!-- Modal cài đặt hạn xử lý -->
        <div id="deadlineModal" class="modal deadline-modal score-options-modal indexdept-modern-modal tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-3 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="deadline-modal-title" hidden>
            <div class="modal-content deadline-modal__content score-options-modal__content tw-relative tw-m-0 tw-flex tw-max-h-[92vh] tw-w-full tw-max-w-5xl tw-flex-col tw-overflow-hidden tw-rounded-lg tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft">
                <button type="button" class="close deadline-modal__close tw-absolute tw-right-3 tw-top-3 tw-z-10 tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-slate-200 tw-bg-white/90 tw-text-xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeDeadlineModal()" aria-label="Đóng cài đặt hạn xử lý">&times;</button>

                <div class="deadline-modal__header score-options-modal__header tw-border-b tw-border-slate-200 tw-bg-slate-50 tw-px-4 tw-py-3">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                        <div class="score-options-modal__heading tw-flex tw-min-w-0 tw-items-start tw-gap-3">
                            <span class="score-options-modal__icon tw-inline-flex tw-h-9 tw-w-9 tw-flex-none tw-items-center tw-justify-center tw-rounded-md tw-bg-[#143583] tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                                <i class="fas fa-calendar-check"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <span class="score-options-modal__eyebrow tw-text-xs tw-font-bold tw-text-[#143583]">Deadline</span>
                                <h3 id="deadline-modal-title" class="modal-title deadline-modal__title score-options-modal__title tw-m-0 tw-pr-10 tw-text-xl tw-font-bold tw-leading-tight tw-text-slate-950">Cài đặt hạn xử lý</h3>
                            </div>
                        </div>
                        <span class="score-options-modal__dept tw-mr-10 tw-hidden tw-max-w-xs tw-truncate tw-rounded-md tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-slate-700 sm:tw-inline-flex" title="<?php echo htmlspecialchars($dept_display_name); ?>">
                            <?php echo htmlspecialchars($dept_display_name); ?>
                        </span>
                    </div>
                </div>

                <div id="update_status" class="deadline-modal__update-status score-options-modal__status tw-mx-4 tw-mt-2" hidden></div>

                <div class="deadline-modal__body score-options-modal__body tw-flex tw-min-h-0 tw-flex-1 tw-flex-col tw-gap-3 tw-px-4 tw-py-3">
                    <div class="deadline-modal__controls">
                        <div class="form-group deadline-modal__group deadline-modal__field">
                            <label for="so_ngay_xuly_chung" class="deadline-modal__label">Số ngày xử lý</label>
                            <input type="number" id="so_ngay_xuly_chung" value="<?php echo $so_ngay_xuly; ?>" min="1" max="30" required class="deadline-modal__input">
                        </div>

                        <div class="form-group deadline-modal__group deadline-modal__field deadline-modal__field--method">
                            <label for="ngay_tinh_han" class="deadline-modal__label">Ngày tính hạn</label>
                            <select id="ngay_tinh_han" onchange="changeNgayTinhHan()" class="deadline-modal__select">
                                <option value="ngay_vao" selected>Ngày vào trừ số ngày</option>
                                <option value="ngay_vao_cong">Ngày vào cộng số ngày</option>
                                <option value="ngay_ra">Ngày ra cộng số ngày</option>
                                <option value="ngay_ra_tru">Ngày ra trừ số ngày</option>
                            </select>
                        </div>

                        <div class="form-group deadline-modal__group deadline-modal__field deadline-modal__quick-field">
                            <span class="deadline-modal__label">Gợi ý nhanh</span>
                            <div class="quick-suggestion deadline-modal__quick-suggestion">
                                <button type="button" onclick="setQuickDays(7)" class="quick-btn">7 ngày</button>
                                <button type="button" onclick="setQuickDays(14)" class="quick-btn">14 ngày</button>
                            </div>
                        </div>
                    </div>

                    <div class="deadline-modal__note-strip">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <span class="note-ngay-tinh deadline-modal__note deadline-modal__note--method" id="note-ngay-tinh">Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày</span>
                    </div>

                    <div class="form-group deadline-modal__group deadline-modal__criteria-panel">
                        <div class="deadline-modal__criteria-toolbar">
                            <label class="deadline-modal__label deadline-modal__criteria-title">Áp dụng cho tiêu chí</label>
                            <div class="deadline-modal__bulk-actions">
                                <button type="button" onclick="selectAllTieuchi(true)" class="small-btn deadline-modal__bulk-btn">Chọn tất cả</button>
                                <button type="button" onclick="selectAllTieuchi(false)" class="small-btn deadline-modal__bulk-btn">Bỏ chọn</button>
                            </div>
                        </div>
                        <div class="deadline-modal__criteria-table-head" aria-hidden="true">
                            <span>Chọn</span>
                            <span>STT</span>
                            <span>Tiêu chí đánh giá</span>
                        </div>
                        <div id="tieuchi_list" class="deadline-modal__criteria-list">
                            <?php
                            $sql_tieuchi = "SELECT id, thutu, noidung FROM tieuchi_dept WHERE dept = ?";
                            $stmt_tieuchi = $connect->prepare($sql_tieuchi);
                            $stmt_tieuchi->bind_param("s", $dept);
                            $stmt_tieuchi->execute();
                            $result_tieuchi = $stmt_tieuchi->get_result();

                            while ($tieuchi = $result_tieuchi->fetch_assoc()) {
                                $criteria_text = (string) $tieuchi['noidung'];
                                echo '<label class="deadline-modal__criteria-item" for="tieuchi_' . (int) $tieuchi['id'] . '" title="' . htmlspecialchars($criteria_text) . '">';
                                echo '<span class="deadline-modal__criteria-check"><input type="checkbox" id="tieuchi_' . (int) $tieuchi['id'] . '" class="tieuchi-checkbox" value="' . (int) $tieuchi['id'] . '"></span>';
                                echo '<span class="deadline-modal__criteria-index">' . (int) $tieuchi['thutu'] . '</span>';
                                echo '<span class="deadline-modal__criteria-label">' . htmlspecialchars($criteria_text) . '</span>';
                                echo '</label>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="form-group deadline-modal__group deadline-modal__group--actions">
                        <div class="deadline-modal__footer-meta" id="deadline_selected_summary">Chưa chọn tiêu chí</div>
                        <div class="deadline-modal__actions">
                            <button type="button" onclick="closeDeadlineModal()" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary deadline-modal__action-btn deadline-modal__action-btn--secondary">
                                <i class="fas fa-times" aria-hidden="true"></i>
                                Hủy
                            </button>
                            <button type="button" onclick="updateDeadlineAll(<?php echo (int) $id; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--primary deadline-modal__action-btn">
                                <i class="fas fa-check tw-mr-2" aria-hidden="true"></i>
                                <span id="deadline_save_label">Lưu cài đặt</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
