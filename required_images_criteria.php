<?php
include 'db_connect.php';

// Mảng ánh xạ tên bộ phận
$dept_names = [
    'kehoach' => 'BỘ PHẬN KẾ HOẠCH',
    'chuanbi_sanxuat_phong_kt' => 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)',
    'kho' => 'KHO NGUYÊN, PHỤ LIỆU',
    'cat' => 'BỘ PHẬN CẮT',
    'ep_keo' => 'BỘ PHẬN ÉP KEO',
    'co_dien' => 'BỘ PHẬN CƠ ĐIỆN',
    'chuyen_may' => 'BỘ PHẬN CHUYỀN MAY',
    'kcs' => 'BỘ PHẬN KCS',
    'ui_thanh_pham' => 'BỘ PHẬN ỦI THÀNH PHẨM',
    'hoan_thanh' => 'BỘ PHẬN HOÀN THÀNH'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quản lý Tiêu Chí Bắt Buộc Hình Ảnh</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .criteria-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .criteria-list {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <a href="<?php echo $_SERVER['HTTP_REFERER'] ?? '/khsanxuat/index.php'; ?>"><img width="45px" src="img/logoht.png" /></a>
        </div>
        <div class="navbar-center" style="display: flex; justify-content: center; width: 100%;">
            <h1 style="font-size: 24px; margin: 0;">QUẢN LÝ TIÊU CHÍ BẮT BUỘC HÌNH ẢNH</h1>
        </div>
    </div>

    <div class="container">
        <?php
        // Xử lý thêm tiêu chí mới
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_criteria'])) {
            $dept = $connect->real_escape_string($_POST['dept']);
            $id_tieuchi = intval($_POST['id_tieuchi']);

            // Kiểm tra tiêu chí có tồn tại không
            $check_sql = "SELECT id FROM tieuchi_dept WHERE dept = ? AND id = ?";
            $check_stmt = $connect->prepare($check_sql);
            $check_stmt->bind_param("si", $dept, $id_tieuchi);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows === 0) {
                echo '<div class="error-message">Tiêu chí không tồn tại!</div>';
            } else {
                $sql_insert = "INSERT INTO required_images_criteria (dept, id_tieuchi) VALUES (?, ?)";
                $stmt = $connect->prepare($sql_insert);
                $stmt->bind_param("si", $dept, $id_tieuchi);

                if ($stmt->execute()) {
                    echo '<div class="success-message">Đã thêm tiêu chí thành công!</div>';
                } else {
                    if ($stmt->errno == 1062) {
                        echo '<div class="error-message">Tiêu chí này đã được thêm trước đó!</div>';
                    } else {
                        echo '<div class="error-message">Lỗi: ' . $stmt->error . '</div>';
                    }
                }
            }
        }

        // Xử lý xóa tiêu chí
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_criteria'])) {
            $id = intval($_POST['id']);
            
            $sql_delete = "DELETE FROM required_images_criteria WHERE id = ?";
            $stmt = $connect->prepare($sql_delete);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo '<div class="success-message">Đã xóa tiêu chí thành công!</div>';
            } else {
                echo '<div class="error-message">Lỗi: ' . $stmt->error . '</div>';
            }
        }
        ?>

        <div class="criteria-form">
            <h2>Thêm Tiêu Chí Mới</h2>
            <form method="POST">
                <input type="hidden" name="add_criteria" value="1">
                <div class="form-group">
                    <label for="dept">Bộ phận:</label>
                    <select name="dept" id="dept" class="form-control" required>
                        <option value="">-- Chọn bộ phận --</option>
                        <?php foreach ($dept_names as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_tieuchi">ID Tiêu chí:</label>
                    <input type="number" name="id_tieuchi" id="id_tieuchi" class="form-control" required min="1" list="tieuchi_list">
                    <datalist id="tieuchi_list"></datalist>
                    <div id="tieuchi_preview" style="margin-top: 5px; color: #666;"></div>
                </div>
                <button type="submit" class="btn-submit">Thêm tiêu chí</button>
            </form>
        </div>

        <div class="criteria-list">
            <h2>Danh sách tiêu chí bắt buộc hình ảnh</h2>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Bộ phận</th>
                        <th>Thứ tự</th>
                        <th>Nội dung tiêu chí</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Lấy danh sách tiêu chí bắt buộc hình ảnh
                    $sql = "SELECT r.id, r.dept, r.id_tieuchi, t.thutu, t.noidung, t.nhom
                            FROM required_images_criteria r
                            LEFT JOIN tieuchi_dept t ON r.id_tieuchi = t.id AND r.dept = t.dept
                            ORDER BY r.dept, t.thutu";
                    
                    $result = $connect->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        $stt = 1;
                        $current_dept = '';
                        while ($row = $result->fetch_assoc()) {
                            if ($current_dept != $row['dept']) {
                                $current_dept = $row['dept'];
                                echo "<tr><td colspan='5' style='background-color: #f3f4f6; font-weight: bold;'>" . 
                                     ($dept_names[$row['dept']] ?? $row['dept']) . "</td></tr>";
                            }
                            echo "<tr>";
                            echo "<td>" . $stt++ . "</td>";
                            echo "<td>" . ($dept_names[$row['dept']] ?? $row['dept']) . "</td>";
                            echo "<td>" . ($row['thutu'] ?? 'N/A') . "</td>";
                            echo "<td>" . ($row['noidung'] ?? 'Không tìm thấy tiêu chí') . "</td>";
                            echo "<td>";
                            echo "<form method='POST' style='display: inline;'>";
                            echo "<input type='hidden' name='delete_criteria' value='1'>";
                            echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                            echo "<button type='submit' class='btn-delete' onclick='return confirm(\"Bạn có chắc chắn muốn xóa tiêu chí này?\")'>Xóa</button>";
                            echo "</form>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center;'>Chưa có tiêu chí bắt buộc hình ảnh nào</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Thêm event listener cho dropdown bộ phận
        document.getElementById('dept').addEventListener('change', function() {
            const selectedDept = this.value;
            loadTieuchiList(selectedDept);
        });

        // Hàm load danh sách tiêu chí theo bộ phận
        function loadTieuchiList(dept) {
            if (!dept) {
                document.getElementById('tieuchi_list').innerHTML = '';
                document.getElementById('tieuchi_preview').innerHTML = '';
                return;
            }

            fetch('get_tieuchi_list.php?dept=' + encodeURIComponent(dept))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const datalist = document.getElementById('tieuchi_list');
                        datalist.innerHTML = '';
                        
                        data.data.forEach(tieuchi => {
                            const option = document.createElement('option');
                            option.value = tieuchi.id;
                            option.textContent = `${tieuchi.thutu}. ${tieuchi.noidung}`;
                            datalist.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Lỗi:', error));
        }

        // Thêm event listener cho input ID tiêu chí
        document.getElementById('id_tieuchi').addEventListener('input', function() {
            const selectedDept = document.getElementById('dept').value;
            const tieuchiId = this.value;
            
            if (selectedDept && tieuchiId) {
                fetch('get_tieuchi_info.php?dept=' + encodeURIComponent(selectedDept) + '&id=' + encodeURIComponent(tieuchiId))
                    .then(response => response.json())
                    .then(data => {
                        const previewDiv = document.getElementById('tieuchi_preview');
                        if (data.success && data.data) {
                            previewDiv.innerHTML = `<strong>Tiêu chí đã chọn:</strong> ${data.data.thutu}. ${data.data.noidung}`;
                            previewDiv.style.color = '#155724';
                        } else {
                            previewDiv.innerHTML = 'Không tìm thấy tiêu chí';
                            previewDiv.style.color = '#721c24';
                        }
                    })
                    .catch(error => console.error('Lỗi:', error));
            }
        });
    </script>
</body>
</html> 