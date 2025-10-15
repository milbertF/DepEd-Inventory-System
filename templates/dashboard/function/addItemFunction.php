<?php
require_once __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../sweetalert/sweetalert.php';

function generateItemUniqueID($conn, $table, $column, $year = null) {
    
    if ($year === null) {
        $year = date('Y');
    }
    
   
    $year = substr(strval($year), 0, 4);
    
    do {
     
        $random_digits = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
 
        $id = $year . $random_digits;
        
        $check = $conn->prepare("SELECT $column FROM `$table` WHERE $column = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);
    
    return $id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_item'])) {
    $item_name = trim($_POST['item_name']);
    $category_id = intval($_POST['category_id']);
    $description = trim($_POST['description'] ?? '');
    $brand = isset($_POST['brand']) && trim($_POST['brand']) !== '' ? trim($_POST['brand']) : null;
    $model = isset($_POST['model']) && trim($_POST['model']) !== '' ? trim($_POST['model']) : null;
    $unit  = isset($_POST['unit'])  && trim($_POST['unit'])  !== '' ? trim($_POST['unit'])  : null;
    $item_status = isset($_POST['item_status']) && trim($_POST['item_status']) !== '' ? trim($_POST['item_status']) : 'Good';

    $unit_cost = floatval($_POST['unit_cost']);
    $date_acquired = !empty($_POST['date_acquired']) ? $_POST['date_acquired'] : null;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($quantity < 1) {
        showSweetAlert('error', 'Invalid Quantity', 'Quantity must be at least 1.');
        return;
    }

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

  
    $serials = [];
    if (!empty($_POST['serial_number'])) {
        $serials[] = trim($_POST['serial_number']);
    }
    if (!empty($_POST['additional_serials']) && is_array($_POST['additional_serials'])) {
        foreach ($_POST['additional_serials'] as $s) {
            if (trim($s)) {
                $serials[] = trim($s);
            }
        }
    }
    if (empty($serials)) {
        $serials[] = null;
    }

    $success_count = 0;

 
    $year = null;
    if (!empty($date_acquired)) {
        $year = date('Y', strtotime($date_acquired));
    }

    foreach ($serials as $serial) {
      
        $item_id = generateItemUniqueID($conn, 'deped_inventory_items', 'item_id', $year);
        
        $stmt = $conn->prepare("
            INSERT INTO deped_inventory_items (
                item_id, item_photo, item_name, category_id, description,
                brand, model, serial_number, quantity, initial_quantity, unit,
                unit_cost, date_acquired, item_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt) {
            $stmt->bind_param(
                "sssissssiisdss",
                $item_id,
                $photo_path,
                $item_name,
                $category_id,
                $description,
                $brand,
                $model,
                $serial,
                $quantity,
                $quantity, 
                $unit,
                $unit_cost,
                $date_acquired,
                $item_status
            );

            if ($stmt->execute()) {
                $success_count++;
            }

            $stmt->close();
        }
    }

    if ($success_count > 0) {
        showSweetAlert(
            'success',
            'Items Added',
            "$success_count item(s) successfully added.",
            $_SERVER['HTTP_REFERER']
        );
    } else {
        showSweetAlert('error', 'Insert Failed', 'Failed to add any items.');
    }

   
}
?>