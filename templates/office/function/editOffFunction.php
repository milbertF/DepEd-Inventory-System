<?php
require __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../sweetalert/sweetalert.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_office'])) {
    $office_id = $_POST['office_id'] ?? '';
    $name = trim($_POST['office_name'] ?? '');
    $description = trim($_POST['office_description'] ?? '');

    if (empty($office_id) || empty($name)) {
        showSweetAlert(
            'error',
            'Missing Data',
            'Office ID and Name are required.'
        );
        return;
    }

  
    $check = $conn->prepare("SELECT office_id FROM deped_inventory_employee_office 
        WHERE LOWER(office_name) = LOWER(?) 
        AND (LOWER(office_description) = LOWER(?) OR (office_description IS NULL AND ? = '')) 
        AND office_id != ?");
    $check->bind_param("ssss", $name, $description, $description, $office_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        showSweetAlert(
            'info',
            'Office Exists',
            'Another office with the same name and description already exists.'
        );
        return;
    }


    $stmt = $conn->prepare("UPDATE deped_inventory_employee_office 
        SET office_name = ?, office_description = ? 
        WHERE office_id = ?");
    $stmt->bind_param("sss", $name, $description, $office_id);

    if ($stmt->execute()) {
        showSweetAlert(
            'success',
            'Updated Successfully',
            "Office <b>" . htmlspecialchars($name) . "</b> has been updated.",
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
