<?php
require __DIR__ . '/../../../database/dbConnection.php';

function generateRequestID($conn, $year = null) {
    if ($year === null) $year = date('Y');
    $year = substr(strval($year), 0, 4);

    do {
        $random_digits = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $id = $year . $random_digits;

        $check = $conn->prepare("SELECT request_id FROM deped_inventory_requests WHERE request_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);

    return $id;
}

function generateRequestItemID($conn, $year = null) {
    if ($year === null) $year = date('Y');
    $year = substr(strval($year), 0, 4);

    do {
        $random_digits = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $id = $year . $random_digits;

        $check = $conn->prepare("SELECT req_item_id FROM deped_inventory_request_items WHERE req_item_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);

    return $id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_item_request'])) {

    if (!isset($_SESSION['user']['user_id'])) {
        showSweetAlert(
            'error',
            'Session Error',
            'You are not logged in. Please log in and try again.'
        );
        return;
    }

    $user_id = $_SESSION['user']['user_id'];

    if (!isset($_POST['item_id']) || count($_POST['item_id']) == 0) {
        showSweetAlert(
            'warning',
            'No Items Selected',
            'Please select at least one item before submitting.'
        );
        return;
    }

    $item_ids   = $_POST['item_id'];
    $quantities = $_POST['quantity_requested'];
    $dates      = $_POST['date_needed'];
    $purposes   = $_POST['item_purpose'];
    
    foreach ($purposes as $purpose) {
        if (empty(trim($purpose))) {
            showSweetAlert(
                'warning',
                'Missing Purpose',
                'Please provide a purpose for all items.'
            );
            return;
        }
    }

    $request_id = generateRequestID($conn);

    $stmt = $conn->prepare("
        INSERT INTO deped_inventory_requests (request_id, user_id, status)
        VALUES (?, ?, 'Pending')
    ");

    if (!$stmt || !$stmt->bind_param("ss", $request_id, $user_id) || !$stmt->execute()) {
        showSweetAlert(
            'error',
            'Request Error',
            'Failed to create request. Please try again.'
        );
        return;
    }
    $stmt->close();

  
    $item_stmt = $conn->prepare("
        INSERT INTO deped_inventory_request_items (req_item_id, request_id, item_id, requested_quantity, purpose, date_needed)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$item_stmt) {
        showSweetAlert(
            'error',
            'Database Error',
            'Failed to prepare item insert.'
        );
        return;
    }

    $successCount = 0;

    for ($i = 0; $i < count($item_ids); $i++) {
        $request_item_id = generateRequestItemID($conn);
        
      
        if (!$item_stmt->bind_param("sssiss", $request_item_id, $request_id, $item_ids[$i], $quantities[$i], $purposes[$i], $dates[$i])) {
            continue;
        }
        if ($item_stmt->execute()) {
            $successCount++;
        }
    }

    $item_stmt->close();

    if ($successCount > 0) {
        showSweetAlert(
            'success',
            'Request Submitted',
            'Your request has been submitted successfully!',
            $_SERVER['HTTP_REFERER']
        );
    } else {
        showSweetAlert(
            'warning',
            'Request Partially Saved',
            'The request was created, but items failed to save.'
        );
    }
}