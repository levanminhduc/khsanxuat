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
function calculateDeadline($ngay_vao, $ngay_ra, $ngay_tinh_han, $so_ngay_xuly) {
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
            // Ngày vào (trừ số ngày nếu có)
            $deadline = clone $ngay_vao_date;
            if ($so_ngay_xuly > 0) {
                $deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
            }
            break;
            
        case 'ngay_vao_cong':
            // Ngày vào cộng số ngày
            $deadline = clone $ngay_vao_date;
            $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            break;
            
        case 'ngay_ra':
            // Ngày ra
            if ($ngay_ra_date) {
                $deadline = clone $ngay_ra_date;
            } else {
                // Nếu không có ngày ra, sử dụng ngày vào cộng 7 ngày
                $deadline = clone $ngay_vao_date;
                $deadline->add(new DateInterval('P7D'));
            }
            break;
            
        case 'ngay_ra_tru':
            // Ngày ra trừ số ngày
            if ($ngay_ra_date) {
                $deadline = clone $ngay_ra_date;
                $deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
            } else {
                // Nếu không có ngày ra, sử dụng ngày vào cộng 7 ngày trừ số ngày
                $deadline = clone $ngay_vao_date;
                $deadline->add(new DateInterval('P7D'));
                $deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
            }
            break;
            
        default:
            // Mặc định là ngày vào cộng số ngày
            $deadline = clone $ngay_vao_date;
            $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            break;
    }
    
    // Trả về ngày hạn xử lý định dạng Y-m-d
    return $deadline ? $deadline->format('Y-m-d') : null;
}

/**
 * Cập nhật tất cả các ngày hạn của bộ phận dựa trên ngày in và ngày ra mới
 */
function updateDeptDeadlines($connect, $id_sanxuat, $new_ngayin, $new_ngayout) {
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

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_date'])) {
    try {
        // Lấy thông tin hiện tại của mã hàng
        $sql_current = "SELECT ngayin, ngayout FROM khsanxuat WHERE stt = ?";
        $stmt_current = $connect->prepare($sql_current);
        $stmt_current->bind_param("i", $id);
        $stmt_current->execute();
        $result_current = $stmt_current->get_result();
        
        if ($result_current->num_rows > 0) {
            $current_data = $result_current->fetch_assoc();
            $current_ngayin = new DateTime($current_data['ngayin']);
            $current_ngayout = new DateTime($current_data['ngayout']);
            
            // Ngày mới từ form
            $new_ngayin = new DateTime($_POST['new_date']);
            
            // Tính khoảng cách ngày
            $interval = $current_ngayin->diff($new_ngayin);
            $days_diff = $interval->format('%R%a'); // +N hoặc -N ngày
            
            // Cập nhật ngày ra theo cùng khoảng cách
            $new_ngayout = clone $current_ngayout;
            $new_ngayout->modify($days_diff . ' days');
            
            // Lưu định dạng ngày thành chuỗi trước khi truyền vào bind_param
            $ngayin_string = $new_ngayin->format('Y-m-d');
            $ngayout_string = $new_ngayout->format('Y-m-d');
            
            // Bắt đầu transaction để đảm bảo tính nhất quán
            $connect->begin_transaction();
            
            // Cập nhật database
            $sql_update = "UPDATE khsanxuat SET ngayin = ?, ngayout = ? WHERE stt = ?";
            $stmt_update = $connect->prepare($sql_update);
            $stmt_update->bind_param("ssi", 
                $ngayin_string, 
                $ngayout_string, 
                $id
            );
            
            if ($stmt_update->execute()) {
                // Cập nhật các ngày hạn của các bộ phận
                $updated_deadlines = updateDeptDeadlines($connect, $id, $ngayin_string, $ngayout_string);
                
                // Commit transaction
                $connect->commit();
                
                $message = "Đã cập nhật ngày thành công!" . ($updated_deadlines > 0 ? " Cập nhật {$updated_deadlines} hạn xử lý của các bộ phận." : "");
            } else {
                $connect->rollback();
                $error = "Lỗi cập nhật: " . $connect->error;
            }
        } else {
            $error = "Không tìm thấy thông tin mã hàng.";
        }
    } catch (Exception $e) {
        // Đảm bảo rollback nếu có lỗi
        if ($connect->connect_errno == 0) {
            $connect->rollback();
        }
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy thông tin mã hàng để hiển thị
$item_data = null;
if ($id > 0) {
    $sql = "SELECT stt, xuong, line1, po, style, qty, ngayin, ngayout FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $item_data = $result->fetch_assoc();
    } else {
        $error = "Không tìm thấy thông tin mã hàng.";
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
    <style>
        .container {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            padding: 15px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="date"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #f0ad4e;
        }
        .btn-secondary:hover {
            background: #ec971f;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table th, .info-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .info-table th {
            background-color: #f2f2f2;
        }
        .date-format {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .date-input-container {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }
        #date_input {
            width: 100%;
            padding: 8px 30px 8px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
            cursor: pointer;
        }
        .calendar-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 16px;
            pointer-events: auto;
        }
        /* Ẩn bộ chọn ngày mặc định */
        input[type="date"] {
            display: none;
        }
        /* Tùy chỉnh bộ chọn ngày của jQuery UI */
        .ui-datepicker {
            font-size: 14px;
        }
        .ui-datepicker .ui-datepicker-header {
            background-color: #4CAF50;
            color: white;
        }
        .ui-datepicker .ui-datepicker-calendar .ui-state-default {
            background: #f6f6f6;
            color: #333;
        }
        .ui-datepicker .ui-datepicker-calendar .ui-state-active {
            background: #4CAF50;
            color: white;
        }
        /* Styles cho modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            animation: modalOpen 0.3s ease-out;
        }
        
        @keyframes modalOpen {
            from {opacity: 0; transform: translateY(-30px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .modal-header {
            padding: 15px;
            background-color: #4CAF50;
            color: white;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            color: white;
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #eee;
        }
        
        .modal-body {
            padding: 20px;
            font-size: 16px;
        }
        
        .modal-footer {
            padding: 15px;
            background-color: #f8f8f8;
            border-top: 1px solid #ddd;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            text-align: right;
        }
        
        #countdown {
            font-weight: bold;
            font-size: 18px;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cập nhật ngày vào chuyền</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <!-- Modal thành công sẽ được hiển thị bằng JavaScript -->
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($item_data): ?>
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
                    <div class="date-input-container">
                        <input type="text" id="date_input" name="date_display" value="<?php echo date('d/m/Y', strtotime($item_data['ngayin'])); ?>" placeholder="DD/MM/YYYY" autocomplete="off">
                        <span class="calendar-icon" id="calendar_icon">📅</span>
                        <input type="hidden" id="new_date" name="new_date" value="<?php echo date('Y-m-d', strtotime($item_data['ngayin'])); ?>">
                    </div>
                </div>
                <div style="margin-bottom: 15px; padding: 10px; background-color: #d9edf7; border: 1px solid #bce8f1; color: #31708f; border-radius: 4px;">
                    <strong>Lưu ý:</strong> Khi thay đổi ngày in, các ngày sau sẽ được tự động điều chỉnh:
                    <ul>
                        <li>Ngày ra sẽ điều chỉnh theo cùng khoảng thời gian với ngày vào</li>
                        <!-- <li>Ngày hạn xử lý của các bộ phận sẽ được tính toán lại</li> -->
                    </ul>
                    <br>Định dạng ngày: <span class="date-format">Ngày/Tháng/Năm</span>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Cập nhật</button>
                    <a href="index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        <?php else: ?>
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
            const countdown = document.getElementById('countdown');
            
            let countdownInterval;
            let secondsLeft = 3;
            
            // Hàm chuyển đổi từ dd/mm/yyyy sang yyyy-mm-dd
            function parseDateString(dateStr) {
                const parts = dateStr.split('/');
                if (parts.length !== 3) return null;
                
                let day = parseInt(parts[0], 10);
                let month = parseInt(parts[1], 10);
                let year = parseInt(parts[2], 10);
                
                // Xử lý năm 2 chữ số
                if (year < 100) {
                    year = year < 50 ? 2000 + year : 1900 + year;
                }
                
                // Kiểm tra tính hợp lệ
                if (isNaN(day) || isNaN(month) || isNaN(year) || 
                    day < 1 || day > 31 || month < 1 || month > 12) {
                    return null;
                }
                
                // Định dạng với số 0 đứng trước nếu cần
                day = day.toString().padStart(2, '0');
                month = month.toString().padStart(2, '0');
                
                return `${year}-${month}-${day}`;
            }
            
            // Hiển thị modal thành công nếu đã cập nhật thành công
            <?php if (!empty($message)): ?>
                // Hiển thị modal thành công
                successModal.style.display = "block";
                
                // Bắt đầu đếm ngược
                startCountdown();
            <?php endif; ?>
            
            // Hàm đếm ngược và chuyển hướng
            function startCountdown() {
                secondsLeft = 3;
                countdown.textContent = secondsLeft;
                
                countdownInterval = setInterval(function() {
                    secondsLeft--;
                    countdown.textContent = secondsLeft;
                    
                    if (secondsLeft <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = "index.php";
                    }
                }, 1000);
            }
            
            // Khởi tạo date picker của jQuery UI
            $('#date_input').datepicker({
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true,
                yearRange: '2000:2030',
                showOtherMonths: true,
                selectOtherMonths: true,
                dayNames: ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'],
                dayNamesMin: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                monthNamesShort: ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10', 'Th11', 'Th12'],
                onSelect: function(dateText, inst) {
                    // Cập nhật giá trị cho input ẩn
                    const formattedDate = parseDateString(dateText);
                    if (formattedDate) {
                        hiddenDateInput.value = formattedDate;
                    }
                }
            });
            
            // Mở date picker khi click vào biểu tượng lịch
            calendarIcon.addEventListener('click', function(e) {
                e.preventDefault();
                $('#date_input').datepicker('show');
            });
            
            // Xử lý khi người dùng nhập trực tiếp vào ô hiển thị
            displayInput.addEventListener('input', function(e) {
                let value = this.value;
                
                // Tự động thêm dấu / sau khi nhập đủ 2 ký tự cho ngày và tháng
                if (value.length === 2 || value.length === 5) {
                    if (value.charAt(value.length - 1) !== '/') {
                        this.value = value + '/';
                    }
                }
                
                // Chuyển đổi sang định dạng yyyy-mm-dd nếu hợp lệ
                const formattedDate = parseDateString(value);
                if (formattedDate) {
                    hiddenDateInput.value = formattedDate;
                }
            });
            
            // Xử lý khi người dùng nhấn phím trong ô hiển thị
            displayInput.addEventListener('keydown', function(e) {
                // Cho phép chỉ nhập số và phím /
                const allowedKeys = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '/', 'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
                if (!allowedKeys.includes(e.key)) {
                    e.preventDefault();
                }
            });
            
            // Xử lý khi submit form
            dateForm.addEventListener('submit', function(e) {
                // Kiểm tra tính hợp lệ của ngày tháng
                if (!hiddenDateInput.value || hiddenDateInput.value.length !== 10) {
                    e.preventDefault(); // Ngăn form submit
                    alert('Vui lòng nhập ngày hợp lệ theo định dạng DD/MM/YYYY');
                    return;
                }
                // Form sẽ được submit bình thường nếu ngày hợp lệ
            });
            
            // Đóng modal khi click ngoài modal
            window.addEventListener('click', function(e) {
                if (e.target === successModal) {
                    clearInterval(countdownInterval);
                    successModal.style.display = "none";
                }
            });
            
            // Xử lý khi click vào nút "Về trang chủ ngay"
            redirectNow.addEventListener('click', function() {
                clearInterval(countdownInterval);
                window.location.href = "index.php";
            });
            
            // Xử lý khi click vào nút "Ở lại trang này"
            stayHere.addEventListener('click', function() {
                clearInterval(countdownInterval);
                successModal.style.display = "none";
            });
        });
    </script>
</body>
</html> 