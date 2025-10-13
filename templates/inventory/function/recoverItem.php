<?php
require __DIR__ . '/../../../database/dbConnection.php';
session_start();

if (!isset($_GET['id'])) {
    $_SESSION['recovery_message'] = true;
    $_SESSION['message'] = "Invalid request!";
    $_SESSION['msg_type'] = "error";
    header("Location: /recentlyDeleted");
    exit();
}

$item_id = intval($_GET['id']);

try {
    
    // Get the item from deleted table including category_name
    $stmt = $conn->prepare("SELECT item_id, item_photo, item_name, category_id, category_name, description, brand, model, serial_number, quantity, initial_quantity,date_acquired, unit, unit_cost, total_cost, item_status, created_at 
                            FROM deped_inventory_items_deleted WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['recovery_message'] = true;
        $_SESSION['message'] = "Item not found in deleted records!";
        $_SESSION['msg_type'] = "error";
        header("Location: /recentlyDeleted");
        exit();
    }

    $item = $result->fetch_assoc();

    // Check if category still exists
    $categoryCheck = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE category_id = ?");
    $categoryCheck->bind_param("i", $item['category_id']);
    $categoryCheck->execute();
    $categoryResult = $categoryCheck->get_result();
    
    $categoryExists = $categoryResult->num_rows > 0;
    $categoryCheck->close();

    $categoryRecreated = false;
    $newCategoryId = $item['category_id'];
    $categoryMessage = "";

    // If category doesn't exist, recreate it with original name (checking for duplicates)
    if (!$categoryExists && !empty($item['category_name'])) {
        // Check if a category with the same name already exists
        $checkDuplicateCategory = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE category_name = ?");
        $checkDuplicateCategory->bind_param("s", $item['category_name']);
        $checkDuplicateCategory->execute();
        $duplicateResult = $checkDuplicateCategory->get_result();
        
        if ($duplicateResult->num_rows > 0) {
            // Category with same name exists, append "-Recovered"
            $recoveredCategoryName = $item['category_name'] . "-Recovered";
            
            // Check if the "-Recovered" version also exists
            $checkRecoveredVersion = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE category_name = ?");
            $checkRecoveredVersion->bind_param("s", $recoveredCategoryName);
            $checkRecoveredVersion->execute();
            $recoveredVersionResult = $checkRecoveredVersion->get_result();
            
            if ($recoveredVersionResult->num_rows > 0) {
                // Both original and "-Recovered" exist, append timestamp to make it unique
                $timestamp = date('Ymd-His');
                $recoveredCategoryName = $item['category_name'] . "-Recovered-" . $timestamp;
            }
            $checkRecoveredVersion->close();
            
            // Create the recovered category with modified name
            $createCategory = $conn->prepare("INSERT INTO deped_inventory_item_category (category_name) VALUES (?)");
            $createCategory->bind_param("s", $recoveredCategoryName);
            
            if ($createCategory->execute()) {
                $newCategoryId = $conn->insert_id;
                $categoryRecreated = true;
                $categoryMessage = " and new category '{$recoveredCategoryName}' was created (original name was in use)";
            } else {
                throw new Exception("Failed to create recovered category");
            }
            $createCategory->close();
        } else {
            // No duplicate, create the original category name
            $createCategory = $conn->prepare("INSERT INTO deped_inventory_item_category (category_name) VALUES (?)");
            $createCategory->bind_param("s", $item['category_name']);
            
            if ($createCategory->execute()) {
                $newCategoryId = $conn->insert_id;
                $categoryRecreated = true;
                $categoryMessage = " and category '{$item['category_name']}' was recreated";
            } else {
                throw new Exception("Failed to recreate category");
            }
            $createCategory->close();
        }
        $checkDuplicateCategory->close();
    } else if (!$categoryExists && empty($item['category_name'])) {
        // If category doesn't exist and we don't have the category name, use "Recovered Items"
        $recoveredCategoryName = "Recovered Items";
        
        // Check if "Recovered Items" category already exists
        $checkRecoveredCategory = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE category_name = ?");
        $checkRecoveredCategory->bind_param("s", $recoveredCategoryName);
        $checkRecoveredCategory->execute();
        $recoveredCategoryResult = $checkRecoveredCategory->get_result();
        
        if ($recoveredCategoryResult->num_rows > 0) {
            // Use existing "Recovered Items" category
            $recoveredCategory = $recoveredCategoryResult->fetch_assoc();
            $newCategoryId = $recoveredCategory['category_id'];
            $categoryMessage = " and assigned to 'Recovered Items' category";
        } else {
            // Create new "Recovered Items" category
            $createCategory = $conn->prepare("INSERT INTO deped_inventory_item_category (category_name) VALUES (?)");
            $createCategory->bind_param("s", $recoveredCategoryName);
            if ($createCategory->execute()) {
                $newCategoryId = $conn->insert_id;
                $categoryMessage = " and new 'Recovered Items' category was created";
            } else {
                throw new Exception("Failed to create recovery category");
            }
            $createCategory->close();
        }
        $checkRecoveredCategory->close();
        $categoryRecreated = true;
    }

    // Insert into main inventory with the (possibly new) category_id - FIXED SQL
    $insert = $conn->prepare("INSERT INTO deped_inventory_items 
        (item_id, item_photo, item_name, category_id, description, brand, model, serial_number, quantity, initial_quantity, date_acquired, unit, unit_cost, total_cost, item_status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    //  16 parameters, 16 characters in bind_param
    $insert->bind_param(
        "ississssiissddss", // 16 characters for 16 parameters
        $item['item_id'],
        $item['item_photo'],
        $item['item_name'],
        $newCategoryId,
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
        $item['created_at']
    );

    if ($insert->execute()) {
        // Delete from deleted table
        $delete = $conn->prepare("DELETE FROM deped_inventory_items_deleted WHERE item_id = ?");
        $delete->bind_param("i", $item_id);
        $delete->execute();

     
        $_SESSION['recovery_message'] = true;
        if ($categoryRecreated) {
            $_SESSION['message'] = "Item \"{$item['item_name']}\" successfully recovered{$categoryMessage}!";
        } else {
            $_SESSION['message'] = "Item \"{$item['item_name']}\" successfully recovered!";
        }
        $_SESSION['msg_type'] = "success";
        $_SESSION['recovered_items'] = [$item['item_name']];
        $_SESSION['recovered_count'] = 1;
    } else {
        $_SESSION['recovery_message'] = true;
        $_SESSION['message'] = "Error recovering item. Please try again.";
        $_SESSION['msg_type'] = "error";
    }
} catch (Exception $e) {
    $_SESSION['recovery_message'] = true;
    $_SESSION['message'] = "Exception: " . $e->getMessage();
    $_SESSION['msg_type'] = "error";
}

header("Location: /recentlyDeleted");
exit();
?>