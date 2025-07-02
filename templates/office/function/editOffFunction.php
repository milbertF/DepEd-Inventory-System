<?php
require __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../sweetalert/sweetalert.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_office'])) {
    $office_id = $_POST['office_id'] ?? '';
    $name = trim($_POST['office_name'] ?? '');
    $location = trim($_POST['office_location'] ?? '');

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
        AND (LOWER(office_location) = LOWER(?) OR (office_location IS NULL AND ? = '')) 
        AND office_id != ?");
    $check->bind_param("ssss", $name, $location, $location, $office_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        showSweetAlert(
            'info',
            'Office Exists',
            'Another office with the same name and location already exists.'
        );
        return;
    }


    $stmt = $conn->prepare("UPDATE deped_inventory_employee_office 
        SET office_name = ?, office_location = ? 
        WHERE office_id = ?");
    $stmt->bind_param("sss", $name, $location, $office_id);

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
