  <?php
  require __DIR__ . '/../../header/html/header.php';
  require __DIR__ . '/../function/fetchItemsByCategory.php';
  require __DIR__ . '/../function/editItemFunction.php';

  ?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BSCI-<?= htmlspecialchars($category['category_name']) ?></title>
    <link rel="stylesheet" href="/styles/items.css" />
    <link rel="stylesheet" href="/styles/viewItemByCategoryTable.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    
  </head>

 




  <body>
  <?php require __DIR__ . '/viewItemModal.php'; ?>
  <?php require __DIR__ . '/editItem.php'; ?>

    <div class="wrapMain">
      
    <?php require __DIR__ . '/viewItemModal.php'; ?>
      <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
      
      
      <div class="con">
        <h3>Items under category: <?= htmlspecialchars($category['category_name']) ?></h3>
        
        <?php require __DIR__ . '/../../quick-access/access.php'; ?>
              
        <div class="tableContainer">
          <?php if (count($items) === 0): ?>
            <p style="text-align: center;">No items found in this category.</p>
          <?php else: ?>

            <div class="searchFilterWrapper">
              <div class="searchContainer">
              <input type="hidden" id="categoryId" value="<?= $categoryId ?>" />
              <input type="text" id="searchItem" placeholder="Search positions..." 
                  value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" />
              </div>

          

              <div class="filterControls">

  <!-- Column Filter Button -->
              <button id="toggleColumnFilter" class="filter-btn" title="Show/Hide Columns">
  <i class="fas fa-columns"></i>
  <span class="sr-only">Show/Hide Columns</span>
</button>


<!-- Column Filter Dropdown -->
<div class="columnFilterContainer hidden" id="columnFilterContainer">
  <div class="filter-header">
    <i class="fas fa-table-columns"></i>
    <span>Show/Hide Columns</span>
  </div>

  <div class="column-checkboxes">
    <label><input type="checkbox" data-column="1" checked> Serial Number</label>
    <label><input type="checkbox" data-column="2" checked> Image</label>
    <label><input type="checkbox" data-column="3" checked> Item Name</label>
    <label><input type="checkbox" data-column="4" checked> Brand</label>
    <label><input type="checkbox" data-column="5" checked> Model</label>
    <label><input type="checkbox" data-column="6" checked> Unit Cost</label>
    <label><input type="checkbox" data-column="7" checked> Quantity</label>
    <label><input type="checkbox" data-column="8" checked> Total Cost</label>
    <label><input type="checkbox" data-column="9" checked> Date Acquired</label>
    <label><input type="checkbox" data-column="10" checked> Actions</label>
  </div>

  <button class="reset-btn" id="resetColumnFilterBtn">Reset</button>
</div>


                
                <!-- Brand Filter Button -->
                <button id="toggleBrandFilter" class="filter-btn" title="Filter by Brand">
                  <i class="fas fa-tags"></i>
                  <span class="sr-only">Filter by Brand</span>
                </button>

                <!-- Quantity Filter Button -->
                <button id="toggleQtyFilter" class="filter-btn" title="Filter by Quantity">
                  <i class="fas fa-sort-amount-up-alt"></i>
                  <span class="sr-only">Filter by Quantity</span>
                </button>

                <!-- Date Filter Button -->
                <button id="toggleDateFilter" class="filter-btn" title="Filter by Date">
                  <i class="fas fa-calendar-alt"></i>
                  <span class="sr-only">Filter by Date</span>
                </button>
              </div>

              <!-- Brand Filter Dropdown -->
              <div class="filterContainer hidden" id="brandFilterContainer">
                <div class="filter-header">
                  <i class="fas fa-tags"></i>
                  <span>Filter by Brand</span>
                </div>
                
                <select id="brandSelect" multiple>
    <?php 

    $allBrands = array_column($items, 'brand');
    $filteredBrands = array_filter($allBrands, function($brand) {
        return $brand !== null && $brand !== '';
    });
    $uniqueBrands = array_unique($filteredBrands);
    
    foreach ($uniqueBrands as $brand): 
    ?>
        <option value="<?= htmlspecialchars($brand) ?>"><?= htmlspecialchars($brand) ?></option>
    <?php endforeach; ?>
</select>

                <div class="filter-actions">
                  <button id="filterByBrandBtn">Apply</button>
                  <button id="resetBrandFilterBtn">Reset</button>
                </div>
              </div>

              <!-- Quantity Filter Dropdown -->
              <div class="filterContainer hidden" id="quantityFilterContainer">
                <div class="filter-header">
                  <i class="fas fa-sort-amount-up-alt"></i>
                  <span>Filter by Quantity</span>
                </div>
                
                <div class="quantity-options">
                  <button id="sortLowToHigh" class="quantity-option">
                    <i class="fas fa-sort-amount-up"></i> Low to High
                  </button>
                  <button id="sortHighToLow" class="quantity-option">
                    <i class="fas fa-sort-amount-down"></i> High to Low
                  </button>
                  <button id="showOutOfStock" class="quantity-option">
                    <i class="fas fa-box-open"></i> Out of Stock
                  </button>
                </div>
              </div>

              <!-- Date Filter Dropdown -->
          <div class="dateFilterContainer hidden" id="dateFilterContainer" >
      <div class="date-filter-header" >
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

           
  <button class="excel-export-btn" style ="margin-bottom:1rem" onclick="document.getElementById('exportModal').style.display='flex'">
    <i class="fas fa-file-excel"></i>
    Export to Excel
  </button>


  <?php require __DIR__ . '/exportModal.php'; ?>



            <table class="itemTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Serial Number</th>
                  <th>Image</th>
                  <th>Item Name</th>
                  <th>Brand</th>
                  <th>Model</th>
                  <th>Unit Cost</th>
                  <th>Quantity</th>
                  <th>Total Cost</th>
                  <th>Date Acquired</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $index => $item): ?>
                  <tr>
                    <td><?= ($page - 1) * $limit + $index + 1 ?></td>
                    <td><?= !empty($item['serial_number']) ? htmlspecialchars($item['serial_number']) : 'None' ?></td>

                    <td>
                      <img
                        src="<?= !empty($item['item_photo']) ? htmlspecialchars($item['item_photo']) : '/images/user-profile/default-image.jpg' ?>"
                        alt="Item Photo"
                        class="item-photo"
                      />
                    </td>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= !empty($item['brand']) ? htmlspecialchars($item['brand']) : 'None' ?></td>
  <td><?= !empty($item['model']) ? htmlspecialchars($item['model']) : 'None' ?></td>
  <td>₱<?= htmlspecialchars($item['unit_cost']) ?></td>

                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td>₱<?= htmlspecialchars($item['total_cost']) ?></td>
                    <td><?= isset($item['date_acquired']) ? date("M-d-Y", strtotime($item['date_acquired'])) : 'N/A' ?></td>
                    <td>
                    <button class="action-btn view" title="View Item"
    data-id="<?= $item['item_id'] ?>"
    data-photo="<?= htmlspecialchars($item['item_photo']) ?>"
    data-category="<?= htmlspecialchars($category['category_name']) ?>"
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
    data-created="<?= $item['created_at'] ?>"
  >
    <i class="fas fa-eye"></i>
    <span class="tooltip">View Item</span>
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
      <span class="tooltip">Edit Item</span>
  </button>


                      <button class="action-btn delete"
    data-id="<?= $item['item_id'] ?>"
    data-name="<?= htmlspecialchars($item['item_name']) ?>">
    <i class="fas fa-trash-alt"></i>
    <span class="tooltip">Delete Item</span>
  </button>


                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="#" data-page="<?= $page - 1 ?>" class="prev-next" title="Previous">
            <i class="fas fa-chevron-left"></i>
        </a>
    <?php else: ?>
        <a class="prev-next disabled" title="Previous">
            <i class="fas fa-chevron-left"></i>
        </a>
    <?php endif; ?>

    <?php if ($page > 3): ?>
        <a href="#" data-page="1">1</a>
        <?php if ($page > 4): ?>
            <span class="ellipsis">...</span>
        <?php endif; ?>
    <?php endif; ?>

    <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
        <a href="#" data-page="<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages - 2): ?>
        <?php if ($page < $totalPages - 3): ?>
            <span class="ellipsis">...</span>
        <?php endif; ?>
        <a href="#" data-page="<?= $totalPages ?>"><?= $totalPages ?></a>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
        <a href="#" data-page="<?= $page + 1 ?>" class="prev-next" title="Next">
            <i class="fas fa-chevron-right"></i>
        </a>
    <?php else: ?>
        <a class="prev-next disabled" title="Next">
            <i class="fas fa-chevron-right"></i>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>
          <?php endif; ?>
          
          <div class="backBtnContainer">
            <a href="/inventory" class="backBtn">
              <i class="fas fa-arrow-left"></i> Back to Category
            </a>
          </div>
        </div>
      </div>
    </div>

    <script src="/javascript/header.js"></script>
    <script src="/javascript/sidebar.js"></script>
    <script src="/javascript/script.js"></script>
    <script src="/javascript/viewItemByCategory.js" defer></script>



   

  </body>
  </html>