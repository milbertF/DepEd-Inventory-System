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

// 6-digit log ID generation function
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
        
        // Remove void from valid actions
        $validActions = ['approve', 'decline', 'release', 'return', 'cancel', 'received'];
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
                i.available_quantity as current_available_quantity,
                i.total_quantity as current_quantity
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
        $currentInitialQuantity = $itemData['current_available_quantity'];
        $currentQuantity = $itemData['current_quantity'];
        
        $checkStmt->close();
        
        $processedRequestId = $requestId;
        
        // Remove void from valid transitions
        $validTransitions = [
            'pending' => ['approve', 'decline', 'cancel'],
            'approved' => ['release', 'decline', 'cancel'],
            'released' => ['return', 'received', 'cancel'],
            'returned' => ['release', 'cancel'],
            'declined' => ['approve', 'cancel'],
            'received' => ['return'],
            'canceled' => [] // No actions for canceled items
        ];
        
        if (!isset($validTransitions[$currentStatus]) || !in_array($actionType, $validTransitions[$currentStatus])) {
            $errors[] = "Invalid status transition from {$currentStatus} to {$actionType} for req_item_id {$reqItemId}";
            continue;
        }
        
        // Remove void from status map
        $statusMap = [
            'approve' => 'Approved',
            'decline' => 'Declined', 
            'release' => 'Released',
            'return' => 'Returned',
            'cancel' => 'Canceled',
            'received' => 'Received'
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
            
            $logId = generateLogID($conn);
            $logMessage = getLogMessage($actionType, $itemName, $currentStatus, $newStatus, $requestedQty);
            logAction($conn, $logId, $reqItemId, $requestId, $itemId, $itemName, $requesterUserId, $actionType, $logMessage, $currentUserId);
            
            // Handle inventory quantity changes using available_quantity
            if ($actionType === 'release') {
                // Subtract the requested quantity from available_quantity (borrowable quantity)
                $deductStmt = $conn->prepare("
                    UPDATE deped_inventory_items 
                    SET available_quantity = available_quantity - ? 
                    WHERE item_id = ? AND available_quantity >= ?
                ");
                
                if ($deductStmt) {
                    $deductStmt->bind_param('iii', $requestedQty, $itemId, $requestedQty);
                    if ($deductStmt->execute()) {
                        if ($deductStmt->affected_rows === 0) {
                            $errors[] = "Insufficient quantity for item '{$itemName}'. Available: {$currentInitialQuantity}, Requested: {$requestedQty}";
                            $successCount--; 
                        }
                    } else {
                        $errors[] = "Failed to deduct quantity for item '{$itemName}': " . $deductStmt->error;
                        $successCount--; 
                    }
                    $deductStmt->close();
                }
            } elseif ($actionType === 'return') {
                // Add the requested quantity back to available_quantity (borrowable quantity)
                $restoreStmt = $conn->prepare("
                    UPDATE deped_inventory_items 
                    SET available_quantity = available_quantity + ? 
                    WHERE item_id = ?
                ");
                
                if ($restoreStmt) {
                    $restoreStmt->bind_param('ii', $requestedQty, $itemId);
                    if ($restoreStmt->execute()) {
                        // Ensure quantity is never lower than available_quantity
                        $syncStmt = $conn->prepare("
                            UPDATE deped_inventory_items 
                            SET total_quantity = GREATEST(total_quantity, available_quantity) 
                            WHERE item_id = ? AND total_quantity < available_quantity
                        ");
                        
                        if ($syncStmt) {
                            $syncStmt->bind_param('i', $itemId);
                            $syncStmt->execute();
                        
                            if ($syncStmt->affected_rows > 0) {
                                $logMessage .= " (Quantity auto-adjusted to match available_quantity)";
                            
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
                        $successCount--; 
                    }
                    $restoreStmt->close();
                }
            } elseif ($actionType === 'cancel' && $currentStatus === 'released') {
                // Handle cancel action when item was previously released - restore the quantity
                $restoreStmt = $conn->prepare("
                    UPDATE deped_inventory_items 
                    SET available_quantity = available_quantity + ? 
                    WHERE item_id = ?
                ");
                
                if ($restoreStmt) {
                    $restoreStmt->bind_param('ii', $requestedQty, $itemId);
                    if ($restoreStmt->execute()) {
                        // Ensure quantity is never lower than available_quantity
                        $syncStmt = $conn->prepare("
                            UPDATE deped_inventory_items 
                            SET total_quantity = GREATEST(total_quantity, available_quantity) 
                            WHERE item_id = ? AND total_quantity < available_quantity
                        ");
                        
                        if ($syncStmt) {
                            $syncStmt->bind_param('i', $itemId);
                            $syncStmt->execute();
                            
                            if ($syncStmt->affected_rows > 0) {
                                $logMessage .= " (Quantity auto-adjusted to match available_quantity)";
                                
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
                        
                        // Update the log message to reflect that quantity was restored
                        $logMessage .= " - Quantity restored: {$requestedQty}";
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
                    } else {
                        $errors[] = "Failed to restore quantity for canceled item '{$itemName}': " . $restoreStmt->error;
                        $successCount--; 
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
            
            // Remove Void from overall status calculation
            if (isset($statusCounts['Canceled']) && $statusCounts['Canceled'] > 0) {
                $overallStatus = 'Canceled';
            } elseif (isset($statusCounts['Declined']) && $statusCounts['Declined'] > 0) {
                $overallStatus = 'Declined';
            } elseif (isset($statusCounts['Received']) && $statusCounts['Received'] === $totalItems) {
                $overallStatus = 'Received';
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

// Remove void from log messages
function getLogMessage($actionType, $itemName, $oldStatus, $newStatus, $quantity) {
    $actionMessages = [
        'approve' => "Item '{$itemName}' was approved. Status changed from {$oldStatus} to {$newStatus}",
        'decline' => "Item '{$itemName}' was declined. Status changed from {$oldStatus} to {$newStatus}",
        'release' => "Item '{$itemName}' was released to requester. Status changed from {$oldStatus} to {$newStatus}. Quantity: {$quantity}",
        'return' => "Item '{$itemName}' was returned. Status changed from {$oldStatus} to {$newStatus}. Quantity: {$quantity}",
        'cancel' => "Item '{$itemName}' was canceled. Status changed from {$oldStatus} to {$newStatus}",
        'received' => "Item '{$itemName}' was marked as received. Status changed from {$oldStatus} to {$newStatus}. Quantity: {$quantity}"
    ];
    
    return $actionMessages[$actionType] ?? "Action '{$actionType}' performed on item '{$itemName}'. Status: {$oldStatus} → {$newStatus}";
}
function logAction($conn, $logId, $reqItemId, $requestId, $itemId, $itemName, $userId, $actionType, $message, $performedBy) {
    

    $logStmt1 = $conn->prepare("
        INSERT INTO deped_inventory_request_logs 
        (log_id, req_item_id, request_id, item_id, item_name, user_id, action_type, message, performed_by, created_at, for_admin, for_emp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), FALSE, TRUE)
    ");
    
    if ($logStmt1) {
        $logStmt1->bind_param('siiisssss', $logId, $reqItemId, $requestId, $itemId, $itemName, $userId, $actionType, $message, $performedBy);
        $logStmt1->execute();
        $logStmt1->close();
    }
    
 
    $logId2 = generateLogID($conn);
    

    $logStmt2 = $conn->prepare("
        INSERT INTO deped_inventory_request_logs 
        (log_id, req_item_id, request_id, item_id, item_name, user_id, action_type, message, performed_by, created_at, for_admin, for_emp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), TRUE, FALSE)
    ");
    
    if ($logStmt2) {
        $logStmt2->bind_param('siiisssss', $logId2, $reqItemId, $requestId, $itemId, $itemName, $userId, $actionType, $message, $performedBy);
        $logStmt2->execute();
        $logStmt2->close();
    }
}
?>