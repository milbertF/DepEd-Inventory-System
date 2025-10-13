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

document.addEventListener("DOMContentLoaded", function () {
  initializeDOMCache();
  initializeTable();
  initFilterControls();
  initColumnFilter(); 
  initTableActions();
  checkForRecoveryDeletedItem();
  checkForRecoveredItem();
  checkForMultipleRecoveredItems()


  restoreColumnSettings();
});

function initializeDOMCache() {
  domElements = {
      tableBody: document.getElementById("inventoryTableBody"),
      pagination: document.getElementById("pagination"),
      searchInput: document.getElementById("searchItem"),
      dateFrom: document.getElementById("dateFrom"),
      dateTo: document.getElementById("dateTo"),
      deletedDateFrom: document.getElementById("deletedDateFrom"),
      deletedDateTo: document.getElementById("deletedDateTo"),
      // Filter containers
      categoryFilter: document.getElementById("categoryFilterContainer"),
      qtyFilter: document.getElementById("quantityFilterContainer"),
      dateFilter: document.getElementById("dateFilterContainer"),
      deletedDateFilter: document.getElementById("deletedDateFilterContainer"),
      deletedByFilter: document.getElementById("deletedByFilterContainer"),
      columnFilter: document.getElementById("columnFilterContainer"), 
      rowsPerPageSelect: document.getElementById("rowsPerPageSelect")
  };
}

function populateCategoryFilters() {
  const categoryCheckboxes = document.getElementById('categoryCheckboxes');
  if (!categoryCheckboxes) return;
  
  // Get unique categories from all rows
  const categories = new Set();
  allRows.forEach(row => {
      const category = row._data.category;
      if (category && category !== 'uncategorized') {
          categories.add(category);
      }
  });
  
  // Sort categories alphabetically
  const sortedCategories = Array.from(categories).sort();
  
  // Create checkboxes
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

function initializeTable() {
  allRows = Array.from(domElements.tableBody.querySelectorAll("tr"));
  
  // Pre-calculate row data for faster filtering
  allRows.forEach((row, index) => {
      const cells = row.cells;
      row._data = {
          category: (cells[1]?.textContent || '').toLowerCase().trim(),
          serialNumber: (cells[2]?.textContent || '').toLowerCase(),
          itemName: (cells[4]?.textContent || '').toLowerCase(),
          brand: (cells[5]?.textContent || '').toLowerCase().trim(),
          model: (cells[6]?.textContent || '').toLowerCase(),
          quantity: parseInt(cells[7]?.textContent || 0),
          dateAcquired: cells[8]?.textContent.trim(),
          deletedBy: (cells[9]?.textContent || '').toLowerCase().trim(),
          deletedDate: cells[10]?.textContent.trim(),
          deletedTime: cells[11]?.textContent.trim()
      };
  });
  
  filteredRows = [...allRows];
  populateCategoryFilters();
  displayPage(1);
  updateItemCounts();
}

// Column filtering functionality for Recently Deleted Items
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

  // Update all body rows
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

// Client-side filtering
function filterItems() {
  const filterKey = generateFilterKey();
  
  // Cache first
  if (filterCache.has(filterKey)) {
      filteredRows = filterCache.get(filterKey);
      updateDisplay();
      return;
  }
  
  const searchValue = (domElements.searchInput?.value || '').toLowerCase();

  requestAnimationFrame(() => {
      filteredRows = allRows.filter(row => {
          const data = row._data;
          
          // Category filter
          if (currentFilters.category.length > 0 && !currentFilters.category.includes(data.category)) {
              return false;
          }
          
          // Quantity filter
          if (currentFilters.quantity === "out" && data.quantity !== 0) return false;
          if (currentFilters.quantity === "available" && data.quantity <= 0) return false;
          
          // Date acquired filter
          if (currentFilters.dateFrom || currentFilters.dateTo) {
              const itemDate = parseTableDate(data.dateAcquired);
              if (!itemDate) return false;
              
              if (currentFilters.dateFrom && itemDate < new Date(currentFilters.dateFrom)) return false;
              if (currentFilters.dateTo) {
                  const toDate = new Date(currentFilters.dateTo);
                  toDate.setHours(23, 59, 59, 999);
                  if (itemDate > toDate) return false;
              }
          }
          
          // Deleted date filter
          if (currentFilters.deletedDateFrom || currentFilters.deletedDateTo) {
              const deletedDate = parseTableDate(data.deletedDate);
              if (!deletedDate) return false;
              
              if (currentFilters.deletedDateFrom && deletedDate < new Date(currentFilters.deletedDateFrom)) return false;
              if (currentFilters.deletedDateTo) {
                  const toDate = new Date(currentFilters.deletedDateTo);
                  toDate.setHours(23, 59, 59, 999);
                  if (deletedDate > toDate) return false;
              }
          }
          
          // Deleted by filter
       // Deleted by filter
if (currentFilters.deletedBy.length > 0 && !currentFilters.deletedBy.some(deletedBy => data.deletedBy.includes(deletedBy))) {
  return false;
}
          
          // Search filter
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
      
      // Update cache
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

const dateCache = new Map();
function parseTableDate(dateString) {
  if (!dateString || dateString === 'N/A') return null;
  
  if (dateCache.has(dateString)) {
      return dateCache.get(dateString);
  }
  
  try {
      const parts = dateString.split('-');
      let result = null;
      
      if (parts.length === 3) {
          const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
          const month = monthNames.indexOf(parts[0]);
          const day = parseInt(parts[1]);
          const year = parseInt(parts[2]);
          
          if (month !== -1 && !isNaN(day) && !isNaN(year)) {
              result = new Date(year, month, day);
          }
      }
      
      if (!result) {
          result = new Date(dateString);
          if (isNaN(result.getTime())) result = null;
      }
      
      dateCache.set(dateString, result);
      return result;
  } catch (e) {
      return null;
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

function displayPage(page = 1) {
  const start = (page - 1) * rowsPerPage;
  const end = start + rowsPerPage;

  requestAnimationFrame(() => {
      // Hide all rows
      allRows.forEach(row => {
          row.style.display = "none";
      });
      
      // Show only current page rows
      for (let i = start; i < Math.min(end, filteredRows.length); i++) {
          filteredRows[i].style.display = "";
      }
      
      updatePagination(page);
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
  
  // First page button (<<) - always show if not on first page
  if (page > 1) {
      html += `<a href="#" class="prev-next" data-page="1" title="First page"><i class="fas fa-angle-double-left"></i></a>`;
  }
  
  // Previous button (<)
  if (page > 1) {
      html += `<a href="#" class="prev-next" data-page="${page - 1}" title="Previous page"><i class="fas fa-chevron-left"></i></a>`;
  }
  
  // Page numbers - always show current page and surrounding pages
  for (let i = Math.max(1, page - range); i <= Math.min(totalPages, page + range); i++) {
      const activeClass = i === page ? "active" : "";
      html += `<a href="#" class="${activeClass}" data-page="${i}">${i}</a>`;
  }
  
  // Next button (>)
  if (page < totalPages) {
      html += `<a href="#" class="prev-next" data-page="${page + 1}" title="Next page"><i class="fas fa-chevron-right"></i></a>`;
  }
  
  // Last page button (>>) - always show if not on last page
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
  // Reset category
  document.querySelectorAll('input[name="categoryFilter"]').forEach(cb => cb.checked = false);
  document.querySelectorAll('input[name="deletedByFilter"]').forEach(cb => cb.checked = false);
  currentFilters.category = [];
  currentFilters.deletedBy = [];
  
  // Reset quantity
  currentFilters.quantity = "";
  
  // Reset date acquired
  domElements.dateFrom.value = "";
  domElements.dateTo.value = "";
  currentFilters.dateFrom = "";
  currentFilters.dateTo = "";
  
  // Reset deleted date
  domElements.deletedDateFrom.value = "";
  domElements.deletedDateTo.value = "";
  currentFilters.deletedDateFrom = "";
  currentFilters.deletedDateTo = "";
  
  // Reset deleted by
  const deletedByInput = document.getElementById("deletedBySearch");
  if (deletedByInput) deletedByInput.value = "";
  currentFilters.deletedBy = "";
  
  // Reset search
  domElements.searchInput.value = "";
  
  filterCache.clear();
  dateCache.clear();
  filterItems();
}

function updateItemCounts() {
  const totalItems = allRows.length;
  const filteredItems = filteredRows.length;
  const isFiltered = filteredItems !== totalItems;
  
  // Update all count displays
  updateCountElement('totalItemsCount', totalItems);
  updateCountElement('totalItemsCount2', totalItems);
  updateCountElement('visibleItemsCount', filteredItems);
  
  // Show/hide filtered count
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

// Table actions
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
              // checkAllItemsDeleteAlerts.js
              function checkForAllDeletedItem() {
                const urlParams = new URLSearchParams(window.location.search);
                const itemDeleted = urlParams.get('item_all_deleted');
                
                if (itemDeleted === '1') {
                    // Get data from data attributes
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
            

function checkForRecoveryDeletedItem() {
  const deletedName = sessionStorage.getItem('deletedItemRecoveryName');
  if (deletedName) {
      // Add a delay to ensure DOM is ready, especially for empty tables
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

// Multiple event listeners to ensure it runs
document.addEventListener('DOMContentLoaded', function() {
    checkForRecoveryDeletedItem();
});

window.addEventListener('load', function() {
    checkForRecoveryDeletedItem();
});

// Also run when page is shown (for back/forward navigation)
window.addEventListener('pageshow', function() {
    checkForRecoveryDeletedItem();
});
let scrollPosition = 0;

function handleViewItem(viewBtn) {
  console.log('handleViewItem called', viewBtn);
  
  // Check if modal exists
  const modal = document.getElementById("itemViewModal");
  if (!modal) {
    console.error('View modal not found!');
    return;
  }
  
  console.log('Modal found, setting content...');
  
  // Set all the content with fallbacks
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
  
  // Handle dates
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

  // Handle photo
  const photoElement = document.getElementById("view-item-photo");
  const photo = viewBtn.dataset.photo;
  if (photoElement) {
    photoElement.src = (photo && photo !== '') ? '/' + photo : '/images/user-profile/default-image.jpg';
  }

  // Show modal with scroll handling
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

function checkForRecoveredItem() {
  const recoveredName = sessionStorage.getItem('recoveredItemName');
  if (recoveredName) {
    Swal.fire({
      icon: 'success',
      title: 'Recovered!',
      html: `Item <b>${recoveredName}</b> has been restored to inventory.`,
      confirmButtonColor: '#3085d6'
    }).then(() => {
      // Clean up URL and sessionStorage
      const url = new URL(window.location.href);
      url.searchParams.delete('recovered');
      url.searchParams.delete('id');
      window.history.replaceState({}, document.title, url.pathname + '?' + url.searchParams.toString());
      
      // IMMEDIATELY remove from sessionStorage
      sessionStorage.removeItem('recoveredItemName');
    });
    
    // Also remove immediately if user doesn't click OK (edge case)
    setTimeout(() => {
      sessionStorage.removeItem('recoveredItemName');
    }, 5000); // Remove after 5 seconds even if alert is ignored
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
      
      // IMMEDIATELY remove from sessionStorage
      sessionStorage.removeItem('recoveringAllItems');
      sessionStorage.removeItem('recoveredItemsCount');
    });
    
    // Also remove immediately if user doesn't click OK
    setTimeout(() => {
      sessionStorage.removeItem('recoveringAllItems');
      sessionStorage.removeItem('recoveredItemsCount');
    }, 5000);
  }
}

function checkForRecoveryDeletedItem() {
  const deletedName = sessionStorage.getItem('deletedItemRecoveryName');
  if (deletedName) {
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
      
      // IMMEDIATELY remove from sessionStorage
      sessionStorage.removeItem('deletedItemRecoveryName');
    });
    

    setTimeout(() => {
      sessionStorage.removeItem('deletedItemRecoveryName');
    }, 5000);
  }
}


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