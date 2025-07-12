# Hệ thống đánh giá sản xuất nhà máy

Hệ thống quản lý và đánh giá quy trình sản xuất nhà máy, giúp theo dõi tiến độ sản xuất qua các bộ phận và đánh giá hiệu suất làm việc.

## Tính năng chính

- **Quản lý đơn hàng**: Theo dõi đơn hàng từ khi nhập vào hệ thống đến khi hoàn thành
- **Đánh giá bộ phận**: Thống kê và đánh giá hiệu suất của từng bộ phận trong quy trình sản xuất
- **Biểu đồ trực quan**: Hiển thị tỷ lệ hoàn thành của các bộ phận qua biểu đồ
- **Tìm kiếm và lọc**: Tìm kiếm đơn hàng theo nhiều tiêu chí khác nhau
- **Giao diện thân thiện**: Thiết kế responsive, dễ sử dụng trên cả máy tính và thiết bị di động
- **Xuất báo cáo**: Khả năng xuất dữ liệu để phân tích và báo cáo

## Yêu cầu hệ thống

- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Web server (Apache/Nginx)
- Trình duyệt hiện đại (Chrome, Firefox, Safari, Edge)

## Cài đặt

1. Clone repository về máy chủ web:

   ```
   git clone https://github.com/your-username/factory-evaluation-system.git
   ```

2. Import cơ sở dữ liệu từ file `database.sql` vào MySQL:

   ```
   mysql -u username -p database_name < database.sql
   ```

3. Cấu hình kết nối cơ sở dữ liệu trong file `config.php`:

   ```php
   define('DB_SERVER', 'localhost');
   define('DB_USERNAME', 'your_username');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'your_database');
   ```

4. Truy cập hệ thống qua trình duyệt web:
   ```
   http://your-domain.com/
   ```

## Cấu trúc thư mục

- `index.php`: Trang chính hiển thị tổng quan và danh sách đơn hàng
- `dept_statistics.php`: Hiển thị thống kê chi tiết của từng bộ phận
- `import.php`: Trang nhập dữ liệu đơn hàng mới
- `config.php`: Cấu hình kết nối cơ sở dữ liệu
- `style.css`: File CSS chính
- `js/`: Thư mục chứa các file JavaScript
- `includes/`: Thư mục chứa các file PHP được include

## Hướng dẫn sử dụng

Xem chi tiết trong file [USAGE_GUIDE.md](USAGE_GUIDE.md)

## Bảo trì và cập nhật

Xem chi tiết trong file [MAINTENANCE.md](MAINTENANCE.md)

## Cấu trúc cơ sở dữ liệu

Xem chi tiết trong file [DATABASE.md](DATABASE.md)

## Liên hệ hỗ trợ

Nếu bạn gặp vấn đề hoặc cần hỗ trợ, vui lòng liên hệ:

- Email: support@example.com
- Điện thoại: 0123 456 789
