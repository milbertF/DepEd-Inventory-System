<?php
require __DIR__ . '/../../../database/dbConnection.php';

header('Content-Type: application/json');

$categoryId = $_GET['category_id'] ?? null;
$page = max((int) ($_GET['page'] ?? 1), 1);
$limit = 10;
$offset = ($page - 1) * $limit;


$brands = $_GET['brands'] ?? [];
$outOfStock = isset($_GET['out_of_stock']) && $_GET['out_of_stock'] == '1';
$sortQuantity = $_GET['sort_quantity'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

$response = [
    'html' => '',
    'showPagination' => false,
    'currentPage' => $page,
    'totalPages' => 0,
    'paginationHtml' => ''
];


if (!$categoryId) {
    $response['html'] = "<tr><td colspan='9' style='text-align:center;'>Invalid category ID.</td></tr>";
    echo json_encode($response);
    exit;
}


$conditions = ["category_id = ?"];
$types = "i";
$params = [$categoryId];


if (!empty($brands)) {
    $placeholders = implode(',', array_fill(0, count($brands), '?'));
    $conditions[] = "brand IN ($placeholders)";
    $types .= str_repeat('s', count($brands));
    $params = array_merge($params, $brands);
}

if ($outOfStock) {
    $conditions[] = "quantity = 0";
}


if ($dateFrom && $dateTo) {
    $conditions[] = "date_acquired BETWEEN ? AND ?";
    $types .= 'ss';
    $params[] = $dateFrom;
    $params[] = $dateTo;
}

$whereClause = implode(" AND ", $conditions);


$orderBy = "ORDER BY item_name ASC";
if ($sortQuantity === "asc") {
    $orderBy = "ORDER BY quantity ASC";
} elseif ($sortQuantity === "desc") {
    $orderBy = "ORDER BY quantity DESC";
}


$countQuery = "SELECT COUNT(*) as total FROM deped_inventory_items WHERE $whereClause";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalItems = $countResult->fetch_assoc()['total'];

if ($totalItems > 0) {
    $totalPages = ceil($totalItems / $limit);
    $response['totalPages'] = $totalPages;
    $response['showPagination'] = $totalItems > $limit;



    $dataQuery = "SELECT * FROM deped_inventory_items WHERE $whereClause $orderBy LIMIT ? OFFSET ?";
    $dataStmt = $conn->prepare($dataQuery);
    $fullTypes = $types . 'ii';
    $params[] = $limit;
    $params[] = $offset;
    $dataStmt->bind_param($fullTypes, ...$params);
    $dataStmt->execute();
    $result = $dataStmt->get_result();
    $rowNumber = $offset + 1;  

ob_start();

while ($row = $result->fetch_assoc()) {
    $itemId = htmlspecialchars($row['item_id']);
    $itemName = htmlspecialchars($row['item_name']);
    $serial = !empty($row['serial_number']) ? htmlspecialchars($row['serial_number']) : 'None';
    $brand = !empty($row['brand']) ? htmlspecialchars($row['brand']) : 'None';
    $model = !empty($row['model']) ? htmlspecialchars($row['model']) : 'None';
    $qty = htmlspecialchars($row['quantity']);
    $unit = !empty($row['unit']) ? htmlspecialchars($row['unit']) : '';
    $photo = !empty($row['item_photo']) ? htmlspecialchars($row['item_photo']) : '/images/user-profile/default-image.jpg';
    $desc = htmlspecialchars($row['description'] ?? 'None');
    $unitCost = htmlspecialchars($row['unit_cost'] ?? 0);
    $totalCost = htmlspecialchars($row['total_cost'] ?? 0);
    $dateAcquired = !empty($row['date_acquired']) && $row['date_acquired'] !== '0000-00-00' ? date('M-d-Y', strtotime($row['date_acquired'])) : 'N/A';
    $dateAcquiredRaw = !empty($row['date_acquired']) && $row['date_acquired'] !== '0000-00-00' ? date('Y-m-d', strtotime($row['date_acquired'])) : '';
    $itemStatus = htmlspecialchars($row['item_status'] ?? '');
    echo "<tr>";
    echo "<td>{$rowNumber}</td>";
    echo "<td>{$serial}</td>";
    echo "<td><img src='{$photo}' class ='item-photo' alt='Item Photo'></td>";
    echo "<td>{$itemName}</td>";
    echo "<td>{$desc}</td>";
    echo "<td>{$brand}</td>";
    echo "<td>{$model}</td>";
    echo "<td>{$unitCost}</td>";
    echo "<td>{$qty} {$unit}</td>";
    echo "<td>{$totalCost}</td>";
    echo "<td>{$dateAcquired}</td>";
    echo "<td>{$itemStatus}</td>";
    echo "<td>
        <button class='action-btn view' title='View Item'
            data-id='{$itemId}'
            data-photo='{$photo}'
            data-description='{$desc}'
            data-name='{$itemName}'
            data-brand='{$brand}'
            data-model='{$model}'
            data-serial='{$serial}'
            data-qty='{$qty}'
            data-date-acquired='{$dateAcquiredRaw}'
            data-unit='{$unit}'
            data-unitcost='{$unitCost}'
            data-totalcost='{$totalCost}'
            data-itemstatus='{$itemStatus}'
            data-created='{$row['created_at']}'>
            <i class='fas fa-eye'></i><span class='tooltip'>View Item</span>
        </button>

        <button class='action-btn edit' title='Edit Item'
            data-id='{$itemId}'
            data-photo='{$photo}'
            data-description='{$desc}'
            data-name='{$itemName}'
            data-brand='{$brand}'
            data-model='{$model}'
            data-serial='{$serial}'
            data-qty='{$qty}'
            data-date-acquired='{$dateAcquiredRaw}'
            data-unit='{$unit}'
            data-unitcost='{$unitCost}'
            data-totalcost='{$totalCost}'
            data-item-status='{$itemStatus}'
            data-category-id='{$row['category_id']}'>
            <i class='fas fa-edit'></i><span class='tooltip'>Edit Item</span>
        </button>

        <button class='action-btn delete' title='Delete Item'
            data-id='{$itemId}'
            data-name='{$itemName}'>
            <i class='fas fa-trash-alt'></i><span class='tooltip'>Delete Item</span>
        </button>
    </td>";
    echo "</tr>";

    $rowNumber++;  
}

$response['html'] = ob_get_clean();



    ob_start();
    ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <button class="page-btn <?= $i === $page ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></button>
        <?php endfor; ?>
    </div>
    <?php
    $response['paginationHtml'] = ob_get_clean();
} else {
    $response['html'] = "<tr><td colspan='9' style='text-align:center;'>No items found for selected filters.</td></tr>";
}

echo json_encode($response);
?>
