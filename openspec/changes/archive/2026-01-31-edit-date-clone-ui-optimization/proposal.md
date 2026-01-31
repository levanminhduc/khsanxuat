## Why

Trang `edit_date_clone.php` hiện đang sử dụng inline CSS thay vì hệ thống component chuẩn hóa `form-page.php`. Điều này gây ra sự không đồng bộ với các quy chuẩn UI của dự án đã được định nghĩa trong `specs/form-pages-ui-standardization/requirements.md`.

Các vấn đề cụ thể:
- Sử dụng màu `#003366` thay vì màu Cyan chuẩn `#0891b2`
- Data table không responsive - thiếu card view cho mobile
- Button spacing dùng `12px` thay vì chuẩn `16px`
- Thiếu font Fira Sans
- Form note viết inline thay vì dùng `render_form_note()` component
- Date input icon listener tồn tại nhưng không có element tương ứng

## What Changes

- Loại bỏ toàn bộ inline CSS trong thẻ `<style>` (line 215-395)
- Thêm link stylesheet `assets/css/form-page.css`
- Wrap nội dung trong `.form-page-component` để kích hoạt CSS isolation
- Thay thế data table bằng `render_form_info_table()` component với cả table view và card view
- Thay thế form note HTML bằng `render_form_note()` component  
- Thêm date input icon element cho date picker
- Cập nhật button classes sử dụng form-page component styles
- Đảm bảo sử dụng CSS variables `--form-page-primary` và `--form-page-spacing-md`

## Capabilities

### New Capabilities

- Không có capability mới

### Modified Capabilities

- `edit-date-clone`: Migrate từ inline CSS sang form-page component system với responsive card view trên mobile

## Impact

- **Files**: `edit_date_clone.php`
- **UI**: 
  - Màu primary button chuyển từ `#003366` sang `#0891b2` (Cyan)
  - Data table sẽ transform thành card view trên mobile < 768px
  - Form layout được bọc trong form-page-component wrapper
- **UX**: 
  - Cải thiện trải nghiệm mobile với responsive card view
  - Date picker có icon để user biết có thể click mở calendar
- **Accessibility**: Cải thiện với proper ARIA labels từ form-page component
- **Maintainability**: Giảm code duplication, centralize styling trong form-page.css
