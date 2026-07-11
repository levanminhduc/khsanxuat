# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

When asked about the codebase, project structure, or to find code, always use the context-engine MCP tool (codebase-retrieval) in the root workspace first before reading individual files. Use `codebase-retrieval` instead of the Explore subagent for codebase exploration and search tasks.

When you need to read a specific file but don't know the exact line range, use the file-retrieval MCP tool instead of reading the entire file. Describe what information you need and it returns only the relevant snippets with line numbers. Use the Read tool with the returned line ranges (expanded as needed) to get current content before making edits.

## Project Context

**khsanxuat** — Hệ thống đánh giá sản xuất nhà máy.

- **Stack**: PHP 7.4+ thuần (không framework), MySQL 5.7+ (mysqli), HTML/CSS/JS vanilla, Apache via Laragon.
- **Mục đích**: Theo dõi đơn hàng qua các bộ phận sản xuất (kế hoạch → kỹ thuật/chuẩn bị sản xuất → kho → cắt → trung tâm BTP → ép keo → cơ điện → chuyền may → KCS → ủi thành phẩm → hoàn thành), đánh giá tiêu chí, quản lý hạn xử lý, upload hình ảnh minh hoạ. Nguồn duy nhất định nghĩa danh sách bộ phận và nhóm: `includes/indexdept/config.php` (`$dept_names`, `$dept_nhom_config`); mọi nơi khác derive qua helper `getValidDepts()`, `getDeptDisplayName()`, `getNhomDisplayName()`, `getNhomOrderByCase()`.
- **Trang chính**: `index.php` (dashboard), `indexdept.php` (chi tiết bộ phận), `pages/theodoi.php`, `pages/settings.php`, `actions/save_danhgia_with_log.php`, `pages/import.php`, `pages/dept_statistics.php`.
- **Modules**: `config/`, `pages/`, `actions/`, `api/`, `helpers/`, `sql/`, `includes/index/`, `includes/indexdept/`, `includes/security/` (csrf-helper.php, auth-helper.php), `components/`, `views/`, `assets/` (css/js), `account/`, `dev-tools/` (script backup/check/debug/migration — không thuộc runtime).
- **DB connection**: `config/database.php` (1 nguồn `$connect`, nạp qua `bootstrap.php`; Laragon mặc định: `localhost`/`root`/blank/`mysqli`). Link/redirect/form qua hằng `BASE_URL` (`config/app.php`); JS fetch qua `window.BASE_URL`.

## Language

- Code, commits, technical terms: tiếng Anh.
- Giải thích, báo cáo, trao đổi với user: **tiếng Việt**.

## Code Style

- **File naming**: PHP script trong `pages/`, `actions/`, `api/`, `helpers/`, `includes/` dùng snake_case, tên dài mô tả rõ ý (vd: `save_danhgia_with_log.php`, `get_tieuchi_list.php`). Partial UI trong `components/`, `views/` dùng kebab-case (vd: `month-filter.php`, `modal-add-criteria.php`).
- **PHP functions**: camelCase (`checkDeptStatus`, `getEarliestDeadline`).
- **PHP variables**: snake_case (`$id_sanxuat`, `$search_value`).
- **Constants**: UPPER_SNAKE_CASE (`DB_SERVER`, `DB_NAME`).
- **DB**: luôn dùng `mysqli` prepared statements + bind params.

## Architecture layers (sau tái cấu trúc 2026-06)

- `index.php`, `indexdept.php` — entry point GIỮ URL ở root.
- `bootstrap.php` — define BASE_PATH, nạp config/app.php + config/database.php.
- `config/` — database.php ($connect), app.php (BASE_URL, error config).
- `pages/` — trang giao diện (URL: /khsanxuat/pages/xxx.php).
- `actions/` — handler ghi (POST: save/update/delete/add).
- `api/` — handler đọc (GET/AJAX trả data).
- `helpers/` — lib dùng chung (activity_logger, admin_menu, download_token, template_files).
- `includes/` — config+function theo area (`index/`, `indexdept/`), `security/csrf-helper.php`, check_tieuchi_image, display_deadline. `includes/indexdept/config.php` là nguồn duy nhất định nghĩa `$dept_names` + `$dept_nhom_config` và các helper dept/nhom.
- `components/`, `views/` — UI partial + template (`views/indexdept/page.php` + `partials/`).
- `assets/` — css/js theo area (`css/index`, `css/indexdept`, `js/indexdept`).
- `account/` — auth (login/register/password + `*_action.php`).
- `sql/` — script SQL (migration, backup).
- `dev-tools/` — script phụ trợ backup/check/debug/fixes/migrations (không thuộc runtime).

Đường dẫn: include qua `BASE_PATH`/`__DIR__`; link/redirect/form qua `BASE_URL`;
JS fetch qua `window.BASE_URL`.

## Phân quyền (Authorization)

Hệ per-app, dùng bảng `user_app_role (user_id, app, role, granted_by, created_at)` — bảng này **dùng chung ~10 dự án** login qua cùng bảng `user`, scope theo cột `app` để mỗi dự án độc lập. Không FK tới `user.id`. Cột `user.role` cũ (admin/quan_doc/to_truong/bao_ve) là của dự án khác (`nhansuhtdb`) — KHÔNG đụng.

- **Login** (`account/login_action.php`): sau khi xác thực, query `user_app_role WHERE user_id = ? AND app IN ('khsanxuat', '*')`, nạp 1 lần vào `$_SESSION['app_role']` (`'admin'` | `'super_admin'` | `null`). Dòng `app='*'` thắng dòng `app='khsanxuat'` (quyền cross-app, dành cho trang tổng cấp quyền sau này — chưa xây). Thu hồi/cấp quyền có hiệu lực khi login lại (không query lại mỗi request).
- **Không có dòng trong `user_app_role`** = user thường: login được, đánh giá tiêu chí được, không sửa cấu hình. Đây là lệch chuẩn có chủ đích so với default-deny (login đã là rào đầu tiên của hệ nội bộ).
- **Helper** `includes/security/auth-helper.php` (nạp SAU `bootstrap.php`):
  - `isLoggedIn()`, `currentAppRole()`, `userCan($feature)` — đọc, không side-effect.
  - `requireLogin()` — chưa login → lưu `$_SESSION['redirect_url']`, redirect `account/login.php`, exit.
  - `requireFeature($feature, $mode)` — `$mode` là `'json'|'redirect'|'page'`, khớp kiểu response thật của endpoint (không phải cứ AJAX là `'json'`, cứ POST là `'redirect'` — luôn đọc code để xác nhận trước khi chọn mode).
  - Map role→feature nằm trong `$GLOBALS['app_role_features']` (đầu file `auth-helper.php`) — **CẤM** viết `role === 'admin'` rải rác ở call-site, luôn gọi `requireFeature('edit_settings', $mode)`. Thêm role/feature mới = sửa mảng này.
- **CSRF** (`includes/security/csrf-helper.php`):
  - AJAX/JSON và link GET (vd `delete_image.php`): `validateCsrfToken($token)` **KHÔNG rotate** — nhiều request/link trên cùng 1 trang dùng chung 1 token, rotate sẽ làm hỏng các request/link còn lại.
  - Form full-page tự submit: `verifyCsrfOrDie()` **CÓ rotate** + `getCsrfInput()` render hidden input trong `<form>`.
- **Trước khi gate 1 file mới**: đọc thật response mode (echo JSON? `header('Location:...')`? render HTML?) và **grep toàn bộ caller thật** (đừng tin tên file hay giả định — nhiều action tưởng "đang dùng" hoá ra mồ côi/script migration cũ, và nhiều caller thật nằm ở JS thay vì component PHP tưởng như vậy).

## Rules Reference

Đọc rule chi tiết trong `.claude/docs/` trước khi thực hiện task liên quan:

- `.claude/docs/coding-rules.md` — Nguyên tắc code, clean code, architecture, comment convention
- `.claude/docs/security-rules.md` — Checklist bảo mật (đọc khi sửa auth, DB, form, upload)
- `.claude/docs/database-rules.md` — Query, migration, schema changes
- `.claude/docs/business-logic-rules.md` — Quy tắc nghiệp vụ (đọc khi sửa logic đơn hàng, tiêu chí, workflow)
- `.claude/docs/dependency-rules.md` — Chính sách thêm thư viện

## Documentation

Docs dự án trong `./docs/` — cập nhật khi feature/schema thay đổi.

## Working Principles

- **YAGNI / KISS / DRY**.
- Đọc `./README.md` + `./docs/` trước khi plan hoặc implement.
- Sửa file hiện có thay vì tạo file enhanced/v2.
- Không commit secrets (`.env`, credentials, API keys).
- Conventional commits (`feat:`, `fix:`, `docs:`, `refactor:`...). Mô tả tiếng Việt OK.
