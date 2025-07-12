<?php
// Bật hiển thị lỗi để dễ debug trong quá trình phát triển
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Kiểm tra xem một tiêu chí có yêu cầu hình ảnh không
 * @param object $connect Kết nối database
 * @param string $dept Tên bộ phận
 * @param int $id_tieuchi ID của tiêu chí
 * @return bool True nếu tiêu chí yêu cầu hình ảnh, ngược lại là False
 */
function isRequiredImageCriteria($connect, $dept, $id_tieuchi) {
    $sql = "SELECT * FROM required_images_criteria WHERE dept = ? AND id_tieuchi = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("si", $dept, $id_tieuchi);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Kiểm tra xem một tiêu chí đã có hình ảnh hay chưa
 * @param object $connect Kết nối database
 * @param int $id_khsanxuat ID của kế hoạch sản xuất
 * @param int $id_tieuchi ID của tiêu chí
 * @return bool True nếu tiêu chí đã có hình ảnh, ngược lại là False
 */
function checkTieuchiHasImage($connect, $id_khsanxuat, $id_tieuchi) {
    $sql = "SELECT * FROM khsanxuat_images WHERE id_khsanxuat = ? AND id_tieuchi = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ii", $id_khsanxuat, $id_tieuchi);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Lấy danh sách các tiêu chí yêu cầu hình ảnh cho một bộ phận
 * @param object $connect Kết nối database
 * @param string $dept Tên bộ phận
 * @return array Mảng ID các tiêu chí yêu cầu hình ảnh
 */
function getRequiredImagesCriteria($connect, $dept) {
    $criteria = [];
    $sql = "SELECT id_tieuchi FROM required_images_criteria WHERE dept = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $criteria[] = $row['id_tieuchi'];
    }
    
    return $criteria;
}

/**
 * Tạo URL để tự động chọn tiêu chí hình ảnh
 * @param int $id ID của kế hoạch sản xuất
 * @param string $dept Tên bộ phận
 * @param int $tieuchi_id ID của tiêu chí cần chọn
 * @return string URL đến trang index với tham số tự động chọn tiêu chí
 */
function createAutoSelectImageURL($id, $dept, $tieuchi_id) {
    return "indexdept.php?dept=" . urlencode($dept) . "&id=" . $id . "&autoselect_image=1&tieuchi_id=" . $tieuchi_id;
}

/**
 * Tạo nút upload hình ảnh cho tiêu chí
 * @param int $id ID của kế hoạch sản xuất
 * @param string $dept Tên bộ phận
 * @param int $tieuchi_id ID của tiêu chí
 * @param string $tieuchi_name Tên của tiêu chí (tùy chọn)
 * @return string HTML cho nút upload hình ảnh
 */
function createUploadImageButton($id, $dept, $tieuchi_id, $tieuchi_name = '') {
    $url = createAutoSelectImageURL($id, $dept, $tieuchi_id);
    $button_text = empty($tieuchi_name) ? "Upload hình ảnh" : "Upload hình ảnh cho " . htmlspecialchars($tieuchi_name);
    
    return '<a href="' . $url . '" class="btn-upload-tieuchi"><i class="fas fa-images"></i> ' . $button_text . '</a>';
}
?> 