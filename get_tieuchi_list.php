<?php
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_GET['dept'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số bộ phận']);
    exit;
}

$dept = $connect->real_escape_string($_GET['dept']);

$sql = "SELECT id, thutu, noidung, nhom 
        FROM tieuchi_dept 
        WHERE dept = ? 
        ORDER BY 
            CASE nhom 
                WHEN 'Nhóm Nghiệp Vụ' THEN 1 
                WHEN 'Nhóm May Mẫu' THEN 2 
                WHEN 'Nhóm Quy Trình' THEN 3 
                ELSE 4 
            END, 
            thutu";

$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $dept);
$stmt->execute();
$result = $stmt->get_result();

$tieuchi_list = [];
while ($row = $result->fetch_assoc()) {
    $tieuchi_list[] = [
        'id' => $row['id'],
        'thutu' => $row['thutu'],
        'noidung' => $row['noidung'],
        'nhom' => $row['nhom']
    ];
}

echo json_encode(['success' => true, 'data' => $tieuchi_list]);
?> 