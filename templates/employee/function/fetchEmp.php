<?php
require __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../config/security.php';
require_once __DIR__ . '/../../../config/encryption.php';

$employees = [];
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $limit;

$countQuery = "SELECT COUNT(*) as total FROM deped_inventory_user_info";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRecords / $limit);

$query = "
  SELECT 
    info.info_id,
    info.user_id,
    info.first_name,
    info.middle_name,
    info.last_name,
    info.contact_number,
    info.address,
    info.profile_photo,
    info.position_id,
    info.office_id,
    info.created_at,

    users.email,
    users.username,
    users.role,

    pos.position_title,
    off.office_name

  FROM deped_inventory_user_info AS info
  LEFT JOIN deped_inventory_users AS users ON info.user_id = users.user_id
  LEFT JOIN deped_inventory_employee_position AS pos ON info.position_id = pos.position_id
  LEFT JOIN deped_inventory_employee_office AS off ON info.office_id = off.office_id
  ORDER BY info.created_at DESC
  LIMIT ?, ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $start, $limit);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $employees[] = [
        'info_id' => $row['info_id'],
        'user_id' => $row['user_id'],
        'first_name' => decryptData($row['first_name'], APP_ENCRYPTION_KEY),
        'middle_name' => decryptData($row['middle_name'], APP_ENCRYPTION_KEY),
        'last_name' => decryptData($row['last_name'], APP_ENCRYPTION_KEY),
        'contact_number' => decryptData($row['contact_number'], APP_ENCRYPTION_KEY),
        'address' => decryptData($row['address'], APP_ENCRYPTION_KEY),
        'profile_photo' => $row['profile_photo'],
        'position_id' => $row['position_id'], 
        'office_id' => $row['office_id'],     
        'position_title' => $row['position_title'],
        'office_name' => $row['office_name'],
        'email' => decryptData($row['email'], APP_ENCRYPTION_KEY),
        'username' => $row['username'],
        'role' => $row['role'],
        'created_at' => $row['created_at']
    ];
}


