let currentFiltersAll = {
  quantity: "",
  dateFrom: "",
  dateTo: "",
  status: []
};

let allRows = [];
let filteredRows = [];
let thisCurrentPage = 1;
let rowsPerPage = parseInt(localStorage.getItem('allItemsTableRowsPerPage')) || 10;
let debounceTimer = null;
let filterCache = new Map();

let domElements = {};

document.addEventListener("DOMContentLoaded", function () {
  initializeDOMCache();
  initializeTable();
  initFilterControlsAll();
  initSearchFunctionalityAll();
  initTableActions();
  checkForDeletedItem();
  initDateValidation();
  calculateEditTotalCost();
  restoreColumnSettings();
  initRowsPerPageSelector(); 
});

// Function for rows per page selector
function initRowsPerPageSelector() {
  const rowsPerPageSelect = document.getElementById("rowsPerPageSelect");
  
  if (rowsPerPageSelect) {
    // Load saved preference FIRST, before setting up event listener
    const savedRowsPerPage = localStorage.getItem('allItemsTableRowsPerPage');
    if (savedRowsPerPage) {
      rowsPerPageSelect.value = savedRowsPerPage;
      rowsPerPage = parseInt(savedRowsPerPage);
    }
    
    // Then set up the event listener
    rowsPerPageSelect.addEventListener('change', function() {
      rowsPerPage = parseInt(this.value);
      thisCurrentPage = 1; // Reset to first page when changing rows per page
      displayPage(thisCurrentPage);
      updateItemCounts();
      
      // Save preference to localStorage
      localStorage.setItem('allItemsTableRowsPerPage', this.value);
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
      // Filter containers
      qtyFilter: document.getElementById("quantityFilterContainerAll"),
      dateFilter: document.getElementById("dateFilterContainerAll"),
      statusFilter: document.getElementById("statusFilterContainer"),
      columnFilter: document.getElementById("columnFilterContainer"),
      // Modal elements
      editItemModal: document.getElementById('editItemModal'),
      
      itemViewModal: document.getElementById('itemViewModal'),
      rowsPerPageSelect: document.getElementById("rowsPerPageSelect") 
  };
}

function updateItemCounts() {
  const totalItems = allRows.length;
  const filteredItems = filteredRows.length;
  const isFiltered = filteredItems !== totalItems;
  
  // Update all count displays
  updateCountElement('totalItemsCount', totalItems);
  updateCountElement('totalItemsCount2', totalItems);
  updateCountElement('visibleItemsCount', filteredItems);
  updateCountElement('visibleCount', filteredItems);
  updateCountElement('totalCount', totalItems);
  updateCountElement('displayedCount', filteredItems);
  updateCountElement('totalItems', totalItems);
  
  // Show/hide filtered count
  const filteredCountElement = document.getElementById('filteredItemsCount');
  if (filteredCountElement) {
      filteredCountElement.style.display = isFiltered ? 'inline' : 'none';
  }
  
  // Update active filters display
  updateActiveFiltersDisplay();
  updatePageInfo(); //  update page info
}

// function to show current page information
function updatePageInfo() {
  const startIndex = (thisCurrentPage - 1) * rowsPerPage + 1;
  const endIndex = Math.min(thisCurrentPage * rowsPerPage, filteredRows.length);
  const totalItems = filteredRows.length;
  
  // Update page info display if element exists
  const pageInfoElement = document.getElementById('pageInfo');
  if (pageInfoElement) {
    if (totalItems > 0) {
      pageInfoElement.textContent = `Showing ${startIndex} to ${endIndex} of ${totalItems} entries`;
    } else {
      pageInfoElement.textContent = 'No entries to show';
    }
  }
}

function updateCountElement(elementId, count) {
  const element = document.getElementById(elementId);
  if (element) {
      element.textContent = count;
  }
}

function updateActiveFiltersDisplay() {
  const activeFiltersElement = document.getElementById('activeFilters');
  if (!activeFiltersElement) return;
  
  const activeFilters = [];
  
  // Check brand filters
  const selectedBrands = domElements.brandSelect ? 
      Array.from(domElements.brandSelect.selectedOptions).map(opt => opt.value) : [];
  if (selectedBrands.length > 0) {
      activeFilters.push(`Brand: ${selectedBrands.length} selected`);
  }
  
  // Check quantity filter
  if (currentFilters.quantity === "out") {
      activeFilters.push("Out of Stock");
  } else if (currentFilters.quantity === "available") {
      activeFilters.push("Available");
  }
  
  // Check date filters
  if (currentFilters.dateFrom || currentFilters.dateTo) {
      let dateFilter = "Date: ";
      if (currentFilters.dateFrom) dateFilter += `from ${currentFilters.dateFrom}`;
      if (currentFilters.dateTo) dateFilter += ` to ${currentFilters.dateTo}`;
      activeFilters.push(dateFilter);
  }
  
  // Check status filters
  if (currentFilters.status.length > 0) {
      activeFilters.push(`Status: ${currentFilters.status.length} selected`);
  }
  
  // Check search
  const searchValue = domElements.searchInput?.value || '';
  if (searchValue) {
      activeFilters.push(`Search: "${searchValue}"`);
  }
  
  // Update display
  if (activeFilters.length > 0) {
      activeFiltersElement.innerHTML = `<strong>Active filters:</strong> ${activeFilters.join(' • ')}`;
      activeFiltersElement.style.color = '#495057';
  } else {
      activeFiltersElement.innerHTML = 'No active filters';
      activeFiltersElement.style.color = '#6c757d';
  }
}


function initializeTable() {
  allRows = Array.from(domElements.tableBody.querySelectorAll("tr"));
  
  // Pre-calculate row data for faster filtering
  allRows.forEach((row, index) => {
      row._data = {
          category: (row.cells[1]?.textContent || '').toLowerCase().trim(),
          serialNumber: (row.cells[2]?.textContent || '').toLowerCase(),
          itemName: (row.cells[4]?.textContent || '').toLowerCase(),
          description: (row.cells[5]?.textContent || '').toLowerCase(),
          brand: (row.cells[6]?.textContent || '').toLowerCase().trim(),
          model: (row.cells[7]?.textContent || '').toLowerCase(),
          quantity: parseInt(row.cells[8]?.textContent || 0),
          dateAcquired: row.cells[9]?.textContent.trim(),
          status: row.cells[10]?.textContent.trim()
      };
  });
  
  filteredRows = [...allRows];
  displayPage(1);
  updateItemCounts();
}

// Client-side filtering
function filterItemsAll() {
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
          
          // Quantity filter
          if (currentFiltersAll.quantity === "out" && data.quantity !== 0) return false;
          if (currentFiltersAll.quantity === "available" && data.quantity <= 0) return false;
          
          // Date filter
          if (currentFiltersAll.dateFrom || currentFiltersAll.dateTo) {
              const itemDate = parseTableDate(data.dateAcquired);
              if (!itemDate) return false;
              
              if (currentFiltersAll.dateFrom && itemDate < new Date(currentFiltersAll.dateFrom)) return false;
              if (currentFiltersAll.dateTo) {
                  const toDate = new Date(currentFiltersAll.dateTo);
                  toDate.setHours(23, 59, 59, 999);
                  if (itemDate > toDate) return false;
              }
          }
          
          // Status filter
          if (currentFiltersAll.status.length > 0 && !currentFiltersAll.status.includes(data.status)) {
              return false;
          }
          
          // Search filter
          if (searchValue && !(
              data.itemName.includes(searchValue) || 
              data.model.includes(searchValue) || 
              data.brand.includes(searchValue) ||
              data.serialNumber.includes(searchValue) ||
              data.description.includes(searchValue) ||
              data.category.includes(searchValue)
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
  return `${currentFiltersAll.quantity}|${currentFiltersAll.dateFrom}|${currentFiltersAll.dateTo}|${currentFiltersAll.status.sort().join(',')}|${searchValue}`;
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
  filteredRows.forEach((row, index) => {
      const cell = row.cells[0];
      if (cell.textContent !== (index + 1).toString()) {
          cell.textContent = index + 1;
      }
  });
}

function showNoResultsMessage(show) {
  let noResultsRow = document.getElementById('noResultsMessage');
  
  if (show && !noResultsRow) {
      noResultsRow = document.createElement("tr");
      noResultsRow.id = 'noResultsMessage';
      noResultsRow.innerHTML = `<td colspan="12" style="text-align:center; color:#666; padding:20px;">No items found matching your filters.</td>`;
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



function initFilterControlsAll() {
  const filterToggles = [
      { id: "toggleQtyFilterAll", container: domElements.qtyFilter },
      { id: "toggleDateFilterAll", container: domElements.dateFilter },
      { id: "toggleStatusFilter", container: domElements.statusFilter },
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

  // Quantity filter handlers
  document.getElementById("sortLowToHighAll")?.addEventListener("click", () => {
      sortByQuantityAll("asc");
      closeAllFilters();
  });

  document.getElementById("sortHighToLowAll")?.addEventListener("click", () => {
      sortByQuantityAll("desc");
      closeAllFilters();
  });

  document.getElementById("showAvailableAll")?.addEventListener("click", () => {
      currentFiltersAll.quantity = "available";
      filterItemsAll();
      closeAllFilters();
  });

  document.getElementById("showOutOfStockAll")?.addEventListener("click", () => {
      currentFiltersAll.quantity = "out";
      filterItemsAll();
      closeAllFilters();
  });

  document.getElementById("resetQuantityFilterAll")?.addEventListener("click", () => {
      currentFiltersAll.quantity = "";
      filterItemsAll();
      closeAllFilters();
  });

  // Date filter
  document.getElementById("filterByDateBtnAll")?.addEventListener("click", () => {
      currentFiltersAll.dateFrom = domElements.dateFrom.value;
      currentFiltersAll.dateTo = domElements.dateTo.value;
      filterItemsAll();
      closeAllFilters();
  });

  document.getElementById("resetDateFilterBtnAll")?.addEventListener("click", () => {
      domElements.dateFrom.value = "";
      domElements.dateTo.value = "";
      currentFiltersAll.dateFrom = "";
      currentFiltersAll.dateTo = "";
      filterItemsAll();
      closeAllFilters();
  });

  // Status filter
  document.getElementById("filterByStatusBtn")?.addEventListener("click", () => {
      const statusCheckboxes = document.querySelectorAll('input[name="statusFilter"]:checked');
      currentFiltersAll.status = Array.from(statusCheckboxes).map(cb => cb.value);
      filterItemsAll();
      closeAllFilters();
  });

  document.getElementById("resetStatusFilterBtn")?.addEventListener("click", () => {
      document.querySelectorAll('input[name="statusFilter"]').forEach(cb => cb.checked = true);
      currentFiltersAll.status = [];
      filterItemsAll();
      closeAllFilters();
  });

  // Reset all filters
  document.getElementById("resetAllFiltersBtn")?.addEventListener("click", resetAllFiltersAll);

  // Search functionality
  domElements.searchInput?.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
          filterItemsAll();
      }, 150);
  });
}

function sortByQuantityAll(order = "asc") {
  filteredRows.sort((a, b) => {
      const qA = a._data.quantity;
      const qB = b._data.quantity;
      return order === "asc" ? qA - qB : qB - qA;
  });

  updateRowNumbers();
  displayPage(thisCurrentPage);
}

function resetAllFiltersAll() {
  currentFiltersAll.quantity = "";
  domElements.dateFrom.value = "";
  domElements.dateTo.value = "";
  currentFiltersAll.dateFrom = "";
  currentFiltersAll.dateTo = "";
  document.querySelectorAll('input[name="statusFilter"]').forEach(cb => cb.checked = true);
  currentFiltersAll.status = [];
  domElements.searchInput.value = "";
  
  filterCache.clear();
  dateCache.clear();
  filterItemsAll();
}

function initSearchFunctionalityAll() {
  // Already handled in initFilterControlsAll
}

// Column filtering functionality
const columnFilterKey = "columnFilterSettingsAll";

function restoreColumnSettings() {
  const savedSettings = JSON.parse(localStorage.getItem(columnFilterKey)) || {};
  document.querySelectorAll("#columnFilterContainer input[type='checkbox']").forEach(cb => {
      const colIndex = cb.getAttribute("data-column");
      if (savedSettings[colIndex] === false) {
          cb.checked = false;
      } else {
          cb.checked = true;
      }
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

document.querySelectorAll("#columnFilterContainer input[type='checkbox']")
  .forEach(checkbox => {
      checkbox.addEventListener("change", function () {
          const colIndex = this.getAttribute("data-column");
          const table = document.querySelector(".itemTable");

          table.querySelectorAll("thead th:nth-child(" + (parseInt(colIndex) + 1) + ")")
              .forEach(th => th.style.display = this.checked ? "" : "none");

          table.querySelectorAll("tbody tr td:nth-child(" + (parseInt(colIndex) + 1) + ")")
              .forEach(td => td.style.display = this.checked ? "" : "none");

          saveColumnSettings();
      });
  });

document.getElementById("resetColumnFilterBtn").addEventListener("click", () => {
  document.querySelectorAll("#columnFilterContainer input[type='checkbox']").forEach(cb => {
      if (!cb.checked) {
          cb.checked = true;
          cb.dispatchEvent(new Event("change"));
      }
  });
  localStorage.removeItem(columnFilterKey);
});

// Keep all your existing functions for table actions, modals, etc.
function initTableActions() {
  document.querySelector('.itemTable')?.addEventListener('click', function(e) {
      const deleteBtn = e.target.closest('.action-btn.delete');
      if (deleteBtn) return handleDeleteItem(deleteBtn);

      const editBtn = e.target.closest('.action-btn.edit');
      if (editBtn) return handleEditItem(editBtn);

      const viewBtn = e.target.closest('.action-btn.view');
      if (viewBtn) return handleViewItem(viewBtn);
  });
}

function handleDeleteItem(deleteBtn) {
  const itemId = deleteBtn.getAttribute('data-id');
  const itemName = deleteBtn.getAttribute('data-name');
  const source = deleteBtn.getAttribute('data-source');

  Swal.fire({
      title: 'Are you sure?',
      text: `You are about to delete "${itemName}".`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
  }).then(result => {
      if (result.isConfirmed) {
          // Use the SAME pattern as your working viewItemByCategory
          sessionStorage.setItem('deletedItemAllName', itemName);
          let url = `/templates/inventory/function/deleteSpecificItem.php?id=${itemId}&source=${source}`;
          window.location.href = url;
      }
  });
}



function handleEditItem(editBtn) {
  const getData = attr => editBtn.getAttribute(attr) || '';
  document.getElementById('edit-item-id').value = getData('data-id');
  document.getElementById('edit-item-name').value = getData('data-name');
  document.getElementById('edit-item-description').value = getData('data-description');
  document.getElementById('edit-item-brand').value = getData('data-brand');
  document.getElementById('edit-item-model').value = getData('data-model');
  document.getElementById('edit-item-serial_number').value = getData('data-serial');
  document.getElementById('edit-item-unit').value = getData('data-unit');
  document.getElementById('edit-item-unit-cost').value = getData('data-unitcost');
  document.getElementById('edit-item-total-cost').value = getData('data-totalcost');
  document.getElementById('edit-item-qty').value = getData('data-qty');
  document.getElementById('edit-item-status').value = getData('data-item-status');
  document.getElementById('edit-item-photo').value = '';

  const dateAcquired = getData('data-date-acquired');
  document.getElementById('edit-item-date-acquired').value = dateAcquired && !isNaN(Date.parse(dateAcquired)) ? new Date(dateAcquired).toISOString().split('T')[0] : '';

  const categoryId = getData('data-category-id');
  const categorySelect = document.getElementById('edit-item-category-id');
  Array.from(categorySelect.options).forEach(opt => opt.selected = opt.value === categoryId);

  const photoOutput = document.getElementById('edit-itemPhotoOutput');
  const photo = getData('data-photo');
  if (photo) {
      photoOutput.src = photo;
      photoOutput.style.display = 'block';
  } else {
      photoOutput.src = '';
      photoOutput.style.display = 'none';
  }

  document.getElementById('editItemModal').style.display = 'flex';
}

let scrollPosition = 0;

function handleViewItem(viewBtn) {
  document.getElementById("view-item-name").textContent = viewBtn.dataset.name;
  document.getElementById("view-item-category").textContent = viewBtn.dataset.category || 'None';
  document.getElementById("view-item-description").textContent = viewBtn.dataset.description || 'None';
  document.getElementById("view-item-brand").textContent = viewBtn.dataset.brand || 'None';
  document.getElementById("view-item-model").textContent = viewBtn.dataset.model || 'None';
  document.getElementById("view-item-serial").textContent = viewBtn.dataset.serial || 'None';
  document.getElementById("view-item-quantity").textContent = viewBtn.dataset.qty;
  document.getElementById("view-item-unit").textContent = viewBtn.dataset.unit || 'None';
  document.getElementById("view-item-unit-cost").textContent = viewBtn.dataset.unitcost;
  document.getElementById("view-item-total-cost").textContent = viewBtn.dataset.totalcost;

  document.getElementById("view-item-date-acquired").textContent =
      viewBtn.dataset.dateAcquired
          ? new Date(viewBtn.dataset.dateAcquired).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: '2-digit'
          }).replace(',', '').replace(' ', '-')
          : 'N/A';
  document.getElementById("view-item-created-at").textContent = viewBtn.dataset.created || 'N/A';
  document.getElementById("view-item-status").textContent = viewBtn.dataset.itemstatus;

  const photo = viewBtn.dataset.photo;
  document.getElementById("view-item-photo").src =
      photo && photo !== '' ? '/' + photo : '/images/user-profile/default-image.jpg';

  scrollPosition = window.scrollY;
  document.body.style.position = "fixed";
  document.body.style.top = `-${scrollPosition}px`;
  document.body.style.overflow = "hidden";
  document.body.style.width = "100%";

  document.getElementById("itemViewModal").style.display = "flex";
}

function closeItemView() {
  document.getElementById("itemViewModal").style.display = "none";
  document.body.style.position = "";
  document.body.style.top = "";
  document.body.style.overflow = "";
  document.body.style.width = "";
  window.scrollTo(0, scrollPosition);
}

function escEditItemModal() {
  document.getElementById('edit-item-form').reset();
  const preview = document.getElementById('edit-itemPhotoOutput');
  preview.src = '';
  preview.style.display = 'none';
  document.getElementById('editItemModal').style.display = 'none';
}

function previewItemEditPhoto(event) {
  const output = document.getElementById('edit-itemPhotoOutput');
  const file = event.target.files[0];
  if (file) {
      output.src = URL.createObjectURL(file);
      output.style.display = "block";
      output.onload = () => URL.revokeObjectURL(output.src);
  } else {
      output.src = "";
      output.style.display = "none";
  }
}

function calculateEditTotalCost() {
  const qtyInput = document.getElementById("edit-item-qty");
  const unitCostInput = document.getElementById("edit-item-unit-cost");
  const totalCostInput = document.getElementById("edit-item-total-cost");

  function updateTotalCost() {
      const qty = parseFloat(qtyInput.value) || 0;
      const unitCost = parseFloat(unitCostInput.value) || 0;
      const total = qty * unitCost;
      totalCostInput.value = total.toFixed(2);
  }

  qtyInput.addEventListener("input", updateTotalCost);
  unitCostInput.addEventListener("input", updateTotalCost);
}

function initDateValidation() {
  const dateFromInput = document.getElementById("dateFrom");
  const dateToInput = document.getElementById("dateTo");

  if (!dateFromInput || !dateToInput) return;

  dateFromInput.addEventListener("change", () => {
      if (dateFromInput.value) {
          dateToInput.min = dateFromInput.value;
          if (dateToInput.value && dateToInput.value < dateFromInput.value) {
              dateToInput.value = "";
          }
      } else {
          dateToInput.min = "";
      }
  });

  dateToInput.addEventListener("input", () => {
      if (dateFromInput.value && dateToInput.value < dateFromInput.value) {
          dateToInput.value = dateFromInput.value;
      }
  });
}