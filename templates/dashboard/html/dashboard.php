<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/fetchDataForDashboard.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BCSI-Dashboard</title>
  <link rel="stylesheet" href="/styles/dashboard.css">
</head>

<body>

  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
    <div class="con">
      <?php require __DIR__ . '/../../header/html/pageHeader.php'; ?>
      <?php require __DIR__ . '/../../quick-access/access.php'; ?>

      <!-- Summary Cards - Different for each role -->
      <div class="summaryCards">
        <?php if ($_SESSION['user']['role'] === 'Admin' || $_SESSION['user']['role'] === 'Logistic_Officer'): ?>
          <!-- Admin/Logistic Officer Dashboard -->
          <div class="card">
            <h4>Total Items</h4>
            <p><?php echo number_format($totalItems); ?></p>
          </div>
          <div class="card">
            <h4>In Stock</h4>
            <p><?php echo number_format($inStockItems); ?></p>
          </div>
          <div class="card">
            <h4>Employees</h4>
            <p><?php echo number_format($totalEmployees); ?></p>
          </div>
          <div class="card">
            <h4>Low Stock</h4>
            <p><?php echo number_format($lowStockItems); ?></p>
          </div>
          <div class="card">
            <h4>Pending Requests</h4>
            <p><?php echo number_format($pendingRequests); ?></p>
          </div>
        <?php else: ?>
          <!-- Employee Dashboard -->
          <div class="card">
            <h4>Total Items</h4>
            <p><?php echo number_format($totalItems); ?></p>
          </div>
          <div class="card">
            <h4>In Stock</h4>
            <p><?php echo number_format($inStockItems); ?></p>
          </div>
          <div class="card">
            <h4>Employees</h4>
            <p><?php echo number_format($totalEmployees); ?></p>
          </div>
          <div class="card">
            <h4>My Pending</h4>
            <p><?php echo number_format($myPendingRequests); ?></p>
          </div>
          <div class="card">
            <h4>Borrowed Items</h4>
            <p><?php echo number_format($myBorrowedItems); ?></p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Low Stock Table (For both Admin and Employee) -->
      <?php if (!empty($lowStockItemsDetails)): ?>
        <div class="section">
          <h4>Low Stock Items (Below 30% Stock)</h4>
          <div class="table-container">
            <table class="dashboardTable">
              <thead>
                <tr>
                  <th>Item Name</th>
                  <th>Available</th>
                  <th>Total</th>
                  <th>Stock Level</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lowStockItemsDetails as $item): ?>
                  <?php 
                  $percentage = ($item['available_quantity'] / $item['total_quantity']) * 100;
                  $statusClass = $percentage <= 10 ? 'danger' : ($percentage <= 30 ? 'warning' : 'info');
                  $statusText = $percentage <= 10 ? 'Critical' : ($percentage <= 30 ? 'Low' : 'Moderate');
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['available_quantity']); ?></td>
                    <td><?php echo htmlspecialchars($item['total_quantity']); ?></td>
                    <td><?php echo number_format($percentage, 1); ?>%</td>
                    <td>
                      <span class="tag <?php echo $statusClass; ?>">
                        <?php echo $statusText; ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php else: ?>
        <div class="section">
          <h4>Low Stock Items</h4>
          <div class="no-data-message">
            No low stock items found. All items are well-stocked!
          </div>
        </div>
      <?php endif; ?>

      <!-- Recent Activity (Only for Admin/Logistic Officer) -->
      <?php if (($_SESSION['user']['role'] === 'Admin' || $_SESSION['user']['role'] === 'Logistic_Officer') && !empty($recentActivity)): ?>
        <div class="section">
          <h4>Recent Activity</h4>
          <ul class="activityLog">
            <?php foreach ($recentActivity as $activity): ?>
              <li>
                <strong><?php echo date('M d', strtotime($activity['created_at'])); ?>:</strong>
                <?php echo htmlspecialchars($activity['message']); ?>
                <?php if (!empty($activity['user_name'])): ?>
                  <em>(by <?php echo htmlspecialchars($activity['user_name']); ?>)</em>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Employee Additional Sections -->
      <?php if ($_SESSION['user']['role'] === 'Employee'): ?>
        <div class="dashboard-grid">
          <!-- My Request Statistics -->
          <div class="section">
            <h4>My Request Statistics</h4>
            <div class="stats-grid">
              <?php
              $statuses = [
                'Pending' => $requestStats['Pending'] ?? 0,
                'Approved' => $requestStats['Approved'] ?? 0,
                'Released' => $requestStats['Released'] ?? 0,
                'Received' => $requestStats['Received'] ?? 0,
                'Returned' => $requestStats['Returned'] ?? 0,
                'Declined' => $requestStats['Declined'] ?? 0
              ];
              ?>
              <?php foreach ($statuses as $status => $count): ?>
                <div class="stat-item">
                  <div class="stat-label"><?php echo $status; ?></div>
                  <div class="stat-value"><?php echo $count; ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Popular Categories -->
          <?php if (!empty($popularCategories)): ?>
            <div class="section">
              <h4>Popular Categories</h4>
              <div class="categories-list">
                <?php foreach ($popularCategories as $category): ?>
                  <div class="category-item">
                    <span class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></span>
                    <span class="request-count"><?php echo $category['request_count']; ?> requests</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- My Recent Items -->
        <?php if (!empty($myTimeline)): ?>
          <div class="section">
            <h4>My Recent Items</h4>
            <div class="timeline">
              <?php foreach ($myTimeline as $item): ?>
                <div class="timeline-item">
                  <div class="timeline-date"><?php echo date('M d', strtotime($item['created_at'])); ?></div>
                  <div class="timeline-content">
                    <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                    <div class="item-status">
                      <span class="tag status-<?php echo strtolower($item['approval_status']); ?>">
                        <?php echo htmlspecialchars($item['approval_status']); ?>
                      </span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </div>

  <script src="/javascript/script.js"></script>
  <script src="/javascript/header.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/settings.js"></script>
  <script src="/javascript/addEmployee.js"></script>

</body>
</html>