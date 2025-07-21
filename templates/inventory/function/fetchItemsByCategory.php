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


$itemQuery = $conn->prepare("SELECT item_name, brand, model, quantity, unit, created_at, date_acquired, item_photo 
                             FROM deped_inventory_items 
                             WHERE category_id = ? 
                             LIMIT ? OFFSET ?");
$itemQuery->bind_param("iii", $categoryId, $limit, $offset);
$itemQuery->execute();
$itemsResult = $itemQuery->get_result();
$items = [];

while ($row = $itemsResult->fetch_assoc()) {
    $items[] = [
        'item_name' => ucfirst($row['item_name'] ?? ''),
        'brand' => ucfirst($row['brand'] ?? ''),
        'model' => ucfirst($row['model'] ?? ''),
        'quantity' => $row['quantity'] ?? 0,
        'unit' => ucfirst($row['unit'] ?? ''),
        'created_at' => $row['created_at'] ?? '',
        'date_acquired' => $row['date_acquired'] ?? '',
        'item_photo' => $row['item_photo'] ?? ''
    ];
}
?>
