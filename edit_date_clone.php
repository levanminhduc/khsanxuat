<?php
// Kết nối database
include 'db_connect.php';

// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Lấy ID từ tham số URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

/**
 * Tính toán lại ngày hạn xử lý dựa trên ngày in và ngày ra mới
 *
 * @param string $ngay_vao - Ngày in
 * @param string $ngay_ra - Ngày ra
 * @param string $ngay_tinh_han - Phương thức tính (ngay_vao, ngay_vao_cong, ngay_ra, ngay_ra_tru)
 * @param int $so_ngay_xuly - Số ngày xử lý
 * @return string - Ngày hạn xử lý mới (Y-m-d)
 */
function calculateDeadline($ngay_vao, $ngay_ra, $ngay_tinh_han, $so_ngay_xuly)
{
    // Nếu không có ngày vào, trả về null
    if (empty($ngay_vao)) {
        return null;
    }

    // Khởi tạo biến ngày hạn
    $deadline = null;

    // Chuyển đổi ngày vào thành đối tượng DateTime
    try {
        $ngay_vao_date = new DateTime($ngay_vao);
    } catch (Exception $e) {
        return null;
    }

    // Chuyển đổi ngày ra thành đối tượng DateTime nếu có
    $ngay_ra_date = null;
    if (!empty($ngay_ra)) {
        try {
            $ngay_ra_date = new DateTime($ngay_ra);
        } catch (Exception $e) {
            // Không làm gì, sử dụng ngày vào
        }
    }

    // Tính toán hạn xử lý dựa trên loại tính hạn
    switch ($ngay_tinh_han) {
        case 'ngay_vao':
            $deadline = clone $ngay_vao_date;
            $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            break;
        case 'ngay_vao_cong':
            $deadline = clone $ngay_vao_date;
            $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            break;
        case 'ngay_ra':
            if ($ngay_ra_date) {
                $deadline = clone $ngay_ra_date;
                $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            } else {
                // Fallback về ngày vào nếu không có ngày ra
                $deadline = clone $ngay_vao_date;
                $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            }
            break;
        case 'ngay_ra_tru':
            if ($ngay_ra_date) {
                $deadline = clone $ngay_ra_date;
                $deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
            } else {
                // Fallback về ngày vào nếu không có ngày ra
                $deadline = clone $ngay_vao_date;
                $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            }
            break;
        default:
            $deadline = clone $ngay_vao_date;
            $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            break;
    }

    return $deadline ? $deadline->format('Y-m-d') : null;
}

/**
 * Cập nhật tất cả các ngày hạn của bộ phận dựa trên ngày in và ngày ra mới
 */
function updateDeptDeadlines($connect, $id_sanxuat, $new_ngayin, $new_ngayout)
{
    $updated = 0;

    // Lấy tất cả tiêu chí đánh giá của mã hàng này
    $sql = "SELECT dt.id, dt.id_tieuchi, dt.so_ngay_xuly, dt.ngay_tinh_han, tc.dept
            FROM danhgia_tieuchi dt
            JOIN tieuchi_dept tc ON dt.id_tieuchi = tc.id
            WHERE dt.id_sanxuat = ?";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id_sanxuat);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Chuẩn bị câu lệnh cập nhật
        $update_sql = "UPDATE danhgia_tieuchi SET han_xuly = ? WHERE id = ?";
        $update_stmt = $connect->prepare($update_sql);

        while ($row = $result->fetch_assoc()) {
            // Tính toán lại hạn xử lý dựa trên ngày mới
            $new_deadline = calculateDeadline(
                $new_ngayin,
                $new_ngayout,
                $row['ngay_tinh_han'],
                $row['so_ngay_xuly']
            );

            if ($new_deadline) {
                $update_stmt->bind_param("si", $new_deadline, $row['id']);
                $update_stmt->execute();

                if ($update_stmt->affected_rows > 0) {
                    $updated++;
                }
            }
        }
    }

    return $updated;
}

// Kiểm tra nếu có ID
if ($id <= 0) {
    $error = "ID không hợp lệ.";
} else {
    // Lấy thông tin mã hàng
    $sql = "SELECT * FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item_data = $result->fetch_assoc();
    } else {
        $error = "Không tìm thấy mã hàng với ID: " . $id;
        $item_data = null;
    }
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_date']) && $item_data) {
    $new_date = $_POST['new_date'];
    $new_qty = isset($_POST['new_qty']) ? $_POST['new_qty'] : '';
    $new_line = isset($_POST['new_line']) ? trim($_POST['new_line']) : '';

    // Validate ngày
    $date_valid = DateTime::createFromFormat('Y-m-d', $new_date) !== false;

    // Validate số lượng
    $qty_valid = is_numeric($new_qty) && $new_qty > 0 && $new_qty == (int)$new_qty;

    // Validate LINE
    $line_valid = is_numeric($new_line) && $new_line >= 1 && $new_line <= 10 && $new_line == (int)$new_line; // Giới hạn số nguyên từ 1-10

    if ($date_valid && $qty_valid && $line_valid) {
        // Tính toán ngày ra mới dựa trên khoảng thời gian hiện tại
        $current_ngay_vao = new DateTime($item_data['ngayin']);
        $current_ngay_ra = new DateTime($item_data['ngayout']);
        $time_diff = $current_ngay_vao->diff($current_ngay_ra);

        $new_ngay_vao = new DateTime($new_date);
        $new_ngay_ra = clone $new_ngay_vao;
        $new_ngay_ra->add($time_diff);

        // Lưu định dạng ngày thành chuỗi trước khi truyền vào bind_param
        $ngayin_string = $new_date;
        $ngayout_string = $new_ngay_ra->format('Y-m-d');
        $qty_int = (int)$new_qty;
        $line_int = (int)$new_line;

        // Cập nhật database
        $update_sql = "UPDATE khsanxuat SET ngayin = ?, ngayout = ?, qty = ?, line1 = ? WHERE stt = ?";
        $update_stmt = $connect->prepare($update_sql);
        $update_stmt->bind_param("ssiii", $ngayin_string, $ngayout_string, $qty_int, $line_int, $id);

        if ($update_stmt->execute()) {
            // Cập nhật các ngày hạn của các bộ phận
            $updated_deadlines = updateDeptDeadlines($connect, $id, $ngayin_string, $ngayout_string);

            $message = "Đã cập nhật ngày, số lượng và LINE thành công!" . ($updated_deadlines > 0 ? " Cập nhật {$updated_deadlines} hạn xử lý của các bộ phận." : "");

            // Cập nhật lại dữ liệu để hiển thị
            $stmt->execute();
            $result = $stmt->get_result();
            $item_data = $result->fetch_assoc();
        } else {
            $error = "Có lỗi xảy ra khi cập nhật: " . $connect->error;
        }
    } else {
        if (!$date_valid) {
            $error = "Định dạng ngày không hợp lệ.";
        } elseif (!$qty_valid) {
            $error = "Số lượng phải là số nguyên dương.";
        } elseif (!$line_valid) {
            $error = "LINE phải là số nguyên từ 1 đến 10.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật ngày in</title>
    <link rel="stylesheet" href="style.css">
    <!-- Thêm jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- Thêm jQuery và jQuery UI JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

</head>
<body class="edit-date-page">
    <div class="container">
        <h1>Cập nhật ngày vào chuyền</h1>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <!-- Modal thành công sẽ được hiển thị bằng JavaScript -->
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($item_data) : ?>
            <table class="info-table">
                <tr>
                    <th>STT</th>
                    <td><?php echo $item_data['stt']; ?></td>
                </tr>
                <tr>
                    <th>Xưởng</th>
                    <td><?php echo $item_data['xuong']; ?></td>
                </tr>
                <tr>
                    <th>Line</th>
                    <td><?php echo $item_data['line1']; ?></td>
                </tr>
                <tr>
                    <th>PO</th>
                    <td><?php echo $item_data['po']; ?></td>
                </tr>
                <tr>
                    <th>Style</th>
                    <td><?php echo $item_data['style']; ?></td>
                </tr>
                <tr>
                    <th>Số lượng</th>
                    <td><?php echo number_format($item_data['qty']); ?></td>
                </tr>
                <tr>
                    <th>Ngày Vào hiện tại</th>
                    <td class="date-format"><?php echo date('d/m/Y', strtotime($item_data['ngayin'])); ?></td>
                </tr>
                <tr>
                    <th>Ngày Ra hiện tại</th>
                    <td class="date-format"><?php echo date('d/m/Y', strtotime($item_data['ngayout'])); ?></td>
                </tr>
            </table>

            <form method="post" action="" id="date_form">
                <div class="form-group">
                    <label for="date_input">Ngày vào mới (Ngày/Tháng/Năm):</label>

                    <!-- Sử dụng một ô input duy nhất -->
                    <div class="date-input-container" style="position: relative; display: inline-block; width: 43%;">
                        <input type="text" id="date_input" name="date_display" value="<?php echo date('d/m/Y', strtotime($item_data['ngayin'])); ?>" placeholder="DD/MM/YYYY" autocomplete="off" style="width: 100%; padding: 8px 35px 8px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; text-align: center;">
                        <span class="calendar-icon" id="calendar_icon" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 16px; color: #666;">📅</span>
                        <input type="hidden" id="new_date" name="new_date" value="<?php echo date('Y-m-d', strtotime($item_data['ngayin'])); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="qty_input">Số lượng mới:</label>
                    <input type="number" id="qty_input" name="new_qty" value="<?php echo $item_data['qty']; ?>" min="1" step="1" required style="width: 40%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; text-align: center;">
                </div>
                <div class="form-group">
                    <label for="line_input">Line mới (1-10):</label>
                    <input type="number" id="line_input" name="new_line" value="<?php echo htmlspecialchars($item_data['line1']); ?>" placeholder="Nhập Line từ 1-10" min="1" max="10" step="1" required style="width: 40%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; text-align: center;">
                </div>
                <div style="margin-bottom: 15px; padding: 10px; background-color: #d9edf7; border: 1px solid #bce8f1; color: #31708f; border-radius: 4px;">
                    <strong>Lưu ý:</strong> Khi thay đổi thông tin:
                    <ul>
                        <li>Ngày ra sẽ điều chỉnh theo cùng khoảng thời gian với ngày vào</li>
                        <li>Số lượng phải là số nguyên dương</li>
                        <li>Line từ 1 đến 10</li>
                    </ul>
                    <br>Định dạng ngày: <span class="date-format">Ngày/Tháng/Năm</span>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Cập nhật</button>
                    <a href="index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        <?php else : ?>
            <div class="alert alert-danger">Không thể lấy thông tin mã hàng.</div>
            <a href="index.php" class="btn">Quay lại</a>
        <?php endif; ?>
    </div>

    <!-- Modal thành công -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cập nhật thành công</h2>
            </div>
            <div class="modal-body">
                <p>Đã cập nhật ngày thành công!</p>
                <p>Tự động chuyển hướng về trang chủ sau <span id="countdown">3</span> giây...</p>
            </div>
            <div class="modal-footer">
                <button id="redirectNow" class="btn">Về trang chủ ngay</button>
                <button id="stayHere" class="btn btn-secondary">Ở lại trang này</button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const hiddenDateInput = document.getElementById('new_date');
            const displayInput = document.getElementById('date_input');
            const calendarIcon = document.getElementById('calendar_icon');
            const dateForm = document.getElementById('date_form');

            const successModal = document.getElementById('successModal');
            const redirectNow = document.getElementById('redirectNow');
            const stayHere = document.getElementById('stayHere');
            const countdownElement = document.getElementById('countdown');

            // Khởi tạo jQuery UI Datepicker
            $("#date_input").datepicker({
                dateFormat: "dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                yearRange: "2020:2030",
                showButtonPanel: true,
                onSelect: function(dateText, inst) {
                    // Chuyển đổi từ dd/mm/yyyy sang yyyy-mm-dd
                    const parts = dateText.split('/');
                    const formattedDate = parts[2] + '-' + parts[1] + '-' + parts[0];
                    hiddenDateInput.value = formattedDate;
                }
            });

            // Xử lý click vào icon lịch
            calendarIcon.addEventListener('click', function() {
                $("#date_input").datepicker('show');
            });

            // Xử lý thay đổi input thủ công
            displayInput.addEventListener('input', function() {
                const value = this.value;
                // Kiểm tra định dạng dd/mm/yyyy
                const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
                const match = value.match(dateRegex);

                if (match) {
                    const day = match[1];
                    const month = match[2];
                    const year = match[3];

                    // Kiểm tra tính hợp lệ của ngày
                    const date = new Date(year, month - 1, day);
                    if (date.getFullYear() == year &&
                        date.getMonth() == month - 1 &&
                        date.getDate() == day) {
                        // Ngày hợp lệ, cập nhật hidden input
                        hiddenDateInput.value = year + '-' + month + '-' + day;
                    }
                }
            });

            // Xử lý submit form
            dateForm.addEventListener('submit', function(e) {
                const dateValue = hiddenDateInput.value;
                const lineValue = document.getElementById('line_input').value.trim();
                const qtyValue = document.getElementById('qty_input').value;
                
                if (!dateValue) {
                    e.preventDefault();
                    alert('Vui lòng chọn ngày hợp lệ.');
                    return false;
                }
                
                if (!lineValue || isNaN(lineValue) || lineValue < 1 || lineValue > 10 || lineValue != parseInt(lineValue)) {
                    e.preventDefault();
                    alert('Line phải là số nguyên từ 1 đến 10.');
                    document.getElementById('line_input').focus();
                    return false;
                }
                
                if (!qtyValue || qtyValue <= 0) {
                    e.preventDefault();
                    alert('Vui lòng nhập số lượng hợp lệ.');
                    document.getElementById('qty_input').focus();
                    return false;
                }
            });

            // Hiển thị modal nếu có thông báo thành công
            <?php if (!empty($message)) : ?>
            successModal.style.display = 'block';

            let countdown = 3;
            const countdownInterval = setInterval(function() {
                countdown--;
                countdownElement.textContent = countdown;

                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = 'index.php';
                }
            }, 1000);

            // Xử lý nút "Về trang chủ ngay"
            redirectNow.addEventListener('click', function() {
                clearInterval(countdownInterval);
                window.location.href = 'index.php';
            });

            // Xử lý nút "Ở lại trang này"
            stayHere.addEventListener('click', function() {
                clearInterval(countdownInterval);
                successModal.style.display = 'none';
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
