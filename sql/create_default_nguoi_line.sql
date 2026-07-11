-- Bảng override người thực hiện mặc định theo Xưởng + Line.
-- Chỉ chứa người; số ngày + loại tính hạn vẫn theo default_settings (theo xưởng).
-- Match chính xác (dept, id_tieuchi, xuong, line); không có override thì
-- fallback về nguoi_chiu_trachnhiem_default của default_settings.
CREATE TABLE IF NOT EXISTS default_nguoi_line (
  id INT(11) NOT NULL AUTO_INCREMENT,
  dept VARCHAR(50) NOT NULL,
  id_tieuchi INT(11) NOT NULL,
  xuong VARCHAR(50) NOT NULL,
  line VARCHAR(50) NOT NULL,
  nguoi_id INT(11) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_dept_tieuchi_xuong_line (dept, id_tieuchi, xuong, line)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
