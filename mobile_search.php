<?php
include 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mobile_search') {
    $search_query = trim($_POST['search_query']);
    
    if (strlen($search_query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    // Chuẩn bị câu truy vấn tìm kiếm trong nhiều trường
    $sql = "SELECT DISTINCT stt, xuong, line1, po, style, model 
            FROM khsanxuat 
            WHERE xuong LIKE ? 
               OR line1 LIKE ? 
               OR po LIKE ? 
               OR style LIKE ?
               OR model LIKE ?
            ORDER BY ngayin DESC 
            LIMIT 10";
            
    $search_param = "%{$search_query}%";
    
    try {
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssss", 
            $search_param, 
            $search_param, 
            $search_param, 
            $search_param,
            $search_param
        );
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            // Highlight từ khóa tìm kiếm trong kết quả
            $style = htmlspecialchars($row['style']);
            $xuong = htmlspecialchars($row['xuong']);
            $line = htmlspecialchars($row['line1']);
            $po = htmlspecialchars($row['po']);
            $model = htmlspecialchars($row['model']);
            
            // Thêm vào mảng kết quả
            $items[] = [
                'stt' => $row['stt'],
                'style' => $style,
                'xuong' => $xuong,
                'line1' => $line,
                'po' => $po,
                'model' => $model
            ];
        }
        
        echo json_encode($items);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Lỗi tìm kiếm: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['error' => 'Invalid request']);
} 