<?php
include 'db_connect.php';
include 'components/modal.php';
require_once 'components/form-page.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

function calculateDeadline($ngay_vao, $ngay_ra, $ngay_tinh_han, $so_ngay_xuly)
{
    if (empty($ngay_vao)) {
        return null;
    }

    $deadline = null;

    try {
        $ngay_vao_date = new DateTime($ngay_vao);
    } catch (Exception $e) {
        return null;
    }

    $ngay_ra_date = null;
    if (!empty($ngay_ra)) {
        try {
            $ngay_ra_date = new DateTime($ngay_ra);
        } catch (Exception $e) {
        }
    }

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
                $deadline = clone $ngay_vao_date;
                $deadline->add(new DateInterval('P' . $so_ngay_xuly . 'D'));
            }
            break;
        case 'ngay_ra_tru':
            if ($ngay_ra_date) {
                $deadline = clone $ngay_ra_date;
                $deadline->sub(new DateInterval('P' . $so_ngay_xuly . 'D'));
            } else {
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

function updateDeptDeadlines($connect, $id_sanxuat, $new_ngayin, $new_ngayout)
{
    $updated = 0;

    $sql = "SELECT dt.id, dt.id_tieuchi, dt.so_ngay_xuly, dt.ngay_tinh_han, tc.dept
            FROM danhgia_tieuchi dt
            JOIN tieuchi_dept tc ON dt.id_tieuchi = tc.id
            WHERE dt.id_sanxuat = ?";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id_sanxuat);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $update_sql = "UPDATE danhgia_tieuchi SET han_xuly = ? WHERE id = ?";
        $update_stmt = $connect->prepare($update_sql);

        while ($row = $result->fetch_assoc()) {
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

if ($id <= 0) {
    $error = "ID không hợp lệ.";
} else {
    $sql = "SELECT line1, xuong, po, style, qty, ngayin, ngayout FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item_data = $result->fetch_assoc();
        
        $line = $item_data['line1'];
        $xuong = $item_data['xuong'];
        $po = $item_data['po'];
        $style = $item_data['style'];
        $qty = $item_data['qty'];
        
        $ngayin = new DateTime($item_data['ngayin']);
        $ngayout = new DateTime($item_data['ngayout']);
        $ngayin_formatted = $ngayin->format('d/m/Y');
        $ngayout_formatted = $ngayout->format('d/m/Y');
    } else {
        $error = "Không tìm thấy mã hàng với ID: " . $id;
        $item_data = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_date']) && $item_data) {
    $new_date = $_POST['new_date'];
    $new_qty = isset($_POST['new_qty']) ? $_POST['new_qty'] : '';
    $new_line = isset($_POST['new_line']) ? trim($_POST['new_line']) : '';

    $date_valid = DateTime::createFromFormat('Y-m-d', $new_date) !== false;

    $qty_valid = is_numeric($new_qty) && $new_qty > 0 && $new_qty == (int)$new_qty;

    $line_valid = is_numeric($new_line) && $new_line >= 1 && $new_line <= 10 && $new_line == (int)$new_line;

    if ($date_valid && $qty_valid && $line_valid) {
        $current_ngay_vao = new DateTime($item_data['ngayin']);
        $current_ngay_ra = new DateTime($item_data['ngayout']);
        $time_diff = $current_ngay_vao->diff($current_ngay_ra);

        $new_ngay_vao = new DateTime($new_date);
        $new_ngay_ra = clone $new_ngay_vao;
        $new_ngay_ra->add($time_diff);

        $ngayin_string = $new_date;
        $ngayout_string = $new_ngay_ra->format('Y-m-d');
        $qty_int = (int)$new_qty;
        $line_int = (int)$new_line;

        $update_sql = "UPDATE khsanxuat SET ngayin = ?, ngayout = ?, qty = ?, line1 = ? WHERE stt = ?";
        $update_stmt = $connect->prepare($update_sql);
        $update_stmt->bind_param("ssiii", $ngayin_string, $ngayout_string, $qty_int, $line_int, $id);

        if ($update_stmt->execute()) {
            $updated_deadlines = updateDeptDeadlines($connect, $id, $ngayin_string, $ngayout_string);

            $message = "Đã cập nhật ngày, số lượng và LINE thành công!" . ($updated_deadlines > 0 ? " Cập nhật {$updated_deadlines} hạn xử lý của các bộ phận." : "");

            $stmt->execute();
            $result = $stmt->get_result();
            $item_data = $result->fetch_assoc();
            
            $line = $item_data['line1'];
            $xuong = $item_data['xuong'];
            $po = $item_data['po'];
            $style = $item_data['style'];
            $qty = $item_data['qty'];
            
            $ngayin = new DateTime($item_data['ngayin']);
            $ngayout = new DateTime($item_data['ngayout']);
            $ngayin_formatted = $ngayin->format('d/m/Y');
            $ngayout_formatted = $ngayout->format('d/m/Y');
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
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="assets/css/form-page.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

</head>
<body>
<?php
$header_config = [
    'title' => 'Cập nhật ngày vào chuyền',
    'title_short' => 'Cập nhật',
    'logo_path' => 'img/logoht.png',
    'logo_link' => '/trangchu/',
    'show_search' => false,
    'show_mobile_menu' => true,
    'actions' => [
        [
            'url' => 'index.php',
            'icon' => 'img/back.png',
            'title' => 'Quay lại',
            'tooltip' => 'Quay lại trang chủ'
        ]
    ]
];
include 'components/header.php';
?>

    <div class="form-page-component">
        <div class="form-page-container">
        <?php if (!empty($message)) : ?>
            <div class="alert alert-success">
                <span class="alert-icon"></span>
                <span class="alert-content"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)) : ?>
            <div class="alert alert-error">
                <span class="alert-icon"></span>
                <span class="alert-content"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($item_data) : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Xưởng</th>
                        <th>Line</th>
                        <th>PO</th>
                        <th>Style</th>
                        <th>Số lượng</th>
                        <th>Ngày vào</th>
                        <th>Ngày ra</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($xuong); ?></td>
                        <td><?php echo htmlspecialchars($line); ?></td>
                        <td><?php echo htmlspecialchars($po); ?></td>
                        <td><?php echo htmlspecialchars($style); ?></td>
                        <td><?php echo htmlspecialchars($qty); ?></td>
                        <td><?php echo $ngayin_formatted; ?></td>
                        <td><?php echo $ngayout_formatted; ?></td>
                    </tr>
                </tbody>
            </table>

            <form method="post" action="" id="date_form">
                <input type="hidden" name="new_date" id="new_date" value="<?php echo date('Y-m-d', strtotime($item_data['ngayin'])); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_input" class="form-label form-label-required">Ngày vào mới:</label>
                        <div class="date-input-container">
                            <input type="text" name="date_display" id="date_input" class="form-input date-picker" value="<?php echo date('d/m/Y', strtotime($item_data['ngayin'])); ?>" placeholder="DD/MM/YYYY" autocomplete="off" required>
                            <span class="date-input-icon"><i class="fas fa-calendar-alt"></i></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="qty_input" class="form-label form-label-required">Số lượng mới:</label>
                        <input type="number" name="new_qty" id="qty_input" class="form-input" value="<?php echo $item_data['qty']; ?>" min="1" step="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="line_input" class="form-label form-label-required">Line (1-10):</label>
                        <input type="number" name="new_line" id="line_input" class="form-input" value="<?php echo htmlspecialchars($item_data['line1']); ?>" placeholder="1-10" min="1" max="10" step="1" required>
                    </div>
                </div>
                
                <?php
                render_form_note([
                    'type' => 'info',
                    'title' => 'Lưu ý:',
                    'items' => [
                        'Ngày ra sẽ điều chỉnh theo cùng khoảng thời gian với ngày vào',
                        'Số lượng phải là số nguyên dương',
                        'Line từ 1 đến 10'
                    ],
                    'footer' => 'Định dạng ngày: Ngày/Tháng/Năm'
                ]);
                ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                    <a href="index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        <?php else : ?>
            <div class="alert alert-error">
                <span class="alert-icon"></span>
                <span class="alert-content">Không thể lấy thông tin mã hàng.</span>
            </div>
            <div class="form-actions">
                <a href="index.php" class="btn btn-primary">Quay lại</a>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <?php
    render_modal([
        'id' => 'successModal',
        'title' => 'Cập nhật thành công',
        'type' => 'success',
        'body' => '<p>Đã cập nhật ngày thành công!</p>',
        'auto_redirect' => [
            'url' => 'index.php',
            'delay' => 3,
            'show_countdown' => true
        ],
        'buttons' => [
            ['text' => 'Về trang chủ ngay', 'class' => 'btn-primary', 'id' => 'redirectNow'],
            ['text' => 'Ở lại trang này', 'class' => 'btn-secondary', 'id' => 'stayHere']
        ]
    ]);
    ?>

    <script>
        $(document).ready(function() {
            const hiddenDateInput = document.getElementById('new_date');
            const displayInput = document.getElementById('date_input');
            const dateForm = document.getElementById('date_form');

            const successModal = document.getElementById('successModal');

            $("#date_input").datepicker({
                dateFormat: "dd/mm/yy",
                changeMonth: true,
                changeYear: true,
                yearRange: "2020:2030",
                showButtonPanel: true,
                onSelect: function(dateText, inst) {
                    const parts = dateText.split('/');
                    const formattedDate = parts[2] + '-' + parts[1] + '-' + parts[0];
                    hiddenDateInput.value = formattedDate;
                }
            });

            $(document).on('click', '.date-input-icon', function() {
                $("#date_input").datepicker('show');
            });

            displayInput.addEventListener('input', function() {
                const value = this.value;
                const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
                const match = value.match(dateRegex);

                if (match) {
                    const day = match[1];
                    const month = match[2];
                    const year = match[3];

                    const date = new Date(year, month - 1, day);
                    if (date.getFullYear() == year &&
                        date.getMonth() == month - 1 &&
                        date.getDate() == day) {
                        hiddenDateInput.value = year + '-' + month + '-' + day;
                    }
                }
            });

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

            <?php if (!empty($message)) : ?>
            successModal.classList.add('is-open');
            <?php endif; ?>
        });
    </script>
    <script src="assets/js/header.js"></script>
</body>
</html>
