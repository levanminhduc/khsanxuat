<?php
/**
 * File menu cho quản trị viên
 * Bao gồm các liên kết đến các trang quản lý hệ thống
 */
?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-cogs"></i> Quản lý hệ thống</h5>
    </div>
    <div class="list-group list-group-flush">
        <a href="settings.php" class="list-group-item list-group-item-action">
            <i class="far fa-clock"></i> Cài đặt hạn xử lý tiêu chí
        </a>
        <a href="check_deadline_system.php" class="list-group-item list-group-item-action">
            <i class="fas fa-check-circle"></i> Kiểm tra hệ thống hạn xử lý
        </a>
        <a href="manage_users.php" class="list-group-item list-group-item-action">
            <i class="fas fa-users-cog"></i> Quản lý người dùng
        </a>
        <a href="manage_departments.php" class="list-group-item list-group-item-action">
            <i class="fas fa-sitemap"></i> Quản lý bộ phận
        </a>
        <a href="manage_tieuchi.php" class="list-group-item list-group-item-action">
            <i class="fas fa-tasks"></i> Quản lý tiêu chí
        </a>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="deadlineDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-clock"></i> Quản lý Hạn Xử lý
            </a>
            <div class="dropdown-menu" aria-labelledby="deadlineDropdown">
                <a class="dropdown-item" href="check_default_settings.php"><i class="fas fa-sliders-h"></i> Kiểm tra Cài đặt Hạn Xử lý</a>
                <a class="dropdown-item" href="batch_update_deadline.php"><i class="fas fa-sync-alt"></i> Cập nhật Hàng loạt Hạn Xử lý</a>
                <a class="dropdown-item" href="check_date_display.php"><i class="fas fa-calendar-alt"></i> Kiểm tra Date Display</a>
                <a class="dropdown-item" href="help_deadline.php"><i class="fas fa-question-circle"></i> Hướng dẫn Hạn Xử lý</a>
            </div>
        </li>
    </div>
</div> 