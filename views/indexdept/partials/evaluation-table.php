        <!-- Thêm vào phần hiển thị form, trước khi hiển thị các tiêu chí -->
        <?php if (isset($error_message)) : ?>
        <div class="alert alert-error" style="padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; background-color: #f8d7da;">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- <div class="evaluation-section" style="max-width: 1200px; margin: 0 auto; overflow-x: auto;"> -->
        <div class="evaluation-section" style="max-width: 1600px; margin: 0 auto; overflow-x: auto;">
            <h2>Tiêu chí đánh giá</h2>
            <form action="save_danhgia_with_log.php" method="POST" id="danhgiaForm">
                <?php echo getCsrfInput(); ?>
                <input type="hidden" name="id_sanxuat" value="<?php echo $id; ?>">
                <input type="hidden" name="dept" value="<?php echo $dept; ?>">

                <table class="evaluation-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">STT</th>
                            <th style="width: 360px;" class="resizable">Tiêu chí đánh giá</th>
                            <th style="width: 130px;" class="resizable">
                                Hạn Xử Lý
                                <span class="tooltip-icon" title="Thời hạn đã được tính toán tự động">ⓘ</span>
                                <?php /* if($is_admin): */ ?>
                                <button type="button" onclick="openDeadlineModal()" class="small-btn">Cài đặt</button>
                                <?php /* endif; */ ?>
                            </th>
                            <th style="width: 200px; height: 50px;" class="resizable">Người chịu trách nhiệm</th>
                            <th style="width: 120px;" class="resizable">Điểm đánh giá</th>
                            <th style="width: 80px;">Đã thực hiện</th>
                            <th style="width: 150px;" class="resizable">Ghi chú</th>
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
                                    <td colspan="7" style="background-color: #f3f4f6; color: #1e40af; font-weight: bold; text-align: left; padding: 10px;">
                                        <?php echo $nhom_display; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            $total_tieuchi++;
                            if ($row['da_thuchien'] == 1) {
                                $completed_tieuchi++;
                            }

                            // Cộng điểm vào tổng
                            $diem_hien_tai = isset($row['diem_danhgia']) ? floatval($row['diem_danhgia']) : 0;
                            $total_points += $diem_hien_tai;

                            // Tính điểm tối đa tùy theo loại tiêu chí
                            if ($dept == 'kehoach' && ($row['thutu'] == 7 || $row['thutu'] == 8)) {
                                $max_possible_points += 1.5; // Điểm tối đa cho tiêu chí 7 và 8 của bộ phận kế hoạch là 1.5
                            } else {
                                $max_possible_points += 3; // Điểm tối đa cho các tiêu chí khác là 3
                            }

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
                            $is_overdue = ($han_tc < $now && (!isset($row['diem_danhgia']) || $row['diem_danhgia'] == 0));
                            $deadline_class = $is_overdue ? 'overdue' : '';

                            // Biến kiểm tra tiêu chí đã hoàn thành hay chưa (để hiển thị khác nhau)
                            $is_completed = (!empty($row['diem_danhgia']) && $row['diem_danhgia'] > 0);
                            ?>
                            <tr>
                                <td><?php echo $row['thutu']; ?></td>
                                <td class="text-left;"><?php echo htmlspecialchars($row['noidung']); ?></td>
                                <td class="deadline-info">
                                    <span class="deadline-date <?php echo $deadline_class; ?>" id="date_display_<?php echo $row['id']; ?>"><?php echo $han_tc_formatted; ?></span>
                                    <?php /* if($is_admin): */ ?>
                                    <div class="deadline-form">
                                        <div style="display: flex; align-items: center;">
                                            <input type="number" id="so_ngay_xuly_<?php echo $row['id']; ?>" value="<?php echo isset($row['so_ngay_xuly']) ? $row['so_ngay_xuly'] : $tc_so_ngay_xuly; ?>" min="1" max="30" class="deadline-input">
                                            <button type="button" onclick="updateDeadline(<?php echo $id; ?>, <?php echo $row['id']; ?>, '<?php echo $dept; ?>')" class="deadline-button">Cập nhật</button>
                                        </div>
                                        <!-- Thêm select để chọn ngày tính hạn xử lý cho từng tiêu chí -->
                                        <div style="display: flex; align-items: center; margin-top: 3px;">
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
                                    <select name="nguoi_thuchien_<?php echo $row['id']; ?>" required class="nguoi-thuchien-select" style="height: 40px; white-space: normal;">
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
                                        <?php if ($dept == 'kehoach' && ($row['thutu'] == 7 || $row['thutu'] == 8)) : ?>
                                            <!-- Mức điểm đặc biệt cho tiêu chí 7 và 8 của Kế Hoạch -->
                                            <option value="0" <?php echo (!isset($row['diem_danhgia']) || $row['diem_danhgia'] === null || $row['diem_danhgia'] == 0) ? 'selected' : ''; ?>>0</option>
                                            <option value="0.5" <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] == 0.5) ? 'selected' : ''; ?>>0.5</option>
                                            <option value="1.5" <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] == 1.5) ? 'selected' : ''; ?>>1.5</option>
                                        <?php else : ?>
                                            <!-- Mức điểm mặc định cho các tiêu chí khác -->
                                            <option value="0" <?php echo (!isset($row['diem_danhgia']) || $row['diem_danhgia'] === null || $row['diem_danhgia'] == 0) ? 'selected' : ''; ?>>0</option>
                                            <option value="1" <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] == 1) ? 'selected' : ''; ?>>1</option>
                                            <option value="3" <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] == 3) ? 'selected' : ''; ?>>3</option>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="checkbox"
                                           class="checkbox-input"
                                           id="checkbox_<?php echo $row['id']; ?>"
                                           data-tieuchi-id="<?php echo $row['id']; ?>"
                                           <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] > 0) ? 'checked' : ''; ?>
                                           disabled>
                                    <label class="checkbox <?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] > 0) ? 'checked' : 'unchecked'; ?>"
                                           for="checkbox_<?php echo $row['id']; ?>"
                                           id="checkbox_label_<?php echo $row['id']; ?>">
                                        <span class="checkmark"><?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] > 0) ? '✓' : 'X'; ?></span>
                                    </label>
                                    <input type="hidden" name="da_thuchien_<?php echo $row['id']; ?>"
                                           value="<?php echo (isset($row['diem_danhgia']) && $row['diem_danhgia'] > 0) ? 1 : 0; ?>"
                                           id="da_thuchien_<?php echo $row['id']; ?>">
                                </td>
                                <td>
                                    <!-- Thêm hidden input để lưu giá trị gốc của ghi chú -->
                                    <input type="hidden" name="old_ghichu_<?php echo $row['id']; ?>"
                                           value="<?php echo htmlspecialchars($row['ghichu'] ?? ''); ?>">
                                    <textarea name="ghichu_<?php echo $row['id']; ?>"
                                              style="width: 120px; height: 100px;"
                                              placeholder="Ghi chú"><?php echo htmlspecialchars($row['ghichu'] ?? ''); ?></textarea>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold;">Tổng điểm đánh giá:</td>
                            <td style="font-weight: bold; text-align: right;" id="total_points">
                                <?php echo number_format($total_points, 1); ?>/<?php echo number_format($max_possible_points, 1); ?>
                            </td>
                            <td colspan="3">
                                <div class="progress-bar-container" style="width: 100%; background-color: #eee; height: 20px; border-radius: 10px; overflow: hidden;">
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
                                    <div class="progress-bar" style="width: <?php echo $percent; ?>%; background-color: <?php echo $bar_color; ?>; height: 100%; text-align: center; line-height: 20px; color: white; font-weight: bold;">
                                        <?php echo round($percent); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div class="button-group">
                    <button type="submit" class="btn-save">Lưu đánh giá</button>
                    <a href="index.php" class="btn-back">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>




