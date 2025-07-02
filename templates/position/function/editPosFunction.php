<?php
require __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../sweetalert/sweetalert.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_position'])) {
    $position_id = $_POST['position_id'] ?? '';
    $title = trim($_POST['position_title'] ?? '');
    $description = trim($_POST['position_description'] ?? '');

    if (empty($position_id) || empty($title)) {
        showSweetAlert(
            'error',
            'Missing Data',
            'Position ID and Title are required.'
        );
        return;
    }


    $check = $conn->prepare("SELECT position_id FROM deped_inventory_employee_position 
        WHERE LOWER(position_title) = LOWER(?) 
        AND (LOWER(position_description) = LOWER(?) OR (position_description IS NULL AND ? = '')) 
        AND position_id != ?");
    $check->bind_param("ssss", $title, $description, $description, $position_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        showSweetAlert(
            'info',
            'Position Exists',
            'Another position with the same title and description already exists.'
        );
        return;
    }

    $stmt = $conn->prepare("UPDATE deped_inventory_employee_position 
        SET position_title = ?, position_description = ? 
        WHERE position_id = ?");
    $stmt->bind_param("sss", $title, $description, $position_id);

    if ($stmt->execute()) {
        showSweetAlert(
            'success',
            'Updated Successfully',
            "Position <b>" . htmlspecialchars($title) . "</b> has been updated.",
            $_SERVER['HTTP_REFERER']
        );
    } else {
        showSweetAlert(
            'error',
            'Update Failed',
            'An error occurred: ' . addslashes($conn->error)
        );
    }
}
?>
