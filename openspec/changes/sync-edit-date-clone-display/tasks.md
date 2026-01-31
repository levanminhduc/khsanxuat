## 1. Backend - Cập nhật SQL Query

- [x] 1.1 Thay đổi query từ `SELECT * FROM khsanxuat WHERE stt = ?` sang `SELECT line1, xuong, po, style, qty, ngayin, ngayout FROM khsanxuat WHERE stt = ?`
- [x] 1.2 Tạo DateTime objects từ ngayin và ngayout thay vì dùng strtotime()

## 2. Frontend - Thay đổi Layout Hiển thị

- [x] 2.1 Xóa hoặc thay thế phần `info-data-strip` bằng bảng `data-table`
- [x] 2.2 Thêm HTML table với các cột: Xưởng, Line, PO, Style, Số lượng, Ngày vào hiện tại, Ngày ra hiện tại
- [x] 2.3 Populate bảng với dữ liệu từ $item_data, sử dụng htmlspecialchars() cho text và DateTime->format('d/m/Y') cho ngày

## 3. Cleanup

- [x] 3.1 Xóa CSS không còn sử dụng cho info-data-strip (hoặc giữ lại nếu dùng ở nơi khác)
- [ ] 3.2 Test hiển thị trên browser để đảm bảo layout đúng
