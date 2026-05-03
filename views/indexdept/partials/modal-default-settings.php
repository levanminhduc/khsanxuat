        <!-- Modal cài đặt hạn xử lý mặc định -->
        <div id="defaultSettingModal" class="modal default-settings-modal score-options-modal indexdept-modern-modal tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-4 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="default-setting-title" hidden>
            <div class="modal-content default-settings-modal__content score-options-modal__content tw-relative tw-m-0 tw-flex tw-max-h-[92vh] tw-w-full tw-max-w-6xl tw-flex-col tw-overflow-hidden tw-rounded-2xl tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft">
                <button type="button" class="close default-settings-modal__close tw-absolute tw-right-4 tw-top-4 tw-z-20 tw-inline-flex tw-h-10 tw-w-10 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-slate-200 tw-bg-white/90 tw-text-2xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeDefaultSettingModal()" aria-label="Đóng cài đặt mặc định">&times;</button>

                <div class="default-settings-modal__header score-options-modal__header tw-border-b tw-border-slate-200 tw-bg-slate-50 tw-px-6 tw-py-5">
                    <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                        <div class="score-options-modal__heading tw-flex tw-min-w-0 tw-items-start tw-gap-4">
                            <span class="score-options-modal__icon tw-inline-flex tw-h-12 tw-w-12 tw-flex-none tw-items-center tw-justify-center tw-rounded-2xl tw-bg-[#143583] tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                                <i class="fas fa-cog"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <span class="score-options-modal__eyebrow tw-text-xs tw-font-bold tw-uppercase tw-tracking-[0.18em] tw-text-[#143583]">Mặc định</span>
                                <h3 id="default-setting-title" class="modal-title default-settings-modal__title score-options-modal__title tw-m-0 tw-mt-1 tw-pr-12 tw-text-2xl tw-font-bold tw-leading-tight tw-text-slate-950">
                                    Cài đặt hạn xử lý
                                </h3>
                                <span class="tw-mt-2 tw-inline-flex tw-max-w-full tw-items-center tw-rounded-full tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-3 tw-py-1 tw-text-sm tw-font-semibold tw-text-slate-700">
                                    Xưởng: <span id="xuong_display_name" class="tw-ml-1 tw-text-[#143583]">Tất cả xưởng</span>
                                </span>
                            </div>
                        </div>
                        <span class="score-options-modal__dept tw-mr-12 tw-hidden tw-max-w-xs tw-truncate tw-rounded-full tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-3 tw-py-1 tw-text-sm tw-font-semibold tw-text-slate-700 sm:tw-inline-flex" title="<?php echo htmlspecialchars($dept_display_name); ?>">
                            <?php echo htmlspecialchars($dept_display_name); ?>
                        </span>
                    </div>
                </div>

                <div id="default_settings_status" class="default-settings-modal__status tw-mx-6 tw-mt-4" hidden></div>
                <input type="hidden" id="current_dept" value="<?php echo htmlspecialchars($dept); ?>">

                <div class="default-settings-modal__body score-options-modal__body tw-flex tw-min-h-0 tw-flex-1 tw-flex-col tw-gap-4 tw-px-6 tw-py-5">
                    <div class="default-settings-modal__toolbar default-settings-modal__toolbar--top tw-flex tw-flex-col tw-gap-4 tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-slate-50 tw-p-4 lg:tw-flex-row lg:tw-items-end lg:tw-justify-between">
                        <div class="default-settings-modal__toolbar-left tw-flex tw-flex-col tw-gap-4 lg:tw-flex-row lg:tw-items-end">
                            <div class="default-settings-modal__xuong-filter tw-min-w-[240px]">
                                <label for="selected_xuong" class="default-settings-modal__label tw-mb-2 tw-block tw-text-sm tw-font-bold tw-text-slate-700">Chọn Xưởng</label>
                                <select id="selected_xuong" class="form-control default-settings-modal__select-xuong tw-h-11 tw-rounded-xl tw-border-slate-300 tw-bg-white tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15" onchange="changeSelectedXuong()">
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

                            <div class="default-settings-modal__actions tw-flex tw-flex-wrap tw-gap-2">
                                <button type="button" onclick="saveAllDefaultSettings('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--primary default-settings-modal__btn default-settings-modal__btn--primary tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-[#143583] tw-bg-[#143583] tw-px-4 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299]">
                                    <i class="fas fa-floppy-disk tw-mr-2" aria-hidden="true"></i>
                                    Lưu tất cả
                                </button>
                                <button type="button" onclick="openStaffModal('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary default-settings-modal__btn default-settings-modal__btn--info tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-px-4 tw-text-sm tw-font-bold tw-text-[#143583] tw-shadow-sm tw-transition hover:tw-bg-[#dbeafe]">
                                    <i class="fas fa-users tw-mr-2" aria-hidden="true"></i>
                                    Người thực hiện
                                </button>
                            </div>
                        </div>

                        <button type="button" onclick="closeDefaultSettingModal()" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary default-settings-modal__btn default-settings-modal__btn--secondary tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-slate-300 tw-bg-white tw-px-4 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50">Đóng</button>
                    </div>

                    <div class="table-container default-settings-modal__table-wrap score-options-modal__table-wrap tw-min-h-0 tw-flex-1 tw-overflow-auto tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-white">
                        <table class="evaluation-table default-settings-modal__table tw-w-full tw-border-collapse tw-text-sm">
                            <thead class="tw-sticky tw-top-0 tw-z-10">
                                <tr class="tw-bg-slate-100 tw-text-left tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-slate-600">
                                    <th class="default-settings-modal__col default-settings-modal__col--stt tw-px-4 tw-py-3">STT</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--criteria tw-px-4 tw-py-3">Tiêu chí đánh giá</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--type tw-px-4 tw-py-3">Loại tính hạn</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--days tw-px-4 tw-py-3">Số ngày</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--owner tw-px-4 tw-py-3">Người chịu trách nhiệm</th>
                                    <th class="default-settings-modal__col default-settings-modal__col--actions tw-px-4 tw-py-3">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="default_settings_tbody" class="tw-divide-y tw-divide-slate-100">
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
                                            <td colspan="6" class="default-settings-modal__group-row tw-bg-slate-900 tw-px-4 tw-py-2 tw-text-left tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-slate-700">
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
                                    <tr id="ds_row_<?php echo (int) $row['id']; ?>" class="tw-bg-white tw-transition hover:tw-bg-[#eef5ff]/60">
                                        <td class="tw-whitespace-nowrap tw-px-4 tw-py-3 tw-font-bold tw-text-slate-700"><?php echo (int) $row['thutu']; ?></td>
                                        <td class="text-left tw-min-w-[260px] tw-px-4 tw-py-3 tw-leading-5 tw-text-slate-800"><?php echo htmlspecialchars($row['noidung']); ?></td>
                                        <td class="tw-px-4 tw-py-3">
                                            <select id="ds_ngay_tinh_han_<?php echo (int) $row['id']; ?>" class="form-control tw-h-10 tw-min-w-[180px] tw-rounded-lg tw-border-slate-300 tw-bg-white tw-text-sm tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15">
                                                <option value="ngay_vao" <?php echo ($ngay_tinh_han == 'ngay_vao') ? 'selected' : ''; ?>>Ngày vào trừ số ngày</option>
                                                <option value="ngay_vao_cong" <?php echo ($ngay_tinh_han == 'ngay_vao_cong') ? 'selected' : ''; ?>>Ngày vào cộng số ngày</option>
                                                <option value="ngay_ra" <?php echo ($ngay_tinh_han == 'ngay_ra') ? 'selected' : ''; ?>>Ngày ra cộng số ngày</option>
                                                <option value="ngay_ra_tru" <?php echo ($ngay_tinh_han == 'ngay_ra_tru') ? 'selected' : ''; ?>>Ngày ra trừ số ngày</option>
                                            </select>
                                        </td>
                                        <td class="tw-px-4 tw-py-3">
                                            <input type="number" id="ds_so_ngay_xuly_<?php echo (int) $row['id']; ?>" class="form-control tw-h-10 tw-w-24 tw-rounded-lg tw-border-slate-300 tw-bg-white tw-text-sm tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15" value="<?php echo (int) $so_ngay_xuly; ?>" min="0" max="365">
                                        </td>
                                        <td class="tw-px-4 tw-py-3">
                                            <select id="ds_nguoi_chiu_trachnhiem_<?php echo (int) $row['id']; ?>" class="form-control default-settings-modal__owner-select tw-h-10 tw-min-w-[220px] tw-rounded-lg tw-border-slate-300 tw-bg-white tw-text-sm tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15">
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
                                        <td class="tw-px-4 tw-py-3">
                                            <button type="button" onclick="saveDefaultSetting(<?php echo (int) $row['id']; ?>, '<?php echo htmlspecialchars($dept); ?>')" class="btn-default-setting score-options-modal__row-btn score-options-modal__row-btn--save tw-inline-flex tw-h-9 tw-w-9 tw-items-center tw-justify-center tw-rounded-lg tw-border tw-border-[#b8cdf4] tw-bg-[#eef5ff] tw-text-[#143583] tw-shadow-sm tw-transition hover:tw-bg-[#dbeafe]" title="Lưu dòng này" aria-label="Lưu cài đặt cho tiêu chí <?php echo (int) $row['thutu']; ?>">
                                                <i class="fas fa-check" aria-hidden="true"></i>
                                                <span class="score-options-modal__sr-only">Lưu</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="default-settings-modal__toolbar default-settings-modal__toolbar--bottom tw-flex tw-flex-col-reverse tw-gap-3 tw-border-t tw-border-slate-100 tw-pt-4 sm:tw-flex-row sm:tw-justify-end">
                        <button type="button" onclick="closeDefaultSettingModal()" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary default-settings-modal__btn default-settings-modal__btn--secondary tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-slate-300 tw-bg-white tw-px-5 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50">Đóng</button>
                        <button type="button" onclick="saveAllDefaultSettings('<?php echo htmlspecialchars($dept); ?>')" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--primary default-settings-modal__btn default-settings-modal__btn--primary tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-[#143583] tw-bg-[#143583] tw-px-5 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299]">
                            <i class="fas fa-floppy-disk tw-mr-2" aria-hidden="true"></i>
                            Lưu tất cả cài đặt
                        </button>
                    </div>
                </div>
            </div>
        </div>
