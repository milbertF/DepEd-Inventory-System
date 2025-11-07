let currentFilters = {
  category: [],
  quantity: "",
  dateFrom: "",
  dateTo: "",
  deletedBy: "",
  deletedDateFrom: "",
  deletedDateTo: ""
};

let allRows = [];
let filteredRows = [];
let thisCurrentPage = 1;
let rowsPerPage = parseInt(localStorage.getItem('deletedItemsTableRowsPerPage')) || 10;
let debounceTimer = null;
let filterCache = new Map();

let domElements = {};

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
});

// =============================================
// SCROLL TO DELETED ITEM FUNCTION
// =============================================
window.scrollToDeletedItem = function(itemId) {
    console.log('scrollToDeletedItem called for:', itemId);
    
    if (!itemId) {
        console.warn('No itemId provided to scrollToDeletedItem');
        return;
    }
    
    if (isRedirectingToDeletedItem) {
        console.log('Already redirecting to a deleted item, skipping...');
        return;
    }
    
    isRedirectingToDeletedItem = true;
    targetDeletedItemId = itemId;
    
    if (typeof resetAllFilters === 'function') {
        console.log('Resetting filters for deleted items...');
        resetAllFilters();
    }
    
    function attemptScroll() {
        console.log('Attempting to scroll to deleted item:', itemId);
        
        const allItemRows = Array.from(document.querySelectorAll('#inventoryTableBody tr[data-item-id]'));
        console.log(`Found ${allItemRows.length} total deleted item rows in DOM`);
        
        const targetRow = allItemRows.find(row => row.getAttribute('data-item-id') === itemId);
        const rowIndex = allItemRows.findIndex(row => row.getAttribute('data-item-id') === itemId);
        
        if (rowIndex !== -1 && targetRow) {
            console.log(`Found deleted item at row index: ${rowIndex}`);
            
            const itemsPerPage = rowsPerPage;
            const targetPage = Math.floor(rowIndex / itemsPerPage) + 1;
            const totalPages = Math.ceil(allItemRows.length / itemsPerPage);
            
            console.log(`Item is on page ${targetPage} of ${totalPages}`);
            
            if (thisCurrentPage !== targetPage) {
                console.log(`Switching from page ${thisCurrentPage} to page ${targetPage}`);
                thisCurrentPage = targetPage;
                
                setTimeout(() => {
                    displayPage(targetPage);
                    
                    setTimeout(() => {
                        scrollToDeletedItemOnCurrentPage(itemId);
                    }, 300);
                }, 100);
            } else {
                console.log('Already on correct page, scrolling directly');
                scrollToDeletedItemOnCurrentPage(itemId);
            }
            
        } else {
            console.warn('Deleted item not found in the table:', itemId);
            showDeletedItemNotFoundMessage(itemId);
        }
    }
    
    function scrollToDeletedItemOnCurrentPage(itemId) {
        console.log('scrollToDeletedItemOnCurrentPage for:', itemId);
        
        const targetRow = document.querySelector(`tr[data-item-id="${itemId}"]`);
        
        if (targetRow && targetRow.style.display !== 'none') {
            console.log('Found visible deleted item row, scrolling and highlighting...');
            
            targetRow.classList.add('highlight-item');
            void targetRow.offsetWidth;
            
            setTimeout(() => {
                targetRow.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center',
                    inline: 'nearest'
                });
            }, 100);
            
            setTimeout(() => {
                targetRow.classList.add('highlight-item-strong');
            }, 500);
            
            setTimeout(() => {
                targetRow.classList.remove('highlight-item-strong');
            }, 3000);
            
            setTimeout(() => {
                targetRow.classList.remove('highlight-item');
                isRedirectingToDeletedItem = false;
                targetDeletedItemId = null;
                
                const url = new URL(window.location);
                if (url.searchParams.get('item_id') === itemId) {
                    url.searchParams.delete('item_id');
                    window.history.replaceState({}, '', url);
                }
            }, 6000);
            
        } else {
            console.warn('Deleted item not visible on current page:', itemId);
            showDeletedItemNotFoundMessage(itemId);
        }
    }
    
    setTimeout(attemptScroll, 500);
};

// =============================================
// ALERT FUNCTIONS
// =============================================
function showDeletedItemNotFoundMessage(itemId) {
    if (isShowingDeletedItemNotFoundAlert) {
        console.log('Alert already showing, skipping...');
        return;
    }
    
    isShowingDeletedItemNotFoundAlert = true;
    
    Swal.fire({
        icon: 'warning',
        title: 'Deleted Item Not Found',
        html: `
            The deleted item (ID: <b>${itemId}</b>) could not be found in the current view.<br>
            It may have been <b>permanently deleted</b>, filtered out, or no longer exists.
        `,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3085d6',
        allowOutsideClick: true,
        allowEscapeKey: true,
        background: '#fff',
    }).then((result) => {
        const url = new URL(window.location);
        if (url.searchParams.get('item_id') === itemId) {
            url.searchParams.delete('item_id');
            window.history.replaceState({}, '', url);
        }
        
        isRedirectingToDeletedItem = false;
        targetDeletedItemId = null;
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
        console.log('Found item_id in URL, will scroll to deleted item:', itemId);
        
        setTimeout(() => {
            scrollToDeletedItem(itemId);
        }, 1000);
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

function initializeTable() {
  allRows = Array.from(domElements.tableBody.querySelectorAll("tr"));
  
  allRows.forEach((row, index) => {
      const cells = row.cells;
      
      const viewBtn = row.querySelector('.action-btn.view');
      const itemId = viewBtn ? viewBtn.dataset.id : '';
      
      if (itemId) {
          row.setAttribute('data-item-id', itemId);
      }
      
      row._data = {
          category: (cells[2]?.textContent || '').toLowerCase().trim(), 
          serialNumber: (cells[4]?.textContent || '').toLowerCase(),
          itemName: (cells[5]?.textContent || '').toLowerCase(), 
          brand: (cells[6]?.textContent || '').toLowerCase().trim(), 
          model: (cells[7]?.textContent || '').toLowerCase(), 
          quantity: parseInt(cells[8]?.textContent || 0), 
          dateAcquired: cells[9]?.textContent.trim(), 
          deletedBy: (cells[11]?.textContent || '').toLowerCase().trim(), 
          deletedDate: cells[12]?.textContent.trim(), 
          deletedTime: cells[13]?.textContent.trim(),
          itemId: itemId
      };
  });
  
  filteredRows = [...allRows];
  populateCategoryFilters();
  displayPage(1);
  updateItemCounts();
}

// =============================================
// TABLE DISPLAY & PAGINATION
// =============================================
function displayPage(page = 1) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    requestAnimationFrame(() => {
        allRows.forEach(row => {
            row.style.display = "none";
        });
        
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
                    console.log('Highlighting deleted item on page change');
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
  
  const currentHTML = domElements.pagination.innerHTML;
  const newHTML = generatePaginationHTML(page, totalPages);
  
  if (currentHTML !== newHTML) {
      domElements.pagination.innerHTML = newHTML;
      attachPaginationEvents(page, totalPages);
  }
}

function generatePaginationHTML(page, totalPages) {
  let html = '';
  const range = 2;
  
  if (page > 1) {
      html += `<a href="#" class="prev-next" data-page="1" title="First page"><i class="fas fa-angle-double-left"></i></a>`;
  }
  
  if (page > 1) {
      html += `<a href="#" class="prev-next" data-page="${page - 1}" title="Previous page"><i class="fas fa-chevron-left"></i></a>`;
  }
  
  for (let i = Math.max(1, page - range); i <= Math.min(totalPages, page + range); i++) {
      const activeClass = i === page ? "active" : "";
      html += `<a href="#" class="${activeClass}" data-page="${i}">${i}</a>`;
  }
  
  if (page < totalPages) {
      html += `<a href="#" class="prev-next" data-page="${page + 1}" title="Next page"><i class="fas fa-chevron-right"></i></a>`;
  }
  
  if (page < totalPages) {
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
// FILTERING & SEARCH
// =============================================
function filterItems() {
  const filterKey = generateFilterKey();
  
  if (filterCache.has(filterKey)) {
      filteredRows = filterCache.get(filterKey);
      updateDisplay();
      return;
  }
  
  const searchValue = (domElements.searchInput?.value || '').toLowerCase();

  requestAnimationFrame(() => {
      filteredRows = allRows.filter(row => {
          const data = row._data;
          
          if (currentFilters.category.length > 0 && !currentFilters.category.includes(data.category)) {
              return false;
          }
          
          if (currentFilters.quantity === "out" && data.quantity !== 0) return false;
          if (currentFilters.quantity === "available" && data.quantity <= 0) return false;
          
          if (currentFilters.dateFrom || currentFilters.dateTo) {
              const itemDate = parseTableDate(data.dateAcquired);
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
          }
          
          if (currentFilters.deletedDateFrom || currentFilters.deletedDateTo) {
              const deletedDate = parseTableDate(data.deletedDate);
              if (!deletedDate) return false;
              
              if (currentFilters.deletedDateFrom) {
                  const fromDate = new Date(currentFilters.deletedDateFrom);
                  fromDate.setHours(0, 0, 0, 0);
                  if (deletedDate < fromDate) return false;
              }
              
              if (currentFilters.deletedDateTo) {
                  const toDate = new Date(currentFilters.deletedDateTo);
                  toDate.setHours(23, 59, 59, 999);
                  if (deletedDate > toDate) return false;
              }
          }
          
          if (currentFilters.deletedBy.length > 0) {
              const matches = currentFilters.deletedBy.some(filterDeletedBy => 
                  data.deletedBy.includes(filterDeletedBy.toLowerCase())
              );
              if (!matches) return false;
          }
          
          if (searchValue && !(
              data.itemName.includes(searchValue) || 
              data.model.includes(searchValue) || 
              data.brand.includes(searchValue) ||
              data.serialNumber.includes(searchValue) ||
              data.category.includes(searchValue) ||
              data.deletedBy.includes(searchValue)
          )) {
              return false;
          }
          
          return true;
      });
      
      if (filterCache.size > 50) filterCache.clear(); 
      filterCache.set(filterKey, filteredRows);
      
      updateDisplay();
  });
}

function generateFilterKey() {
  const searchValue = domElements.searchInput?.value || '';
  return `${currentFilters.category.join(',')}|${currentFilters.quantity}|${currentFilters.dateFrom}|${currentFilters.dateTo}|${currentFilters.deletedBy}|${currentFilters.deletedDateFrom}|${currentFilters.deletedDateTo}|${searchValue}`;
}

function updateDisplay() {
  updateRowNumbers();
  showNoResultsMessage(filteredRows.length === 0);
  thisCurrentPage = 1;
  displayPage(thisCurrentPage);
  updateItemCounts();
}

// =============================================
// FILTER CONTROLS
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

  document.addEventListener('click', function(e) {
      const toggle = e.target.closest('[id^="toggle"]');
      if (toggle) {
          e.stopPropagation();
          const filterConfig = filterToggles.find(f => f.id === toggle.id);
          if (filterConfig && filterConfig.container) {
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

  filterToggles.forEach(({ container }) => {
      container?.addEventListener('click', e => e.stopPropagation());
  });

  // Category filter handlers
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

  // Quantity filter handlers
  document.getElementById("sortLowToHigh")?.addEventListener("click", () => {
      sortByQuantity("asc");
      closeAllFilters();
  });

  document.getElementById("sortHighToLow")?.addEventListener("click", () => {
      sortByQuantity("desc");
      closeAllFilters();
  });

  document.getElementById("showAvailable")?.addEventListener("click", () => {
      currentFilters.quantity = "available";
      filterItems();
      closeAllFilters();
  });

  document.getElementById("showOutOfStock")?.addEventListener("click", () => {
      currentFilters.quantity = "out";
      filterItems();
      closeAllFilters();
  });

  document.getElementById("resetQuantityFilter")?.addEventListener("click", () => {
      currentFilters.quantity = "";
      filterItems();
      closeAllFilters();
  });

  // Date acquired filter
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

  document.getElementById("filterByDeletedByBtn")?.addEventListener("click", () => {
    const deletedByCheckboxes = document.querySelectorAll('input[name="deletedByFilter"]:checked');
    currentFilters.deletedBy = Array.from(deletedByCheckboxes).map(cb => cb.value.toLowerCase());
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

  // Deleted by filter
  document.getElementById("filterByDeletedByBtn")?.addEventListener("click", () => {
      const deletedByInput = document.getElementById("deletedBySearch");
      currentFilters.deletedBy = deletedByInput.value;
      filterItems();
      closeAllFilters();
  });

  document.getElementById("resetDeletedByBtn")?.addEventListener("click", () => {
    document.querySelectorAll('input[name="deletedByFilter"]').forEach(cb => cb.checked = false);
    currentFilters.deletedBy = [];
    filterItems();
    closeAllFilters();
});

  // Reset all filters
  document.getElementById("resetAllFiltersBtn")?.addEventListener("click", resetAllFilters);

  // Search functionality
  domElements.searchInput?.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
          filterItems();
      }, 200);
  });

  // Rows per page selector
  if (domElements.rowsPerPageSelect) {
      domElements.rowsPerPageSelect.addEventListener('change', function() {
          rowsPerPage = parseInt(this.value);
          thisCurrentPage = 1;
          displayPage(thisCurrentPage);
          updateItemCounts();
          
          localStorage.setItem('deletedItemsTableRowsPerPage', this.value);
      });
      
      const savedRowsPerPage = localStorage.getItem('deletedItemsTableRowsPerPage');
    if (savedRowsPerPage) {
        domElements.rowsPerPageSelect.value = savedRowsPerPage;
        rowsPerPage = parseInt(savedRowsPerPage);
    }
  }
}

// =============================================
// COLUMN FILTERING
// =============================================
const columnFilterKey = "deletedItemsColumnFilterSettings";

function restoreColumnSettings() {
  const savedSettings = JSON.parse(localStorage.getItem(columnFilterKey)) || {};
  document.querySelectorAll("#columnFilterContainer input[type='checkbox']").forEach(cb => {
    const colIndex = cb.getAttribute("data-column");
    if (savedSettings[colIndex] === false) {
      cb.checked = false;
      toggleColumnVisibility(parseInt(colIndex), false);
    } else {
      cb.checked = true;
      toggleColumnVisibility(parseInt(colIndex), true);
    }
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

function sortByQuantity(order = "asc") {
  filteredRows.sort((a, b) => {
      const qA = a._data.quantity;
      const qB = b._data.quantity;
      return order === "asc" ? qA - qB : qB - qA;
  });

  updateRowNumbers();
  displayPage(thisCurrentPage);
  updateItemCounts();
}

function resetAllFilters() {
  document.querySelectorAll('input[name="categoryFilter"]').forEach(cb => cb.checked = false);
  document.querySelectorAll('input[name="deletedByFilter"]').forEach(cb => cb.checked = false);
  currentFilters.category = [];
  currentFilters.deletedBy = [];
  
  currentFilters.quantity = "";
  
  domElements.dateFrom.value = "";
  domElements.dateTo.value = "";
  currentFilters.dateFrom = "";
  currentFilters.dateTo = "";
  
  domElements.deletedDateFrom.value = "";
  domElements.deletedDateTo.value = "";
  currentFilters.deletedDateFrom = "";
  currentFilters.deletedDateTo = "";
  
  const deletedByInput = document.getElementById("deletedBySearch");
  if (deletedByInput) deletedByInput.value = "";
  currentFilters.deletedBy = "";
  
  domElements.searchInput.value = "";
  
  filterCache.clear();
  dateCache.clear();
  filterItems();
}

function updateItemCounts() {
  const totalItems = allRows.length;
  const filteredItems = filteredRows.length;
  const isFiltered = filteredItems !== totalItems;
  
  updateCountElement('totalItemsCount', totalItems);
  updateCountElement('totalItemsCount2', totalItems);
  updateCountElement('visibleItemsCount', filteredItems);
  
  const filteredCountElement = document.getElementById('filteredItemsCount');
  if (filteredCountElement) {
      filteredCountElement.style.display = isFiltered ? 'inline' : 'none';
  }
  
  updatePageInfo();
}

function updateCountElement(elementId, count) {
  const element = document.getElementById(elementId);
  if (element) {
      element.textContent = count;
  }
}

function updatePageInfo() {
  const startIndex = (thisCurrentPage - 1) * rowsPerPage + 1;
  const endIndex = Math.min(thisCurrentPage * rowsPerPage, filteredRows.length);
  const totalItems = filteredRows.length;
  
  const pageInfoElement = document.getElementById('pageInfo');
  if (pageInfoElement) {
      if (totalItems > 0) {
          pageInfoElement.textContent = `Showing ${startIndex} to ${endIndex} of ${totalItems} entries`;
      } else {
          pageInfoElement.textContent = 'No entries to show';
      }
  }
}

function updateRowNumbers() {
  const startIndex = (thisCurrentPage - 1) * rowsPerPage;
  const endIndex = Math.min(startIndex + rowsPerPage, filteredRows.length);
  
  for (let i = startIndex; i < endIndex; i++) {
      const row = filteredRows[i];
      const cell = row.cells[0];
      if (cell.textContent !== (i + 1).toString()) {
          cell.textContent = i + 1;
      }
  }
}

function showNoResultsMessage(show) {
  let noResultsRow = document.getElementById('noResultsMessage');
  
  if (show && !noResultsRow) {
      noResultsRow = document.createElement("tr");
      noResultsRow.id = 'noResultsMessage';
      noResultsRow.innerHTML = `<td colspan="13" style="text-align:center; color:#666; padding:20px;">No deleted items found matching your filters.</td>`;
      domElements.tableBody.appendChild(noResultsRow);
  } else if (!show && noResultsRow) {
      noResultsRow.remove();
  }
}

// =============================================
// DATE PARSING
// =============================================
const dateCache = new Map();
function parseTableDate(dateString) {
    if (!dateString || dateString === 'N/A' || dateString === '—' || dateString === '') {
        return null;
    }
    
    if (dateCache.has(dateString)) {
        return dateCache.get(dateString);
    }
    
    try {
        let result = null;
        
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
                if (result.getDate() !== day || result.getMonth() !== month || result.getFullYear() !== year) {
                    result = null;
                }
            }
        }
        else if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
            result = new Date(dateString);
        }
        else {
            result = new Date(dateString);
        }
        
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
    reverseButtons: true
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