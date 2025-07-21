<?php
require __DIR__ . '/../../../database/dbConnection.php';

$offices = [];

$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); 
$offset = ($page - 1) * $limit;

$totalQuery = "SELECT COUNT(*) AS total FROM deped_inventory_employee_office";
$totalResult = $conn->query($totalQuery);
$total = ($totalResult && $row = $totalResult->fetch_assoc()) ? (int)$row['total'] : 0;

$totalPages = ceil($total / $limit);

$query = "SELECT office_id, office_name, office_description, created_at 
          FROM deped_inventory_employee_office 
          ORDER BY office_name ASC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $offices[] = [
            'office_id' => $row['office_id'] ?? '',
            'office_name' => ucfirst($row['office_name'] ?? ''),
            'office_description' => ucfirst($row['office_description'] ?? ''),
            'created_at' => $row['created_at'] ?? ''
        ];
    }
}
?>
