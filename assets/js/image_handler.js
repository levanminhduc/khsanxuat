// Trang Xử lý hình ảnh: cấu hình Lightbox + form đánh giá điểm.
// Dữ liệu PHP được nạp qua window.IMAGE_HANDLER_BOOTSTRAP (khai báo inline trong image_handler.php).
(function () {
    var bootstrap = window.IMAGE_HANDLER_BOOTSTRAP || {};
    var tieuchiData = bootstrap.tieuchiData || {};
    var isKeHoach = bootstrap.isKeHoach === true;
    var scoreReset = bootstrap.scoreReset || null;

    // Cấu hình Lightbox
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': 'Hình %1 / %2',
        'disableScrolling': true,
        'fadeDuration': 300,
        'imageFadeDuration': 300,
        'positionFromTop': 50,
        'alwaysShowNavOnTouchDevices': true,
        'showImageNumberLabel': true,
        'maxWidth': 1200,
        'maxHeight': 800
    });

    // Hàm cập nhật trạng thái thực hiện dựa trên điểm
    function updateDaThucHien() {
        const diem = parseFloat(document.getElementById('diem_danhgia').value);
        // Nếu diem_danhgia_special đang hiển thị, sử dụng giá trị từ đó
        if (document.getElementById('special_points_container').style.display !== 'none') {
            const diemSpecial = parseFloat(document.getElementById('diem_danhgia_special').value);
            return diemSpecial > 0;
        }
        return diem > 0;
    }

    // Hàm tải dữ liệu của tiêu chí khi chọn
    function loadTieuchiData() {
        const tieuchiId = document.getElementById('tieuchi_id').value;
        const tieuchiThuTu = document.querySelector(`#tieuchi_id option[value="${tieuchiId}"]`)?.textContent.split('.')[0] || '';

        // Kiểm tra nếu là tiêu chí 7 hoặc 8 của bộ phận kế hoạch
        const isSpecialKeHoach = isKeHoach && (tieuchiThuTu === '7' || tieuchiThuTu === '8');

        // Hiển thị/ẩn dropdown điểm đặc biệt
        document.getElementById('special_points_container').style.display = isSpecialKeHoach ? 'block' : 'none';
        document.getElementById('diem_danhgia').style.display = isSpecialKeHoach ? 'none' : 'block';

        // Nếu có dữ liệu cho tiêu chí này, hiển thị thông tin
        if (tieuchiData[tieuchiId]) {
            const data = tieuchiData[tieuchiId];

            // Điền giá trị
            if (isSpecialKeHoach) {
                document.getElementById('diem_danhgia_special').value = data.diem_danhgia;
            } else {
                document.getElementById('diem_danhgia').value = data.diem_danhgia;
            }

            document.getElementById('nguoi_thuchien').value = data.nguoi_thuchien;
            document.getElementById('ghichu').value = data.ghichu || '';
        } else {
            // Reset form
            document.getElementById('diem_danhgia').value = '0';
            document.getElementById('diem_danhgia_special').value = '0';
            document.getElementById('ghichu').value = '';
            // Giữ nguyên người thực hiện nếu đã chọn
        }
    }
    window.loadTieuchiData = loadTieuchiData;

    // Khi trang tải xong, kiểm tra tiêu chí đã chọn
    document.addEventListener('DOMContentLoaded', function() {
        // Nếu có tiêu chí được chọn trong URL, tải dữ liệu
        const urlParams = new URLSearchParams(window.location.search);
        const tieuchiId = urlParams.get('tieuchi_id');

        if (tieuchiId) {
            document.getElementById('tieuchi_id').value = tieuchiId;
            loadTieuchiData();
        }
    });

    // Hàm chuẩn bị dữ liệu trước khi gửi form
    function prepareSubmit() {
        const tieuchiId = document.getElementById('tieuchi_id').value;
        if (!tieuchiId) {
            alert('Vui lòng chọn tiêu chí!');
            return false;
        }

        const tieuchiThuTu = document.querySelector(`#tieuchi_id option[value="${tieuchiId}"]`)?.textContent.split('.')[0] || '';
        const isSpecialKeHoach = isKeHoach && (tieuchiThuTu === '7' || tieuchiThuTu === '8');

        // Nếu là tiêu chí đặc biệt của kế hoạch, sử dụng điểm đặc biệt
        if (isSpecialKeHoach) {
            const diemSpecial = document.getElementById('diem_danhgia_special').value;
            document.getElementById('diem_danhgia').value = diemSpecial;
        }

        return true;
    }
    window.prepareSubmit = prepareSubmit;

    // Hàm để chọn và di chuyển đến phần đánh giá điểm
    function editScore(tieuchiId) {
        document.getElementById('tieuchi_id').value = tieuchiId;
        loadTieuchiData();
        document.querySelector('.rating-form').scrollIntoView({ behavior: 'smooth' });
    }
    window.editScore = editScore;

    // Kiểm tra nếu có điểm đánh giá bị reset
    window.addEventListener('DOMContentLoaded', function() {
        if (scoreReset) {
            // Lưu thông tin vào localStorage để trang indexdept.php có thể đọc được
            localStorage.setItem('tieuchi_score_reset', String(scoreReset.tieuchi_reset));
            localStorage.setItem('khsanxuat_id', String(scoreReset.id));
            localStorage.setItem('dept', String(scoreReset.dept));
            localStorage.setItem('reset_timestamp', Date.now().toString());
        }
    });
})();
