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
       
        $fetchStmt = $conn->prepare("SELECT * FROM deped_inventory_items WHERE item_id = ?");
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
                (deleted_id, item_id, item_photo, item_name, category_id, description, brand, model, serial_number, quantity, date_acquired, unit, unit_cost, total_cost, status, created_at, deleted_by_user_id, deleted_by_fname, deleted_by_lname)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->bind_param(
                "sssssssssissddsssss",
                $deleted_id,
                $item['item_id'],
                $item['item_photo'],
                $item['item_name'],
                $item['category_id'],
                $item['description'],
                $item['brand'],
                $item['model'],
                $item['serial_number'],
                $item['quantity'],
                $item['date_acquired'],
                $item['unit'],
                $item['unit_cost'],
                $item['total_cost'],
                $item['status'],
                $item['created_at'],
                $deleted_by_user_id,
                $deleted_by_fname,
                $deleted_by_lname
            );

            if (!$insertStmt->execute()) {
                throw new Exception("Failed to insert into deleted items table");
            }
            $insertStmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM deped_inventory_items WHERE item_id = ?");
        $stmt->bind_param("s", $item_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete from main items table");
        }

    
        $conn->commit();
        header("Location: /allItems?deleted=1");
        exit;
    } catch (Exception $e) {
    
        $conn->rollback();
        error_log("Delete item failed: " . $e->getMessage());
        header("Location: /allItems?deleted=0");
        exit;
    }
}
?>