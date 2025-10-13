<?php
require __DIR__ . '/../../../database/dbConnection.php';

$categories = [];

$categoryQuery = "
    SELECT 
        c.category_id, 
        c.category_name, 
        c.created_at,
        COUNT(i.item_id) as item_count
    FROM deped_inventory_item_category c 
    LEFT JOIN deped_inventory_items i ON c.category_id = i.category_id 
    GROUP BY c.category_id, c.category_name, c.created_at
    ORDER BY c.category_name ASC
";

$result = $conn->query($categoryQuery);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'category_id' => $row['category_id'] ?? '',
            'category_name' => ucfirst($row['category_name'] ?? ''),
            'created_at' => $row['created_at'] ?? '',
            'item_count' => $row['item_count'] ?? 0
        ];
    }
}

?>