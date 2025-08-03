<?php
require __DIR__ . '/../../../database/dbConnection.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

$response = [
    'html' => '',
    'showPagination' => false,
    'currentPage' => $page,
    'totalPages' => 0,
];

$totalQuery = "SELECT COUNT(*) AS total FROM deped_inventory_item_category";
$params = [];
$types = '';

if (!empty($search)) {
    $totalQuery .= " WHERE category_name LIKE ?";
    $params[] = "%$search%";
    $types = 's';
}

$stmtTotal = $conn->prepare($totalQuery);
if (!empty($params)) {
    $stmtTotal->bind_param($types, ...$params);
}
$stmtTotal->execute();
$total = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;

$response['showPagination'] = $total > $limit;
$response['totalPages'] = ceil($total / $limit);

// Get paginated category results
$query = "SELECT category_id, category_name FROM deped_inventory_item_category";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " WHERE category_name LIKE ?";
    $params[] = "%$search%";
    $types = 's';
}

$query .= " ORDER BY category_name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rowNumber = $offset + 1;
ob_start();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['category_id'];
        $name = ucfirst(htmlspecialchars($row['category_name'], ENT_QUOTES));

        echo "<tr>
            <td>{$rowNumber}</td>
            <td>{$name}</td>
            <td>
                <button class='action-btn view' title='View' onclick=\"window.location.href='/itemsByCategory?category_id={$id}'\">
                    <i class='fas fa-eye'></i>
                    <span class='tooltip'>View Items</span>
                </button>
                <button class='action-btn edit' data-id='{$id}' data-name='{$name}'>
                    <i class='fas fa-edit'></i>
                    <span class='tooltip'>Edit Items</span>
                </button>
                <button class='action-btn delete' data-id='{$id}' data-name='{$name}'>
                    <i class='fas fa-trash-alt'></i>
                    <span class='tooltip'>View Items</span>
                </button>
            </td>
        </tr>";
        $rowNumber++;
    }
} else {
    echo "<tr><td colspan='3' style='text-align:center;'>No matching categories found.</td></tr>";
}

$response['html'] = ob_get_clean();
echo json_encode($response);
