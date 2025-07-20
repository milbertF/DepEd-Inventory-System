<?php
require __DIR__ . '/../../../database/dbConnection.php';

if (isset($_GET['id'])) {
    $category_id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM deped_inventory_item_category WHERE category_id = ?");
    $stmt->bind_param("s", $category_id);

    if ($stmt->execute()) {
        header("Location: /items?deleted=1");
        exit;
    } else {
        header("Location: /items?deleted=0");
        exit;
    }
}
?>
