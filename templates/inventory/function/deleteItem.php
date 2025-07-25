<?php
require __DIR__ . '/../../../database/dbConnection.php';

if (isset($_GET['id'], $_GET['category_id'])) {
    $item_id = $_GET['id'];
    $category_id = $_GET['category_id'];

    $stmt = $conn->prepare("DELETE FROM deped_inventory_items WHERE item_id = ?");
    $stmt->bind_param("s", $item_id);

    if ($stmt->execute()) {
        header("Location: /itemsByCategory?category_id=$category_id&deleted=1");
        return;
    } else {
        header("Location: /itemsByCategory?category_id=$category_id&deleted=0");
        return;
    }
}
?>      
