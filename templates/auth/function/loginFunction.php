<?php
session_start();
require_once __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../config/security.php';
require_once __DIR__ . '/../../../sweetalert/sweetalert.php';
require_once __DIR__ . '/../../../config/encryption.php'; 

function handleLogin() {
    global $conn;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($identifier) || empty($password)) {
            showSweetAlert('error', 'Error', 'Please fill in all fields');
            return false;
        }

        try {
            $user = null;
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

            if ($isEmail) {
                $stmt = $conn->prepare("SELECT * FROM `deped_inventory_users`");
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $decryptedEmail = decryptData($row['email'], APP_ENCRYPTION_KEY);
                    if ($decryptedEmail && strtolower($decryptedEmail) === strtolower($identifier)) {
                        $user = $row;
                        break;
                    }
                }

                if (!$user) {
                    showSweetAlert('error', 'Error', 'Invalid credentials');
                    return false;
                }
            } else {
                $stmt = $conn->prepare("SELECT * FROM `deped_inventory_users` WHERE username = ?");
                $stmt->bind_param("s", $identifier);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if (!$user) {
                    showSweetAlert('error', 'Error', 'Invalid credentials');
                    return false;
                }
            }

            if (!password_verify($password, $user['password'])) {
                showSweetAlert('error', 'Error', 'Invalid credentials');
                return false;
            }

            $infoStmt = $conn->prepare("SELECT * FROM `deped_inventory_user_info` WHERE user_id = ?");
            $infoStmt->bind_param("s", $user['user_id']);
            $infoStmt->execute();
            $infoResult = $infoStmt->get_result();
            $userInfo = $infoResult->fetch_assoc();

            $_SESSION['user'] = [
                'id' => $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'first_name' => decryptData($userInfo['first_name'], APP_ENCRYPTION_KEY),
                'last_name' => decryptData($userInfo['last_name'], APP_ENCRYPTION_KEY),
                'profile_photo' => $userInfo['profile_photo'],
                'logged_in' => true
            ];

            return true;

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            showSweetAlert('error', 'Error', 'Login failed. Please try again.');
            return false;
        }
    }

    return false;
}
