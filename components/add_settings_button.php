<?php
/**
 * Template này chứa mã HTML và JavaScript để thêm nút cài đặt hạn xử lý vào giao diện.
 * Hướng dẫn sử dụng:
 * 1. Include file này vào file index.php hoặc file hiển thị danh sách đơn hàng
 * 2. Thêm class 'btn-deadline-settings' vào nút cài đặt hạn xử lý, và thêm data-id và data-dept
 * Ví dụ: <button class="btn btn-sm btn-info btn-deadline-settings" data-id="123" data-dept="kehoach">Cài đặt hạn</button>
 * 3. Đảm bảo đã include settings_deadline.php vào file index để hiển thị modal cài đặt hạn xử lý
 */
?>

<!-- Nút cài đặt hạn xử lý mẫu -->
<div class="dropdown d-inline-block">
    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-cog"></i> Thao tác
    </button>
    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <!-- Thêm vào menu dropdown -->
        <a href="#" class="dropdown-item btn-deadline-settings" data-id="<?php echo $row['stt']; ?>" data-dept="<?php echo $dept; ?>">
            <i class="far fa-clock"></i> Cài đặt hạn xử lý
        </a>
        <!-- Các thao tác khác -->
        <a href="#" class="dropdown-item view-details" data-id="<?php echo $row['stt']; ?>">
            <i class="far fa-eye"></i> Xem chi tiết
        </a>
        <a href="#" class="dropdown-item edit-order" data-id="<?php echo $row['stt']; ?>">
            <i class="far fa-edit"></i> Sửa đơn hàng
        </a>
    </div>
</div>

<!-- CSS cho các badge và button -->
<style>
.badge-deadline-ok {
    background-color: #28a745;
    color: white;
}

.badge-deadline-warning {
    background-color: #ffc107;
    color: black;
}

.badge-deadline-danger {
    background-color: #dc3545;
    color: white;
}

.badge-deadline-info {
    background-color: #17a2b8;
    color: white;
}

.badge-deadline-none {
    background-color: #6c757d;
    color: white;
}
</style>

<!-- Script để xử lý sự kiện click vào nút cài đặt hạn xử lý -->
<script>
$(document).ready(function() {
    // Sự kiện khi click vào nút cài đặt hạn xử lý
    $(document).on('click', '.btn-deadline-settings', function(e) {
        e.preventDefault();
        
        var id_sanxuat = $(this).data('id');
        var dept = $(this).data('dept');
        
        showDeadlineSettings(id_sanxuat, dept);
    });
    
    // Hàm hiển thị thông báo
    function showAlert(type, title, message) {
        var icon = 'info';
        if (type === 'success') icon = 'success';
        if (type === 'error') icon = 'error';
        if (type === 'warning') icon = 'warning';
        
        Swal.fire({
            icon: icon,
            title: title,
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    }
});
</script> 