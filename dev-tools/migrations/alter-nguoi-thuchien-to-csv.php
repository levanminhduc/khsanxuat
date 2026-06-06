<?php
/**
 * Migration: đổi cột danhgia_tieuchi.nguoi_thuchien từ INT sang VARCHAR(255)
 * để lưu nhiều người chịu trách nhiệm dạng CSV id ("3,7,12").
 *
 * Idempotent: chỉ ALTER nếu kiểu cột hiện tại còn là int.
 * Dữ liệu cũ (1 id dạng số) tự tương thích khi chuyển sang chuỗi.
 *
 * Cách chạy: php dev-tools/migrations/alter-nguoi-thuchien-to-csv.php
 *            hoặc mở qua trình duyệt.
 */

require_once __DIR__ . '/../../db_connect.php';

if (!$connect) {
    die("Lỗi kết nối database\n");
}

// Lấy kiểu cột hiện tại
$result = $connect->query("SHOW COLUMNS FROM danhgia_tieuchi LIKE 'nguoi_thuchien'");
if (!$result || $result->num_rows === 0) {
    die("Không tìm thấy cột nguoi_thuchien trong bảng danhgia_tieuchi\n");
}

$column = $result->fetch_assoc();
$current_type = strtolower($column['Type']);

// Nếu đã là varchar thì bỏ qua (idempotent)
if (strpos($current_type, 'varchar') !== false) {
    echo "Cột nguoi_thuchien đã là {$column['Type']} — không cần migration.\n";
    exit(0);
}

// Nới sql_mode cho RIÊNG phiên này: bảng có dữ liệu ngày cũ '0000-00-00' ở cột
// khác (han_xuly) khiến strict mode chặn việc rebuild bảng khi ALTER. Chỉ tác động
// session hiện tại, không đổi cấu hình server, không sửa dữ liệu.
$connect->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");

// Đổi sang VARCHAR(255) cho phép NULL
$sql_alter = "ALTER TABLE danhgia_tieuchi MODIFY nguoi_thuchien VARCHAR(255) NULL";
if ($connect->query($sql_alter)) {
    echo "Thành công: đổi nguoi_thuchien từ {$column['Type']} sang VARCHAR(255).\n";
} else {
    die("Lỗi khi ALTER bảng: " . $connect->error . "\n");
}
