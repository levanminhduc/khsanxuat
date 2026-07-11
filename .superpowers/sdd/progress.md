# Progress: gom-dinh-nghia-nhom
base: e478ed0
Task 1: complete (commits e478ed0..d346a81, review clean)
Task 2: complete (commits d346a81..b8723ee, review clean)
  MINOR/manual-verify: browser render indexdept dept=chuanbi_sanxuat_phong_kt & dept=kho (order a-d + labels + 2 modals); kho ELSE 3->5 is order-neutral (doc note only)
Task 3: complete (commits b8723ee..549c999, review clean)
  manual-verify: dropdown thêm tiêu chí dept=chuanbi (4 opt) & dept=kho (2 opt) + test submit
Task 4: complete (commits 549c999..f86d7fa, review clean)
  final-review note: confirm getValidDepts() returns same 10 depts; confirm config.php has no session_start side-effect vs save_score.php
Task 5: complete (commit ba0e6e0, dead file removed; grep confirmed 0 refs; done inline)
Task 6: complete (commit 91bbaa8, review clean)
  fetch URL bug fixed (3 relative fetch -> absolute window.BASE_URL + encodeURIComponent); git mv api/get_tieuchi.php -> dev-tools/debug/dump_tieuchi_by_group.php (100% rename); reviewer hand-traced {success,data} envelope compat = correct
Final whole-branch review (Tasks 1-5): READY WITH FOLLOW-UPS
  - cross-cutting checks 1-3 all verified clean (dept equivalence, config.php side-effect-free, no cross-dept CASE collision)
  - Task 6 was the only follow-up; now complete + reviewed
ALL 6 TASKS COMPLETE. Commits: d346a81 b8723ee 549c999 f86d7fa ba0e6e0 91bbaa8
OUTSTANDING (manual, cannot automate): browser render check indexdept dept=chuanbi_sanxuat_phong_kt & dept=kho (group order + labels + 3 modals); 2 image-mgmt pages load criteria no 404

--- FYI: sửa ngoài phạm vi refactor (bug thật khi thêm tiêu chí) ---
BUG: form "Thêm tiêu chí" fail. Root causes (3 lớp):
  1. add_criteria.php redirect tương đối -> 404 (đã fix: dùng BASE_URL, 3 dòng)
  2. tieuchi_dept.id INT NOT NULL, KHÔNG auto_increment -> INSERT thiếu id fail (STRICT mode)
  3. khi ALTER: dòng rác id=0 gây resequence dup; zero-date trong danhgia_tieuchi chặn re-add FK
FIX DB (migration sql/migration_tieuchi_dept_id_autoincrement.sql + backup sql/backups/):
  - UPDATE id=0 -> 163 (dòng "Hoàn Thành" rác, 0 con tham chiếu)
  - ALTER tieuchi_dept MODIFY id INT AUTO_INCREMENT (next=165 sau test)
  - re-add 3 FK y nguyên (SESSION sql_mode nới NO_ZERO_DATE để qua zero-date rác)
  - verify: 3 FK OK, INSERT không id -> tự sinh 164 OK, cleanup xong
config.php: thêm 'trung_tam_btp' => 'TRUNG TÂM BTP' vào $dept_names
CHƯA COMMIT. Còn nợ: dữ liệu rác id=163 dept='Hoàn Thành', 1 dòng zero-date han_xuly; DB chưa có tiêu chí BTP; dept_statistics.php chưa có BTP

---
# Progress: user-app-role (branch feature/user-app-role)
base: c39c453ea94e5b7fc46cfb439716a850cfd84987
Task 1: complete (commits c39c453..7be8f42, review clean)
Task 2: complete (commits 7be8f42..5851a60, review clean after 1 fix)
  fix: force-add dev-tools/tests/test_auth_helper.php (gitignored dev-tools/, precedent dump_tieuchi_by_group.php)
Task 3: complete (commits 5851a60..e296489, review clean)
  verify: CLI script mo phong SQL (khong co mat khau that de login browser); login browser that deferred sang Task 9
Task 4: complete (commits e296489..98cc0c3, review clean)
  MINOR (chua fix, cho final review): pages/settings.php - comment "// Permission check" tieng Anh, nen doi tieng Viet
Task 5: complete (commits 98cc0c3..bf24d48, review clean)
  DEVIATION tu plan (user confirm): apply_default_settings.php (so nhieu) KHONG gate - duoc import.php include_once goi truc tiep, import.php khong check quyen (ngoai pham vi). Chi xoa display_errors.
  Caller thuc te KHAC brief: save_default_setting/apply_default_setting/save_all_default_settings goi tu indexdept.js (4 xhr.send, tru khong phai settings_deadline.php). save_default_settings (so nhieu) moi la caller cua settings_deadline.php.
  MINOR (chua fix, cho final review): apply_default_settings.php thieu comment giai thich vi sao khong gate -> de nham "fix" sau nay
Task 6: complete (commits bf24d48..fa7a5bd, review clean)
  DEVIATION tu brief: update_deadline_tieuchi.php co 2 caller that (indexdept.js + settings_deadline.php, 2 ham updateDeadline khac nhau) - da sua ca 2.
  batch_update_deadline.php la page+action gop 1 file (khong phai JSON) - dung requireLogin+requireFeature('page') + verifyCsrfOrDie() rotating + getCsrfInput() trong form.
  update_deadline.php + update_deadline_ajax.php: MO COI, brief claim caller la pages/theodoi.php SAI (da grep xac nhan khong co caller nao) - van gate (defense in depth), khong sua caller nao.
  INFO (khong phai loi task nay): components/settings_deadline.php hien CHUA duoc include o dau (dead code, chi co comment nhac trong add_settings_button.php) - sua CSRF o day van dung/vo hai, chi la chua co tac dung thuc te cho toi khi duoc wire vao trang.
Task 7: complete (commits fa7a5bd..757af88, review clean)
  DEVIATION tu brief: 3/6 file mode SAI trong brief (template_actions.php, delete_rows.php, add_criteria.php la REDIRECT khong phai JSON) - da sua dung theo response that.
  add_criteria.php: caller that la form full-page modal-add-criteria.php (khong phai AJAX) - dung verifyCsrfOrDie() rotating + them getCsrfInput() vao form.
  execute_update_tieuchi.php: MO COI + script migration nguy hiem (thuc thi SQL tu file cung, khong check REQUEST_METHOD) - gate requireLogin+requireFeature('page'), khong CSRF (khong co caller).
  helpers/template_files.php (canManageTemplates() stub luon true) - KHONG dung, ngoai pham vi.
Task 8: complete (commits 757af88..aa0c6bb, review clean)
  CHECKPOINT user: update_khsanxuat.php + delete_image.php -> edit_settings (admin-only), khac 4 file con lai (chi requireLogin).
  PHAT HIEN QUAN TRONG: update_nguoi_thuchien.php VA update_khsanxuat.php deu la SCRIPT MIGRATION 1 LAN MO COI (khong phai action nghiep vu dang dung nhu brief tuong) - da grep xac nhan khong co caller. Tinh nang "doi nguoi thuc hien" that nam trong save_danhgia_with_log.php.
  delete_image.php: phat hien la GET-link (khong phai POST), TRUOC TASK NAY KHONG CO BAT KY AUTH NAO (ai cung xoa duoc anh neu biet id). Dung CSRF qua $_GET (khong rotate, vi 1 trang co nhieu link xoa dung chung 1 token) - pattern moi chua tung dung o task truoc.
  Git index bi trong bat thuong giua luc implementer lam viec - da tu xu ly an toan bang git reset (khong --hard), verify qua reflog khong mat commit nao.
Task 9: complete (grep sweep only, base aa0c6bb, no new commit)
  Grep sweep: access_allowed 0 ket qua (sach). SESSION['user_role'] chi con o dev-tools/backups/ (dung ngoai le du kien). display_errors,1 con 6 trang (edit_date.php, theodoi.php, factory_templates.php, edit_date_clone.php, download_all_files.php, import_date_display.php) - KHONG file nao thuoc pham vi 9 task da sua, ngoai pham vi plan nay (co san tu truoc).
  Ma tran 3 vai + test app='*': user QUYET DINH BO QUA test browser thu cong, tin tuong dua tren code review (tat ca 8 task truoc deu duoc reviewer approve sach/gan sach).
ALL 9 TASKS COMPLETE tren branch feature/user-app-role. Commits: 7be8f42 5851a60 e296489 98cc0c3 bf24d48 fa7a5bd 757af88 aa0c6bb
FINAL WHOLE-BRANCH REVIEW (opus, base c39c453..aa0c6bb): "With fixes" - phat hien 2 lo hong THAT ngoai 9 file cua plan (cung loai loi):
  - CRITICAL: pages/required_images_criteria.php hoan toan khong co auth/CSRF (insert/delete required_images_criteria qua 2 form POST).
  - IMPORTANT: pages/file_templates.php chi dua vao stub canManageTemplates() luon true (khong co requireLogin/requireFeature that).
  Da fix ca 2, commit df2b91c, re-review approved sach.
  Minor (ghi nhan, KHONG fix - ngoai pham vi/rui ro thap): $is_admin=true dead code trong image_handler.php; indexdept.php:34 co san $_SESSION['username']==='admin' hardcode (truoc plan nay, khong dung); 5 script migration mo coi (update_deadline.php, update_deadline_ajax.php, execute_update_tieuchi.php, update_khsanxuat.php, update_nguoi_thuchien.php) da gate nhung khuyen nghi xoa han sau nay; login_action.php khong check mysqli_prepare() that bai.
BRANCH feature/user-app-role SAN SANG MERGE (sau fix). Commits: 7be8f42 5851a60 e296489 98cc0c3 bf24d48 fa7a5bd 757af88 aa0c6bb df2b91c
DISCOVERY (git status truoc khi ket thuc): actions/update_deadline_all.php + 1 dong indexdept.js bi bo sot, chua commit tu 1 subagent truoc do (co the la Task 6, tu grep JS ma khong duoc yeu cau add file nay). Da doc toan bo + dispatch reviewer doc lap kiem tra 8 diem bao mat -> an toan, commit c1bc3eb.
  git clean -n xac nhan khong con file PHP mo coi nao khac (chi con thu muc gitignore quen thuoc).

---
# Progress: logout-button-header (branch feature/user-app-role)
base: c1bc3eb5e76bd4b3cdc32737a66fa6158694602f
Task 1: complete (commit c1bc3eb..2caf54f, review clean)
Task 2: complete (commits 2caf54f..8c2342c, review clean)
  MINOR (khong fix, cho final review): header.php:76 return $initials!=='' ? ... : 'USER' la dead code (words da non-empty do guard truoc) - co the rut gon con `return $initials;`; $map khoi tao lai moi lan goi (khong static) - khong dang ngai.
Task 3: complete (commits 8c2342c..cd6b731, review clean)
  MINOR (khong fix): escaping style khac voi $actions/$mobile_actions cu (assign-time vs echo-time) - dung theo dung template brief, khong phai loi; dev-tools/test_header_render.php (local, khong commit) thieu needle rieng cho mobile logged_out row - chi la ho hong test script local, code that da verify dung qua file read.
Task 4: complete (commits cd6b731..9cccc87, review clean)
  reviewer hand-trace: 3-dieu kien conjunction dung ===/!==, khong loose-compare bug; 5 adversarial input deu dung (//evil, https://, backslash, bare-no-slash, hop le). MINOR (khong fix): redirect_url cu khong bi clear khi request sau khong co/co redirect invalid - dung y brief "khong them hanh vi ngoai storing".
Task 5: complete (commits 9cccc87..b004941, review clean)
Task 6b (tweak ngoai plan, user yeu cau sau preview): gop badge+icon logout thanh 1 avatar tron LVMD, ca desktop+mobile.
  commits b004941..b674556 (feat) + b674556..64ea899 (fix aria-label).
  review 1: Approved, 1 Important (plan-mandated tu brief cua toi): desktop <a> mat accessible-name (text "LVMD" de len tren title) -> screen reader doc sai.
  fix: them aria-label="$user_display_name — Đăng xuất" (khong doi visual/CSS). Re-review: Approved, xac nhan dung accname spec (aria-label > text-content > title).
  MINOR (khong fix): mobile badge 24px khong bi thu nho o breakpoint <=639.98px (rule cu chi target <img>, span moi khong khop) - dong nhat voi .mobile-nav-item-icon (font-icon) da co san, khong phai regression moi.

---
# Progress: account-cleanup (branch feature/user-app-role)
base: 26018656a628c857cce88e816d7a12aba333895f
Task 1: complete (commits 2601865..44bfb01, review clean)
Task 2: complete (commits 44bfb01..c3cfd40, review clean)
  MINOR (khong fix, cho final review): thu tu validate trong change_password_action (match-check chay truoc min-8) - chi la UX message, khong phai loi
Task 3: complete (commits c3cfd40..d4fe999, review clean)
  MINOR pre-existing (ngoai pham vi, ghi nhan): requireFeature mode redirect gui ?error=<msg> nhung index.php:217 doi ?error=1&message=... -> user khong thay message; loi co san o 5+ call site khac, khong phai regression
Task 4: complete (commits d4fe999..36ec773, review clean)
  MINOR (khong fix): comment label trong account.css lay wording cua register.php thay vi login.php (chi la text comment); header comment account.css khong theo style /** */ tieng Anh nhu header.css/form-page.css
FINAL WHOLE-BRANCH REVIEW (opus, 2601865..36ec773): READY TO MERGE - Yes. 0 Critical/Important.
  Minor triage (khong blocker): (1) requireLogin luu redirect_url cua POST action -> sau login GET vao action bi 403 CSRF tho (chi cham duoc khi POST truc tiep luc logout, van an toan hon hien trang; fix goi y: skip luu redirect khi REQUEST_METHOD=POST); (2) requireFeature redirect ?error=<msg> vs index.php doi ?error=1&message= (PRE-EXISTING 5+ call site, nen fix rieng); (3) change_password chua co entry point UI - follow-up them link header; (4) thu tu validate match-check truoc min-8 (UX); (5) two-tab login tab 2 dinh 403 do rotate token (chap nhan voi he noi bo).
ALL 4 TASKS COMPLETE. Commits: 44bfb01 c3cfd40 d4fe999 36ec773

# Progress: account-minors (branch feature/user-app-role), base: 6e8cf47
Task 1: complete (commits 6e8cf47..c7776ae, review clean) - fix requireFeature redirect error=1&message= + index.php uu tien message
Task 2: complete (commits c7776ae..30da0fd, review clean) - avatar dropdown (ten + Doi mat khau + Dang xuat) desktop, them item Doi mat khau mobile menu
FINAL REVIEW account-minors (opus, 6e8cf47..30da0fd): READY TO MERGE - Yes. 0 Critical/Important. Minors ghi nhan: (1) index.php?error=1&message= gio hien text tuy y (da escape, impact thap); (2) message[] dang array -> htmlspecialchars warning PHP7.4 (pattern co san, co the phong thu is_string). BOTH TASKS COMPLETE. Commits: c7776ae 30da0fd
