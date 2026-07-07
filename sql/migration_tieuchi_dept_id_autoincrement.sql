-- =============================================================================
-- Migration: cho tieuchi_dept.id tu tang (AUTO_INCREMENT)
-- =============================================================================
-- LY DO:
--   Cot tieuchi_dept.id la INT NOT NULL, KHONG auto_increment / khong default.
--   Voi sql_mode STRICT_TRANS_TABLES, form "Them tieu chi" (actions/add_criteria.php)
--   INSERT khong truyen id -> loi "Field 'id' doesn't have a default value"
--   -> that bai voi MOI bo phan.
--
-- CACH LAM AN TOAN (KHONG dung SET FOREIGN_KEY_CHECKS=0):
--   drop 3 FK dang tro vao tieuchi_dept.id -> MODIFY them AUTO_INCREMENT -> re-add lai y nguyen.
--   Khong doi kieu (van INT), khong doi gia tri id cu -> du lieu + FK giu nguyen.
--
-- HAI "QUA MIN" da lam lan chay dau tien dut ganh (DB dich rat co the cung dinh):
--   1. Dong rac id=0: khi MODIFY ...AUTO_INCREMENT, MySQL resequence id=0 -> 1
--      -> trung khoa -> ERROR 1062. Phai doi id=0 sang MAX(id)+1 TRUOC.
--   2. Ngay rac '0000-00-00' trong danhgia_tieuchi.han_xuly: voi sql_mode co
--      NO_ZERO_DATE, luc re-add FK se ERROR 1292. Phai noi sql_mode trong CUNG
--      session re-add FK.
--
-- QUAN TRONG:
--   - CHAY TUNG KHOI theo dung thu tu duoi day. Neu 1 khoi loi -> DUNG, xu ly
--     roi chay tiep; KHONG bo qua.
--   - BACKUP TRUOC (xem docs/huong-dan-migration-tieuchi-autoincrement.md, Buoc 1).
--   - Chay luc vang nguoi dung (ALTER TABLE khoa bang).
-- =============================================================================

-- -----------------------------------------------------------------------------
-- KHOI 0: KIEM TRA 2 QUA MIN TREN DB DICH (chi doc, khong doi gi)
-- -----------------------------------------------------------------------------
-- 0a. Cot id da auto_increment chua? Neu EXTRA co 'auto_increment' -> DA CHAY ROI,
--     DUNG LAI, khong chay tiep cac khoi ben duoi.
SELECT COLUMN_NAME, COLUMN_TYPE, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tieuchi_dept' AND COLUMN_NAME = 'id';

-- 0b. Co dong rac id=0 khong? (quyet dinh co phai chay KHOI 1 hay khong)
SELECT COUNT(*) AS co_id_0 FROM tieuchi_dept WHERE id = 0;

-- 0c. Dong id=0 co con tham chieu khong? Ca 2 phai = 0 thi doi id moi an toan.
SELECT
  (SELECT COUNT(*) FROM danhgia_tieuchi       WHERE id_tieuchi = 0) AS con_danhgia,
  (SELECT COUNT(*) FROM tieuchi_score_options WHERE id_tieuchi = 0) AS con_score_options;

-- 0d. Con ngay rac '0000-00-00' khong? (bao truoc kha nang ERROR 1292 o KHOI 4)
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';
SELECT COUNT(*) AS co_zero_date
FROM danhgia_tieuchi
WHERE CAST(han_xuly AS CHAR) = '0000-00-00';

-- -----------------------------------------------------------------------------
-- KHOI 1: XU LY DONG RAC id=0 (CHI chay khi 0b > 0 VA 0c ca hai = 0)
-- -----------------------------------------------------------------------------
-- Doi id=0 sang MAX(id)+1. Dung bien de tranh loi "update + subquery cung bang".
-- Neu 0c > 0 (co con tham chieu id=0): DUNG, xu ly du lieu con truoc, dung tu y chay.
SET @new_id = (SELECT MAX(id) + 1 FROM tieuchi_dept);
UPDATE tieuchi_dept SET id = @new_id WHERE id = 0;

-- -----------------------------------------------------------------------------
-- KHOI 2: GO 3 FK dang tro vao tieuchi_dept.id (chi go rang buoc, KHONG dung du lieu)
-- -----------------------------------------------------------------------------
ALTER TABLE danhgia_tieuchi        DROP FOREIGN KEY danhgia_tieuchi_ibfk_2;
ALTER TABLE danhgia_tieuchi        DROP FOREIGN KEY fk_danhgia_tieuchi_tieuchi;
ALTER TABLE tieuchi_score_options  DROP FOREIGN KEY fk_score_options_tieuchi;

-- -----------------------------------------------------------------------------
-- KHOI 3: cot cha het bi tham chieu -> them AUTO_INCREMENT
-- -----------------------------------------------------------------------------
ALTER TABLE tieuchi_dept MODIFY id INT NOT NULL AUTO_INCREMENT;

-- -----------------------------------------------------------------------------
-- KHOI 4: GAN LAI 3 FK Y NGUYEN nhu cu
--   PHAI chay dong SET SESSION ngay trong khoi nay (cung session) de noi NO_ZERO_DATE,
--   neu khong dong '0000-00-00' con sot se gay ERROR 1292 khi re-add FK.
--   Giu ca 2 FK trung tren danhgia_tieuchi de bao toan hanh vi (khong don gian hoa).
-- -----------------------------------------------------------------------------
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';

ALTER TABLE danhgia_tieuchi
  ADD CONSTRAINT danhgia_tieuchi_ibfk_2
  FOREIGN KEY (id_tieuchi) REFERENCES tieuchi_dept (id);

ALTER TABLE danhgia_tieuchi
  ADD CONSTRAINT fk_danhgia_tieuchi_tieuchi
  FOREIGN KEY (id_tieuchi) REFERENCES tieuchi_dept (id) ON UPDATE CASCADE;

ALTER TABLE tieuchi_score_options
  ADD CONSTRAINT fk_score_options_tieuchi
  FOREIGN KEY (id_tieuchi) REFERENCES tieuchi_dept (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- -----------------------------------------------------------------------------
-- KHOI 5: VERIFY (chi doc)
-- -----------------------------------------------------------------------------
-- 5a. id da auto_increment chua
SELECT COLUMN_NAME, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tieuchi_dept' AND COLUMN_NAME = 'id';

-- 5b. Du 3 FK chua
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'tieuchi_dept';

-- 5c. Khong con id=0
SELECT COUNT(*) AS con_id_0 FROM tieuchi_dept WHERE id = 0;
