## Context

Trang `edit_date_clone.php` hiện đang sử dụng kiểu hiển thị `info-data-strip` với các data points riêng lẻ theo chiều ngang. Trang `indexdept.php` sử dụng bảng `data-table` với cấu trúc rõ ràng hơn. Cần đồng bộ giao diện giữa hai trang.

## Goals / Non-Goals

**Goals:**
- Thay đổi layout hiển thị thông tin từ horizontal data strip sang bảng data-table
- Sử dụng cùng cấu trúc cột như indexdept.php: Xưởng, Line, PO, Style, Số lượng, Ngày vào, Ngày ra, Xử Lý Hình Ảnh, Hồ Sơ SA
- Cải thiện bảo mật với htmlspecialchars() và optimized query
- Giữ nguyên chức năng form cập nhật ngày/số lượng/line

**Non-Goals:**
- Không thay đổi logic xử lý form POST
- Không thay đổi cấu trúc database
- Không thêm tính năng Xử Lý Hình Ảnh và Hồ Sơ SA (vì edit_date_clone là trang cập nhật ngày, không cần link đến image_handler và file_templates)

## Decisions

### 1. Sử dụng bảng data-table thay vì info-data-strip
**Rationale**: Bảng data-table từ indexdept.php có cấu trúc chuẩn hơn, dễ đọc trên nhiều thiết bị và phù hợp với style.css có sẵn.

### 2. Chỉ hiển thị các cột thông tin cơ bản
**Rationale**: Trang edit_date_clone.php là trang cập nhật ngày, không cần các cột tương tác như "Xử Lý Hình Ảnh" và "Hồ Sơ SA". Chỉ giữ: Xưởng, Line, PO, Style, Số lượng, Ngày vào hiện tại, Ngày ra hiện tại.

### 3. Sử dụng DateTime object thay vì strtotime()
**Rationale**: DateTime->format() rõ ràng hơn, dễ debug, và consistent với indexdept.php.

### 4. Tối ưu SQL query
**Rationale**: Chỉ SELECT các trường cần thiết thay vì SELECT * để giảm data transfer.

## Risks / Trade-offs

- **[Risk]** CSS của info-data-strip vẫn còn trong file → **Mitigation**: Có thể giữ lại CSS cũ hoặc xóa sau, không ảnh hưởng chức năng
- **[Risk]** Layout thay đổi có thể gây khó chịu cho người dùng quen → **Mitigation**: Layout mới nhất quán với indexdept.php nên người dùng sẽ quen nhanh
