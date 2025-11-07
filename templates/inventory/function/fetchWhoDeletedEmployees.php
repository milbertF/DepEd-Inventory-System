<?php
require __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../config/security.php';
require_once __DIR__ . '/../../../config/encryption.php';


$employees = [];
$query = "
  SELECT 
    info.info_id,
    info.first_name,
    info.middle_name,
    info.last_name,
    pos.position_title,
    off.office_name
  FROM deped_inventory_user_info AS info
  LEFT JOIN deped_inventory_employee_position AS pos ON info.position_id = pos.position_id
  LEFT JOIN deped_inventory_employee_office AS off ON info.office_id = off.office_id
  ORDER BY info.created_at DESC
";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $first = decryptData($row['first_name'], APP_ENCRYPTION_KEY);
    $middle = decryptData($row['middle_name'], APP_ENCRYPTION_KEY);
    $last = decryptData($row['last_name'], APP_ENCRYPTION_KEY);

    $fullName = trim($first . ' ' . ($middle ?: '') . ' ' . $last);

    $employees[] = [
        'info_id' => $row['info_id'],
        'first_name' => $first,
        'middle_name' => $middle,
        'last_name' => $last,
        'full_name' => $fullName,
        'position_title' => $row['position_title'] ?? 'N/A',
        'office_name' => $row['office_name'] ?? 'N/A'
    ];
}



?>