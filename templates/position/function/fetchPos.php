<?php
require __DIR__ . '/../../../database/dbConnection.php';

$positions = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$limit = 10; 
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1); 
$offset = ($page - 1) * $limit;

// Get total for pagination
$totalQuery = "SELECT COUNT(*) AS total FROM deped_inventory_employee_position";
$params = [];
$types = '';

if (!empty($search)) {
    $totalQuery .= " WHERE position_title LIKE ? OR position_description LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
    $types = 'ss';
}

$stmt = $conn->prepare($totalQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalResult = $stmt->get_result();
$total = ($totalResult && $row = $totalResult->fetch_assoc()) ? (int)$row['total'] : 0;
$totalPages = ceil($total / $limit);

// Fetch paginated positions
$positionQuery = "SELECT position_id, position_title, position_description, created_at 
                  FROM deped_inventory_employee_position";

if (!empty($search)) {
    $positionQuery .= " WHERE position_title LIKE ? OR position_description LIKE ?";
}

$positionQuery .= " ORDER BY position_title ASC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($positionQuery);

if (!empty($search)) {
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
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
