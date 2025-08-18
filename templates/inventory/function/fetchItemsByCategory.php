<?php
require __DIR__ . '/../../../database/dbConnection.php';

$categoryId = $_GET['category_id'] ?? null;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

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


$countQuery = $conn->prepare("SELECT COUNT(*) as total FROM deped_inventory_items WHERE category_id = ?");
$countQuery->bind_param("i", $categoryId);
$countQuery->execute();
$countResult = $countQuery->get_result();
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $limit);


$itemQuery = $conn->prepare("SELECT item_id, item_name, category_id, brand, model, serial_number, quantity, unit, description, unit_cost, total_cost, created_at, date_acquired, item_photo 
                             FROM deped_inventory_items 
                             WHERE category_id = ? 
                             ORDER BY item_name ASC
                             LIMIT ? OFFSET ?");

$itemQuery->bind_param("iii", $categoryId, $limit, $offset);
$itemQuery->execute();
$itemsResult = $itemQuery->get_result();

$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = [
        'item_id' => $row['item_id'] ?? '',
        'item_name' => ucfirst($row['item_name'] ?? ''),
        'category_id' => ucfirst($row['category_id'] ?? ''),
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
        'item_photo' => $row['item_photo'] ?? ''
    ];
}


$brandQuery = $conn->prepare("SELECT DISTINCT brand FROM deped_inventory_items WHERE category_id = ? AND brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
$brandQuery->bind_param("i", $categoryId);
$brandQuery->execute();
$brandResult = $brandQuery->get_result();

$brands = [];
while ($row = $brandResult->fetch_assoc()) {
    $brands[] = $row['brand'];
}

$modelQuery = $conn->prepare("SELECT DISTINCT model FROM deped_inventory_items WHERE category_id = ? AND model IS NOT NULL AND model != '' ORDER BY model ASC");
$modelQuery->bind_param("i", $categoryId);
$modelQuery->execute();
$modelResult = $modelQuery->get_result();

$models = [];
while ($row = $modelResult->fetch_assoc()) {
    $models[] = $row['model'];
}

?>
