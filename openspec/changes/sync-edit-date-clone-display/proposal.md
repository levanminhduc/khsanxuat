## Why

Trang `edit_date_clone.php` hiện tại hiển thị thông tin mã hàng khác biệt so với `indexdept.php`. Người dùng cần giao diện nhất quán giữa các trang để dễ dàng theo dõi và thao tác.

## What Changes

- Thay đổi cách hiển thị thông tin mã hàng từ kiểu `info-data-strip` sang kiểu bảng `data-table` giống `indexdept.php`
- Cập nhật query SQL để chỉ lấy các trường cần thiết: `line1, xuong, po, style, qty, ngayin, ngayout, han_xuly, so_ngay_xuly`
- Thêm các cột: Xưởng, Line, PO, Style, Số lượng, Ngày vào, Ngày ra, Xử Lý Hình Ảnh, Hồ Sơ SA
- Sử dụng `DateTime->format('d/m/Y')` để format ngày thay vì `date('d/m/Y', strtotime())`
- Áp dụng `htmlspecialchars()` cho tất cả text output để tăng bảo mật

## Capabilities

### New Capabilities

### Modified Capabilities

- `edit-date-clone`: Thay đổi cách hiển thị thông tin từ horizontal data strip sang bảng data-table theo chuẩn indexdept.php

## Impact

- **Files**: `edit_date_clone.php`
- **UI**: Layout phần hiển thị thông tin mã hàng thay đổi hoàn toàn
- **Security**: Tăng cường bảo mật với `htmlspecialchars()` và prepared statements
- **Performance**: Giảm dữ liệu truy vấn bằng cách chỉ SELECT các trường cần thiết
