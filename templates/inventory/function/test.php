<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../../../database/dbConnection.php';

$categoryId = $_GET['category_id'] ?? null;
if (!$categoryId) {
    echo json_encode(['error' => 'Missing category_id']);
    exit;
}

$brands = $_GET['brands'] ?? [];
$sortQuantity = $_GET['sort_quantity'] ?? null;
$outOfStock = isset($_GET['out_of_stock']) && $_GET['out_of_stock'] == '1';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$limit = 10;
$page = max((int) ($_GET['page'] ?? 1), 1);
$offset = ($page - 1) * $limit;

$conditions = ["category_id = ?"];
$paramTypes = "i";
$paramValues = [$categoryId];

if (!empty($brands)) {
    $placeholders = implode(',', array_fill(0, count($brands), '?'));
    $conditions[] = "brand IN ($placeholders)";
    $paramTypes .= str_repeat('s', count($brands));
    $paramValues = array_merge($paramValues, $brands);
}

if ($outOfStock) {
    $conditions[] = "quantity = 0";
}

if ($dateFrom && $dateTo) {
    $conditions[] = "date_acquired BETWEEN ? AND ?";
    $paramTypes .= "ss";
    $paramValues[] = $dateFrom;
    $paramValues[] = $dateTo;
}

$conditionSql = implode(" AND ", $conditions);
$orderSql = $sortQuantity === 'asc' ? "ORDER BY quantity ASC" : ($sortQuantity === 'desc' ? "ORDER BY quantity DESC" : "");

// COUNT query
$countStmt = $conn->prepare("SELECT COUNT(*) FROM deped_inventory_items WHERE $conditionSql");
$countStmt->bind_param($paramTypes, ...$paramValues);
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_row()[0];
$totalPages = ceil($totalItems / $limit);

// Append limit & offset
$sql = "SELECT * FROM deped_inventory_items WHERE $conditionSql $orderSql LIMIT ? OFFSET ?";
$paramTypesWithLimit = $paramTypes . "ii";
$paramValuesWithLimit = array_merge($paramValues, [$limit, $offset]);

$itemStmt = $conn->prepare($sql);
$itemStmt->bind_param($paramTypesWithLimit, ...$paramValuesWithLimit);
$itemStmt->execute();
$result = $itemStmt->get_result();

// HTML rows output
$rowsHtml = "";
$rowNumber = $offset + 1;
while ($row = $result->fetch_assoc()) {
    $rowsHtml .= "<tr>";
    $rowsHtml .= "<td>{$rowNumber}</td>";
    $rowsHtml .= "<td>" . (!empty($row['serial_number']) ? htmlspecialchars($row['serial_number']) : 'None') . "</td>";

    $photo = !empty($row['item_photo']) ? htmlspecialchars($row['item_photo']) : '/images/user-profile/default-image.jpg';
    $rowsHtml .= "<td><img src='{$photo}' alt='Item Photo' class='item-photo' /></td>";

    $rowsHtml .= "<td>" . htmlspecialchars($row['item_name']) . "</td>";
    $rowsHtml .= "<td>" . (!empty($row['brand']) ? htmlspecialchars($row['brand']) : 'None') . "</td>";
    $rowsHtml .= "<td>" . (!empty($row['model']) ? htmlspecialchars($row['model']) : 'None') . "</td>";
    $rowsHtml .= "<td>{$row['quantity']}</td>";

    $dateAcquired = (!empty($row['date_acquired']) && $row['date_acquired'] !== '0000-00-00') ?
        date('M-d-Y', strtotime($row['date_acquired'])) : 'N/A';
    $rowsHtml .= "<td>{$dateAcquired}</td>";

    $rowsHtml .= "<td>
        <button class='action-btn view' title='View Item'
            data-id='{$row['item_id']}'
            data-photo='{$photo}'
            data-category='" . htmlspecialchars($row['category_name'] ?? '') . "'
            data-description='" . htmlspecialchars($row['description'] ?? '') . "'
            data-name='" . htmlspecialchars($row['item_name']) . "'
            data-brand='" . htmlspecialchars($row['brand']) . "'
            data-model='" . htmlspecialchars($row['model']) . "'
            data-serial='" . htmlspecialchars($row['serial_number']) . "'
            data-qty='{$row['quantity']}'
            data-date-acquired='" . ($row['date_acquired'] ?? '') . "'
            data-unit='{$row['unit']}'
            data-unitcost='" . ($row['unit_cost'] ?? 0) . "'
            data-totalcost='" . ($row['total_cost'] ?? 0) . "'
            data-created='{$row['created_at']}'>
            <i class='fas fa-eye'></i><span class='tooltip'>View Item</span>
        </button>

        <button class='action-btn edit' title='Edit Item'
            data-id='{$row['item_id']}'
            data-photo='{$photo}'
            data-category-id='{$row['category_id']}'
            data-description='" . htmlspecialchars($row['description'] ?? '') . "'
            data-name='" . htmlspecialchars($row['item_name']) . "'
            data-brand='" . htmlspecialchars($row['brand']) . "'
            data-model='" . htmlspecialchars($row['model']) . "'
            data-serial='" . htmlspecialchars($row['serial_number']) . "'
            data-qty='{$row['quantity']}'
            data-date-acquired='" . ($row['date_acquired'] ?? '') . "'
            data-unit='{$row['unit']}'
            data-unitcost='" . ($row['unit_cost'] ?? 0) . "'
            data-totalcost='" . ($row['total_cost'] ?? 0) . "'>
            <i class='fas fa-edit'></i><span class='tooltip'>Edit Item</span>
        </button>

        <button class='action-btn delete'
            data-id='{$row['item_id']}'
            data-name='" . htmlspecialchars($row['item_name']) . "'>
            <i class='fas fa-trash-alt'></i><span class='tooltip'>Delete Item</span>
        </button>
    </td>";

    $rowsHtml .= "</tr>";
    $rowNumber++;
}

echo json_encode([
    'html' => $rowsHtml,
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'showPagination' => $totalItems > $limit
]);
exit;
