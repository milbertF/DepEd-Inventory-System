<?php
require_once __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../sweetalert/sweetalert.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_edit_item'])) {
    $item_id = $_POST['item_id'] ?? '';
    $item_name = trim($_POST['item_name']);
    $category_id = intval($_POST['category_id']);

    $description = !empty(trim($_POST['description'] ?? '')) ? trim($_POST['description']) : null;
    $brand = !empty(trim($_POST['brand'] ?? '')) ? trim($_POST['brand']) : null;
    $model = !empty(trim($_POST['model'] ?? '')) ? trim($_POST['model']) : null;
    $serial_number = !empty(trim($_POST['serial_number'] ?? '')) ? trim($_POST['serial_number']) : null;
    $quantity = intval($_POST['quantity']);
    $initial_quantity = intval($_POST['initial_quantity']);
    $unit = !empty(trim($_POST['unit'] ?? '')) ? trim($_POST['unit']) : null;

    $unit_cost = floatval($_POST['unit_cost']);
    $date_acquired = (!empty($_POST['date_acquired']) && $_POST['date_acquired'] !== '0000-00-00')
        ? $_POST['date_acquired']
        : null;
    $item_status = trim($_POST['item_status']);
    $remarks = !empty(trim($_POST['remarks'] ?? '')) ? trim($_POST['remarks']) : null;

    // Get current user info
    $user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;
    
    // Get old item data for comparison - UPDATED: Added initial_quantity and remarks
    $stmt_old = $conn->prepare("SELECT item_name, quantity, initial_quantity, category_id, remarks FROM deped_inventory_items WHERE item_id = ?");
    $stmt_old->bind_param("s", $item_id);
    $stmt_old->execute();
    $stmt_old->bind_result($old_item_name, $old_quantity, $old_initial_quantity, $old_category_id, $old_remarks);
    $stmt_old->fetch();
    $stmt_old->close();

    $stmt_old_photo = $conn->prepare("SELECT item_photo FROM deped_inventory_items WHERE item_id = ?");
    $stmt_old_photo->bind_param("s", $item_id);
    $stmt_old_photo->execute();
    $stmt_old_photo->bind_result($existing_photo);
    $stmt_old_photo->fetch();
    $stmt_old_photo->close();

    $photo_path = $existing_photo;

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_tmp = $_FILES['photo']['tmp_name'];
        $photo_name = basename($_FILES['photo']['name']);
        $photo_ext = strtolower(pathinfo($photo_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($photo_ext, $allowed_exts)) {
            $unique_name = uniqid('item_', true) . '.' . $photo_ext;
            $upload_dir = __DIR__ . '/../../../images/items/';
            $photo_path = 'images/items/' . $unique_name;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (!move_uploaded_file($photo_tmp, $upload_dir . $unique_name)) {
                showSweetAlert('error', 'Upload Error', 'Failed to upload new image.');
                return;
            }
        } else {
            showSweetAlert('error', 'Invalid File', 'Only JPG, JPEG, PNG, and GIF are allowed.');
            return;
        }
    }

    $stmt = $conn->prepare("
        UPDATE deped_inventory_items SET
            item_photo = ?,
            item_name = ?,
            category_id = ?,
            description = ?,
            brand = ?,
            model = ?,
            serial_number = ?,
            quantity = ?,
            initial_quantity = ?,
            unit = ?,
            unit_cost = ?,
            date_acquired = ?,
            item_status = ?,
            remarks = ?
        WHERE item_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param(
            "ssissssiisdsssi",
            $photo_path,
            $item_name,
            $category_id,
            $description,
            $brand,
            $model,
            $serial_number,
            $quantity,
            $initial_quantity,
            $unit,
            $unit_cost,
            $date_acquired,
            $item_status,
            $remarks,
            $item_id
        );

        foreach (['description', 'brand', 'model', 'serial_number', 'unit', 'date_acquired'] as $field) {
            if ($$field === null) {
                $stmt->send_long_data(array_search($field, ['description', 'brand', 'model', 'serial_number', 'unit', 'date_acquired']), null);
            }
        }

        if ($stmt->execute()) {
           
            // UPDATED: Pass initial_quantity and remarks to the notification function
            createItemUpdateNotification($conn, $user_id, $item_id, $item_name, $old_item_name, $quantity, $old_quantity, $initial_quantity, $old_initial_quantity, $remarks, $old_remarks);
            
            showSweetAlert(
                'success',
                'Item Updated',
                "Item <b>$item_name</b> has been updated.",
                $_SERVER['HTTP_REFERER']
            );
        } else {
            showSweetAlert('error', 'Update Failed', 'Failed to update item. Please try again.');
        }

        $stmt->close();
    } else {
        showSweetAlert('error', 'Database Error', $conn->error);
    }
}

// UPDATED: Added initial_quantity parameters to the function
function createItemUpdateNotification($conn, $user_id, $item_id, $new_item_name, $old_item_name, $new_quantity, $old_quantity, $new_initial_quantity, $old_initial_quantity, $new_remarks, $old_remarks) {
    if (!$user_id) return;
    
    $action_type = 'item_updated';
    $message = '';
    
    $changes = [];
    
    // Check for item name changes
    if ($new_item_name !== $old_item_name) {
        $changes[] = "name changed from '$old_item_name' to '$new_item_name'";
    }
    
    // Check for quantity changes
    if ($new_quantity != $old_quantity) {
        $changes[] = "quantity changed from $old_quantity to $new_quantity";
    }
    
    // Check for initial quantity changes
    if ($new_initial_quantity != $old_initial_quantity) {
        $changes[] = "initial quantity changed from $old_initial_quantity to $new_initial_quantity";
    }
    
    // Check for remarks changes
    if ($new_remarks !== $old_remarks) {
        if (empty($new_remarks)) {
            $changes[] = "remarks removed";
        } elseif (empty($old_remarks)) {
            $changes[] = "remarks added: '$new_remarks'";
        } else {
            $changes[] = "remarks updated to: '$new_remarks'";
        }
    }
    
    // Build the message based on changes detected
    if (!empty($changes)) {
        $change_text = implode(', ', $changes);
        $message = "Item #$item_id ($new_item_name) was updated: $change_text";
    } else {
        $message = "Item #$item_id ($new_item_name) was updated with no visible changes";
    }
    
    $stmt = $conn->prepare("
        INSERT INTO deped_inventory_notifications 
        (user_id, item_id, item_name, action_type, old_quantity, new_quantity, message) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("sisssis", 
        $user_id, 
        $item_id, 
        $new_item_name, 
        $action_type, 
        $old_quantity, 
        $new_quantity, 
        $message
    );
    
    $stmt->execute();
    $stmt->close();
}
?>