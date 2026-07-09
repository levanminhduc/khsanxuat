<?php
require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/includes/security/csrf-helper.php';
require_once BASE_PATH . '/helpers/template_files.php';
require_once BASE_PATH . '/includes/security/auth-helper.php';

// canManageTemplates() la stub luon tra ve true (khong duoc sua helpers/template_files.php),
// nen phai gac that bang requireLogin()/requireFeature() truoc khi toi check stub.
requireLogin();
requireFeature('edit_settings', 'page');

if (!canManageTemplates()) {
    http_response_code(403);
    die('Bạn không có quyền truy cập trang này.');
}

$id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';

if ($id <= 0 || !isValidTemplateDept($dept)) {
    die('Thiếu thông tin hoặc bộ phận không hợp lệ.');
}

// Flash sau redirect (PRG). Đọc xong xoá để F5 không hiện lại.
$message = '';
$message_type = '';
if (isset($_SESSION['flash'])) {
    $message = $_SESSION['flash']['msg'];
    $message_type = $_SESSION['flash']['type'];
    unset($_SESSION['flash']);
}

try {
    $sql = "SELECT style, po, xuong, line1, qty, ngayin, ngayout FROM khsanxuat WHERE stt = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        die('Không tìm thấy dữ liệu đơn hàng.');
    }
    $row   = $result->fetch_assoc();
    $style = $row['style'];
    $po    = $row['po'];
    $xuong = $row['xuong'];
    $line  = $row['line1'];
    $qty   = $row['qty'];

    $ngayin_formatted  = !empty($row['ngayin'])  ? (new DateTime($row['ngayin']))->format('d/m/Y')  : 'N/A';
    $ngayout_formatted = !empty($row['ngayout']) ? (new DateTime($row['ngayout']))->format('d/m/Y') : 'N/A';

    // Danh sách biểu mẫu.
    $templates = [];
    $stmt_t = $connect->prepare("SELECT id, template_name, template_description FROM dept_templates WHERE dept = ? ORDER BY id ASC");
    $stmt_t->bind_param("s", $dept);
    $stmt_t->execute();
    $res_t = $stmt_t->get_result();
    while ($r = $res_t->fetch_assoc()) {
        $templates[] = $r;
    }

    // Số file mỗi template (gom 1 query thay vì query trong vòng lặp).
    $file_counts = [];
    $stmt_c = $connect->prepare("SELECT id_template, COUNT(*) AS cnt FROM dept_template_files WHERE id_khsanxuat = ? AND dept = ? GROUP BY id_template");
    $stmt_c->bind_param("is", $id, $dept);
    $stmt_c->execute();
    $res_c = $stmt_c->get_result();
    while ($r = $res_c->fetch_assoc()) {
        $file_counts[$r['id_template']] = $r['cnt'];
    }

    // Danh sách file đã upload.
    $template_files = [];
    $stmt_f = $connect->prepare("SELECT f.*, t.template_name FROM dept_template_files f JOIN dept_templates t ON f.id_template = t.id WHERE f.id_khsanxuat = ? AND f.dept = ? ORDER BY f.upload_date DESC");
    $stmt_f->bind_param("is", $id, $dept);
    $stmt_f->execute();
    $res_f = $stmt_f->get_result();
    while ($r = $res_f->fetch_assoc()) {
        $template_files[] = $r;
    }
} catch (Exception $e) {
    error_log('file_templates query: ' . $e->getMessage());
    die('Lỗi tải dữ liệu. Vui lòng thử lại.');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Template - <?php echo htmlspecialchars($style); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/file_templates.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/loading-overlay.css">
</head>
<body>
    <!-- Thanh điều hướng - Shared Header Component -->
    <?php
    $header_config = [
        'title' => 'Quản Lý Hồ Sơ SA',
        'title_short' => 'Hồ Sơ SA',
        'logo_path' => BASE_URL . '/img/logoht.png',
        'logo_link' => BASE_URL . '/index.php',
        'show_search' => false,
        'show_mobile_menu' => true,
        'actions' => []
    ];
    include BASE_PATH . '/components/header.php';
    ?>

    <div class="container">
        <a href="<?php echo BASE_URL; ?>/indexdept.php?dept=<?php echo urlencode($dept); ?>&id=<?php echo $id; ?>" class="back-link">
            &larr; Quay lại trang chi tiết
        </a>

        <div class="card">
            <div class="product-info">
                <div class="style-header">
                    <h3>Style: <?php echo htmlspecialchars($style); ?> (STT: <?php echo $id; ?>)</h3>
                </div>
                <div class="product-details">
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>PO:</strong> <?php echo htmlspecialchars($po); ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Line:</strong> <?php echo htmlspecialchars($line); ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Xưởng:</strong> <?php echo htmlspecialchars($xuong); ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Số lượng:</strong> <?php echo htmlspecialchars($qty); ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Ngày vào:</strong> <?php echo htmlspecialchars($ngayin_formatted); ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Ngày ra:</strong> <?php echo htmlspecialchars($ngayout_formatted); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Tab navigation -->
        <div class="tabs">
            <div class="tab active" data-tab="templates">Biểu Mẫu</div>
            <div class="tab" data-tab="upload">Upload Files</div>
            <div class="tab" data-tab="files">Danh sách Files</div>
        </div>

        <!-- Tab Templates -->
        <div class="tab-content active" id="tab-templates">
            <div class="card">
                <h2>Danh sách Biểu Mẫu</h2>

                <?php if (empty($templates)): ?>
                <div class="no-files">
                    <p>Chưa có biểu mẫu nào cho phòng ban này.</p>
                    <p>Thêm biểu mẫu mới để bắt đầu quản lý files.</p>
                </div>
                <?php else: ?>
                <table class="info-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Tên Biểu Mẫu</th>
                            <th>Mô tả</th>
                            <th style="width: 100px;">Số Files</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><?php echo $template['id']; ?></td>
                            <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                            <td><?php echo htmlspecialchars($template['template_description']); ?></td>
                            <td><?php echo isset($file_counts[$template['id']]) ? (int)$file_counts[$template['id']] : 0; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <h3 style="margin-top: 30px;">Thêm Biểu Mẫu Mới</h3>
                <form action="<?php echo BASE_URL; ?>/actions/template_actions.php" method="post" class="upload-form" data-loading data-loading-text="Đang thêm biểu mẫu...">
                    <?php echo getCsrfInput(); ?>
                    <input type="hidden" name="action" value="add_template">
                    <input type="hidden" name="id_sanxuat" value="<?php echo $id; ?>">
                    <input type="hidden" name="dept" value="<?php echo htmlspecialchars($dept); ?>">
                    <div class="form-group">
                        <label for="template_name" class="form-label">Tên Biểu Mẫu:</label>
                        <input type="text" id="template_name" name="template_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="template_description" class="form-label">Mô tả:</label>
                        <textarea id="template_description" name="template_description" class="form-control textarea-control"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Thêm Template
                    </button>
                </form>
            </div>
        </div>

        <!-- Tab Upload Files -->
        <div class="tab-content" id="tab-upload">
            <div class="card">
                <h2>Upload Files</h2>

                <?php if (empty($templates)): ?>
                <div class="alert alert-error">
                    <p>Vui lòng tạo biểu mẫu trước khi upload files.</p>
                </div>
                <?php else: ?>
                <form action="<?php echo BASE_URL; ?>/actions/template_actions.php" method="post" enctype="multipart/form-data" class="upload-form" data-loading data-loading-text="Đang tải file lên...">
                    <?php echo getCsrfInput(); ?>
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="id_sanxuat" value="<?php echo $id; ?>">
                    <input type="hidden" name="dept" value="<?php echo htmlspecialchars($dept); ?>">
                    <div class="form-group">
                        <label for="id_template" class="form-label">Chọn Biểu Mẫu:</label>
                        <select id="id_template" name="id_template" class="form-control" required>
                            <option value="">-- Chọn Biểu Mẫu --</option>
                            <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>">
                                 <?php echo $template['id']; ?> - <?php echo htmlspecialchars($template['template_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="template_file" class="form-label">Chọn Files:</label>
                        <input type="file" id="template_file" name="template_file[]" class="form-control" multiple required>
                        <small style="display: block; margin-top: 5px; color: #6c757d;">
                            Chọn nhiều files (Hỗ trợ: JPG, JPEG, PNG, PDF, Excel và Word) - Dung lượng: < 30MB ( Liên Hệ IT để tăng giới hạn )
                        </small>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Upload Files
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Files List -->
        <div class="tab-content" id="tab-files">
            <div class="card">
                <h2>Danh sách Files đã Upload</h2>

                <?php if (empty($template_files)): ?>
                <div class="no-files">
                    <p>Chưa có file nào được upload.</p>
                </div>
                <?php else: ?>
                <div class="file-list">
                    <div class="file-list-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>Tổng cộng: <?php echo count($template_files); ?> files</div>
                        <?php if (count($template_files) > 0): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/download_all_files.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>" class="btn btn-success" style="margin-left: 10px;" data-loading-download data-loading-text="Đang nén và tải xuống...">
                            <i class="fas fa-cloud-download-alt"></i> Tải xuống tất cả
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php foreach ($template_files as $file): ?>
                    <div class="file-list-item">
                        <div class="file-icon">
                            <?php
                            $icon = 'fas fa-file';
                            switch ($file['file_type']) {
                                case 'image':
                                    $icon = 'fas fa-file-image';
                                    break;
                                case 'pdf':
                                    $icon = 'fas fa-file-pdf';
                                    break;
                                case 'excel':
                                    $icon = 'fas fa-file-excel';
                                    break;
                                case 'word':
                                    $icon = 'fas fa-file-word';
                                    break;
                            }
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                        </div>

                        <div class="file-info">
                            <div class="file-name"><?php echo htmlspecialchars($file['file_name']); ?></div>
                            <div class="file-meta">
                                 <?php echo htmlspecialchars($file['template_name']); ?><br>
                                Uploaded: <?php echo date('d/m/Y H:i', strtotime($file['upload_date'])); ?>
                            </div>
                        </div>

                        <div class="file-actions">
                            <a href="<?php echo BASE_URL . '/' . htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                            <a href="<?php echo BASE_URL . '/' . htmlspecialchars($file['file_path']); ?>" download class="btn btn-success">
                                <i class="fas fa-download"></i> Tải về
                            </a>
                            <form action="<?php echo BASE_URL; ?>/actions/template_actions.php" method="post" style="display:inline;" data-loading data-loading-text="Đang xóa file..." onsubmit="return confirm('Bạn có chắc chắn muốn xóa file này?');">
                                <?php echo getCsrfInput(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                <input type="hidden" name="id_sanxuat" value="<?php echo $id; ?>">
                                <input type="hidden" name="dept" value="<?php echo htmlspecialchars($dept); ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </button>
                            </form>
                        </div>

                        <?php if ($file['file_type'] === 'image'): ?>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($file['file_path']); ?>" alt="<?php echo htmlspecialchars($file['file_name']); ?>" style="max-width: 100%; max-height: 200px; display: block; margin: 0 auto; border-radius: 4px;">
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include BASE_PATH . '/components/loading-overlay.php'; ?>

    <script src="<?php echo BASE_URL; ?>/assets/js/file_templates.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/loading-overlay.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/header.js"></script>
</body>
</html>