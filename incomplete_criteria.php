<?php
// Kết nối database
include 'db_connect.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database");
}

// Khởi tạo phiên làm việc nếu chưa có
session_start();

// Lấy thông tin từ URL
$style = isset($_GET['style']) ? $_GET['style'] : '';
$stt = isset($_GET['stt']) ? intval($_GET['stt']) : 0;

// Ánh xạ tên hiển thị cho từng bộ phận và thứ tự hiển thị
$dept_order = [
    'kehoach' => 1,
    'chuanbi_sanxuat_phong_kt' => 2,
    'kho' => 3,
    'cat' => 4,
    'ep_keo' => 5,
    'co_dien' => 6,
    'chuyen_may' => 7,
    'kcs' => 8,
    'ui_thanh_pham' => 9,
    'hoan_thanh' => 10
];

$dept_names = [
    'kehoach' => 'BỘ PHẬN KẾ HOẠCH',
    'chuanbi_sanxuat_phong_kt' => 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)',
    'kho' => 'KHO NGUYÊN, PHỤ LIỆU',
    'cat' => 'BỘ PHẬN CẮT',
    'ep_keo' => 'BỘ PHẬN ÉP KEO',
    'co_dien' => 'BỘ PHẬN CƠ ĐIỆN',
    'chuyen_may' => 'BỘ PHẬN CHUYỀN MAY',
    'kcs' => 'BỘ PHẬN KCS',
    'ui_thanh_pham' => 'BỘ PHẬN ỦI THÀNH PHẨM',
    'hoan_thanh' => 'BỘ PHẬN HOÀN THÀNH'
];

// Màu sắc cho từng bộ phận (lấy từ index.php)
$dept_colors = [
    'kehoach' => '#f59e0b',
    'chuanbi_sanxuat_phong_kt' => '#8b5cf6',
    'kho' => '#06b6d4',
    'cat' => '#10b981',
    'ep_keo' => '#f97316',
    'co_dien' => '#6366f1',
    'chuyen_may' => '#ec4899',
    'kcs' => '#14b8a6',
    'ui_thanh_pham' => '#8b5cf6',
    'hoan_thanh' => '#ef4444'
];

// Lấy thông tin chung về style và stt
$sql_info = "SELECT stt, style, po, line1, xuong, ngayin, ngayout FROM khsanxuat WHERE style = ?";
if ($stt > 0) {
    $sql_info .= " AND stt = ?";
}
$sql_info .= " LIMIT 1";

$stmt_info = $connect->prepare($sql_info);
if ($stt > 0) {
    $stmt_info->bind_param("si", $style, $stt);
} else {
    $stmt_info->bind_param("s", $style);
}
$stmt_info->execute();
$result_info = $stmt_info->get_result();

if ($result_info->num_rows === 0) {
    die("Không tìm thấy thông tin cho Style này");
}

$row_info = $result_info->fetch_assoc();
$stt = $row_info['stt']; // Lấy STT từ kết quả truy vấn nếu không có trong URL

// Lấy danh sách các tiêu chí chưa hoàn thành (điểm không phải 1 hoặc 3) của tất cả các bộ phận cho style và stt đã chọn
$sql = "SELECT
            kh.stt,
            kh.style,
            kh.po,
            kh.line1,
            kh.xuong,
            kh.ngayin,
            kh.ngayout,
            tc.dept,
            tc.noidung AS tieuchi_noidung,
            tc.thutu,
            tc.nhom,
            tc.id AS tieuchi_id,
            dg.diem_danhgia,
            dg.nguoi_thuchien,
            dg.ghichu,
            dg.id AS danhgia_id
        FROM
            khsanxuat kh
        JOIN
            tieuchi_dept tc ON tc.dept IN ('" . implode("','", array_keys($dept_names)) . "')
        LEFT JOIN
            danhgia_tieuchi dg ON kh.stt = dg.id_sanxuat AND tc.id = dg.id_tieuchi
        WHERE
            kh.stt = ?
            AND (dg.diem_danhgia IS NULL OR dg.diem_danhgia = 0)
        ORDER BY
            CASE tc.dept
                WHEN 'kehoach' THEN 1
                WHEN 'chuanbi_sanxuat_phong_kt' THEN 2
                WHEN 'kho' THEN 3
                WHEN 'cat' THEN 4
                WHEN 'ep_keo' THEN 5
                WHEN 'co_dien' THEN 6
                WHEN 'chuyen_may' THEN 7
                WHEN 'kcs' THEN 8
                WHEN 'ui_thanh_pham' THEN 9
                WHEN 'hoan_thanh' THEN 10
                ELSE 11
            END,
            tc.nhom,
            tc.thutu";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $stt);
$stmt->execute();
$result = $stmt->get_result();

// Lấy danh sách người thực hiện từ bảng nhan_vien
$sql_nhanvien = "SELECT id, ten FROM nhan_vien WHERE active = 1";
$result_nhanvien = $connect->query($sql_nhanvien);
$nhanvien = [];
if ($result_nhanvien && $result_nhanvien->num_rows > 0) {
    while ($row_nv = $result_nhanvien->fetch_assoc()) {
        $nhanvien[$row_nv['id']] = $row_nv['ten'];
    }
}

// Hiển thị thông báo thành công/lỗi nếu có
if (isset($_GET['success'])) {
    echo '<div class="success-message">Thao tác thành công!</div>';
}
if (isset($_GET['error'])) {
    echo '<div class="error-message">Có lỗi xảy ra: ' . htmlspecialchars($_GET['error']) . '</div>';
}

// Đếm số tiêu chí chưa hoàn thành theo bộ phận
$dept_counts = [];
$total_incomplete = 0;

// Lưu kết quả vào mảng để xử lý
$criteria_data = [];
while ($row = $result->fetch_assoc()) {
    $criteria_data[] = $row;
    $dept = $row['dept'];
    if (!isset($dept_counts[$dept])) {
        $dept_counts[$dept] = 0;
    }
    $dept_counts[$dept]++;
    $total_incomplete++;
}

// Sắp xếp lại mảng $dept_counts theo thứ tự bộ phận
$sorted_dept_counts = [];
foreach ($dept_order as $dept => $order) {
    if (isset($dept_counts[$dept])) {
        $sorted_dept_counts[$dept] = $dept_counts[$dept];
    }
}
$dept_counts = $sorted_dept_counts;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Danh sách tiêu chí chưa hoàn thành - <?php echo htmlspecialchars($style); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <style>
        .navbar {
            background-color: #003366;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-title {
            font-size: 24px;
            font-weight: bold;
        }

        .navbar-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px 40px 20px;
        }

        .style-info {
            background-color: #f0f7ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 5px solid #1e40af;
        }

        .style-info h2 {
            margin-top: 0;
            color: #1e40af;
        }

        .criteria-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            table-layout: fixed;
        }

        /* Thêm khoảng cách cho header bộ phận */
        .criteria-table tr[id^="dept-"] {
            border-top: 15px solid white;
        }

        /* Loại bỏ khoảng cách cho header bộ phận đầu tiên */
        .criteria-table tr[id^="dept-"]:first-of-type {
            border-top: none;
        }

        .criteria-table th, .criteria-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .criteria-table th {
            background-color: #1e40af;
            color: white;
        }

        /* Loại bỏ position: sticky từ thead */
        .criteria-table thead {
            background-color: #1e40af;
        }

        /* Thêm lớp sticky-header mới */
        .sticky-header {
            display: none;
            position: fixed;
            top: 245px;
            left: 0;
            right: 0;
            z-index: 99;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
            padding: 0;
            margin: 0 auto;
        }

        .criteria-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .criteria-table tr:hover {
            background-color: #f0f7ff;
        }

        .dept-header {
            font-weight: bold;
            color: white;
            padding: 10px !important;
            transition: all 0.3s ease;
        }

        /* Thêm lớp mới cho header bộ phận khi được chọn */
        .dept-header.active {
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.5);
        }

        .group-header {
            background-color: #f0f7ff;
            font-weight: bold;
            color: #2563eb;
            font-style: italic;
        }

        .status-incomplete {
            background-color: #F44336;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            text-align: center;
        }

        .btn-back {
            background-color: #1e40af;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn-back:hover {
            background-color: #153e75;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }

        .summary-section {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 35px;
            position: sticky;
            top: 70px;
            background-color: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .summary-card {
            flex: 1;
            min-width: 120px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 10px;
            text-align: center;
            transition: transform 0.2s;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .summary-card.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border: 2px solid;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .summary-card h3 {
            margin-top: 0;
            font-size: 12px;
            color: #4b5563;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .summary-card .count {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
        }

        .summary-card.total {
            background-color: #f0f7ff;
        }

        .summary-card.total::before {
            background-color: #3b82f6;
        }

        .summary-card.total .count {
            color: #3b82f6;
        }

        .progress-container {
            margin-top: 10px;
            background-color: #f3f4f6;
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: #10b981;
        }

        .content-container {
            display: flex;
            flex-direction: column;
            min-height: 90vh;
        }

        .table-container {
            flex-grow: 1;
            overflow-y: auto;
            padding-right: 10px;
            padding-top: 25px;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 300px;
        }
    </style>
    <script>
        // Hàm cuộn đến bộ phận khi nhấp vào thẻ tổng kết
        function scrollToDept(deptId) {
            const element = document.getElementById('dept-' + deptId);
            if (element) {
                // Thêm offset để đảm bảo tiêu đề bộ phận không bị che khuất
                const offset = 300;
                const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
                window.scrollTo({
                    top: elementPosition - offset,
                    behavior: 'smooth'
                });

                // Đánh dấu thẻ tổng kết đang được chọn
                const summaryCards = document.querySelectorAll('.summary-card');
                summaryCards.forEach(card => {
                    card.classList.remove('active');
                });

                const activeCard = document.getElementById('summary-' + deptId);
                if (activeCard) {
                    activeCard.classList.add('active');
                }
            }
        }

        // Theo dõi vị trí cuộn để đánh dấu thẻ tổng kết tương ứng
        document.addEventListener('DOMContentLoaded', function() {
            // Phương pháp mới: Sử dụng một div cố định để chứa bảng tiêu đề
            const tableContainer = document.querySelector('.table-container');
            const originalTable = document.querySelector('.criteria-table');

            // Tạo một div container cho tiêu đề cố định
            const stickyContainer = document.createElement('div');
            stickyContainer.className = 'sticky-header';
            stickyContainer.style.display = 'none';

            // Sao chép toàn bộ bảng để đảm bảo cấu trúc và độ rộng cột giống nhau
            const clonedTable = originalTable.cloneNode(true);

            // Chỉ giữ lại phần thead, xóa phần tbody
            const tbody = clonedTable.querySelector('tbody');
            if (tbody) {
                tbody.parentNode.removeChild(tbody);
            }

            // Thêm bảng đã sao chép vào container
            stickyContainer.appendChild(clonedTable);

            // Thêm vào DOM - đặt trực tiếp vào table-container để đảm bảo căn chỉnh chính xác
            tableContainer.insertBefore(stickyContainer, originalTable);

            // Đảm bảo tất cả tiêu đề bộ phận đều có ID
            const deptCells = document.querySelectorAll('.dept-header');
            deptCells.forEach(cell => {
                const parentRow = cell.parentElement;
                if (!parentRow.id) {
                    const deptName = cell.textContent.trim();
                    // Tạo ID ngẫu nhiên nếu cần
                    parentRow.id = 'dept-row-' + Math.random().toString(36).substring(2, 9);
                }
            });

            // Theo dõi vị trí cuộn
            window.addEventListener('scroll', function() {
                const deptHeaders = document.querySelectorAll('[id^="dept-"]');
                const scrollPosition = window.scrollY;
                const tableRect = originalTable.getBoundingClientRect();

                // Tìm vị trí của tiêu đề bộ phận đầu tiên
                let firstDeptHeaderPos = 0;
                if (deptHeaders.length > 0) {
                    firstDeptHeaderPos = deptHeaders[0].getBoundingClientRect().top + window.scrollY;
                }

                // Hiển thị tiêu đề cố định chỉ khi đã cuộn qua tiêu đề bộ phận đầu tiên
                if (scrollPosition > firstDeptHeaderPos - 220) {
                    stickyContainer.style.display = 'block';

                    // Đảm bảo tiêu đề cố định có cùng độ rộng và vị trí với bảng gốc
                    const tableWidth = tableRect.width;
                    const tableLeft = tableRect.left;

                    stickyContainer.style.width = tableWidth + 'px';
                    stickyContainer.style.left = tableLeft + 'px';
                    stickyContainer.style.right = 'auto'; // Vô hiệu hóa right: 0 để tránh xung đột

                    // Thêm timeout để hiệu ứng opacity hoạt động
                    setTimeout(function() {
                        stickyContainer.style.opacity = '1';
                    }, 10);
                } else {
                    stickyContainer.style.opacity = '0';
                    setTimeout(function() {
                        if (scrollPosition <= firstDeptHeaderPos - 220) {
                            stickyContainer.style.display = 'none';
                        }
                    }, 300);
                }

                // Đánh dấu thẻ tổng kết tương ứng với bộ phận đang xem
                let currentDeptId = null;

                deptHeaders.forEach(header => {
                    if (header.getBoundingClientRect().top <= 300) {
                        currentDeptId = header.id.replace('dept-', '');
                    }

                    // Loại bỏ lớp active cho tất cả các tiêu đề
                    header.classList.remove('active');
                });

                if (currentDeptId) {
                    const summaryCards = document.querySelectorAll('.summary-card');
                    summaryCards.forEach(card => {
                        card.classList.remove('active');
                    });

                    const activeCard = document.getElementById('summary-' + currentDeptId);
                    if (activeCard) {
                        activeCard.classList.add('active');

                        // Đánh dấu tiêu đề bộ phận tương ứng
                        const activeDeptHeader = document.getElementById('dept-' + currentDeptId);
                        if (activeDeptHeader) {
                            activeDeptHeader.classList.add('active');
                        }
                    }
                }
            });

            // Đảm bảo kích thước khớp khi thay đổi kích thước cửa sổ
            window.addEventListener('resize', function() {
                if (stickyContainer.style.display === 'block') {
                    const tableRect = originalTable.getBoundingClientRect();
                    stickyContainer.style.width = tableRect.width + 'px';
                    stickyContainer.style.left = tableRect.left + 'px';
                }
            });
        });
    </script>
</head>
<body>
    <?php
    $header_config = [
        'title' => 'DANH SÁCH TIÊU CHÍ CHƯA HOÀN THÀNH',
        'title_short' => 'Chưa hoàn thành',
        'logo_path' => 'img/logoht.png',
        'logo_link' => '/trangchu/',
        'show_search' => false,
        'show_mobile_menu' => true,
        'actions' => []
    ];
    ?>
    <?php include 'components/header.php'; ?>

    <div class="container">
        <div class="style-info">
            <h2>Style: <?php echo htmlspecialchars($row_info['style']); ?> (STT: <?php echo $row_info['stt']; ?>)</h2>
            <?php
            $ngayin = new DateTime($row_info['ngayin']);
            $ngayout = new DateTime($row_info['ngayout']);

            echo '<p><strong>PO:</strong> ' . htmlspecialchars($row_info['po']) . '</p>';
            echo '<p><strong>Line:</strong> ' . htmlspecialchars($row_info['line1']) . '</p>';
            echo '<p><strong>Xưởng:</strong> ' . htmlspecialchars($row_info['xuong']) . '</p>';
            echo '<p><strong>Ngày vào:</strong> ' . $ngayin->format('d/m/Y') . '</p>';
            echo '<p><strong>Ngày ra:</strong> ' . $ngayout->format('d/m/Y') . '</p>';
            ?>
        </div>

        <!-- Thêm phần tổng kết -->
        <div class="summary-section">
            <div class="summary-card total" id="summary-total" onclick="window.scrollTo(0, 0);">
                <h3>Tổng tiêu chí</h3>
                <div class="count"><?php echo $total_incomplete; ?></div>
            </div>

            <?php foreach ($dept_counts as $dept => $count): ?>
                <div class="summary-card" id="summary-<?php echo $dept; ?>" style="border-top: 4px solid <?php echo $dept_colors[$dept]; ?>;" onclick="scrollToDept('<?php echo $dept; ?>')">
                    <h3><?php echo $dept_names[$dept]; ?></h3>
                    <div class="count" style="color: <?php echo $dept_colors[$dept]; ?>;"><?php echo $count; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="content-container">
            <div class="table-container">
                <?php if (count($criteria_data) > 0): ?>
                    <table class="criteria-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">STT</th>
                                <th style="width: 18%;">Bộ phận</th>
                                <th style="width: 42%;">Tiêu chí</th>
                                <th style="width: 15%;">Người chịu trách nhiệm</th>
                                <th style="width: 10%;">Trạng thái</th>
                                <th style="width: 10%;">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_dept = '';
                            $current_group = '';
                            $stt_display = 1;

                            foreach ($criteria_data as $row) {
                                $dept_display = isset($dept_names[$row['dept']]) ? $dept_names[$row['dept']] : $row['dept'];
                                $dept_color = isset($dept_colors[$row['dept']]) ? $dept_colors[$row['dept']] : '#333333';

                                // Hiển thị header bộ phận nếu thay đổi
                                if ($current_dept != $row['dept']) {
                                    $current_dept = $row['dept'];
                                    $current_group = ''; // Reset nhóm khi đổi bộ phận
                                    echo '<tr id="dept-' . $row['dept'] . '">';
                                    echo '<td colspan="6" class="dept-header" style="background-color: ' . $dept_color . ';">' . $dept_display . '</td>';
                                    echo '</tr>';
                                }

                                // Hiển thị header nhóm cho bộ phận Kỹ Thuật và Kho nếu có
                                if (($row['dept'] == 'chuanbi_sanxuat_phong_kt' || $row['dept'] == 'kho') &&
                                    !empty($row['nhom']) && $current_group != $row['nhom']) {
                                    $current_group = $row['nhom'];
                                    echo '<tr class="group-header">';
                                    echo '<td colspan="6">' . htmlspecialchars($row['nhom']) . '</td>';
                                    echo '</tr>';
                                }

                                echo '<tr>';
                                echo '<td style="width: 5%; text-align: center;">' . $stt_display++ . '</td>';
                                echo '<td style="width: 18%;">' . $dept_display . '</td>';
                                echo '<td style="width: 42%;">' . $row['thutu'] . '. ' . htmlspecialchars($row['tieuchi_noidung']) . '</td>';
                                echo '<td style="width: 15%; text-align: center;">' . htmlspecialchars($nhanvien[$row['nguoi_thuchien']] ?? 'Chưa phân công') . '</td>';
                                echo '<td style="width: 10%; text-align: center;"><span class="status-incomplete">Chưa hoàn thành</span></td>';
                                echo '<td style="width: 10%;">' . htmlspecialchars($row['ghichu'] ?? '') . '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>Không có tiêu chí nào chưa hoàn thành cho Style này.</p>
                    </div>
                <?php endif; ?>
            </div>

            <a href="index.php" class="btn-back">Quay lại trang chủ</a>
        </div>
    </div>
    <script src="assets/js/header.js"></script>
</body>
</html>