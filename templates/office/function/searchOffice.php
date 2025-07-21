<?php
require __DIR__ . '/../../../database/dbConnection.php';

$offices = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

$officeQuery = "SELECT office_id, office_name, office_description, created_at 
                FROM deped_inventory_employee_office";

$params = [];
$types = '';

if (!empty($search)) {
    $officeQuery .= " WHERE office_name LIKE ? OR office_description LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
    $types = 'ss';
}

$officeQuery .= " ORDER BY office_name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($officeQuery);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $rowNumber = $offset + 1;
    while ($row = $result->fetch_assoc()) {
        $officeName = ucfirst(htmlspecialchars($row['office_name']));
        $officeDescRaw = $row['office_description'];
        $officeDesc = $officeDescRaw ? ucfirst(htmlspecialchars($officeDescRaw)) : '<em>No description</em>';

        echo "<tr>
                <td>{$rowNumber}</td>
                <td>{$officeName}</td>
                <td>{$officeDesc}</td>
                <td>
                    <button class='action-btn edit'
                      data-id='{$row['office_id']}'
                      data-title='{$officeName}'
                      data-description='" . htmlspecialchars($officeDescRaw) . "'>
                      <i class='fas fa-edit'></i>
                    </button>
                    <button class='action-btn delete'
                      data-id='{$row['office_id']}'
                      data-title='{$officeName}'>
                      <i class='fas fa-trash-alt'></i>
                    </button>
                </td>
              </tr>";
        $rowNumber++;
    }
} else {
    echo "<tr><td colspan='4' style='text-align:center;'>No matching offices found.</td></tr>";
}
?>
