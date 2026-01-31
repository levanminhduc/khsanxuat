## ADDED Requirements

### Requirement: Data Table Display
Trang edit_date_clone.php SHALL hiển thị thông tin mã hàng trong bảng `data-table` với các cột: Xưởng, Line, PO, Style, Số lượng, Ngày vào hiện tại, Ngày ra hiện tại.

#### Scenario: Hiển thị bảng thông tin
- **WHEN** người dùng truy cập trang edit_date_clone.php với ID hợp lệ
- **THEN** hệ thống hiển thị bảng data-table chứa thông tin mã hàng tương ứng

### Requirement: Date Format
Hệ thống SHALL sử dụng DateTime object với format('d/m/Y') để hiển thị ngày thay vì strtotime().

#### Scenario: Format ngày đúng chuẩn
- **WHEN** dữ liệu ngày được load từ database
- **THEN** ngày được hiển thị theo định dạng dd/mm/yyyy (ví dụ: 31/01/2026)

### Requirement: Security Escaping
Tất cả text output MUST được escape bằng htmlspecialchars() để tránh XSS.

#### Scenario: Escape dữ liệu text
- **WHEN** hệ thống hiển thị các trường text như xuong, po, style
- **THEN** nội dung được wrap trong htmlspecialchars() trước khi output

### Requirement: Optimized Query
Hệ thống SHALL chỉ SELECT các trường cần thiết: line1, xuong, po, style, qty, ngayin, ngayout.

#### Scenario: Query tối ưu
- **WHEN** hệ thống truy vấn thông tin mã hàng
- **THEN** query chỉ lấy các trường: line1, xuong, po, style, qty, ngayin, ngayout thay vì SELECT *
