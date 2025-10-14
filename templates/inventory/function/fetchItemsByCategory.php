<?php
require __DIR__ . '/../../../database/dbConnection.php';

$categoryId = $_GET['category_id'] ?? null;

if (!$categoryId) {
    echo "Invalid category ID.";
    exit;
}


$categoryQuery = $conn->prepare("SELECT category_name FROM deped_inventory_item_category WHERE category_id = ?");
$categoryQuery->bind_param("i", $categoryId);
$categoryQuery->execute();
$categoryResult = $categoryQuery->get_result();
$category = $categoryResult->fetch_assoc();

if (!$category) {
    echo "Category not found.";
    exit;
}

$categoryName = ucfirst($category['category_name'] ?? '');


$itemQuery = $conn->prepare("
    SELECT item_id, item_name, category_id, brand, model, serial_number, quantity, unit, description,
           unit_cost, total_cost, created_at, date_acquired, item_status, item_photo 
    FROM deped_inventory_items 
    WHERE category_id = ? 
    ORDER BY date_acquired DESC
");
$itemQuery->bind_param("i", $categoryId);
$itemQuery->execute();
$itemsResult = $itemQuery->get_result();

$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = [
        'item_id' => $row['item_id'] ?? '',
        'item_name' => ucfirst($row['item_name'] ?? ''),
        'description' => $row['description'] ?? '',
        'category_id' => $row['category_id'] ?? '',
        'brand' => ucfirst($row['brand'] ?? ''),
        'model' => ucfirst($row['model'] ?? ''),
        'serial_number' => $row['serial_number'] ?? '',
        'quantity' => $row['quantity'] ?? 0,
        'unit' => ucfirst($row['unit'] ?? ''),
        'unit_cost' => $row['unit_cost'] ?? 0,
        'total_cost' => $row['total_cost'] ?? 0,
        'created_at' => $row['created_at'] ?? '',
        'date_acquired' => $row['date_acquired'] ?? '',
        'item_status' => ucfirst($row['item_status'] ?? ''),
        'item_photo' => $row['item_photo'] ?? ''
    ];
}


$brandQuery = $conn->prepare("
    SELECT DISTINCT brand 
    FROM deped_inventory_items 
    WHERE category_id = ? AND brand IS NOT NULL AND brand != '' 
    ORDER BY brand ASC
");
$brandQuery->bind_param("i", $categoryId);
$brandQuery->execute();
$brandResult = $brandQuery->get_result();

$brands = [];
while ($row = $brandResult->fetch_assoc()) {
    $brands[] = $row['brand'];
}


$modelQuery = $conn->prepare("
    SELECT DISTINCT model 
    FROM deped_inventory_items 
    WHERE category_id = ? AND model IS NOT NULL AND model != '' 
    ORDER BY model ASC
");
$modelQuery->bind_param("i", $categoryId);
$modelQuery->execute();
$modelResult = $modelQuery->get_result();

$models = [];
while ($row = $modelResult->fetch_assoc()) {
    $models[] = $row['model'];
}
?>
