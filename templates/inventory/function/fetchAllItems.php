<?php
require __DIR__ . '/../../../database/dbConnection.php';

$itemQuery = $conn->prepare("
    SELECT 
        i.item_id,
        i.item_name,
        i.category_id,
        c.category_name,
        i.brand,
        i.model,
        i.serial_number,
        i.quantity,
        i.unit,
        i.description,
        i.unit_cost,
        i.total_cost,
        i.created_at,
        i.date_acquired,
        i.item_status,
        i.item_photo
    FROM deped_inventory_items i
    LEFT JOIN deped_inventory_item_category c ON i.category_id = c.category_id
    ORDER BY i.created_at DESC
");

$itemQuery->execute();
$itemsResult = $itemQuery->get_result();

$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = [
        'item_id' => $row['item_id'] ?? '',
        'item_name' => ucfirst($row['item_name'] ?? ''),
        'category_id' => $row['category_id'] ?? '',
        'category_name' => ucfirst($row['category_name'] ?? 'Uncategorized'),
        'brand' => ucfirst($row['brand'] ?? ''),
        'model' => ucfirst($row['model'] ?? ''),
        'serial_number' => $row['serial_number'] ?? '',
        'quantity' => $row['quantity'] ?? 0,
        'unit' => ucfirst($row['unit'] ?? ''),
        'description' => $row['description'] ?? '',
        'unit_cost' => $row['unit_cost'] ?? 0,
        'total_cost' => $row['total_cost'] ?? 0,
        'created_at' => $row['created_at'] ?? '',
        'date_acquired' => $row['date_acquired'] ?? '',
        'item_status' => $row['item_status'] ?? '',
        'item_photo' => $row['item_photo'] ?? ''
    ];
}
?>