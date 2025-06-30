<?php
require __DIR__ . '/../../../database/dbConnection.php';


$positions = [];
$positionQuery = "SELECT position_id, position_title FROM deped_inventory_employee_position ORDER BY position_title ASC";
$positionResult = $conn->query($positionQuery);
if ($positionResult) {
    while ($row = $positionResult->fetch_assoc()) {
        $positions[] = $row;
    }
}


$offices = [];
$officeQuery = "SELECT office_id, office_name FROM deped_inventory_employee_office ORDER BY office_name ASC";
$officeResult = $conn->query($officeQuery);
if ($officeResult) {
    while ($row = $officeResult->fetch_assoc()) {
        $offices[] = $row;
    }
}
?>
