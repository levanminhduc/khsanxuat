    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 20px;">
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
                        <a href="image_handler.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>" class="btn-upload-image">
                            <?php if ($image_count > 0) : ?>
                            <i class="fas fa-exclamation-triangle warning-icon"></i>
                            <span class="image-count-badge"><?php echo $image_count; ?></span>
                            <?php endif; ?>
                            Xử lý hình ảnh
                        </a>
                    </td>
                    <td>
                        <a href="file_templates.php?id=<?php echo $id; ?>&dept=<?php echo $dept; ?>" class="btn-upload-file">
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

