        <div id="scoreOptionsModal" class="modal score-options-modal indexdept-modern-modal tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-3 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="score-options-title" hidden>
            <div class="modal-content default-settings-modal__content score-options-modal__content tw-relative tw-m-0 tw-flex tw-max-h-[92vh] tw-w-full tw-max-w-6xl tw-flex-col tw-overflow-hidden tw-rounded-lg tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft">
                <button type="button" class="close default-settings-modal__close score-options-modal__close tw-absolute tw-right-3 tw-top-3 tw-z-20 tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-slate-200 tw-bg-white/90 tw-text-xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeScoreOptionsModal()" aria-label="Đóng cài mốc điểm">&times;</button>

                <div class="default-settings-modal__header score-options-modal__header tw-border-b tw-border-slate-200 tw-bg-slate-50 tw-px-4 tw-py-3">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                        <div class="score-options-modal__heading tw-flex tw-min-w-0 tw-items-start tw-gap-3">
                            <span class="score-options-modal__icon tw-inline-flex tw-h-9 tw-w-9 tw-flex-none tw-items-center tw-justify-center tw-rounded-md tw-bg-[#143583] tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                                <i class="fas fa-sliders-h"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <span class="score-options-modal__eyebrow tw-text-xs tw-font-bold tw-uppercase tw-tracking-[0.18em] tw-text-[#143583]">Bảng điểm</span>
                                <h3 id="score-options-title" class="modal-title default-settings-modal__title score-options-modal__title tw-m-0 tw-pr-10 tw-text-xl tw-font-bold tw-leading-tight tw-text-slate-950">
                                    Cài mốc điểm
                                </h3>
                            </div>
                        </div>
                        <span class="score-options-modal__dept tw-mr-10 tw-hidden tw-max-w-xs tw-truncate tw-rounded-md tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-slate-700 sm:tw-inline-flex" title="<?php echo htmlspecialchars($dept_display_name); ?>">
                            <?php echo htmlspecialchars($dept_display_name); ?>
                        </span>
                    </div>
                </div>

                <div id="score_options_status" class="default-settings-modal__status score-options-modal__status tw-mx-4 tw-mt-2" hidden></div>

                <div class="default-settings-modal__body score-options-modal__body tw-flex tw-min-h-0 tw-flex-1 tw-flex-col tw-gap-3 tw-px-4 tw-py-3">
                    <div class="table-container default-settings-modal__table-wrap score-options-modal__table-wrap tw-min-h-0 tw-flex-1 tw-overflow-auto tw-rounded-md tw-border tw-border-slate-200 tw-bg-white">
                        <table class="evaluation-table default-settings-modal__table score-options-modal__table tw-w-full tw-border-collapse tw-text-sm">
                            <thead class="tw-sticky tw-top-0 tw-z-10">
                                <tr class="tw-bg-slate-100 tw-text-left tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-slate-600">
                                    <th class="score-options-modal__col score-options-modal__col--stt tw-px-2 tw-py-2">STT</th>
                                    <th class="score-options-modal__col score-options-modal__col--criteria tw-px-2 tw-py-2">Tiêu chí đánh giá</th>
                                    <th class="score-options-modal__col score-options-modal__col--state tw-px-2 tw-py-2">Trạng thái</th>
                                    <th class="score-options-modal__col score-options-modal__col--scores tw-px-2 tw-py-2">Mốc điểm</th>
                                    <th class="score-options-modal__col score-options-modal__col--actions tw-px-2 tw-py-2">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="score_options_tbody" class="tw-divide-y tw-divide-slate-100">
                                <?php
                                $score_configured_options = getConfiguredScoreOptionsMap($connect, $dept);
                                $sql = "SELECT tc.*
                                       FROM tieuchi_dept tc
                                       WHERE tc.dept = ?
                                       ORDER BY
                                           CASE tc.nhom
                                               WHEN 'Nhóm Nghiệp Vụ' THEN 1
                                               WHEN 'Nhóm May Mẫu' THEN 2
                                               WHEN 'Nhóm Quy Trình' THEN 3
                                               WHEN 'Kho Nguyên Liệu' THEN 1
                                               WHEN 'Kho Phụ Liệu' THEN 2
                                               ELSE 4
                                           END,
                                           tc.thutu";

                                $stmt = $connect->prepare($sql);
                                $stmt->bind_param("s", $dept);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                $current_nhom = '';

                                while ($row = $result->fetch_assoc()) {
                                    if ($current_nhom != $row['nhom']) {
                                        $current_nhom = $row['nhom'];

                                        $nhom_display = '';
                                        if ($dept == 'chuanbi_sanxuat_phong_kt') {
                                            switch ($row['nhom']) {
                                                case 'Nhóm Nghiệp Vụ':
                                                    $nhom_display = 'a. Nhóm Nghiệp Vụ';
                                                    break;
                                                case 'Nhóm May Mẫu':
                                                    $nhom_display = 'b. Nhóm May Mẫu';
                                                    break;
                                                case 'Nhóm Quy Trình':
                                                    $nhom_display = 'c. Nhóm Quy Trình Công Nghệ, Thiết Kế Chuyền';
                                                    break;
                                            }
                                        } elseif ($dept == 'kho') {
                                            switch ($row['nhom']) {
                                                case 'Kho Nguyên Liệu':
                                                    $nhom_display = 'a. Kho Nguyên Liệu';
                                                    break;
                                                case 'Kho Phụ Liệu':
                                                    $nhom_display = 'b. Kho Phụ Liệu';
                                                    break;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td colspan="5" class="default-settings-modal__group-row score-options-modal__group-row tw-bg-slate-900 tw-px-2 tw-py-1 tw-text-left tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-slate-700">
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
                                        class="tw-bg-white tw-transition hover:tw-bg-[#eef5ff]/60"
                                        data-tieuchi-id="<?php echo $id_tieuchi; ?>"
                                        data-fallback-scores="<?php echo htmlspecialchars($fallback_values); ?>"
                                        data-original-scores="<?php echo htmlspecialchars($score_values); ?>">
                                        <td class="score-options-modal__index tw-whitespace-nowrap tw-px-2 tw-py-2 tw-font-bold tw-text-slate-700"><?php echo (int) $row['thutu']; ?></td>
                                        <td class="text-left score-options-modal__criteria-cell tw-min-w-[320px] tw-px-2 tw-py-2 tw-leading-5 tw-text-slate-800"><?php echo htmlspecialchars($row['noidung']); ?></td>
                                        <td class="score-options-modal__state-cell tw-px-2 tw-py-2">
                                            <span id="score_badge_<?php echo $id_tieuchi; ?>"
                                                  class="score-options-modal__badge <?php echo $has_custom_scores ? 'score-options-modal__badge--custom tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-text-[#143583]' : 'score-options-modal__badge--default tw-border-slate-200 tw-bg-slate-50 tw-text-slate-700'; ?> tw-inline-flex tw-h-7 tw-items-center tw-rounded-md tw-border tw-px-2 tw-text-xs tw-font-bold">
                                                <?php echo $has_custom_scores ? 'Tùy chỉnh' : 'Mặc định'; ?>
                                            </span>
                                        </td>
                                        <td class="score-options-modal__input-cell tw-px-2 tw-py-2">
                                            <div class="score-options-modal__score-editor" data-score-editor data-tieuchi-id="<?php echo $id_tieuchi; ?>">
                                                <div id="score_chips_<?php echo $id_tieuchi; ?>"
                                                     class="score-options-modal__chip-list"
                                                     aria-label="Danh sách mốc điểm cho tiêu chí <?php echo (int) $row['thutu']; ?>"></div>
                                                <div class="score-options-modal__chip-entry">
                                                    <input type="text"
                                                           id="score_entry_<?php echo $id_tieuchi; ?>"
                                                           class="score-options-modal__chip-input"
                                                           placeholder="Thêm mốc"
                                                           inputmode="decimal"
                                                           onkeydown="handleScoreEntryKeydown(event, <?php echo $id_tieuchi; ?>)"
                                                           aria-label="Thêm mốc điểm cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                    <button type="button"
                                                            onclick="addScoreChip(<?php echo $id_tieuchi; ?>)"
                                                            class="score-options-modal__chip-add"
                                                            title="Thêm mốc điểm"
                                                            aria-label="Thêm mốc điểm cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                                <input type="hidden"
                                                       id="score_values_<?php echo $id_tieuchi; ?>"
                                                       class="score-options-modal__score-input"
                                                       value="<?php echo htmlspecialchars($score_values); ?>">
                                            </div>
                                        </td>
                                        <td class="score-options-modal__action-cell tw-px-2 tw-py-2">
                                            <div class="score-options-modal__row-actions tw-flex tw-gap-1">
                                                <button type="button" onclick="saveScoreOptions(<?php echo $id_tieuchi; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-default-setting score-options-modal__row-btn score-options-modal__row-btn--save tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-text-[#143583] tw-shadow-sm tw-transition hover:tw-bg-[#dbeafe]" title="Lưu dòng này" aria-label="Lưu mốc điểm cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                    <i class="fas fa-check" aria-hidden="true"></i>
                                                    <span class="score-options-modal__sr-only">Lưu</span>
                                                </button>
                                                <button type="button" onclick="resetScoreOptions(<?php echo $id_tieuchi; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-default-setting score-options-modal__row-btn score-options-modal__reset-btn tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-slate-200 tw-bg-white tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-100" title="Dùng mặc định" aria-label="Dùng mốc điểm mặc định cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                    <i class="fas fa-undo" aria-hidden="true"></i>
                                                    <span class="score-options-modal__sr-only">Mặc định</span>
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

                    <div class="default-settings-modal__toolbar default-settings-modal__toolbar--bottom score-options-modal__toolbar score-options-modal__toolbar--bottom tw-flex tw-flex-col tw-gap-2 tw-border-t tw-border-slate-100 tw-pt-3 sm:tw-flex-row sm:tw-items-center sm:tw-justify-between">
                        <div class="score-options-modal__footer-meta" id="score_options_changed_summary">Chưa có thay đổi</div>
                        <div class="score-options-modal__footer-actions tw-flex tw-flex-col-reverse tw-gap-2 sm:tw-flex-row sm:tw-justify-end">
                            <button type="button" onclick="closeScoreOptionsModal()" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--secondary score-options-modal__btn score-options-modal__btn--secondary tw-inline-flex tw-h-9 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-slate-300 tw-bg-white tw-px-3 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50">
                                <i class="fas fa-times tw-mr-2" aria-hidden="true"></i>
                                <span>Đóng</span>
                            </button>
                            <button type="button" onclick="saveAllScoreOptions('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--primary score-options-modal__btn score-options-modal__btn--primary tw-inline-flex tw-h-9 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-[#143583] tw-bg-[#143583] tw-px-3 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299]">
                                <i class="fas fa-floppy-disk tw-mr-2" aria-hidden="true"></i>
                                <span id="score_options_save_all_label">Lưu tất cả mốc điểm</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
