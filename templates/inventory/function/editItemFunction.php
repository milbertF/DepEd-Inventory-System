<?php
require_once __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../sweetalert/sweetalert.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_edit_item'])) {
    $item_id = $_POST['item_id'] ?? '';
    $item_name = trim($_POST['item_name']);
    $category_id = intval($_POST['category_id']);
    $description = trim($_POST['description'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $quantity = intval($_POST['quantity']);
    $unit = trim($_POST['unit']);
    $unit_cost = floatval($_POST['unit_cost']);
    $date_acquired = (!empty($_POST['date_acquired']) && $_POST['date_acquired'] !== '0000-00-00') 
    ? $_POST['date_acquired'] 
    : null;


    $stmt_old = $conn->prepare("SELECT item_photo FROM deped_inventory_items WHERE item_id = ?");
    $stmt_old->bind_param("s", $item_id);
    $stmt_old->execute();
    $stmt_old->bind_result($existing_photo);
    $stmt_old->fetch();
    $stmt_old->close();

    $photo_path = $existing_photo;


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

       
            $old_file_path = __DIR__ . '/../../../' . $existing_photo;
            if (is_file($old_file_path)) {
                unlink($old_file_path);
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
            unit = ?,
            unit_cost = ?,
            date_acquired = ?
        WHERE item_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param(
            "ssissssisdss",
            $photo_path,
            $item_name,
            $category_id,
            $description,
            $brand,
            $model,
            $serial_number,
            $quantity,
            $unit,
            $unit_cost,
            $date_acquired,
            $item_id
        );

        if ($stmt->execute()) {
            showSweetAlert(
                'success',
                'Item Updated',
                "Item <b>$item_name</b>  has been updated.",
                $_SERVER['HTTP_REFERER']
            );
        } else {
            showSweetAlert('error', 'Update Failed', 'Failed to update item. Please try again.');
        }

        $stmt->close();
    } else {
        showSweetAlert('error', 'Database Error', $conn->error);
    }

    $conn->close();
}
?>
