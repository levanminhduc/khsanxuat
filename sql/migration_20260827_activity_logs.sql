-- =====================================================================
-- Migration 2026-08-27: activity_logs
-- Chuyen DDL tu helpers/activity_logger.php (chay CREATE/ALTER moi request) sang file nay.
-- Idempotent: chay lai nhieu lan van exit 0, khong hong du lieu.
-- =====================================================================

-- ---------- BUOC 1: tao bang (cai dat moi) ----------
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(50) NOT NULL,
  user_full_name VARCHAR(100) NOT NULL,
  action_type ENUM('update_score','update_person','update_note','update_multiple',
                   'add_image','add_template','delete_image','delete_template') NOT NULL,
  target_type ENUM('tieuchi','image','template') NOT NULL,
  target_id INT NOT NULL,
  id_khsanxuat INT NOT NULL,
  dept VARCHAR(50) NOT NULL COMMENT 'Ma bo phan, xem includes/indexdept/config.php',
  old_value TEXT NULL,
  new_value TEXT NULL,
  action_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  additional_info JSON NULL,
  INDEX idx_user (user_name),
  INDEX idx_action (action_type),
  INDEX idx_target (target_type, target_id),
  INDEX idx_khsanxuat (id_khsanxuat),
  INDEX idx_dept (dept),
  INDEX idx_action_time (action_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- BUOC 2: bo sung index cho DB da ton tai ----------
-- MySQL khong co CREATE INDEX IF NOT EXISTS => tra information_schema roi ADD INDEX dong,
-- neu khong file se chet ERROR 1061 o lan chay thu hai.
-- Trang activity log sap xep ORDER BY action_time DESC + phan trang => can index nay.
SET @idx_action_time_exists = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'activity_logs'
    AND INDEX_NAME = 'idx_action_time'
);

SET @sql_add_idx_action_time = IF(
  @idx_action_time_exists = 0,
  'ALTER TABLE activity_logs ADD INDEX idx_action_time (action_time)',
  'DO 0'
);

PREPARE stmt_add_idx_action_time FROM @sql_add_idx_action_time;
EXECUTE stmt_add_idx_action_time;
DEALLOCATE PREPARE stmt_add_idx_action_time;
