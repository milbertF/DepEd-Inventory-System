<?php
require_once __DIR__ . '/../../../config/restrictRoles.php';

restrictRoles(['Employee']);
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/fetchDeletedItems.php'; 
require __DIR__ . '/../function/fetchWhoDeletedEmployees.php'; 

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recently Deleted Items</title>
  <link rel="stylesheet" href="/styles/items.css" />
  <link rel="stylesheet" href="/styles/viewItemByCategoryTable.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<?php if (isset($_SESSION['message']) && isset($_SESSION['msg_type']) && isset($_SESSION['recovery_message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const message = "<?= addslashes($_SESSION['message']) ?>";
    const msgType = "<?= $_SESSION['msg_type'] ?>";
    
    if (msgType === 'success' && <?= isset($_SESSION['recovered_items']) ? 'true' : 'false' ?>) {
        // Multiple items recovered
        const count = <?= $_SESSION['recovered_count'] ?? 0 ?>;
        let htmlMessage = `Successfully recovered ${count} item${count > 1 ? 's' : ''}!`;
        
        <?php if (isset($_SESSION['recovered_items']) && count($_SESSION['recovered_items']) <= 5): ?>
        htmlMessage += `<br><small>Recovered: <?= implode(', ', $_SESSION['recovered_items']) ?></small>`;
        <?php elseif (isset($_SESSION['recovered_items']) && count($_SESSION['recovered_items']) > 5): ?>
        htmlMessage += `<br><small>Recovered ${count} items including: <?= implode(', ', array_slice($_SESSION['recovered_items'], 0, 5)) ?> and more...</small>`;
        <?php endif; ?>
        
        Swal.fire({
            icon: 'success',
            title: 'Items Recovered!',
            html: htmlMessage,
            confirmButtonColor: '#3085d6'
        });
    } else {
        // Single item or other message
        Swal.fire({
            icon: msgType,
            title: msgType.charAt(0).toUpperCase() + msgType.slice(1),
            text: message,
            confirmButtonColor: '#3085d6'
        });
    }
    
    // Clear the session - ADD recovery_message here too
    <?php
    unset($_SESSION['message']);
    unset($_SESSION['msg_type']);
    unset($_SESSION['recovered_items']);
    unset($_SESSION['recovery_message']); // Add this line
    ?>
});
</script>
<?php endif; ?>

<!-- Add this in your recentlyDeleted.php file -->
<div id="permanentDeleteAlertData" 
     data-permanent-deleted-name="<?php echo htmlspecialchars($_SESSION['permanent_deleted_item_name'] ?? ''); ?>"
     data-permanent-is-last-item="<?php echo isset($_SESSION['permanent_deleted_is_last_item']) && $_SESSION['permanent_deleted_is_last_item'] ? 'true' : 'false'; ?>"
     style="display: none;">
</div>

<?php
// Clear the session after setting the data attributes
if (isset($_SESSION['permanent_deleted_item_name'])) {
    unset($_SESSION['permanent_deleted_item_name']);
    unset($_SESSION['permanent_deleted_is_last_item']);
}
?>


<body>
<?php require __DIR__ . '/viewItemModal.php'; ?>
<?php require __DIR__ . '/editItem.php'; ?>

<div class="wrapMain">
  <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>

  <div class="con">
  <?php require __DIR__ . '/../../header/html/pageHeader.php'; ?>
    
    <?php require __DIR__ . '/../../quick-access/access.php'; ?>

    <div class="tableContainer">
      <?php if (count($items) === 0): ?>
        <p style="text-align: center;">No items found.</p>
      <?php else: ?>

        <!-- Search + Filters -->
        <div class="searchFilterWrapper" style="position: relative; display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
          <div class="searchContainer" style="flex: 1;">
            <input type="text" id="searchItem" placeholder="Search deleted items..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" />
          </div>

          <div class="filterControls" style="position: relative;">
            <!-- Reset All Filters Button -->
            <button id="resetAllFiltersBtn" class="filter-btn" title="Reset All Filters">
              <i class="fas fa-refresh"></i>
              <span class="sr-only">Reset All Filters</span>
              <div class="tooltip">Reset All Filters</div>
            </button>
            
            <button id="toggleColumnFilter" class="filter-btn" title="Show/Hide Columns">
            <i class="fas fa-columns"></i>
            <span class="sr-only">Show/Hide Columns</span>
            <div class="tooltip">Show/Hide Columns</div>
          </button>

            <!-- Category Filter Button -->
            <button id="toggleCategoryFilter" class="filter-btn" title="Filter by Category">
              <i class="fas fa-folder"></i>
              <span class="sr-only">Filter by Category</span>
              <div class="tooltip">Filter by Category</div>
            </button>

            <!-- Quantity Filter Button -->
            <button id="toggleQtyFilter" class="filter-btn" title="Filter by Quantity">
              <i class="fas fa-sort-amount-up-alt"></i>
              <span class="sr-only">Filter by Quantity</span>
              <div class="tooltip">Filter by Quantity</div>
            </button>

            <!-- Date Acquired Filter Button -->
            <button id="toggleDateFilter" class="filter-btn" title="Filter by Date Acquired">
              <i class="fas fa-calendar-alt"></i>
              <span class="sr-only">Filter by Date Acquired</span>
              <div class="tooltip">Filter by Date Acquired</div>
            </button>

            <!-- Deleted Date Filter Button -->
            <button id="toggleDeletedDateFilter" class="filter-btn" title="Filter by Deleted Date">
  <i class="fas fa-calendar-times"></i>
  <span class="sr-only">Filter by Deleted Date</span>
  <div class="tooltip">Filter by Deleted Date</div>
</button>

            <!-- Deleted By Filter Button -->
            <button id="toggleDeletedByFilter" class="filter-btn" title="Filter by Deleted By">
              <i class="fas fa-user-slash"></i>
              <span class="sr-only">Filter by Deleted By</span>
              <div class="tooltip">Filter by Deleted By</div>
            </button>


            


<!-- Column Filter Dropdown -->
<div class="columnFilterContainer hidden" id="columnFilterContainer" style="right: 0; min-width: 200px;">
  <div class="filter-header">
    <i class="fas fa-table-columns"></i>
    <span>Show/Hide Columns</span>
  </div>
  <div class="column-checkboxes">
    <label><input type="checkbox" data-column="1" checked> Item ID</label>
    <label><input type="checkbox" data-column="2" checked> Category</label>
    <label><input type="checkbox" data-column="3" checked> Image</label>
    <label><input type="checkbox" data-column="4" checked> Serial Number</label>
   
    <label><input type="checkbox" data-column="5" checked> Item Name</label>
    <label><input type="checkbox" data-column="6" checked> Brand</label>
    <label><input type="checkbox" data-column="7" checked> Model</label>
    <label><input type="checkbox" data-column="8" checked> Quantity</label>

    <label><input type="checkbox" data-column="9" checked> Date Acquired</label>
    <label><input type="checkbox" data-column="10" checked> Status</label>
    <label><input type="checkbox" data-column="11" checked> Deleted By</label>
    <label><input type="checkbox" data-column="12" checked> Deleted Date</label>
    <label><input type="checkbox" data-column="13" > Deleted Time</label>
    <label><input type="checkbox" data-column="14" checked> Actions</label>
  </div>
  <button class="reset-btn" id="resetColumnFilterBtn">Reset Columns</button>
</div>


        
            <!-- Category Filter Dropdown -->
<div class="filterContainer hidden" id="categoryFilterContainer" style="right: 0; min-width: 200px;">
  <div class="filter-header">
    <i class="fas fa-folder"></i>
    <span>Filter by Category</span>
  </div>
  <div class="category-checkboxes" id="categoryCheckboxes">
    <!-- Categories will be populated dynamically -->
  </div>
  <div class="filter-actions">
    <button id="filterByCategoryBtn">Apply</button>
    <button id="resetCategoryFilterBtn">Reset</button>
  </div>
</div>

            <!-- Quantity Filter Dropdown -->
            <div class="filterContainer hidden" id="quantityFilterContainer" style="right: 0; min-width: 200px;">
              <div class="filter-header">
                <i class="fas fa-sort-amount-up-alt"></i>
                <span>Filter by Quantity</span>
              </div>
              <div class="quantity-options">
                <button id="sortLowToHigh" class="quantity-option"><i class="fas fa-sort-amount-up"></i> Low to High</button>
                <button id="sortHighToLow" class="quantity-option"><i class="fas fa-sort-amount-down"></i> High to Low</button>
                <button id="showAvailable" class="quantity-option"><i class="fas fa-box"></i> Available (Qty > 0)</button>
                <button id="showOutOfStock" class="quantity-option"><i class="fas fa-box-open"></i> Out of Stock (Qty = 0)</button>
                <button id="resetQuantityFilter" class="quantity-option"><i class="fas fa-times"></i> Show All</button>
              </div>
            </div>

            <!-- Date Acquired Filter Dropdown -->
            <div class="dateFilterContainer hidden" id="dateFilterContainer" style="right: 0; min-width: 250px;">
              <div class="date-filter-header">
                <i class="fas fa-filter"></i>
                <span>Filter by Date Acquired</span>
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

            <!-- Deleted Date Filter Dropdown -->
            <div class="dateFilterContainer hidden" id="deletedDateFilterContainer" style="right: 0; min-width: 250px;">
              <div class="date-filter-header">
                <i class="fas fa-trash-calendar"></i>
                <span>Filter by Deleted Date</span>
              </div>
              <label for="deletedDateFrom">From:</label>
              <input type="date" id="deletedDateFrom" name="deletedDateFrom">
              <label for="deletedDateTo">To:</label>
              <input type="date" id="deletedDateTo" name="deletedDateTo">
              <div class="filter-actions">
                <button id="filterByDeletedDateBtn" class="filter-btn">Apply</button>
                <button id="resetDeletedDateFilterBtn" class="filter-btn">Reset</button>
              </div>
            </div>

        
          <!-- Deleted By Filter Dropdown -->
<div class="filterContainer hidden" id="deletedByFilterContainer" style="right: 0; min-width: 250px;">
  <div class="filter-header">
    <i class="fas fa-user-slash"></i>
    <span>Filter by Deleted By</span>
  </div>
  <div class="deleted-by-options" style="max-height: 300px; overflow-y: auto; padding: 5px;">
    <?php foreach ($employees as $employee): ?>
      <label style="display: flex; align-items: center; gap: 8px; padding: 5px; border-radius: 4px; transition: background-color 0.2s;">
      <input type="checkbox" name="deletedByFilter" value="<?= htmlspecialchars($employee['first_name']) ?>">
<span><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <div class="filter-actions">
    <button id="filterByDeletedByBtn">Apply</button>
    <button id="resetDeletedByBtn">Reset</button>
  </div>
</div>
          </div>
        </div>

        <button class="excel-export-btn" style="margin-bottom:1rem" onclick="retrieveAllItems()">
  <i class="fas fa-list"></i>
  Retrieve All
</button>


        <?php require __DIR__ . '/exportModalforViewAll.php'; ?>

        <table class="itemTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Item ID</th>
              <th>Category</th>
               <th>Image</th>
              <th>Serial Number</th>
             
              <th>Item Name</th>
              <th>Brand</th>
              <th>Model</th>
              <th>Quantity</th>
              <th>Date Acquired</th>
              <th>Item Status</th>
              <th>Deleted By</th>
              <th>Deleted Date</th> 
              <th>Deleted Time</th> 
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="inventoryTableBody">
            <?php foreach ($items as $index => $item): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= htmlspecialchars($item['item_id']) ?></td>
              <td><?= htmlspecialchars($item['category_name']) ?></td>
              <td>
                <img src="<?= !empty($item['item_photo']) ? htmlspecialchars($item['item_photo']) : '/images/user-profile/default-image.jpg' ?>" class="item-photo" alt="Item Photo" />
              </td>
              <td><?= !empty($item['serial_number']) ? htmlspecialchars($item['serial_number']) : 'None' ?></td>
              
              <td><?= htmlspecialchars($item['item_name']) ?></td>
              <td><?= !empty($item['brand']) ? htmlspecialchars($item['brand']) : 'None' ?></td>
              <td><?= !empty($item['model']) ? htmlspecialchars($item['model']) : 'None' ?></td>
              <td><?= htmlspecialchars($item['quantity']) ?></td>
              <td><?= isset($item['date_acquired']) ? date("M-d-Y", strtotime($item['date_acquired'])) : 'N/A' ?></td>
              <td><?= htmlspecialchars($item['item_status']) ?></td>
              <td><?= htmlspecialchars($item['deleted_by_fname'] . ' ' . $item['deleted_by_lname']) ?></td>
              <td><?= !empty($item['deleted_at']) ? date("M-d-Y", strtotime($item['deleted_at'])) : 'N/A' ?></td> 
              <td><?= !empty($item['deleted_at']) ? date("h:i A", strtotime($item['deleted_at'])) : 'N/A' ?></td> 
              
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
                 
                  data-itemstatus="<?= $item['item_status'] ?>"
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
              <span id="totalItemsCount"><?= count($items) ?></span> total items
              <span id="filteredItemsCount" style="display: none;">
                | Showing <span id="visibleItemsCount">0</span> of <span id="totalItemsCount2"><?= count($items) ?></span>
              </span>
            </div>
          </div>
        </div>
        
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
<script src="/javascript/recentlyDeletedItems.js"></script>


<script>
  // checkPermanentDeleteAlerts.js
function checkForPermanentDeletedItem() {
    const urlParams = new URLSearchParams(window.location.search);
    const permanentDeleted = urlParams.get('permanent_deleted');
    
    if (permanentDeleted === '1') {
        // Get data from data attributes
        const alertContainer = document.getElementById('permanentDeleteAlertData');
        if (alertContainer) {
            const deletedName = alertContainer.getAttribute('data-permanent-deleted-name');
            const isLastItem = alertContainer.getAttribute('data-permanent-is-last-item') === 'true';
            
            if (deletedName) {
                setTimeout(() => {
                    let message = `Item <b>${deletedName}</b> was permanently deleted from the system.`;
                    
                    if (isLastItem) {
                        message += `<br><small><i class="fas fa-info-circle"></i> This was the last deleted item.</small>`;
                    }
                    
                    Swal.fire({
                        icon: 'success', 
                        title: 'Permanently Deleted!', 
                        html: message,
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('permanent_deleted');
                        window.history.replaceState({}, document.title, url.pathname);
                    });
                }, 100);
            }
        }
    } else if (permanentDeleted === '0') {
        Swal.fire({
            icon: 'error',
            title: 'Delete Failed',
            text: 'Failed to permanently delete item. Please try again.',
            confirmButtonColor: '#3085d6'
        }).then(() => {
            const url = new URL(window.location.href);
            url.searchParams.delete('permanent_deleted');
            window.history.replaceState({}, document.title, url.pathname);
        });
    }
}

// Multiple event listeners to ensure it runs
document.addEventListener('DOMContentLoaded', function() {
    checkForPermanentDeletedItem();
});

window.addEventListener('load', function() {
    checkForPermanentDeletedItem();
});

window.addEventListener('pageshow', function() {
    checkForPermanentDeletedItem();
});
</script>

</body>
</html>