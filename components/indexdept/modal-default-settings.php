<?php
$xuong_list = getXuongList($connect);
?>
<div id="defaultSettingModal" class="modal">
    <div class="modal-content" style="width: 70%; max-width: 900px; max-height: 80vh; overflow: hidden; margin: 5% auto;">
        <span class="close" onclick="closeDefaultSettingModal()">&times;</span>
        <h3 class="modal-title">Cài đặt hạn xử lý mặc định cho <?php echo $dept_display_name; ?> - <span id="xuong_display_name">Tất cả xưởng</span></h3>
        <p style="color: #666; margin-bottom: 15px;">Các cài đặt này sẽ được áp dụng tự động cho tất cả đơn hàng mới.</p>

        <div id="default_settings_status" style="margin-bottom: 15px; display: none;"></div>
        <input type="hidden" id="current_dept" value="<?php echo htmlspecialchars($dept); ?>">

        <div style="display: flex; flex-direction: column; height: calc(80vh - 150px);">
            <div style="margin-bottom: 15px; display: flex; justify-content: space-between; position: sticky; top: 0; background-color: white; padding: 10px 0; z-index: 100;">
                <div>
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <label for="selected_xuong" style="margin-right: 10px; font-weight: bold;">Chọn Xưởng:</label>
                        <select id="selected_xuong" class="form-control" onchange="changeSelectedXuong()" style="width: 200px;">
                            <option value="">-- Tất cả xưởng --</option>
                            <?php if ($xuong_list && $xuong_list->num_rows > 0) : ?>
                                <?php while ($row_xuong = $xuong_list->fetch_assoc()) : ?>
                                    <option value="<?php echo htmlspecialchars($row_xuong['xuong']); ?>" <?php echo ($row_xuong['xuong'] == $xuong) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($row_xuong['xuong']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="button" onclick="saveAllDefaultSettings('<?php echo htmlspecialchars($dept, ENT_QUOTES, 'UTF-8'); ?>')" class="btn-add-criteria">Lưu tất cả cài đặt</button>
                    <button type="button" onclick="openStaffModal('<?php echo htmlspecialchars($dept, ENT_QUOTES, 'UTF-8'); ?>')" class="btn-add-criteria" style="background-color: #17a2b8; margin-left: 10px;">Quản lý người thực hiện</button>
                </div>
                <button type="button" onclick="closeDefaultSettingModal()" class="btn-add-criteria" style="background-color: #6c757d;">Đóng</button>
            </div>

            <div class="table-container" style="flex: 1; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                <table class="evaluation-table" style="width: 100%;">
                    <thead style="position: sticky; top: 0; background-color: #003366; z-index: 10;">
                        <tr>
                            <th style="width: 5%; color: white;">STT</th>
                            <th style="width: 30%; color: white;">Tiêu chí đánh giá</th>
                            <th style="width: 15%; color: white;">Loại tính hạn</th>
                            <th style="width: 10%; color: white;">Số ngày</th>
                            <th style="width: 20%; height: 50px; color: white;">Người chịu trách nhiệm</th>
                            <th style="width: 20%; color: white;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="default_settings_tbody">
                        <?php
                        $sql = "SELECT tc.*, ds.ngay_tinh_han, ds.so_ngay_xuly, ds.nguoi_chiu_trachnhiem_default, nv.ten as ten_nguoi_thuchien
                               FROM tieuchi_dept tc
                               LEFT JOIN default_settings ds ON tc.id = ds.id_tieuchi AND ds.dept = ?
                               LEFT JOIN nhan_vien nv ON ds.nguoi_chiu_trachnhiem_default = nv.id
                               WHERE tc.dept = ?
                               ORDER BY
                                   CASE tc.nhom
                                       WHEN 'Nhóm Nghiệp Vụ' THEN 1
                                       WHEN 'Nhóm May Mẫu' THEN 2
                                       WHEN 'Nhóm Quy Trình' THEN 3
                                       WHEN 'Kho Nguyên Liệu' THEN 1
                                       WHEN 'Kho Phụ Liệu' THEN 2
                                       ELSE 4
                                   END, tc.thutu";
                        $stmt = $connect->prepare($sql);
                        $stmt->bind_param("ss", $dept, $dept);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        $current_nhom = '';
                        while ($row = $result->fetch_assoc()) :
                            if ($current_nhom != $row['nhom']) :
                                $current_nhom = $row['nhom'];
                                $nhom_display = getNhomDisplayName($dept, $row['nhom']);
                                if ($nhom_display) :
                        ?>
                        <tr>
                            <td colspan="6" style="background-color: #f3f4f6; color: #1e40af; font-weight: bold; text-align: left; padding: 10px;">
                                <?php echo $nhom_display; ?>
                            </td>
                        </tr>
                        <?php
                                endif;
                            endif;

                            $ngay_tinh_han = $row['ngay_tinh_han'] ?? 'ngay_vao';
                            $so_ngay = $row['so_ngay_xuly'] ?? 7;
                            $nguoi_default = $row['nguoi_chiu_trachnhiem_default'] ?? 0;
                            $staff_list = getStaffByDept($connect, $dept);
                        ?>
                        <tr id="ds_row_<?php echo $row['id']; ?>">
                            <td><?php echo $row['thutu']; ?></td>
                            <td class="text-left"><?php echo htmlspecialchars($row['noidung']); ?></td>
                            <td>
                                <select id="ds_ngay_tinh_han_<?php echo $row['id']; ?>" class="form-control" style="width: 100%;">
                                    <option value="ngay_vao" <?php echo ($ngay_tinh_han == 'ngay_vao') ? 'selected' : ''; ?>>Ngày vào trừ số ngày</option>
                                    <option value="ngay_vao_cong" <?php echo ($ngay_tinh_han == 'ngay_vao_cong') ? 'selected' : ''; ?>>Ngày vào cộng số ngày</option>
                                    <option value="ngay_ra" <?php echo ($ngay_tinh_han == 'ngay_ra') ? 'selected' : ''; ?>>Ngày ra cộng số ngày</option>
                                    <option value="ngay_ra_tru" <?php echo ($ngay_tinh_han == 'ngay_ra_tru') ? 'selected' : ''; ?>>Ngày ra trừ số ngày</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" id="ds_so_ngay_xuly_<?php echo $row['id']; ?>" class="form-control" style="width: 100%;" value="<?php echo $so_ngay; ?>" min="0" max="365">
                            </td>
                            <td>
                                <select id="ds_nguoi_chiu_trachnhiem_<?php echo $row['id']; ?>" class="form-control" style="width: 100%; height: 40px;">
                                    <option value="0">-- Chọn người chịu trách nhiệm --</option>
                                    <?php while ($staff = $staff_list->fetch_assoc()) : ?>
                                        <option value="<?php echo $staff['id']; ?>" <?php echo ($nguoi_default == $staff['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($staff['ten']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                <button type="button" onclick="saveDefaultSetting(<?php echo intval($row['id']); ?>, '<?php echo htmlspecialchars($dept, ENT_QUOTES, 'UTF-8'); ?>')" class="btn-default-setting">Lưu cài đặt</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 15px; display: flex; justify-content: space-between; position: sticky; bottom: 0; background-color: white; padding: 10px 0; z-index: 100;">
                <button type="button" onclick="saveAllDefaultSettings('<?php echo htmlspecialchars($dept, ENT_QUOTES, 'UTF-8'); ?>')" class="btn-add-criteria">Lưu tất cả cài đặt</button>
                <button type="button" onclick="closeDefaultSettingModal()" class="btn-add-criteria" style="background-color: #6c757d;">Đóng</button>
            </div>
        </div>
    </div>
</div>
