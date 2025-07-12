# Tài liệu Tổng hợp Tính năng Hệ thống Quản lý Sản Xuất Nhà Máy

Tài liệu này tổng hợp các tính năng, cấu trúc dữ liệu, và hướng dẫn bảo trì cho hệ thống đánh giá sản xuất nhà máy. Mục đích của tài liệu là giúp người phát triển và quản trị hiểu rõ hệ thống để dễ dàng bảo trì, nâng cấp và xử lý sự cố trong tương lai.

## Mục lục

1. [Tổng quan hệ thống](#tổng-quan-hệ-thống)
2. [Nguyên tắc phát triển](#nguyên-tắc-phát-triển)
3. [Cấu trúc dữ liệu](#cấu-trúc-dữ-liệu)
4. [Các trang chính](#các-trang-chính)
   - [index.php](#indexphp)
   - [indexdept.php](#indexdeptphp)
   - [factory_templates.php](#factory_templatesphp)
   - [image_handler.php](#image_handlerphp)
5. [Điều kiện đặc biệt cho tiêu chí đánh giá](#điều-kiện-đặc-biệt-cho-tiêu-chí-đánh-giá)
6. [Hướng dẫn nâng cấp và bảo trì](#hướng-dẫn-nâng-cấp-và-bảo-trì)
7. [Chi tiết tính năng indexdept.php](#chi-tiết-tính-năng-indexdeptphp)
8. [Chi tiết tính năng theodoi.php](#chi-tiết-tính-năng-theodoiphp)

## Nguyên tắc phát triển

### Quy tắc chung

1. **Ưu tiên dữ liệu có sẵn**:

   - Luôn sử dụng dữ liệu từ database hiện có
   - Không tạo thêm bảng hoặc cột mới khi chưa cần thiết
   - Tận dụng các mối quan hệ và cấu trúc dữ liệu sẵn có

2. **Đơn giản hóa giải pháp**:

   - Chọn cách tiếp cận đơn giản nhất có thể
   - Tránh các giải pháp phức tạp không cần thiết
   - Ưu tiên sử dụng các chức năng cơ bản có sẵn

3. **Bảo vệ dữ liệu**:
   - KHÔNG tự ý thêm, sửa, xóa dữ liệu trong database
   - Mọi thay đổi cấu trúc hoặc dữ liệu phải được phê duyệt
   - Luôn sao lưu dữ liệu trước khi thực hiện thay đổi lớn

### Quy trình phát triển tính năng mới

1. **Phân tích yêu cầu**:

   - Xác định rõ mục tiêu và phạm vi
   - Kiểm tra các bảng và dữ liệu hiện có
   - Đánh giá khả năng tận dụng cấu trúc hiện tại

2. **Thiết kế giải pháp**:

   - Ưu tiên sử dụng cấu trúc database hiện có
   - Đề xuất giải pháp đơn giản nhất
   - Liệt kê các thay đổi cần thiết (nếu có)

3. **Thực hiện**:

   - Xin phép trước khi thay đổi cấu trúc database
   - Viết code rõ ràng, dễ bảo trì
   - Tận dụng các hàm và thư viện có sẵn

4. **Kiểm thử**:
   - Thử nghiệm kỹ lưỡng trước khi triển khai
   - Đảm bảo không ảnh hưởng đến dữ liệu hiện có
   - Kiểm tra tương thích với các tính năng khác

## Tổng quan hệ thống

Hệ thống Quản lý Sản Xuất Nhà Máy là một ứng dụng theo dõi và đánh giá quy trình sản xuất tại nhà máy. Hệ thống giúp quản lý đơn hàng từ khi nhập vào hệ thống đến khi hoàn thành, đánh giá hiệu suất của từng bộ phận trong quy trình sản xuất, và hiển thị trực quan kết quả thông qua các biểu đồ và bảng thống kê.

Các chức năng chính của hệ thống:

- Quản lý đơn hàng và các mã sản phẩm (style)
- Theo dõi tiến độ sản xuất qua các bộ phận
- Đánh giá tiêu chí theo từng bộ phận
- Quản lý biểu mẫu và tài liệu cho mỗi bộ phận và mã hàng
- Quản lý hình ảnh minh họa theo tiêu chí đánh giá
- Theo dõi hạn xử lý và tính toán hiệu suất

## Cấu trúc dữ liệu

Hệ thống sử dụng các bảng dữ liệu chính sau:

1. **khsanxuat**: Lưu thông tin về các mã hàng và đơn hàng

   - `stt`: ID của mã hàng
   - `style`: Mã hàng
   - `po`: Số PO
   - `line1`: Dây chuyền sản xuất
   - `xuong`: Xưởng sản xuất
   - `qty`: Số lượng
   - `ngayin`: Ngày vào
   - `ngayout`: Ngày ra
   - `han_xuly`: Hạn xử lý
   - `so_ngay_xuly`: Số ngày xử lý mặc định

2. **dept_status**: Trạng thái hoàn thành của từng bộ phận

   - `id`: ID tự tăng
   - `id_sanxuat`: ID mã hàng (liên kết với khsanxuat.stt)
   - `dept`: Mã bộ phận
   - `completed`: Trạng thái hoàn thành (0/1)

3. **tieuchi_dept**: Các tiêu chí đánh giá theo bộ phận

   - `id`: ID tiêu chí
   - `dept`: Mã bộ phận
   - `thutu`: Thứ tự hiển thị
   - `noidung`: Nội dung tiêu chí
   - `giai_thich`: Giải thích tiêu chí

4. **danhgia_tieuchi**: Đánh giá chi tiết theo từng tiêu chí

   - `id`: ID đánh giá
   - `id_sanxuat`: ID mã hàng
   - `id_tieuchi`: ID tiêu chí
   - `diem_danhgia`: Điểm đánh giá
   - `han_xuly`: Hạn xử lý riêng cho tiêu chí
   - `nguoi_thuchien`: ID người thực hiện

5. **dept_templates**: Lưu trữ các biểu mẫu theo bộ phận

   - `id`: ID biểu mẫu
   - `dept`: Mã bộ phận
   - `template_name`: Tên biểu mẫu
   - `template_description`: Mô tả biểu mẫu

6. **dept_template_files**: Lưu trữ các file đính kèm cho biểu mẫu

   - `id`: ID file
   - `id_template`: ID biểu mẫu
   - `id_khsanxuat`: ID mã hàng
   - `file_path`: Đường dẫn file
   - `upload_date`: Ngày upload

7. **khsanxuat_images**: Lưu trữ hình ảnh cho từng mã hàng

   - `id`: ID hình ảnh
   - `id_khsanxuat`: ID mã hàng
   - `dept`: Mã bộ phận
   - `image_path`: Đường dẫn hình ảnh
   - `id_tieuchi`: ID tiêu chí
   - `upload_date`: Ngày upload

8. **default_settings**: Lưu trữ cài đặt mặc định
   - `id`: ID cài đặt
   - `dept`: Mã bộ phận
   - `xuong`: Xưởng sản xuất
   - `id_tieuchi`: ID tiêu chí
   - `ngay_tinh_han`: Phương thức tính hạn xử lý
   - `so_ngay_xuly`: Số ngày xử lý mặc định
   - `nguoi_chiu_trachnhiem_default`: ID người chịu trách nhiệm mặc định

## Các trang chính

### index.php

**Mục đích**: Trang chính hiển thị tổng quan và danh sách đơn hàng.

**Tính năng chính**:

- Hiển thị danh sách mã hàng theo tháng/năm
- Tìm kiếm mã hàng theo nhiều tiêu chí (xưởng, line, PO, style, model)
- Hiển thị thống kê tiến độ của các bộ phận qua biểu đồ
- Chức năng lọc và phân trang danh sách mã hàng
- Liên kết tới trang chi tiết của từng mã hàng theo bộ phận

**Các hàm quan trọng**:

1. `checkDeptStatus($connect, $id_sanxuat, $dept)`: Kiểm tra trạng thái hoàn thành của một bộ phận
2. `getEarliestDeadline($connect, $id_sanxuat, $dept)`: Lấy ngày hạn xử lý thấp nhất của tiêu chí
3. `hasIncompleteCriteria($connect, $style, $stt)`: Kiểm tra xem mã hàng có tiêu chí chưa hoàn thành không

**Luồng dữ liệu**:

- Lấy danh sách mã hàng từ bảng `khsanxuat` theo tháng và năm
- Tính toán trạng thái hoàn thành của từng bộ phận qua bảng `dept_status`
- Hiển thị thống kê và biểu đồ tiến độ
- Hiển thị danh sách mã hàng với trạng thái từng bộ phận

### indexdept.php

**Mục đích**: Trang chi tiết đánh giá cho một mã hàng theo bộ phận cụ thể.

**Tính năng chính**:

- Hiển thị thông tin chi tiết của mã hàng (style, PO, xưởng, line)
- Hiển thị danh sách tiêu chí đánh giá của bộ phận
- Nhập điểm đánh giá cho từng tiêu chí
- Cập nhật hạn xử lý cho từng tiêu chí
- Quản lý người thực hiện đánh giá
- Liên kết tới quản lý hình ảnh

**Biến và cấu trúc dữ liệu quan trọng**:

- `$dept`: Mã bộ phận hiện tại
- `$id`: ID mã hàng đang xem
- `$dept_names`: Mảng ánh xạ mã bộ phận với tên hiển thị
- `$han_xuly`: Hạn xử lý của mã hàng
- `$tieuchi_list`: Danh sách tiêu chí đánh giá của bộ phận
- `$danhgia_data`: Dữ liệu đánh giá đã lưu

**Quy trình đánh giá**:

1. Lấy danh sách tiêu chí từ bảng `tieuchi_dept`
2. Lấy dữ liệu đánh giá đã lưu từ bảng `danhgia_tieuchi` (nếu có)
3. Hiển thị form đánh giá cho từng tiêu chí
4. Người dùng nhập điểm đánh giá
5. Dữ liệu đánh giá được lưu vào bảng `danhgia_tieuchi`
6. Cập nhật trạng thái hoàn thành trong bảng `dept_status`

### Thang điểm đánh giá

Hệ thống sử dụng các thang điểm đánh giá khác nhau tùy theo loại tiêu chí:

1. **Thang điểm chuẩn**:

   - Mức 0: Tiêu chí chưa đạt
   - Mức 1: Tiêu chí đạt mức cơ bản
   - Mức 3: Tiêu chí đạt mức cao

2. **Thang điểm cho tiêu chí đặc biệt**:
   - Mức 0: Tiêu chí chưa đạt
   - Mức 0.5: Tiêu chí đạt mức thấp
   - Mức 1.5: Tiêu chí đạt mức trung bình

Tất cả các điểm đánh giá đều được lưu trong trường `diem_danhgia` của bảng `danhgia_tieuchi`. Hệ thống sử dụng kiểu dữ liệu DECIMAL hoặc FLOAT để lưu trữ các giá trị thập phân.

### Xác định trạng thái hoàn thành

Quy tắc xác định trạng thái hoàn thành của một bộ phận (`dept_status.completed`) như sau:

1. **Điều kiện để hoàn thành (completed = 1)**:

   - Tất cả các tiêu chí đều phải có điểm đánh giá > 0
   - Điều này áp dụng cho cả tiêu chí thông thường và tiêu chí đặc biệt

2. **Trường hợp chưa hoàn thành (completed = 0)**:
   - Nếu có ít nhất một tiêu chí có điểm = 0 hoặc NULL (chưa được đánh giá)
   - Hệ thống sẽ đánh dấu bộ phận là chưa hoàn thành, ngay cả khi các tiêu chí khác đã có điểm > 0

Lưu ý:

- Các điểm đánh giá đã nhập vẫn được lưu trong bảng `danhgia_tieuchi`, bất kể trạng thái hoàn thành của bộ phận
- Khi tất cả tiêu chí được đánh giá (điểm > 0), hệ thống sẽ tự động cập nhật trạng thái hoàn thành
- Không có cơ chế "hoàn thành một phần" - bộ phận chỉ có thể ở trạng thái hoàn thành (1) hoặc chưa hoàn thành (0)

### factory_templates.php

**Mục đích**: Quản lý biểu mẫu và tài liệu theo xưởng và mã hàng.

**Tính năng chính**:

- Hiển thị danh sách mã hàng theo xưởng và tháng/năm
- Hiển thị danh sách biểu mẫu cho từng bộ phận
- Tìm kiếm và lọc mã hàng
- Quản lý file đính kèm cho từng biểu mẫu
- Tải xuống tất cả các file của một mã hàng

**Cấu trúc dữ liệu**:

- `$dept_names`: Mảng ánh xạ mã bộ phận với tên hiển thị
- `$products`: Danh sách mã hàng của xưởng
- `$departments`: Danh sách các bộ phận có biểu mẫu
- `$all_templates`: Danh sách tất cả biểu mẫu
- `$product_templates`: Danh sách biểu mẫu cho mã hàng cụ thể

**Quy trình quản lý biểu mẫu**:

1. Hiển thị danh sách mã hàng theo xưởng
2. Người dùng chọn mã hàng cụ thể
3. Hiển thị danh sách biểu mẫu theo bộ phận
4. Người dùng có thể quản lý file cho từng biểu mẫu

### image_handler.php

**Mục đích**: Quản lý hình ảnh cho từng mã hàng và tiêu chí đánh giá.

**Tính năng chính**:

- Upload hình ảnh cho mã hàng theo tiêu chí
- Xem và quản lý hình ảnh đã upload
- Xóa hình ảnh
- Hiển thị hình ảnh theo gallery với chế độ xem chi tiết

**Cấu trúc dữ liệu**:

- `$tieuchi_list`: Danh sách tiêu chí của bộ phận
- `$images`: Danh sách hình ảnh đã upload
- `$image_folder`: Thư mục lưu trữ hình ảnh

**Quy trình quản lý hình ảnh**:

1. Người dùng chọn tiêu chí cần upload hình ảnh
2. Upload một hoặc nhiều hình ảnh
3. Hình ảnh được lưu vào thư mục theo cấu trúc `images/{dept}/{id}/tieuchi_{id_tieuchi}/`
4. Thông tin hình ảnh được lưu vào bảng `khsanxuat_images`
5. Hiển thị gallery hình ảnh đã upload
6. Người dùng có thể xem chi tiết hoặc xóa hình ảnh

## Điều kiện đặc biệt cho tiêu chí đánh giá

### Tiêu chí bắt buộc có hình ảnh

Hệ thống có một số tiêu chí đặc biệt yêu cầu phải có hình ảnh đính kèm trước khi có thể đánh giá. Hiện tại, điều kiện này áp dụng cho:

**Tiêu chí số 5 của Kho Phụ Liệu (ID: 131)**

1. **Mô tả yêu cầu**:

   - Không thể đánh giá điểm > 0 nếu chưa có hình ảnh đính kèm
   - Hình ảnh phải được upload trước khi thực hiện đánh giá
   - Hệ thống tự động kiểm tra điều kiện này

2. **Cách thức hoạt động**:

   - Kiểm tra khi submit form đánh giá trong indexdept.php
   - Kiểm tra realtime thông qua JavaScript khi người dùng thay đổi điểm
   - Tự động reset điểm về 0 nếu chưa có hình ảnh

3. **File liên quan**:

   - indexdept.php: Xử lý chính và hiển thị
   - check_tieuchi_image.php: Kiểm tra sự tồn tại của hình ảnh
   - ajax_check_tieuchi_image.php: API kiểm tra hình ảnh qua AJAX

4. **Database**:

   - Bảng: khsanxuat_images
   - Quan hệ: id_khsanxuat, id_tieuchi

5. **Quy trình kiểm tra**:

   ```php
   if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_diem'])) {
       if ($dept == 'kho' && isset($_POST['diem'][131]) && $_POST['diem'][131] > 0) {
           if (!checkTieuchiHasImage($connect, $id, 131)) {
               // Bỏ qua giá trị điểm và hiển thị thông báo lỗi
               unset($_POST['diem'][131]);
           }
       }
   }
   ```

6. **Hướng dẫn bảo trì**:

   a. Thêm tiêu chí mới yêu cầu hình ảnh:

   ```php
   // Trong indexdept.php
   if ($dept == 'ten_bo_phan' && isset($_POST['diem'][id_tieuchi]) && $_POST['diem'][id_tieuchi] > 0) {
       if (!checkTieuchiHasImage($connect, $id, id_tieuchi)) {
           unset($_POST['diem'][id_tieuchi]);
       }
   }
   ```

   b. Kiểm tra hình ảnh:

   ```php
   function checkTieuchiHasImage($connect, $id_khsanxuat, $id_tieuchi) {
       $sql = "SELECT COUNT(*) as count FROM khsanxuat_images
               WHERE id_khsanxuat = ? AND id_tieuchi = ?";
       $stmt = $connect->prepare($sql);
       $stmt->bind_param("ii", $id_khsanxuat, $id_tieuchi);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       return $row['count'] > 0;
   }
   ```

7. **Xử lý lỗi phổ biến**:

   - Kiểm tra quyền ghi vào thư mục images/
   - Đảm bảo bảng khsanxuat_images tồn tại và có cấu trúc đúng
   - Kiểm tra giới hạn kích thước upload trong php.ini
   - Xác minh các file PHP xử lý hình ảnh tồn tại và có quyền thực thi

8. **Kế hoạch nâng cấp**:
   - Thêm tùy chọn cấu hình tiêu chí bắt buộc hình ảnh qua giao diện admin
   - Cho phép nhiều hình ảnh cho một tiêu chí
   - Thêm validation cho loại file và kích thước hình ảnh
   - Tích hợp preview hình ảnh trước khi upload

## Quản lý Tiêu Chí Bắt Buộc Hình Ảnh (required_images_criteria.php)

### Mục đích

Quản lý các tiêu chí yêu cầu bắt buộc phải có hình ảnh trước khi đánh giá điểm cho từng bộ phận.

### Tính năng chính

1. **Thêm tiêu chí bắt buộc hình ảnh**:

   - Chọn bộ phận từ danh sách có sẵn
   - Nhập ID tiêu chí với tính năng gợi ý
   - Kiểm tra tồn tại của tiêu chí trước khi thêm
   - Ngăn chặn thêm trùng lặp

2. **Hiển thị danh sách tiêu chí**:

   - Nhóm theo bộ phận
   - Hiển thị STT, tên bộ phận, thứ tự và nội dung tiêu chí
   - Chức năng xóa tiêu chí

3. **Tương tác người dùng**:
   - Preview nội dung tiêu chí khi nhập ID
   - Xác nhận trước khi xóa
   - Thông báo kết quả thao tác

### Cấu trúc dữ liệu

Sử dụng các bảng:

1. **required_images_criteria**:

   - `id`: ID tự tăng
   - `dept`: Mã bộ phận
   - `id_tieuchi`: ID tiêu chí
   - Ràng buộc UNIQUE cho (dept, id_tieuchi)

2. **tieuchi_dept** (liên kết):
   - Lấy thông tin chi tiết của tiêu chí
   - Kiểm tra tồn tại của tiêu chí

### Quy trình thêm tiêu chí mới

1. Kiểm tra tiêu chí tồn tại trong bảng `tieuchi_dept`
2. Kiểm tra tiêu chí chưa được thêm trước đó
3. Thêm vào bảng `required_images_criteria`
4. Hiển thị thông báo kết quả

### Quy trình xóa tiêu chí

1. Yêu cầu xác nhận từ người dùng
2. Xóa bản ghi từ bảng `required_images_criteria`
3. Hiển thị thông báo kết quả

### Tích hợp với các tính năng khác

1. **Với indexdept.php**:

   - Kiểm tra điều kiện hình ảnh trước khi cho phép đánh giá
   - Tự động reset điểm về 0 nếu chưa có hình ảnh

2. **Với image_handler.php**:
   - Hiển thị yêu cầu upload hình ảnh cho các tiêu chí bắt buộc
   - Liên kết trực tiếp tới trang upload hình ảnh

### API Endpoints

1. **get_tieuchi_list.php**:

   - Input: dept (mã bộ phận)
   - Output: Danh sách tiêu chí của bộ phận

2. **get_tieuchi_info.php**:
   - Input: dept, id (mã bộ phận và ID tiêu chí)
   - Output: Thông tin chi tiết của tiêu chí

## Hướng dẫn nâng cấp và bảo trì

### Thêm bộ phận mới

1. Cập nhật mảng `$dept_names` trong các file:

   - `index.php`
   - `indexdept.php`
   - `factory_templates.php`
   - `image_handler.php`

2. Thêm tiêu chí đánh giá cho bộ phận mới trong bảng `tieuchi_dept`:

   ```sql
   INSERT INTO tieuchi_dept (dept, thutu, noidung, giai_thich)
   VALUES ('ma_bo_phan_moi', 1, 'Nội dung tiêu chí 1', 'Giải thích tiêu chí');
   ```

3. Thêm biểu mẫu cho bộ phận mới trong bảng `dept_templates`:
   ```sql
   INSERT INTO dept_templates (dept, template_name, template_description)
   VALUES ('ma_bo_phan_moi', 'Tên biểu mẫu', 'Mô tả biểu mẫu');
   ```

### Thêm tiêu chí đánh giá mới

1. Thêm tiêu chí vào bảng `tieuchi_dept`:

   ```sql
   INSERT INTO tieuchi_dept (dept, thutu, noidung, giai_thich)
   VALUES ('ma_bo_phan', thutu_moi, 'Nội dung tiêu chí mới', 'Giải thích tiêu chí');
   ```

2. Cập nhật cài đặt mặc định nếu cần:
   ```sql
   INSERT INTO default_settings (dept, xuong, id_tieuchi, ngay_tinh_han, so_ngay_xuly)
   VALUES ('ma_bo_phan', 'xuong', ID_tieuchi_moi, 'ngay_vao', 7);
   ```

### Xử lý sự cố phổ biến

1. **Lỗi kết nối CSDL**:

   - Kiểm tra thông tin kết nối trong file `db_connect.php`
   - Đảm bảo MySQL đang chạy
   - Kiểm tra quyền truy cập của user MySQL

2. **Lỗi thiếu bảng hoặc cột**:

   - Kiểm tra các bảng thông qua phần quản trị MySQL
   - Tham khảo các đoạn code kiểm tra và tạo bảng trong `indexdept.php`

3. **Lỗi upload file/hình ảnh**:

   - Kiểm tra quyền ghi vào thư mục `template_files/` và `images/`
   - Kiểm tra giới hạn kích thước upload trong `php.ini`

4. **Hiệu suất chậm**:
   - Tối ưu các truy vấn SQL phức tạp
   - Thêm chỉ mục cho các cột thường xuyên tìm kiếm
   - Giới hạn số lượng bản ghi hiển thị mỗi trang

## Chi tiết tính năng indexdept.php

### Tổng quan

File `indexdept.php` là trang chi tiết đánh giá mã hàng theo từng bộ phận. Đây là nơi người dùng thực hiện đánh giá các tiêu chí, cập nhật hạn xử lý, và quản lý thông tin chi tiết của mã hàng.

### Các biến quan trọng

- `$dept`: Mã bộ phận hiện tại (từ tham số GET)
- `$id`: ID mã hàng đang xem (từ tham số GET)
- `$is_admin`: Biến xác định quyền admin (hiện tại mặc định là `true`)
- `$dept_names`: Mảng ánh xạ mã bộ phận với tên hiển thị
- `$han_xuly`: Hạn xử lý của mã hàng
- `$tieuchi_list`: Mảng chứa danh sách tiêu chí đánh giá
- `$danhgia_data`: Mảng chứa dữ liệu đánh giá đã lưu (lấy từ bảng `danhgia_tieuchi`)

### Quy trình đánh giá

1. **Hiển thị thông tin mã hàng**: Lấy từ bảng `khsanxuat`

   - Style, PO, line, xưởng, số lượng, ngày vào/ra

2. **Tính toán hạn xử lý**: Dựa vào phương thức tính hạn xử lý trong database

   - `ngay_vao`: Ngày vào - số ngày xử lý
   - `ngay_ra`: Ngày ra + số ngày xử lý
   - `ngay_ra_tru`: Ngày ra - số ngày xử lý
   - `ngay_vao_cong`: Ngày vào + số ngày xử lý

3. **Tải danh sách tiêu chí đánh giá**:

   - Lấy từ bảng `tieuchi_dept` theo `dept`
   - Sắp xếp theo thứ tự `thutu`

4. **Tải dữ liệu đánh giá đã lưu**:

   - Lấy từ bảng `danhgia_tieuchi` theo `id_sanxuat` và `id_tieuchi`
   - Bao gồm điểm đánh giá, hạn xử lý tiêu chí, người thực hiện

5. **Hiển thị form đánh giá**:

   - Form cho phép nhập điểm đánh giá (0-10)
   - Cập nhật hạn xử lý cho từng tiêu chí
   - Chọn người thực hiện

6. **Lưu đánh giá**:

   - Dữ liệu được gửi tới `save_danhgia.php`
   - Thực hiện INSERT hoặc UPDATE vào bảng `danhgia_tieuchi`
   - Điểm đánh giá được lưu vào trường `diem_danhgia`

7. **Cập nhật trạng thái hoàn thành**:
   - Nếu tất cả tiêu chí đạt điểm > 0, cập nhật `dept_status.completed = 1`
   - Ngược lại, `dept_status.completed = 0`

### Tiêu chí đánh giá

Các tiêu chí đánh giá được lưu trữ trong bảng `tieuchi_dept` với cấu trúc:

- `id`: ID tiêu chí
- `dept`: Mã bộ phận
- `thutu`: Thứ tự hiển thị
- `noidung`: Nội dung tiêu chí
- `giai_thich`: Giải thích chi tiết

Điểm đánh giá được lưu trong bảng `danhgia_tieuchi`:

- `id`: ID đánh giá
- `id_sanxuat`: ID mã hàng
- `id_tieuchi`: ID tiêu chí
- `diem_danhgia`: Điểm đánh giá (0-10)
- `han_xuly`: Hạn xử lý riêng cho tiêu chí
- `nguoi_thuchien`: ID người thực hiện

### Cài đặt mặc định

Hệ thống sử dụng bảng `default_settings` để lưu các cài đặt mặc định cho từng tiêu chí:

- `dept`: Mã bộ phận
- `xuong`: Xưởng
- `id_tieuchi`: ID tiêu chí
- `ngay_tinh_han`: Phương thức tính hạn xử lý mặc định
- `so_ngay_xuly`: Số ngày xử lý mặc định
- `nguoi_chiu_trachnhiem_default`: ID người chịu trách nhiệm mặc định

Những cài đặt này được áp dụng khi thêm mã hàng mới vào hệ thống.

### Liên kết với các tính năng khác

- **Quản lý hình ảnh**: Liên kết tới `image_handler.php` để quản lý hình ảnh theo tiêu chí
- **Quản lý biểu mẫu**: Liên kết tới `factory_templates.php` để quản lý biểu mẫu và tài liệu
- **Cập nhật hạn xử lý**: Sử dụng `update_deadline_tieuchi.php` để cập nhật hạn xử lý tiêu chí

### Hướng dẫn bảo trì và mở rộng

1. **Thêm trường mới vào đánh giá tiêu chí**:

   - Thêm cột vào bảng `danhgia_tieuchi`
   - Cập nhật form đánh giá trong `indexdept.php`
   - Cập nhật xử lý lưu trong `save_danhgia.php`

2. **Thay đổi cách tính hạn xử lý**:

   - Cập nhật các phương thức tính trong `indexdept.php`
   - Thêm tùy chọn mới vào form cài đặt hạn xử lý
   - Cập nhật bảng `default_settings` với phương thức mới

3. **Thêm quyền và phân quyền**:
   - Cập nhật biến `$is_admin` để sử dụng hệ thống phân quyền thực tế
   - Thêm kiểm tra quyền cho các chức năng nhạy cảm
   - Tạo bảng và quản lý người dùng với các quyền khác nhau

Khi cập nhật mã nguồn, luôn đảm bảo sao lưu dữ liệu và kiểm tra kỹ lưỡng các thay đổi trước khi triển khai lên môi trường sản xuất.

## Chi tiết tính năng theodoi.php

File `theodoi.php` là trang hiển thị lịch sử hoạt động của hệ thống, cho phép theo dõi và kiểm tra các thay đổi được thực hiện trên các mã hàng và tiêu chí đánh giá.

### Các biến và cấu trúc dữ liệu quan trọng

1. **Biến lọc và giới hạn**:

   - `$filters`: Mảng chứa các điều kiện lọc
   - `$filters['limit']`: Giới hạn số bản ghi (mặc định 100, tối đa 1000)

2. **Ánh xạ loại hoạt động (`$action_types`)**:

   ```php
   $action_types = [
       'update_score' => 'Cập nhật điểm',
       'update_person' => 'Thay đổi người thực hiện',
       'update_note' => 'Cập nhật ghi chú',
       'update_multiple' => 'Cập nhật nhiều thông tin',
       'add_image' => 'Thêm hình ảnh',
       'add_template' => 'Thêm biểu mẫu',
       'delete_image' => 'Xóa hình ảnh',
       'delete_template' => 'Xóa biểu mẫu'
   ]
   ```

3. **Ánh xạ loại đối tượng (`$target_types`)**:
   ```php
   $target_types = [
       'tieuchi' => 'Tiêu chí',
       'image' => 'Hình ảnh',
       'template' => 'Biểu mẫu'
   ]
   ```

### Tính năng chính

1. **Lọc hoạt động**:

   - Theo loại hoạt động (update, add, delete)
   - Theo mã hàng (ID)
   - Theo bộ phận trong chi tiết
   - Giới hạn số lượng bản ghi hiển thị

2. **Hiển thị thông tin**:

   - Thời gian thực hiện
   - Người thực hiện (tên và họ tên đầy đủ)
   - Loại hoạt động
   - Mã hàng
   - Chi tiết thay đổi

3. **Định dạng hiển thị**:

   - Badge màu cho các loại hoạt động khác nhau
   - Hiển thị thông tin người dùng theo 2 dòng
   - Định dạng chi tiết thay đổi với màu sắc và style riêng

4. **Tương tác người dùng**:
   - Form lọc với các dropdown và input
   - Hiển thị thông tin bộ lọc hiện tại
   - Nút xóa bộ lọc
   - Highlight các kết quả phù hợp với bộ lọc

### Cấu trúc HTML và CSS

1. **Container chính**:

   - Giới hạn chiều rộng tối đa 1200px
   - Nền trắng với shadow nhẹ
   - Bo góc và padding phù hợp

2. **Form lọc**:

   - Flexbox layout với gap
   - Style cho select và input
   - Nút lọc với hover effect

3. **Bảng kết quả**:

   - Responsive table
   - Alternating row colors
   - Hover effect trên các dòng
   - Column alignment và padding phù hợp

4. **Responsive Design**:
   - Media queries cho màn hình nhỏ
   - Điều chỉnh layout form lọc
   - Scroll ngang cho bảng trên mobile

### JavaScript Functionality

1. **Xử lý lọc theo bộ phận**:

   - Thu thập thông tin bộ phận từ chi tiết
   - Tạo dropdown options động
   - Lưu trữ bộ phận trong Map

2. **Quản lý URL Parameters**:

   - Thêm/xóa tham số URL không reload trang
   - Duy trì trạng thái lọc qua URL

3. **Hiệu ứng UI**:
   - Highlight kết quả lọc
   - Hiển thị/ẩn thông tin bộ lọc
   - Xử lý không tìm thấy kết quả

### Hướng dẫn bảo trì

1. **Thêm loại hoạt động mới**:

   ```php
   $action_types['new_action'] = 'Tên hiển thị';
   ```

2. **Thêm trường lọc mới**:

   - Thêm điều kiện vào mảng `$filters`
   - Cập nhật form HTML
   - Thêm xử lý JavaScript nếu cần

3. **Tùy chỉnh hiển thị**:

   - Sửa CSS classes trong file
   - Thêm/sửa các badge styles
   - Điều chỉnh layout và responsive breakpoints

4. **Xử lý lỗi phổ biến**:
   - Kiểm tra kết nối database
   - Validate input parameters
   - Xử lý trường hợp không có dữ liệu

### Kế hoạch nâng cấp

1. **Tính năng mới**:

   - Thêm phân trang cho kết quả
   - Export dữ liệu ra file
   - Tìm kiếm nâng cao
   - Lọc theo khoảng thời gian

2. **Cải thiện UI/UX**:

   - Thêm loading states
   - Animate các thay đổi
   - Cải thiện mobile experience
   - Thêm dark mode

3. **Tối ưu hiệu suất**:
   - Cache kết quả phổ biến
   - Lazy loading cho dữ liệu lớn
   - Tối ưu queries database
   - Nén response data
