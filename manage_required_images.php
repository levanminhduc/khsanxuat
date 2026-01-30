<?php
// Kết nối database
include 'db_connect.php';

// Kiểm tra và tạo bảng nếu chưa tồn tại
$sql_create_table = "CREATE TABLE IF NOT EXISTS required_images_criteria (
    id INT(11) NOT NULL AUTO_INCREMENT,
    dept VARCHAR(50) NOT NULL,
    id_tieuchi INT(11) NOT NULL,
    thutu INT(11) NOT NULL,
    noidung VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_dept_tieuchi (dept, id_tieuchi)
)";

if (!$connect->query($sql_create_table)) {
    die("Lỗi tạo bảng: " . $connect->error);
}

// Thêm tiêu chí mặc định (tiêu chí số 5 của Kho Phụ Liệu)
$sql_check_default = "SELECT * FROM required_images_criteria WHERE dept = 'kho' AND id_tieuchi = 131";
$result = $connect->query($sql_check_default);

if ($result->num_rows == 0) {
    $sql_insert_default = "INSERT INTO required_images_criteria (dept, id_tieuchi, thutu, noidung) 
                          VALUES ('kho', 131, 5, 'Tiêu chí số 5 của Kho Phụ Liệu')";
    $connect->query($sql_insert_default);
}

// Xử lý thêm tiêu chí mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $dept = $connect->real_escape_string($_POST['dept']);
    $id_tieuchi = intval($_POST['id_tieuchi']);
    $thutu = intval($_POST['thutu']);
    $noidung = $connect->real_escape_string($_POST['noidung']);

    // Kiểm tra xem tiêu chí có tồn tại trong bảng tieuchi_dept không
    $sql_check = "SELECT * FROM tieuchi_dept WHERE dept = ? AND id = ?";
    $stmt = $connect->prepare($sql_check);
    $stmt->bind_param("si", $dept, $id_tieuchi);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sql_insert = "INSERT INTO required_images_criteria (dept, id_tieuchi, thutu, noidung) 
                      VALUES (?, ?, ?, ?)";
        $stmt = $connect->prepare($sql_insert);
        $stmt->bind_param("siis", $dept, $id_tieuchi, $thutu, $noidung);
        
        if ($stmt->execute()) {
            $success_message = "Đã thêm tiêu chí thành công!";
        } else {
            $error_message = "Lỗi: " . $stmt->error;
        }
    } else {
        $error_message = "Tiêu chí không tồn tại trong hệ thống!";
    }
}

// Xử lý xóa tiêu chí
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    
    // Không cho phép xóa tiêu chí mặc định
    $sql_check = "SELECT * FROM required_images_criteria WHERE id = ? AND id_tieuchi = 131 AND dept = 'kho'";
    $stmt = $connect->prepare($sql_check);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $error_message = "Không thể xóa tiêu chí mặc định!";
    } else {
        $sql_delete = "DELETE FROM required_images_criteria WHERE id = ?";
        $stmt = $connect->prepare($sql_delete);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "Đã xóa tiêu chí thành công!";
        } else {
            $error_message = "Lỗi: " . $stmt->error;
        }
    }
}

// Lấy danh sách tiêu chí bắt buộc hình ảnh
$sql = "SELECT r.*, t.noidung as tieuchi_noidung, 
        CASE r.dept 
            WHEN 'kehoach' THEN 'Bộ phận Kế hoạch'
            WHEN 'chuanbi_sanxuat_phong_kt' THEN 'Bộ phận Chuẩn bị sản xuất (Phòng KT)'
            WHEN 'kho' THEN 'Kho nguyên, phụ liệu'
            WHEN 'cat' THEN 'Bộ phận Cắt'
            WHEN 'ep_keo' THEN 'Bộ phận Ép keo'
            WHEN 'co_dien' THEN 'Bộ phận Cơ điện'
            WHEN 'chuyen_may' THEN 'Bộ phận Chuyền may'
            WHEN 'kcs' THEN 'Bộ phận KCS'
            WHEN 'ui_thanh_pham' THEN 'Bộ phận Ủi thành phẩm'
            WHEN 'hoan_thanh' THEN 'Bộ phận Hoàn thành'
            ELSE r.dept
        END as dept_name
        FROM required_images_criteria r
        LEFT JOIN tieuchi_dept t ON r.id_tieuchi = t.id AND r.dept = t.dept
        ORDER BY r.dept, r.thutu";

$result = $connect->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quản lý Tiêu Chí Bắt Buộc Hình Ảnh</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <style>
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .form-section { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .list-section { background: #fff; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-submit { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .default-criteria { background-color: #fff3cd; }
    </style>
</head>
<body>
    <?php
    $header_config = [
        'title' => 'Quản lý Tiêu Chí Bắt Buộc Hình Ảnh',
        'title_short' => 'QL Bắt buộc ảnh',
        'logo_path' => 'img/logoht.png',
        'logo_link' => '/trangchu/',
        'show_search' => false,
        'show_mobile_menu' => true,
        'actions' => []
    ];
    ?>
    <?php include 'components/header.php'; ?>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2>Thêm Tiêu Chí Mới</h2>
            <form method="POST">
                <input type="hidden" name="add" value="1">
                <div class="form-group">
                    <label>Bộ phận:</label>
                    <select name="dept" class="form-control" required>
                        <option value="">-- Chọn bộ phận --</option>
                        <option value="kehoach">Bộ phận Kế hoạch</option>
                        <option value="chuanbi_sanxuat_phong_kt">Bộ phận Chuẩn bị sản xuất (Phòng KT)</option>
                        <option value="kho">Kho nguyên, phụ liệu</option>
                        <option value="cat">Bộ phận Cắt</option>
                        <option value="ep_keo">Bộ phận Ép keo</option>
                        <option value="co_dien">Bộ phận Cơ điện</option>
                        <option value="chuyen_may">Bộ phận Chuyền may</option>
                        <option value="kcs">Bộ phận KCS</option>
                        <option value="ui_thanh_pham">Bộ phận Ủi thành phẩm</option>
                        <option value="hoan_thanh">Bộ phận Hoàn thành</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ID Tiêu chí:</label>
                    <input type="number" name="id_tieuchi" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Thứ tự:</label>
                    <input type="number" name="thutu" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nội dung:</label>
                    <textarea name="noidung" class="form-control" required></textarea>
                </div>
                <button type="submit" class="btn-submit">Thêm tiêu chí</button>
            </form>
        </div>

        <div class="list-section">
            <h2>Danh sách tiêu chí bắt buộc hình ảnh</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bộ phận</th>
                        <th>ID Tiêu chí</th>
                        <th>Thứ tự</th>
                        <th>Nội dung tiêu chí</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="<?php echo ($row['dept'] == 'kho' && $row['id_tieuchi'] == 131) ? 'default-criteria' : ''; ?>">
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['id_tieuchi']); ?></td>
                                <td><?php echo htmlspecialchars($row['thutu']); ?></td>
                                <td><?php echo htmlspecialchars($row['tieuchi_noidung'] ?? $row['noidung']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <?php if (!($row['dept'] == 'kho' && $row['id_tieuchi'] == 131)): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="delete" value="1">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Không có tiêu chí nào</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Thêm JavaScript để tải nội dung tiêu chí khi chọn bộ phận
        document.querySelector('select[name="dept"]').addEventListener('change', function() {
            const dept = this.value;
            const idInput = document.querySelector('input[name="id_tieuchi"]');
            const thutuInput = document.querySelector('input[name="thutu"]');
            const noidungTextarea = document.querySelector('textarea[name="noidung"]');
            
            if (dept) {
                // Gọi API để lấy danh sách tiêu chí của bộ phận
                fetch('get_tieuchi.php?dept=' + dept)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            // Tự động điền thứ tự tiếp theo
                            const maxThutu = Math.max(...data.data.map(t => t.thutu), 0);
                            thutuInput.value = maxThutu + 1;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    </script>
    <script src="assets/js/header.js"></script>
</body>
</html> 