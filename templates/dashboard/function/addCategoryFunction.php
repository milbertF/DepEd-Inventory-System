<?php
require __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../sweetalert/sweetalert.php';

function generateCategoryID($conn) {
    do {
        $id = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $check = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE category_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);
    return $id;
}

if (isset($_POST['submit_category'])) {
    $successCount = 0;
    $errorCount = 0;
    $duplicateCount = 0;
    $successNames = [];
    $errorMessages = [];
    $duplicateMessages = [];
    $processedNames = [];

    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
        foreach ($_POST['categories'] as $category) {
            $name = trim($category['name']);

            if (!empty($name)) {
                $entryKey = strtolower($name);
                if (isset($processedNames[$entryKey])) {
                    $duplicateCount++;
                    $duplicateMessages[] = "Duplicate in current submission: " . htmlspecialchars($name);
                    continue;
                }
                $processedNames[$entryKey] = true;

                $check = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE LOWER(category_name) = LOWER(?)");
                $check->bind_param("s", $name);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $duplicateCount++;
                    $duplicateMessages[] = "Category already exists: " . htmlspecialchars($name);
                    continue;
                }

                $category_id = generateCategoryID($conn);
                $stmt = $conn->prepare("INSERT INTO deped_inventory_item_category (category_id, category_name, created_at) VALUES (?, ?, NOW())");
                $stmt->bind_param("ss", $category_id, $name);

                if ($stmt->execute()) {
                    $successCount++;
                    $successNames[] = htmlspecialchars($name);
                } else {
                    $errorCount++;
                    $errorMessages[] = "Error adding category " . htmlspecialchars($name) . ": " . addslashes($conn->error);
                }
            }
        }
    } else {
        $name = trim($_POST['category_name'] ?? '');

        if (!empty($name)) {
            $check = $conn->prepare("SELECT category_id FROM deped_inventory_item_category WHERE LOWER(category_name) = LOWER(?)");
            $check->bind_param("s", $name);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                showSweetAlert(
                    'info',
                    'Category Exists',
                    "Category already exists: <b>" . htmlspecialchars($name) . "</b>"
                );
                return;
            }

            $category_id = generateCategoryID($conn);
            $stmt = $conn->prepare("INSERT INTO deped_inventory_item_category (category_id, category_name, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ss", $category_id, $name);

            if ($stmt->execute()) {
                showSweetAlert(
                    'success',
                    'Success',
                    "Category <b>" . htmlspecialchars($name) . "</b> added successfully!",
                    $_SERVER['HTTP_REFERER']
                );
            } else {
                showSweetAlert(
                    'error',
                    'Error',
                    'Error adding category: ' . addslashes($conn->error)
                );
            }
            return;
        }
    }

    $message = '';
    if ($successCount > 0) {
        $message .= "<b>Successfully added " . $successCount . " category(ies):</b><br>";
        $message .= implode("<br>", $successNames) . "<br><br>";
    }

    if ($duplicateCount > 0) {
        $message .= "<b>The following categories were not added (duplicates):</b><br>";
        $message .= implode("<br>", $duplicateMessages) . "<br><br>";
    }

    if ($errorCount > 0) {
        $message .= "<b>Failed to add " . $errorCount . " category(ies):</b><br>";
        $message .= implode("<br>", $errorMessages);
    }

    if ($successCount > 0 && $errorCount === 0 && $duplicateCount === 0) {
        showSweetAlert('success', 'Success', $message, $_SERVER['HTTP_REFERER']);
    } elseif ($successCount === 0 && $errorCount === 0 && $duplicateCount > 0) {
        showSweetAlert('info', 'Duplicate', $message, $_SERVER['HTTP_REFERER']);
    } elseif ($successCount > 0 && ($errorCount > 0 || $duplicateCount > 0)) {
        showSweetAlert('warning', 'Partial Success', $message, $_SERVER['HTTP_REFERER']);
    } elseif ($successCount === 0 && $errorCount > 0) {
        showSweetAlert('error', 'Error', $message);
    } else {
        showSweetAlert('error', 'Error', 'No valid categories were submitted.');
    }
}
?>
