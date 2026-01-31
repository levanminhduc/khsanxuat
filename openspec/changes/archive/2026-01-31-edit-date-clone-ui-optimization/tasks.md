## 1. Setup

- [x] 1.1 Thêm require_once components/form-page.php sau include modal.php (line 3)
- [x] 1.2 Thêm link stylesheet assets/css/form-page.css vào head (sau line 211)

## 2. Remove Inline CSS

- [x] 2.1 Xóa toàn bộ <style> tag và nội dung inline CSS (line 215-395)

## 3. Layout Structure

- [x] 3.1 Thay thế div.container bằng form-page-component wrapper với form-page-container bên trong
- [x] 3.2 Cập nhật alert classes sử dụng form-page component format (alert với alert-icon và alert-content)

## 4. Data Table Migration

- [x] 4.1 Thay thế HTML table (line 428-451) bằng lời gọi render_form_info_table() với rows array chứa 7 fields: Xưởng, Line, PO, Style, Số lượng, Ngày vào, Ngày ra
- [x] 4.2 Đảm bảo output escape với htmlspecialchars cho các giá trị

## 5. Form Elements

- [x] 5.1 Cập nhật input classes từ custom CSS sang form-input class
- [x] 5.2 Cập nhật label classes sử dụng form-label và form-label-required
- [x] 5.3 Wrap date input trong date-input-container và thêm span.date-input-icon element

## 6. Form Note

- [x] 6.1 Thay thế div.form-note HTML (line 473-481) bằng lời gọi render_form_note() với type info, title Lưu ý, items array và footer

## 7. Button Styling

- [x] 7.1 Cập nhật button classes sử dụng form-page component btn classes
- [x] 7.2 Cập nhật form-actions container sử dụng form-page-component class

## 8. Verification

- [x] 8.1 Verify trang load không có lỗi CSS hoặc PHP
- [x] 8.2 Verify button màu #0891b2 (Cyan) thay vì #003366
- [x] 8.3 Verify responsive card view hoạt động khi resize < 768px
- [x] 8.4 Verify date picker icon xuất hiện và click được
