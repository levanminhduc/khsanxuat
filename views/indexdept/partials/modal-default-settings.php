        <?php
        // Ô select/input của modal: phần chung; kích thước + padding đặt riêng trên từng ô
        $ds_field = 'tw-box-border tw-rounded-md tw-border tw-border-solid tw-border-slate-300 tw-bg-white tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-2 focus:tw-ring-[#143583]/15';
        // Ô trong bảng: kế thừa font của modal, số dạng tabular
        $ds_table_field = $ds_field . ' tw-h-8 tw-min-h-8 tw-px-2 tw-py-[5px] [font-family:inherit] tw-text-[13px] tw-font-bold tw-tabular-nums';
        $ds_label = 'tw-mb-1 tw-block tw-text-xs tw-font-bold tw-text-slate-700';
        $ds_th = 'tw-sticky tw-top-0 tw-z-10 tw-box-border tw-border tw-border-solid tw-border-[#ddd] tw-border-b-[#0f2861] tw-bg-[#143583] tw-px-2 tw-py-[7px] tw-text-center tw-text-[12px] tw-font-bold tw-leading-[1.25] tw-tracking-normal tw-text-slate-50 tw-whitespace-nowrap [word-break:keep-all] tw-break-words tw-align-middle tw-shadow-[0_1px_0_rgba(0,0,0,0.1)]';
        // Ô dữ liệu: màu chữ đặt riêng trên từng ô (utility màu cùng nhóm sẽ đè nhau theo thứ tự build)
        $ds_td = 'tw-border tw-border-solid tw-border-[#ddd] tw-border-b-[#eef2f7] tw-bg-white tw-px-2 tw-py-[5px] tw-align-middle tw-text-[12.5px] tw-leading-[1.28] group-hover:tw-bg-slate-50 max1024:tw-text-[12px] max768:tw-p-1.5 max768:tw-text-[12px]';
        // Nút footer trên mobile: không giãn, giữ 1 dòng (khớp footer chung của các modal)
        $ds_action_btn = 'max768:tw-mr-0 max768:tw-w-auto max768:tw-flex-none max768:tw-whitespace-nowrap';
        ?>
        <!-- Modal cài đặt hạn xử lý mặc định -->
        <div id="defaultSettingModal" class="modal score-options-modal indexdept-modern-modal [font-family:Arial,Helvetica,sans-serif] tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-3 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="default-setting-title" hidden>
            <div class="modal-content tw-relative tw-m-0 tw-flex tw-max-h-[92vh] tw-w-full tw-max-w-7xl tw-flex-col tw-overflow-hidden tw-rounded-[10px] tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft max768:tw-mx-auto max768:tw-my-[3vh] max768:tw-w-[96vw] max768:tw-max-w-[96vw]">
                <button type="button" class="close tw-absolute tw-right-3 tw-top-3 tw-z-20 tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-white/90 [font-family:inherit] tw-text-xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:-tw-translate-y-px hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeDefaultSettingModal()" aria-label="Đóng cài đặt mặc định">&times;</button>

                <div class="tw-flex tw-items-center tw-justify-between tw-gap-4 tw-border-b [border-bottom-style:solid] max768:tw-flex-col max768:tw-items-start max768:tw-gap-2.5 tw-border-slate-200 tw-bg-slate-50 tw-px-4 tw-py-3">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                        <div class="tw-flex tw-min-w-0 tw-items-start tw-gap-3 max768:tw-pr-10">
                            <span class="tw-inline-flex tw-h-9 tw-w-9 tw-flex-none tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-[#0f2861] tw-bg-[#143583] tw-text-[15px] tw-text-white max768:tw-text-[16px] tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                                <i class="fas fa-cog"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <span class="tw-mb-0.5 tw-block tw-text-xs tw-font-bold tw-text-[#143583]">Mặc định</span>
                                <h3 id="default-setting-title" class="modal-title tw-m-0 tw-text-balance tw-pr-10 tw-text-xl tw-font-bold tw-leading-tight tw-text-slate-950">
                                    Cài đặt hạn xử lý
                                </h3>
                                <span class="tw-mt-1 tw-inline-flex tw-min-h-[26px] tw-max-w-full tw-items-center tw-rounded-md tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-slate-700">
                                    Xưởng: <span id="xuong_display_name" class="tw-ml-1 tw-text-[#143583]">Tất cả xưởng</span>
                                </span>
                            </div>
                        </div>
                        <span class="tw-mr-10 tw-hidden tw-min-h-7 tw-max-w-xs tw-items-center tw-truncate tw-rounded-md tw-border tw-border-solid tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1 tw-text-right tw-text-xs tw-font-semibold tw-text-slate-700 tw-shadow-[0_1px_2px_rgba(15,23,42,0.06)] sm:tw-inline-flex max768:tw-w-full max768:tw-justify-start max768:tw-text-left" title="<?php echo htmlspecialchars($dept_display_name); ?>">
                            <?php echo htmlspecialchars($dept_display_name); ?>
                        </span>
                    </div>
                </div>

                <div id="default_settings_status" class="score-options-modal__status tw-mx-4 tw-mt-2 tw-rounded-md tw-px-[9px] tw-py-[7px] tw-text-center" hidden></div>
                <input type="hidden" id="current_dept" value="<?php echo htmlspecialchars($dept); ?>">

                <div class="tw-flex tw-min-h-0 tw-flex-1 tw-flex-col tw-gap-3 tw-bg-[#eef2f5] tw-px-4 tw-py-3 max768:tw-gap-1.5 max768:tw-p-1.5">
                    <!-- max768:tw-w-full: giữ đúng kiểu cũ (width 100% + padding, content-box) nên khối này rộng hơn thân modal 24px trên mobile -->
                    <div class="tw-flex tw-flex-col tw-items-center tw-justify-between tw-gap-3 tw-rounded-md tw-border tw-border-slate-200 tw-bg-slate-50 tw-p-3 max768:tw-w-full max768:tw-items-stretch lg:tw-flex-row lg:tw-items-end">
                        <div class="tw-flex tw-flex-col tw-flex-wrap tw-items-center tw-gap-3 max768:tw-items-stretch lg:tw-flex-row lg:tw-items-end">
                            <div class="tw-flex tw-min-w-[240px] tw-items-center tw-gap-2 max768:tw-flex-col max768:tw-items-stretch">
                                <label for="selected_xuong" class="<?php echo $ds_label; ?>">Chọn xưởng</label>
                                <select id="selected_xuong" class="<?php echo $ds_field; ?> tw-h-9 tw-min-h-[34px] tw-w-[220px] tw-px-2 tw-py-[5px] tw-text-[13px] tw-font-bold max768:tw-w-full" onchange="changeSelectedXuong()">
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

                            <div class="tw-min-w-[160px]">
                                <label for="selected_line" class="<?php echo $ds_label; ?>">Chọn line</label>
                                <select id="selected_line" class="<?php echo $ds_field; ?> tw-h-9 tw-w-full tw-p-2 tw-text-[14px] disabled:tw-cursor-not-allowed disabled:tw-bg-slate-100" onchange="changeSelectedLine()" disabled>
                                    <option value="">-- Tất cả line --</option>
                                </select>
                            </div>

                            <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2 max768:tw-flex-col max768:tw-items-stretch">
                                <button type="button" onclick="openStaffModal('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria tw-inline-flex tw-h-9 tw-min-h-[34px] tw-items-center tw-justify-center tw-gap-2 tw-rounded-md tw-border tw-border-solid tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-px-3 tw-py-0 tw-text-sm tw-font-bold tw-text-[#143583] tw-shadow-sm tw-transition hover:tw-bg-[#dbeafe] hover:tw-text-slate-900 active:tw-scale-[0.96] max768:tw-mr-0 max768:tw-flex-1">
                                    <i class="fas fa-users tw-mr-2" aria-hidden="true"></i>
                                    Người thực hiện
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="tw-mx-3 tw-mt-3 tw-max-h-[calc(92vh-158px)] tw-min-h-0 tw-flex-1 tw-overflow-auto tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-white tw-shadow-[inset_0_1px_0_rgba(255,255,255,0.85)] max768:tw-m-0 max768:tw-max-h-[calc(94vh-140px)]">
                        <table class="tw-mb-5 tw-w-full tw-min-w-full tw-border-collapse tw-border-spacing-0 tw-text-sm max1024:tw-min-w-[1020px] max768:tw-min-w-[980px]">
                            <thead class="tw-sticky tw-top-0 tw-z-10">
                                <tr class="tw-bg-slate-100 tw-text-left tw-text-xs tw-font-bold tw-tracking-wide tw-text-slate-600">
                                    <th class="<?php echo $ds_th; ?> tw-w-11 tw-min-w-[44px]">STT</th>
                                    <th class="<?php echo $ds_th; ?> tw-w-[360px] tw-min-w-[360px]">Tiêu chí đánh giá</th>
                                    <th class="<?php echo $ds_th; ?> tw-w-[210px] tw-min-w-[210px]">Loại tính hạn</th>
                                    <th class="<?php echo $ds_th; ?> tw-w-[92px] tw-min-w-[92px]">Số ngày</th>
                                    <th class="<?php echo $ds_th; ?> tw-w-[230px] tw-min-w-[230px]">Người chịu trách nhiệm</th>
                                    <th class="<?php echo $ds_th; ?> tw-w-[70px] tw-min-w-[70px]">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="default_settings_tbody" class="tw-divide-y tw-divide-slate-100">
                                <?php
                                // Lấy danh sách tiêu chí
                                // Đọc từ default_settings (nguồn mà save + import + API get_default_settings dùng)
                                // thay cho khsanxuat_default_settings, để modal hiển thị đúng giá trị đã lưu.
                                // Ưu tiên cài đặt theo xưởng hiện tại, fallback về cài đặt "tất cả xưởng" (xuong = '').
                                $sql = "SELECT tc.*, ds.ngay_tinh_han, ds.so_ngay_xuly, ds.nguoi_chiu_trachnhiem_default, nv.ten as ten_nguoi_thuchien
                                       FROM tieuchi_dept tc
                                       LEFT JOIN default_settings ds
                                              ON ds.id_tieuchi = tc.id
                                             AND ds.dept = ?
                                             AND ds.xuong = (
                                                 SELECT ds2.xuong FROM default_settings ds2
                                                 WHERE ds2.dept = ? AND ds2.id_tieuchi = tc.id AND (ds2.xuong = ? OR ds2.xuong = '')
                                                 ORDER BY CASE WHEN ds2.xuong = ? THEN 0 ELSE 1 END
                                                 LIMIT 1
                                             )
                                       LEFT JOIN nhan_vien nv ON ds.nguoi_chiu_trachnhiem_default = nv.id
                                       WHERE tc.dept = ?
                                       ORDER BY
                                           " . getNhomOrderByCase() . ",
                                           tc.thutu";

                                $stmt = $connect->prepare($sql);
                                $stmt->bind_param("sssss", $dept, $dept, $xuong, $xuong, $dept);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                $current_nhom = '';
                                $default_settings_count = 0;

                                while ($row = $result->fetch_assoc()) {
                                    $default_settings_count++;
                                    if ($current_nhom != $row['nhom']) {
                                        $current_nhom = $row['nhom'];

                                        // Hiển thị tên nhóm
                                        $nhom_display = getNhomDisplayName($dept, $row['nhom']);
                                        ?>
                                        <tr>
                                            <td colspan="6" class="tw-border tw-border-solid tw-border-[#ddd] tw-border-t-[#d7e4fb] tw-border-b-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1.5 tw-text-left tw-align-middle tw-text-[12.5px] tw-font-bold tw-leading-[1.28] tw-text-[#143583] max1024:tw-text-[12px] max768:tw-p-1.5 max768:tw-text-[12px]">
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
                                    <tr id="ds_row_<?php echo (int) $row['id']; ?>" class="tw-group tw-bg-white tw-transition hover:tw-bg-[#eef5ff]/60">
                                        <td class="<?php echo $ds_td; ?> tw-whitespace-nowrap tw-text-center tw-font-bold tw-tabular-nums tw-text-slate-900"><?php echo (int) $row['thutu']; ?></td>
                                        <td class="<?php echo $ds_td; ?> tw-min-w-[330px] tw-text-left tw-font-semibold tw-text-gray-800"><?php echo htmlspecialchars($row['noidung']); ?></td>
                                        <td class="<?php echo $ds_td; ?> tw-text-slate-700">
                                            <select id="ds_ngay_tinh_han_<?php echo (int) $row['id']; ?>" class="<?php echo $ds_table_field; ?> tw-w-full tw-min-w-[200px]">
                                                <option value="ngay_vao" <?php echo ($ngay_tinh_han == 'ngay_vao') ? 'selected' : ''; ?>>Ngày vào trừ số ngày</option>
                                                <option value="ngay_vao_cong" <?php echo ($ngay_tinh_han == 'ngay_vao_cong') ? 'selected' : ''; ?>>Ngày vào cộng số ngày</option>
                                                <option value="ngay_ra" <?php echo ($ngay_tinh_han == 'ngay_ra') ? 'selected' : ''; ?>>Ngày ra cộng số ngày</option>
                                                <option value="ngay_ra_tru" <?php echo ($ngay_tinh_han == 'ngay_ra_tru') ? 'selected' : ''; ?>>Ngày ra trừ số ngày</option>
                                            </select>
                                        </td>
                                        <td class="<?php echo $ds_td; ?> tw-text-slate-700">
                                            <input type="number" id="ds_so_ngay_xuly_<?php echo (int) $row['id']; ?>" class="<?php echo $ds_table_field; ?> tw-w-[78px] tw-min-w-[78px] tw-leading-5" value="<?php echo (int) $so_ngay_xuly; ?>" min="0" max="365">
                                        </td>
                                        <td class="<?php echo $ds_td; ?> tw-text-slate-700">
                                            <select id="ds_nguoi_chiu_trachnhiem_<?php echo (int) $row['id']; ?>" class="<?php echo $ds_table_field; ?> tw-w-full tw-min-w-[90px] max768:tw-min-w-[200px] max768:tw-text-[12px]">
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
                                        <td class="<?php echo $ds_td; ?> tw-text-slate-700">
                                            <button type="button" onclick="saveDefaultSetting(<?php echo (int) $row['id']; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-default-setting tw-inline-flex tw-h-8 tw-min-h-8 tw-w-8 tw-min-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-p-0 tw-text-[13px] tw-leading-none tw-text-[#143583] tw-shadow-sm tw-transition hover:-tw-translate-y-px hover:tw-bg-[#dbeafe] hover:tw-shadow-[0_6px_12px_rgba(15,23,42,0.14)] active:tw-scale-[0.96]" title="Lưu dòng này" aria-label="Lưu cài đặt cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                <i class="fas fa-check" aria-hidden="true"></i>
                                                <span class="tw-sr-only">Lưu</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="tw-sticky tw-bottom-0 tw-z-[12] tw-flex tw-shrink-0 tw-flex-col tw-justify-end tw-gap-2 tw-border-t tw-border-slate-100 [border-top-style:solid] tw-bg-slate-50/[0.94] tw-px-[14px] tw-pb-3 tw-pt-3 tw-backdrop-blur-[8px] sm:tw-flex-row sm:tw-items-center sm:tw-justify-between max768:tw-w-full max768:tw-flex-col max768:tw-items-stretch max768:tw-gap-1.5 max768:tw-px-0 max768:tw-pb-0 max768:tw-pt-1.5">
                        <div class="tw-inline-flex tw-min-h-7 tw-items-center tw-text-[12px] tw-font-extrabold tw-text-slate-500"><?php echo (int) $default_settings_count; ?> dòng cài đặt</div>
                        <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 max768:tw-w-full max768:tw-justify-between">
                            <button type="button" onclick="closeDefaultSettingModal()" class="btn-add-criteria <?php echo $ds_action_btn; ?> tw-inline-flex tw-h-9 tw-min-h-[34px] tw-items-center tw-justify-center tw-gap-2 tw-rounded-md tw-border tw-border-solid tw-border-slate-300 tw-bg-white tw-px-3 tw-py-0 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50 hover:tw-text-slate-900 active:tw-scale-[0.96] max768:tw-min-w-[92px]">
                                <i class="fas fa-times tw-mr-2" aria-hidden="true"></i>
                                Đóng
                            </button>
                            <button type="button" onclick="saveAllDefaultSettings('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria <?php echo $ds_action_btn; ?> tw-inline-flex tw-h-9 tw-min-h-[34px] tw-items-center tw-justify-center tw-gap-2 tw-rounded-md tw-border tw-border-solid tw-border-[#143583] tw-bg-[#143583] tw-px-3 tw-py-0 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299] active:tw-scale-[0.96] max768:tw-max-w-[calc(100%-108px)] max768:tw-min-w-[min(220px,calc(100%-108px))]">
                                <i class="fas fa-floppy-disk tw-mr-2" aria-hidden="true"></i>
                                Lưu tất cả cài đặt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        // Map xưởng → danh sách line, dùng cho dropdown "Chọn line" trong modal cài đặt mặc định
        window.XUONG_LINE_MAP = <?php
        $xuong_line_map = [];
        $sql_line_map = "SELECT DISTINCT xuong, line1 FROM khsanxuat WHERE xuong != '' AND line1 != '' ORDER BY xuong, LENGTH(line1), line1";
        $result_line_map = $connect->query($sql_line_map);
        if ($result_line_map) {
            while ($row_line_map = $result_line_map->fetch_assoc()) {
                $xuong_line_map[$row_line_map['xuong']][] = $row_line_map['line1'];
            }
        }
        echo json_encode($xuong_line_map, JSON_UNESCAPED_UNICODE);
        ?>;
        </script>
