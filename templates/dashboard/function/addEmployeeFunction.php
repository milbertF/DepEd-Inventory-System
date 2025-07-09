<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_employee'])) {
    require __DIR__ . '/../../../database/dbConnection.php';
    require_once __DIR__ . '/../../../config/security.php';
    require_once __DIR__ . '/../../../config/encryption.php';
    require_once  __DIR__ . '/../../../sweetalert/sweetalert.php';
    function generateUniqueID($conn, $table, $column) {
        do {
            $id = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $check = $conn->prepare("SELECT $column FROM `$table` WHERE $column = ?");
            $check->bind_param("s", $id);
            $check->execute();
            $check->store_result();
        } while ($check->num_rows > 0);
        return $id;
    }





    if (!defined('APP_ENCRYPTION_KEY') || strlen(APP_ENCRYPTION_KEY) !== 64 || !ctype_xdigit(APP_ENCRYPTION_KEY)) {
        showSweetAlert('error', 'System Error', 'Invalid encryption configuration');
        return;
    }

    $requiredFields = ['email', 'firstName', 'lastName', 'contact', 'address', 'employee_position', 'employee_office', 'account_role'];
    foreach ($requiredFields as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            showSweetAlert('error', 'Error', 'Please fill in all required fields.');
            return;
        }
    }

    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        showSweetAlert('error', 'Invalid Email', 'Please enter a valid email address.');
        return;
    }

    $firstName = htmlspecialchars(trim($_POST['firstName']));
    $middleName = htmlspecialchars(trim($_POST['middleName'] ?? ''));
    $lastName = htmlspecialchars(trim($_POST['lastName']));
    $contact = preg_replace('/[^0-9]/', '', trim($_POST['contact']));
    $address = htmlspecialchars(trim($_POST['address']));
    $positionId = (int)$_POST['employee_position'];
    $officeId = (int)$_POST['employee_office'];
    $role = in_array($_POST['account_role'], ['Admin', 'Employee']) ? $_POST['account_role'] : 'Employee';
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

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
        if (!in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
            showSweetAlert('error', 'Error', 'Invalid file extension.');
            return;
        }

        $cleanFirstName = preg_replace('/[^a-z0-9]/i', '_', strtolower($firstName));
        $cleanLastName = preg_replace('/[^a-z0-9]/i', '_', strtolower($lastName));
        $baseFilename = $cleanFirstName . '_' . $cleanLastName;
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
            showSweetAlert('error', 'Error', 'Failed to upload profile photo.');
            return;
        }
    }

    if (empty($username)) {
        $username = strtolower($firstName);
    }

    if (empty($password)) {
        $password = strtolower($lastName);
    } else {
        if ($password !== $confirmPassword) {
            showSweetAlert('error', 'Password Mismatch', 'Passwords do not match.');
            return;
        }
        if (strlen($password) < 8) {
            showSweetAlert('error', 'Weak Password', 'Password must be at least 8 characters long.');
            return;
        }
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $userId = generateUniqueID($conn, 'deped_inventory_users', 'user_id');
    $infoId = generateUniqueID($conn, 'deped_inventory_user_info', 'info_id');

    $encryptedEmail = encryptData($email, APP_ENCRYPTION_KEY);
    $encryptedFirstName = encryptData($firstName, APP_ENCRYPTION_KEY);
    $encryptedMiddleName = encryptData($middleName, APP_ENCRYPTION_KEY);
    $encryptedLastName = encryptData($lastName, APP_ENCRYPTION_KEY);
    $encryptedContact = encryptData($contact, APP_ENCRYPTION_KEY);
    $encryptedAddress = encryptData($address, APP_ENCRYPTION_KEY);

    try {
        $conn->begin_transaction();

        $checkEmail = $conn->prepare("SELECT user_id FROM `deped_inventory_users` WHERE email = ?");
        $checkEmail->bind_param("s", $encryptedEmail);
        $checkEmail->execute();
        $checkEmail->store_result();

        if ($checkEmail->num_rows > 0) {
            showSweetAlert('error', 'Duplicate Email', 'This email is already registered.');
            return;
        }

        $stmtUser = $conn->prepare("INSERT INTO `deped_inventory_users`
            (user_id, email, username, password, role, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())");
        $stmtUser->bind_param("sssss", $userId, $encryptedEmail, $username, $hashedPassword, $role);
        $stmtUser->execute();

        $stmtInfo = $conn->prepare("INSERT INTO `deped_inventory_user_info`
            (info_id, user_id, first_name, middle_name, last_name, contact_number,
             address, profile_photo, position_id, office_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmtInfo->bind_param("ssssssssss", $infoId, $userId, $encryptedFirstName, $encryptedMiddleName,
            $encryptedLastName, $encryptedContact, $encryptedAddress, $profilePhoto, $positionId, $officeId);
        $stmtInfo->execute();

        $conn->commit();

        showSweetAlert(
            'success',
            'Success',
            "Employee account for <b>$firstName $lastName</b> created successfully!",
            $_SERVER['HTTP_REFERER']
        );

 
      
    } catch (Exception $e) {
        $conn->rollback();

        if ($profilePhoto && file_exists(__DIR__ . '/../../../' . $profilePhoto)) {
            unlink(__DIR__ . '/../../../' . $profilePhoto);
        }

        error_log("Employee creation failed: " . $e->getMessage());
        showSweetAlert('error', 'Error', 'Failed to create account. Please try again.');


   
    }
}