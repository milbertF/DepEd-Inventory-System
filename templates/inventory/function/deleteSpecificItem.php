<?php
require __DIR__ . '/../../../database/dbConnection.php';
session_start();

function generateDeletedID($conn) {
    do {
        $id = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $check = $conn->prepare("SELECT deleted_id FROM deped_inventory_items_deleted WHERE deleted_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);
    return $id;
}

if (isset($_GET['id'])) {
    $item_id = $_GET['id'];

    $conn->begin_transaction();

    try {
        // First, get the category name by joining with categories table
        $fetchStmt = $conn->prepare("
            SELECT i.*, c.category_name 
            FROM deped_inventory_items i 
            LEFT JOIN deped_inventory_item_category c ON i.category_id = c.category_id 
            WHERE i.item_id = ?
        ");
        $fetchStmt->bind_param("s", $item_id);
        $fetchStmt->execute();
        $result = $fetchStmt->get_result();

        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();

            $deleted_by_user_id = $_SESSION['user']['user_id'] ?? null;
            $deleted_by_fname   = $_SESSION['user']['first_name'] ?? 'Unknown';
            $deleted_by_lname   = $_SESSION['user']['last_name'] ?? 'Unknown';

            $deleted_id = generateDeletedID($conn);

            $insertStmt = $conn->prepare("
                INSERT INTO deped_inventory_items_deleted
                (deleted_id, item_id, item_photo, item_name, category_id, category_name, description, brand, model, serial_number, quantity,initial_quantity, date_acquired, unit, unit_cost, total_cost, item_status, created_at, deleted_by_user_id, deleted_by_fname, deleted_by_lname)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $status = $item['status'] ?? $item['item_status'] ?? 'Active';
            
            $insertStmt->bind_param(
                "ssssisssssiissddsssss",
                $deleted_id,
                $item['item_id'],
                $item['item_photo'],
                $item['item_name'],
                $item['category_id'],
                $item['category_name'],
                $item['description'],
                $item['brand'],
                $item['model'],
                $item['serial_number'],
                $item['initial_quantity'],
                $item['quantity'],
                $item['date_acquired'],
                $item['unit'],
                $item['unit_cost'],
                $item['total_cost'],
                $item['item_status'],
                $item['created_at'],
                $deleted_by_user_id,
                $deleted_by_fname,
                $deleted_by_lname
            );

            if (!$insertStmt->execute()) {
                throw new Exception("Failed to insert into deleted items table: " . $insertStmt->error);
            }
            $insertStmt->close();
        } else {
            throw new Exception("Item not found in main items table");
        }

        // CHECK IF THIS IS THE LAST ITEM IN THE CATEGORY BEFORE DELETING
        $checkLastItemStmt = $conn->prepare("SELECT COUNT(*) as item_count FROM deped_inventory_items WHERE category_id = ?");
        $checkLastItemStmt->bind_param("s", $item['category_id']);
        $checkLastItemStmt->execute();
        $countResult = $checkLastItemStmt->get_result();
        $itemCount = $countResult->fetch_assoc()['item_count'];
        $checkLastItemStmt->close();

        $is_last_item = ($itemCount == 1); 

        // Delete from main items table
        $stmt = $conn->prepare("DELETE FROM deped_inventory_items WHERE item_id = ?");
        $stmt->bind_param("s", $item_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete from main items table: " . $stmt->error);
        }

        // Create notification for item deletion
        $user_id = $_SESSION['user']['user_id'] ?? null;
        $action_type = 'item_deleted';
        $message = "Item #{$item['item_id']} ({$item['item_name']}) was deleted from the inventory .";
        
        $notifStmt = $conn->prepare("
            INSERT INTO deped_inventory_notifications 
            (user_id, item_id, item_name, action_type, old_quantity, new_quantity, quantity_added, message, is_read) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $old_quantity = $item['quantity'];
        $new_quantity = NULL;
        $quantity_added = NULL;
        $is_read = 0;
        
        $notifStmt->bind_param("isssiiisi", 
            $user_id, 
            $item['item_id'], 
            $item['item_name'], 
            $action_type, 
            $old_quantity,
            $new_quantity,
            $quantity_added,
            $message,
            $is_read
        );
        
        $notifStmt->execute();
        $notifStmt->close();

        $conn->commit();
        
        // Store data in session for success message
        $_SESSION['deleted_all_item_name'] = $item['item_name'];
        $_SESSION['deleted_all_is_last_item'] = $is_last_item;
        
        // Simple redirect
        header("Location: /allItems?item_all_deleted=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete item failed: " . $e->getMessage());
        
        header("Location: /allItems?item_all_deleted=0");
        exit;
    }
}