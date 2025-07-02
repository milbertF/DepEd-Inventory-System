<?php
require __DIR__ . '/../../../database/dbConnection.php';

if (isset($_GET['id'])) {
    $office_id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM deped_inventory_employee_office WHERE office_id = ?");
    $stmt->bind_param("s", $office_id);

    if ($stmt->execute()) {
        header("Location: /office?deleted=1");
        exit;
    } else {
        header("Location: /office?deleted=0");
        exit;
    }
}
?>
