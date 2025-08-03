<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/fetchAllItems.php'; // New file
require __DIR__ . '/../function/editItemFunction.php';
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
    <h3>All Items</h3>

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
          <button id="toggleBrandFilter" class="filter-btn" title="Filter by Brand">
            <i class="fas fa-tags"></i>
          </button>
          <button id="toggleQtyFilter" class="filter-btn" title="Filter by Quantity">
            <i class="fas fa-sort-amount-up-alt"></i>
          </button>
          <button id="toggleDateFilter" class="filter-btn" title="Filter by Date">
            <i class="fas fa-calendar-alt"></i>
          </button>
        </div>

        <!-- Brand Filter -->
        <div class="filterContainer hidden" id="brandFilterContainer">
          <div class="filter-header">
            <i class="fas fa-tags"></i>
            <span>Filter by Brand</span>
          </div>
          <select id="brandSelect" multiple>
  <?php foreach ($brands as $brand): ?>
    <option value="<?= htmlspecialchars($brand) ?>"><?= htmlspecialchars($brand) ?></option>
  <?php endforeach; ?>
</select>

          <div class="filter-actions">
            <button id="filterByBrandBtn">Apply</button>
            <button id="resetBrandFilterBtn">Reset</button>
          </div>
        </div>

        <!-- Quantity Filter -->
        <div class="filterContainer hidden" id="quantityFilterContainer">
          <div class="filter-header">
            <i class="fas fa-sort-amount-up-alt"></i>
            <span>Filter by Quantity</span>
          </div>
          <div class="quantity-options">
            <button id="sortLowToHigh" class="quantity-option"><i class="fas fa-sort-amount-up"></i> Low to High</button>
            <button id="sortHighToLow" class="quantity-option"><i class="fas fa-sort-amount-down"></i> High to Low</button>
            <button id="showOutOfStock" class="quantity-option"><i class="fas fa-box-open"></i> Out of Stock</button>
          </div>
        </div>

        <!-- Date Filter -->
        <div class="dateFilterContainer hidden" id="dateFilterContainer">
          <div class="date-filter-header">
            <i class="fas fa-filter"></i>
            <span>Filter by acquire date</span>
          </div>
          <label for="dateFrom">From:</label>
          <input type="date" id="dateFrom" name="dateFrom">
          <label for="dateTo">To:</label>
          <input type="date" id="dateTo" name="dateTo">
          <div class="filter-actions">
            <button id="filterByDateBtn" class="filter-btn">Apply</button>
            <button id="resetDateFilterBtn" class="filter-btn">Reset</button>
          </div>
        </div>
      </div>

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
                data-date-acquired="<?= (!empty($item['date_acquired']) && $item['date_acquired'] !== '0000-00-00') ? date('Y-m-d', strtotime($item['date_acquired'])) : '' ?>"
                data-unit="<?= $item['unit'] ?>"
                data-unitcost="<?= $item['unit_cost'] ?? 0 ?>"
                data-totalcost="<?= $item['total_cost'] ?? 0 ?>"
                data-created="<?= $item['created_at'] ?>">
                <i class="fas fa-eye"></i>
              </button>

              <button class="action-btn edit" title="Edit Item"
                data-id="<?= $item['item_id'] ?>"
                data-photo="<?= htmlspecialchars($item['item_photo']) ?>"
                data-category-id="<?= $item['category_id'] ?>"
                data-description="<?= $item['description'] ?>"
                data-name="<?= $item['item_name'] ?>"
                data-brand="<?= $item['brand'] ?>"
                data-model="<?= $item['model'] ?>"
                data-serial="<?= $item['serial_number'] ?>"
                data-qty="<?= $item['quantity'] ?>"
                data-date-acquired="<?= (!empty($item['date_acquired']) && $item['date_acquired'] !== '0000-00-00') ? date('Y-m-d', strtotime($item['date_acquired'])) : '' ?>"
                data-unit="<?= $item['unit'] ?>"
                data-unitcost="<?= $item['unit_cost'] ?? 0 ?>"
                data-totalcost="<?= $item['total_cost'] ?? 0 ?>">
                <i class="fas fa-edit"></i>
              </button>

              <button class="action-btn delete"
        data-id="<?= $item['item_id'] ?>"
        data-name="<?= htmlspecialchars($item['item_name']) ?>"
        data-source="all">
  <i class="fas fa-trash-alt"></i>
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
        <a href="/inventory" class="backBtn"><i class="fas fa-arrow-left"></i> Back to Inventory</a>
      </div>
    </div>
  </div>
</div>

<script src="/javascript/header.js"></script>
<script src="/javascript/sidebar.js"></script>
<script src="/javascript/script.js"></script>

<script src="/javascript/viewItemByCategory.js"></script>

</body>
</html>
