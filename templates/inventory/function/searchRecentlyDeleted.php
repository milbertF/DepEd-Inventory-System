<?php
require __DIR__ . '/../../../database/dbConnection.php';

header('Content-Type: application/json');

$page = max((int) ($_GET['page'] ?? 1), 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';

$response = [
    'html' => '',
    'showPagination' => false,
    'currentPage' => $page,
    'totalPages' => 0,
    'paginationHtml' => ''
];

// --- Build WHERE clause ---
$conditions = ["1=1"];
$types = "";
$params = [];

if (!empty($search)) {
    $conditions[] = "(d.item_name LIKE ? 
                      OR d.brand LIKE ? 
                      OR d.model LIKE ? 
                      OR d.serial_number LIKE ? 
                      OR d.description LIKE ? 
                      OR d.deleted_by_fname LIKE ? 
                      OR d.deleted_by_lname LIKE ? 
                      OR c.category_name LIKE ?)";
    $types .= str_repeat("s", 8);
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like, $like]);
}

$whereClause = implode(" AND ", $conditions);

// --- Count total ---
$countQuery = "SELECT COUNT(*) as total 
               FROM deped_inventory_items_deleted d
               LEFT JOIN deped_inventory_item_category c ON d.category_id = c.category_id
               WHERE $whereClause";

$countStmt = $conn->prepare($countQuery);
if (!empty($types)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalItems = $countResult->fetch_assoc()['total'];

if ($totalItems > 0) {
    $totalPages = ceil($totalItems / $limit);
    $response['totalPages'] = $totalPages;
    $response['showPagination'] = $totalItems > $limit;

    // --- Fetch paginated results ---
    $dataQuery = "SELECT d.*, c.category_name 
                  FROM deped_inventory_items_deleted d
                  LEFT JOIN deped_inventory_item_category c ON d.category_id = c.category_id
                  WHERE $whereClause 
                  ORDER BY d.deleted_at DESC 
                  LIMIT ? OFFSET ?";

    $dataStmt = $conn->prepare($dataQuery);
    $fullTypes = $types . 'ii';
    $paramsWithLimit = array_merge($params, [$limit, $offset]);
    $dataStmt->bind_param($fullTypes, ...$paramsWithLimit);
    $dataStmt->execute();
    $result = $dataStmt->get_result();

    $rowNumber = $offset + 1;

    ob_start();
    while ($row = $result->fetch_assoc()) {
        $itemId = htmlspecialchars($row['item_id']);
        $itemName = htmlspecialchars($row['item_name']);
        $category = !empty($row['category_name']) ? htmlspecialchars($row['category_name']) : 'None';
        $serial = !empty($row['serial_number']) ? htmlspecialchars($row['serial_number']) : 'None';
        $brand = !empty($row['brand']) ? htmlspecialchars($row['brand']) : 'None';
        $model = !empty($row['model']) ? htmlspecialchars($row['model']) : 'None';
        $deletedBy = ucfirst(htmlspecialchars($row['deleted_by_fname'])) . " " . ucfirst(htmlspecialchars($row['deleted_by_lname']));
        $deletedAt = !empty($row['deleted_at']) ? date('M-d-Y H:i', strtotime($row['deleted_at'])) : 'N/A';
        $photo = !empty($row['item_photo']) ? htmlspecialchars($row['item_photo']) : '/images/user-profile/default-image.jpg';

        echo "<tr>";
        echo "<td>{$rowNumber}</td>";
        echo "<td><img src='{$photo}' class ='item-photo' alt='Item Photo'></td>";
        echo "<td>{$itemName}</td>";
        echo "<td>{$category}</td>";
        echo "<td>{$serial}</td>";
        echo "<td>{$brand}</td>";
        echo "<td>{$model}</td>";
        echo "<td>{$deletedBy}</td>";
        echo "<td>{$deletedAt}</td>";
        echo "<td>
            <button class='action-btn restore' title='Restore Item'
                data-id='{$itemId}' 
                data-name='{$itemName}'>
                <i class='fas fa-undo'></i><span class='tooltip'>Restore</span>
            </button>
        </td>";
        echo "</tr>";

        $rowNumber++;
    }
    $response['html'] = ob_get_clean();

    // --- Pagination HTML ---
    ob_start(); ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <button class="page-link-all <?= $i === $page ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></button>
        <?php endfor; ?>
    </div>
    <?php
    $response['paginationHtml'] = ob_get_clean();
} else {
    $response['html'] = "<tr><td colspan='10' style='text-align:center;'>No deleted items found.</td></tr>";
}

echo json_encode($response);
