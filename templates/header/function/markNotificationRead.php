<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);


ob_start();

try {
    require_once __DIR__ . '/../../../database/dbConnection.php';
    require_once __DIR__ . '/fetchNotification.php';

    session_start();
    header('Content-Type: application/json');

 
    if (!isset($_SESSION['user']['id']) && !isset($_SESSION['user']['user_id'])) {
        throw new Exception('Not authenticated');
    }

    $user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception('User ID not found');
    }

  
    $is_admin = isAdmin($conn, $user_id);
    if (!$is_admin) {
        throw new Exception('Only administrators can mark notifications as read');
    }


    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    if (isset($input['mark_all']) && $input['mark_all']) {
      
        $stmt = $conn->prepare("SELECT notification_id FROM deped_inventory_notifications WHERE is_read = FALSE");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute statement: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $notification_ids = array_column($notifications, 'notification_id');
        $stmt->close();
        
        if (!empty($notification_ids)) {
            markNotificationsAsRead($conn, $notification_ids, $user_id);
            echo json_encode([
                'success' => true, 
                'marked_count' => count($notification_ids),
                'message' => count($notification_ids) . ' notifications marked as read'
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'marked_count' => 0, 
                'message' => 'No unread notifications'
            ]);
        }
    } else {
        throw new Exception('Invalid request - mark_all parameter required');
    }

} catch (Exception $e) {
    
    ob_clean();
    
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} finally {
   
    ob_end_flush();
}
?>