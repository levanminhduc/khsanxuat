<?php
$criteria_list = getCriteriaList($connect, $dept);
?>
<div id="deadlineModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeadlineModal()">&times;</span>
        <h3 class="modal-title">Cài đặt hạn xử lý chung cho tất cả tiêu chí</h3>

        <div class="form-group">
            <label for="so_ngay_xuly_chung">Số ngày cần trừ từ ngày vào:</label>
            <input type="number" id="so_ngay_xuly_chung" value="<?php echo $so_ngay_xuly; ?>" min="1" max="30" required>
            <div class="quick-suggestion">
                <span>Gợi ý: </span>
                <button type="button" onclick="setQuickDays(7)" class="quick-btn">7 ngày</button>
                <button type="button" onclick="setQuickDays(14)" class="quick-btn">14 ngày</button>
            </div>
            <p class="note">Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày</p>
        </div>

        <div class="form-group">
            <label for="ngay_tinh_han">Chọn ngày tính hạn xử lý:</label>
            <select id="ngay_tinh_han" onchange="changeNgayTinhHan()">
                <option value="ngay_vao" selected>Ngày vào trừ số ngày</option>
                <option value="ngay_vao_cong">Ngày vào cộng số ngày</option>
                <option value="ngay_ra">Ngày ra cộng số ngày</option>
                <option value="ngay_ra_tru">Ngày ra trừ số ngày</option>
            </select>
            <p class="note-ngay-tinh" id="note-ngay-tinh">Ví dụ: Nếu nhập 7, "Hạn Xử Lý" sẽ là "Ngày Vào" - 7 ngày</p>
        </div>

        <div class="form-group">
            <label>Áp dụng cho tiêu chí:</label>
            <div id="tieuchi_list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
                <?php while ($tieuchi = $criteria_list->fetch_assoc()) : ?>
                <div style="margin-bottom: 5px;">
                    <input type="checkbox" id="tieuchi_<?php echo $tieuchi['id']; ?>" class="tieuchi-checkbox" value="<?php echo $tieuchi['id']; ?>">
                    <label for="tieuchi_<?php echo $tieuchi['id']; ?>">
                        <?php echo $tieuchi['thutu'] . '. ' . substr($tieuchi['noidung'], 0, 50) . (strlen($tieuchi['noidung']) > 50 ? '...' : ''); ?>
                    </label>
                </div>
                <?php endwhile; ?>
                <div style="margin-top: 10px;">
                    <button type="button" onclick="selectAllTieuchi(true)" class="small-btn">Chọn tất cả</button>
                    <button type="button" onclick="selectAllTieuchi(false)" class="small-btn">Bỏ chọn tất cả</button>
                </div>
            </div>
        </div>

        <div class="form-group">
            <button type="button" onclick="updateDeadlineAll(<?php echo intval($id); ?>, '<?php echo htmlspecialchars($dept, ENT_QUOTES, 'UTF-8'); ?>')" class="btn-add-criteria">Lưu cài đặt</button>
            <button type="button" onclick="closeDeadlineModal()" class="btn-add-criteria">Hủy</button>
        </div>
        <div id="update_status" style="margin-top: 10px; text-align: center; display: none;"></div>
    </div>
</div>
