    <!-- Modal Quản lý người thực hiện -->
    <?php
    // Class chung cho 4 ô tiêu đề bảng nhân sự; giữ font Arial như bảng đánh giá
    $staff_th = 'tw-sticky tw-top-0 tw-z-10 tw-box-border tw-border tw-border-solid tw-border-[#ddd] tw-border-b-[#0f2861] tw-bg-[#143583] tw-px-2 tw-py-[7px] tw-text-left tw-font-[Arial,Helvetica,sans-serif] tw-text-xs tw-font-bold tw-normal-case tw-leading-[1.25] tw-tracking-normal tw-text-slate-50 tw-shadow-[0_1px_0_rgba(0,0,0,0.1)] tw-break-words';
    ?>
    <div id="staffModal" class="modal staff-modal score-options-modal indexdept-modern-modal [font-family:Arial,Helvetica,sans-serif] tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-3 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="staff-modal-title" hidden>
        <div class="modal-content tw-relative tw-m-0 tw-flex tw-max-h-[92vh] tw-w-full tw-max-w-5xl tw-flex-col tw-overflow-hidden tw-rounded-[10px] tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft max768:tw-mx-auto max768:tw-my-[3vh] max768:tw-w-[96vw] max768:tw-max-w-[96vw]">
            <button type="button" class="close tw-absolute tw-right-3 tw-top-3 tw-z-20 tw-inline-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-white/90 tw-text-xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:-tw-translate-y-px hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeStaffModal()" aria-label="Đóng quản lý người chịu trách nhiệm">&times;</button>

            <div class="tw-flex tw-items-center tw-justify-between tw-gap-4 tw-border-b [border-bottom-style:solid] max768:tw-flex-col max768:tw-items-start max768:tw-gap-2.5 tw-border-slate-200 tw-bg-slate-50 tw-px-4 tw-py-3">
                <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                    <div class="tw-flex tw-min-w-0 tw-items-start tw-gap-3 max768:tw-pr-10">
                        <span class="tw-inline-flex tw-h-9 tw-w-9 tw-flex-none tw-items-center tw-justify-center tw-rounded-md tw-border tw-border-solid tw-border-[#0f2861] tw-bg-[#143583] tw-text-[15px] tw-text-white max768:tw-text-[16px] tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                            <i class="fas fa-users"></i>
                        </span>
                        <div class="tw-min-w-0">
                            <span class="tw-mb-0.5 tw-block tw-text-xs tw-font-bold tw-text-[#143583]">Nhân sự</span>
                            <h3 id="staff-modal-title" class="modal-title tw-m-0 tw-text-balance tw-pr-10 tw-text-xl tw-font-bold tw-leading-tight tw-text-slate-950">
                                Người chịu trách nhiệm
                            </h3>
                        </div>
                    </div>
                    <span class="tw-mr-10 tw-hidden tw-min-h-7 tw-max-w-xs tw-items-center tw-truncate tw-rounded-md tw-border tw-border-solid tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-2 tw-py-1 tw-text-right tw-text-xs tw-font-semibold tw-text-slate-700 tw-shadow-[0_1px_2px_rgba(15,23,42,0.06)] sm:tw-inline-flex max768:tw-w-full max768:tw-justify-start max768:tw-text-left">
                        <span id="dept_display_name"></span>
                    </span>
                </div>
            </div>

            <div id="staff_status" class="score-options-modal__status tw-mx-4 tw-mt-2 tw-rounded-md tw-px-[9px] tw-py-[7px] tw-text-center" hidden></div>
            <input type="hidden" id="current_staff_dept" value="">

            <div class="tw-flex tw-min-h-0 tw-flex-1 tw-flex-col tw-gap-3 tw-bg-slate-50 tw-px-4 tw-py-3 max768:tw-gap-1.5 max768:tw-p-1.5">
                <div class="tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-slate-50 tw-p-3">
                    <h4 class="tw-m-0 tw-mb-2 tw-text-sm tw-font-bold tw-text-slate-800">Thêm người chịu trách nhiệm</h4>
                    <div class="tw-grid tw-grid-cols-1 tw-items-center tw-gap-2 md:tw-grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                        <input type="text" id="new_staff_name" class="form-control staff-modal__input tw-h-9 tw-rounded-md tw-border-slate-300 tw-bg-white tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-2 focus:tw-ring-[#143583]/15" placeholder="Tên người chịu trách nhiệm">
                        <input type="text" id="new_staff_position" class="form-control staff-modal__input tw-h-9 tw-rounded-md tw-border-slate-300 tw-bg-white tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-2 focus:tw-ring-[#143583]/15" placeholder="Chức vụ (không bắt buộc)">
                        <button type="button" onclick="addNewStaff()" class="btn-add-criteria tw-inline-flex tw-h-9 tw-min-h-[34px] tw-min-w-[76px] tw-items-center tw-justify-center tw-gap-2 tw-rounded-md tw-border tw-border-solid tw-border-[#143583] tw-bg-[#143583] tw-px-3 tw-py-0 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299] active:tw-scale-[0.96] max768:tw-mr-0 max768:tw-flex-1">
                            <i class="fas fa-plus tw-mr-2" aria-hidden="true"></i>
                            Thêm
                        </button>
                    </div>
                </div>

                <div class="tw-mx-3 tw-mt-3 tw-max-h-[calc(92vh-158px)] tw-min-h-0 tw-flex-1 tw-overflow-auto tw-rounded-md tw-border tw-border-solid tw-border-slate-200 tw-bg-white tw-shadow-[inset_0_1px_0_rgba(255,255,255,0.85)] max768:tw-m-0 max768:tw-max-h-[calc(94vh-140px)]">
                    <table class="staff-modal__table tw-mb-5 tw-w-full tw-min-w-full tw-table-fixed tw-border-collapse tw-border-spacing-0 tw-text-sm max768:tw-min-w-[640px]">
                        <thead class="tw-sticky tw-top-0 tw-z-10">
                            <tr class="tw-bg-slate-100 tw-text-left tw-text-xs tw-font-bold tw-tracking-wide tw-text-slate-600">
                                <th class="<?php echo $staff_th; ?> tw-w-12 tw-min-w-[48px]">STT</th>
                                <th class="<?php echo $staff_th; ?> tw-w-[36%]">Tên</th>
                                <th class="<?php echo $staff_th; ?> tw-w-[30%]">Chức vụ</th>
                                <th class="<?php echo $staff_th; ?> tw-w-[154px] tw-min-w-[154px]">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="staff_tbody" class="tw-divide-y tw-divide-slate-100">
                            <!-- Danh sách người thực hiện sẽ được load bằng JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="tw-flex tw-flex-col tw-justify-end tw-gap-2 tw-border-t tw-border-slate-100 tw-pt-3 [border-top-style:solid] sm:tw-flex-row sm:tw-items-center sm:tw-justify-between">
                    <span id="staff_count_label" class="score-options-modal__footer-meta tw-inline-flex tw-min-h-7 tw-items-center tw-text-[12px] tw-font-extrabold">0 người chịu trách nhiệm</span>
                    <button type="button" onclick="closeStaffModal()" class="btn-add-criteria tw-inline-flex tw-h-9 tw-min-h-[34px] tw-items-center tw-justify-center tw-gap-2 tw-rounded-md tw-border tw-border-solid tw-border-slate-300 tw-bg-white tw-px-3 tw-py-0 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50 hover:tw-text-slate-900 active:tw-scale-[0.96] max768:tw-mr-0 max768:tw-w-full max768:tw-flex-1">Đóng</button>
                </div>
            </div>
        </div>
    </div>
