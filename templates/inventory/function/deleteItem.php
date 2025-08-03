<?php
require __DIR__ . '/../../../database/dbConnection.php';

if (isset($_GET['id'])) {
    $item_id = $_GET['id'];
    $category_id = $_GET['category_id'] ?? null;
    $source = $_GET['source'] ?? 'category'; 

    $stmt = $conn->prepare("DELETE FROM deped_inventory_items WHERE item_id = ?");
    $stmt->bind_param("s", $item_id);

    if ($stmt->execute()) {
        if ($source === 'all') {
            header("Location: /allItems?deleted=1");
        } else {
            header("Location: /itemsByCategory?category_id=$category_id&deleted=1");
        }
        exit;
    } else {
        if ($source === 'all') {
            header("Location: /allItems?deleted=0");
        } else {
            header("Location: /itemsByCategory?category_id=$category_id&deleted=0");
        }
        exit;
    }
}
?>
