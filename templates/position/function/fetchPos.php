<?php
require __DIR__ . '/../../../database/dbConnection.php';

$positions = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';


$positionQuery = "SELECT position_id, position_title, position_description, created_at 
                  FROM deped_inventory_employee_position";

$params = [];
$types = '';


if (!empty($search)) {
    $positionQuery .= " WHERE position_title LIKE ? OR position_description LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
    $types = 'ss';
}

$positionQuery .= " ORDER BY position_title ASC";

$stmt = $conn->prepare($positionQuery);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();


if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $positions[] = [
            'position_id' => $row['position_id'] ?? '',
            'position_title' => ucfirst($row['position_title'] ?? ''),
            'position_description' => ucfirst($row['position_description'] ?? ''),
            'created_at' => $row['created_at'] ?? ''
        ];
    }
}
?>
