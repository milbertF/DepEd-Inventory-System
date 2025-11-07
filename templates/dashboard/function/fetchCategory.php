<?php
require __DIR__ . '/../../../database/dbConnection.php';

$categories = [];
$categoryQuery = "SELECT category_id, category_name, created_at FROM deped_inventory_item_category ORDER BY category_name ASC";
$categoryResult = $conn->query($categoryQuery);

if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
