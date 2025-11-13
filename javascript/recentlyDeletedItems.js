let currentFilters = {
  category: [],
  quantity: "",
  dateFrom: "",
  dateTo: "",
  deletedBy: [],
  deletedDateFrom: "",
  deletedDateTo: ""
};

let allRows = [];
let filteredRows = [];
let thisCurrentPage = 1;
let rowsPerPage = parseInt(localStorage.getItem('deletedItemsTableRowsPerPage')) || 10;
let debounceTimer = null;
let filterCache = new Map();
let currentSort = { field: null, order: null };

let domElements = {};
let columnMap = {};

// =============================================
// SCROLL & NAVIGATION VARIABLES
// =============================================
let isRedirectingToDeletedItem = false;
let targetDeletedItemId = null;
let isShowingDeletedItemNotFoundAlert = false;

// =============================================
// INITIALIZATION
// =============================================
document.addEventListener("DOMContentLoaded", function () {
  initializeDOMCache();
  buildColumnMap();
  initializeTable();
  initFilterControls();
  initColumnFilter(); 
  initTableActions();
  checkForRecoveryDeletedItem();
  checkForRecoveredItem();
  checkForMultipleRecoveredItems();
  
  setTimeout(() => {
    initializeScrollFromURL();
  }, 500);

  restoreColumnSettings();
  initRowsPerPageSelector();
});

// =============================================
// COLUMN MAPPING - OPTIMIZED VERSION
// =============================================
function buildColumnMap() {
    const headerRow = document.querySelector('.itemTable thead tr');
    if (!headerRow) {
        console.error('No table header found!');
        return {};
    }
    
    const headers = Array.from(headerRow.cells);
    console.log('=== BUILDING COLUMN MAP (Deleted Items) ===');
    
    const mappings = [
        { keywords: ['#', 'number'], property: 'rowNumber' },
        { keywords: ['item id', 'id'], property: 'itemId' },
        { keywords: ['category'], property: 'category' },
        { keywords: ['image', 'photo'], property: 'image' },
        { keywords: ['serial'], property: 'serialNumber' },
        { keywords: ['item name', 'name'], property: 'itemName' },
        { keywords: ['brand'], property: 'brand' },
        { keywords: ['model'], property: 'model' },
        { keywords: ['total quantity', 'quantity'], property: 'totalQuantity' },
        { keywords: ['available quantity', 'available'], property: 'availableQuantity' },
        { keywords: ['date acquired', 'acquired'], property: 'dateAcquired' },
        { keywords: ['status'], property: 'status' },
        { keywords: ['deleted by'], property: 'deletedBy' },
        { keywords: ['deleted date'], property: 'deletedDate' },
        { keywords: ['deleted time'], property: 'deletedTime' },
        { keywords: ['actions', 'action'], property: 'actions' }
    ];
    
    columnMap = {};
    
    headers.forEach((header, index) => {
        const headerText = header.textContent.trim().toLowerCase().replace(/\s+/g, ' ');
        let matched = false;
        
        for (const mapping of mappings) {
            if (mapping.keywords.some(keyword => headerText.includes(keyword.toLowerCase()))) {
                columnMap[mapping.property] = index;
                console.log(` Mapped "${mapping.property}" to column ${index}: "${header.textContent.trim()}"`);
                matched = true;
                break;
            }
        }
        
        if (!matched) {
            console.log(` Unmapped column ${index}: "${header.textContent.trim()}"`);
        }
    });
    
    // Apply fallback mapping for required columns
    const fallbackMap = {
        rowNumber: 0, itemId: 1, category: 2, image: 3, serialNumber: 4,
        itemName: 5, brand: 6, model: 7, totalQuantity: 8, availableQuantity: 9,
        dateAcquired: 10, status: 11, deletedBy: 12, deletedDate: 13,
        deletedTime: 14, actions: 15
    };
    
    Object.keys(fallbackMap).forEach(key => {
        if (columnMap[key] === undefined) {
            columnMap[key] = fallbackMap[key];
            console.log(`🔁 Fallback mapped "${key}" to column ${fallbackMap[key]}`);
        }
    });
    
    console.log('=== FINAL COLUMN MAP (Deleted Items) ===', columnMap);
    return columnMap;
}

function getCellData(row, property) {
    const index = columnMap[property];
    if (index === undefined || !row.cells[index]) {
        return '';
    }
    return row.cells[index].textContent || '';
}

// =============================================
// SCROLL TO DELETED ITEM FUNCTION - OPTIMIZED
// =============================================
window.scrollToDeletedItem = function(itemId) {
    if (!itemId || isRedirectingToDeletedItem) return;
    
    isRedirectingToDeletedItem = true;
    targetDeletedItemId = itemId;
    
    if (typeof resetAllFilters === 'function') {
        resetAllFilters();
    }
    
    setTimeout(() => attemptScroll(itemId), 500);
};

function attemptScroll(itemId) {
    const allItemRows = Array.from(document.querySelectorAll('#inventoryTableBody tr[data-item-id]'));
    const targetRow = allItemRows.find(row => row.getAttribute('data-item-id') === itemId);
    const rowIndex = allItemRows.findIndex(row => row.getAttribute('data-item-id') === itemId);
    
    if (rowIndex !== -1 && targetRow) {
        const itemsPerPage = rowsPerPage;
        const targetPage = Math.floor(rowIndex / itemsPerPage) + 1;
        
        if (thisCurrentPage !== targetPage) {
            thisCurrentPage = targetPage;
            setTimeout(() => {
                displayPage(targetPage);
                setTimeout(() => scrollToDeletedItemOnCurrentPage(itemId), 300);
            }, 100);
        } else {
            scrollToDeletedItemOnCurrentPage(itemId);
        }
    } else {
        showDeletedItemNotFoundMessage(itemId);
    }
}

function scrollToDeletedItemOnCurrentPage(itemId) {
    const targetRow = document.querySelector(`tr[data-item-id="${itemId}"]`);
    
    if (targetRow && targetRow.style.display !== 'none') {
        targetRow.classList.add('highlight-item');
        
        setTimeout(() => {
            targetRow.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center',
                inline: 'nearest'
            });
            targetRow.classList.add('highlight-item-strong');
        }, 100);
        
        setTimeout(() => {
            targetRow.classList.remove('highlight-item-strong');
        }, 3000);
        
        setTimeout(() => {
            targetRow.classList.remove('highlight-item');
            cleanupScrollState(itemId);
        }, 6000);
    } else {
        showDeletedItemNotFoundMessage(itemId);
    }
}

function cleanupScrollState(itemId) {
    isRedirectingToDeletedItem = false;
    targetDeletedItemId = null;
    
    const url = new URL(window.location);
    if (url.searchParams.get('item_id') === itemId) {
        url.searchParams.delete('item_id');
        window.history.replaceState({}, document.title, url);
    }
}

// =============================================
// ALERT FUNCTIONS
// =============================================
function showDeletedItemNotFoundMessage(itemId) {
    if (isShowingDeletedItemNotFoundAlert) return;
    
    isShowingDeletedItemNotFoundAlert = true;
    
    Swal.fire({
        icon: 'warning',
        title: 'Deleted Item Not Found',
        html: `The deleted item (ID: <b>${itemId}</b>) could not be found in the current view.`,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3085d6',
        background: '#fff',
    }).then(() => {
        cleanupScrollState(itemId);
        isShowingDeletedItemNotFoundAlert = false;
    });
}

// =============================================
// INITIALIZATION FUNCTIONS
// =============================================
function initializeScrollFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemId = urlParams.get('item_id');
    
    if (itemId) {
        setTimeout(() => scrollToDeletedItem(itemId), 1000);
    }
}

function initRowsPerPageSelector() {
    const rowsPerPageSelect = document.getElementById("rowsPerPageSelect");
    
    if (rowsPerPageSelect) {
        const savedRowsPerPage = localStorage.getItem('deletedItemsTableRowsPerPage');
        if (savedRowsPerPage) {
            rowsPerPageSelect.value = savedRowsPerPage;
            rowsPerPage = parseInt(savedRowsPerPage);
        }
        
        rowsPerPageSelect.addEventListener('change', function() {
            rowsPerPage = parseInt(this.value);
            thisCurrentPage = 1;
            displayPage(thisCurrentPage);
            updateItemCounts();
            localStorage.setItem('deletedItemsTableRowsPerPage', this.value);
        });
    }
}

function initializeDOMCache() {
  domElements = {
      tableBody: document.getElementById("inventoryTableBody"),
      pagination: document.getElementById("pagination"),
      searchInput: document.getElementById("searchItem"),
      dateFrom: document.getElementById("dateFrom"),
      dateTo: document.getElementById("dateTo"),
      deletedDateFrom: document.getElementById("deletedDateFrom"),
      deletedDateTo: document.getElementById("deletedDateTo"),
      categoryFilter: document.getElementById("categoryFilterContainer"),
      qtyFilter: document.getElementById("quantityFilterContainer"),
      dateFilter: document.getElementById("dateFilterContainer"),
      deletedDateFilter: document.getElementById("deletedDateFilterContainer"),
      deletedByFilter: document.getElementById("deletedByFilterContainer"),
      columnFilter: document.getElementById("columnFilterContainer"), 
      rowsPerPageSelect: document.getElementById("rowsPerPageSelect")
  };
}

// =============================================
// TABLE INITIALIZATION - OPTIMIZED
// =============================================
function initializeTable() {
  allRows = Array.from(domElements.tableBody.querySelectorAll("tr"));
  
  allRows.forEach((row, index) => {
      const viewBtn = row.querySelector('.action-btn.view');
      const itemId = viewBtn ? viewBtn.dataset.id : '';
      
      if (itemId) {
          row.setAttribute('data-item-id', itemId);
      }
      
      row._data = {
          category: getCellData(row, 'category').toLowerCase().trim(),
          serialNumber: getCellData(row, 'serialNumber').toLowerCase(),
          itemName: getCellData(row, 'itemName').toLowerCase(),
          brand: getCellData(row, 'brand').toLowerCase().trim(),
          model: getCellData(row, 'model').toLowerCase(),
          totalQuantity: parseInt(getCellData(row, 'totalQuantity') || 0),
          availableQuantity: parseInt(getCellData(row, 'availableQuantity') || 0),
          dateAcquired: getCellData(row, 'dateAcquired').trim(),
          status: getCellData(row, 'status').trim(),
          deletedBy: getCellData(row, 'deletedBy').trim(),
          deletedDate: getCellData(row, 'deletedDate').trim(),
          deletedTime: getCellData(row, 'deletedTime').trim(),
          itemId: itemId,
          dateValue: parseTableDate(getCellData(row, 'dateAcquired').trim()),
          deletedDateValue: parseTableDate(getCellData(row, 'deletedDate').trim())
      };
  });
  
  filteredRows = [...allRows];
  populateCategoryFilters();
  displayPage(1);
  updateItemCounts();
}

// =============================================
// TABLE DISPLAY & PAGINATION - OPTIMIZED
// =============================================
function displayPage(page = 1) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    requestAnimationFrame(() => {
        allRows.forEach(row => row.style.display = "none");
        
        for (let i = start; i < Math.min(end, filteredRows.length); i++) {
            if (filteredRows[i]) {
                filteredRows[i].style.display = "";
            }
        }
        
        updatePagination(page);
        updatePageInfo();
        
        if (isRedirectingToDeletedItem && targetDeletedItemId && page === thisCurrentPage) {
            setTimeout(() => {
                const targetRow = document.querySelector(`tr[data-item-id="${targetDeletedItemId}"]`);
                if (targetRow && targetRow.style.display !== 'none') {
                    targetRow.classList.add('highlight-item');
                }
            }, 200);
        }
    });
}

function updatePagination(page) {
  const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
  
  if (totalPages <= 1) {
      domElements.pagination.style.display = "none";
      domElements.pagination.innerHTML = "";
      return;
  }
  
  domElements.pagination.style.display = "flex";
  const newHTML = generatePaginationHTML(page, totalPages);
  
  if (domElements.pagination.innerHTML !== newHTML) {
      domElements.pagination.innerHTML = newHTML;
      attachPaginationEvents(page, totalPages);
  }
}

function generatePaginationHTML(page, totalPages) {
  let html = '';
  const range = 2;
  
  // First and previous buttons
  if (page > 1) {
      html += `<a href="#" class="prev-next" data-page="1" title="First page"><i class="fas fa-angle-double-left"></i></a>`;
      html += `<a href="#" class="prev-next" data-page="${page - 1}" title="Previous page"><i class="fas fa-chevron-left"></i></a>`;
  }
  
  // Page numbers
  for (let i = Math.max(1, page - range); i <= Math.min(totalPages, page + range); i++) {
      html += `<a href="#" class="${i === page ? 'active' : ''}" data-page="${i}">${i}</a>`;
  }
  
  // Next and last buttons
  if (page < totalPages) {
      html += `<a href="#" class="prev-next" data-page="${page + 1}" title="Next page"><i class="fas fa-chevron-right"></i></a>`;
      html += `<a href="#" class="prev-next" data-page="${totalPages}" title="Last page"><i class="fas fa-angle-double-right"></i></a>`;
  }
  
  return html;
}

function attachPaginationEvents(page, totalPages) {
  domElements.pagination.querySelectorAll('a[data-page]').forEach(link => {
      link.addEventListener("click", function(e) {
          e.preventDefault();
          const newPage = parseInt(this.getAttribute('data-page'));
          if (newPage >= 1 && newPage <= totalPages) {
              thisCurrentPage = newPage;
              displayPage(thisCurrentPage);
          }
      });
  });
}

// =============================================
// FILTERING & SEARCH - NEW PATTERN
// =============================================
function filterItems() {
  const filterKey = generateFilterKey();
  
  if (filterCache.has(filterKey)) {
      filteredRows = filterCache.get(filterKey);
      updateDisplay();
      return;
  }
  
  requestAnimationFrame(() => {
      applyFiltersAndSort();
      
      // Cache management
      if (filterCache.size > 50) filterCache.clear();
      filterCache.set(filterKey, filteredRows);
      
      updateDisplay();
  });
}

function applyFiltersAndSort() {
  const searchValue = (domElements.searchInput?.value || '').toLowerCase();
  
  console.log('=== APPLYING FILTERS AND SORT ===');
  console.log('Current filters:', currentFilters);
  console.log('Current sort:', currentSort);
  
  // First apply all filters from the full dataset
  filteredRows = allRows.filter(row => {
      const data = row._data;
      
      // Category filter
      if (currentFilters.category.length > 0 && !currentFilters.category.includes(data.category)) {
          return false;
      }
      
      // Quantity filter - FIXED: Now using availableQuantity
      if (currentFilters.quantity === "out" && data.availableQuantity !== 0) return false;
      if (currentFilters.quantity === "available" && data.availableQuantity <= 0) return false;
      
      // Date acquired filter
      if (!passesDateFilter(data.dateAcquired)) return false;
      
      // Deleted date filter
      if (!passesDeletedDateFilter(data.deletedDate)) return false;
      
      // Deleted by filter - CASE INSENSITIVE FIX
      if (currentFilters.deletedBy.length > 0) {
          const rowDeletedBy = data.deletedBy.toLowerCase().trim();
          let matches = false;
          for (const filterName of currentFilters.deletedBy) {
              if (rowDeletedBy.includes(filterName.toLowerCase())) {
                  matches = true;
                  break;
              }
          }
          if (!matches) {
              console.log('Filtered out by deletedBy:', data.itemName, 'deletedBy:', data.deletedBy);
              return false;
          }
      }
      
      // Search filter
      if (searchValue && !passesSearchFilter(data, searchValue)) return false;
      
      return true;
  });
  
  console.log('After filtering:', filteredRows.length, 'rows');
  
  // Then apply current sort if exists
  if (currentSort.field) {
      applyCurrentSort();
  }
}

function passesDateFilter(dateAcquired) {
  if (!currentFilters.dateFrom && !currentFilters.dateTo) return true;
  
  const itemDate = parseTableDate(dateAcquired);
  if (!itemDate) return false;
  
  if (currentFilters.dateFrom) {
      const fromDate = new Date(currentFilters.dateFrom);
      fromDate.setHours(0, 0, 0, 0);
      if (itemDate < fromDate) return false;
  }
  
  if (currentFilters.dateTo) {
      const toDate = new Date(currentFilters.dateTo);
      toDate.setHours(23, 59, 59, 999);
      if (itemDate > toDate) return false;
  }
  
  return true;
}

function passesDeletedDateFilter(deletedDate) {
  if (!currentFilters.deletedDateFrom && !currentFilters.deletedDateTo) return true;
  
  const itemDeletedDate = parseTableDate(deletedDate);
  if (!itemDeletedDate) return false;
  
  if (currentFilters.deletedDateFrom) {
      const fromDate = new Date(currentFilters.deletedDateFrom);
      fromDate.setHours(0, 0, 0, 0);
      if (itemDeletedDate < fromDate) return false;
  }
  
  if (currentFilters.deletedDateTo) {
      const toDate = new Date(currentFilters.deletedDateTo);
      toDate.setHours(23, 59, 59, 999);
      if (itemDeletedDate > toDate) return false;
  }
  
  return true;
}

function passesSearchFilter(data, searchValue) {
  return data.itemId.includes(searchValue) ||
         data.itemName.includes(searchValue) || 
         data.model.includes(searchValue) || 
         data.brand.includes(searchValue) ||
         data.serialNumber.includes(searchValue) ||
         data.category.includes(searchValue) ||
         data.deletedBy.includes(searchValue);
}

function generateFilterKey() {
  const searchValue = domElements.searchInput?.value || '';
  return `${currentFilters.category.join(',')}|${currentFilters.quantity}|${currentFilters.dateFrom}|${currentFilters.dateTo}|${currentFilters.deletedBy.join(',')}|${currentFilters.deletedDateFrom}|${currentFilters.deletedDateTo}|${searchValue}|${currentSort.field}|${currentSort.order}`;
}

// =============================================
// SORTING FUNCTIONS - NEW PATTERN
// =============================================
function sortByQuantity(order = "asc") {
  console.log('=== SORTING BY QUANTITY ===');
  console.log('Current filters:', currentFilters);
  console.log('Current sort:', currentSort);
  
  // Update sort state
  currentSort.field = 'availableQuantity';
  currentSort.order = order;
  
  // Clear cache since we're changing sort
  filterCache.clear();
  
  // Re-apply current filters with new sort order
  applyFiltersAndSort();
  
  // Reset to first page and update display
  thisCurrentPage = 1;
  updateTableDOM();
  updateItemCounts();
}

function applyCurrentSort() {
  if (!currentSort.field) return;
  
  console.log('Applying sort:', currentSort);
  
  filteredRows.sort((a, b) => {
      const aVal = a._data[currentSort.field];
      const bVal = b._data[currentSort.field];
      
      // Handle numeric sorting for quantities
      if (currentSort.field === 'availableQuantity') {
          const numA = parseInt(aVal) || 0;
          const numB = parseInt(bVal) || 0;
          const result = currentSort.order === "asc" ? numA - numB : numB - numA;
          console.log(`Sorting: ${numA} vs ${numB} = ${result}`);
          return result;
      }
      
      // Default string sorting for other fields
      if (aVal < bVal) return currentSort.order === "asc" ? -1 : 1;
      if (aVal > bVal) return currentSort.order === "asc" ? 1 : -1;
      return 0;
  });
  
  console.log('First 5 items after sort:', filteredRows.slice(0, 5).map(r => r._data.availableQuantity));
}

function updateTableDOM() {
  const tableBody = document.getElementById("inventoryTableBody");
  
  console.log('Updating table DOM with', filteredRows.length, 'rows');
  console.log('Current sort:', currentSort);
  console.log('Current filters:', currentFilters);
  
  // Clear and rebuild table in correct order
  tableBody.innerHTML = '';
  filteredRows.forEach(row => tableBody.appendChild(row));
  
  // Update display
  updateRowNumbers();
  displayPage(thisCurrentPage);
}

// =============================================
// FILTER CONTROLS WITH CLOSE ON APPLY
// =============================================
function initFilterControls() {
  const filterToggles = [
      { id: "toggleCategoryFilter", container: domElements.categoryFilter },
      { id: "toggleQtyFilter", container: domElements.qtyFilter },
      { id: "toggleDateFilter", container: domElements.dateFilter },
      { id: "toggleDeletedDateFilter", container: domElements.deletedDateFilter },
      { id: "toggleDeletedByFilter", container: domElements.deletedByFilter },
      { id: "toggleColumnFilter", container: domElements.columnFilter }
  ];

  function closeAllFilters() {
      filterToggles.forEach(({ container }) => {
          if (container) container.classList.add("hidden");
      });
  }

  // Global click handler for filter toggles
  document.addEventListener('click', function(e) {
      const toggle = e.target.closest('[id^="toggle"]');
      if (toggle) {
          e.stopPropagation();
          const filterConfig = filterToggles.find(f => f.id === toggle.id);
          if (filterConfig?.container) {
              const isHidden = filterConfig.container.classList.contains("hidden");
              closeAllFilters();
              if (isHidden) {
                  filterConfig.container.classList.remove("hidden");
              }
          }
      } else {
          closeAllFilters();
      }
  });

  // Prevent filter containers from closing when clicked
  filterToggles.forEach(({ container }) => {
      container?.addEventListener('click', e => e.stopPropagation());
  });

  // Setup individual filter handlers
  setupCategoryFilters();
  setupQuantityFilters();
  setupDateFilters();
  setupDeletedDateFilters();
  setupDeletedByFilters();

  // Reset all filters
  document.getElementById("resetAllFiltersBtn")?.addEventListener("click", resetAllFilters);

  // Search functionality
  domElements.searchInput?.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => filterItems(), 150);
  });

  function setupCategoryFilters() {
      document.getElementById("filterByCategoryBtn")?.addEventListener("click", () => {
          const categoryCheckboxes = document.querySelectorAll('input[name="categoryFilter"]:checked');
          currentFilters.category = Array.from(categoryCheckboxes).map(cb => cb.value.toLowerCase());
          filterItems();
          closeAllFilters();
      });

      document.getElementById("resetCategoryFilterBtn")?.addEventListener("click", () => {
          document.querySelectorAll('input[name="categoryFilter"]').forEach(cb => cb.checked = false);
          currentFilters.category = [];
          filterItems();
          closeAllFilters();
      });
  }

  function setupQuantityFilters() {
      document.getElementById("sortLowToHigh")?.addEventListener("click", () => {
          sortByQuantity("asc");
          closeAllFilters();
      });

      document.getElementById("sortHighToLow")?.addEventListener("click", () => {
          sortByQuantity("desc");
          closeAllFilters();
      });
  }

  function setupDateFilters() {
      document.getElementById("filterByDateBtn")?.addEventListener("click", () => {
          currentFilters.dateFrom = domElements.dateFrom.value;
          currentFilters.dateTo = domElements.dateTo.value;
          filterItems();
          closeAllFilters();
      });

      document.getElementById("resetDateFilterBtn")?.addEventListener("click", () => {
          domElements.dateFrom.value = "";
          domElements.dateTo.value = "";
          currentFilters.dateFrom = "";
          currentFilters.dateTo = "";
          filterItems();
          closeAllFilters();
      });
  }

  function setupDeletedDateFilters() {
      document.getElementById("filterByDeletedDateBtn")?.addEventListener("click", () => {
          currentFilters.deletedDateFrom = domElements.deletedDateFrom.value;
          currentFilters.deletedDateTo = domElements.deletedDateTo.value;
          filterItems();
          closeAllFilters();
      });

      document.getElementById("resetDeletedDateFilterBtn")?.addEventListener("click", () => {
          domElements.deletedDateFrom.value = "";
          domElements.deletedDateTo.value = "";
          currentFilters.deletedDateFrom = "";
          currentFilters.deletedDateTo = "";
          filterItems();
          closeAllFilters();
      });
  }

  function setupDeletedByFilters() {
      document.getElementById("filterByDeletedByBtn")?.addEventListener("click", () => {
          const deletedByCheckboxes = document.querySelectorAll('input[name="deletedByFilter"]:checked');
          currentFilters.deletedBy = Array.from(deletedByCheckboxes).map(cb => cb.value.toLowerCase());
          filterItems();
          closeAllFilters();
      });

      document.getElementById("resetDeletedByBtn")?.addEventListener("click", () => {
          document.querySelectorAll('input[name="deletedByFilter"]').forEach(cb => cb.checked = false);
          currentFilters.deletedBy = [];
          filterItems();
          closeAllFilters();
      });
  }
}

function resetAllFilters() {
  // Reset category filter
  document.querySelectorAll('input[name="categoryFilter"]').forEach(cb => cb.checked = false);
  currentFilters.category = [];
  
  // Reset deleted by filter
  document.querySelectorAll('input[name="deletedByFilter"]').forEach(cb => cb.checked = false);
  currentFilters.deletedBy = [];
  
  // Reset quantity filter
  currentFilters.quantity = "";
  
  // Reset date filters
  domElements.dateFrom.value = "";
  domElements.dateTo.value = "";
  currentFilters.dateFrom = "";
  currentFilters.dateTo = "";
  
  // Reset deleted date filters
  domElements.deletedDateFrom.value = "";
  domElements.deletedDateTo.value = "";
  currentFilters.deletedDateFrom = "";
  currentFilters.deletedDateTo = "";
  
  // Reset search
  domElements.searchInput.value = "";
  
  // Clear sort when resetting all filters
  currentSort.field = null;
  currentSort.order = null;
  
  filterCache.clear();
  dateCache.clear();
  filterItems();
}

// =============================================
// COLUMN FILTERING - OPTIMIZED
// =============================================
const columnFilterKey = "deletedItemsColumnFilterSettings";

function restoreColumnSettings() {
  const savedSettings = JSON.parse(localStorage.getItem(columnFilterKey)) || {};
  document.querySelectorAll("#columnFilterContainer input[type='checkbox']").forEach(cb => {
    const colIndex = cb.getAttribute("data-column");
    cb.checked = savedSettings[colIndex] !== false;
    cb.dispatchEvent(new Event("change"));
  });
}

function saveColumnSettings() {
  const settings = {};
  document.querySelectorAll("#columnFilterContainer input[type='checkbox']").forEach(cb => {
    const colIndex = cb.getAttribute("data-column");
    settings[colIndex] = cb.checked;
  });
  localStorage.setItem(columnFilterKey, JSON.stringify(settings));
}

function toggleColumnVisibility(colIndex, show) {
  const table = document.querySelector(".itemTable");
  const displayValue = show ? "" : "none";
  
  table.querySelectorAll(`thead th:nth-child(${colIndex + 1})`)
    .forEach(th => th.style.display = displayValue);

  table.querySelectorAll(`tbody tr td:nth-child(${colIndex + 1})`)
    .forEach(td => td.style.display = displayValue);
}

function initColumnFilter() {
  document.querySelectorAll("#columnFilterContainer input[type='checkbox']")
    .forEach(checkbox => {
      checkbox.addEventListener("change", function () {
        const colIndex = parseInt(this.getAttribute("data-column"));
        toggleColumnVisibility(colIndex, this.checked);
        saveColumnSettings();
      });
    });

  document.getElementById("resetColumnFilterBtn").addEventListener("click", () => {
    document.querySelectorAll("#columnFilterContainer input[type='checkbox']").forEach(cb => {
      if (!cb.checked) {
        cb.checked = true;
        toggleColumnVisibility(parseInt(cb.getAttribute("data-column")), true);
      }
    });
    localStorage.removeItem(columnFilterKey);
  });
}

// =============================================
// UTILITY FUNCTIONS
// =============================================
function populateCategoryFilters() {
  const categoryCheckboxes = document.getElementById('categoryCheckboxes');
  if (!categoryCheckboxes) return;
  
  const categories = new Set();
  allRows.forEach(row => {
      const category = row._data.category;
      if (category && category !== 'uncategorized') {
          categories.add(category);
      }
  });
  
  const sortedCategories = Array.from(categories).sort();
  
  categoryCheckboxes.innerHTML = '';
  sortedCategories.forEach(category => {
      const label = document.createElement('label');
      label.innerHTML = `
          <input type="checkbox" name="categoryFilter" value="${category}">
          ${category.charAt(0).toUpperCase() + category.slice(1)}
      `;
      categoryCheckboxes.appendChild(label);
  });
}

function updateDisplay() {
  updateRowNumbers();
  showNoResultsMessage(filteredRows.length === 0);
  
  if (!isRedirectingToDeletedItem) {
      thisCurrentPage = 1;
  }
  
  displayPage(thisCurrentPage);
  updateItemCounts();
}

function updateRowNumbers() {
  filteredRows.forEach((row, index) => {
      const cell = row.cells[columnMap.rowNumber || 0];
      if (cell) {
          cell.textContent = index + 1;
      }
  });
}

function showNoResultsMessage(show) {
  let noResultsRow = document.getElementById('noResultsMessage');
  
  if (show && !noResultsRow) {
      noResultsRow = document.createElement("tr");
      noResultsRow.id = 'noResultsMessage';
      noResultsRow.innerHTML = `<td colspan="17" style="text-align:center; color:#666; padding:20px;">No deleted items found matching your filters.</td>`;
      domElements.tableBody.appendChild(noResultsRow);
  } else if (!show && noResultsRow) {
      noResultsRow.remove();
  }
}

function updateItemCounts() {
  const totalItems = allRows.length;
  const filteredItems = filteredRows.length;
  const isFiltered = filteredItems !== totalItems;
  
  updateCountElement('totalItemsCount', totalItems);
  updateCountElement('totalItemsCount2', totalItems);
  updateCountElement('visibleItemsCount', filteredItems);
  updateCountElement('visibleCount', filteredItems);
  updateCountElement('totalCount', totalItems);
  updateCountElement('displayedCount', filteredItems);
  updateCountElement('totalItems', totalItems);
  
  const filteredCountElement = document.getElementById('filteredItemsCount');
  if (filteredCountElement) {
      filteredCountElement.style.display = isFiltered ? 'inline' : 'none';
  }
  
  updateActiveFiltersDisplay();
  updatePageInfo();
}

function updateActiveFiltersDisplay() {
    const activeFiltersElement = document.getElementById('activeFilters');
    if (!activeFiltersElement) return;
    
    const activeFilters = [];
    
    // Check category filters
    if (currentFilters.category.length > 0) {
        activeFilters.push(`Category: ${currentFilters.category.length} selected`);
    }
    
    // Check quantity filter
    if (currentFilters.quantity === "out") {
        activeFilters.push("Out of Stock");
    } else if (currentFilters.quantity === "available") {
        activeFilters.push("Available");
    }
    
    // Check date filters
    if (currentFilters.dateFrom || currentFilters.dateTo) {
        activeFilters.push(`Date Acquired: ${currentFilters.dateFrom || 'any'} to ${currentFilters.dateTo || 'any'}`);
    }
    
    // Check deleted date filters
    if (currentFilters.deletedDateFrom || currentFilters.deletedDateTo) {
        activeFilters.push(`Deleted Date: ${currentFilters.deletedDateFrom || 'any'} to ${currentFilters.deletedDateTo || 'any'}`);
    }
    
    // Check deleted by filters
  // In the applyFiltersAndSort function, update the deleted by filter section:
if (currentFilters.deletedBy.length > 0) {
  const rowDeletedBy = data.deletedBy.toLowerCase().trim();
  let matches = false;
  for (const filterName of currentFilters.deletedBy) {
      if (rowDeletedBy.includes(filterName.toLowerCase())) {
          matches = true;
          break;
      }
  }
  if (!matches) {
      return false;
  }
}
    
    // Check search
    const searchValue = domElements.searchInput?.value || '';
    if (searchValue) {
        activeFilters.push(`Search: "${searchValue}"`);
    }
    
    // Check sort
    if (currentSort.field) {
        const sortDirection = currentSort.order === "asc" ? "Low to High" : "High to Low";
        activeFilters.push(`Sorted by: Quantity (${sortDirection})`);
    }
    
    if (activeFilters.length > 0) {
        activeFiltersElement.innerHTML = `<strong>Active filters:</strong> ${activeFilters.join(' • ')}`;
        activeFiltersElement.style.color = '#495057';
    } else {
        activeFiltersElement.innerHTML = 'No active filters';
        activeFiltersElement.style.color = '#6c757d';
    }
}

function updatePageInfo() {
  const startIndex = (thisCurrentPage - 1) * rowsPerPage + 1;
  const endIndex = Math.min(thisCurrentPage * rowsPerPage, filteredRows.length);
  const totalItems = filteredRows.length;
  
  const pageInfoElement = document.getElementById('pageInfo');
  if (pageInfoElement) {
      pageInfoElement.textContent = totalItems > 0 
          ? `Showing ${startIndex} to ${endIndex} of ${totalItems} entries`
          : 'No entries to show';
  }
}

function updateCountElement(elementId, count) {
  const element = document.getElementById(elementId);
  if (element) {
      element.textContent = count;
  }
}

// =============================================
// DATE PARSING - OPTIMIZED
// =============================================
const dateCache = new Map();
function parseTableDate(dateString) {
    if (!dateString || ['N/A', '—', ''].includes(dateString)) {
        return null;
    }
    
    if (dateCache.has(dateString)) {
        return dateCache.get(dateString);
    }
    
    try {
        let result = null;
        
        // Format: "Mar-15-2024"
        if (dateString.match(/^[A-Za-z]{3}-\d{1,2}-\d{4}$/)) {
            const monthNames = {
                "Jan": 0, "Feb": 1, "Mar": 2, "Apr": 3, "May": 4, "Jun": 5,
                "Jul": 6, "Aug": 7, "Sep": 8, "Oct": 9, "Nov": 10, "Dec": 11
            };
            const parts = dateString.split('-');
            const month = monthNames[parts[0]];
            const day = parseInt(parts[1]);
            const year = parseInt(parts[2]);
            
            if (month !== undefined && !isNaN(day) && !isNaN(year)) {
                result = new Date(year, month, day);
                // Validate date
                if (result.getDate() !== day || result.getMonth() !== month || result.getFullYear() !== year) {
                    result = null;
                }
            }
        }
        // Format: "2024-03-15" (ISO)
        else if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
            result = new Date(dateString);
        }
        // Try native parsing
        else {
            result = new Date(dateString);
        }
        
        // Final validation
        if (!result || isNaN(result.getTime())) {
            console.warn('Invalid date:', dateString);
            result = null;
        }
        
        dateCache.set(dateString, result);
        return result;
    } catch (e) {
        console.error('Date parsing error:', e, 'for date:', dateString);
        return null;
    }
}

// =============================================
// TABLE ACTIONS
// =============================================
function initTableActions() {
  document.querySelector('.itemTable')?.addEventListener('click', function(e) {
      const deleteBtn = e.target.closest('.action-btn.delete');
      if (deleteBtn) return handleDeleteItem(deleteBtn);

      const recoverBtn = e.target.closest('.action-btn.restore');
      if (recoverBtn) return handleRecoverItem(recoverBtn);

      const viewBtn = e.target.closest('.action-btn.view');
      if (viewBtn) return handleViewItem(viewBtn);
  });
}

function handleDeleteItem(deleteBtn) {
  const itemId = deleteBtn.getAttribute('data-id');
  const itemName = deleteBtn.getAttribute('data-name');

  Swal.fire({
      title: 'Are you sure?',
      text: `You are about to permanently delete "${itemName}". This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
  }).then(result => {
      if (result.isConfirmed) {
          sessionStorage.setItem('deletedItemRecoveryName', itemName);
          window.location.href = `/templates/inventory/function/finalDeleteItem.php?id=${itemId}&source=deleted`;
      }
  });
}

function handleRecoverItem(recoverBtn) {
  const itemId = recoverBtn.getAttribute('data-id');
  const itemName = recoverBtn.getAttribute('data-name');

  Swal.fire({
    title: 'Recover Item?',
    text: `Are you sure you want to recover "${itemName}" back to the inventory?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, recover it!',
    cancelButtonText: 'Cancel'
  }).then(result => {
    if (result.isConfirmed) {
      sessionStorage.setItem('recoveredItemName', itemName);
      let url = `/templates/inventory/function/recoverItem.php?id=${itemId}`;
      window.location.href = url;
    }
  });
}

function retrieveAllItems() {
  Swal.fire({
    title: 'Recover All Items?',
    text: 'This will restore all deleted items back to the inventory. This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, recover all!',
    cancelButtonText: 'Cancel',
  
  }).then((result) => {
    if (result.isConfirmed) {
      sessionStorage.setItem('recoveringAllItems', 'true');
      window.location.href = '/templates/inventory/function/recoverAllItems.php';
    }
  });
}

let scrollPosition = 0;

function handleViewItem(viewBtn) {
  console.log('handleViewItem called', viewBtn);
  
  const modal = document.getElementById("itemViewModal");
  if (!modal) {
    console.error('View modal not found!');
    return;
  }
  
  console.log('Modal found, setting content...');
  
  const setContent = (id, value, fallback = 'N/A') => {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = value || fallback;
    } else {
      console.error(`Element with id ${id} not found`);
    }
  };

  setContent("view-item-name", viewBtn.dataset.name);
  setContent("view-item-category", viewBtn.dataset.category);
  setContent("view-item-description", viewBtn.dataset.description);
  setContent("view-item-brand", viewBtn.dataset.brand);
  setContent("view-item-model", viewBtn.dataset.model);
  setContent("view-item-serial", viewBtn.dataset.serial);
  setContent("view-item-quantity", viewBtn.dataset.qty);
  setContent("view-available-quantity", viewBtn.dataset.availableQty);
  setContent("view-item-unit", viewBtn.dataset.unit);
  setContent("view-item-unit-cost", viewBtn.dataset.unitcost);
  setContent("view-item-total-cost", viewBtn.dataset.totalcost);
  setContent("view-item-deleted-by", viewBtn.dataset.deletedby);
  
  const deletedAt = viewBtn.dataset.deletedat;
  setContent("view-item-deleted-at", deletedAt ? new Date(deletedAt).toLocaleString() : 'N/A');

  const dateAcquired = viewBtn.dataset.dateAcquired;
  setContent("view-item-date-acquired", 
    dateAcquired ? new Date(dateAcquired).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: '2-digit'
    }).replace(',', '').replace(' ', '-') : 'N/A'
  );
  
  setContent("view-item-created-at", viewBtn.dataset.created);
  setContent("view-item-status", viewBtn.dataset.itemstatus);

  const photoElement = document.getElementById("view-item-photo");
  const photo = viewBtn.dataset.photo;
  if (photoElement) {
    photoElement.src = (photo && photo !== '') ? '/' + photo : '/images/user-profile/default-image.jpg';
  }

  scrollPosition = window.scrollY;
  document.body.style.position = "fixed";
  document.body.style.top = `-${scrollPosition}px`;
  document.body.style.overflow = "hidden";
  document.body.style.width = "100%";

  modal.style.display = "flex";
  console.log('Modal should be visible now');
}

function closeItemView() {
  document.getElementById("itemViewModal").style.display = "none";
  document.body.style.position = "";
  document.body.style.top = "";
  document.body.style.overflow = "";
  document.body.style.width = "";
  window.scrollTo(0, scrollPosition);
}

// =============================================
// NOTIFICATION HANDLERS
// =============================================
function checkForRecoveryDeletedItem() {
  const deletedName = sessionStorage.getItem('deletedItemRecoveryName');
  if (deletedName) {
      setTimeout(() => {
          Swal.fire({ 
              icon: 'success', 
              title: 'Permanently Deleted!', 
              html: `Item <b>${deletedName}</b> was permanently deleted.`, 
              confirmButtonColor: '#3085d6' 
          }).then(() => {
              const url = new URL(window.location.href);
              url.searchParams.delete('deleted');
              url.searchParams.delete('id');
              window.history.replaceState({}, document.title, url.pathname);
          });
          sessionStorage.removeItem('deletedItemRecoveryName');
      }, 100);
  }
}

function checkForRecoveredItem() {
  const recoveredName = sessionStorage.getItem('recoveredItemName');
  if (recoveredName) {
    Swal.fire({
      icon: 'success',
      title: 'Recovered!',
      html: `Item <b>${recoveredName}</b> has been restored to inventory.`,
      confirmButtonColor: '#3085d6'
    }).then(() => {
      const url = new URL(window.location.href);
      url.searchParams.delete('recovered');
      url.searchParams.delete('id');
      window.history.replaceState({}, document.title, url.pathname + '?' + url.searchParams.toString());
      
      sessionStorage.removeItem('recoveredItemName');
    });
    
    setTimeout(() => {
      sessionStorage.removeItem('recoveredItemName');
    }, 5000);
  }
}

function checkForMultipleRecoveredItems() {
  const recoveringAll = sessionStorage.getItem('recoveringAllItems');
  const recoveredCount = sessionStorage.getItem('recoveredItemsCount');
  
  if (recoveringAll && recoveredCount) {
    const count = parseInt(recoveredCount);
    let message = `Successfully recovered ${count} item${count > 1 ? 's' : ''}!`;
    
    Swal.fire({
      icon: 'success',
      title: 'Items Recovered!',
      html: message,
      confirmButtonColor: '#3085d6'
    }).then(() => {
      const url = new URL(window.location.href);
      url.searchParams.delete('recovered');
      window.history.replaceState({}, document.title, url.pathname + '?' + url.searchParams.toString());
      
      sessionStorage.removeItem('recoveringAllItems');
      sessionStorage.removeItem('recoveredItemsCount');
    });
    
    setTimeout(() => {
      sessionStorage.removeItem('recoveringAllItems');
      sessionStorage.removeItem('recoveredItemsCount');
    }, 5000);
  }
}

// =============================================
// EVENT LISTENERS
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    checkForRecoveryDeletedItem();
});

window.addEventListener('load', function() {
    checkForRecoveryDeletedItem();
});

window.addEventListener('pageshow', function() {
    checkForRecoveryDeletedItem();
});

window.addEventListener('beforeunload', function() {
  if (!window.location.href.includes('/recentlyDeleted')) {
    sessionStorage.removeItem('recoveredItemName');
    sessionStorage.removeItem('recoveringAllItems');
    sessionStorage.removeItem('recoveredItemsCount');
    sessionStorage.removeItem('deletedItemRecoveryName');
  }
});

document.addEventListener('visibilitychange', function() {
  if (document.visibilityState === 'hidden') {
    sessionStorage.removeItem('recoveredItemName');
    sessionStorage.removeItem('recoveringAllItems');
    sessionStorage.removeItem('recoveredItemsCount');
    sessionStorage.removeItem('deletedItemRecoveryName');
  }
});

window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemId = urlParams.get('item_id');
    if (itemId) {
        setTimeout(() => scrollToDeletedItem(itemId), 1500);
    }
});

window.addEventListener('popstate', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemId = urlParams.get('item_id');
    if (itemId) {
        setTimeout(() => scrollToDeletedItem(itemId), 800);
    }
});