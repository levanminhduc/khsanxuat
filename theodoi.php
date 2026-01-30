<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Bắt đầu session nếu chưa được bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    // Lưu URL hiện tại để redirect sau khi đăng nhập
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Kết nối database
include 'db_connect.php';
include 'activity_logger.php';

// Kiểm tra kết nối
if (!$connect) {
    die("Lỗi kết nối database");
}

// Lấy logger
$logger = getActivityLogger($connect);

// Xử lý các tham số lọc
$filters = [];
if (isset($_GET['user_name'])) {
    $filters['user_name'] = $_GET['user_name'];
}
if (isset($_GET['action_type'])) {
    $filters['action_type'] = $_GET['action_type'];
}
if (isset($_GET['id_khsanxuat'])) {
    $filters['id_khsanxuat'] = intval($_GET['id_khsanxuat']);
}

// Mặc định giới hạn 100 bản ghi mỗi trang
$filters['limit'] = isset($_GET['limit']) ? min(intval($_GET['limit']), 1000) : 100;

// Lấy danh sách hoạt động
$activities = $logger->getActivityLogs($filters);

// Ánh xạ tên hiển thị cho các loại hành động
$action_types = [
    'update_score' => 'Cập nhật điểm',
    'update_person' => 'Thay đổi người thực hiện',
    'update_note' => 'Cập nhật ghi chú',
    'update_multiple' => 'Cập nhật nhiều thông tin',
    'add_image' => 'Thêm hình ảnh',
    'add_template' => 'Thêm biểu mẫu',
    'delete_image' => 'Xóa hình ảnh',
    'delete_template' => 'Xóa biểu mẫu'
];

// Ánh xạ tên hiển thị cho các loại đối tượng
$target_types = [
    'tieuchi' => 'Tiêu chí',
    'image' => 'Hình ảnh',
    'template' => 'Biểu mẫu'
];

// Ánh xạ tên bộ phận
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THEO DÕI THÔNG TIN CẬP NHẬP ĐÁNH GIÁ</title>
    <link rel="stylesheet" href="assets/css/header.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        /* Note: Navbar styles now handled by shared header.css */

        .container {
            max-width: 1200px;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1a365d;
            margin-bottom: 20px;
        }

        .filter-form {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-form select, .filter-form input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
        }

        .filter-form button {
            padding: 8px 16px;
            background-color: #1a365d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .filter-form button:hover {
            background-color: #0d2240;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #1a365d;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f2f2f2;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-update {
            background-color: #28a745;
            color: white;
        }

        .badge-delete {
            background-color: #dc3545;
            color: white;
        }

        .badge-add {
            background-color: #17a2b8;
            color: white;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            /* font-weight: bold; */
            margin-bottom: 3px;
        }

        .user-full-name {
            font-weight: bold;
            font-size: 0.9em;
            color: rgb(0, 0, 0);
        }

        .value-diff {
            background-color: #fff3cd;
            padding: 8px;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 14px;
        }

        .value-diff .old {
            color: #dc3545;
            text-decoration: line-through;
        }

        .value-diff .new {
            color: #28a745;
        }

        .filter-info {
            margin-top: 10px;
            padding: 8px;
            background-color: #e9f5fb;
            border-radius: 4px;
            font-size: 14px;
        }

        .highlight {
            background-color: #ffffcc !important;
        }

        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }

            .filter-form select, .filter-form input {
                width: 100%;
                margin-right: 0;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <!-- Thanh điều hướng - Shared Header Component -->
    <?php
    $header_config = [
        'title' => 'THEO DÕI THÔNG TIN CẬP NHẬP ĐÁNH GIÁ',
        'title_short' => 'Theo Dõi',
        'logo_path' => 'img/logoht.png',
        'logo_link' => '/khsanxuat/index.php',
        'show_search' => false,
        'show_mobile_menu' => true,
        'actions' => []
    ];
    include 'components/header.php';
    ?>

    <div class="container">
        <form class="filter-form" method="GET" id="filter-form">
            <select name="action_type" id="action_type">
                <option value="">Tất cả hoạt động</option>
                <?php foreach ($action_types as $key => $value): ?>
                <option value="<?php echo $key; ?>" <?php echo isset($_GET['action_type']) && $_GET['action_type'] === $key ? 'selected' : ''; ?>>
                    <?php echo $value; ?>
                </option>
                <?php endforeach; ?>
            </select>

            <select id="details_dept" name="details_dept">
                <option value="">Lọc bộ phận trong chi tiết</option>
            </select>

            <input type="number" name="id_khsanxuat" id="id_khsanxuat" placeholder="ID mã hàng" 
                   value="<?php echo isset($_GET['id_khsanxuat']) ? htmlspecialchars($_GET['id_khsanxuat']) : ''; ?>">

            <input type="number" name="limit" id="limit" placeholder="Số lượng hiển thị" 
                   value="<?php echo isset($_GET['limit']) ? htmlspecialchars($_GET['limit']) : '100'; ?>">

            <button type="submit">Lọc</button>
        </form>

        <div id="filter-info" class="filter-info" style="display: none;">
            <strong>Bộ lọc hiện tại:</strong> <span id="current-filter"></span>
            <button type="button" id="btn-clear-filter" style="margin-left: 10px; padding: 2px 8px;">Xóa bộ lọc</button>
        </div>

        <table id="activity-table">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người thực hiện</th>
                    <th>Hoạt động</th>
                    <th>Mã hàng</th>
                    <th>Chi tiết thay đổi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                <tr class="activity-row" data-dept="<?php echo htmlspecialchars($activity['dept']); ?>">
                    <td><?php echo date('d/m/Y H:i:s', strtotime($activity['action_time'])); ?></td>
                    <td>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($activity['user_name']); ?></div>
                            <div class="user-full-name"><?php echo htmlspecialchars($activity['user_full_name']); ?></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?php 
                            echo strpos($activity['action_type'], 'update_') === 0 ? 'update' : 
                                (strpos($activity['action_type'], 'delete_') === 0 ? 'delete' : 'add'); 
                        ?>">
                            <?php echo $action_types[$activity['action_type']] ?? $activity['action_type']; ?>
                        </span>
                    </td>
                    <td><?php echo $activity['id_khsanxuat']; ?></td>
                    <td class="details-cell">
                        <?php if ($activity['old_value'] !== null || $activity['new_value'] !== null): ?>
                        <!-- <div class="value-diff">
                            <?php if ($activity['new_value'] !== null): ?>
                            <div class="new">Giá trị mới: <?php echo nl2br(htmlspecialchars($activity['new_value'])); ?></div>
                            <?php endif; ?>
                        </div> -->
                        <?php endif; ?>
                        <?php if ($activity['additional_info']): ?>
                        <div class="additional-info">
                            <?php 
                            $info = json_decode($activity['additional_info'], true);
                            if ($info) {
                                // Lấy mã bộ phận từ additional_info nếu có
                                $dept_code = '';
                                if (isset($info['dept_info']) && isset($info['dept_info']['dept_code'])) {
                                    $dept_code = $info['dept_info']['dept_code'];
                                    echo '<div class="dept-info" data-dept-code="' . htmlspecialchars($dept_code) . '">';
                                } else {
                                    echo '<div class="dept-info" data-dept-code="' . htmlspecialchars($activity['dept']) . '">';
                                }
                                
                                // Hiển thị thông tin bộ phận
                                if (isset($info['dept_info']) && isset($info['dept_info']['dept_name'])) {
                                    // Lấy tên bộ phận từ additional_info
                                    $dept_display = $info['dept_info']['dept_name'];
                                } else {
                                    // Sử dụng phương pháp hiện tại nếu không có trong additional_info
                                    $dept_info = isset($activity['dept']) ? $activity['dept'] : '';
                                    $dept_display = isset($dept_names[$dept_info]) ? $dept_names[$dept_info] : $dept_info;
                                }
                                
                                echo "<strong>Bộ phận:</strong> " . htmlspecialchars($dept_display) . "</div>";
                                
                                // Hiển thị ghi chú trước nếu có
                                // Hiển thị chi tiết ghi chú từng tiêu chí
                                // if (isset($info['note_entries']) && is_array($info['note_entries']) && !empty($info['note_entries'])) {
                                //     echo "<ul style='margin: 5px 0; padding-left: 20px;'>";
                                //     foreach ($info['note_entries'] as $note_entry) {
                                //         echo "<li>Tiêu chí " . htmlspecialchars($note_entry['thutu']) . ": " . 
                                //              htmlspecialchars($note_entry['noidung']) . 
                                //              "<div class='note-content' style='font-style:italic; color:#555; margin-left:5px;'>" . 
                                //              nl2br(htmlspecialchars($note_entry['ghichu'])) . 
                                //              "</div></li>";
                                //     }
                                //     echo "</ul>";
                                // } else if (isset($info['note'])) {
                                //     echo "<div style='margin-top: 8px;'>" . nl2br(htmlspecialchars($info['note'])) . "</div>";
                                // }
                                
                                if (isset($info['changes'])) {
                                    echo "<div><strong>Thay đổi:</strong> " . htmlspecialchars($info['changes']) . "</div>";
                                }
                                
                                if (isset($info['changed_tieuchi']) && is_array($info['changed_tieuchi'])) {
                                    echo "<ul style='margin: 5px 0; padding-left: 20px;'>";
                                    
                                    // Phân tích dữ liệu old_value và new_value để hiển thị chỉ các phần thay đổi
                                    $old_parts = explode(" | ", $activity['old_value']);
                                    $new_parts = explode(" | ", $activity['new_value']);
                                    $tieuchi_changes = [];
                                    
                                    // Tạo một mảng để lưu trữ thông tin thay đổi theo từng tiêu chí
                                    foreach ($old_parts as $idx => $old_part) {
                                        if (isset($new_parts[$idx])) {
                                            // Phân tích chuỗi để lấy thông tin tiêu chí (ví dụ: "tiêu chí 1: điểm 4, người thực hiện John")
                                            if (preg_match('/tiêu chí (\d+)/', $old_part, $matches)) {
                                                $tc_num = $matches[1];
                                                
                                                // Lấy giá trị điểm cũ và mới
                                                $old_score = null;
                                                if (preg_match('/điểm (\d+)/', $old_part, $matches)) {
                                                    $old_score = $matches[1];
                                                }
                                                
                                                $new_score = null;
                                                if (preg_match('/điểm (\d+)/', $new_parts[$idx], $matches)) {
                                                    $new_score = $matches[1];
                                                }
                                                
                                                // Lấy thông tin người thực hiện cũ và mới
                                                $old_person = null;
                                                if (preg_match('/người thực hiện ([^,]+)/', $old_part, $matches)) {
                                                    $old_person = $matches[1];
                                                }
                                                
                                                $new_person = null;
                                                if (preg_match('/người thực hiện ([^,]+)/', $new_parts[$idx], $matches)) {
                                                    $new_person = $matches[1];
                                                }
                                                
                                                // Lấy thông tin ghi chú cũ và mới
                                                $old_note = null;
                                                if (preg_match('/ghi chú: ([^|]+)/', $old_part, $matches)) {
                                                    $old_note = trim($matches[1]);
                                                }
                                                
                                                $new_note = null;
                                                if (preg_match('/ghi chú: ([^|]+)/', $new_parts[$idx], $matches)) {
                                                    $new_note = trim($matches[1]);
                                                }
                                                
                                                // Chỉ lưu trữ thông tin thực sự thay đổi
                                                $changes = [];
                                                if ($old_score !== $new_score) {
                                                    $changes[] = "Điểm thay đổi từ {$old_score} --> {$new_score}";
                                                }
                                                
                                                if ($old_person !== $new_person) {
                                                    $changes[] = "Người thực hiện thay đổi từ \"{$old_person}\" --> \"{$new_person}\"";
                                                }
                                                
                                                if ($old_note !== $new_note) {
                                                    if (empty($old_note)) {
                                                        $changes[] = "Thêm ghi chú: \"{$new_note}\"";
                                                    } else if (empty($new_note)) {
                                                        $changes[] = "Xóa ghi chú: \"{$old_note}\"";
                                                    } else {
                                                        $changes[] = "Ghi chú thay đổi từ \"{$old_note}\" --> \"{$new_note}\"";
                                                    }
                                                }
                                                
                                                if (!empty($changes)) {
                                                    $tieuchi_changes[$tc_num] = $changes;
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Hiển thị chi tiết thay đổi, kết hợp với thông tin tiêu chí
                                    foreach ($info['changed_tieuchi'] as $tieuchi) {
                                        $thutu = $tieuchi['thutu'];
                                        
                                        // Chỉ hiển thị tiêu chí nếu có thay đổi
                                        if (isset($tieuchi_changes[$thutu])) {
                                            echo "<li>Tiêu chí " . htmlspecialchars($thutu) . ": " . 
                                                 htmlspecialchars($tieuchi['noidung']);
                                            
                                            echo "<div style='margin-left: 5px; color: #28a745;'>";
                                            foreach ($tieuchi_changes[$thutu] as $change) {
                                                echo "- " . htmlspecialchars($change) . "<br>";
                                            }
                                            echo "</div>";
                                            
                                            echo "</li>";
                                        }
                                    }
                                    
                                    echo "</ul>";
                                }
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lấy các phần tử DOM
        const filterForm = document.getElementById('filter-form');
        const btnClearFilter = document.getElementById('btn-clear-filter');
        const filterInfo = document.getElementById('filter-info');
        const currentFilter = document.getElementById('current-filter');
        const detailsDeptSelect = document.getElementById('details_dept');
        const activityRows = document.querySelectorAll('.activity-row');
        
        // Thu thập tất cả các bộ phận có trong chi tiết
        const detailsDepts = new Map(); // Sử dụng Map để lưu trữ {code: name}
        
        // Lặp qua tất cả các bản ghi để thu thập thông tin bộ phận
        document.querySelectorAll('.dept-info').forEach(deptInfo => {
            const deptCode = deptInfo.getAttribute('data-dept-code');
            if (deptCode) {
                // Lấy tên hiển thị của bộ phận (text content sau khi bỏ "Bộ phận:")
                let deptName = deptInfo.textContent.trim();
                deptName = deptName.replace('Bộ phận:', '').trim();
                
                // Thêm vào Map nếu chưa có
                if (!detailsDepts.has(deptCode)) {
                    detailsDepts.set(deptCode, deptName);
                }
            }
        });
        
        // Thêm options vào dropdown lọc theo bộ phận trong chi tiết
        if (detailsDepts.size > 0) {
            detailsDepts.forEach((name, code) => {
                const option = document.createElement('option');
                option.value = code;
                option.textContent = name;
                detailsDeptSelect.appendChild(option);
            });
        } else {
            // Nếu không tìm thấy bộ phận nào trong chi tiết, ẩn dropdown
            detailsDeptSelect.style.display = 'none';
        }
        
        // Đã có bộ lọc từ URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const detailsDeptParam = urlParams.get('details_dept');
        
        // Nếu có tham số details_dept trong URL, set giá trị cho dropdown
        if (detailsDeptParam) {
            detailsDeptSelect.value = detailsDeptParam;
        }
        
        // Bắt sự kiện thay đổi của dropdown lọc theo chi tiết
        detailsDeptSelect.addEventListener('change', function() {
            const selectedDept = this.value;
            if (!selectedDept) {
                clearDetailFilter();
                return;
            }
            
            // Tìm tên hiển thị của bộ phận đã chọn
            const selectedOption = this.options[this.selectedIndex];
            const deptDisplayName = selectedOption.textContent;
            
            // Hiển thị thông tin bộ lọc
            filterInfo.style.display = 'block';
            currentFilter.textContent = 'Bộ phận trong chi tiết: ' + deptDisplayName;
            
            // Lọc các hàng
            let found = 0;
            activityRows.forEach(row => {
                // Tìm thông tin bộ phận trong chi tiết
                const deptInfos = row.querySelectorAll('.dept-info');
                let match = false;
                
                deptInfos.forEach(info => {
                    const deptCode = info.getAttribute('data-dept-code');
                    if (deptCode === selectedDept) {
                        match = true;
                    }
                });
                
                if (match) {
                    row.style.display = '';
                    row.classList.add('highlight');
                    found++;
                } else {
                    row.style.display = 'none';
                    row.classList.remove('highlight');
                }
            });
            
            if (found === 0) {
                alert('Không tìm thấy bản ghi nào với bộ phận đã chọn trong chi tiết!');
                clearDetailFilter();
            } else {
                // Thêm tham số vào URL nhưng không reload trang
                const url = new URL(window.location);
                url.searchParams.set('details_dept', selectedDept);
                window.history.pushState({}, '', url);
            }
        });
        
        // Sự kiện xóa bộ lọc
        btnClearFilter.addEventListener('click', clearDetailFilter);
        
        // Hàm xóa bộ lọc chi tiết
        function clearDetailFilter() {
            filterInfo.style.display = 'none';
            currentFilter.textContent = '';
            detailsDeptSelect.value = '';
            
            activityRows.forEach(row => {
                row.style.display = '';
                row.classList.remove('highlight');
            });
            
            // Xóa tham số khỏi URL
            const url = new URL(window.location);
            url.searchParams.delete('details_dept');
            window.history.pushState({}, '', url);
        }
        
        // Kiểm tra nếu có bộ lọc chi tiết từ URL và áp dụng
        if (detailsDeptParam && detailsDeptSelect.value) {
            // Kích hoạt sự kiện change để áp dụng bộ lọc
            const event = new Event('change');
            detailsDeptSelect.dispatchEvent(event);
        }
    });
    </script>
    <script src="assets/js/header.js"></script>
</body>
</html> 