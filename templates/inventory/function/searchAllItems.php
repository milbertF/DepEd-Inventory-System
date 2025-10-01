<?php
require __DIR__ . '/../../../database/dbConnection.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$response = [
    'html' => '',
    'showPagination' => false,
    'currentPage' => $page,
    'totalPages' => 0,
    'paginationHtml' => ''
];

$whereClause = '';
$bindTypes = '';
$bindParams = [];

if (!empty($search)) {
    $searchTerm = '%' . $search . '%';
    $whereClause = "WHERE 
        i.item_name LIKE ? OR 
        i.serial_number LIKE ? OR 
        i.brand LIKE ? OR i.description  LIKE ?";
    $bindTypes = 'ssss';
    $bindParams = [$searchTerm, $searchTerm, $searchTerm,$searchTerm];
}

// Count query
$countSql = "SELECT COUNT(*) as total FROM deped_inventory_items i
             LEFT JOIN deped_inventory_item_category c ON i.category_id = c.category_id
             $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($bindTypes)) {
    $countStmt->bind_param($bindTypes, ...$bindParams);
}
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$response['totalPages'] = ceil($totalItems / $limit);
$response['showPagination'] = $response['totalPages'] > 1;


$sql = "SELECT 
        i.item_id, i.item_name, i.category_id, c.category_name, i.brand, i.model,
        i.serial_number, i.quantity, i.unit, i.description, i.unit_cost, i.total_cost,
        i.created_at, i.date_acquired, i.item_photo
    FROM deped_inventory_items i
    LEFT JOIN deped_inventory_item_category c ON i.category_id = c.category_id
    $whereClause
    ORDER BY i.created_at DESC
    LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

if (!empty($bindTypes)) {
    $fullBindTypes = $bindTypes . 'ii';
  
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

ob_start();
if ($result->num_rows > 0) {
    $indexOffset = ($page - 1) * $limit;
    while ($item = $result->fetch_assoc()) {
        ?>
        <tr>
            <td><?= ++$indexOffset ?></td>
            <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
            <td><?= !empty($item['serial_number']) ? htmlspecialchars($item['serial_number']) : 'None' ?></td>
            <td>
                <img src="<?= !empty($item['item_photo']) ? htmlspecialchars($item['item_photo']) : '/images/user-profile/default-image.jpg' ?>" class="item-photo" alt="Item Photo" />
            </td>
            <td><?= htmlspecialchars($item['item_name']) ?></td>
            <td><?= !empty($item['brand']) ? htmlspecialchars($item['brand']) : 'None' ?></td>
            <td><?= !empty($item['model']) ? htmlspecialchars($item['model']) : 'None' ?></td>
            <td><?= htmlspecialchars($item['quantity']) ?></td>
            <td><?= isset($item['date_acquired']) ? date("M-d-Y", strtotime($item['date_acquired'])) : 'N/A' ?></td>
            <td>
                <button class="action-btn view"
                    data-id="<?= $item['item_id'] ?>"
                    data-photo="<?= htmlspecialchars($item['item_photo']) ?>"
                    data-category="<?= htmlspecialchars($item['category_name']) ?>"
                    data-description="<?= htmlspecialchars($item['description']) ?>"
                    data-name="<?= htmlspecialchars($item['item_name']) ?>"
                    data-brand="<?= htmlspecialchars($item['brand']) ?>"
                    data-model="<?= htmlspecialchars($item['model']) ?>"
                    data-serial="<?= htmlspecialchars($item['serial_number']) ?>"
                    data-qty="<?= $item['quantity'] ?>"
                    data-date-acquired="<?= (!empty($item['date_acquired']) && $item['date_acquired'] !== '0000-00-00') ? date('Y-m-d', strtotime($item['date_acquired'])) : '' ?>"
                    data-unit="<?= $item['unit'] ?>"
                    data-unitcost="<?= $item['unit_cost'] ?? 0 ?>"
                    data-totalcost="<?= $item['total_cost'] ?? 0 ?>"
                    data-created="<?= $item['created_at'] ?>">
                    <i class="fas fa-eye"></i>
                </button>

                <button class="action-btn edit"
                    data-id="<?= $item['item_id'] ?>"
                    data-photo="<?= htmlspecialchars($item['item_photo']) ?>"
                    data-category-id="<?= $item['category_id'] ?>"
                    data-description="<?= $item['description'] ?>"
                    data-name="<?= $item['item_name'] ?>"
                    data-brand="<?= $item['brand'] ?>"
                    data-model="<?= $item['model'] ?>"
                    data-serial="<?= $item['serial_number'] ?>"
                    data-qty="<?= $item['quantity'] ?>"
                    data-date-acquired="<?= (!empty($item['date_acquired']) && $item['date_acquired'] !== '0000-00-00') ? date('Y-m-d', strtotime($item['date_acquired'])) : '' ?>"
                    data-unit="<?= $item['unit'] ?>"
                    data-unitcost="<?= $item['unit_cost'] ?? 0 ?>"
                    data-totalcost="<?= $item['total_cost'] ?? 0 ?>">
                    <i class="fas fa-edit"></i>
                </button>

                <button class="action-btn delete"
                    data-id="<?= $item['item_id'] ?>"
                    data-name="<?= htmlspecialchars($item['item_name']) ?>"
                    data-source="all">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
        <?php
    }
} else {
    echo '<tr><td colspan="10" style="text-align:center;">No items found.</td></tr>';
}
$response['html'] = ob_get_clean();
echo json_encode($response);
?>
