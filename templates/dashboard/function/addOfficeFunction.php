<?php
require __DIR__ . '/../../../database/dbConnection.php';


function generateOfficeID($conn) {
    do {
        $id = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT); 
        $check = $conn->prepare("SELECT office_id FROM deped_inventory_employee_office WHERE office_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);
    return $id;
}

if (isset($_POST['submit_office'])) {
    $successCount = 0;
    $errorCount = 0;
    $duplicateCount = 0;
    $successNames = [];
    $errorMessages = [];
    $duplicateMessages = [];
    $processedEntries = [];

    if (isset($_POST['offices']) && is_array($_POST['offices'])) {
        foreach ($_POST['offices'] as $office) {
            $name = trim($office['name']);
            $description = trim($office['description'] ?? '');
            
            if (!empty($name)) {
             
                $entryKey = strtolower($name) . '|' . strtolower($description);
                if (isset($processedEntries[$entryKey])) {
                    $duplicateCount++;
                    $duplicateMessages[] = "Duplicate in current submission: " . htmlspecialchars($name) . ($description ? " (" . htmlspecialchars($description) . ")" : "");
                    continue;
                }
                $processedEntries[$entryKey] = true;

              
                $check = $conn->prepare("SELECT office_id FROM deped_inventory_employee_office 
                                       WHERE LOWER(office_name) = LOWER(?) 
                                       AND (LOWER(office_description) = LOWER(?) OR (office_description IS NULL AND ? = ''))");
                $check->bind_param("sss", $name, $description, $description);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $duplicateCount++;
                    $duplicateMessages[] = "Office  Title and Description already exists : " . htmlspecialchars($name) . ($description ? " (" . htmlspecialchars($description) . ")" : "");
                    continue;
                }

         
                $office_id = generateOfficeID($conn);
                $stmt = $conn->prepare("INSERT INTO deped_inventory_employee_office (office_id, office_name, office_description, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", $office_id, $name, $description);
                

                if ($stmt->execute()) {
                    $successCount++;
                    $successNames[] = htmlspecialchars($name) . ($description ? " (" . htmlspecialchars($description) . ")" : "");
                } else {
                    $errorCount++;
                    $errorMessages[] = "Error adding office " . htmlspecialchars($name) . ": " . addslashes($conn->error);
                }
            }
        }
    } else {
       
        $name = trim($_POST['office_name'] ?? '');
        $description = trim($_POST['office_description'] ?? '');
        
        if (!empty($name)) {
       
            $check = $conn->prepare("SELECT office_id FROM deped_inventory_employee_office 
                                   WHERE LOWER(office_name) = LOWER(?) 
                                   AND (LOWER(office_description) = LOWER(?) OR (office_description IS NULL AND ? = ''))");
            $check->bind_param("sss", $name, $description, $description);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $duplicateMessage = "Office Title and Description already exists : " . htmlspecialchars($name) . ($description ? " (" . htmlspecialchars($description) . ")" : "");
                showSweetAlert(
                    'info',
                    'Office Exists',
                    $duplicateMessage
                );
                return;
            }

         
            $office_id = generateOfficeID($conn);
            $stmt = $conn->prepare("INSERT INTO deped_inventory_employee_office (office_id, office_name, office_description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $office_id, $name, $description);
            

            if ($stmt->execute()) {
                showSweetAlert(
                    'success',
                    'Success',
                    "Office <b>" . htmlspecialchars($name) . "</b>" . ($description ? " (" . htmlspecialchars($description) . ")" : "") . " added successfully!",
                    $_SERVER['HTTP_REFERER']
                );
            } else {
                showSweetAlert(
                    'error',
                    'Error',
                    'Error adding office: ' . addslashes($conn->error)
                );
            }
            return;
        }
    }


    $message = '';
    
    if ($successCount > 0) {
        $message .= "<b>Successfully added " . $successCount . " office(s):</b><br>";
        $message .= implode("<br>", $successNames) . "<br><br>";
    }
    
    if ($duplicateCount > 0) {
        $message .= "<b>The following offices were not added:</b><br>";
        $message .= implode("<br>", $duplicateMessages) . "<br><br>";
    }
    
    if ($errorCount > 0) {
        $message .= "<b>Failed to add " . $errorCount . " office(s):</b><br>";
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
            'Existing Offices',
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
            'No valid offices were submitted.'
        );
    }
}
?>