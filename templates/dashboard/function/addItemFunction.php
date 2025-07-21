<?php
require_once __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../sweetalert/sweetalert.php';

function generateUniqueID($conn, $table, $column) {
    do {
        $id = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $check = $conn->prepare("SELECT $column FROM `$table` WHERE $column = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);
    return $id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_item'])) {
    $item_id = generateUniqueID($conn, 'deped_inventory_items', 'item_id');
    $item_name = trim($_POST['item_name']);
    $category_id = intval($_POST['category_id']);
    $description = trim($_POST['description'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $quantity = intval($_POST['quantity']);
    $unit = trim($_POST['unit']);
    $unit_cost = floatval($_POST['unit_cost']);
    $date_acquired = !empty($_POST['date_acquired']) ? $_POST['date_acquired'] : null;

 
    $photo_path = '';
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
                showSweetAlert('error', 'Upload Error', 'Failed to upload image.');
                return;
            }
        } else {
            showSweetAlert('error', 'Invalid File', 'Only JPG, JPEG, PNG, and GIF are allowed.');
            return;
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO deped_inventory_items (
            item_id, item_photo, item_name, category_id, description,
            brand, model, serial_number, quantity, unit, unit_cost, date_acquired
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt) {
        $stmt->bind_param(
            "sssssssssdds",
            $item_id,
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
            $date_acquired
        );

        if ($stmt->execute()) {
            showSweetAlert(
                'success',
                'Item Added',
                "Item <b>$item_name</b> successfully added with ID: <b>$item_id</b>",
                $_SERVER['HTTP_REFERER']
            );
        } else {
            showSweetAlert('error', 'Insert Failed', 'Failed to add item. Please try again.');
        }

        $stmt->close();
    } else {
        showSweetAlert('error', 'Database Error', $conn->error);
    }

    $conn->close();
}
