<?php
require_once __DIR__ . '/../../../config/restrictRoles.php';

restrictRoles(['Employee']);

require  __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/fetchRequest.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCSI-Request</title>
    <link rel="stylesheet" href="/styles/request.css">
    <link rel="stylesheet" href="/styles/requestTable.css">
    <link rel="stylesheet" href="/styles/viewRequestItemModal.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>



<body>
    <div class="wrapMain">
        <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
        <div class="con">
            <?php require __DIR__ . '/../../header/html/pageHeader.php'; ?>
            <?php require __DIR__ . '/../../quick-access/access.php'; ?>
            <?php require __DIR__ . '/viewRequestItemModal.php'; ?>

            <div class="tableContainer">
            <div class="searchContainer">
                    <input type="text" id="searchRequest" placeholder="Search requests..." />
                </div>

                <div class="status-filter-container">
                   
                    <div class="status-filter-checkbox">
                        <input type="checkbox" id="filter-pending" class="status-checkbox" data-status="pending" checked>
                        <label for="filter-pending">
                            Pending
                            <span class="status-count">(<?= $statusCounts['pending'] ?>)</span>
                        </label>
                    </div>
                    <div class="status-filter-checkbox">
                        <input type="checkbox" id="filter-approved" class="status-checkbox" data-status="approved">
                        <label for="filter-approved">
                            Approved
                            <span class="status-count">(<?= $statusCounts['approved'] ?>)</span>
                        </label>
                    </div>
                    <div class="status-filter-checkbox">
                        <input type="checkbox" id="filter-declined" class="status-checkbox" data-status="declined">
                        <label for="filter-declined">
                            Declined
                            <span class="status-count">(<?= $statusCounts['declined'] ?>)</span>
                        </label>
                    </div>
                    <div class="status-filter-checkbox">
                        <input type="checkbox" id="filter-released" class="status-checkbox" data-status="released">
                        <label for="filter-released">
                            Released
                            <span class="status-count">(<?= $statusCounts['released'] ?>)</span>
                        </label>
                    </div>
                    <div class="status-filter-checkbox">
                        <input type="checkbox" id="filter-returned" class="status-checkbox" data-status="returned">
                        <label for="filter-returned">
                            Returned
                            <span class="status-count">(<?= $statusCounts['returned'] ?>)</span>
                        </label>
                    </div>
                    <div class="status-filter-checkbox">
                        <input type="checkbox" id="filter-void" class="status-checkbox" data-status="void">
                        <label for="filter-void">
                         Void
                            <span class="status-count">(<?= $statusCounts['void'] ?>)</span>
                        </label>
                    </div>
                    </div>

                  

                <table class="requestTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Request ID</th>
                            <th>Requested By</th>
                            <th>Office</th>
                            <th>Items</th>
                            <th>Date Needed</th>
                            <th>Date Requested</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $index => $request): ?>
                                <tr data-status="<?= strtolower($request['status']) ?>" 
                                    data-request='<?= htmlspecialchars(json_encode([
                                        'request_id' => $request['request_id'],
                                        'status' => $request['status'],
                                        'requester_name' => $request['requester_name'],
                                        'position_title' => $request['position_title'],
                                        'office_name' => $request['office_name'],
                                        'created_at' => $request['created_at'],
                                        'items' => $request['items']
                                    ]), ENT_QUOTES, 'UTF-8') ?>'>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($request['request_id']) ?></td>
                                    <td>
                                        <div class="requester-info">
                                            <div class="requester-name">
                                                <?= htmlspecialchars($request['requester_name'] ?? 'N/A') ?>
                                            </div>
                                            <div class="requester-position">
                                                <?= htmlspecialchars($request['position_title'] ?? '') ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($request['office_name'] ?? 'N/A') ?></td>
                                   
                                    <td>
                                        <div class="items-info">
                                            <span class="items-count"><?= htmlspecialchars($request['items_count'] ?? 0) ?> item(s)</span>
                                            <?php if (!empty($request['sample_items'])): ?>
                                                <div class="sample-items">
                                                    <?= htmlspecialchars($request['sample_items']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($request['earliest_date_needed'])): ?>
                                            <?= date("M-d-Y", strtotime($request['earliest_date_needed'])) ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?= isset($request['created_at']) ? date("M-d-Y", strtotime($request['created_at'])) : 'N/A' ?></td>
                                    <td>
    <div class="status-breakdown">
        <?php
        $statusCounts = $request['item_status_counts'];
        $nonZeroStatuses = array_filter($statusCounts);
        $statusCount = count($nonZeroStatuses);
        
        if ($statusCount > 1): 
        ?>
        
            <div class="status-badges-grid">
                <?php foreach ($statusCounts as $status => $count): ?>
                    <?php if ($count > 0): ?>
                        <span class="status-badge status-<?= $status ?>">
                            <?= $count ?> <?= ucfirst($status) ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            
            <?php 
            $mainStatus = '';
            $mainCount = 0;
            foreach ($statusCounts as $status => $count) {
                if ($count > 0) {
                    $mainStatus = $status;
                    $mainCount = $count;
                    break;
                }
            }
            ?>
            <span class="status-badge status-<?= $mainStatus ?>">
                <?= ucfirst($mainStatus) ?> (<?= $mainCount ?>)
            </span>
        <?php endif; ?>
    </div>
</td>
                                   
                                    <td>
                                        <button class="action-btn view" 
                                                data-request-id="<?= $request['request_id'] ?>" 
                                                title="View Request">
                                            <i class="fas fa-eye"></i>
                                            <span class="tooltip">View Request</span>
                                        </button>

                                       
                                  
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">No requests found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="/javascript/header.js"></script>
    <script src="/javascript/sidebar.js"></script>
    <script src="/javascript/script.js"></script>
    <script src="/javascript/request.js"></script>
</body>
</html>