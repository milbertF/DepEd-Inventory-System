<?php
require_once __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../config/security.php';
require_once __DIR__ . '/../../../config/encryption.php';

// UPDATED: Get combined notifications with pagination - FIXED DUPLICATES
function getCombinedNotifications($conn, $user_id, $limit = 15, $offset = 0) {
    $is_admin = isAdmin($conn, $user_id);
    $all_notifications = [];

    if ($is_admin) {
        // Admin gets system notifications
        $stmt = $conn->prepare("
            SELECT 
                n.*,
                info.first_name,
                info.middle_name, 
                info.last_name,
                'system' as notification_type
            FROM deped_inventory_notifications n 
            LEFT JOIN deped_inventory_user_info info ON n.user_id = info.user_id 
            ORDER BY n.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $system_notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $all_notifications = array_merge($all_notifications, $system_notifications);
    }

    // Request notifications logic - FIXED: Only get relevant entries
    $request_notifications = [];
    
    if ($is_admin) {
        // ADMIN: Get request notifications where for_admin = TRUE
        $stmt = $conn->prepare("
            SELECT DISTINCT
                NULL as notification_id,
                rl.log_id,
                ? as current_user_id,
                rl.item_id,
                rl.item_name,
                rl.action_type,
                rl.message,
                rl.created_at,
                rl.is_read,
                rl.performed_by,
                r.request_id,
                r.user_id as requester_id,
                performer_info.first_name as performer_first_name,
                performer_info.middle_name as performer_middle_name,
                performer_info.last_name as performer_last_name,
                requester_info.first_name as requester_first_name,
                requester_info.middle_name as requester_middle_name,
                requester_info.last_name as requester_last_name,
                performer_user.role as performer_role,
                'request' as notification_type
            FROM deped_inventory_request_logs rl
            INNER JOIN deped_inventory_request_items ri ON rl.req_item_id = ri.req_item_id
            INNER JOIN deped_inventory_requests r ON ri.request_id = r.request_id
            LEFT JOIN deped_inventory_user_info performer_info ON rl.performed_by = performer_info.user_id
            LEFT JOIN deped_inventory_user_info requester_info ON r.user_id = requester_info.user_id
            LEFT JOIN deped_inventory_users performer_user ON rl.performed_by = performer_user.user_id
            WHERE rl.for_admin = TRUE
            ORDER BY rl.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("sii", $user_id, $limit, $offset);
    } else {
        // REGULAR USER: Get request notifications where for_emp = TRUE AND user is the requester
        $stmt = $conn->prepare("
            SELECT DISTINCT
                NULL as notification_id,
                rl.log_id,
                ? as current_user_id,
                rl.item_id,
                rl.item_name,
                rl.action_type,
                rl.message,
                rl.created_at,
                rl.is_read,
                rl.performed_by,
                r.request_id,
                r.user_id as requester_id,
                performer_info.first_name as performer_first_name,
                performer_info.middle_name as performer_middle_name,
                performer_info.last_name as performer_last_name,
                performer_user.role as performer_role,
                'request' as notification_type
            FROM deped_inventory_request_logs rl
            INNER JOIN deped_inventory_request_items ri ON rl.req_item_id = ri.req_item_id
            INNER JOIN deped_inventory_requests r ON ri.request_id = r.request_id
            LEFT JOIN deped_inventory_user_info performer_info ON rl.performed_by = performer_info.user_id
            LEFT JOIN deped_inventory_users performer_user ON rl.performed_by = performer_user.user_id
            WHERE rl.for_emp = TRUE AND r.user_id = ?
            ORDER BY rl.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ssii", $user_id, $user_id, $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $request_notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $all_notifications = array_merge($all_notifications, $request_notifications);
    
    // Sort all notifications by date
    usort($all_notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit the final result
    $all_notifications = array_slice($all_notifications, 0, $limit);
    
    // Process names and determine display text
    foreach ($all_notifications as &$notif) {
        if ($notif['notification_type'] === 'system') {
            // System notifications - show who triggered the system action
            if ($notif['first_name'] && $notif['last_name']) {
                $first = decryptData($notif['first_name'], APP_ENCRYPTION_KEY);
                $middle = $notif['middle_name'] ? decryptData($notif['middle_name'], APP_ENCRYPTION_KEY) : '';
                $last = decryptData($notif['last_name'], APP_ENCRYPTION_KEY);
                
                $fullName = trim($first . ' ' . ($middle ?: '') . ' ' . $last);
                $notif['user_full_name'] = $fullName;
            } else {
                $notif['user_full_name'] = 'System';
            }
        } elseif ($notif['notification_type'] === 'request') {
            // Create new message with Request ID, Item ID, and status change
            $requestId = $notif['request_id'] ?? '';
            $itemId = $notif['item_id'] ?? '';
            
            if ($requestId && $itemId) {
                // Extract status change information
                $oldStatus = '';
                $newStatus = '';
                
                // Parse the existing message to get status changes
                if (preg_match('/Status changed from (\w+) to (\w+)/', $notif['message'], $matches)) {
                    $oldStatus = ucfirst($matches[1]);  
                    $newStatus = ucfirst($matches[2]); 
                }
                
                // Create new message format
                $notif['message'] = "Request #{$requestId} - Item #{$itemId} status changed from {$oldStatus} to {$newStatus}";
            }
            
            // Request notifications - complex logic for display
            $current_user_id = $notif['current_user_id'];
            $performed_by = $notif['performed_by'];
            $requester_id = $notif['requester_id'];
            $performer_role = $notif['performer_role'];
            
            // Get who did the action full name
            $performer_name = 'System';
            if ($notif['performer_first_name'] && $notif['performer_last_name']) {
                $first = decryptData($notif['performer_first_name'], APP_ENCRYPTION_KEY);
                $middle = $notif['performer_middle_name'] ? decryptData($notif['performer_middle_name'], APP_ENCRYPTION_KEY) : '';
                $last = decryptData($notif['performer_last_name'], APP_ENCRYPTION_KEY);
                $performer_name = trim($first . ' ' . ($middle ?: '') . ' ' . $last);
            }
            
            if ($is_admin) {
                $notif['display_text'] = "From: {$performer_name}";
            } else {
                // Regular user view
                if ($performed_by === $current_user_id) {
                    // User performed the action themselves (received/cancel) - don't show "From: You"
                    $notif['display_text'] = null;
                } else {
                    // Someone else performed the action
                    if ($performer_role === 'Admin') {
                        $notif['display_text'] = "From: Admin";
                    } else {
                        $notif['display_text'] = "From: {$performer_name}";
                    }
                }
            }
        }
    }
    
    return $all_notifications;
}

// UPDATED: Get unread notifications count - FIXED DUPLICATE COUNTING
function getUnreadNotificationsCount($conn, $user_id) {
    $is_admin = isAdmin($conn, $user_id);
    $total_unread = 0;

    if ($is_admin) {
        // Admin counts system notifications
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM deped_inventory_notifications WHERE is_read = FALSE");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_unread += $row['count'];
        $stmt->close();

        // Admin counts DISTINCT request notifications where for_admin = TRUE
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT rl.log_id) as count 
            FROM deped_inventory_request_logs rl
            WHERE rl.is_read = FALSE AND rl.for_admin = TRUE
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_unread += $row['count'];
        $stmt->close();
    } else {
        // Regular users count DISTINCT request notifications where for_emp = TRUE
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT rl.log_id) as count 
            FROM deped_inventory_request_logs rl
            INNER JOIN deped_inventory_request_items ri ON rl.req_item_id = ri.req_item_id
            INNER JOIN deped_inventory_requests r ON ri.request_id = r.request_id
            WHERE r.user_id = ? AND rl.is_read = FALSE AND rl.for_emp = TRUE
        ");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_unread = $row['count'];
        $stmt->close();
    }
    
    return $total_unread;
}

// UPDATED: Get all notifications with pagination
function getAllNotifications($conn, $user_id, $limit = 15, $offset = 0) {
    return getCombinedNotifications($conn, $user_id, $limit, $offset);
}

// UPDATED: Mark notifications as read - FIXED FOR DUAL ENTRIES
function markNotificationsAsRead($conn, $notification_ids, $user_id) {
    if (empty($notification_ids)) return;
    
    $placeholders = implode(',', array_fill(0, count($notification_ids), '?'));
    $types = str_repeat('i', count($notification_ids));
    
    $is_admin = isAdmin($conn, $user_id);
    
    if ($is_admin) {
        // Mark system notifications as read
        $stmt = $conn->prepare("
            UPDATE deped_inventory_notifications 
            SET is_read = TRUE 
            WHERE notification_id IN ($placeholders)
        ");
        $params = $notification_ids;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        
        // Mark request logs as read (for admin) - mark BOTH entries if they exist
        $stmt = $conn->prepare("
            UPDATE deped_inventory_request_logs 
            SET is_read = TRUE 
            WHERE log_id IN ($placeholders)
        ");
        $params = $notification_ids;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    } else {
        // Mark request logs as read (for employee) - mark BOTH entries if they exist
        $stmt = $conn->prepare("
            UPDATE deped_inventory_request_logs 
            SET is_read = TRUE 
            WHERE log_id IN ($placeholders)
        ");
        $params = $notification_ids;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    }
}

// Keep existing helper functions...
function getRequestActionIcon($action_type) {
    $icons = [
        'approve' => 'fas fa-check text-success',
        'decline' => 'fas fa-times text-danger',
        'release' => 'fas fa-box-open text-primary',
        'return' => 'fas fa-undo text-warning',
        'void' => 'fas fa-ban text-secondary',
        'cancel' => 'fas fa-ban text-secondary',
        'received' => 'fas fa-check-circle text-success'
    ];
    return $icons[$action_type] ?? 'fas fa-bell text-info';
}

function getStatusClass($status) {
    $status = strtolower($status);
    $classes = [
        'pending' => 'status-pending',
        'approved' => 'status-approved',
        'declined' => 'status-declined',
        'released' => 'status-released',
        'returned' => 'status-returned',
        'void' => 'status-void',
        'received' => 'status-received',
        'canceled' => 'status-canceled',
        'cancelled' => 'status-canceled'
    ];
    return $classes[$status] ?? '';
}

function isAdmin($conn, $user_id) {
    $stmt = $conn->prepare("SELECT role FROM deped_inventory_users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user['role'] === 'Admin'; 
    }
    
    $stmt->close();
    return false;
}

function getNotificationIcon($action_type, $notification_type = 'system') {
    if ($notification_type === 'request') {
        return getRequestActionIcon($action_type);
    }
    
    $icons = [
        'quantity_added' => 'fas fa-plus-circle',
        'item_created' => 'fas fa-box',
        'item_updated' => 'fas fa-edit',
        'item_deleted' => 'fas fa-trash',
        'low_stock' => 'fas fa-exclamation-triangle',
        'general' => 'fas fa-info-circle'
    ];
    return $icons[$action_type] ?? 'fas fa-bell';
}

function formatNotificationTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M d, Y', $time);
}
?>