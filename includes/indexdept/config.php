<?php

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

$required_settings_files = [
    'save_default_setting.php',
    'save_all_default_settings.php',
    'apply_default_settings.php'
];

function getDeptDisplayName($dept, $dept_names) {
    return isset($dept_names[$dept]) ? $dept_names[$dept] : 'KHÔNG XÁC ĐỊNH';
}

function getNhomDisplayName($dept, $nhom) {
    if ($dept == 'chuanbi_sanxuat_phong_kt') {
        switch ($nhom) {
            case 'Nhóm Nghiệp Vụ':
                return 'a. Nhóm Nghiệp Vụ';
            case 'Nhóm May Mẫu':
                return 'b. Nhóm May Mẫu';
            case 'Nhóm Quy Trình':
                return 'c. Nhóm Quy Trình Công Nghệ, Thiết Kế Chuyền';
        }
    } elseif ($dept == 'kho') {
        switch ($nhom) {
            case 'Kho Nguyên Liệu':
                return 'a. Kho Nguyên Liệu';
            case 'Kho Phụ Liệu':
                return 'b. Kho Phụ Liệu';
        }
    }
    return '';
}
