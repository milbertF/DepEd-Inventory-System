<?php
require_once __DIR__ . '/../../../database/dbConnection.php';


function addQuantityToItem($conn, $item_id, $quantity_to_add, $user_id) {
 
    $conn->begin_transaction();
    
    try {
       
        $stmt = $conn->prepare("SELECT item_name, total_quantity, available_quantity FROM deped_inventory_items WHERE item_id = ?");
        $stmt->bind_param("s", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Item not found.");
        }
        
        $item = $result->fetch_assoc();
        $current_total_quantity = $item['total_quantity'];
        $current_available_quantity = $item['available_quantity'];
        $item_name = $item['item_name'];
        
       
        $new_total_quantity = $current_total_quantity + $quantity_to_add;
        $new_available_quantity = $current_available_quantity + $quantity_to_add;
        
     
        $update_stmt = $conn->prepare("UPDATE deped_inventory_items SET total_quantity = ?, available_quantity = ? WHERE item_id = ?");
        $update_stmt->bind_param("iis", $new_total_quantity, $new_available_quantity, $item_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update item quantity.");
        }
        
       
        $notification_stmt = $conn->prepare("
            INSERT INTO deped_inventory_notifications 
            (user_id, item_id, item_name, action_type, old_quantity, new_quantity, quantity_added, message) 
            VALUES (?, ?, ?, 'quantity_added', ?, ?, ?, ?)
        ");

        $message = "Item #{$item_id} ({$item_name}): Added {$quantity_to_add} quantity. Quantity changed from {$current_total_quantity} to {$new_total_quantity}.";
        $notification_stmt->bind_param("issiiis", $user_id, $item_id, $item_name, $current_total_quantity, $new_total_quantity, $quantity_to_add, $message);
        
        if (!$notification_stmt->execute()) {
            throw new Exception("Failed to create notification.");
        }
        

        $conn->commit();
        
        return [
            'success' => true,
            'old_quantity' => $current_total_quantity,
            'new_quantity' => $new_total_quantity,
            'quantity_added' => $quantity_to_add,
            'item_name' => $item_name,
            'item_id' => $item_id
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_quantity'])) {
  
    
    if (!isset($_SESSION['user']['user_id'])) {
        showSweetAlert('error', 'Authentication Error', 'Please log in to continue.');
        exit();
    }
    
    $item_id = trim($_POST['item_id']);
    $quantity_to_add = intval($_POST['total_quantity']);
    $user_id = $_SESSION['user']['user_id'];
    
    if ($quantity_to_add < 1) {
        showSweetAlert('error', 'Invalid Quantity', 'Quantity must be at least 1.');
        exit();
    }
    
    $result = addQuantityToItem($conn, $item_id, $quantity_to_add, $user_id);
    
    if ($result['success']) {
        // Store in session for any follow-up actions
        $_SESSION['quantity_added'] = true;
        $_SESSION['added_item_name'] = $result['item_name'];
        $_SESSION['quantity_added_amount'] = $result['quantity_added'];
        $_SESSION['old_quantity'] = $result['old_quantity'];
        $_SESSION['new_quantity'] = $result['new_quantity'];
        $_SESSION['item_id'] = $result['item_id'];
        
        showSweetAlert(
            'success',
            'Quantity Added',
            "Successfully added {$result['quantity_added']} to '{$result['item_name']}'. Quantity updated from {$result['old_quantity']} to {$result['new_quantity']}.",
            $_SERVER['HTTP_REFERER']
        );
    } else {
        showSweetAlert('error', 'Operation Failed', $result['error']);
    }
    
 
}
?>