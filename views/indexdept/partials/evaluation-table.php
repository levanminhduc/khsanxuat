        <!-- Thêm vào phần hiển thị form, trước khi hiển thị các tiêu chí -->
        <?php if (isset($error_message)) : ?>
        <div class="alert alert-error evaluation-alert">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <div class="evaluation-section evaluation-section--wide">
            <h2>Tiêu chí đánh giá</h2>
            <form action="save_danhgia_with_log.php" method="POST" id="danhgiaForm">
                <?php echo getCsrfInput(); ?>
                <input type="hidden" name="id_sanxuat" value="<?php echo $id; ?>">
                <input type="hidden" name="dept" value="<?php echo $dept; ?>">

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
                        // Lấy danh sách tiêu chí và trạng thái đánh giá
                        $sql = "SELECT tc.*, dg.nguoi_thuchien, dg.da_thuchien, dg.diem_danhgia, dg.ghichu, dg.han_xuly, dg.so_ngay_xuly, dg.ngay_tinh_han
                               FROM tieuchi_dept tc
                               LEFT JOIN danhgia_tieuchi dg ON tc.id = dg.id_tieuchi
                                    AND dg.id_sanxuat = ?
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
                        $stmt->bind_param("is", $id, $dept);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        $total_tieuchi = 0;
                        $completed_tieuchi = 0;
                        $current_nhom = '';
                        $total_points = 0; // Biến lưu tổng điểm
                        $max_possible_points = 0; // Biến lưu tổng điểm tối đa có thể đạt được

                        while ($row = $result->fetch_assoc()) {
                            if ($current_nhom != $row['nhom']) {
                                $current_nhom = $row['nhom'];

                                // Hiển thị tên nhóm
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
                                    <td colspan="7" class="evaluation-group-row">
                                        <?php echo $nhom_display; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            $total_tieuchi++;

                            $score_options = getScoreOptionsForCriteria($connect, $row['id'], $dept, $row['thutu']);
                            $default_score_value = getDefaultScoreValueForCriteria($connect, $row['id'], $dept, $row['thutu']);
                            $has_saved_score = isset($row['diem_danhgia']) && $row['diem_danhgia'] !== null;
                            $effective_score = $has_saved_score ? floatval($row['diem_danhgia']) : floatval($default_score_value);

                            if ($effective_score > 0) {
                                $completed_tieuchi++;
                            }

                            // Cộng điểm vào tổng
                            $diem_hien_tai = $effective_score;
                            $total_points += $diem_hien_tai;

                            $max_possible_points += getMaxScoreFromOptions($score_options);

                            // Xử lý hạn xử lý cho từng tiêu chí
                            if (isset($row['han_xuly']) && !empty($row['han_xuly'])) {
                                // Sử dụng hạn xử lý riêng của tiêu chí nếu có
                                $han_tc = new DateTime($row['han_xuly']);
                                $han_tc_formatted = $han_tc->format('d/m/Y');
                                $tc_so_ngay_xuly = isset($row['so_ngay_xuly']) ? intval($row['so_ngay_xuly']) : $so_ngay_xuly;
                                $tc_ngay_tinh_han = isset($row['ngay_tinh_han']) ? $row['ngay_tinh_han'] : 'ngay_vao';
                            } else {
                                // Nếu chưa có hạn xử lý riêng, sử dụng hạn xử lý chung
                                $han_tc = clone $han_xuly;
                                $han_tc_formatted = $han_xuly_formatted;
                                $tc_so_ngay_xuly = $so_ngay_xuly;
                                $tc_ngay_tinh_han = 'ngay_vao'; // Giá trị mặc định
                            }

                            // Kiểm tra nếu đã quá hạn (chỉ hiển thị cảnh báo nếu chưa hoàn thành)
                            $now = new DateTime();
                            // Chỉ hiển thị màu đỏ khi: 1) Đã quá hạn VÀ 2) Chưa hoàn thành (dấu X đỏ)
                            $is_overdue = ($han_tc < $now && $effective_score <= 0);
                            $deadline_class = $is_overdue ? 'overdue' : '';

                            // Biến kiểm tra tiêu chí đã hoàn thành hay chưa (để hiển thị khác nhau)
                            $is_completed = ($effective_score > 0);
                            ?>
                            <tr>
                                <td><?php echo $row['thutu']; ?></td>
                                <td class="text-left;"><?php echo htmlspecialchars($row['noidung']); ?></td>
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
                                <td>
                                    <!-- Thêm hidden input để lưu giá trị gốc của người thực hiện -->
                                    <input type="hidden" name="old_nguoi_thuchien_<?php echo $row['id']; ?>"
                                           value="<?php echo $row['nguoi_thuchien']; ?>">
                                    <select name="nguoi_thuchien_<?php echo $row['id']; ?>" required class="nguoi-thuchien-select">
                                        <?php
                                        // Lấy danh sách người thực hiện từ cơ sở dữ liệu
                                        $sql_staff = "SELECT id, ten FROM nhan_vien WHERE phong_ban = ? AND active = 1 ORDER BY ten";
                                        $stmt_staff = $connect->prepare($sql_staff);
                                        $stmt_staff->bind_param("s", $dept);
                                        $stmt_staff->execute();
                                        $result_staff = $stmt_staff->get_result();

                                        if ($result_staff->num_rows > 0) {
                                            while ($staff = $result_staff->fetch_assoc()) {
                                                $selected = ($row['nguoi_thuchien'] == $staff['id']) ? 'selected' : '';
                                                echo "<option value='" . $staff['id'] . "' $selected>" . htmlspecialchars($staff['ten']) . "</option>";
                                            }
                                        } else {
                                            // Dùng danh sách mặc định nếu không có dữ liệu
                                            $nguoi_thuchien = ($dept == 'kehoach')
                                                ? ['Nguyễn Văn A', 'Trần Thị B']
                                                : ['Phạm Văn X', 'Lê Thị Y'];

                                            foreach ($nguoi_thuchien as $nguoi) {
                                                $selected = ($row['nguoi_thuchien'] == $nguoi) ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($nguoi) . "' $selected>" . htmlspecialchars($nguoi) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
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




