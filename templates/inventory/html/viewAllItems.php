<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/fetchAllItems.php'; 

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

<!-- Add this in your allItems PHP file -->
<div id="allItemsDeleteAlertData" 
     data-all-deleted-name="<?php echo htmlspecialchars($_SESSION['deleted_all_item_name'] ?? ''); ?>"
     data-all-is-last-item="<?php echo isset($_SESSION['deleted_all_is_last_item']) && $_SESSION['deleted_all_is_last_item'] ? 'true' : 'false'; ?>"
     style="display: none;">
</div>

<?php

if (isset($_SESSION['deleted_all_item_name'])) {
    unset($_SESSION['deleted_all_item_name']);
    unset($_SESSION['deleted_all_is_last_item']);
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

        <div class="searchFilterWrapper" style="position: relative; display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
            <div class="searchContainer" style="flex: 1;">
              <input type="hidden" id="categoryId" value="<?= $categoryId ?>" />
              <input type="text" id="searchItem" placeholder="Search items by name, brand, model, serial number..."
                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" />
            </div>

      <!-- Search + Filters -->
    
        <div class="filterControls" style="position: relative;">
          <!-- Reset All Filters Button -->
          <button id="resetAllFiltersBtn" class="filter-btn" title="Reset All Filters">
            <i class="fas fa-refresh"></i>
            <span class="sr-only">Reset All Filters</span>
            <div class="tooltip">Reset All Filters</div>
          </button>

          <!-- Column Filter Button -->
          <button id="toggleColumnFilter" class="filter-btn" title="Show/Hide Columns">
            <i class="fas fa-columns"></i>
            <span class="sr-only">Show/Hide Columns</span>
            <div class="tooltip">Show/Hide Columns</div>
          </button>

           <!-- Quantity Filter Button -->
           <button id="toggleQtyFilterAll" class="filter-btn" title="Filter by Quantity">
            <i class="fas fa-sort-amount-up-alt"></i>
            <span class="sr-only">Filter by Quantity</span>
            <div class="tooltip">Filter by Quantity</div>
          </button>

       

       
          <button id="toggleDateFilterAll" class="filter-btn" title="Filter by Date">
            <i class="fas fa-calendar-alt"></i>
            <span class="sr-only">Filter by Acquired Date</span>
            <div class="tooltip">Filter by Acquired Date</div>
          </button>

             <!-- Status Filter Button -->
             <button id="toggleStatusFilter" class="filter-btn" title="Filter by Status">
            <i class="fas fa-info-circle"></i>
            <span class="sr-only">Filter by Status</span>
            <div class="tooltip">Filter by Status</div>
          </button>


         
        

          <!-- Column Filter Dropdown -->
          <div class="columnFilterContainer hidden" id="columnFilterContainer" style="right: 0; min-width: 200px;">
            <div class="filter-header">
              <i class="fas fa-table-columns"></i>
              <span>Show/Hide Columns</span>
            </div>
            <div class="column-checkboxes">
            <label><input type="checkbox" data-column="1" > Item ID</label>
              <label><input type="checkbox" data-column="2" checked> Category</label>
              <label><input type="checkbox" data-column="3" > Image</label>
              <label><input type="checkbox" data-column="4" checked> Serial Number</label>
              <label><input type="checkbox" data-column="5" checked> Item Name</label>
              <label><input type="checkbox" data-column="6" checked> Description</label>
              <label><input type="checkbox" data-column="7" > Brand</label>
              <label><input type="checkbox" data-column="8" > Model</label>
              <label><input type="checkbox" data-column="9" checked> Unit Cost</label>
              <label><input type="checkbox" data-column="10" checked> Total Quantity</label>
              <label><input type="checkbox" data-column="11" checked> Available Quantity</label>
              <label><input type="checkbox" data-column="12" checked> Total Cost</label>
              <label><input type="checkbox" data-column="13" checked> Date Acquired</label>
              <label><input type="checkbox" data-column="14" checked> Status</label>
              <label><input type="checkbox" data-column="15" checked> Actions</label>
            </div>
            <button class="reset-btn" id="resetColumnFilterBtn">Reset Columns</button>
          </div>

          <!-- Status Filter Dropdown -->
          <div class="statusFilterContainer hidden" id="statusFilterContainer" style="right: 0; min-width: 200px;">
            <div class="filter-header">
              <i class="fas fa-info-circle"></i>
              <span>Filter by Status</span>
            </div>
            <div class="status-checkboxes">
              <?php
              $allStatuses = array_column($items, 'item_status');
              $uniqueStatuses = array_unique($allStatuses);
              sort($uniqueStatuses);
              
              foreach ($uniqueStatuses as $status): 
                if (!empty($status) && $status !== ''): ?>
                  <label>
                    <input type="checkbox" name="statusFilter" value="<?= htmlspecialchars($status) ?>" >
                    <?= htmlspecialchars($status) ?>
                  </label>
              <?php endif; 
              endforeach; ?>
            </div>
            <div class="filter-actions">
              <button id="filterByStatusBtn">Apply</button>
              <button id="resetStatusFilterBtn">Reset</button>
            </div>
          </div>

          <!-- Quantity Filter Dropdown -->
          <div class="filterContainer hidden" id="quantityFilterContainerAll">
            <div class="filter-header">
              <i class="fas fa-sort-amount-up-alt"></i>
              <span>Filter by Available Quantity</span>
            </div>
            <div class="quantity-options">
              <button id="sortLowToHighAll" class="quantity-option"><i class="fas fa-sort-amount-up"></i> Low to High</button>
              <button id="sortHighToLowAll" class="quantity-option"><i class="fas fa-sort-amount-down"></i> High to Low</button>
           
            </div>
          </div>

          <!-- Date Filter Dropdown -->
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
      </div>
      <?php if (isset($_SESSION['user']['role']) && 
         ($_SESSION['user']['role'] === 'Admin' || $_SESSION['user']['role'] === 'logisticsOfficer')): ?>

      <button class="excel-export-btn" style="margin-bottom:1rem" onclick="document.getElementById('exportModal').style.display='flex'">
        <i class="fas fa-file-excel"></i>
        Export to Excel
      </button>
      <button class="excel-export-btn" style="margin-bottom:1rem" onclick="printAllCurrentTableView()">
    <i class="fas fa-print" style="color: #2b579a;"></i>
    Print 
</button>

      <?php endif; ?>
      <?php require __DIR__ . '/exportModalforViewAll.php'; ?>

      <!-- Items Table -->
      <table class="itemTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Item ID</th>
            <th>Category</th>
            <th>Image</th>
            <th>Serial Number</th>
            <th>Item Name</th>
            <th>Description</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Unit Cost</th>
            <th> Total Quantity</th>
            <th> Available Quantity</th>
            <th>Total Cost</th>
            <th>Date Acquired</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="inventoryTableBody">
          <?php foreach ($items as $index => $item): ?>
            <tr data-item-id="<?= $item['item_id'] ?>">
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($item['item_id']) ?></td>
            <td><?= htmlspecialchars($item['category_name']) ?></td>
            <td>
              <img src="<?= !empty($item['item_photo']) ? htmlspecialchars($item['item_photo']) : '/images/user-profile/default-image.jpg' ?>" class="item-photo" alt="Item Photo" />
            </td>
            <td><?= !empty($item['serial_number']) ? htmlspecialchars($item['serial_number']) : '—' ?></td>
           
            <td><?= htmlspecialchars($item['item_name']) ?></td>
            <td><?= !empty($item['description']) ? htmlspecialchars($item['description']) : '—' ?></td>
            <td><?= !empty($item['brand']) ? htmlspecialchars($item['brand']) : '—' ?></td>
            <td><?= !empty($item['model']) ? htmlspecialchars($item['model']) : '—' ?></td>
            <td>₱<?= number_format($item['unit_cost'], 2) ?></td>
            <td><?= htmlspecialchars($item['total_quantity']) ?></td>
            <td><?= htmlspecialchars($item['available_quantity']) ?></td>
            <td>₱<?= number_format($item['total_cost'], 2) ?></td>
            <td><?= isset($item['date_acquired']) ? date("M-d-Y", strtotime($item['date_acquired'])) : 'N/A' ?></td>
            <td><?= htmlspecialchars($item['item_status']) ?></td>
            <td><?= !empty($item['remarks']) ? htmlspecialchars($item['remarks']) : '—' ?></td>
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
                data-qty="<?= $item['total_quantity'] ?>"
                data-available-qty="<?= $item['available_quantity'] ?>"
                data-date-acquired="<?= (!empty($item['date_acquired']) && $item['date_acquired'] !== '0000-00-00') ? date('Y-m-d', strtotime($item['date_acquired'])) : '' ?>"
                data-unit="<?= $item['unit'] ?>"
                data-unitcost="<?= $item['unit_cost'] ?? 0 ?>"
                data-totalcost="<?= $item['total_cost'] ?? 0 ?>"
                data-itemstatus="<?= $item['item_status'] ?? 0 ?>"
                data-remarks="<?= htmlspecialchars($item['remarks']) ?>"
                data-created="<?= $item['created_at'] ?>">
                <i class="fas fa-eye"></i>
                <span class="tooltip">View Item</span>
              </button>

              <?php if (isset($_SESSION['user']['role']) && 
         ($_SESSION['user']['role'] === 'Admin' || $_SESSION['user']['role'] === 'logisticsOfficer')): ?>
              <button class="action-btn edit" title="Edit Item"
                data-id="<?= $item['item_id'] ?>"
                data-photo="<?= htmlspecialchars($item['item_photo']) ?>"
                data-category-id="<?= $item['category_id'] ?>"
                data-description="<?= $item['description'] ?>"
                data-name="<?= $item['item_name'] ?>"
                data-brand="<?= $item['brand'] ?>"
                data-model="<?= $item['model'] ?>"
                data-serial="<?= $item['serial_number'] ?>"
             
                data-qty="<?= $item['total_quantity'] ?>"
                data-available-qty="<?= $item['available_quantity'] ?>"
            
                data-date-acquired="<?= (!empty($item['date_acquired']) && $item['date_acquired'] !== '0000-00-00') ? date('Y-m-d', strtotime($item['date_acquired'])) : '' ?>"
                data-item-status="<?= $item['item_status'] ?>"
                data-remarks="<?= $item['remarks'] ?>"
                data-unit="<?= $item['unit'] ?>"
                data-unitcost="<?= $item['unit_cost'] ?? 0 ?>"
                data-totalcost="<?= $item['total_cost'] ?? 0 ?>">
                <i class="fas fa-edit"></i>
                <span class="tooltip">Edit Item</span>
              </button>

              <button class="action-btn delete"
                data-id="<?= $item['item_id'] ?>"
                data-name="<?= htmlspecialchars($item['item_name']) ?>"
                data-source="all">
                <i class="fas fa-trash-alt"></i>
                <span class="tooltip">Delete Item</span>
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Pagination -->
     
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

         

              <script>
           
function checkForAllDeletedItem() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemDeleted = urlParams.get('item_all_deleted');
    
    if (itemDeleted === '1') {
    
        const alertContainer = document.getElementById('allItemsDeleteAlertData');
        if (alertContainer) {
            const deletedName = alertContainer.getAttribute('data-all-deleted-name');
            const isLastItem = alertContainer.getAttribute('data-all-is-last-item') === 'true';
            
            if (deletedName) {
                setTimeout(() => {
                    let message = `Item <b>${deletedName}</b> was deleted successfully.`;
                    
                    if (isLastItem) {
                      
                    }
                    
                    Swal.fire({
                        icon: 'success', 
                        title: 'Deleted!', 
                        html: message,
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('item_all_deleted');
                        window.history.replaceState({}, document.title, url.pathname);
                    });
                }, 100);
            }
        }
    } else if (itemDeleted === '0') {
        Swal.fire({
            icon: 'error',
            title: 'Delete Failed',
            text: 'Failed to delete item. Please try again.',
            confirmButtonColor: '#3085d6'
        }).then(() => {
            const url = new URL(window.location.href);
            url.searchParams.delete('item_all_deleted');
            window.history.replaceState({}, document.title, url.pathname);
        });
    }
}



window.addEventListener('load', function() {
    checkForAllDeletedItem();
});

window.addEventListener('pageshow', function() {
    checkForAllDeletedItem();
})




              </script>




<script src="/javascript/header.js"></script>
<script src="/javascript/sidebar.js"></script>
<script src="/javascript/script.js"></script>
<script src="/javascript/viewAllItems.js"></script>

</body>
</html>