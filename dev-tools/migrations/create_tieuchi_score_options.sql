CREATE TABLE IF NOT EXISTS tieuchi_score_options (
    id INT NOT NULL AUTO_INCREMENT,
    id_tieuchi INT NOT NULL,
    score_value DECIMAL(6,2) UNSIGNED NOT NULL,
    label VARCHAR(50) DEFAULT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tieuchi_score_value (id_tieuchi, score_value),
    UNIQUE KEY uq_tieuchi_sort_order (id_tieuchi, sort_order),
    KEY idx_tieuchi_active_sort (id_tieuchi, active, sort_order),
    CONSTRAINT fk_score_options_tieuchi
        FOREIGN KEY (id_tieuchi)
        REFERENCES tieuchi_dept (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tieuchi_score_options (id_tieuchi, score_value, label, sort_order)
SELECT tc.id, options.score_value, options.label, options.sort_order
FROM tieuchi_dept tc
JOIN (
    SELECT 0.00 AS score_value, '0' AS label, 1 AS sort_order
    UNION ALL SELECT 0.50, '0.5', 2
    UNION ALL SELECT 1.50, '1.5', 3
) options
LEFT JOIN (
    SELECT DISTINCT id_tieuchi
    FROM tieuchi_score_options
) configured ON configured.id_tieuchi = tc.id
WHERE tc.dept = 'kehoach'
  AND tc.thutu IN (7, 8)
  AND configured.id_tieuchi IS NULL;
