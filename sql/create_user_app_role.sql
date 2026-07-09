-- sql/create_user_app_role.sql
-- Phan quyen per-app dung chung cac du an login qua bang `user`.
-- Convention: app='*' + role='super_admin' = quyen moi app (danh cho trang tong cap quyen sau nay).
-- Khong FK toi user.id: bang user (utf8mb3) dung chung 10 app, tranh rang buoc bao tri cheo.

CREATE TABLE IF NOT EXISTS user_app_role (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  app VARCHAR(50) NOT NULL,
  role VARCHAR(30) NOT NULL,
  granted_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_app (user_id, app),
  KEY idx_app_role (app, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed admin ban dau cho khsanxuat (granted_by NULL = seed bang SQL)
INSERT IGNORE INTO user_app_role (user_id, app, role) VALUES
  (1, 'khsanxuat', 'admin'),   -- duclvm
  (3, 'khsanxuat', 'admin');   -- admin
