<?php
require __DIR__ . '/../../../database/dbConnection.php';
require_once  __DIR__ . '/../../../sweetalert/sweetalert.php';

function generatePositionID($conn) {
    do {
        $id = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT); 
        $check = $conn->prepare("SELECT position_id FROM deped_inventory_employee_position WHERE position_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);
    return $id;
}



if (isset($_POST['submit_position'])) {
    $successCount = 0;
    $errorCount = 0;
    $duplicateCount = 0;
    $successTitles = [];
    $errorMessages = [];
    $duplicateMessages = [];
    $processedEntries = [];

    if (isset($_POST['positions']) && is_array($_POST['positions'])) {
        foreach ($_POST['positions'] as $position) {
            $title = trim($position['title']);
            $desc = trim($position['description'] ?? '');
            
            if (!empty($title)) {
          
                $entryKey = strtolower($title) . '|' . strtolower($desc);
                if (isset($processedEntries[$entryKey])) {
                    $duplicateCount++;
                    $duplicateMessages[] = "Duplicate in current submission: " . htmlspecialchars($title) . ($desc ? " (" . htmlspecialchars($desc) . ")" : "");
                    continue;
                }
                $processedEntries[$entryKey] = true;

                
                $check = $conn->prepare("SELECT position_id FROM deped_inventory_employee_position 
                                       WHERE LOWER(position_title) = LOWER(?) 
                                       AND (LOWER(position_description) = LOWER(?) OR (position_description IS NULL AND ? = ''))");
                $check->bind_param("sss", $title, $desc, $desc);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $duplicateCount++;
                    $duplicateMessages[] = "Position Title and Description already exists : " . htmlspecialchars($title) . ($desc ? " (" . htmlspecialchars($desc) . ")" : "");
                    continue;
                }

             
                $position_id = generatePositionID($conn);
                $stmt = $conn->prepare("INSERT INTO deped_inventory_employee_position (position_id, position_title, position_description, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", $position_id, $title, $desc);
                

                if ($stmt->execute()) {
                    $successCount++;
                    $successTitles[] = htmlspecialchars($title) . ($desc ? " (" . htmlspecialchars($desc) . ")" : "");
                } else {
                    $errorCount++;
                    $errorMessages[] = "Error adding position " . htmlspecialchars($title) . ": " . addslashes($conn->error);
                }
            }
        }
    } else {
        
        $title = trim($_POST['position_title'] ?? '');
        $desc = trim($_POST['position_description'] ?? '');
        
        if (!empty($title)) {
           
            $check = $conn->prepare("SELECT position_id FROM deped_inventory_employee_position 
                                   WHERE LOWER(position_title) = LOWER(?) 
                                   AND (LOWER(position_description) = LOWER(?) OR (position_description IS NULL AND ? = ''))");
            $check->bind_param("sss", $title, $desc, $desc);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $duplicateMessage = "Position Title and Description already exists: " . htmlspecialchars($title) . ($desc ? " (" . htmlspecialchars($desc) . ")" : "");
                showSweetAlert(
                    'info',
                    'Position Exists',
                    $duplicateMessage
                );
                return;
            }

         
            $position_id = generatePositionID($conn);
            $stmt = $conn->prepare("INSERT INTO deped_inventory_employee_position (position_id, position_title, position_description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $position_id, $title, $desc);
            

            if ($stmt->execute()) {
                showSweetAlert(
                    'success',
                    'Success',
                    "Position <b>" . htmlspecialchars($title) . "</b>" . ($desc ? " (" . htmlspecialchars($desc) . ")" : "") . " added successfully!",
                    $_SERVER['HTTP_REFERER']
                );
            } else {
                showSweetAlert(
                    'error',
                    'Error',
                    'Error adding position: ' . addslashes($conn->error)
                );
            }
            return;
        }
    }

 
    $message = '';
    
    if ($successCount > 0) {
        $message .= "<b>Successfully added " . $successCount . " position(s):</b><br>";
        $message .= implode("<br>", $successTitles) . "<br><br>";
    }
    
    if ($duplicateCount > 0) {
        $message .= "<b>The following positions were not added:</b><br>";
        $message .= implode("<br>", $duplicateMessages) . "<br><br>";
    }
    
    if ($errorCount > 0) {
        $message .= "<b>Failed to add " . $errorCount . " position(s):</b><br>";
        $message .= implode("<br>", $errorMessages);
    }


    if ($successCount > 0 && $errorCount === 0 && $duplicateCount === 0) {
        showSweetAlert(
            'success',
            'Success',
            $message,
            $_SERVER['HTTP_REFERER']
        );
    } elseif ($successCount === 0 && $errorCount === 0 && $duplicateCount > 0) {
        showSweetAlert(
            'info',
            'Existing Positions',
            $message,
            $_SERVER['HTTP_REFERER']
        );
    } elseif ($successCount > 0 && ($errorCount > 0 || $duplicateCount > 0)) {
        showSweetAlert(
            'warning',
            'Partial Success',
            $message,
            $_SERVER['HTTP_REFERER']
        );
    } elseif ($successCount === 0 && $errorCount > 0) {
        showSweetAlert(
            'error',
            'Error',
            $message
        );
    } else {
        showSweetAlert(
            'error',
            'Error',
            'No valid positions were submitted.'
        );
    }
}
?>