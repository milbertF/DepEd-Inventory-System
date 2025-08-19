<?php
require_once __DIR__ . '/../../../config/restrictRoles.php';

restrictRoles(['Employee']);
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/fetchDeletedItems.php'; 

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>All Items</title>
  <link rel="stylesheet" href="/styles/items.css" />
  <link rel="stylesheet" href="/styles/viewItemByCategoryTable.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<?php require __DIR__ . '/viewItemModal.php'; ?>
<?php require __DIR__ . '/editItem.php'; ?>

<div class="wrapMain">
  <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>

  <div class="con">
    <h3>Recently Deleted Items</h3>
    

    <?php require __DIR__ . '/../../quick-access/access.php'; ?>

    <div class="tableContainer">
      <?php if (count($items) === 0): ?>
        <p style="text-align: center;">No items found.</p>
      <?php else: ?>

      <!-- Search + Filters -->
      <div class="searchFilterWrapper">
        <div class="searchContainer">
          <input type="text" id="searchItem" placeholder="Search Item.." />
        </div>

        <div class="filterControls">
         
          <button id="toggleQtyFilterAll" class="filter-btn" title="Filter by Quantity">
            <i class="fas fa-sort-amount-up-alt"></i>
          </button>
          <button id="toggleDateFilterAll" class="filter-btn" title="Filter by Date">
            <i class="fas fa-calendar-alt"></i>
          </button>
        </div>

        <!-- Brand Filter -->
        <div class="filterContainer hidden" >
        
        </div>

        <!-- Quantity Filter -->
        <div class="filterContainer hidden" id="quantityFilterContainerAll">
          <div class="filter-header">
            <i class="fas fa-sort-amount-up-alt"></i>
            <span>Filter by Quantity</span>
          </div>
          <div class="quantity-options">
            <button id="sortLowToHighAll" class="quantity-option"><i class="fas fa-sort-amount-up"></i> Low to High</button>
            <button id="sortHighToLowAll" class="quantity-option"><i class="fas fa-sort-amount-down"></i> High to Low</button>
            <button id="showOutOfStockAll" class="quantity-option"><i class="fas fa-box-open"></i> Out of Stock</button>
          </div>
        </div>

        <!-- Date Filter -->
        <div class="dateFilterContainer hidden" id="dateFilterContainerAll">
          <div class="date-filter-header">
            <i class="fas fa-filter"></i>
            <span>Filter by acquire date</span>
          </div>
          <label for="dateFrom">From:</label>
          <input type="date" id="dateFrom" name="dateFrom">
          <label for="dateTo">To:</label>
          <input type="date" id="dateTo" name="dateTo">
          <div class="filter-actions">
            <button id="filterByDateBtnAll" class="filter-btn">Apply</button>
            <button id="resetDateFilterBtnAll" class="filter-btn">Reset</button>
          </div>
        </div>
      </div>

      <button class="excel-export-btn" style ="margin-bottom:1rem" onclick="document.getElementById('exportModal').style.display='flex'">
    <i class="fas fa-file-excel"></i>
    Export to Excel
  </button>
  <?php require __DIR__ . '/exportModalforViewAll.php'; ?>


    <!-- Items Table -->
<table class="itemTable">
  <thead>
    <tr>
      <th>#</th>
      <th>Category</th>
      <th>Serial Number</th>
      <th>Image</th>
      <th>Item Name</th>
      <th>Brand</th>
      <th>Model</th>
      <th>Quantity</th>
      <th>Date Acquired</th>
      <th>Deleted By</th>
      <th>Deleted Date</th> <!-- NEW -->
      <th>Deleted Time</th> <!-- NEW -->
     
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $index => $item): ?>
    <tr>
      <td><?= ($page - 1) * $limit + $index + 1 ?></td>
      <td><?= htmlspecialchars($item['category_name']) ?></td>
      <td><?= !empty($item['serial_number']) ? htmlspecialchars($item['serial_number']) : 'None' ?></td>
      <td>
        <img src="<?= !empty($item['item_photo']) ? htmlspecialchars($item['item_photo']) : '/images/user-profile/default-image.jpg' ?>" class="item-photo" alt="Item Photo" />
      </td>
      <td><?= htmlspecialchars($item['item_name']) ?></td>
      <td><?= !empty($item['brand']) ? htmlspecialchars($item['brand']) : 'None' ?></td>
      <td><?= !empty($item['model']) ? htmlspecialchars($item['model']) : 'None' ?></td>
      <td><?= htmlspecialchars($item['quantity']) ?></td>
      <td><?= isset($item['date_acquired']) ? date("M-d-Y", strtotime($item['date_acquired'])) : 'N/A' ?></td>
      <td><?= htmlspecialchars($item['deleted_by_fname'] . ' ' . $item['deleted_by_lname']) ?></td>
      <td><?= !empty($item['deleted_at']) ? date("M-d-Y", strtotime($item['deleted_at'])) : 'N/A' ?></td> <!-- Date only -->
      <td><?= !empty($item['deleted_at']) ? date("h:i A", strtotime($item['deleted_at'])) : 'N/A' ?></td> <!-- Time only -->
      
      <td>
        <button class="action-btn view" title="View Item"
          data-id="<?= $item['item_id'] ?>"
          data-photo="<?= htmlspecialchars($item['item_photo']) ?>"
          data-category="<?= htmlspecialchars($item['category_name']) ?>"
          data-description="<?= htmlspecialchars($item['description']) ?>"
          data-name="<?= htmlspecialchars($item['item_name']) ?>"
          data-brand="<?= htmlspecialchars($item['brand']) ?>"
          data-model="<?= htmlspecialchars($item['model']) ?>"
          data-serial="<?= htmlspecialchars($item['serial_number']) ?>"
          data-qty="<?= $item['quantity'] ?>"
          data-deletedby="<?= htmlspecialchars($item['deleted_by_fname'] . ' ' . $item['deleted_by_lname']) ?>" 
          data-deletedat="<?= (!empty($item['deleted_at']) ? date('Y-m-d H:i:s', strtotime($item['deleted_at'])) : '') ?>" 
          data-date-acquired="<?= (!empty($item['date_acquired']) && $item['date_acquired'] !== '0000-00-00') ? date('Y-m-d', strtotime($item['date_acquired'])) : '' ?>"
          data-unit="<?= $item['unit'] ?>"
          data-unitcost="<?= $item['unit_cost'] ?? 0 ?>"
          data-totalcost="<?= $item['total_cost'] ?? 0 ?>"
          data-created="<?= $item['created_at'] ?>">
          <i class="fas fa-eye"></i>
          <span class="tooltip">View Item</span>
        </button>
        <button class="action-btn restore" 
    data-id="<?= $item['item_id'] ?>" 
    data-name="<?= htmlspecialchars($item['item_name']) ?>" 
    title="Restore Item">
    <i class="fas fa-undo"></i>
    <span class="tooltip">Restore Item</span>
  </button>

        <button class="action-btn delete"
          data-id="<?= $item['item_id'] ?>"
          data-name="<?= htmlspecialchars($item['item_name']) ?>"
          data-source="deleted">
          <i class="fas fa-trash-alt"></i>
          <span class="tooltip">Delete Item</span>
        </button>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="prev-next"><i class="fas fa-chevron-left"></i></a>
          <?php else: ?>
            <a class="prev-next disabled"><i class="fas fa-chevron-left"></i></a>
          <?php endif; ?>

          <?php if ($page > 3): ?>
            <a href="?page=1">1</a>
            <?php if ($page > 4): ?><span class="ellipsis">...</span><?php endif; ?>
          <?php endif; ?>

          <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
            <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>

          <?php if ($page < $totalPages - 2): ?>
            <?php if ($page < $totalPages - 3): ?><span class="ellipsis">...</span><?php endif; ?>
            <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
          <?php endif; ?>

          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="prev-next"><i class="fas fa-chevron-right"></i></a>
          <?php else: ?>
            <a class="prev-next disabled"><i class="fas fa-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php endif; ?>

      <div class="backBtnContainer">
        <a href="/inventory" class="backBtn"><i class="fas fa-arrow-left"></i> Back to Category</a>
      </div>
    </div>
  </div>
</div>

<script src="/javascript/header.js"></script>
<script src="/javascript/sidebar.js"></script>
<script src="/javascript/script.js"></script>

<script src="/javascript/viewAllitems.js"></script>\


</body>
</html>
