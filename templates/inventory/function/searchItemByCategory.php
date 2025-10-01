<?php
require __DIR__ . '/../../../database/dbConnection.php';

header('Content-Type: application/json');

$categoryId = $_GET['category_id'] ?? null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1);
$limit = 10;
$offset = ($page - 1) * $limit;

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

// Count query
$countQuery = "SELECT COUNT(*) as total FROM deped_inventory_items WHERE category_id = ?";
$params = [$categoryId];
$types = 'i';

if (!empty($search)) {
    $countQuery .= " AND (item_name LIKE ? OR serial_number LIKE ? OR brand LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$categoryId, $searchTerm, $searchTerm,$searchTerm, $searchTerm];
    $types = 'issss';
}

$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$response['showPagination'] = $total > $limit;
$response['totalPages'] = ceil($total / $limit);


$query = "SELECT * FROM deped_inventory_items WHERE category_id = ?";
$params = [$categoryId];
$types = 'i';

if (!empty($search)) {
    $query .= " AND (item_name LIKE ? OR serial_number LIKE ? OR brand LIKE ? OR description LIKE ? )";
    $params = [$categoryId, $searchTerm, $searchTerm,  $searchTerm, $searchTerm];
    $types = 'issss';
}

$query .= " ORDER BY item_name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


ob_start();
if ($result->num_rows > 0) {
    $rowNumber = $offset + 1;
    while ($row = $result->fetch_assoc()) {
        $itemId = htmlspecialchars($row['item_id']);
        $itemName = ucfirst(htmlspecialchars($row['item_name']));
        $desc = htmlspecialchars($row['description'] ?? '');
        $serial = !empty($row['serial_number']) ? htmlspecialchars($row['serial_number']) : 'None';
        $brand = !empty($row['brand']) ? ucfirst(htmlspecialchars($row['brand'])) : 'None';
        $model = !empty($row['model']) ? ucfirst(htmlspecialchars($row['model'])) : 'None';
        $qty = htmlspecialchars($row['quantity']);
        $unit = !empty($row['unit']) ? htmlspecialchars($row['unit']) : '';
        $photo = !empty($row['item_photo']) ? htmlspecialchars($row['item_photo']) : '/images/user-profile/default-image.jpg';
    
        $unitCost = $row['unit_cost'] ?? 0;
        $totalCost = $row['total_cost'] ?? 0;
        $dateAcquired = !empty($row['date_acquired']) && $row['date_acquired'] !== '0000-00-00'
            ? date('M-d-Y', strtotime($row['date_acquired']))
            : 'N/A';
        $dateAcquiredRaw = !empty($row['date_acquired']) && $row['date_acquired'] !== '0000-00-00'
            ? date('Y-m-d', strtotime($row['date_acquired']))
            : '';

        echo "<tr>
            <td>{$rowNumber}</td>
            <td>{$serial}</td>
            <td><img src='{$photo}' alt='Item Photo' class='item-photo' /></td>
            <td>{$itemName}</td>
            <td>{$desc}</td>
            <td>{$brand}</td>
            <td>{$model}</td>
            <td>{$unitCost}</td>
            <td>{$qty} {$unit}</td>
            <td>{$totalCost}</td>
            <td>{$dateAcquired}</td>
            <td>
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
                    data-created='{$row['created_at']}'>
                    <i class='fas fa-eye'></i>
                    <span class='tooltip'>View Item</span>
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
                    data-category-id='{$row['category_id']}'>
                    <i class='fas fa-edit'></i>
                    <span class='tooltip'>Edit Item</span>
                </button>

                <button class='action-btn delete' title='Delete Item'
                    data-id='{$itemId}'
                    data-name='{$itemName}'>
                    <i class='fas fa-trash-alt'></i>
                    <span class='tooltip'>Delete Item</span>
                </button>
            </td>
        </tr>";
        $rowNumber++;
    }
} else {
    echo "<tr><td colspan='9' style='text-align:center;'>No matching items found.</td></tr>";
}
$response['html'] = ob_get_clean();

echo json_encode($response);
