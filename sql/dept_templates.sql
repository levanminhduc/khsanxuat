-- Tạo bảng template + file cho chức năng Quản lý biểu mẫu (file_templates.php).
-- Chạy thủ công 1 lần (Laragon → HeidiSQL/phpMyAdmin) nếu bảng chưa tồn tại.
-- DDL đã được bỏ khỏi request path; trang giả định 2 bảng dưới đã có sẵn.

CREATE TABLE IF NOT EXISTS dept_templates (
    id INT(11) NOT NULL AUTO_INCREMENT,
    dept VARCHAR(50) NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    template_description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_dept (dept)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dept_template_files (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_template INT(11) NOT NULL,
    id_khsanxuat INT(11) NOT NULL,
    dept VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_id_template (id_template),
    KEY idx_id_khsanxuat (id_khsanxuat),
    KEY idx_dept (dept)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
