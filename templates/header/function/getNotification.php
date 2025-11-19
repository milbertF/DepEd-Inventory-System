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

// Get pagination parameters
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

$notifications = getAllNotifications($conn, $user_id, $limit, $offset);
$unread_count = getUnreadNotificationsCount($conn, $user_id);

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count,
    'has_more' => count($notifications) >= $limit 
]);
?>