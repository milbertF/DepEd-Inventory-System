<?php
require __DIR__ . '/../../../database/dbConnection.php';

$positions = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

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

$positionQuery .= " ORDER BY position_title ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($positionQuery);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $rowNumber = $offset + 1;
    while ($row = $result->fetch_assoc()) {
        $positionTitle = ucfirst(htmlspecialchars($row['position_title']));
        $positionDescRaw = $row['position_description'];
        $positionDesc = $positionDescRaw ? ucfirst(htmlspecialchars($positionDescRaw)) : '<em>No description</em>';

        echo "<tr>
                <td>{$rowNumber}</td>
                <td>{$positionTitle}</td>
                <td>{$positionDesc}</td>
                <td>
                    <button class='action-btn edit'
                      data-id='{$row['position_id']}'
                      data-title='{$positionTitle}'
                      data-description='" . htmlspecialchars($positionDescRaw) . "'>
                      <i class='fas fa-edit'></i>
                    </button>
                    <button class='action-btn delete'
                      data-id='{$row['position_id']}'
                      data-title='{$positionTitle}'>
                      <i class='fas fa-trash-alt'></i>
                    </button>
                </td>
              </tr>";
        $rowNumber++;
    }
} else {
    echo "<tr><td colspan='4' style='text-align:center;'>No matching positions found.</td></tr>";
}
?>
