<?php
/**
 * Header Component Test Page
 * 
 * This file tests the shared header component with various configurations.
 * Access via: http://localhost/khsanxuat/components/test_header.php
 * 
 * DELETE THIS FILE AFTER TESTING - not for production use.
 */

// Basic configuration test
$header_config = [
    'title' => 'ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT NHÀ MÁY',
    'title_short' => 'ĐÁNH GIÁ HỆ THỐNG',
    'logo_path' => '../img/logoht.png',
    'logo_link' => '../index.php',
    'show_search' => true,
    'show_mobile_menu' => true,
    'search_params' => [
        'action' => '../index.php',
        'month' => date('m'),
        'year' => date('Y'),
        'search_type' => 'xuong',
        'search_value' => ''
    ],
    'actions' => [
        [
            'url' => '../dept_statistics_month.php',
            'icon' => '../img/thongke.png',
            'title' => 'Thống kê',
            'tooltip' => 'Xem thống kê'
        ],
        [
            'url' => '../import.php',
            'icon' => '../img/add.png',
            'title' => 'Nhập dữ liệu',
            'tooltip' => 'Nhập dữ liệu mới'
        ],
        [
            'url' => '../export.php',
            'icon' => '../img/export.jpg',
            'title' => 'Xuất dữ liệu',
            'tooltip' => 'Xuất dữ liệu'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header Component Test</title>
    <!-- Include the header CSS -->
    <link rel="stylesheet" href="../assets/css/header.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
        }
        .test-content {
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .test-info {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .test-info h2 {
            margin-top: 0;
            color: #003366;
        }
        .test-info pre {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
        .test-checklist {
            list-style: none;
            padding: 0;
        }
        .test-checklist li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .test-checklist li:last-child {
            border-bottom: none;
        }
        .status-ok { color: #10b981; }
        .status-check { color: #f59e0b; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="test-content">
        <div class="test-info">
            <h2>Header Component Test Page</h2>
            <p>This page tests the shared header component. Use this to verify:</p>
            
            <h3>Test Checklist</h3>
            <ul class="test-checklist">
                <li><span class="status-check">[ ]</span> Header displays with #003366 background color</li>
                <li><span class="status-check">[ ]</span> Logo appears on the left and links correctly</li>
                <li><span class="status-check">[ ]</span> Title displays in center (full on desktop, short on mobile)</li>
                <li><span class="status-check">[ ]</span> Action buttons appear on the right (desktop only)</li>
                <li><span class="status-check">[ ]</span> Search form displays when show_search=true</li>
                <li><span class="status-check">[ ]</span> Hamburger menu appears at &lt;768px width</li>
                <li><span class="status-check">[ ]</span> Mobile menu opens/closes on hamburger click</li>
                <li><span class="status-check">[ ]</span> Menu closes when clicking outside</li>
                <li><span class="status-check">[ ]</span> Menu closes when resizing above 768px</li>
                <li><span class="status-check">[ ]</span> No JavaScript console errors</li>
            </ul>
            
            <h3>Configuration Used</h3>
            <pre><?php print_r($header_config); ?></pre>
        </div>
        
        <div class="test-info">
            <h3>Files Created</h3>
            <ul>
                <li><code>assets/css/header.css</code> - <?php echo file_exists('../assets/css/header.css') ? '<span class="status-ok">✓ Exists</span>' : '<span style="color:red">✗ Missing</span>'; ?></li>
                <li><code>assets/js/header.js</code> - <?php echo file_exists('../assets/js/header.js') ? '<span class="status-ok">✓ Exists</span>' : '<span style="color:red">✗ Missing</span>'; ?></li>
                <li><code>components/header.php</code> - <?php echo file_exists('header.php') ? '<span class="status-ok">✓ Exists</span>' : '<span style="color:red">✗ Missing</span>'; ?></li>
            </ul>
        </div>
    </div>
    
    <!-- Include the header JavaScript -->
    <script src="../assets/js/header.js"></script>
</body>
</html>
