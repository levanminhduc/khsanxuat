-- Xóa các tiêu chí cũ (nếu cần)
DELETE FROM tieuchi_dept WHERE dept = 'chuanbi_sanxuat_phong_kt';

-- Thêm các tiêu chí mới
-- Nhóm Nghiệp vụ
INSERT INTO tieuchi_dept (dept, thutu, noidung, giai_thich) VALUES
('chuanbi_sanxuat_phong_kt', 1, 'Kiểm tra thông tin đơn hàng', 'Kiểm tra đầy đủ thông tin về số lượng, màu sắc, size, ngày giao hàng'),
('chuanbi_sanxuat_phong_kt', 2, 'Kiểm tra bảng size spec', 'Đảm bảo bảng size spec đầy đủ và chính xác theo yêu cầu khách hàng'),
('chuanbi_sanxuat_phong_kt', 3, 'Kiểm tra định mức nguyên phụ liệu', 'Tính toán và kiểm tra định mức NPL theo từng đơn hàng'),
('chuanbi_sanxuat_phong_kt', 4, 'Lập kế hoạch sản xuất', 'Lập kế hoạch chi tiết cho từng công đoạn sản xuất'),
('chuanbi_sanxuat_phong_kt', 5, 'Theo dõi tiến độ sản xuất', 'Cập nhật và theo dõi tiến độ thực hiện của các công đoạn');

-- Nhóm May mẫu
INSERT INTO tieuchi_dept (dept, thutu, noidung, giai_thich) VALUES
('chuanbi_sanxuat_phong_kt', 6, 'May mẫu đầu tiên', 'May và hoàn thiện mẫu đầu tiên theo yêu cầu'),
('chuanbi_sanxuat_phong_kt', 7, 'Kiểm tra kỹ thuật may', 'Đánh giá các thông số kỹ thuật may của mẫu'),
('chuanbi_sanxuat_phong_kt', 8, 'Đối chiếu mẫu với size spec', 'So sánh mẫu may với bảng size spec'),
('chuanbi_sanxuat_phong_kt', 9, 'Điều chỉnh mẫu theo yêu cầu', 'Thực hiện các điều chỉnh cần thiết theo phản hồi'),
('chuanbi_sanxuat_phong_kt', 10, 'Lưu trữ mẫu và tài liệu kỹ thuật', 'Lưu giữ mẫu và các tài liệu kỹ thuật liên quan');

-- Nhóm Quy trình
INSERT INTO tieuchi_dept (dept, thutu, noidung, giai_thich) VALUES
('chuanbi_sanxuat_phong_kt', 11, 'Xây dựng quy trình may', 'Thiết lập quy trình may chi tiết cho sản phẩm'),
('chuanbi_sanxuat_phong_kt', 12, 'Tính thời gian may', 'Tính toán thời gian may cho từng công đoạn'),
('chuanbi_sanxuat_phong_kt', 13, 'Chuẩn bị bảng hướng dẫn may', 'Tạo bảng hướng dẫn may chi tiết cho công nhân'),
('chuanbi_sanxuat_phong_kt', 14, 'Kiểm tra thiết bị và công cụ', 'Đảm bảo đầy đủ thiết bị và công cụ cần thiết'),
('chuanbi_sanxuat_phong_kt', 15, 'Đào tạo công nhân mới', 'Hướng dẫn và đào tạo công nhân về quy trình mới');

-- Thêm các tiêu chí bắt buộc hình ảnh
INSERT INTO required_images_criteria (dept, id_tieuchi, thutu, noidung) VALUES
('chuanbi_sanxuat_phong_kt', 6, 6, 'May mẫu đầu tiên'),
('chuanbi_sanxuat_phong_kt', 8, 8, 'Đối chiếu mẫu với size spec'),
('chuanbi_sanxuat_phong_kt', 13, 13, 'Chuẩn bị bảng hướng dẫn may'); 