<?php
require __DIR__ . '/../../../database/dbConnection.php';
session_start();

if (isset($_GET['id'])) {
    $item_id = $_GET['id'];

    $conn->begin_transaction();

    try {
 
        $stmt = $conn->prepare("DELETE FROM deped_inventory_items_deleted WHERE item_id = ?");
        $stmt->bind_param("s", $item_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete item.");
        }

        $stmt->close();

        $conn->commit();
        header("Location: /recentlyDeleted?deleted=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Hard delete failed: " . $e->getMessage());
        header("Location: /recentDeletedItems?deleted=0");
        exit;
    }
}
?>
