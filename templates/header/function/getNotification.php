<?php
require_once __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/fetchNotification.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id']) && !isset($_SESSION['user']['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;


$notifications = getAllNotifications($conn, $user_id, 15);
$unread_count = getUnreadNotificationsCount($conn, $user_id);

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);


?>