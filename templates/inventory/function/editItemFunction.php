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
    $total_quantity = intval($_POST['total_quantity']);
    $available_quantity = intval($_POST['available_quantity']);
    $unit = !empty(trim($_POST['unit'] ?? '')) ? trim($_POST['unit']) : null;
    $unit_cost = floatval($_POST['unit_cost']);
    $date_acquired = (!empty($_POST['date_acquired']) && $_POST['date_acquired'] !== '0000-00-00')
        ? $_POST['date_acquired']
        : null;
    $item_status = trim($_POST['item_status']);
    $remarks = !empty(trim($_POST['remarks'] ?? '')) ? trim($_POST['remarks']) : null;

    $user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;

    // 🔹 Get old item data for comparison
    $stmt_old = $conn->prepare("
        SELECT item_name, total_quantity, available_quantity, category_id, description, brand, model, serial_number, unit, unit_cost, date_acquired, item_status, remarks
        FROM deped_inventory_items 
        WHERE item_id = ?
    ");
    $stmt_old->bind_param("s", $item_id);
    $stmt_old->execute();
    $stmt_old->bind_result(
        $old_item_name, $old_total_quantity, $old_available_quantity, $old_category_id,
        $old_description, $old_brand, $old_model, $old_serial_number,
        $old_unit, $old_unit_cost, $old_date_acquired, $old_item_status, $old_remarks
    );
    $stmt_old->fetch();
    $stmt_old->close();

    // 🔹 Get existing photo
    $stmt_old_photo = $conn->prepare("SELECT item_photo FROM deped_inventory_items WHERE item_id = ?");
    $stmt_old_photo->bind_param("s", $item_id);
    $stmt_old_photo->execute();
    $stmt_old_photo->bind_result($existing_photo);
    $stmt_old_photo->fetch();
    $stmt_old_photo->close();

    $photo_path = $existing_photo;

    // 🔹 Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo_tmp = $_FILES['photo']['tmp_name'];
        $photo_name = basename($_FILES['photo']['name']);
        $photo_ext = strtolower(pathinfo($photo_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($photo_ext, $allowed_exts)) {
            $unique_name = uniqid('item_', true) . '.' . $photo_ext;
            $upload_dir = __DIR__ . '/../../../images/items/';
            $photo_path = 'images/items/' . $unique_name;

            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (!move_uploaded_file($photo_tmp, $upload_dir . $unique_name)) {
                showSweetAlert('error', 'Upload Error', 'Failed to upload new image.');
                return;
            }
        } else {
            showSweetAlert('error', 'Invalid File', 'Only JPG, JPEG, PNG, and GIF are allowed.');
            return;
        }
    }

    // 🔹 Update the item
    $stmt = $conn->prepare("
        UPDATE deped_inventory_items SET
            item_photo = ?,
            item_name = ?,
            category_id = ?,
            description = ?,
            brand = ?,
            model = ?,
            serial_number = ?,
            total_quantity = ?,
            available_quantity = ?,
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
            $total_quantity,
            $available_quantity,
            $unit,
            $unit_cost,
            $date_acquired,
            $item_status,
            $remarks,
            $item_id
        );

        if ($stmt->execute()) {
            // 🔹 Create detailed notification
            createItemUpdateNotification(
                $conn,
                $user_id,
                $item_id,
                $item_name,
                compact(
                    'old_item_name', 'old_total_quantity', 'old_available_quantity', 'old_category_id',
                    'old_description', 'old_brand', 'old_model', 'old_serial_number',
                    'old_unit', 'old_unit_cost', 'old_date_acquired', 'old_item_status', 'old_remarks'
                ),
                compact(
                    'item_name', 'total_quantity', 'available_quantity', 'category_id',
                    'description', 'brand', 'model', 'serial_number',
                    'unit', 'unit_cost', 'date_acquired', 'item_status', 'remarks'
                )
            );

            showSweetAlert(
                'success',
                'Item Updated',
                "Item <b>$item_name</b> has been successfully updated.",
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

/**
 * 🔔 Creates a detailed notification listing all changed fields
 */
function createItemUpdateNotification($conn, $user_id, $item_id, $item_name, $oldData, $newData)
{
    if (!$user_id) return;

    $changes = [];

    foreach ($newData as $field => $newValue) {
        $oldValue = $oldData['old_' . $field] ?? null;

        // Normalize nulls
        $oldValue = $oldValue === null ? '' : $oldValue;
        $newValue = $newValue === null ? '' : $newValue;

        if ($newValue != $oldValue) {
            $fieldLabel = str_replace('_', ' ', $field);
            if ($oldValue === '') {
                $changes[] = "$fieldLabel set to '$newValue'";
            } elseif ($newValue === '') {
                $changes[] = "$fieldLabel removed (was '$oldValue')";
            } else {
                $changes[] = "$fieldLabel changed from '$oldValue' to '$newValue'";
            }
        }
    }

    $action_type = 'item_updated';
    $message = empty($changes)
        ? "Item #$item_id ($item_name) was updated with no visible changes"
        : "Item #$item_id ($item_name) was updated: " . implode(', ', $changes);

    $old_quantity = $oldData['old_total_quantity'] ?? null;
    $new_quantity = $newData['total_quantity'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO deped_inventory_notifications 
        (user_id, item_id, item_name, action_type, old_quantity, new_quantity, message) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sisssis",
        $user_id,
        $item_id,
        $item_name,
        $action_type,
        $old_quantity,
        $new_quantity,
        $message
    );
    $stmt->execute();
    $stmt->close();
}
?>
