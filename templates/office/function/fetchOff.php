<?php
require __DIR__ . '/../../../database/dbConnection.php';

$offices = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';


$query = "SELECT office_id, office_name, office_description, created_at 
          FROM deped_inventory_employee_office";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " WHERE office_name LIKE ? OR office_description LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
    $types = 'ss';
}

$query .= " ORDER BY office_name ASC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

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
