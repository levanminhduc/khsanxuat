-- Backfill user_app_role cho cac legacy admin (user.role IN ('admin','super_admin'))
-- chua duoc cap quyen per-app 'khsanxuat'. Sinh ra do migration ban dau chi seed
-- user_id 1 va 3, khien cac admin cu con lai bi 403 (thieu feature edit_settings).
-- An toan chay lai: chi chen dong con thieu nho UNIQUE (user_id, app) + NOT EXISTS.
INSERT INTO user_app_role (user_id, app, role, granted_by, created_at)
SELECT u.id, 'khsanxuat', u.role, NULL, NOW()
FROM `user` u
WHERE u.role IN ('admin', 'super_admin')
  AND NOT EXISTS (
    SELECT 1 FROM user_app_role r
    WHERE r.user_id = u.id AND r.app = 'khsanxuat'
  );
