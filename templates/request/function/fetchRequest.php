<?php
require __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../config/security.php';
require_once __DIR__ . '/../../../config/encryption.php';

$requests = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Initialize status counts for ITEMS (not requests)
$statusCounts = [
    'pending' => 0,
    'approved' => 0,
    'declined' => 0,
    'released' => 0,
    'returned' => 0,
    'void' => 0
];

$requestQuery = "
    SELECT 
        r.request_id,
        r.status,
        r.created_at,
        ui.first_name,
        ui.middle_name,
        ui.last_name,
        pos.position_title,
        off.office_name
    FROM deped_inventory_requests r
    LEFT JOIN deped_inventory_user_info ui ON r.user_id = ui.user_id
    LEFT JOIN deped_inventory_employee_position pos ON ui.position_id = pos.position_id
    LEFT JOIN deped_inventory_employee_office off ON ui.office_id = off.office_id
    WHERE r.user_id = ?
";

$params = [$_SESSION['user']['user_id']];
$types = 's';

$requestQuery .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($requestQuery);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $requestId = $row['request_id'];
            
            $itemsQuery = "
                SELECT 
                    ri.req_item_id,  -- ← THIS IS CRITICAL
                    i.item_id,
                    i.item_name,
                    i.description,
                    i.brand,
                    i.model,
                    i.serial_number,
                    c.category_name,
                    ri.requested_quantity,
                    ri.date_needed,
                    ri.purpose,
                    ri.approval_status as item_status
                FROM deped_inventory_request_items ri
                LEFT JOIN deped_inventory_items i ON ri.item_id = i.item_id
                LEFT JOIN deped_inventory_item_category c ON i.category_id = c.category_id
                WHERE ri.request_id = ?
                ORDER BY ri.req_item_id ASC
            ";
            
            $itemsStmt = $conn->prepare($itemsQuery);
            $itemsStmt->bind_param('i', $requestId);
            $itemsStmt->execute();
            $itemsResult = $itemsStmt->get_result();
            
            $items = [];
            $items_count = 0;
            $sample_items = [];
            $earliest_date_needed = null;
            
            // Count items by status for THIS request
            $itemStatusCounts = [
                'pending' => 0,
                'approved' => 0,
                'declined' => 0,
                'released' => 0,
                'returned' => 0,
                'void' => 0
            ];
            
            while ($itemRow = $itemsResult->fetch_assoc()) {
                $items[] = [
                    'req_item_id' => $itemRow['req_item_id'],  // ← Include this
                    'item_id' => $itemRow['item_id'],
                    'item_name' => $itemRow['item_name'],
                    'description' => $itemRow['description'],
                    'brand' => $itemRow['brand'],
                    'model' => $itemRow['model'],
                    'serial_number' => $itemRow['serial_number'],
                    'category_name' => $itemRow['category_name'],
                    'requested_quantity' => (int)$itemRow['requested_quantity'],
                    'date_needed' => $itemRow['date_needed'],
                    'purpose' => $itemRow['purpose'],
                    'status' => $itemRow['item_status']
                ];
                
                $items_count++;
                $sample_items[] = $itemRow['item_name'];
                
                // Count item status for THIS request
                $itemStatus = strtolower($itemRow['item_status']);
                if (isset($itemStatusCounts[$itemStatus])) {
                    $itemStatusCounts[$itemStatus]++;
                }
                
                // Count item status for GLOBAL filter counts
                if (isset($statusCounts[$itemStatus])) {
                    $statusCounts[$itemStatus]++;
                }
                
                if ($itemRow['date_needed'] && (!$earliest_date_needed || $itemRow['date_needed'] < $earliest_date_needed)) {
                    $earliest_date_needed = $itemRow['date_needed'];
                }
            }
            $itemsStmt->close();
            
            $requests[] = [
                'request_id' => $requestId,
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'requester_name' => trim(
                    decryptData($row['first_name'], APP_ENCRYPTION_KEY) . ' ' .
                    (!empty($row['middle_name']) ? decryptData($row['middle_name'], APP_ENCRYPTION_KEY) . ' ' : '') .
                    decryptData($row['last_name'], APP_ENCRYPTION_KEY)
                ),
                'position_title' => $row['position_title'] ?? '',
                'office_name' => $row['office_name'] ?? '',
                'items_count' => $items_count,
                'sample_items' => implode(', ', $sample_items),
                'earliest_date_needed' => $earliest_date_needed,
                'items' => $items,
                'item_status_counts' => $itemStatusCounts
            ];
        }
    }
    $stmt->close();
}
?>