        <!-- Thêm vào phần hiển thị form, trước khi hiển thị các tiêu chí -->
        <?php if (isset($error_message)) : ?>
        <div class="alert alert-error evaluation-alert">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <div class="evaluation-section evaluation-section--wide">
            <div class="evaluation-section__header">
                <h2>Tiêu chí đánh giá</h2>
                <button type="button" id="toggleCompletedRows" class="btn-toggle-completed" onclick="toggleCompletedRows()">
                    <i class="fas fa-eye"></i>
                    <span id="toggleCompletedLabel">Hiện đã hoàn thành</span>
                    <span class="hidden-count" id="hiddenRowsCount"></span>
                </button>
            </div>
            <form action="<?php echo BASE_URL; ?>/actions/save_danhgia_with_log.php" method="POST" id="danhgiaForm">
                <?php echo getCsrfInput(); ?>
                <input type="hidden" name="id_sanxuat" value="<?php echo $id; ?>">
                <input type="hidden" name="dept" value="<?php echo $dept; ?>">

                <!-- Mobile: gom tiêu chí đã chấm (không tính loại đạt mặc định, đã có nút riêng) sau 1 dòng bật/tắt -->
                <button type="button" class="scored-toggle" id="scoredToggle" onclick="toggleScoredRows()" aria-expanded="false">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span class="scored-toggle__label">Đã chấm <span id="scoredToggleCount">0</span></span>
                    <span class="scored-toggle__action" id="scoredToggleAction">Hiện</span>
                </button>

                <div class="table-responsive table-responsive--spaced">
                    <table class="evaluation-table">
                        <thead>
                            <tr>
                                <th class="col-stt">STT</th>
                                <th class="resizable col-criteria-wide">Tiêu chí đánh giá</th>
                                <th class="resizable col-deadline">
                                    Hạn Xử Lý
                                    <span class="tooltip-icon" title="Thời hạn đã được tính toán tự động">ⓘ</span>
                                    <button type="button" onclick="openDeadlineModal()" class="small-btn">Cài đặt</button>
                                </th>
                                <th class="resizable col-owner col-header-tall">Người chịu trách nhiệm</th>
                                <th class="resizable col-score">Điểm đánh giá</th>
                                <th class="col-status">Đã thực hiện</th>
                                <th class="resizable col-note">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Thu thập + tính toán ở fetchEvaluationRows() (includes/indexdept/functions.php);
                        // phần dưới chỉ render. $total_tieuchi / $completed_tieuchi / $max_possible_points
                        // còn được indexdept.php đọc lại để dựng window.INDEXDEPT_BOOTSTRAP.
                        $evaluation_data = fetchEvaluationRows($connect, $id, $dept, $han_xuly, $han_xuly_formatted, $so_ngay_xuly);

                        $rows_data           = $evaluation_data['rows'];
                        $staff_list          = $evaluation_data['staff_list'];
                        $total_tieuchi       = $evaluation_data['total_tieuchi'];
                        $completed_tieuchi   = $evaluation_data['completed_tieuchi'];
                        $total_points        = $evaluation_data['total_points'];
                        $max_possible_points = $evaluation_data['max_possible_points'];

                        $current_nhom = false;

                        foreach ($rows_data as $item) {
                            $row = $item['row'];

                            if ($current_nhom !== $item['nhom']) {
                                $current_nhom = $item['nhom'];

                                // Hiển thị tên nhóm
                                $nhom_display = getNhomDisplayName($dept, $row['nhom']);
                                ?>
                                <tr>
                                    <td colspan="7" class="evaluation-group-row">
                                        <?php echo $nhom_display; ?>
                                    </td>
                                </tr>
                                <?php
                            }

                            $score_options = $item['score_options'];
                            $effective_score = $item['effective_score'];
                            $han_tc_formatted = $item['han_tc_formatted'];
                            $tc_so_ngay_xuly = $item['tc_so_ngay_xuly'];
                            $tc_ngay_tinh_han = $item['tc_ngay_tinh_han'];
                            $is_overdue = $item['is_overdue'];
                            $is_completed = $item['is_completed'];
                            $has_high_default = $item['has_high_default'];
                            $deadline_class = $is_overdue ? 'overdue' : '';

                            // Parse CSV id (tương thích dữ liệu cũ chỉ 1 id)
                            $selected_ids = array_filter(array_map('intval', explode(',', (string) $row['nguoi_thuchien'])));

                            // Tên người phụ trách dạng text cho dòng gọn ở mobile
                            $owner_names = [];
                            foreach ($staff_list as $staff) {
                                if (in_array((int) $staff['id'], $selected_ids, true)) {
                                    $owner_names[] = $staff['ten'];
                                }
                            }

                            // Nhãn điểm hiện tại (badge của dòng gọn)
                            $current_score_label = '';
                            foreach ($score_options as $score_option) {
                                if (scoreValuesAreEqual($effective_score, $score_option['value'])) {
                                    $current_score_label = $score_option['label'];
                                    break;
                                }
                            }

                            $row_classes = [];
                            if ($has_high_default) {
                                $row_classes[] = 'row-completed';
                            }
                            if ($is_completed) {
                                $row_classes[] = 'is-scored';
                            }
                            if ($is_overdue) {
                                $row_classes[] = 'is-overdue';
                            }
                            // Mobile: ô ghi chú ẩn sau nút "Ghi chú", mở sẵn khi đã có nội dung
                            $note_text = trim((string) ($row['ghichu'] ?? ''));
                            if ($note_text !== '') {
                                $row_classes[] = 'is-note-open';
                            }
                            ?>
                            <tr class="<?php echo implode(' ', $row_classes); ?>" data-has-default="<?php echo $has_high_default ? '1' : '0'; ?>" data-overdue="<?php echo $is_overdue ? '1' : '0'; ?>" data-tieuchi-row="<?php echo $row['id']; ?>">
                                <td><?php echo $row['thutu']; ?></td>
                                <td class="text-left;">
                                    <span class="tc-stt-inline"><?php echo $row['thutu']; ?></span>
                                    <span class="tc-noidung"><?php echo htmlspecialchars($row['noidung']); ?></span>
                                    <span class="tc-score-badge" id="tc_score_badge_<?php echo $row['id']; ?>"><?php echo htmlspecialchars($current_score_label); ?></span>
                                    <span class="tc-owner-flat" id="owner_flat_<?php echo $row['id']; ?>"><?php echo htmlspecialchars(implode(' · ', $owner_names)); ?></span>
                                    <span class="tc-note-flat" id="tc_note_flat_<?php echo $row['id']; ?>"><?php echo htmlspecialchars($note_text); ?></span>
                                </td>
                                <td class="deadline-info">
                                    <span class="deadline-date <?php echo $deadline_class; ?>" id="date_display_<?php echo $row['id']; ?>"><?php echo $han_tc_formatted; ?></span>
                                    <?php /* if($is_admin): */ ?>
                                    <div class="deadline-form">
                                        <div class="inline-flex-center">
                                            <input type="number" id="so_ngay_xuly_<?php echo $row['id']; ?>" value="<?php echo isset($row['so_ngay_xuly']) ? $row['so_ngay_xuly'] : $tc_so_ngay_xuly; ?>" min="1" max="30" class="deadline-input">
                                            <button type="button" onclick="updateDeadline(<?php echo $id; ?>, <?php echo $row['id']; ?>, '<?php echo $dept; ?>')" class="deadline-button">Cập nhật</button>
                                        </div>
                                        <!-- Thêm select để chọn ngày tính hạn xử lý cho từng tiêu chí -->
                                        <div class="inline-flex-center inline-flex-center--mt-xs">
                                            <select id="ngay_tinh_han_<?php echo $row['id']; ?>" class="ngay-tinh-han-select">
                                                <option value="ngay_vao" <?php echo ($tc_ngay_tinh_han == 'ngay_vao') ? 'selected' : ''; ?>>Ngày vào trừ số ngày</option>
                                                <option value="ngay_vao_cong" <?php echo ($tc_ngay_tinh_han == 'ngay_vao_cong') ? 'selected' : ''; ?>>Ngày vào cộng số ngày</option>
                                                <option value="ngay_ra" <?php echo ($tc_ngay_tinh_han == 'ngay_ra') ? 'selected' : ''; ?>>Ngày ra cộng số ngày</option>
                                                <option value="ngay_ra_tru" <?php echo ($tc_ngay_tinh_han == 'ngay_ra_tru') ? 'selected' : ''; ?>>Ngày ra trừ số ngày</option>
                                            </select>
                                        </div>
                                    </div>
                                    <?php /* endif; */ ?>
                                </td>
                                <td class="owner-cell">
                                    <!-- Marker phân biệt "đã submit nhưng bỏ chọn hết" với "không submit" -->
                                    <input type="hidden" name="nguoi_thuchien_present_<?php echo $row['id']; ?>" value="1">
                                    <?php if (!empty($staff_list)) : ?>
                                        <div class="owner-select" data-owner-select>
                                            <button type="button" class="owner-select__toggle" data-owner-toggle
                                                    title="Thêm người chịu trách nhiệm"
                                                    aria-label="Thêm người chịu trách nhiệm">
                                                <i class="fas fa-user-plus" aria-hidden="true"></i>
                                            </button>
                                            <div class="owner-select__chips" data-owner-chips></div>
                                            <div class="owner-select__panel" data-owner-panel hidden>
                                                <?php foreach ($staff_list as $staff) : ?>
                                                    <?php $checked = in_array((int) $staff['id'], $selected_ids, true) ? 'checked' : ''; ?>
                                                    <label class="owner-checklist__item <?php echo $checked ? 'owner-checklist__item--checked' : ''; ?>">
                                                        <input type="checkbox"
                                                               name="nguoi_thuchien_<?php echo $row['id']; ?>[]"
                                                               value="<?php echo (int) $staff['id']; ?>"
                                                               <?php echo $checked; ?>>
                                                        <span class="owner-checklist__name"><?php echo htmlspecialchars($staff['ten']); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <span class="owner-checklist__empty">Chưa có nhân sự cho bộ phận này</span>
                                    <?php endif; ?>
                                </td>


                                <td>
                                    <!-- Thêm hidden input để lưu giá trị gốc của điểm -->
                                    <input type="hidden" name="old_diem_<?php echo $row['id']; ?>"
                                           value="<?php echo isset($row['diem_danhgia']) ? $row['diem_danhgia'] : '0'; ?>">
                                    <select name="diem_danhgia_<?php echo $row['id']; ?>"
                                            class="diem-dropdown"
                                            data-tieuchi-id="<?php echo $row['id']; ?>"
                                            onchange="updateStatus(this)">
                                        <?php foreach ($score_options as $score_option) : ?>
                                            <?php
                                            $score_value = $score_option['value'];
                                            $is_selected = scoreValuesAreEqual($effective_score, $score_value);
                                            ?>
                                            <option value="<?php echo htmlspecialchars($score_value); ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($score_option['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <!-- Segmented control cho mobile: chỉ điều khiển select ở trên, không có name nên không vào form -->
                                    <div class="score-seg" data-score-seg data-tieuchi-id="<?php echo $row['id']; ?>">
                                        <?php foreach ($score_options as $score_option) : ?>
                                            <?php $is_selected = scoreValuesAreEqual($effective_score, $score_option['value']); ?>
                                            <button type="button"
                                                    class="score-seg__opt <?php echo $is_selected ? 'is-active' : ''; ?>"
                                                    data-value="<?php echo htmlspecialchars($score_option['value']); ?>">
                                                <?php echo htmlspecialchars($score_option['label']); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="note-toggle" data-note-toggle aria-expanded="<?php echo $note_text !== '' ? 'true' : 'false'; ?>">Ghi chú</button>
                                </td>
                                <td>
                                    <input type="checkbox"
                                           class="checkbox-input"
                                           id="checkbox_<?php echo $row['id']; ?>"
                                           data-tieuchi-id="<?php echo $row['id']; ?>"
                                           <?php echo $is_completed ? 'checked' : ''; ?>
                                           disabled>
                                    <label class="checkbox <?php echo $is_completed ? 'checked' : 'unchecked'; ?>"
                                           for="checkbox_<?php echo $row['id']; ?>"
                                           id="checkbox_label_<?php echo $row['id']; ?>">
                                        <span class="checkmark"><?php echo $is_completed ? '✓' : 'X'; ?></span>
                                    </label>
                                    <input type="hidden" name="da_thuchien_<?php echo $row['id']; ?>"
                                           value="<?php echo $is_completed ? 1 : 0; ?>"
                                           id="da_thuchien_<?php echo $row['id']; ?>">
                                </td>
                                <td>
                                    <!-- Thêm hidden input để lưu giá trị gốc của ghi chú -->
                                    <input type="hidden" name="old_ghichu_<?php echo $row['id']; ?>"
                                           value="<?php echo htmlspecialchars($row['ghichu'] ?? ''); ?>">
                                    <textarea name="ghichu_<?php echo $row['id']; ?>"
                                              class="evaluation-note-textarea"
                                              placeholder="Ghi chú"><?php echo htmlspecialchars($row['ghichu'] ?? ''); ?></textarea>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right-bold">Tổng điểm đánh giá:</td>
                            <td class="text-right-bold" id="total_points">
                                <?php echo number_format($total_points, 1); ?>/<?php echo number_format($max_possible_points, 1); ?>
                            </td>
                            <td colspan="3">
                                <div class="progress-bar-container">
                                    <?php
                                    $percent = ($max_possible_points > 0) ? ($total_points / $max_possible_points) * 100 : 0;
                                    $bar_color = "#4CAF50"; // Màu xanh lá mặc định

                                    // Thay đổi màu sắc dựa vào phần trăm hoàn thành
                                    if ($percent < 30) {
                                        $bar_color = "#F44336"; // Đỏ
                                    } elseif ($percent < 70) {
                                        $bar_color = "#FFC107"; // Vàng
                                    }
                                    ?>
                                    <div class="progress-bar" data-progress="<?php echo $percent; ?>">
                                        <?php echo round($percent); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                </div>

                <div class="button-group evaluation-actions">
                    <button type="submit" class="btn-save">Lưu đánh giá</button>
                    <a href="index.php" class="btn-back">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="save-pill" id="savePill">
    <button type="submit" form="danhgiaForm" class="save-pill__btn">
        <i class="fas fa-save" aria-hidden="true"></i>
        <span class="save-pill__label" id="savePillLabel">Lưu thay đổi</span>
    </button>
</div>
