<?php
require_once __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../config/security.php';
require_once __DIR__ . '/../../../config/encryption.php';

function getUnreadNotifications($conn, $user_id, $limit = 10) {
    
    $is_admin = isAdmin($conn, $user_id);
    
    if ($is_admin) {
     
        $stmt = $conn->prepare("
            SELECT 
                n.*,
                info.first_name,
                info.middle_name, 
                info.last_name
            FROM deped_inventory_notifications n 
            LEFT JOIN deped_inventory_user_info info ON n.user_id = info.user_id 
            WHERE n.is_read = FALSE 
            ORDER BY n.created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
   
        foreach ($notifications as &$notif) {
            if ($notif['first_name'] && $notif['last_name']) {
                $first = decryptData($notif['first_name'], APP_ENCRYPTION_KEY);
                $middle = $notif['middle_name'] ? decryptData($notif['middle_name'], APP_ENCRYPTION_KEY) : '';
                $last = decryptData($notif['last_name'], APP_ENCRYPTION_KEY);
                
                $fullName = trim($first . ' ' . ($middle ?: '') . ' ' . $last);
                $notif['user_full_name'] = $fullName;
            } else {
                $notif['user_full_name'] = 'Unknown User';
            }
        }
        
        return $notifications;
    } else {
      
        return [];
    }
}

function getAllNotifications($conn, $user_id, $limit = 15) {
    
    $is_admin = isAdmin($conn, $user_id);
    
    if ($is_admin) {
       
        $stmt = $conn->prepare("
            SELECT 
                n.*,
                info.first_name,
                info.middle_name, 
                info.last_name
            FROM deped_inventory_notifications n 
            LEFT JOIN deped_inventory_user_info info ON n.user_id = info.user_id 
            ORDER BY n.created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
   
        foreach ($notifications as &$notif) {
            if ($notif['first_name'] && $notif['last_name']) {
                $first = decryptData($notif['first_name'], APP_ENCRYPTION_KEY);
                $middle = $notif['middle_name'] ? decryptData($notif['middle_name'], APP_ENCRYPTION_KEY) : '';
                $last = decryptData($notif['last_name'], APP_ENCRYPTION_KEY);
                
                $fullName = trim($first . ' ' . ($middle ?: '') . ' ' . $last);
                $notif['user_full_name'] = $fullName;
            } else {
                $notif['user_full_name'] = 'Unknown User';
            }
        }
        
        return $notifications;
    } else {
      
        return [];
    }
}

function getUnreadNotificationsCount($conn, $user_id) {
    // Check if user is admin
    $is_admin = isAdmin($conn, $user_id);
    
    if ($is_admin) {
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM deped_inventory_notifications WHERE is_read = FALSE");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'];
        $stmt->close();
        return $count;
    } else {
      
        return 0;
    }
}

function markNotificationsAsRead($conn, $notification_ids, $user_id) {
    if (empty($notification_ids)) return;
    
    $placeholders = implode(',', array_fill(0, count($notification_ids), '?'));
    $types = str_repeat('i', count($notification_ids));
    

    $is_admin = isAdmin($conn, $user_id);
    
    if ($is_admin) {
       
        $stmt = $conn->prepare("
            UPDATE deped_inventory_notifications 
            SET is_read = TRUE 
            WHERE notification_id IN ($placeholders)
        ");
        $params = $notification_ids;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    }
  
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


function getNotificationIcon($action_type) {
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