<?php
// Kết nối database
require "contdb.php";

echo "<h1>Kiểm tra hệ thống hạn xử lý</h1>";

// Kiểm tra cấu trúc các bảng liên quan
echo "<h2>1. Kiểm tra cấu trúc bảng khsanxuat</h2>";
$result = mysqli_query($connect, "DESCRIBE khsanxuat");
if ($result) {
    $has_ngay_tinh_han = false;
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Default</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['Field'] == 'ngay_tinh_han' || $row['Field'] == 'han_xuly' || $row['Field'] == 'so_ngay_xuly') {
            echo "<tr style='background-color: #e6ffe6;'>";
            echo "<td><strong>{$row['Field']}</strong></td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
            
            if ($row['Field'] == 'ngay_tinh_han') {
                $has_ngay_tinh_han = true;
            }
        } else {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    echo "<p>" . ($has_ngay_tinh_han ? "✅ Trường ngay_tinh_han đã tồn tại" : "❌ Trường ngay_tinh_han chưa tồn tại") . "</p>";
}

echo "<h2>2. Kiểm tra cấu trúc bảng danhgia_tieuchi</h2>";
$result = mysqli_query($connect, "DESCRIBE danhgia_tieuchi");
if ($result) {
    $has_ngay_tinh_han = false;
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Default</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['Field'] == 'ngay_tinh_han' || $row['Field'] == 'han_xuly' || $row['Field'] == 'so_ngay_xuly') {
            echo "<tr style='background-color: #e6ffe6;'>";
            echo "<td><strong>{$row['Field']}</strong></td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
            
            if ($row['Field'] == 'ngay_tinh_han') {
                $has_ngay_tinh_han = true;
            }
        } else {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    echo "<p>" . ($has_ngay_tinh_han ? "✅ Trường ngay_tinh_han đã tồn tại" : "❌ Trường ngay_tinh_han chưa tồn tại") . "</p>";
}

// Kiểm tra dữ liệu trong bảng khsanxuat
echo "<h2>3. Kiểm tra dữ liệu ngay_tinh_han trong bảng khsanxuat</h2>";

$result = mysqli_query($connect, "SELECT stt, xuong, po, style, ngayin, ngayout, so_ngay_xuly, han_xuly, ngay_tinh_han FROM khsanxuat ORDER BY stt DESC LIMIT 10");

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Xưởng</th><th>PO</th><th>Style</th><th>Ngày vào</th><th>Ngày ra</th><th>Số ngày xử lý</th><th>Hạn xử lý</th><th>Ngày tính hạn</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['stt']}</td>";
        echo "<td>{$row['xuong']}</td>";
        echo "<td>{$row['po']}</td>";
        echo "<td>{$row['style']}</td>";
        echo "<td>{$row['ngayin']}</td>";
        echo "<td>{$row['ngayout']}</td>";
        echo "<td>{$row['so_ngay_xuly']}</td>";
        echo "<td>{$row['han_xuly']}</td>";
        echo "<td>{$row['ngay_tinh_han']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Không có dữ liệu hoặc có lỗi: " . mysqli_error($connect) . "</p>";
}

// Kiểm tra dữ liệu trong bảng danhgia_tieuchi
echo "<h2>4. Kiểm tra dữ liệu ngay_tinh_han trong bảng danhgia_tieuchi</h2>";

$result = mysqli_query($connect, "SELECT d.id, d.id_sanxuat, k.po, k.style, t.noidung, d.han_xuly, d.so_ngay_xuly, d.ngay_tinh_han 
                              FROM danhgia_tieuchi d 
                              JOIN khsanxuat k ON d.id_sanxuat = k.stt 
                              JOIN tieuchi_dept t ON d.id_tieuchi = t.id 
                              ORDER BY d.id DESC LIMIT 10");

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>ID Sản xuất</th><th>PO</th><th>Style</th><th>Tiêu chí</th><th>Hạn xử lý</th><th>Số ngày xử lý</th><th>Ngày tính hạn</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['id_sanxuat']}</td>";
        echo "<td>{$row['po']}</td>";
        echo "<td>{$row['style']}</td>";
        echo "<td>{$row['noidung']}</td>";
        echo "<td>{$row['han_xuly']}</td>";
        echo "<td>{$row['so_ngay_xuly']}</td>";
        echo "<td>{$row['ngay_tinh_han']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Không có dữ liệu hoặc có lỗi: " . mysqli_error($connect) . "</p>";
}

// Đóng kết nối
mysqli_close($connect);
?> 