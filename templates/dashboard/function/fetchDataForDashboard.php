<?php
require __DIR__ . '/../../../database/dbConnection.php';

// Get user info from session
$userRole = $_SESSION['user']['role'] ?? 'Employee';
$userId = $_SESSION['user']['user_id'] ?? '';

// Fetch common dashboard data
$totalItems = getTotalItems($conn);
$inStockItems = getInStockItems($conn);
$totalEmployees = getTotalEmployees($conn);

// Fetch role-specific data
if ($userRole === 'Admin' || $userRole === 'Logistic_Officer') {
    $lowStockItems = getLowStockItems($conn);
    $pendingRequests = getPendingRequestsCount($conn);
    $lowStockItemsDetails = getLowStockItemsDetails($conn);
    $recentActivity = getRecentActivity($conn);
} else {
    // Employee data
    $lowStockItems = getLowStockItems($conn);
    $lowStockItemsDetails = getLowStockItemsDetails($conn);
    $myPendingRequests = getMyPendingRequestItemsCount($conn, $userId);
    $myBorrowedItems = getMyBorrowedItemsCount($conn, $userId);
    $requestStats = getRequestStats($conn, $userId);
    $popularCategories = getPopularCategories($conn);
    $myTimeline = getMyRequestTimeline($conn, $userId);
}

// Common Statistical Functions
function getTotalItems($conn) {
    $sql = "SELECT COUNT(*) as total FROM deped_inventory_items";
    $result = $conn->query($sql);
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getInStockItems($conn) {
    $sql = "SELECT SUM(available_quantity) as total FROM deped_inventory_items";
    $result = $conn->query($sql);
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getLowStockItems($conn) {
    $sql = "SELECT COUNT(*) as total FROM deped_inventory_items 
            WHERE (available_quantity/total_quantity) <= 0.3";
    $result = $conn->query($sql);
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getTotalEmployees($conn) {
    $sql = "SELECT COUNT(*) as total FROM deped_inventory_users WHERE role = 'Employee'";
    $result = $conn->query($sql);
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getPendingRequestsCount($conn) {
    $sql = "SELECT COUNT(*) as total FROM deped_inventory_request_items WHERE approval_status = 'Pending'";
    $result = $conn->query($sql);
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getLowStockItemsDetails($conn) {
    $sql = "SELECT item_id, item_name, available_quantity, total_quantity,
                   ROUND((available_quantity/total_quantity) * 100, 2) as stock_percentage
            FROM deped_inventory_items 
            WHERE (available_quantity/total_quantity) <= 0.3
            ORDER BY stock_percentage ASC";
    $result = $conn->query($sql);
    $items = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    return $items;
}

function getRecentActivity($conn) {
    $sql = "SELECT rl.message, rl.created_at, ui.first_name, ui.last_name 
            FROM deped_inventory_request_logs rl 
            LEFT JOIN deped_inventory_user_info ui ON rl.performed_by = ui.user_id 
            ORDER BY rl.created_at DESC 
            LIMIT 8";
    $result = $conn->query($sql);
    $activities = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = [
                'message' => $row['message'],
                'created_at' => $row['created_at'],
                'user_name' => 'User'
            ];
        }
    }
    return $activities;
}

// Employee-specific Functions
function getMyPendingRequestItemsCount($conn, $userId) {
    $sql = "SELECT COUNT(*) as total FROM deped_inventory_request_items ri
            JOIN deped_inventory_requests r ON ri.request_id = r.request_id
            WHERE r.user_id = ? AND ri.approval_status = 'Pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getMyBorrowedItemsCount($conn, $userId) {
    $sql = "SELECT COUNT(*) as total FROM deped_inventory_request_items ri
            JOIN deped_inventory_requests r ON ri.request_id = r.request_id
            WHERE r.user_id = ? AND ri.approval_status = 'Received'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc()['total'] : 0;
}

function getRequestStats($conn, $userId) {
    $sql = "SELECT approval_status, COUNT(*) as count 
            FROM deped_inventory_request_items ri
            JOIN deped_inventory_requests r ON ri.request_id = r.request_id
            WHERE r.user_id = ?
            GROUP BY approval_status";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats[$row['approval_status']] = $row['count'];
        }
    }
    return $stats;
}

function getPopularCategories($conn) {
    $sql = "SELECT c.category_name, COUNT(ri.item_id) as request_count
            FROM deped_inventory_request_items ri
            JOIN deped_inventory_items i ON ri.item_id = i.item_id
            JOIN deped_inventory_item_category c ON i.category_id = c.category_id
            GROUP BY c.category_name
            ORDER BY request_count DESC
            LIMIT 6";
    $result = $conn->query($sql);
    $categories = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

function getMyRequestTimeline($conn, $userId) {
    $sql = "SELECT r.request_id, r.created_at, ri.approval_status, i.item_name
            FROM deped_inventory_requests r
            JOIN deped_inventory_request_items ri ON r.request_id = ri.request_id
            JOIN deped_inventory_items i ON ri.item_id = i.item_id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT 6";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $timeline = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $timeline[] = $row;
        }
    }
    return $timeline;
}
?>