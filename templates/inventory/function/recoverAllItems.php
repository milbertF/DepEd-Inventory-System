<?php
require __DIR__ . '/../../../database/dbConnection.php';
session_start();

try {
    

    $stmt = $conn->prepare("SELECT item_id, item_photo, item_name, category_id, category_name, description, brand, model, serial_number, total_quantity,available_quantity, date_acquired, unit, unit_cost, total_cost, item_status, created_at 
                            FROM deped_inventory_items_deleted");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['recovery_message'] = true; 
        $_SESSION['message'] = "No items found in deleted records!";
        $_SESSION['msg_type'] = "info";
        header("Location: /recentlyDeleted");
        exit();
    }

    $recovered_count = 0;
    $recovered_items = [];
    $categories_created = 0;
    $modified_category_names = [];

  
    $insert = $conn->prepare("INSERT INTO deped_inventory_items 
        (item_id, item_photo, item_name, category_id, description, brand, model, serial_number, total_quantity,available_quantity ,date_acquired, unit, unit_cost, total_cost, item_status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?)");

    $delete = $conn->prepare("DELETE FROM deped_inventory_items_deleted WHERE item_id = ?");
    
   
    $categoryCheck = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE category_id = ?");
    
    
    $createCategory = $conn->prepare("INSERT INTO deped_inventory_item_category (category_name) VALUES (?)");
    

    $checkDuplicateCategory = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE category_name = ?");
    
  
    $recoveredCategoryCheck = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE category_name = 'Recovered Items'");
    $recoveredCategoryCheck->execute();
    $recoveredCategoryResult = $recoveredCategoryCheck->get_result();
    $recoveredCategoryId = null;
    
    if ($recoveredCategoryResult->num_rows > 0) {
        $recoveredCategory = $recoveredCategoryResult->fetch_assoc();
        $recoveredCategoryId = $recoveredCategory['category_id'];
    }
    $recoveredCategoryCheck->close();

    while ($item = $result->fetch_assoc()) {
        $currentCategoryId = $item['category_id'];
        
        
        $categoryCheck->bind_param("i", $item['category_id']);
        $categoryCheck->execute();
        $categoryResult = $categoryCheck->get_result();
        $categoryExists = $categoryResult->num_rows > 0;
        
       
        if (!$categoryExists) {
            if (!empty($item['category_name'])) {
               
                $checkDuplicateCategory->bind_param("s", $item['category_name']);
                $checkDuplicateCategory->execute();
                $duplicateResult = $checkDuplicateCategory->get_result();
                
                $finalCategoryName = $item['category_name'];
                $categoryWasModified = false;
                
                if ($duplicateResult->num_rows > 0) {
                   
                    $finalCategoryName = $item['category_name'] . "-Recovered";
                    
                  
                    $checkRecoveredVersion = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE category_name = ?");
                    $checkRecoveredVersion->bind_param("s", $finalCategoryName);
                    $checkRecoveredVersion->execute();
                    $recoveredVersionResult = $checkRecoveredVersion->get_result();
                    
                    if ($recoveredVersionResult->num_rows > 0) {
                   
                        $timestamp = date('Ymd-His');
                        $finalCategoryName = $item['category_name'] . "-Recovered-" . $timestamp;
                    }
                    $checkRecoveredVersion->close();
                    
                    $categoryWasModified = true;
                    $modified_category_names[] = $finalCategoryName;
                }
                
                
                $createCategory->bind_param("s", $finalCategoryName);
                if ($createCategory->execute()) {
                    $currentCategoryId = $conn->insert_id;
                    $categories_created++;
                    
                    if ($categoryWasModified) {
                       
                        $_SESSION['modified_categories'][] = [
                            'original' => $item['category_name'],
                            'new' => $finalCategoryName
                        ];
                    }
                } else {
                   
                    if (!$recoveredCategoryId) {
                       
                        $createRecoveredCategory = $conn->prepare("INSERT INTO deped_inventory_item_category (category_name) VALUES ('Recovered Items')");
                        if ($createRecoveredCategory->execute()) {
                            $recoveredCategoryId = $conn->insert_id;
                            $categories_created++;
                        }
                        $createRecoveredCategory->close();
                    }
                    $currentCategoryId = $recoveredCategoryId;
                }
            } else {
                
                if (!$recoveredCategoryId) {
                 
                    $createRecoveredCategory = $conn->prepare("INSERT INTO deped_inventory_item_category (category_name) VALUES ('Recovered Items')");
                    if ($createRecoveredCategory->execute()) {
                        $recoveredCategoryId = $conn->insert_id;
                        $categories_created++;
                    }
                    $createRecoveredCategory->close();
                }
                $currentCategoryId = $recoveredCategoryId;
            }
        }

      
        $insert->bind_param(
            "ississssiissddss", 
            $item['item_id'],
            $item['item_photo'],
            $item['item_name'],
            $currentCategoryId,
            $item['description'],
            $item['brand'],
            $item['model'],
            $item['serial_number'],
            $item['total_quantity'],
            $item['available_quantity'],
            $item['date_acquired'],
            $item['unit'],
            $item['unit_cost'],
            $item['total_cost'],
            $item['item_status'], 
            $item['created_at']
        );

        if ($insert->execute()) {
            $delete->bind_param("i", $item['item_id']);
            $delete->execute();
            $recovered_count++;
            $recovered_items[] = $item['item_name'];
        }
    }

 
    $categoryCheck->close();
    $createCategory->close();
    $checkDuplicateCategory->close();
    $insert->close();
    $delete->close();

    if ($recovered_count > 0) {
        $message = "Successfully recovered {$recovered_count} items!";
        if ($categories_created > 0) {
            $message .= " {$categories_created} categories were recreated.";
        }
        
      
        if (!empty($modified_category_names)) {
            $unique_modified = array_unique($modified_category_names);
            if (count($unique_modified) > 0) {
                $message .= " Some categories were renamed due to duplicates: " . implode(', ', $unique_modified);
            }
        }
        
       
        $_SESSION['recovery_message'] = true;
        $_SESSION['message'] = $message;
        $_SESSION['msg_type'] = "success";
        $_SESSION['recovered_items'] = $recovered_items;
        $_SESSION['recovered_count'] = $recovered_count;
    } else {
      
        $_SESSION['recovery_message'] = true;
        $_SESSION['message'] = "Error recovering items. Please try again.";
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