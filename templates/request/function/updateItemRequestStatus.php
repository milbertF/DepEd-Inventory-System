<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../database/dbConnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['actions']) || !is_array($input['actions']) || empty($input['actions'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No actions provided']);
    exit;
}

// ADDED: 6-digit log ID generation function
function generateLogID($conn) {
    do {
        $id = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $check = $conn->prepare("SELECT log_id FROM deped_inventory_request_logs WHERE log_id = ?");
        $check->bind_param("s", $id);
        $check->execute();
        $check->store_result();
    } while ($check->num_rows > 0);
    return $id;
}

try {
    
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
    }

    $conn->begin_transaction();
    
    $successCount = 0;
    $errors = [];
    $processedRequestId = null;
    
    foreach ($input['actions'] as $index => $action) {

        if (!isset($action['req_item_id'], $action['action'])) {
            $errors[] = "Invalid action format for action {$index}: missing req_item_id or action";
            continue;
        }
        
        $reqItemId = $action['req_item_id'];
        $actionType = $action['action'];
        $currentUserId = $_SESSION['user']['user_id'] ?? 'system';
        
        $validActions = ['approve', 'decline', 'release', 'return', 'void'];
        if (!in_array($actionType, $validActions)) {
            $errors[] = "Invalid action type: {$actionType} for req_item_id {$reqItemId}";
            continue;
        }
        
        $checkStmt = $conn->prepare("
            SELECT 
                ri.approval_status, 
                ri.request_id,
                ri.item_id,
                ri.requested_quantity,
                i.item_name,
                r.user_id,
                i.initial_quantity as current_initial_quantity,
                i.quantity as current_quantity
            FROM deped_inventory_request_items ri
            LEFT JOIN deped_inventory_items i ON ri.item_id = i.item_id
            LEFT JOIN deped_inventory_requests r ON ri.request_id = r.request_id
            WHERE ri.req_item_id = ?
        ");
        
        if (!$checkStmt) {
            $errors[] = "Prepare failed: " . $conn->error;
            continue;
        }
        
        $checkStmt->bind_param('i', $reqItemId);
        
        if (!$checkStmt->execute()) {
            $errors[] = "Execute failed: " . $checkStmt->error;
            $checkStmt->close();
            continue;
        }
        
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $errors[] = "Request item not found: {$reqItemId}";
            $checkStmt->close();
            continue;
        }
        
        $itemData = $checkResult->fetch_assoc();
        $currentStatus = strtolower($itemData['approval_status']);
        $requestId = $itemData['request_id'];
        $itemId = $itemData['item_id'];
        $itemName = $itemData['item_name'];
        $requestedQty = $itemData['requested_quantity'];
        $requesterUserId = $itemData['user_id'];
        $currentInitialQuantity = $itemData['current_initial_quantity'];
        $currentQuantity = $itemData['current_quantity'];
        
        $checkStmt->close();
        
        $processedRequestId = $requestId;
        
        $validTransitions = [
            'pending' => ['approve', 'decline'],
            'approved' => ['release', 'decline'],
            'released' => ['return', 'void'],
            'returned' => ['release', 'void'],
            'declined' => ['approve'],
            'void' => ['approve']
        ];
        
        if (!isset($validTransitions[$currentStatus]) || !in_array($actionType, $validTransitions[$currentStatus])) {
            $errors[] = "Invalid status transition from {$currentStatus} to {$actionType} for req_item_id {$reqItemId}";
            continue;
        }
        
        $statusMap = [
            'approve' => 'Approved',
            'decline' => 'Declined', 
            'release' => 'Released',
            'return' => 'Returned',
            'void' => 'Void'
        ];
        
        $newStatus = $statusMap[$actionType] ?? 'Pending';
        
        $updateStmt = $conn->prepare("
            UPDATE deped_inventory_request_items 
            SET approval_status = ? 
            WHERE req_item_id = ?
        ");
        
        if (!$updateStmt) {
            $errors[] = "Update prepare failed: " . $conn->error;
            continue;
        }
        
        $updateStmt->bind_param('si', $newStatus, $reqItemId);
        
        if ($updateStmt->execute()) {
            $successCount++;
            
            // ADDED: Generate 6-digit log ID and log the action
            $logId = generateLogID($conn);
            $logMessage = getLogMessage($actionType, $itemName, $currentStatus, $newStatus, $requestedQty);
            logAction($conn, $logId, $reqItemId, $requestId, $itemId, $itemName, $requesterUserId, $actionType, $logMessage, $currentUserId);
            
            // UPDATED: Handle inventory quantity changes using initial_quantity
            if ($actionType === 'release') {
                // Subtract the requested quantity from initial_quantity (borrowable quantity)
                $deductStmt = $conn->prepare("
                    UPDATE deped_inventory_items 
                    SET initial_quantity = initial_quantity - ? 
                    WHERE item_id = ? AND initial_quantity >= ?
                ");
                
                if ($deductStmt) {
                    $deductStmt->bind_param('iii', $requestedQty, $itemId, $requestedQty);
                    if ($deductStmt->execute()) {
                        if ($deductStmt->affected_rows === 0) {
                            $errors[] = "Insufficient quantity for item '{$itemName}'. Available: {$currentInitialQuantity}, Requested: {$requestedQty}";
                            $successCount--; // Decrement success count since inventory update failed
                        }
                    } else {
                        $errors[] = "Failed to deduct quantity for item '{$itemName}': " . $deductStmt->error;
                        $successCount--; // Decrement success count since inventory update failed
                    }
                    $deductStmt->close();
                }
            } elseif ($actionType === 'return') {
                // Add the requested quantity back to initial_quantity (borrowable quantity)
                $restoreStmt = $conn->prepare("
                    UPDATE deped_inventory_items 
                    SET initial_quantity = initial_quantity + ? 
                    WHERE item_id = ?
                ");
                
                if ($restoreStmt) {
                    $restoreStmt->bind_param('ii', $requestedQty, $itemId);
                    if ($restoreStmt->execute()) {
                        // NEW: Ensure quantity is never lower than initial_quantity
                        $syncStmt = $conn->prepare("
                            UPDATE deped_inventory_items 
                            SET quantity = GREATEST(quantity, initial_quantity) 
                            WHERE item_id = ? AND quantity < initial_quantity
                        ");
                        
                        if ($syncStmt) {
                            $syncStmt->bind_param('i', $itemId);
                            $syncStmt->execute();
                            
                            // Check if quantity was adjusted
                            if ($syncStmt->affected_rows > 0) {
                                $logMessage .= " (Quantity auto-adjusted to match initial_quantity)";
                                
                                // Update the log with the corrected message
                                $updateLogStmt = $conn->prepare("
                                    UPDATE deped_inventory_request_logs 
                                    SET message = ? 
                                    WHERE log_id = ?
                                ");
                                
                                if ($updateLogStmt) {
                                    $updateLogStmt->bind_param('ss', $logMessage, $logId);
                                    $updateLogStmt->execute();
                                    $updateLogStmt->close();
                                }
                            }
                            $syncStmt->close();
                        }
                    } else {
                        $errors[] = "Failed to restore quantity for item '{$itemName}': " . $restoreStmt->error;
                        $successCount--; // Decrement success count since inventory update failed
                    }
                    $restoreStmt->close();
                }
            }
            
        } else {
            $errors[] = "Failed to update req_item_id {$reqItemId}: " . $updateStmt->error;
        }
        
        $updateStmt->close();
    }
    
    if ($successCount > 0 && $processedRequestId) {
        $statusStmt = $conn->prepare("
            SELECT approval_status, COUNT(*) as count 
            FROM deped_inventory_request_items 
            WHERE request_id = ? 
            GROUP BY approval_status
        ");
        
        if ($statusStmt) {
            $statusStmt->bind_param('i', $processedRequestId);
            $statusStmt->execute();
            $statusResult = $statusStmt->get_result();
            
            $statusCounts = [];
            while ($row = $statusResult->fetch_assoc()) {
                $statusCounts[$row['approval_status']] = $row['count'];
            }
            
            $statusStmt->close();
            
            $totalItems = array_sum($statusCounts);
            $overallStatus = 'Pending';
            
            if (isset($statusCounts['Declined']) && $statusCounts['Declined'] > 0) {
                $overallStatus = 'Declined';
            } elseif (isset($statusCounts['Released']) && $statusCounts['Released'] === $totalItems) {
                $overallStatus = 'Released';
            } elseif (isset($statusCounts['Approved']) && $statusCounts['Approved'] === $totalItems) {
                $overallStatus = 'Approved';
            } elseif (isset($statusCounts['Returned']) && $statusCounts['Returned'] === $totalItems) {
                $overallStatus = 'Returned';
            }
            
            $updateReqStmt = $conn->prepare("
                UPDATE deped_inventory_requests 
                SET status = ? 
                WHERE request_id = ?
            ");
            
            if ($updateReqStmt) {
                $updateReqStmt->bind_param('si', $overallStatus, $processedRequestId);
                $updateReqStmt->execute();
                $updateReqStmt->close();
            }
        }
    }
    
    if (!empty($errors)) {
        $conn->rollback();
        http_response_code(207);
        echo json_encode([
            'success' => false, 
            'message' => 'Some actions failed',
            'processed' => $successCount,
            'failed' => count($errors),
            'errors' => $errors
        ]);
    } else {
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => "Successfully processed {$successCount} action(s)",
            'processed' => $successCount
        ]);
    }
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// ADDED: Logging functions
function getLogMessage($actionType, $itemName, $oldStatus, $newStatus, $quantity) {
    $actionMessages = [
        'approve' => "Item '{$itemName}' was approved. Status changed from {$oldStatus} to {$newStatus}",
        'decline' => "Item '{$itemName}' was declined. Status changed from {$oldStatus} to {$newStatus}",
        'release' => "Item '{$itemName}' was released to requester. Status changed from {$oldStatus} to {$newStatus}. Quantity: {$quantity}",
        'return' => "Item '{$itemName}' was returned. Status changed from {$oldStatus} to {$newStatus}. Quantity: {$quantity}",
        'void' => "Item '{$itemName}' was voided. Status changed from {$oldStatus} to {$newStatus}"
    ];
    
    return $actionMessages[$actionType] ?? "Action '{$actionType}' performed on item '{$itemName}'. Status: {$oldStatus} → {$newStatus}";
}

function logAction($conn, $logId, $reqItemId, $requestId, $itemId, $itemName, $userId, $actionType, $message, $performedBy) {
    $logStmt = $conn->prepare("
        INSERT INTO deped_inventory_request_logs 
        (log_id, req_item_id, request_id, item_id, item_name, user_id, action_type, message, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if ($logStmt) {
        $logStmt->bind_param('siiissss', $logId, $reqItemId, $requestId, $itemId, $itemName, $userId, $actionType, $message);
        $logStmt->execute();
        $logStmt->close();
    }
}
?>