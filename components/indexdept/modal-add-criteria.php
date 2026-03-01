<?php ?>
<div id="addCriteriaModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 class="modal-title">Thêm tiêu chí mới cho <?php echo $dept_display_name; ?></h3>
        <form action="add_criteria.php" method="POST">
            <input type="hidden" name="dept" value="<?php echo htmlspecialchars($dept); ?>">
            <input type="hidden" name="id_sanxuat" value="<?php echo intval($id); ?>">

            <?php if ($dept == 'chuanbi_sanxuat_phong_kt' || $dept == 'kho') : ?>
            <div class="form-group">
                <label for="nhom">Nhóm:</label>
                <select id="nhom" name="nhom" required class="form-control">
                    <?php if ($dept == 'chuanbi_sanxuat_phong_kt') : ?>
                        <option value="Nhóm Nghiệp Vụ">a. Nhóm Nghiệp Vụ</option>
                        <option value="Nhóm May Mẫu">b. Nhóm May Mẫu</option>
                        <option value="Nhóm Quy Trình">c. Nhóm Quy Trình Công Nghệ, Thiết Kế Chuyền</option>
                    <?php elseif ($dept == 'kho') : ?>
                        <option value="Kho Nguyên Liệu">a. Kho Nguyên Liệu</option>
                        <option value="Kho Phụ Liệu">b. Kho Phụ Liệu</option>
                    <?php endif; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="thutu">Thứ tự:</label>
                <input type="number" id="thutu" name="thutu" required min="1">
            </div>
            <div class="form-group">
                <label for="noidung">Nội dung tiêu chí:</label>
                <textarea id="noidung" name="noidung" required rows="4"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-add-criteria">Lưu tiêu chí</button>
                <button type="button" onclick="closeModal()" class="btn-back">Hủy</button>
            </div>
        </form>
    </div>
</div>
