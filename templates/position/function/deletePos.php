<?php
require __DIR__ . '/../../../database/dbConnection.php';

if (isset($_GET['id'])) {
    $position_id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM deped_inventory_employee_position WHERE position_id = ?");
    $stmt->bind_param("s", $position_id);

    if ($stmt->execute()) {

        header("Location: /position?deleted=1");
        exit;
    } else {
        header("Location: /position?deleted=0");
        exit;
    }
}
?>
