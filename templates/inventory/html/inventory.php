<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/editCategoryFunction.php';
require __DIR__ . '/../function/fetchCategory.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSI-Inventory</title>
  <link rel="stylesheet" href="/styles/items.css" />
  <link rel="stylesheet" href="/styles/empItemTable.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  <?php require __DIR__ . '/editCategory.php'; ?>

  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>

    <div class="con">
    <?php require __DIR__ . '/../../header/html/pageHeader.php'; ?>
      <?php require __DIR__ . '/../../quick-access/access.php'; ?>

      <div class="tableContainer">
        <div class="searchContainer">
          <input type="text" id="searchCategory" placeholder="Search Category..." />
        </div>

        <table class="itemTable">
  <thead>
    <tr>
      <th>#</th>
      <th>Category Name</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody id="categoryTableBody">
    <?php if (!empty($categories)): ?>
      <?php foreach ($categories as $index => $category): ?>
        <tr>
          <td><?= $index + 1 ?></td>
          <td><?= htmlspecialchars($category['category_name']) ?></td>
          <td>
            <button class="action-btn view"
              onclick="window.location.href='/itemsByCategory?category_id=<?= $category['category_id'] ?>'">
              <i class="fas fa-external-link-alt"></i>
              <span class="tooltip">View Items (<?= $category['item_count'] ?? 0 ?> items)</span>
            </button>

            <?php if (isset($_SESSION['user']['role']) && 
         ($_SESSION['user']['role'] === 'Admin' || $_SESSION['user']['role'] === 'logisticsOfficer')): ?>

              <button class="action-btn edit"
                data-id="<?= $category['category_id'] ?>"
                data-name="<?= htmlspecialchars($category['category_name']) ?>">
                <i class="fas fa-edit"></i>
                <span class="tooltip">Edit Category</span>
              </button>

              <!-- Delete button - only show if category has no items -->
              <?php if (($category['item_count'] ?? 0) == 0): ?>
                <button class="action-btn delete"
                  data-id="<?= $category['category_id'] ?>"
                  data-name="<?= htmlspecialchars($category['category_name']) ?>">
                  <i class="fas fa-trash-alt"></i>
                  <span class="tooltip">Delete Category</span>
                </button>
              <?php else: ?>
                <!-- Show disabled delete button with tooltip -->
                <button class="action-btn delete disabled" 
                  title="Cannot delete category with items" 
                  disabled
                  style="opacity: 0.5; cursor: not-allowed;">
                  <i class="fas fa-trash-alt"></i>
                  <span class="tooltip">Category contains <?= $category['item_count'] ?? 0 ?> item(s)</span>
                </button>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="3" style="text-align:center;">No categories found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

        <div class="pagination" id="pagination"></div>

        <div class="table-footer">
  <div class="footer-left">
    <div class="items-per-page-selector">
      <label for="rowsPerPageSelect">Show:</label>
      <select id="rowsPerPageSelect" class="form-select">
        <option value="10">10</option>
        <option value="20">20</option>
        <option value="30">30</option>
        <option value="50">50</option>
      </select>
      <span>entries</span>
    </div>

  </div>
  
  <div class="footer-right">
    <div class="item-count-display" style="background: #f8f9fa; padding: 8px 16px; border-radius: 20px; font-weight: 500; color: #495057;">
      <span id="totalItemsCount"><?= count($categories) ?></span> total items
      <span id="filteredItemsCount" style="display: none;">
        | Showing <span id="visibleItemsCount">0</span> of <span id="totalItemsCount2"><?= count($categories) ?></span>
      </span>
    </div>
  </div>
</div>

        

        <div class="viewAllContainer">
          <a href="/allItems" class="viewAll"><i class="fas fa-boxes"></i> View All Items</a>
          <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin'): ?>
            <a href="/recentlyDeleted" class="viewAll"><i class="fas fa-trash-alt"></i> Recently Deleted Items</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="/javascript/header.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/inventory.js"></script>
      <script src="/javascript/script.js"></script>
</body>
</html>
