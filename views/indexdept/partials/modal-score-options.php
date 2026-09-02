        <?php
        // Ô tiêu đề bảng: sticky + font Arial như bảng đánh giá; z-index/bóng đặt riêng vì cột cuối ghim phải
        $so_th = 'tw-sticky tw-top-0 tw-box-border tw-border tw-border-solid tw-border-[#ddd] tw-border-b-[#0f2861] tw-bg-[#143583] tw-px-2 tw-py-[7px] tw-text-center tw-text-[12px] tw-font-bold tw-normal-case tw-leading-[1.25] tw-tracking-normal tw-text-slate-50 tw-whitespace-nowrap [word-break:keep-all] tw-break-words tw-align-middle';
        // Ô dữ liệu: màu chữ đặt riêng trên từng ô (utility màu cùng nhóm sẽ đè nhau theo thứ tự build)
        $so_td = 'tw-border tw-border-solid tw-border-[#ddd] tw-border-b-gray-200 tw-bg-white tw-px-2 tw-py-[5px] tw-align-middle tw-text-[12.5px] tw-leading-[1.28] group-hover:tw-bg-slate-50 max768:tw-p-1.5 max768:tw-text-[12px]';
        // Nút footer trên mobile: không giãn, giữ 1 dòng (khớp footer chung của các modal)
        $so_action_btn = 'max768:tw-mr-0 max768:tw-w-auto max768:tw-flex-none max768:tw-whitespace-nowrap';
        ?>
        <div id="scoreOptionsModal" class="modal score-options-modal indexdept-modern-modal [font-family:Arial,Helvetica,sans-serif] tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-3 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="score-options-title" hidden>
            <div class="modal-content tw-relative tw-m-0 tw-flex tw-max-h-[92vh] tw-w-full tw-max-w-6xl tw-flex-col tw-overflow-hidden tw-rounded-[10px] tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft max768:tw-mx-auto max768:tw-my-[3vh] max768:tw-w-[96vw] max768:tw-max-w-[96vw]">
                <button type="button" class="close tw-absolute tw-right-3 tw-top-3 tw-z-20 tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-white/90 [font-family:inherit] tw-text-xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:-tw-translate-y-px hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeScoreOptionsModal()" aria-label="Đóng cài mốc điểm">&times;</button>

                <div class="tw-flex tw-items-center tw-justify-between tw-gap-4 tw-border-b [border-bottom-style:solid] max768:tw-flex-col max768:tw-items-start max768:tw-gap-2.5 tw-border-slate-200 tw-bg-slate-50 tw-px-4 tw-py-3">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                        <div class="tw-flex tw-min-w-0 tw-items-start tw-gap-3 max768:tw-pr-10">
                            <span class="tw-inline-flex tw-h-9 tw-w-9 tw-flex-none tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-[#0f2861] tw-bg-[#143583] tw-text-[15px] tw-text-white max768:tw-text-[16px] tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                                <i class="fas fa-sliders-h"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <span class="tw-mb-0.5 tw-block tw-text-xs tw-font-bold tw-uppercase tw-tracking-[0.18em] tw-text-[#143583]">Bảng điểm</span>
                                <h3 id="score-options-title" class="modal-title tw-m-0 tw-text-balance tw-pr-10 tw-text-xl tw-font-bold tw-leading-tight tw-text-slate-950">
                                    Cài mốc điểm
                                </h3>
                            </div>
                        </div>
                        <span class="tw-mr-10 tw-hidden tw-min-h-7 tw-max-w-xs tw-items-center tw-truncate tw-rounded-md tw-border tw-border-solid tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1 tw-text-right tw-text-xs tw-font-semibold tw-text-slate-700 tw-shadow-[0_1px_2px_rgba(15,23,42,0.06)] sm:tw-inline-flex max768:tw-w-full max768:tw-justify-start max768:tw-text-left" title="<?php echo htmlspecialchars($dept_display_name); ?>">
                            <?php echo htmlspecialchars($dept_display_name); ?>
                        </span>
                    </div>
                </div>

                <div id="score_options_status" class="score-options-modal__status tw-mx-4 tw-mt-2 tw-rounded-md tw-px-[9px] tw-py-[7px] tw-text-center" hidden></div>

                <div class="tw-flex tw-min-h-0 tw-flex-1 tw-flex-col tw-gap-3 tw-bg-[#eef2f5] tw-px-4 tw-py-3 max768:tw-gap-1.5 max768:tw-p-1.5">
                    <div class="tw-mx-3 tw-mt-3 tw-max-h-[calc(92vh-158px)] tw-min-h-0 tw-flex-1 tw-overflow-auto tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-white tw-shadow-[inset_0_1px_0_rgba(255,255,255,0.85)] max768:tw-m-0 max768:tw-max-h-[calc(94vh-140px)]">
                        <table class="tw-mb-5 tw-w-full tw-min-w-[900px] tw-table-auto tw-border-collapse tw-border-spacing-0 tw-text-sm max1024:tw-min-w-[820px] max768:tw-min-w-[780px]">
                            <thead class="tw-sticky tw-top-0 tw-z-10">
                                <tr class="tw-bg-slate-100 tw-text-left tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-slate-600">
                                    <th class="<?php echo $so_th; ?> tw-z-10 tw-shadow-[0_1px_0_rgba(0,0,0,0.1)] tw-w-11 tw-min-w-[44px]">STT</th>
                                    <th class="<?php echo $so_th; ?> tw-z-10 tw-shadow-[0_1px_0_rgba(0,0,0,0.1)] tw-w-auto tw-min-w-[330px] max1024:tw-min-w-[320px]">Tiêu chí đánh giá</th>
                                    <th class="<?php echo $so_th; ?> tw-z-10 tw-shadow-[0_1px_0_rgba(0,0,0,0.1)] tw-w-[88px] tw-min-w-[88px]">Trạng thái</th>
                                    <th class="<?php echo $so_th; ?> tw-z-10 tw-shadow-[0_1px_0_rgba(0,0,0,0.1)] tw-w-[290px] tw-min-w-[290px]">Mốc điểm</th>
                                    <th class="<?php echo $so_th; ?> tw-right-0 tw-z-[14] tw-w-[84px] tw-min-w-[84px] tw-shadow-[-10px_0_18px_rgba(15,23,42,0.12)]">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="score_options_tbody" class="tw-divide-y tw-divide-slate-100">
                                <?php
                                $score_configured_options = getConfiguredScoreOptionsMap($connect, $dept);
                                $sql = "SELECT tc.*
                                       FROM tieuchi_dept tc
                                       WHERE tc.dept = ?
                                       ORDER BY
                                           " . getNhomOrderByCase() . ",
                                           tc.thutu";

                                $stmt = $connect->prepare($sql);
                                $stmt->bind_param("s", $dept);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                $current_nhom = '';

                                while ($row = $result->fetch_assoc()) {
                                    if ($current_nhom != $row['nhom']) {
                                        $current_nhom = $row['nhom'];

                                        $nhom_display = getNhomDisplayName($dept, $row['nhom']);
                                        ?>
                                        <tr>
                                            <td colspan="5" class="tw-border tw-border-solid tw-border-[#ddd] tw-border-b-gray-200 tw-bg-white tw-px-2 tw-py-1.5 tw-text-left tw-align-middle tw-text-[12.5px] tw-font-bold tw-uppercase tw-leading-[1.28] tw-tracking-wide tw-text-slate-700 max768:tw-p-1.5 max768:tw-text-[12px]">
                                                <?php echo htmlspecialchars($nhom_display); ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }

                                    $id_tieuchi = (int) $row['id'];
                                    $has_custom_scores = !empty($score_configured_options[$id_tieuchi]);
                                    $fallback_options = getLegacyScoreOptions($dept, $row['thutu']);
                                    $fallback_values = implode(', ', array_column($fallback_options, 'value'));
                                    $score_options = $has_custom_scores ? $score_configured_options[$id_tieuchi] : $fallback_options;
                                    $score_values = implode(', ', array_column($score_options, 'value'));
                                    ?>
                                    <tr id="score_row_<?php echo $id_tieuchi; ?>"
                                        class="tw-group tw-bg-white tw-transition hover:tw-bg-[#eef5ff]/60"
                                        data-tieuchi-id="<?php echo $id_tieuchi; ?>"
                                        data-fallback-scores="<?php echo htmlspecialchars($fallback_values); ?>"
                                        data-original-scores="<?php echo htmlspecialchars($score_values); ?>">
                                        <td class="<?php echo $so_td; ?> tw-whitespace-nowrap tw-text-center tw-font-bold tw-tabular-nums tw-text-slate-900 group-hover:tw-shadow-[inset_3px_0_0_#143583]"><?php echo (int) $row['thutu']; ?></td>
                                        <td class="<?php echo $so_td; ?> tw-min-w-[330px] tw-text-left tw-font-semibold tw-text-gray-800"><?php echo htmlspecialchars($row['noidung']); ?></td>
                                        <td class="<?php echo $so_td; ?> tw-text-center tw-text-slate-700">
                                            <span id="score_badge_<?php echo $id_tieuchi; ?>"
                                                  class="<?php echo $has_custom_scores ? 'score-options-modal__badge--custom tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-text-[#143583]' : 'score-options-modal__badge--default tw-border-slate-200 tw-bg-slate-50 tw-text-slate-700'; ?> tw-inline-flex tw-h-7 tw-min-h-[22px] tw-min-w-[70px] tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-px-2 tw-py-[3px] tw-text-center tw-text-xs tw-font-bold">
                                                <?php echo $has_custom_scores ? 'Tùy chỉnh' : 'Mặc định'; ?>
                                            </span>
                                        </td>
                                        <td class="<?php echo $so_td; ?> tw-min-w-[290px] tw-text-slate-700 max768:tw-min-w-[230px]">
                                            <div class="tw-flex tw-min-w-[260px] tw-flex-wrap tw-items-center tw-gap-1 max768:tw-min-w-[230px]" data-score-editor data-tieuchi-id="<?php echo $id_tieuchi; ?>">
                                                <div id="score_chips_<?php echo $id_tieuchi; ?>"
                                                     class="tw-flex tw-min-h-[23px] tw-flex-wrap tw-items-center tw-gap-1 tw-p-0"
                                                     aria-label="Danh sách mốc điểm cho tiêu chí <?php echo (int) $row['thutu']; ?>"></div>
                                                <div class="tw-grid tw-flex-[1_1_112px] tw-grid-cols-[minmax(68px,1fr)_28px] tw-gap-1 max768:tw-basis-[104px] max768:tw-grid-cols-[minmax(64px,1fr)_28px]">
                                                    <input type="text"
                                                           id="score_entry_<?php echo $id_tieuchi; ?>"
                                                           class="tw-w-full tw-min-h-7 tw-rounded-[5px] tw-border tw-border-solid tw-border-slate-300 tw-bg-white tw-px-[7px] tw-py-1 tw-text-[12px] tw-font-bold tw-tabular-nums tw-text-slate-900 focus:tw-border-[#143583] focus:tw-shadow-[0_0_0_2px_rgba(20,53,131,0.14)] focus:[outline:none]"
                                                           placeholder="Thêm mốc"
                                                           inputmode="decimal"
                                                           onkeydown="handleScoreEntryKeydown(event, <?php echo $id_tieuchi; ?>)"
                                                           aria-label="Thêm mốc điểm cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                    <button type="button"
                                                            onclick="addScoreChip(<?php echo $id_tieuchi; ?>)"
                                                            class="tw-inline-flex tw-h-7 tw-w-7 tw-cursor-pointer tw-items-center tw-justify-center tw-rounded-[5px] tw-border-0 tw-border-none tw-border-current tw-bg-[#143583] tw-text-[11px] tw-text-white tw-transition-[background-color,color,transform] tw-duration-[160ms] tw-ease-[ease] hover:tw-bg-[#1a4299] active:tw-scale-[0.96]"
                                                            title="Thêm mốc điểm"
                                                            aria-label="Thêm mốc điểm cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                                <input type="hidden"
                                                       id="score_values_<?php echo $id_tieuchi; ?>"
                                                       value="<?php echo htmlspecialchars($score_values); ?>">
                                            </div>
                                        </td>
                                        <td class="<?php echo $so_td; ?> tw-sticky tw-right-0 tw-z-[6] tw-text-center tw-text-slate-700 tw-shadow-[-6px_0_12px_rgba(15,23,42,0.07)]">
                                            <div class="tw-flex tw-items-center tw-justify-center tw-gap-1">
                                                <button type="button" onclick="saveScoreOptions(<?php echo $id_tieuchi; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-default-setting tw-inline-flex tw-h-8 tw-min-h-8 tw-w-8 tw-min-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-p-0 tw-text-[13px] tw-leading-none tw-text-[#143583] tw-shadow-sm tw-transition hover:-tw-translate-y-px hover:tw-bg-[#dbeafe] hover:tw-shadow-[0_6px_12px_rgba(15,23,42,0.14)] active:tw-scale-[0.96]" title="Lưu dòng này" aria-label="Lưu mốc điểm cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                    <i class="fas fa-check" aria-hidden="true"></i>
                                                    <span class="tw-sr-only">Lưu</span>
                                                </button>
                                                <button type="button" onclick="resetScoreOptions(<?php echo $id_tieuchi; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-default-setting tw-inline-flex tw-h-8 tw-min-h-8 tw-w-8 tw-min-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-white tw-p-0 tw-text-[13px] tw-leading-none tw-text-slate-700 tw-shadow-sm tw-transition hover:-tw-translate-y-px hover:tw-bg-slate-100 hover:tw-shadow-[0_6px_12px_rgba(15,23,42,0.14)] active:tw-scale-[0.96]" title="Dùng mặc định" aria-label="Dùng mốc điểm mặc định cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                    <i class="fas fa-undo" aria-hidden="true"></i>
                                                    <span class="tw-sr-only">Mặc định</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="tw-sticky tw-bottom-0 tw-z-[12] tw-flex tw-shrink-0 tw-flex-col tw-justify-end tw-gap-2 tw-border-t tw-border-slate-100 [border-top-style:solid] tw-bg-slate-50/[0.94] tw-px-[14px] tw-pb-3 tw-pt-3 tw-backdrop-blur-[8px] sm:tw-flex-row sm:tw-items-center sm:tw-justify-between max768:tw-w-full max768:tw-flex-col max768:tw-items-stretch max768:tw-gap-1.5 max768:tw-px-0 max768:tw-pb-0 max768:tw-pt-1.5">
                        <div class="score-options-modal__footer-meta tw-inline-flex tw-min-h-7 tw-items-center tw-text-[12px] tw-font-extrabold" id="score_options_changed_summary">Chưa có thay đổi</div>
                        <div class="tw-flex tw-flex-col-reverse tw-items-center tw-gap-2 sm:tw-flex-row sm:tw-justify-end max768:tw-w-full max768:tw-flex-row max768:tw-items-center max768:tw-justify-between max768:tw-gap-2">
                            <button type="button" onclick="closeScoreOptionsModal()" class="btn-add-criteria <?php echo $so_action_btn; ?> tw-inline-flex tw-h-9 tw-min-h-[34px] tw-items-center tw-justify-center tw-gap-2 tw-rounded-md tw-border tw-border-solid tw-border-slate-300 tw-bg-white tw-px-3 tw-py-0 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50 hover:tw-text-slate-900 active:tw-scale-[0.96] max768:tw-min-w-[92px]">
                                <i class="fas fa-times tw-mr-2" aria-hidden="true"></i>
                                <span>Đóng</span>
                            </button>
                            <button type="button" onclick="saveAllScoreOptions('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria <?php echo $so_action_btn; ?> tw-inline-flex tw-h-9 tw-min-h-[34px] tw-items-center tw-justify-center tw-gap-2 tw-rounded-md tw-border tw-border-solid tw-border-[#143583] tw-bg-[#143583] tw-px-3 tw-py-0 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299] active:tw-scale-[0.96] max768:tw-max-w-[calc(100%-108px)] max768:tw-min-w-[min(220px,calc(100%-108px))]">
                                <i class="fas fa-floppy-disk tw-mr-2" aria-hidden="true"></i>
                                <span id="score_options_save_all_label">Lưu tất cả mốc điểm</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
