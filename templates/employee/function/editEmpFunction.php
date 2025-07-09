<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_edit_employee'])) {
    require __DIR__ . '/../../../database/dbConnection.php';
    require_once __DIR__ . '/../../../config/security.php';
    require_once __DIR__ . '/../../../config/encryption.php';
    require_once __DIR__ . '/../../../sweetalert/sweetalert.php';

    if (!defined('APP_ENCRYPTION_KEY') || strlen(APP_ENCRYPTION_KEY) !== 64 || !ctype_xdigit(APP_ENCRYPTION_KEY)) {
        showSweetAlert('error', 'System Error', 'Invalid encryption configuration');
        return;
    }

    $requiredFields = ['info_id', 'user_id', 'firstName', 'lastName', 'contact', 'address', 'employee_position', 'employee_office', 'account_role'];
    foreach ($requiredFields as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            showSweetAlert('error', 'Error', 'Please fill in all required fields.');
            return;
        }
    }

    $infoId = $_POST['info_id'];
    $userId = $_POST['user_id'];
    $firstName = htmlspecialchars(trim($_POST['firstName']));
    $middleName = htmlspecialchars(trim($_POST['middleName'] ?? ''));
    $lastName = htmlspecialchars(trim($_POST['lastName']));
    $contact = preg_replace('/[^0-9]/', '', trim($_POST['contact']));
    $address = htmlspecialchars(trim($_POST['address']));
    $positionId = (int)$_POST['employee_position'];
    $officeId = (int)$_POST['employee_office'];
    $accountRole = trim($_POST['account_role']);

    $profilePhoto = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../images/user-profile/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['photo']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            showSweetAlert('error', 'Invalid Image', 'Only JPG, PNG, and GIF files are allowed.');
            return;
        }

        $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $baseFilename = preg_replace('/[^a-z0-9]/i', '_', strtolower($firstName . '_' . $lastName));
        $fileName = $baseFilename . '.' . $fileExt;
        $uploadPath = $uploadDir . $fileName;

        $counter = 1;
        while (file_exists($uploadPath)) {
            $fileName = $baseFilename . '_' . $counter . '.' . $fileExt;
            $uploadPath = $uploadDir . $fileName;
            $counter++;
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            $profilePhoto = '/images/user-profile/' . $fileName;
        } else {
            showSweetAlert('error', 'Upload Error', 'Failed to upload profile photo.');
            return;
        }
    }


    $encryptedFirstName = encryptData($firstName, APP_ENCRYPTION_KEY);
    $encryptedMiddleName = encryptData($middleName, APP_ENCRYPTION_KEY);
    $encryptedLastName = encryptData($lastName, APP_ENCRYPTION_KEY);
    $encryptedContact = encryptData($contact, APP_ENCRYPTION_KEY);
    $encryptedAddress = encryptData($address, APP_ENCRYPTION_KEY);

    try {
      
        $query = "
            UPDATE deped_inventory_user_info
            SET first_name = ?, middle_name = ?, last_name = ?, 
                contact_number = ?, address = ?, position_id = ?, office_id = ?
                " . ($profilePhoto ? ", profile_photo = ?" : "") . "
            WHERE info_id = ?
        ";

        if ($profilePhoto) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssssss",
                $encryptedFirstName, $encryptedMiddleName, $encryptedLastName,
                $encryptedContact, $encryptedAddress, $positionId, $officeId,
                $profilePhoto, $infoId
            );
        } else {
            $query = str_replace(", profile_photo = ?", "", $query);
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssssi",
                $encryptedFirstName, $encryptedMiddleName, $encryptedLastName,
                $encryptedContact, $encryptedAddress, $positionId, $officeId,
                $infoId
            );
        }

        $stmt->execute();


        $roleQuery = "UPDATE deped_inventory_users SET role = ? WHERE user_id = ?";
        $roleStmt = $conn->prepare($roleQuery);
        $roleStmt->bind_param("si", $accountRole, $userId);
        $roleStmt->execute();

        showSweetAlert(
            'success',
            'Updated',
            "Employee account for <b>$firstName $lastName</b> has been updated!",
            $_SERVER['HTTP_REFERER']
        );
    } catch (Exception $e) {
        error_log("Edit failed: " . $e->getMessage());
        showSweetAlert('error', 'Error', 'Failed to update employee details.');
    }
}
?>
