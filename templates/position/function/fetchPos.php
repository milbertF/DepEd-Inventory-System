<?php
require __DIR__ . '/../../../database/dbConnection.php';

$positions = [];

$limit = 10; 
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1); 
$offset = ($page - 1) * $limit;


$totalQuery = "SELECT COUNT(*) AS total FROM deped_inventory_employee_position";
$totalResult = $conn->query($totalQuery);
$total = ($totalResult && $row = $totalResult->fetch_assoc()) ? (int)$row['total'] : 0;

$totalPages = ceil($total / $limit);


$positionQuery = "SELECT position_id, position_title, position_description, created_at 
                  FROM deped_inventory_employee_position 
                  ORDER BY position_title ASC 
                  LIMIT ? OFFSET ?";
$stmt = $conn->prepare($positionQuery);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $positions[] = [
            'position_id' => $row['position_id'] ?? '',
            'position_title' => $row['position_title'] ?? '',
            'position_description' => $row['position_description'] ?? '',
            'created_at' => $row['created_at'] ?? ''
        ];
    }
}
