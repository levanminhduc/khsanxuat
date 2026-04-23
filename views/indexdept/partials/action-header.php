    <div class="container">
        <div class="action-buttons">
            <h2>Thông tin chi tiết - <?php echo $dept_display_name; ?></h2>
            <div class="header-actions">
                <button type="button" class="btn-add-criteria" onclick="openModal()">
                    <i class="fas fa-plus"></i> Thêm tiêu chí
                </button>
                <button type="button" class="btn-add-criteria" onclick="openDefaultSettingModal()">
                    <i class="fas fa-cog"></i> Cài đặt mặc định
                </button>
                <button type="button" class="btn-add-criteria" onclick="syncTieuChiWithDefaultSettings('<?php echo $dept; ?>', '<?php echo $xuong; ?>')" style="background-color: #ffc107; color: #212529;">
                    <i class="fas fa-sync-alt"></i> Áp dụng giá trị mặc định
                </button>
            </div>
        </div>

