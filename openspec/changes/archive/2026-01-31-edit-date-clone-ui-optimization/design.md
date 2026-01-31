## Context

Trang `edit_date_clone.php` là form page để cập nhật ngày vào chuyền, số lượng và line cho mã hàng. Hiện tại trang này sử dụng inline CSS (line 215-395) với khoảng 180 dòng CSS riêng biệt, trong khi dự án đã có sẵn hệ thống component chuẩn hóa:

- **CSS**: `assets/css/form-page.css` (981 dòng) - CSS variables với prefix `--form-page-*`, Fira Sans font, responsive breakpoint 768px
- **PHP Component**: `components/form-page.php` - Các helper functions: `render_form_page_start()`, `render_form_page_end()`, `render_form_info_table()`, `render_form_note()`
- **Requirements**: `specs/form-pages-ui-standardization/requirements.md` - Đã định nghĩa UI standards với Cyan #0891b2, Slate #334155

Change này là UI migration, không thay đổi business logic hoặc database schema.

## Goals / Non-Goals

**Goals:**
- Loại bỏ toàn bộ inline CSS trong `<style>` tag
- Tích hợp `assets/css/form-page.css` stylesheet
- Sử dụng `.form-page-component` wrapper cho CSS isolation
- Chuyển đổi data table sang responsive format với card view trên mobile
- Thay thế hardcoded colors (#003366) bằng CSS variables (#0891b2)
- Sử dụng `render_form_note()` thay vì HTML thủ công
- Thêm date input icon element cho date picker

**Non-Goals:**
- Thay đổi business logic (calculateDeadline, updateDeptDeadlines)
- Thay đổi database queries hoặc schema
- Thêm/xóa form fields
- Thay đổi validation rules
- Sử dụng form-page-component wrapper cho toàn bộ trang (giữ header component riêng)

## Decisions

### 1. Giữ header component riêng, chỉ wrap form content

**Decision**: Không wrap toàn bộ body trong `.form-page-component`, chỉ wrap phần container chứa form

**Rationale**: Header component (`components/header.php`) có styling riêng trong `assets/css/header.css`. Việc wrap toàn bộ có thể gây conflict CSS. Pattern này đã được sử dụng trong change `sync-edit-date-clone-display`.

**Alternatives considered**:
- Wrap toàn bộ body: Có thể gây conflict với header styles
- Không dùng wrapper: Sẽ cần scope tất cả CSS classes, phức tạp hơn

### 2. Sử dụng render_form_info_table thay vì custom table

**Decision**: Thay thế data table (line 428-451) bằng `render_form_info_table()` với định dạng key-value rows

**Rationale**: 
- Component này tự động render cả table view (desktop) và card view (mobile)
- Đảm bảo responsive behavior nhất quán với các trang khác
- Giảm code duplication

**Alternatives considered**:
- Giữ table và thêm CSS media query cho card view: Cần viết thêm CSS, không tận dụng component
- Chỉ dùng card view: Mất thông tin dạng table trên desktop

### 3. Include form-page.php để có access các helper functions

**Decision**: Thêm `require_once 'components/form-page.php';` sau `include 'components/modal.php';`

**Rationale**: File này expose `render_form_info_table()` và `render_form_note()` cần thiết cho migration. Nó cũng tự động include `form-input.php` nếu cần.

### 4. Thêm date-input-icon element

**Decision**: Wrap date input trong `.date-input-container` và thêm icon element

**Rationale**: 
- Line 535-537 có listener cho `.date-input-icon` nhưng không có element
- Icon giúp user biết có thể click để mở calendar
- CSS đã định nghĩa sẵn style cho `.date-input-icon` trong form-page.css

## Risks / Trade-offs

| Risk | Mitigation |
|------|------------|
| CSS conflict giữa inline styles cũ và form-page.css | Xóa hoàn toàn inline styles, không để lại remnants |
| jQuery UI datepicker styling có thể không match | jQuery UI base theme đã được load (line 211), datepicker hoạt động độc lập |
| Modal component đang được include từ modal.php riêng | Giữ nguyên modal.php include, form-page.php không override |
| Button classes có thể khác | Form-page.css định nghĩa `.btn`, `.btn-primary`, `.btn-secondary` - cần verify compatibility |
