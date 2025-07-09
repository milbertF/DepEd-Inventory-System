<?php

require __DIR__ . '/../database/dbConnection.php';
require_once __DIR__ . '/../config/security.php';

function decryptData($encryptedData, $key) {
    if (empty($encryptedData)) return null;
    
    $keyBinary = hex2bin($key);
    list($encrypted, $iv) = explode('::', base64_decode($encryptedData), 2);
    
    return openssl_decrypt($encrypted, 'aes-256-cbc', $keyBinary, 0, $iv);
}


if (!defined('APP_ENCRYPTION_KEY') || strlen(APP_ENCRYPTION_KEY) !== 64 || !ctype_xdigit(APP_ENCRYPTION_KEY)) {
    die('Invalid encryption configuration');
}


$query = "SELECT 
            u.user_id, u.email, u.username, u.role, u.created_at,
            ui.first_name, ui.middle_name, ui.last_name, 
            ui.contact_number, ui.address, ui.profile_photo,
            ui.position_id, ui.office_id
          FROM deped_inventory_users u
          JOIN deped_inventory_user_info ui ON u.user_id = ui.user_id
          ORDER BY u.created_at DESC";

$result = $conn->query($query);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Employee Data Decrypter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        img { max-width: 100px; max-height: 100px; }
    </style>
</head>
<body>
    <h1>Employee Data Decrypter</h1>
    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>Email</th>
                <th>Username</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Role</th>
                <th>Position ID</th>
                <th>Office ID</th>
                <th>Profile Photo</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
    echo "<td>" . htmlspecialchars(decryptData($row['email'], APP_ENCRYPTION_KEY)) . "</td>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars(decryptData($row['first_name'], APP_ENCRYPTION_KEY)) . "</td>";
    echo "<td>" . htmlspecialchars(decryptData($row['middle_name'], APP_ENCRYPTION_KEY)) . "</td>";
    echo "<td>" . htmlspecialchars(decryptData($row['last_name'], APP_ENCRYPTION_KEY)) . "</td>";
    echo "<td>" . htmlspecialchars(decryptData($row['contact_number'], APP_ENCRYPTION_KEY)) . "</td>";
    echo "<td>" . htmlspecialchars(decryptData($row['address'], APP_ENCRYPTION_KEY)) . "</td>";
    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
    echo "<td>" . htmlspecialchars($row['position_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['office_id']) . "</td>";
    

    if (!empty($row['profile_photo'])) {
        echo "<td><img src='" . htmlspecialchars($row['profile_photo']) . "' alt='Profile Photo'></td>";
    } else {
        echo "<td>No photo</td>";
    }
    
    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
    echo "</tr>";
}

echo "</tbody>
    </table>
</body>
</html>";

$conn->close();
?>