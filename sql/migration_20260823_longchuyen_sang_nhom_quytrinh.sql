-- =====================================================================
-- Migration 2026-08-23: chuyen 3 tieu chi "long chuyen" (30/80/130pcs)
-- tu nhom "d. Ky Thuat Chuyen" sang "c. Nhom Quy Trinh Cong Nghe, Thiet Ke Chuyen"
-- + doi so ngay xu ly, nguoi chiu trach nhiem, va tao dong danh gia cho don da co.
--
-- Id tren local: 166 = 30pcs, 170 = 80pcs, 171 = 130pcs; 157 = muc 2 cua nhom dich.
-- CHAY BUOC 0 TRUOC de xac nhan id tren prod trung khop; neu khac thi thay so trong toan bo file.
-- =====================================================================

-- ---------- BUOC 0: kiem tra id ----------
SELECT id, nhom, thutu, noidung FROM tieuchi_dept
WHERE dept = 'chuanbi_sanxuat_phong_kt' AND noidung LIKE 'Tiến hành lồng chuyền%';

SELECT id, thutu, noidung FROM tieuchi_dept
WHERE dept = 'chuanbi_sanxuat_phong_kt' AND nhom = 'Nhóm Quy Trình' ORDER BY thutu;

SELECT DISTINCT xuong FROM default_settings WHERE dept = 'chuanbi_sanxuat_phong_kt';

SELECT id_tieuchi, xuong, nguoi_chiu_trachnhiem_default
FROM default_settings WHERE dept = 'chuanbi_sanxuat_phong_kt' AND id_tieuchi IN (30, 157);

-- Ky vong: chi co 'ngay_vao'. Neu co gia tri khac thi BUOC 5 se khong dung cho cac dong do.
SELECT ngay_tinh_han, COUNT(*) FROM danhgia_tieuchi
WHERE id_tieuchi IN (166, 170, 171) GROUP BY ngay_tinh_han;

-- ---------- BUOC 1: backup ----------
CREATE TABLE bak_tieuchi_dept_20260823       AS SELECT * FROM tieuchi_dept       WHERE id IN (158, 166, 170, 171);
CREATE TABLE bak_default_settings_20260823   AS SELECT * FROM default_settings   WHERE id_tieuchi IN (166, 170, 171);
CREATE TABLE bak_default_nguoi_line_20260823 AS SELECT * FROM default_nguoi_line WHERE dept = 'chuanbi_sanxuat_phong_kt' AND id_tieuchi IN (166, 170, 171);
CREATE TABLE bak_danhgia_tieuchi_20260823    AS SELECT * FROM danhgia_tieuchi    WHERE id_tieuchi IN (166, 170, 171);

START TRANSACTION;

-- ---------- BUOC 2: chuyen nhom + thu tu ----------
-- "Can bang chuyen..." xuong cuoi de nhuong cho 3 tieu chi long chuyen o vi tri 3,4,5
UPDATE tieuchi_dept SET thutu = 6 WHERE id = 158;
UPDATE tieuchi_dept SET nhom = 'Nhóm Quy Trình', thutu = 3 WHERE id = 166;  -- 30pcs
UPDATE tieuchi_dept SET nhom = 'Nhóm Quy Trình', thutu = 4 WHERE id = 170;  -- 80pcs
UPDATE tieuchi_dept SET nhom = 'Nhóm Quy Trình', thutu = 5 WHERE id = 171;  -- 130pcs

-- ---------- BUOC 3: so ngay xu ly mac dinh (30pcs -> 6, 80pcs -> 5, 130pcs -> 4) ----------
UPDATE default_settings SET so_ngay_xuly = 6 WHERE id_tieuchi = 166;
UPDATE default_settings SET so_ngay_xuly = 5 WHERE id_tieuchi = 170;
UPDATE default_settings SET so_ngay_xuly = 4 WHERE id_tieuchi = 171;

-- Bo sung dong mac dinh cho moi xuong con thieu (sua danh sach xuong o BUOC 0 neu prod khac)
INSERT INTO default_settings (dept, xuong, id_tieuchi, ngay_tinh_han, so_ngay_xuly, nguoi_chiu_trachnhiem_default)
SELECT 'chuanbi_sanxuat_phong_kt', x.xuong, t.id_tieuchi, 'ngay_vao', t.so_ngay, 0
FROM (SELECT '' AS xuong UNION ALL SELECT 'DỆT KIM' UNION ALL SELECT 'NHẬT' UNION ALL SELECT 'TRƯỚC') x
JOIN (SELECT 166 AS id_tieuchi, 6 AS so_ngay UNION ALL SELECT 170, 5 UNION ALL SELECT 171, 4) t
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT xuong, id_tieuchi FROM default_settings WHERE dept = 'chuanbi_sanxuat_phong_kt') d
    WHERE d.xuong = x.xuong AND d.id_tieuchi = t.id_tieuchi
);

-- ---------- BUOC 4: nguoi chiu trach nhiem mac dinh = giong muc 2 cua nhom dich ----------
UPDATE default_settings ds
JOIN (SELECT xuong, nguoi_chiu_trachnhiem_default FROM (
          SELECT xuong, nguoi_chiu_trachnhiem_default FROM default_settings
          WHERE dept = 'chuanbi_sanxuat_phong_kt' AND id_tieuchi = 157
      ) tmp) src ON src.xuong = ds.xuong
SET ds.nguoi_chiu_trachnhiem_default = src.nguoi_chiu_trachnhiem_default
WHERE ds.dept = 'chuanbi_sanxuat_phong_kt' AND ds.id_tieuchi IN (166, 170, 171);

-- Muc 2 khong co override theo line -> bo override cua 3 muc nay cho dong nhat
DELETE FROM default_nguoi_line
WHERE dept = 'chuanbi_sanxuat_phong_kt' AND id_tieuchi IN (166, 170, 171);

-- ---------- BUOC 5: don hang da co - so ngay + han xu ly (han = ngay vao - so ngay) ----------
UPDATE danhgia_tieuchi d JOIN khsanxuat k ON k.stt = d.id_sanxuat
SET d.so_ngay_xuly = 6, d.han_xuly = DATE_SUB(k.ngayin, INTERVAL 6 DAY)
WHERE d.id_tieuchi = 166 AND d.ngay_tinh_han = 'ngay_vao' AND k.ngayin IS NOT NULL;

UPDATE danhgia_tieuchi d JOIN khsanxuat k ON k.stt = d.id_sanxuat
SET d.so_ngay_xuly = 5, d.han_xuly = DATE_SUB(k.ngayin, INTERVAL 5 DAY)
WHERE d.id_tieuchi = 170 AND d.ngay_tinh_han = 'ngay_vao' AND k.ngayin IS NOT NULL;

UPDATE danhgia_tieuchi d JOIN khsanxuat k ON k.stt = d.id_sanxuat
SET d.so_ngay_xuly = 4, d.han_xuly = DATE_SUB(k.ngayin, INTERVAL 4 DAY)
WHERE d.id_tieuchi = 171 AND d.ngay_tinh_han = 'ngay_vao' AND k.ngayin IS NOT NULL;

-- ---------- BUOC 6: don hang da co - nguoi thuc hien giong muc 2 cua cung don ----------
UPDATE danhgia_tieuchi d
JOIN danhgia_tieuchi src ON src.id_sanxuat = d.id_sanxuat AND src.id_tieuchi = 157
SET d.nguoi_thuchien = src.nguoi_thuchien
WHERE d.id_tieuchi IN (166, 170, 171);

-- Don khong co muc 2 -> lay theo mac dinh cua xuong
UPDATE danhgia_tieuchi d
JOIN khsanxuat k ON k.stt = d.id_sanxuat
JOIN default_settings ds ON ds.dept = 'chuanbi_sanxuat_phong_kt'
     AND ds.id_tieuchi = d.id_tieuchi AND ds.xuong = k.xuong
SET d.nguoi_thuchien = ds.nguoi_chiu_trachnhiem_default
WHERE d.id_tieuchi IN (166, 170, 171) AND d.nguoi_thuchien IS NULL;

-- ---------- BUOC 7: tao dong danh gia cho muc 4,5 o cac don da co muc 3 ----------
INSERT INTO danhgia_tieuchi
  (id_sanxuat, id_tieuchi, nguoi_thuchien, da_thuchien, ghichu, dept, diem_danhgia, han_xuly, so_ngay_xuly, ngay_tinh_han, is_custom)
SELECT s.id_sanxuat, t.id_tieuchi, s.nguoi_thuchien, 0, NULL, s.dept, NULL,
       DATE_SUB(k.ngayin, INTERVAL t.so_ngay DAY), t.so_ngay, 'ngay_vao', 0
FROM danhgia_tieuchi s
JOIN khsanxuat k ON k.stt = s.id_sanxuat
JOIN (SELECT 170 AS id_tieuchi, 5 AS so_ngay UNION ALL SELECT 171, 4) t
WHERE s.id_tieuchi = 166 AND k.ngayin IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM (SELECT id_sanxuat, id_tieuchi FROM danhgia_tieuchi WHERE id_tieuchi IN (170, 171)) e
      WHERE e.id_sanxuat = s.id_sanxuat AND e.id_tieuchi = t.id_tieuchi
  );

COMMIT;

-- ---------- BUOC 8: kiem tra ----------
SELECT id, thutu, LEFT(noidung, 50) FROM tieuchi_dept
WHERE dept = 'chuanbi_sanxuat_phong_kt' AND nhom = 'Nhóm Quy Trình' ORDER BY thutu;

SELECT ds.id_tieuchi, ds.xuong, ds.so_ngay_xuly, ds.nguoi_chiu_trachnhiem_default, nv.ten
FROM default_settings ds LEFT JOIN nhan_vien nv ON nv.id = ds.nguoi_chiu_trachnhiem_default
WHERE ds.dept = 'chuanbi_sanxuat_phong_kt' AND ds.id_tieuchi IN (166, 170, 171)
ORDER BY ds.id_tieuchi, ds.xuong;

SELECT id_tieuchi, so_ngay_xuly, nguoi_thuchien, COUNT(*)
FROM danhgia_tieuchi WHERE id_tieuchi IN (166, 170, 171) GROUP BY 1, 2, 3 ORDER BY 1, 3;

-- =====================================================================
-- ROLLBACK (chay theo dung thu tu duoi day khi can hoan tac)
-- =====================================================================
-- DELETE FROM danhgia_tieuchi WHERE id_tieuchi IN (170, 171)
--   AND id NOT IN (SELECT id FROM bak_danhgia_tieuchi_20260823);
-- UPDATE danhgia_tieuchi d JOIN bak_danhgia_tieuchi_20260823 b ON b.id = d.id
--   SET d.so_ngay_xuly = b.so_ngay_xuly, d.han_xuly = b.han_xuly, d.nguoi_thuchien = b.nguoi_thuchien;
-- DELETE FROM default_settings WHERE id_tieuchi IN (166, 170, 171)
--   AND id NOT IN (SELECT id FROM bak_default_settings_20260823);
-- UPDATE default_settings ds JOIN bak_default_settings_20260823 b ON b.id = ds.id
--   SET ds.so_ngay_xuly = b.so_ngay_xuly, ds.ngay_tinh_han = b.ngay_tinh_han,
--       ds.nguoi_chiu_trachnhiem_default = b.nguoi_chiu_trachnhiem_default;
-- INSERT INTO default_nguoi_line SELECT * FROM bak_default_nguoi_line_20260823;
-- UPDATE tieuchi_dept t JOIN bak_tieuchi_dept_20260823 b ON b.id = t.id
--   SET t.nhom = b.nhom, t.thutu = b.thutu;
