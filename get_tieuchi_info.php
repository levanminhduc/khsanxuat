<?php
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_GET['dept']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số']);
    exit;
}

$dept = $connect->real_escape_string($_GET['dept']);
$id = intval($_GET['id']);

$sql = "SELECT id, thutu, noidung, nhom 
        FROM tieuchi_dept 
        WHERE dept = ? AND id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("si", $dept, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true, 
        'data' => [
            'id' => $row['id'],
            'thutu' => $row['thutu'],
            'noidung' => $row['noidung'],
            'nhom' => $row['nhom']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy tiêu chí']);
}
?> 