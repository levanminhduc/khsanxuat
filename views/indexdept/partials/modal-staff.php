    <!-- Modal Quản lý người thực hiện -->
    <div id="staffModal" class="modal staff-modal score-options-modal indexdept-modern-modal tw-fixed tw-inset-0 tw-z-[10000] tw-hidden tw-items-center tw-justify-center tw-overflow-y-auto tw-bg-slate-950/55 tw-p-4 tw-backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="staff-modal-title" hidden>
        <div class="modal-content staff-modal__content score-options-modal__content tw-relative tw-m-0 tw-flex tw-max-h-[92vh] tw-w-full tw-max-w-4xl tw-flex-col tw-overflow-hidden tw-rounded-2xl tw-border tw-border-white/70 tw-bg-white tw-p-0 tw-shadow-modal-soft">
            <button type="button" class="close staff-modal__close tw-absolute tw-right-4 tw-top-4 tw-z-20 tw-inline-flex tw-h-10 tw-w-10 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-slate-200 tw-bg-white/90 tw-text-2xl tw-leading-none tw-text-slate-500 tw-shadow-sm tw-transition hover:tw-bg-slate-100 hover:tw-text-slate-900" onclick="closeStaffModal()" aria-label="Đóng quản lý người chịu trách nhiệm">&times;</button>

            <div class="staff-modal__header score-options-modal__header tw-border-b tw-border-slate-200 tw-bg-slate-50 tw-px-6 tw-py-5">
                <div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
                    <div class="score-options-modal__heading tw-flex tw-min-w-0 tw-items-start tw-gap-4">
                        <span class="score-options-modal__icon tw-inline-flex tw-h-12 tw-w-12 tw-flex-none tw-items-center tw-justify-center tw-rounded-2xl tw-bg-[#143583] tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20" aria-hidden="true">
                            <i class="fas fa-users"></i>
                        </span>
                        <div class="tw-min-w-0">
                            <span class="score-options-modal__eyebrow tw-text-xs tw-font-bold tw-uppercase tw-tracking-[0.18em] tw-text-[#143583]">Nhân sự</span>
                            <h3 id="staff-modal-title" class="modal-title staff-modal__title score-options-modal__title tw-m-0 tw-mt-1 tw-pr-12 tw-text-2xl tw-font-bold tw-leading-tight tw-text-slate-950">
                                Người chịu trách nhiệm
                            </h3>
                        </div>
                    </div>
                    <span class="score-options-modal__dept tw-mr-12 tw-hidden tw-max-w-xs tw-truncate tw-rounded-full tw-border tw-border-[#d7e4fb] tw-bg-[#eef5ff] tw-px-3 tw-py-1 tw-text-sm tw-font-semibold tw-text-slate-700 sm:tw-inline-flex">
                        <span id="dept_display_name"></span>
                    </span>
                </div>
            </div>

            <div id="staff_status" class="staff-modal__status tw-mx-6 tw-mt-4" hidden></div>
            <input type="hidden" id="current_staff_dept" value="">

            <div class="staff-modal__body score-options-modal__body tw-flex tw-min-h-0 tw-flex-1 tw-flex-col tw-gap-4 tw-px-6 tw-py-5">
                <div class="staff-modal__form-card tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-slate-50 tw-p-4">
                    <h4 class="staff-modal__form-title tw-m-0 tw-mb-3 tw-text-sm tw-font-bold tw-text-slate-800">Thêm người chịu trách nhiệm</h4>
                    <div class="staff-modal__form-row tw-grid tw-grid-cols-1 tw-gap-3 md:tw-grid-cols-[1fr_1fr_auto]">
                        <input type="text" id="new_staff_name" class="form-control staff-modal__input tw-h-11 tw-rounded-xl tw-border-slate-300 tw-bg-white tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15" placeholder="Tên người chịu trách nhiệm">
                        <input type="text" id="new_staff_position" class="form-control staff-modal__input tw-h-11 tw-rounded-xl tw-border-slate-300 tw-bg-white tw-text-slate-900 tw-shadow-sm focus:tw-border-[#143583] focus:tw-ring-4 focus:tw-ring-[#143583]/15" placeholder="Chức vụ (không bắt buộc)">
                        <button type="button" onclick="addNewStaff()" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--primary staff-modal__btn-add tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-[#143583] tw-bg-[#143583] tw-px-5 tw-text-sm tw-font-bold tw-text-white tw-shadow-lg tw-shadow-[#0f2861]/20 tw-transition hover:tw-bg-[#1a4299]">
                            <i class="fas fa-plus tw-mr-2" aria-hidden="true"></i>
                            Thêm
                        </button>
                    </div>
                </div>

                <div class="table-container staff-modal__table-wrap score-options-modal__table-wrap tw-min-h-0 tw-flex-1 tw-overflow-auto tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-white">
                    <table class="evaluation-table staff-modal__table tw-w-full tw-border-collapse tw-text-sm">
                        <thead class="tw-sticky tw-top-0 tw-z-10">
                            <tr class="tw-bg-slate-100 tw-text-left tw-text-xs tw-font-bold tw-uppercase tw-tracking-wide tw-text-slate-600">
                                <th class="staff-modal__col staff-modal__col--stt tw-px-4 tw-py-3">STT</th>
                                <th class="staff-modal__col staff-modal__col--name tw-px-4 tw-py-3">Tên</th>
                                <th class="staff-modal__col staff-modal__col--position tw-px-4 tw-py-3">Chức vụ</th>
                                <th class="staff-modal__col staff-modal__col--actions tw-px-4 tw-py-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="staff_tbody" class="tw-divide-y tw-divide-slate-100">
                            <!-- Danh sách người thực hiện sẽ được load bằng JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="staff-modal__footer tw-flex tw-justify-end tw-border-t tw-border-slate-100 tw-pt-4">
                    <button type="button" onclick="closeStaffModal()" class="btn-add-criteria score-options-modal__btn score-options-modal__btn--secondary tw-inline-flex tw-h-11 tw-items-center tw-justify-center tw-rounded-xl tw-border tw-border-slate-300 tw-bg-white tw-px-5 tw-text-sm tw-font-bold tw-text-slate-700 tw-shadow-sm tw-transition hover:tw-bg-slate-50">Đóng</button>
                </div>
            </div>
        </div>
    </div>
