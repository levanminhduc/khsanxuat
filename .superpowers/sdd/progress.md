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
