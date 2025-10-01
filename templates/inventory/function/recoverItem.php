<?php
require __DIR__ . '/../../../database/dbConnection.php';
session_start();

if (!isset($_GET['id'])) {
    $_SESSION['message'] = "Invalid request!";
    $_SESSION['msg_type'] = "error";
    header("Location: /templates/inventory/deletedItems.php");
    exit();
}

$item_id = intval($_GET['id']);

try {
    
    $stmt = $conn->prepare("SELECT item_id, item_photo, item_name, category_id, description, brand, model, serial_number, quantity, date_acquired, unit, unit_cost, total_cost, created_at 
                            FROM deped_inventory_items_deleted WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['message'] = "Item not found in deleted records!";
        $_SESSION['msg_type'] = "error";
        header("Location: /templates/inventory/deletedItems.php");
        exit();
    }

    $item = $result->fetch_assoc();

  
    $insert = $conn->prepare("INSERT INTO deped_inventory_items 
        (item_id, item_photo, item_name, category_id, description, brand, model, serial_number, quantity, date_acquired, unit, unit_cost, total_cost, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $insert->bind_param(
        "ississssissdds",
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
        $item['created_at']
    );

    if ($insert->execute()) {
       
        $delete = $conn->prepare("DELETE FROM deped_inventory_items_deleted WHERE item_id = ?");
        $delete->bind_param("i", $item_id);
        $delete->execute();

        $_SESSION['message'] = "Item \"{$item['item_name']}\" successfully recovered!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error recovering item. Please try again.";
        $_SESSION['msg_type'] = "error";
    }
} catch (Exception $e) {
    $_SESSION['message'] = "Exception: " . $e->getMessage();
    $_SESSION['msg_type'] = "error";
}

header("Location: /recentlyDeleted");
exit();
