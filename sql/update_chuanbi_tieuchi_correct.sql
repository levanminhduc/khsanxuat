-- Xóa dữ liệu đánh giá cũ trước
DELETE FROM danhgia_tieuchi 
WHERE id_tieuchi IN (
    SELECT id FROM tieuchi_dept 
    WHERE dept = 'chuanbi_sanxuat_phong_kt'
);

-- Xóa các tiêu chí bắt buộc hình ảnh cũ
DELETE FROM required_images_criteria 
WHERE dept = 'chuanbi_sanxuat_phong_kt';

-- Xóa các tiêu chí cũ của bộ phận chuẩn bị sản xuất
DELETE FROM tieuchi_dept 
WHERE dept = 'chuanbi_sanxuat_phong_kt';

-- Thêm các tiêu chí mới theo nhóm
-- Nhóm Nghiệp Vụ (17 tiêu chí)
INSERT INTO tieuchi_dept (dept, thutu, nhom, noidung) VALUES
('chuanbi_sanxuat_phong_kt', 1, 'Nhóm Nghiệp Vụ', 'Nhận kế hoạch sản xuất chi tiết từ bộ phận kế hoạch'),
('chuanbi_sanxuat_phong_kt', 2, 'Nhóm Nghiệp Vụ', 'Nhận tài liệu gốc từ Phòng KD May hoặc khách hàng'),
('chuanbi_sanxuat_phong_kt', 3, 'Nhóm Nghiệp Vụ', 'Theo dõi tình trạng đồng bộ NPL may mẫu'),
('chuanbi_sanxuat_phong_kt', 4, 'Nhóm Nghiệp Vụ', 'Nhận rập gốc từ Phòng KD May hoặc khách hàng'),
('chuanbi_sanxuat_phong_kt', 5, 'Nhóm Nghiệp Vụ', 'Kiểm tra độ khớp, thông số của rập'),
('chuanbi_sanxuat_phong_kt', 6, 'Nhóm Nghiệp Vụ', 'Dịch và cập nhật các Comment từ khách hàng'),
('chuanbi_sanxuat_phong_kt', 7, 'Nhóm Nghiệp Vụ', 'Làm bảng màu gửi khách duyệt'),
('chuanbi_sanxuat_phong_kt', 8, 'Nhóm Nghiệp Vụ', 'Xây dựng bảng quy định chi tiết ép keo'),
('chuanbi_sanxuat_phong_kt', 9, 'Nhóm Nghiệp Vụ', 'Ban hành các quy định cho bộ phận ép keo'),
('chuanbi_sanxuat_phong_kt', 10, 'Nhóm Nghiệp Vụ', 'Cung cấp các loại rập cho bộ phận may'),
('chuanbi_sanxuat_phong_kt', 11, 'Nhóm Nghiệp Vụ', 'Xây dựng định mức NPL'),
('chuanbi_sanxuat_phong_kt', 12, 'Nhóm Nghiệp Vụ', 'Kiểm tra tài liệu kỹ thuật'),
('chuanbi_sanxuat_phong_kt', 13, 'Nhóm Nghiệp Vụ', 'Kiểm tra chất lượng mẫu'),
('chuanbi_sanxuat_phong_kt', 14, 'Nhóm Nghiệp Vụ', 'Cập nhật các thay đổi kỹ thuật'),
('chuanbi_sanxuat_phong_kt', 15, 'Nhóm Nghiệp Vụ', 'Xây dựng quy trình may chi tiết'),
('chuanbi_sanxuat_phong_kt', 16, 'Nhóm Nghiệp Vụ', 'Tính thời gian may cho từng công đoạn'),
('chuanbi_sanxuat_phong_kt', 17, 'Nhóm Nghiệp Vụ', 'Chuẩn bị bảng hướng dẫn may');

-- Nhóm May Mẫu (6 tiêu chí)
INSERT INTO tieuchi_dept (dept, thutu, nhom, noidung) VALUES
('chuanbi_sanxuat_phong_kt', 18, 'Nhóm May Mẫu', 'May mẫu đầu tiên theo yêu cầu'),
('chuanbi_sanxuat_phong_kt', 19, 'Nhóm May Mẫu', 'Kiểm tra kỹ thuật may của mẫu'),
('chuanbi_sanxuat_phong_kt', 20, 'Nhóm May Mẫu', 'Đối chiếu mẫu với size spec'),
('chuanbi_sanxuat_phong_kt', 21, 'Nhóm May Mẫu', 'Điều chỉnh mẫu theo yêu cầu'),
('chuanbi_sanxuat_phong_kt', 22, 'Nhóm May Mẫu', 'Lưu trữ mẫu và tài liệu kỹ thuật'),
('chuanbi_sanxuat_phong_kt', 23, 'Nhóm May Mẫu', 'Kiểm tra thiết bị và công cụ cần thiết');

-- Nhóm Quy Trình (3 tiêu chí)
INSERT INTO tieuchi_dept (dept, thutu, nhom, noidung) VALUES
('chuanbi_sanxuat_phong_kt', 24, 'Nhóm Quy Trình', 'Đào tạo công nhân về quy trình mới'),
('chuanbi_sanxuat_phong_kt', 25, 'Nhóm Quy Trình', 'Theo dõi và điều chỉnh quy trình'),
('chuanbi_sanxuat_phong_kt', 26, 'Nhóm Quy Trình', 'Cập nhật và lưu trữ các cải tiến');

-- Cập nhật các tiêu chí bắt buộc có hình ảnh
INSERT INTO required_images_criteria (dept, id_tieuchi) 
SELECT 'chuanbi_sanxuat_phong_kt', id 
FROM tieuchi_dept 
WHERE dept = 'chuanbi_sanxuat_phong_kt' 
AND thutu IN (18, 20, 17) -- May mẫu đầu tiên, Đối chiếu mẫu với size spec, Chuẩn bị bảng hướng dẫn may 