<?php
require __DIR__ . '/../../../database/dbConnection.php';

header('Content-Type: application/json');

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

// Count query
$countQuery = "SELECT COUNT(*) as total 
               FROM deped_inventory_items_deleted d
               LEFT JOIN deped_inventory_item_category c ON d.category_id = c.category_id
               WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $countQuery .= " AND (d.item_name LIKE ? 
                     OR d.serial_number LIKE ? 
                     OR d.brand LIKE ? 
                     OR d.model LIKE ? 
                     OR d.description LIKE ?
                     OR d.deleted_by_fname LIKE ?
                     OR d.deleted_by_lname LIKE ?
                     OR c.category_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $types = 'ssssssss';
}

$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$response['showPagination'] = $total > $limit;
$response['totalPages'] = ceil($total / $limit);

// Query for paginated deleted items
$query = "SELECT d.*, c.category_name 
          FROM deped_inventory_items_deleted d
          LEFT JOIN deped_inventory_item_category c ON d.category_id = c.category_id
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (d.item_name LIKE ? 
                OR d.serial_number LIKE ? 
                OR d.brand LIKE ? 
                OR d.model LIKE ? 
                OR d.description LIKE ?
                OR d.deleted_by_fname LIKE ?
                OR d.deleted_by_lname LIKE ?
                OR c.category_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $types = 'ssssssss';
}

$query .= " ORDER BY d.deleted_at DESC LIMIT ? OFFSET ?";
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
        $serial = !empty($row['serial_number']) ? htmlspecialchars($row['serial_number']) : 'None';
        $brand = !empty($row['brand']) ? ucfirst(htmlspecialchars($row['brand'])) : 'None';
        $model = !empty($row['model']) ? ucfirst(htmlspecialchars($row['model'])) : 'None';
        $qty = (int) $row['quantity'];
        $category = ucfirst(htmlspecialchars($row['category_name'] ?? 'Uncategorized'));
        $photo = !empty($row['item_photo']) ? htmlspecialchars($row['item_photo']) : '/images/user-profile/default-image.jpg';
        $dateAcquired = !empty($row['date_acquired']) && $row['date_acquired'] !== '0000-00-00'
            ? date('M-d-Y', strtotime($row['date_acquired']))
            : 'N/A';

        $deletedBy = ucfirst(htmlspecialchars($row['deleted_by_fname'])) . " " . ucfirst(htmlspecialchars($row['deleted_by_lname']));
        $deletedDate = !empty($row['deleted_at']) ? date("M-d-Y", strtotime($row['deleted_at'])) : 'N/A';
        $deletedTime = !empty($row['deleted_at']) ? date("h:i A", strtotime($row['deleted_at'])) : 'N/A';

        echo "<tr>
            <td>{$rowNumber}</td>
            <td>{$category}</td>
            <td>{$serial}</td>
            <td><img src='{$photo}' alt='Item Photo' class='item-photo' /></td>
            <td>{$itemName}</td>
            <td>{$brand}</td>
            <td>{$model}</td>
            <td>{$qty}</td>
            <td>{$dateAcquired}</td>
            <td>{$deletedBy}</td>
            <td>{$deletedDate}</td>
            <td>{$deletedTime}</td>
            <td>
                <button class='action-btn view' title='View Item'
                    data-id='{$itemId}'
                    data-photo='{$photo}'
                    data-category='{$category}'
                    data-description='" . htmlspecialchars($row['description']) . "'
                    data-name='{$itemName}'
                    data-brand='{$brand}'
                    data-model='{$model}'
                    data-serial='{$serial}'
                    data-qty='{$qty}'
                    data-deletedby='{$deletedBy}' 
                    data-deletedat='" . (!empty($row['deleted_at']) ? date('Y-m-d H:i:s', strtotime($row['deleted_at'])) : '') . "' 
                    data-date-acquired='" . ((!empty($row['date_acquired']) && $row['date_acquired'] !== '0000-00-00') ? date('Y-m-d', strtotime($row['date_acquired'])) : '') . "'
                    data-unit='" . htmlspecialchars($row['unit']) . "'
                    data-unitcost='" . ($row['unit_cost'] ?? 0) . "'
                    data-totalcost='" . ($row['total_cost'] ?? 0) . "'
                    data-created='" . htmlspecialchars($row['created_at']) . "'>
                    <i class='fas fa-eye'></i>
                    <span class='tooltip'>View Item</span>
                </button>

                <button class='action-btn restore' 
                    data-id='{$itemId}' 
                    data-name='{$itemName}' 
                    title='Restore Item'>
                    <i class='fas fa-undo'></i>
                    <span class='tooltip'>Restore Item</span>
                </button>
  
                <button class='action-btn delete'
                    data-id='{$itemId}'
                    data-name='{$itemName}'
                    data-source='deleted'>
                    <i class='fas fa-trash-alt'></i>
                    <span class='tooltip'>Delete Item</span>
                </button>
            </td>
        </tr>";
        $rowNumber++;
    }
} else {
    echo "<tr><td colspan='13' style='text-align:center;'>No deleted items found.</td></tr>";
}
$response['html'] = ob_get_clean();

echo json_encode($response);
