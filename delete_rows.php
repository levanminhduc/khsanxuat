<?php
require "contdb.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_rows']) && !empty($_POST['selected_rows'])) {
    $selected_rows = $_POST['selected_rows'];
    $placeholders = str_repeat('?,', count($selected_rows) - 1) . '?';
    
    try {
        // Bắt đầu transaction
        $connect->begin_transaction();
        
        // 1. Xóa dữ liệu từ bảng danhgia_tieuchi trước
        $sql_danhgia = "DELETE FROM danhgia_tieuchi WHERE id_sanxuat IN ($placeholders)";
        $stmt_danhgia = $connect->prepare($sql_danhgia);
        $stmt_danhgia->bind_param(str_repeat('i', count($selected_rows)), ...$selected_rows);
        $stmt_danhgia->execute();
        
        // 2. Xóa dữ liệu từ bảng dept_status
        $sql_dept = "DELETE FROM dept_status WHERE id_sanxuat IN ($placeholders)";
        $stmt_dept = $connect->prepare($sql_dept);
        $stmt_dept->bind_param(str_repeat('i', count($selected_rows)), ...$selected_rows);
        $stmt_dept->execute();
        
        // 3. Cuối cùng xóa dữ liệu từ bảng khsanxuat
        $sql_khsanxuat = "DELETE FROM khsanxuat WHERE stt IN ($placeholders)";
        $stmt_khsanxuat = $connect->prepare($sql_khsanxuat);
        $stmt_khsanxuat->bind_param(str_repeat('i', count($selected_rows)), ...$selected_rows);
        $stmt_khsanxuat->execute();
        
        // Commit transaction nếu tất cả thành công
        $connect->commit();
        header("Location: index.php?success=1&action=delete");
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $connect->rollback();
        header("Location: index.php?error=1&action=delete&message=" . urlencode($e->getMessage()));
    }
} else {
    header("Location: index.php");
}
?> 