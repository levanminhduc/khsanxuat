        <!-- Modal cài đặt hạn xử lý -->
        <?php
        // Class chung cho nhãn trong modal hạn xử lý (3 nhãn ô nhập + tiêu đề bảng tiêu chí)
        $deadline_label = 'tw-block tw-text-xs tw-font-bold tw-leading-[1.2] tw-text-slate-700';
        // Ô nhập + select: phần chung; viền/bo/padding đặt riêng trên từng ô (ô số ngày giữ kiểu cũ #ddd/4px/8px)
        $deadline_field = 'tw-box-border tw-w-full tw-min-h-8 tw-border tw-border-solid tw-bg-white [font-family:inherit] tw-text-[13px] tw-font-bold tw-tabular-nums tw-text-slate-900 tw-transition-[border-color,box-shadow] tw-duration-[160ms] tw-ease-[ease] focus:tw-border-[#143583] focus:tw-shadow-[0_0_0_2px_rgba(20,53,131,0.14)] focus:[outline:none]';
        // Nút gợi ý nhanh; trạng thái .active do JS bật vẫn nằm trong indexdept-modals.css
        $deadline_quick_btn = 'quick-btn tw-inline-flex tw-min-h-[30px] tw-cursor-pointer tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-px-[9px] tw-py-0 tw-text-[12px] tw-font-extrabold tw-text-[#143583] tw-transition-[background-color,border-color,transform] tw-duration-[160ms] tw-ease-[ease] hover:tw-border-[#8fb0eb] hover:tw-bg-[#dbeafe] active:tw-scale-[0.96]';
        $deadline_bulk_btn = 'small-btn tw-min-h-7 tw-cursor-pointer tw-rounded-md tw-border tw-border-solid tw-border-slate-300 tw-bg-white tw-px-[9px] tw-py-0 tw-text-[12px] tw-font-extrabold tw-text-slate-700 tw-transition-[background-color,color,transform] tw-duration-[160ms] tw-ease-[ease] hover:tw-bg-slate-100 hover:tw-text-slate-900 active:tw-scale-[0.96]';
        // Lưới 3 cột dùng chung cho hàng tiêu đề và từng dòng tiêu chí
        $deadline_grid = 'tw-grid tw-grid-cols-[42px_54px_minmax(0,1fr)] tw-items-center max768:tw-grid-cols-[38px_42px_minmax(0,1fr)]';
        $deadline_action_btn = 'tw-min-h-[34px] tw-min-w-[110px] tw-rounded-md tw-font-bold max768:tw-mr-0 max768:tw-w-auto max768:tw-flex-none max768:tw-whitespace-nowrap';
        ?>
        <div id="deadlineModal" class="modal deadline-modal score-options-modal indexdept-modern-modal tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-3 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="deadline-modal-title" hidden>
            <div class="modal-content score-options-modal__content tw-relative tw-m-0 tw-flex tw-max-h-[92vh] tw-w-full tw-max-w-5xl tw-flex-col tw-overflow-hidden tw-rounded-lg tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft">
                <button type="button" class="close tw-absolute tw-right-3 tw-top-3 tw-z-10 tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-white/90 tw-text-xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:-tw-translate-y-px hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeDeadlineModal()" aria-label="Đóng cài đặt hạn xử lý">&times;</button>

                <div class="score-options-modal__header tw-border-b tw-border-slate-200 tw-bg-slate-50 tw-bg-gradient-to-b tw-from-white tw-from-0% tw-to-[#f8fbff] tw-to-100% tw-px-4 tw-py-3">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                        <div class="score-options-modal__heading tw-flex tw-min-w-0 tw-items-start tw-gap-3">
                            <span class="score-options-modal__icon tw-inline-flex tw-h-9 tw-w-9 tw-flex-none tw-items-center tw-justify-center tw-rounded-md tw-bg-[#143583] tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                                <i class="fas fa-calendar-check"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <span class="score-options-modal__eyebrow tw-text-xs tw-font-bold tw-text-[#143583]">Deadline</span>
                                <h3 id="deadline-modal-title" class="modal-title score-options-modal__title tw-m-0 tw-pr-10 tw-text-balance tw-text-xl tw-font-bold tw-leading-tight tw-text-slate-950">Cài đặt hạn xử lý</h3>
                            </div>
                        </div>
                        <span class="score-options-modal__dept tw-mr-10 tw-hidden tw-max-w-xs tw-truncate tw-rounded-md tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-slate-700 sm:tw-inline-flex" title="<?php echo htmlspecialchars($dept_display_name); ?>">
                            <?php echo htmlspecialchars($dept_display_name); ?>
                        </span>
                    </div>
                </div>

                <div id="update_status" class="score-options-modal__status tw-mx-4 tw-mt-2 tw-text-center" hidden></div>

                <div class="score-options-modal__body tw-flex tw-min-h-0 tw-flex-1 tw-flex-col tw-gap-3 tw-bg-slate-50 tw-px-4 tw-py-3">
                    <div class="tw-grid tw-grid-cols-[150px_minmax(230px,1fr)_auto] [align-items:end] tw-gap-2 tw-rounded-md tw-border tw-border-solid tw-border-slate-300 tw-bg-slate-50 tw-p-2.5 max768:tw-grid-cols-1 max768:tw-gap-[7px] max768:tw-p-2">
                        <div class="tw-min-w-0">
                            <label for="so_ngay_xuly_chung" class="<?php echo $deadline_label; ?> tw-mb-[5px]">Số ngày xử lý</label>
                            <input type="number" id="so_ngay_xuly_chung" value="<?php echo $so_ngay_xuly; ?>" min="1" max="30" required class="<?php echo $deadline_field; ?> tw-rounded tw-border-[#ddd] tw-p-2">
                        </div>

                        <div class="tw-min-w-0">
                            <label for="ngay_tinh_han" class="<?php echo $deadline_label; ?> tw-mb-[5px]">Ngày tính hạn</label>
                            <select id="ngay_tinh_han" onchange="changeNgayTinhHan()" class="<?php echo $deadline_field; ?> tw-rounded-md tw-border-slate-300 tw-px-2 tw-py-[5px]">
                                <option value="ngay_vao" selected>Ngày vào trừ số ngày</option>
                                <option value="ngay_vao_cong">Ngày vào cộng số ngày</option>
                                <option value="ngay_ra">Ngày ra cộng số ngày</option>
                                <option value="ngay_ra_tru">Ngày ra trừ số ngày</option>
                            </select>
                        </div>

                        <div class="tw-min-w-[150px] max768:tw-min-w-0">
                            <span class="<?php echo $deadline_label; ?> tw-mb-[5px]">Gợi ý nhanh</span>
                            <div class="quick-suggestion tw-mt-0 tw-flex tw-min-h-8 tw-flex-wrap tw-items-center tw-gap-1.5 max768:tw-mt-[5px] max768:tw-gap-[5px]">
                                <button type="button" onclick="setQuickDays(7)" class="<?php echo $deadline_quick_btn; ?>">7 ngày</button>
                                <button type="button" onclick="setQuickDays(14)" class="<?php echo $deadline_quick_btn; ?>">14 ngày</button>
                            </div>
                        </div>
                    </div>

                    <div class="tw-flex tw-min-h-[30px] tw-items-center tw-gap-[7px] tw-rounded-md tw-border tw-border-solid tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1.5 tw-text-[12px] tw-font-semibold tw-text-[#143583]">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <span class="note-ngay-tinh tw-m-0 tw-text-xs tw-not-italic tw-leading-[1.35] tw-text-inherit" id="note-ngay-tinh">Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày</span>
                    </div>

                    <div class="tw-flex tw-min-h-0 tw-flex-auto tw-flex-col">
                        <div class="tw-flex tw-items-center tw-justify-between tw-gap-2 tw-rounded-t-md tw-border tw-border-solid tw-border-slate-300 tw-bg-slate-50 tw-px-2.5 tw-py-2 max768:tw-flex-col max768:tw-items-stretch">
                            <label class="<?php echo $deadline_label; ?> tw-mb-[5px]">Áp dụng cho tiêu chí</label>
                            <div class="tw-mt-0 tw-flex tw-items-center tw-gap-1.5">
                                <button type="button" onclick="selectAllTieuchi(true)" class="<?php echo $deadline_bulk_btn; ?>">Chọn tất cả</button>
                                <button type="button" onclick="selectAllTieuchi(false)" class="<?php echo $deadline_bulk_btn; ?>">Bỏ chọn</button>
                            </div>
                        </div>
                        <div class="<?php echo $deadline_grid; ?> tw-min-h-[30px] tw-border-x tw-border-x-slate-300 [border-left-style:solid] [border-right-style:solid] tw-bg-[#143583] tw-text-xs tw-font-bold tw-leading-[1.2] tw-text-slate-50" aria-hidden="true">
                            <span class="tw-px-2 tw-text-center">Chọn</span>
                            <span class="tw-px-2 tw-text-center">STT</span>
                            <span class="tw-px-2">Tiêu chí đánh giá</span>
                        </div>
                        <div id="tieuchi_list" class="tw-mt-0 tw-max-h-[min(48vh,430px)] tw-overflow-y-auto tw-rounded-b-md tw-border tw-border-t-0 [border-style:none_solid_solid] tw-border-x-slate-300 tw-border-b-slate-300 tw-bg-white tw-p-0 max768:tw-max-h-[calc(94vh-330px)]">
                            <?php
                            $sql_tieuchi = "SELECT id, thutu, noidung FROM tieuchi_dept WHERE dept = ?";
                            $stmt_tieuchi = $connect->prepare($sql_tieuchi);
                            $stmt_tieuchi->bind_param("s", $dept);
                            $stmt_tieuchi->execute();
                            $result_tieuchi = $stmt_tieuchi->get_result();

                            while ($tieuchi = $result_tieuchi->fetch_assoc()) {
                                $criteria_text = (string) $tieuchi['noidung'];
                                echo '<label class="' . $deadline_grid . ' tw-box-border tw-mb-[5px] tw-min-h-[38px] tw-w-full tw-cursor-pointer tw-gap-0 tw-border-b tw-border-b-gray-200 [border-bottom-style:solid] tw-font-bold tw-text-gray-800 tw-transition-[background-color,box-shadow] tw-duration-[160ms] tw-ease-[ease] hover:tw-bg-slate-50 hover:tw-shadow-[inset_3px_0_0_#143583]" for="tieuchi_' . (int) $tieuchi['id'] . '" title="' . htmlspecialchars($criteria_text) . '">';
                                echo '<span class="tw-inline-flex tw-min-h-[38px] tw-items-center tw-justify-center"><input type="checkbox" id="tieuchi_' . (int) $tieuchi['id'] . '" class="tieuchi-checkbox tw-box-border tw-m-0 tw-inline-block tw-h-4 tw-min-h-4 tw-w-4 tw-min-w-4 tw-max-w-4 tw-flex-[0_0_16px] tw-p-0 tw-accent-[#143583]" value="' . (int) $tieuchi['id'] . '"></span>';
                                echo '<span class="tw-inline-flex tw-min-h-[38px] tw-items-center tw-px-2 tw-text-[12px] tw-font-extrabold tw-tabular-nums tw-text-slate-900">' . (int) $tieuchi['thutu'] . '</span>';
                                echo '<span class="tw-line-clamp-2 tw-w-auto tw-min-w-0 tw-flex-auto tw-px-2 tw-py-[7px] tw-text-left tw-text-[12.5px] tw-font-semibold tw-leading-[1.3] tw-text-gray-800 [overflow-wrap:anywhere] max768:tw-px-1.5 max768:tw-text-[12px]">' . htmlspecialchars($criteria_text) . '</span>';
                                echo '</label>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-border-t tw-border-t-gray-200 [border-top-style:solid] tw-pt-2.5 max768:tw-flex-col max768:tw-items-stretch">
                        <div class="deadline-modal__footer-meta tw-inline-flex tw-min-h-7 tw-items-center tw-text-[12px] tw-font-extrabold" id="deadline_selected_summary">Chưa chọn tiêu chí</div>
                        <div class="tw-flex tw-justify-end tw-gap-2 max768:tw-w-full max768:tw-items-center max768:tw-justify-between">
                            <button type="button" onclick="closeDeadlineModal()" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary <?php echo $deadline_action_btn; ?> tw-bg-white tw-text-slate-700 hover:tw-bg-slate-100 hover:tw-text-slate-900 max768:tw-min-w-[92px]">
                                <i class="fas fa-times" aria-hidden="true"></i>
                                Hủy
                            </button>
                            <button type="button" onclick="updateDeadlineAll(<?php echo (int) $id; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--primary <?php echo $deadline_action_btn; ?> max768:tw-max-w-[calc(100%-108px)] max768:tw-min-w-[min(220px,calc(100%-108px))]">
                                <i class="fas fa-check tw-mr-2" aria-hidden="true"></i>
                                <span id="deadline_save_label">Lưu cài đặt</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
