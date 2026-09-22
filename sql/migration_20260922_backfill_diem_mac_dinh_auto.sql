-- Backfill điểm cho tiêu chí "bắt buộc ảnh + mốc điểm không có mốc 0".
--
-- Bối cảnh: trước fix, JS checkImageForTieuchi() reset dropdown về 0 ngay lúc load
-- trang cho mọi tiêu chí bắt buộc ảnh chưa có ảnh, kể cả tiêu chí được hệ thống chấm
-- sẵn ở mốc mặc định >= 1. Giao diện hiện 3 nhưng bản ghi lưu NULL/0 → báo cáo và
-- dashboard tính thiếu điểm. Script này kéo dữ liệu cũ về đúng điểm mặc định.
--
-- CHẠY THỦ CÔNG, sau khi đã backup bảng danhgia_tieuchi.
-- KHÔNG idempotent-nguy hiểm: chạy lại nhiều lần cho cùng kết quả, nhưng sẽ ghi đè
-- cả những dòng user cố ý chấm 0 (mốc 0 vẫn tồn tại trong dropdown do
-- ensureZeroScoreOption() chèn vào). Trước fix user không lưu được mốc khác 0 cho
-- các tiêu chí này nên mọi 0 hiện có đều là hệ quả của bug.

-- 1) Xem phạm vi trước khi chạy: các tiêu chí dính lỗi.
SELECT tc.dept, tc.id AS id_tieuchi, tc.thutu, MIN(so.score_value) AS default_score,
       COUNT(DISTINCT so.id) AS so_moc_diem
FROM tieuchi_dept tc
JOIN required_images_criteria ri ON ri.id_tieuchi = tc.id AND ri.dept = tc.dept
JOIN tieuchi_score_options so    ON so.id_tieuchi = tc.id AND so.active = 1
GROUP BY tc.dept, tc.id, tc.thutu
HAVING MIN(so.score_value) >= 1;

-- 2) Đếm số bản ghi sẽ bị sửa.
SELECT dg.id_tieuchi, COUNT(*) AS so_ban_ghi
FROM danhgia_tieuchi dg
JOIN tieuchi_dept tc             ON tc.id = dg.id_tieuchi
JOIN required_images_criteria ri ON ri.id_tieuchi = tc.id AND ri.dept = tc.dept
JOIN (
    SELECT so.id_tieuchi, MIN(so.score_value) AS default_score
    FROM tieuchi_score_options so
    WHERE so.active = 1
    GROUP BY so.id_tieuchi
    HAVING MIN(so.score_value) >= 1
) d ON d.id_tieuchi = dg.id_tieuchi
WHERE dg.diem_danhgia IS NULL OR dg.diem_danhgia = 0
GROUP BY dg.id_tieuchi;

-- 3) Backfill.
-- Điểm mặc định ở đây lấy MIN(score_value); PHP getDefaultScoreValueForCriteria() lấy
-- mốc đầu theo (sort_order, score_value). Hai cách trùng nhau khi mốc điểm được sắp
-- tăng dần — đúng với dữ liệu hiện tại (mỗi tiêu chí dính lỗi chỉ có 1 mốc active).
-- Nếu bước (1) trả về tiêu chí có so_moc_diem > 1, kiểm tra lại sort_order trước khi chạy.
UPDATE danhgia_tieuchi dg
JOIN tieuchi_dept tc             ON tc.id = dg.id_tieuchi
JOIN required_images_criteria ri ON ri.id_tieuchi = tc.id AND ri.dept = tc.dept
JOIN (
    SELECT so.id_tieuchi, MIN(so.score_value) AS default_score
    FROM tieuchi_score_options so
    WHERE so.active = 1
    GROUP BY so.id_tieuchi
    HAVING MIN(so.score_value) >= 1
) d ON d.id_tieuchi = dg.id_tieuchi
SET dg.diem_danhgia = d.default_score,
    dg.da_thuchien  = 1
WHERE dg.diem_danhgia IS NULL OR dg.diem_danhgia = 0;
