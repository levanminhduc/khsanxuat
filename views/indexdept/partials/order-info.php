    <!-- Strip rút gọn: chỉ hiện ở mobile, thay cho bảng thông tin đơn hàng -->
    <div class="order-strip">
        <div class="order-strip__row">
            <div class="order-strip__meta">
                <span class="order-strip__label">PO:</span><span class="order-strip__po"><?php echo htmlspecialchars($po); ?></span>
                <span class="order-strip__sep">-</span>
                <span class="order-strip__label">Mã Hàng:</span><span class="order-strip__style"><?php echo htmlspecialchars($style); ?></span>
            </div>
            <a href="<?php echo BASE_URL; ?>/pages/image_handler.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>"
               class="order-strip__btn" title="Xử lý hình ảnh" aria-label="Xử lý hình ảnh">
                <i class="fas fa-image" aria-hidden="true"></i>
                <?php if ($image_count > 0) : ?>
                <span class="order-strip__badge"><?php echo $image_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo BASE_URL; ?>/pages/file_templates.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>"
               class="order-strip__btn" title="Biểu mẫu" aria-label="Biểu mẫu">
                <i class="fas fa-file-alt" aria-hidden="true"></i>
            </a>
        </div>
        <div class="order-strip__details">
            <span class="order-strip__chip"><span class="order-strip__chip-label">Xưởng</span><?php echo htmlspecialchars($xuong); ?></span>
            <span class="order-strip__chip"><span class="order-strip__chip-label">Line</span><?php echo htmlspecialchars($line); ?></span>
            <span class="order-strip__chip"><span class="order-strip__chip-label">SL</span><?php echo is_numeric($qty) ? number_format((float)$qty, 0, ',', '.') : htmlspecialchars($qty); ?></span>
            <span class="order-strip__chip"><span class="order-strip__chip-label">Vào</span><?php echo $ngayin_formatted; ?></span>
            <span class="order-strip__chip"><span class="order-strip__chip-label">Ra</span><?php echo $ngayout_formatted; ?></span>
        </div>
        <div class="order-strip__progress">
            <span class="order-strip__track"><span id="orderStripProgress"></span></span>
            <span class="order-strip__score" id="orderStripScore"></span>
        </div>
    </div>

    <div class="table-responsive table-responsive--spaced order-info-table-wrap">
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
                    <th>Xử Lý Hình Ảnh</th>
                    <th>Hồ Sơ SA</th>
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
                    <td>
                        <a href="<?php echo BASE_URL; ?>/pages/image_handler.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>" class="btn-upload-image">
                            <?php if ($image_count > 0) : ?>
                            <i class="fas fa-exclamation-triangle warning-icon"></i>
                            <span class="image-count-badge"><?php echo $image_count; ?></span>
                            <?php endif; ?>
                            Xử lý hình ảnh
                        </a>
                    </td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>/pages/file_templates.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>" class="btn-upload-file">
                            <?php
                            // Kiểm tra số file đã upload
                            $file_count = 0;
                            // Kiểm tra nếu bảng tồn tại trước khi thực hiện truy vấn
                            $check_table_exists = $connect->query("SHOW TABLES LIKE 'dept_template_files'");
                            if ($check_table_exists->num_rows > 0) {
                                $sql_count_files = "SELECT COUNT(*) as file_count FROM dept_template_files WHERE id_khsanxuat = ? AND dept = ?";
                                $stmt_count_files = $connect->prepare($sql_count_files);
                                $stmt_count_files->bind_param("is", $id, $dept);
                                $stmt_count_files->execute();
                                $result_count_files = $stmt_count_files->get_result();
                                $file_count = $result_count_files->fetch_assoc()['file_count'];
                            }

                            if ($file_count > 0) :
                                ?>
                            <i class="fas fa-exclamation-triangle warning-icon"></i>
                            <span class="file-count-badge"><?php echo $file_count; ?></span>
                            <?php endif; ?>
                            Update File
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

