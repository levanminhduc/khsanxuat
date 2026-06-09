# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

When asked about the codebase, project structure, or to find code, always use the context-engine MCP tool (codebase-retrieval) in the root workspace first before reading individual files. Use `codebase-retrieval` instead of the Explore subagent for codebase exploration and search tasks.

When you need to read a specific file but don't know the exact line range, use the file-retrieval MCP tool instead of reading the entire file. Describe what information you need and it returns only the relevant snippets with line numbers. Use the Read tool with the returned line ranges (expanded as needed) to get current content before making edits.

## Project Context

**khsanxuat** — Hệ thống đánh giá sản xuất nhà máy.

- **Stack**: PHP 7.4+ thuần (không framework), MySQL 5.7+ (mysqli), HTML/CSS/JS vanilla, Apache via Laragon.
- **Mục đích**: Theo dõi đơn hàng qua 10 bộ phận sản xuất (kế hoạch → kỹ thuật → kho → cắt → ép keo → cơ điện → chuyền may → KCS → ủi thành phẩm → hoàn thành), đánh giá tiêu chí, quản lý hạn xử lý, upload hình ảnh minh hoạ.
- **Trang chính**: `index.php` (dashboard), `indexdept.php` (chi tiết bộ phận), `theodoi.php`, `settings.php`, `save_danhgia_with_log.php`, `import.php`, `dept_statistics.php`.
- **Modules**: `includes/index/`, `includes/indexdept/`, `includes/security/csrf-helper.php`, `components/`, `views/`, `account/`.
- **DB connection**: `contdb.php`, `db_connect.php` (Laragon mặc định: `localhost`/`root`/blank/`mysqli`).

## Language

- Code, commits, technical terms: tiếng Anh.
- Giải thích, báo cáo, trao đổi với user: **tiếng Việt**.

## Code Style

- **File naming**: kebab-case, tên dài mô tả rõ ý (vd: `save-danhgia-with-log.php`).
- **PHP functions**: camelCase (`checkDeptStatus`, `getEarliestDeadline`).
- **PHP variables**: snake_case (`$id_sanxuat`, `$search_value`).
- **Constants**: UPPER_SNAKE_CASE (`DB_SERVER`, `DB_NAME`).
- **DB**: luôn dùng `mysqli` prepared statements + bind params.

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
