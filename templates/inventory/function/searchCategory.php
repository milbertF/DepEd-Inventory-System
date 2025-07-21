<?php
require __DIR__ . '/../../../database/dbConnection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;


$totalQuery = "SELECT COUNT(*) AS total FROM deped_inventory_item_category";
$params = [];
$types = '';

if (!empty($search)) {
    $totalQuery .= " WHERE category_name LIKE ?";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $types .= 's';
}

$stmtTotal = $conn->prepare($totalQuery);
if (!empty($params)) {
    $stmtTotal->bind_param($types, ...$params);
}
$stmtTotal->execute();
$totalResult = $stmtTotal->get_result();
$total = ($totalResult && $row = $totalResult->fetch_assoc()) ? (int)$row['total'] : 0;
$totalPages = ceil($total / $limit);


$categoryQuery = "SELECT category_id, category_name, created_at 
                  FROM deped_inventory_item_category";

$params = [];
$types = '';

if (!empty($search)) {
    $categoryQuery .= " WHERE category_name LIKE ?";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $types .= 's';
}

$categoryQuery .= " ORDER BY category_name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($categoryQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rowNumber = $offset + 1;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categoryId = $row['category_id'];
        $categoryNameRaw = $row['category_name'];
        $categoryName = ucfirst(htmlspecialchars($categoryNameRaw));

        echo "<tr>
                <td>{$rowNumber}</td>
                <td>{$categoryName}</td>
                <td>
                    <button class='action-btn view' title='View Items'
                        onclick=\"window.location.href='/itemsByCategory?category_id={$categoryId}'\">
                        <i class='fas fa-eye'></i>
                    </button>

                    <button class='action-btn edit' 
                        data-id='{$categoryId}' 
                        data-name='" . htmlspecialchars($categoryNameRaw) . "'>
                        <i class='fas fa-edit'></i>
                    </button>

                    <button class='action-btn delete' title='Delete'
                        data-id='{$categoryId}' 
                        data-name='" . htmlspecialchars($categoryNameRaw) . "'>
                        <i class='fas fa-trash-alt'></i>
                    </button>
                </td>
              </tr>";
        $rowNumber++;
    }

} else {
    echo "<tr><td colspan='3' style='text-align:center;'>No matching categories found.</td></tr>";
}
?>
