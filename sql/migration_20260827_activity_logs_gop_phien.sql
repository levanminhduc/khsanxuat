-- =====================================================================
-- Migration 2026-08-27: index phuc vu gop log theo phien
-- helpers/activity_logger.php tim dong log gan nhat cua cung
-- (id_khsanxuat, dept, user_name) trong cua so 30 phut truoc MOI lan ghi log.
-- Khong co index nay MySQL chay index_merge + filesort tren moi luot cham diem.
-- Idempotent: chay lai nhieu lan van exit 0.
-- =====================================================================

SET @idx_merge_exists = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'activity_logs'
    AND INDEX_NAME = 'idx_merge'
);

SET @sql_add_idx_merge = IF(
  @idx_merge_exists = 0,
  'ALTER TABLE activity_logs ADD INDEX idx_merge (id_khsanxuat, dept, user_name, action_time)',
  'DO 0'
);

PREPARE stmt_add_idx_merge FROM @sql_add_idx_merge;
EXECUTE stmt_add_idx_merge;
DEALLOCATE PREPARE stmt_add_idx_merge;
