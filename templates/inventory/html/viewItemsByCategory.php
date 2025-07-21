<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/fetchItemsByCategory.php';
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
  <style>
    /* Search and Filter Wrapper */
    

  

    /* Filter Controls */
   
 


  </style>
</head>

<body>
  <div class="wrapMain">
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
              <input type="text" id="searchItem" placeholder="Search Item.." />
            </div>

            <div class="filterControls">
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
                $uniqueBrands = array_unique(array_column($items, 'brand'));
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

          <table class="itemTable">
            <thead>
              <tr>
                <th>#</th>
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
                  <td>
                    <img
                      src="<?= !empty($item['item_photo']) ? htmlspecialchars($item['item_photo']) : '/images/user-profile/default-image.jpg' ?>"
                      alt="Item Photo"
                      class="item-photo"
                    />
                  </td>
                  <td><?= htmlspecialchars($item['item_name']) ?></td>
                  <td><?= htmlspecialchars($item['brand']) ?></td>
                  <td><?= htmlspecialchars($item['model']) ?></td>
                  <td><?= htmlspecialchars($item['quantity']) ?></td>
                  <td><?= isset($item['date_acquired']) ? date("M-d-Y", strtotime($item['date_acquired'])) : 'N/A' ?></td>
                  <td>
                    <button class="action-btn view" title="View Items">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn edit" title="Edit Item">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" title="Delete Item">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <?php if ($totalPages > 1): ?>
            <div class="pagination">
              <?php if ($page > 1): ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $page - 1 ?>" class="prev-next" title="Previous">
                  <i class="fas fa-chevron-left"></i>
                </a>
              <?php else: ?>
                <a class="prev-next disabled" title="Previous">
                  <i class="fas fa-chevron-left"></i>
                </a>
              <?php endif; ?>

              <?php if ($page > 3): ?>
                <a href="?category_id=<?= $categoryId ?>&page=1">1</a>
                <?php if ($page > 4): ?>
                  <span class="ellipsis">...</span>
                <?php endif; ?>
              <?php endif; ?>

              <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
                  <?= $i ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $totalPages - 2): ?>
                <?php if ($page < $totalPages - 3): ?>
                  <span class="ellipsis">...</span>
                <?php endif; ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a>
              <?php endif; ?>

              <?php if ($page < $totalPages): ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $page + 1 ?>" class="prev-next" title="Next">
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
          <a href="/items" class="backBtn">
            <i class="fas fa-arrow-left"></i> Back to Inventory
          </a>
        </div>
      </div>
    </div>
  </div>

  <script src="/javascript/header.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/script.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
     
      const brandToggle = document.getElementById("toggleBrandFilter");
      const brandFilter = document.getElementById("brandFilterContainer");
      
      const qtyToggle = document.getElementById("toggleQtyFilter");
      const qtyFilter = document.getElementById("quantityFilterContainer");
      
      const dateToggle = document.getElementById("toggleDateFilter");
      const dateFilter = document.getElementById("dateFilterContainer");

 
      function closeAllFilters() {
        brandFilter.classList.add("hidden");
        qtyFilter.classList.add("hidden");
        dateFilter.classList.add("hidden");
      }

     
      brandToggle.addEventListener("click", function(e) {
        e.stopPropagation();
        if (brandFilter.classList.contains("hidden")) {
          closeAllFilters();
          brandFilter.classList.remove("hidden");
        } else {
          brandFilter.classList.add("hidden");
        }
      });

      
      qtyToggle.addEventListener("click", function(e) {
        e.stopPropagation();
        if (qtyFilter.classList.contains("hidden")) {
          closeAllFilters();
          qtyFilter.classList.remove("hidden");
        } else {
          qtyFilter.classList.add("hidden");
        }
      });

   
      dateToggle.addEventListener("click", function(e) {
        e.stopPropagation();
        if (dateFilter.classList.contains("hidden")) {
          closeAllFilters();
          dateFilter.classList.remove("hidden");
        } else {
          dateFilter.classList.add("hidden");
        }
      });

 
      document.addEventListener('click', function() {
        closeAllFilters();
      });

 
      [brandFilter, qtyFilter, dateFilter].forEach(filter => {
        filter.addEventListener('click', function(e) {
          e.stopPropagation();
        });
      });


      document.getElementById("sortLowToHigh").addEventListener("click", function() {
      
        qtyFilter.classList.add("hidden");
        console.log("Sorting Low to High");
    
      });

      document.getElementById("sortHighToLow").addEventListener("click", function() {
 
        qtyFilter.classList.add("hidden");
        console.log("Sorting High to Low");
    
      });

      document.getElementById("showOutOfStock").addEventListener("click", function() {
       
        qtyFilter.classList.add("hidden");
        console.log("Showing Out of Stock items");
   
      });

 
      document.getElementById("filterByBrandBtn").addEventListener("click", function() {
 
        brandFilter.classList.add("hidden");
        console.log("Applying brand filter");
      
      });

      document.getElementById("resetBrandFilterBtn").addEventListener("click", function() {
    
        document.getElementById("brandSelect").selectedIndex = -1;
        brandFilter.classList.add("hidden");
        console.log("Resetting brand filter");
       
      });

 
      document.getElementById("filterByDateBtn").addEventListener("click", function() {
      
        dateFilter.classList.add("hidden");
        console.log("Applying date filter");
       
      });

      document.getElementById("resetDateFilterBtn").addEventListener("click", function() {
    
        document.getElementById("dateFrom").value = "";
        document.getElementById("dateTo").value = "";
        dateFilter.classList.add("hidden");
        console.log("Resetting date filter");
      
      });
    });
  </script>
</body>
</html>