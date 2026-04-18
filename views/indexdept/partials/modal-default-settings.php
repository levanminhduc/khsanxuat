        <!-- Modal cài đặt hạn xử lý mặc định -->
        <div id="defaultSettingModal" class="modal default-settings-modal" role="dialog" aria-modal="true" aria-labelledby="default-setting-title">
            <div class="modal-content default-settings-modal__content">
                <button type="button" class="close default-settings-modal__close" onclick="closeDefaultSettingModal()" aria-label="Đóng cài đặt mặc định">&times;</button>

                <div class="default-settings-modal__header">
                    <h3 id="default-setting-title" class="modal-title default-settings-modal__title">
                        Cài đặt hạn xử lý mặc định cho <?php echo htmlspecialchars($dept_display_name); ?>
                        - <span id="xuong_display_name">Tất cả xưởng</span>
                    </h3>
                    <p class="default-settings-modal__subtitle">
                        Các cài đặt này sẽ được áp dụng tự động cho tất cả đơn hàng mới được import vào hệ thống.
                    </p>
                </div>

                <div id="default_settings_status" class="default-settings-modal__status" style="display: none;"></div>
                <input type="hidden" id="current_dept" value="<?php echo htmlspecialchars($dept); ?>">

                <div class="default-settings-modal__body">
                    <div class="default-settings-modal__toolbar default-settings-modal__toolbar--top">
                        <div class="default-settings-modal__toolbar-left">
                            <div class="default-settings-modal__xuong-filter">
                                <label for="selected_xuong" class="default-settings-modal__label">Chọn Xưởng:</label>
                                <select id="selected_xuong" class="form-control default-settings-modal__select-xuong" onchange="changeSelectedXuong()">
                                    <?php
                                    // Lấy danh sách xưởng từ bảng khsanxuat
                                    $sql_xuong = "SELECT DISTINCT xuong FROM khsanxuat WHERE xuong != '' ORDER BY xuong";
                                    $result_xuong = $connect->query($sql_xuong);

                                    echo '<option value="">-- Tất cả xưởng --</option>';
                                    if ($result_xuong && $result_xuong->num_rows > 0) {
                                        while ($row_xuong = $result_xuong->fetch_assoc()) {
                                            $selected = ($row_xuong['xuong'] == $xuong) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($row_xuong['xuong']) . '" ' . $selected . '>' . htmlspecialchars($row_xuong['xuong']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="default-settings-modal__actions">
                                <button type="button" onclick="saveAllDefaultSettings('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--primary">Lưu tất cả cài đặt</button>
                                <button type="button" onclick="openStaffModal('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--info">Quản lý người thực hiện</button>
                            </div>
                        </div>

                        <button type="button" onclick="closeDefaultSettingModal()" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--secondary">Đóng</button>
                    </div>

                    <div class="table-container default-settings-modal__table-wrap">
                        <table class="evaluation-table default-settings-modal__table">
                            <thead>
                                <tr>
                                    <th class="default-settings-modal__col default-settings-modal__col--stt">STT</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--criteria">Tiêu chí đánh giá</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--type">Loại tính hạn</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--days">Số ngày</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--owner">Người chịu trách nhiệm</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--actions">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="default_settings_tbody">
                                <?php
                                // Lấy danh sách tiêu chí
                                $sql = "SELECT tc.*, kds.ngay_tinh_han, kds.so_ngay_xuly, kds.nguoi_chiu_trachnhiem_default, nv.ten as ten_nguoi_thuchien
                                       FROM tieuchi_dept tc
                                       LEFT JOIN khsanxuat_default_settings kds ON tc.id = kds.id_tieuchi AND kds.dept = ?
                                       LEFT JOIN nhan_vien nv ON kds.nguoi_chiu_trachnhiem_default = nv.id
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
                                $stmt->bind_param("ss", $dept, $dept);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                $current_nhom = '';

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
                                            <td colspan="6" class="default-settings-modal__group-row">
                                                <?php echo htmlspecialchars($nhom_display); ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }

                                    // Giá trị mặc định
                                    $ngay_tinh_han = isset($row['ngay_tinh_han']) ? $row['ngay_tinh_han'] : 'ngay_vao';
                                    $so_ngay_xuly = isset($row['so_ngay_xuly']) ? intval($row['so_ngay_xuly']) : 7;
                                    $nguoi_chiu_trachnhiem_default = isset($row['nguoi_chiu_trachnhiem_default']) ? intval($row['nguoi_chiu_trachnhiem_default']) : 0;
                                    ?>
                                    <tr id="ds_row_<?php echo $row['id']; ?>">
                                        <td><?php echo (int) $row['thutu']; ?></td>
                                        <td class="text-left"><?php echo htmlspecialchars($row['noidung']); ?></td>
                                        <td>
                                            <select id="ds_ngay_tinh_han_<?php echo $row['id']; ?>" class="form-control">
                                                <option value="ngay_vao" <?php echo ($ngay_tinh_han == 'ngay_vao') ? 'selected' : ''; ?>>Ngày vào trừ số ngày</option>
                                                <option value="ngay_vao_cong" <?php echo ($ngay_tinh_han == 'ngay_vao_cong') ? 'selected' : ''; ?>>Ngày vào cộng số ngày</option>
                                                <option value="ngay_ra" <?php echo ($ngay_tinh_han == 'ngay_ra') ? 'selected' : ''; ?>>Ngày ra cộng số ngày</option>
                                                <option value="ngay_ra_tru" <?php echo ($ngay_tinh_han == 'ngay_ra_tru') ? 'selected' : ''; ?>>Ngày ra trừ số ngày</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" id="ds_so_ngay_xuly_<?php echo $row['id']; ?>" class="form-control" value="<?php echo (int) $so_ngay_xuly; ?>" min="0" max="365">
                                        </td>
                                        <td>
                                            <select id="ds_nguoi_chiu_trachnhiem_<?php echo $row['id']; ?>" class="form-control default-settings-modal__owner-select">
                                                <option value="0">-- Chọn người chịu trách nhiệm --</option>
                                                <?php
                                                // Lấy danh sách người thực hiện thuộc bộ phận
                                                $sql_staff = "SELECT id, ten FROM nhan_vien WHERE phong_ban = ? AND active = 1 ORDER BY ten";
                                                $stmt_staff = $connect->prepare($sql_staff);
                                                $stmt_staff->bind_param("s", $dept);
                                                $stmt_staff->execute();
                                                $result_staff = $stmt_staff->get_result();

                                                while ($staff = $result_staff->fetch_assoc()) {
                                                    $selected = ($nguoi_chiu_trachnhiem_default == $staff['id']) ? 'selected' : '';
                                                    echo '<option value="' . (int) $staff['id'] . '" ' . $selected . '>' . htmlspecialchars($staff['ten']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" onclick="saveDefaultSetting(<?php echo (int) $row['id']; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-default-setting">Lưu cài đặt</button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="default-settings-modal__toolbar default-settings-modal__toolbar--bottom">
                        <button type="button" onclick="saveAllDefaultSettings('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--primary">Lưu tất cả cài đặt</button>
                        <button type="button" onclick="closeDefaultSettingModal()" class="btn-add-criteria default-settings-modal__btn default-settings-modal__btn--secondary">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
