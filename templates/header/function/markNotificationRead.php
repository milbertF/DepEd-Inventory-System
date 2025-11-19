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

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    if (isset($input['mark_all']) && $input['mark_all']) {
        $total_marked = 0;
        
        if ($is_admin) {
            // Mark system notifications as read
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
                $total_marked += count($notification_ids);
            }
            
            // Mark ALL admin request logs as read
            $stmt = $conn->prepare("
                SELECT log_id 
                FROM deped_inventory_request_logs 
                WHERE is_read = FALSE AND for_admin = TRUE
            ");
            
            if (!$stmt) {
                throw new Exception('Failed to prepare statement for request logs: ' . $conn->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute statement for request logs: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $request_logs = $result->fetch_all(MYSQLI_ASSOC);
            $log_ids = array_column($request_logs, 'log_id');
            $stmt->close();
            
            if (!empty($log_ids)) {
                $placeholders = implode(',', array_fill(0, count($log_ids), '?'));
                $types = str_repeat('i', count($log_ids));
                
                $stmt = $conn->prepare("
                    UPDATE deped_inventory_request_logs 
                    SET is_read = TRUE 
                    WHERE log_id IN ($placeholders) AND for_admin = TRUE
                ");
                
                if ($stmt) {
                    $params = $log_ids;
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $total_marked += $stmt->affected_rows;
                    $stmt->close();
                }
            }
        } else {
            // Regular user: mark their employee request logs as read
            $stmt = $conn->prepare("
                SELECT rl.log_id 
                FROM deped_inventory_request_logs rl
                INNER JOIN deped_inventory_request_items ri ON rl.req_item_id = ri.req_item_id
                INNER JOIN deped_inventory_requests r ON ri.request_id = r.request_id
                WHERE r.user_id = ? AND rl.is_read = FALSE AND rl.for_emp = TRUE
            ");
            
            if (!$stmt) {
                throw new Exception('Failed to prepare statement for request logs: ' . $conn->error);
            }
            
            $stmt->bind_param("s", $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute statement for request logs: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $request_logs = $result->fetch_all(MYSQLI_ASSOC);
            $log_ids = array_column($request_logs, 'log_id');
            $stmt->close();
            
            if (!empty($log_ids)) {
                $placeholders = implode(',', array_fill(0, count($log_ids), '?'));
                $types = str_repeat('i', count($log_ids));
                
                $stmt = $conn->prepare("
                    UPDATE deped_inventory_request_logs 
                    SET is_read = TRUE 
                    WHERE log_id IN ($placeholders) AND for_emp = TRUE
                ");
                
                if ($stmt) {
                    $params = $log_ids;
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $total_marked += $stmt->affected_rows;
                    $stmt->close();
                }
            }
        }
        
        echo json_encode([
            'success' => true, 
            'marked_count' => $total_marked,
            'message' => $total_marked . ' notifications marked as read'
        ]);
        
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