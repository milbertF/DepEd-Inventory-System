<?php
require __DIR__ . '/../../../database/dbConnection.php';

// Removed pagination parameters
$itemQuery = $conn->prepare("
    SELECT 
        d.deleted_id,
        d.item_id,
        d.item_photo,
        d.item_name,
        d.category_id,
        c.category_name,
        d.description,
        d.brand,
        d.model,
        d.serial_number,
        d.quantity,
        d.initial_quantity,
        d.date_acquired,
        d.unit,
        d.unit_cost,
        d.total_cost,
        d.item_status,
        d.created_at,
        d.deleted_by_user_id,
        d.deleted_by_fname,
        d.deleted_by_lname,
        d.deleted_at
    FROM deped_inventory_items_deleted d
    LEFT JOIN deped_inventory_item_category c ON d.category_id = c.category_id
    ORDER BY d.deleted_at DESC
");

$itemQuery->execute();
$itemsResult = $itemQuery->get_result();

$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = [
        'deleted_id' => $row['deleted_id'] ?? '',
        'item_id' => $row['item_id'] ?? '',
        'item_name' => ucfirst($row['item_name'] ?? ''),
        'category_id' => $row['category_id'] ?? '',
        'category_name' => ucfirst($row['category_name'] ?? 'Uncategorized'),
        'brand' => ucfirst($row['brand'] ?? ''),
        'model' => ucfirst($row['model'] ?? ''),
        'serial_number' => $row['serial_number'] ?? '',
        'quantity' => $row['quantity'] ?? 0,
        'initial_quantity' => $row['initial_quantity'] ?? 0,
        'unit' => ucfirst($row['unit'] ?? ''),
        'description' => $row['description'] ?? '',
        'unit_cost' => $row['unit_cost'] ?? 0,
        'total_cost' => $row['total_cost'] ?? 0,
        'item_status' => $row['item_status'] ?? '',
        'created_at' => $row['created_at'] ?? '',
        'deleted_by_user_id' => $row['deleted_by_user_id'] ?? '',
        'deleted_by_fname' => ucfirst($row['deleted_by_fname'] ?? ''),
        'deleted_by_lname' => ucfirst($row['deleted_by_lname'] ?? ''),
        'deleted_at' => $row['deleted_at'] ?? '',
        'date_acquired' => $row['date_acquired'] ?? '',
        'item_photo' => $row['item_photo'] ?? ''
    ];
}
?>