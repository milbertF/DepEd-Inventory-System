<?php
require __DIR__ . '/../../../database/dbConnection.php';
session_start();

if (isset($_GET['id'])) {
    $item_id = $_GET['id'];

    $conn->begin_transaction();

    try {
        // First, get the item name and check if it's the last item before deleting
        $fetchStmt = $conn->prepare("SELECT item_name FROM deped_inventory_items_deleted WHERE item_id = ?");
        $fetchStmt->bind_param("s", $item_id);
        $fetchStmt->execute();
        $result = $fetchStmt->get_result();

        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
            $item_name = $item['item_name'];
        } else {
            throw new Exception("Item not found in deleted items table");
        }
        $fetchStmt->close();

        // CHECK IF THIS IS THE LAST ITEM IN THE DELETED TABLE
        $checkLastItemStmt = $conn->prepare("SELECT COUNT(*) as item_count FROM deped_inventory_items_deleted");
        $checkLastItemStmt->execute();
        $countResult = $checkLastItemStmt->get_result();
        $itemCount = $countResult->fetch_assoc()['item_count'];
        $checkLastItemStmt->close();

        $is_last_item = ($itemCount == 1); // This will be true if we're deleting the last item

        // Delete from deleted table
        $stmt = $conn->prepare("DELETE FROM deped_inventory_items_deleted WHERE item_id = ?");
        $stmt->bind_param("s", $item_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete item.");
        }

        $stmt->close();

        $conn->commit();
        
        // Store data in session 
        $_SESSION['permanent_deleted_item_name'] = $item_name;
        $_SESSION['permanent_deleted_is_last_item'] = $is_last_item;
        
        // Redirect with parameter 
        header("Location: /recentlyDeleted?permanent_deleted=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Hard delete failed: " . $e->getMessage());
        header("Location: /recentlyDeleted?permanent_deleted=0");
        exit;
    }
}
?>