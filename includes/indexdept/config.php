<?php

$dept_names = [
    'kehoach' => 'BỘ PHẬN KẾ HOẠCH',
    'chuanbi_sanxuat_phong_kt' => 'BỘ PHẬN CHUẨN BỊ SẢN XUẤT (PHÒNG KT)',
    'kho' => 'KHO NGUYÊN, PHỤ LIỆU',
    'cat' => 'BỘ PHẬN CẮT',
    'trung_tam_btp' => 'TRUNG TÂM BTP',
    'ep_keo' => 'BỘ PHẬN ÉP KEO',
    'co_dien' => 'BỘ PHẬN CƠ ĐIỆN',
    'chuyen_may' => 'BỘ PHẬN CHUYỀN MAY',
    'kcs' => 'BỘ PHẬN KCS',
    'ui_thanh_pham' => 'BỘ PHẬN ỦI THÀNH PHẨM',
    'hoan_thanh' => 'BỘ PHẬN HOÀN THÀNH'
];

// Nguồn duy nhất định nghĩa các bộ phận ĐƯỢC HIỂN THỊ trên dashboard (tập con của
// $dept_names, thứ tự = thứ tự cột bảng ở index.php). index.php và
// pages/incomplete_criteria.php cùng derive từ đây để luôn hiện đúng cùng bộ phận.
$dashboard_dept_config = [
    'kehoach'                  => ['label' => 'Kế Hoạch',      'color' => '#FF6384'],
    'kho'                      => ['label' => 'Kho',           'color' => '#FFCE56'],
    'cat'                      => ['label' => 'Cắt',           'color' => '#4BC0C0'],
    'trung_tam_btp'            => ['label' => 'Trung Tâm BTP', 'color' => '#8B5CF6'],
    'chuanbi_sanxuat_phong_kt' => ['label' => 'Kỹ Thuật',      'color' => '#36A2EB'],
    'co_dien'                  => ['label' => 'Cơ Điện',       'color' => '#9966FF'],
    'chuyen_may'               => ['label' => 'Chuyền May',    'color' => '#14B8A6'],
    'kcs'                      => ['label' => 'KCS',           'color' => '#F97316'],
];

// Nguồn duy nhất định nghĩa nhóm cho các bộ phận có phân nhóm.
// Thứ tự khai báo trong mỗi dept = thứ tự sắp xếp hiển thị (1,2,3,...).
// Thêm/sửa nhóm chỉ cần sửa mảng này; mọi nơi khác derive qua helper bên dưới.
$dept_nhom_config = [
    'chuanbi_sanxuat_phong_kt' => [
        'Nhóm Nghiệp Vụ'       => 'a. Nhóm Nghiệp Vụ',
        'Nhóm May Mẫu'         => 'b. Nhóm May Mẫu',
        'Nhóm Quy Trình'       => 'c. Nhóm Quy Trình Công Nghệ, Thiết Kế Chuyền',
        'Nhóm Kỹ Thuật Chuyền' => 'd. Kỹ Thuật Chuyền',
    ],
    'kho' => [
        'Kho Nguyên Liệu' => 'a. Kho Nguyên Liệu',
        'Kho Phụ Liệu'    => 'b. Kho Phụ Liệu',
    ],
];

$required_settings_files = [
    BASE_PATH . '/actions/save_default_setting.php',
    BASE_PATH . '/actions/save_all_default_settings.php',
    BASE_PATH . '/actions/apply_default_settings.php'
];

function getDeptDisplayName($dept, $dept_names) {
    return isset($dept_names[$dept]) ? $dept_names[$dept] : 'KHÔNG XÁC ĐỊNH';
}

function getNhomDisplayName($dept, $nhom) {
    global $dept_nhom_config;
    return $dept_nhom_config[$dept][$nhom] ?? '';
}

function getValidNhomForDept($dept) {
    global $dept_nhom_config;
    return array_keys($dept_nhom_config[$dept] ?? []);
}

function getNhomOptionsHtml($dept) {
    global $dept_nhom_config;
    $html = '';
    foreach (($dept_nhom_config[$dept] ?? []) as $key => $label) {
        $html .= '<option value="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '">'
               . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    return $html;
}

// Sinh chuỗi CASE cho ORDER BY từ $dept_nhom_config.
// An toàn injection: mọi giá trị nhom là literal do code định nghĩa (không nhận input người dùng).
// Mỗi query chỉ lọc theo 1 dept nên các dept dùng chung thứ tự 1,2,... không xung đột.
function getNhomOrderByCase($col = 'tc.nhom') {
    global $dept_nhom_config;
    $when = '';
    $max = 0;
    foreach ($dept_nhom_config as $groups) {
        $order = 1;
        foreach ($groups as $key => $label) {
            $safe = str_replace("'", "''", $key);
            $when .= " WHEN '" . $safe . "' THEN " . $order;
            if ($order > $max) { $max = $order; }
            $order++;
        }
    }
    return 'CASE ' . $col . $when . ' ELSE ' . ($max + 1) . ' END';
}

function getValidDepts() {
    global $dept_names;
    return array_keys($dept_names);
}

function getDashboardDepts() {
    global $dashboard_dept_config;
    return array_keys($dashboard_dept_config);
}

function getDashboardDeptColor($dept) {
    global $dashboard_dept_config;
    return $dashboard_dept_config[$dept]['color'] ?? '#333333';
}
