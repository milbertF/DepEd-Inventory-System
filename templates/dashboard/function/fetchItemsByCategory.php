<?php
require __DIR__ . '/../../../database/dbConnection.php';

$categories = [];
$catQuery = "SELECT * FROM deped_inventory_item_category ORDER BY category_name ASC";
$catResult = $conn->query($catQuery);
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row;
}

$items = [];
$itemQuery = "
  SELECT 
    i.item_id, i.item_name, i.category_id, 
    i.brand, i.model, i.serial_number, 
    i.quantity, i.description
  FROM deped_inventory_items i
  ORDER BY i.item_name ASC
";
$itemResult = $conn->query($itemQuery);
while ($row = $itemResult->fetch_assoc()) {
    $items[] = $row;
}
