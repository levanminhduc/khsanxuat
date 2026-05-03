<?php
// Kết nối cơ sở dữ liệu
include 'contdb.php';

// Kiểm tra nếu form được gửi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy tất cả các stt từ cơ sở dữ liệu
    $result = mysqli_query($connect, "SELECT stt FROM khsanxuat");
    
    // Lặp qua tất cả các stt và cập nhật dữ liệu
    while ($row = mysqli_fetch_assoc($result)) {
        $stt = $row['stt']; // Lấy giá trị stt từ cơ sở dữ liệu

        // Lấy dữ liệu từ form
        $chiutn = $_POST['chiutn_' . $stt]; // Người chịu trách nhiệm
        $hoanthanh = $_POST['hoanthanh_' . $stt]; // Trạng thái hoàn thành
        $ghichu = $_POST['ghichu_' . $stt]; // Ghi chú

        // Cập nhật vào cơ sở dữ liệu
        $sql = "UPDATE khsanxuat SET chiutn = '$chiutn', hoanthanh = '$hoanthanh', ghichu = '$ghichu' WHERE stt = $stt";

        // Thực thi câu lệnh SQL
        if (mysqli_query($connect, $sql)) {
            echo "Dữ liệu đã được cập nhật cho STT $stt<br>";
        } else {
            echo "Lỗi cập nhật dữ liệu cho STT $stt: " . mysqli_error($connect) . "<br>";
        }
    }
}

// Đóng kết nối cơ sở dữ liệu
mysqli_close($connect);
?>
