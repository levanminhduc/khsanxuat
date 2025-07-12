<!-- Modal Cài đặt hạn xử lý tiêu chí -->
<div class="modal fade" id="settingsDeadlineModal" tabindex="-1" role="dialog" aria-labelledby="settingsDeadlineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingsDeadlineModalLabel">Cài đặt hạn xử lý tiêu chí</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Hạn xử lý tiêu chí được tính dựa trên ngày vào hoặc ngày ra của đơn hàng, và số ngày xử lý.
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Cài đặt hạn xử lý mặc định</h5>
                            </div>
                            <div class="card-body">
                                <p>Cài đặt này sẽ áp dụng cho tất cả đơn hàng mới. Các đơn hàng hiện tại sẽ không bị ảnh hưởng trừ khi bạn chọn áp dụng lại cài đặt mặc định.</p>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Thời gian mặc định:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="number" id="default_so_ngay_xuly" class="form-control" value="7" min="1" max="30">
                                            <div class="input-group-append">
                                                <span class="input-group-text">ngày</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Tính hạn dựa trên:</label>
                                    <div class="col-sm-8">
                                        <select id="default_ngay_tinh_han" class="form-control">
                                            <option value="ngay_vao">Ngày vào</option>
                                            <option value="ngay_vao_cong">Ngày vào + số ngày</option>
                                            <option value="ngay_ra">Ngày ra</option>
                                            <option value="ngay_ra_tru">Ngày ra - số ngày</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button id="btn_save_default_settings" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Lưu cài đặt mặc định
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Cài đặt hạn xử lý cho đơn hàng hiện tại</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Tiêu chí</th>
                                                <th width="120">Số ngày</th>
                                                <th width="200">Tính hạn dựa trên</th>
                                                <th width="120">Trạng thái</th>
                                                <th width="100">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tieuchi_deadline_settings">
                                            <!-- Danh sách tiêu chí sẽ được thêm bằng JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="form-check mb-3 mt-3">
                                    <input class="form-check-input" type="checkbox" id="apply_to_default">
                                    <label class="form-check-label" for="apply_to_default">
                                        Áp dụng thay đổi này vào cài đặt mặc định
                                    </label>
                                    <small class="form-text text-muted">
                                        Khi chọn tùy chọn này, mọi thay đổi bạn thực hiện sẽ được áp dụng vào cài đặt mặc định cho tiêu chí tương ứng
                                    </small>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="apply_to_all">
                                    <label class="form-check-label" for="apply_to_all">
                                        Áp dụng cùng hạn xử lý cho tất cả tiêu chí
                                    </label>
                                </div>
                                
                                <div id="all_tieuchi_settings" class="card p-3 mb-3" style="display: none;">
                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label">Số ngày xử lý:</label>
                                        <div class="col-sm-8">
                                            <div class="input-group">
                                                <input type="number" id="all_so_ngay_xuly" class="form-control" value="7" min="1" max="30">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">ngày</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <label class="col-sm-4 col-form-label">Tính hạn dựa trên:</label>
                                        <div class="col-sm-8">
                                            <select id="all_ngay_tinh_han" class="form-control">
                                                <option value="ngay_vao">Ngày vào</option>
                                                <option value="ngay_vao_cong">Ngày vào + số ngày</option>
                                                <option value="ngay_ra">Ngày ra</option>
                                                <option value="ngay_ra_tru">Ngày ra - số ngày</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <button id="btn_apply_all" class="btn btn-primary">
                                        <i class="fa fa-check"></i> Áp dụng cho tất cả tiêu chí
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" id="btn_save_settings" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </div>
    </div>
</div>

<!-- Script xử lý cài đặt hạn xử lý -->
<script>
// Biến lưu ID sản xuất hiện tại
var currentSanxuatId;
var currentDept;

// Hiển thị modal cài đặt hạn xử lý
function showDeadlineSettings(id_sanxuat, dept) {
    currentSanxuatId = id_sanxuat;
    currentDept = dept;
    
    // Reset các tùy chọn
    $('#apply_to_all').prop('checked', false);
    $('#apply_to_default').prop('checked', false);
    $('#all_tieuchi_settings').hide();
    
    // Lấy danh sách tiêu chí và cài đặt hiện tại
    $.ajax({
        url: 'get_tieuchi_deadline.php',
        type: 'GET',
        data: {
            id_sanxuat: id_sanxuat,
            dept: dept
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Hiển thị danh sách tiêu chí
                displayTieuchiDeadline(response.tieuchi);
                
                // Hiển thị cài đặt mặc định
                $('#default_so_ngay_xuly').val(response.default_settings.so_ngay_xuly || 7);
                $('#default_ngay_tinh_han').val(response.default_settings.ngay_tinh_han || 'ngay_vao');
                
                // Hiển thị modal
                $('#settingsDeadlineModal').modal('show');
            } else {
                showAlert('error', 'Lỗi', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Lỗi', 'Không thể tải danh sách tiêu chí.');
        }
    });
}

// Hiển thị danh sách tiêu chí và cài đặt hạn xử lý
function displayTieuchiDeadline(tieuchi) {
    var html = '';
    
    $.each(tieuchi, function(index, item) {
        var status_badge = '';
        if (item.is_custom) {
            status_badge = '<span class="badge badge-info">Tùy chỉnh</span>';
        } else {
            status_badge = '<span class="badge badge-secondary">Mặc định</span>';
        }
        
        html += '<tr data-id="' + item.id + '">';
        html += '<td>' + item.noidung + '</td>';
        html += '<td>';
        html += '<div class="input-group input-group-sm">';
        html += '<input type="number" class="form-control form-control-sm so_ngay_xuly" value="' + (item.so_ngay_xuly || 7) + '" min="1" max="30">';
        html += '<div class="input-group-append">';
        html += '<span class="input-group-text">ngày</span>';
        html += '</div>';
        html += '</div>';
        html += '</td>';
        html += '<td>';
        html += '<select class="form-control form-control-sm ngay_tinh_han">';
        html += '<option value="ngay_vao" ' + (item.ngay_tinh_han === 'ngay_vao' ? 'selected' : '') + '>Ngày vào</option>';
        html += '<option value="ngay_vao_cong" ' + (item.ngay_tinh_han === 'ngay_vao_cong' ? 'selected' : '') + '>Ngày vào + số ngày</option>';
        html += '<option value="ngay_ra" ' + (item.ngay_tinh_han === 'ngay_ra' ? 'selected' : '') + '>Ngày ra</option>';
        html += '<option value="ngay_ra_tru" ' + (item.ngay_tinh_han === 'ngay_ra_tru' ? 'selected' : '') + '>Ngày ra - số ngày</option>';
        html += '</select>';
        html += '</td>';
        html += '<td>' + status_badge + '</td>';
        html += '<td>';
        html += '<button type="button" class="btn btn-primary btn-sm btn-update-deadline" data-id="' + item.id + '">';
        html += '<i class="fa fa-save"></i> Lưu';
        html += '</button>';
        html += '</td>';
        html += '</tr>';
    });
    
    $('#tieuchi_deadline_settings').html(html);
}

// Khi checkbox "Áp dụng cho tất cả tiêu chí" được thay đổi
$(document).on('change', '#apply_to_all', function() {
    if ($(this).is(':checked')) {
        $('#all_tieuchi_settings').slideDown();
    } else {
        $('#all_tieuchi_settings').slideUp();
    }
});

// Khi nút "Áp dụng cho tất cả tiêu chí" được nhấn
$(document).on('click', '#btn_apply_all', function() {
    var so_ngay_xuly = $('#all_so_ngay_xuly').val();
    var ngay_tinh_han = $('#all_ngay_tinh_han').val();
    var is_default = $('#apply_to_default').is(':checked');
    
    // Áp dụng giá trị vào tất cả tiêu chí
    $('#tieuchi_deadline_settings tr').each(function() {
        $(this).find('.so_ngay_xuly').val(so_ngay_xuly);
        $(this).find('.ngay_tinh_han').val(ngay_tinh_han);
    });
    
    // Hiển thị thông báo
    showAlert('success', 'Thành công', 'Đã áp dụng cài đặt cho tất cả tiêu chí.');
});

// Khi nút "Lưu cài đặt mặc định" được nhấn
$(document).on('click', '#btn_save_default_settings', function() {
    var so_ngay_xuly = $('#default_so_ngay_xuly').val();
    var ngay_tinh_han = $('#default_ngay_tinh_han').val();
    
    $.ajax({
        url: 'save_default_settings.php',
        type: 'POST',
        data: {
            dept: currentDept,
            so_ngay_xuly: so_ngay_xuly,
            ngay_tinh_han: ngay_tinh_han
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Thành công', 'Đã lưu cài đặt mặc định.');
            } else {
                showAlert('error', 'Lỗi', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Lỗi', 'Không thể lưu cài đặt mặc định.');
        }
    });
});

// Khi nút "Lưu thay đổi" được nhấn
$(document).on('click', '#btn_save_settings', function() {
    // Lưu tất cả các thay đổi
    var promises = [];
    
    $('#tieuchi_deadline_settings tr').each(function() {
        var id_tieuchi = $(this).data('id');
        var so_ngay_xuly = $(this).find('.so_ngay_xuly').val();
        var ngay_tinh_han = $(this).find('.ngay_tinh_han').val();
        var is_default = $('#apply_to_default').is(':checked');
        
        var promise = updateDeadline(id_tieuchi, so_ngay_xuly, ngay_tinh_han, is_default);
        promises.push(promise);
    });
    
    // Khi tất cả đã hoàn thành
    Promise.all(promises).then(function(results) {
        showAlert('success', 'Thành công', 'Đã lưu tất cả cài đặt hạn xử lý.');
        $('#settingsDeadlineModal').modal('hide');
        
        // Nếu có cần reload dữ liệu
        if (typeof loadSanxuatData === 'function') {
            loadSanxuatData();
        }
    }).catch(function(error) {
        showAlert('error', 'Lỗi', 'Có lỗi xảy ra khi lưu cài đặt.');
    });
});

// Khi nút "Lưu" trên từng dòng được nhấn
$(document).on('click', '.btn-update-deadline', function() {
    var row = $(this).closest('tr');
    var id_tieuchi = $(this).data('id');
    var so_ngay_xuly = row.find('.so_ngay_xuly').val();
    var ngay_tinh_han = row.find('.ngay_tinh_han').val();
    var is_default = $('#apply_to_default').is(':checked');
    
    updateDeadline(id_tieuchi, so_ngay_xuly, ngay_tinh_han, is_default).then(function(result) {
        if (result.success) {
            row.find('td:eq(3)').html('<span class="badge badge-info">Tùy chỉnh</span>');
            showAlert('success', 'Thành công', 'Đã cập nhật hạn xử lý.');
        }
    });
});

// Hàm cập nhật hạn xử lý
function updateDeadline(id_tieuchi, so_ngay_xuly, ngay_tinh_han, is_default) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'update_deadline_tieuchi.php',
            type: 'POST',
            data: {
                id_sanxuat: currentSanxuatId,
                id_tieuchi: id_tieuchi,
                so_ngay_xuly: so_ngay_xuly,
                ngay_tinh_han: ngay_tinh_han,
                dept: currentDept,
                is_default: is_default
            },
            dataType: 'json',
            success: function(response) {
                resolve(response);
            },
            error: function(xhr, status, error) {
                reject({
                    success: false,
                    message: 'Không thể cập nhật hạn xử lý'
                });
            }
        });
    });
}
</script> 