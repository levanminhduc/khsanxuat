<?php
require "contdb.php";

function checkAllCriteriaCompleted($connect, $id_sanxuat, $dept) {
    // Đếm tổng số tiêu chí của bộ phận
    $sql_count = "SELECT COUNT(*) as total FROM tieuchi_dept WHERE dept = ?";
    $stmt_count = $connect->prepare($sql_count);
    $stmt_count->bind_param("s", $dept);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_row = $result_count->fetch_assoc();
    $total_criteria = $total_row['total'];

    // Đếm số tiêu chí đã hoàn thành
    $sql_completed = "SELECT COUNT(*) as completed 
                     FROM danhgia_tieuchi dg 
                     JOIN tieuchi_dept tc ON dg.id_tieuchi = tc.id 
                     WHERE dg.id_sanxuat = ? 
                     AND tc.dept = ? 
                     AND dg.da_thuchien = 1";
    $stmt_completed = $connect->prepare($sql_completed);
    $stmt_completed->bind_param("is", $id_sanxuat, $dept);
    $stmt_completed->execute();
    $result_completed = $stmt_completed->get_result();
    $completed_row = $result_completed->fetch_assoc();
    $completed_criteria = $completed_row['completed'];

    // Trả về true nếu tất cả tiêu chí đã hoàn thành
    return ($total_criteria > 0 && $total_criteria == $completed_criteria);
}

try {
    // Lấy tất cả các mã sản xuất có trong dept_status
    $sql = "SELECT DISTINCT id_sanxuat, dept FROM dept_status WHERE completed = 1";
    $result = $connect->query($sql);

    while ($row = $result->fetch_assoc()) {
        $id_sanxuat = $row['id_sanxuat'];
        $dept = $row['dept'];

        // Kiểm tra lại tất cả tiêu chí
        $all_completed = checkAllCriteriaCompleted($connect, $id_sanxuat, $dept);

        // Nếu không còn hoàn thành tất cả, cập nhật lại trạng thái
        if (!$all_completed) {
            $sql_update = "UPDATE dept_status 
                          SET completed = 0, 
                              completed_date = NULL 
                          WHERE id_sanxuat = ? 
                          AND dept = ?";
            $stmt_update = $connect->prepare($sql_update);
            $stmt_update->bind_param("is", $id_sanxuat, $dept);
            $stmt_update->execute();

            // Log để theo dõi
            $log_message = date('Y-m-d H:i:s') . " - Reset completion status for ID: $id_sanxuat, Dept: $dept\n";
            file_put_contents('completion_check.log', $log_message, FILE_APPEND);
        }
    }

    echo "Kiểm tra hoàn thành thành công!";

} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
    file_put_contents('completion_check.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
} 