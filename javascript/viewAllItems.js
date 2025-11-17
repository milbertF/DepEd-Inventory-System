let currentFiltersAll = {
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
let isRedirectingToItem = false;
let targetItemId = null;

let domElements = {};
let columnMap = {};
let currentSort = { field: null, order: null }; // Track current sort state

// =============================================
// SCROLL & NAVIGATION VARIABLES
// =============================================
let isShowingItemNotFoundAlert = false;

// =============================================
// INITIALIZATION
// =============================================
document.addEventListener("DOMContentLoaded", function () {
    initializeDOMCache();
    buildColumnMap();
    initializeTable();
    initFilterControlsAll();
    initSearchFunctionalityAll();
    initTableActions();
    initDateValidation();
    calculateEditTotalCost();
    restoreColumnSettings();
    initRowsPerPageSelector();
    initDragToScroll(); // Add this line
    setTimeout(() => {
        initializeScrollFromURL();
    }, 500);
});

// =============================================
// COLUMN MAPPING - OPTIMIZED VERSION
// =============================================
// =============================================
// COLUMN MAPPING - FLEXIBLE VERSION FOR CATEGORY VIEW
// =============================================
function buildColumnMap() {
    const headerRow = document.querySelector('.itemTable thead tr');
    if (!headerRow) {
        console.error('No table header found!');
        return {};
    }
    
    const headers = Array.from(headerRow.cells);
    console.log('=== BUILDING COLUMN MAP (Category View) ===');
    
    const mappings = [
        { keywords: ['#', 'number'], property: 'rowNumber' },
        { keywords: ['item id', 'id'], property: 'itemId' },
        { keywords: ['image', 'photo'], property: 'image' },
        { keywords: ['serial'], property: 'serialNumber' },
        { keywords: ['item name', 'name'], property: 'itemName' },
        { keywords: ['description'], property: 'description' },
        { keywords: ['brand'], property: 'brand' },
        { keywords: ['model'], property: 'model' },
        { keywords: ['unit cost'], property: 'unitCost' },
        { keywords: ['total quantity', 'quantity'], property: 'totalQuantity' },
        { keywords: ['available quantity', 'available'], property: 'availableQuantity' },
        { keywords: ['total cost'], property: 'totalCost' },
        { keywords: ['date acquired', 'acquired'], property: 'dateAcquired' },
        { keywords: ['status'], property: 'status' },
        { keywords: ['remarks'], property: 'remarks' },
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
        rowNumber: 0, itemId: 1, image: 2, serialNumber: 3, itemName: 4,
        description: 5, brand: 6, model: 7, unitCost: 8, totalQuantity: 10,
        availableQuantity: 11, totalCost: 11, dateAcquired: 12, status: 13,
        remarks: 14, actions: 15
    };
    
    Object.keys(fallbackMap).forEach(key => {
        if (columnMap[key] === undefined) {
            columnMap[key] = fallbackMap[key];
            console.log(`🔁 Fallback mapped "${key}" to column ${fallbackMap[key]}`);
        }
    });
    
    console.log('=== FINAL COLUMN MAP (Category View) ===', columnMap);
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
// SCROLL TO ITEM FUNCTION - OPTIMIZED
// =============================================
window.scrollToItem = function(itemId) {
    if (!itemId || isRedirectingToItem) return;
    
    isRedirectingToItem = true;
    targetItemId = itemId;
    
    // Reset filters if function exists
    if (typeof resetAllFiltersAll === 'function') {
        resetAllFiltersAll();
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
                setTimeout(() => scrollToItemOnCurrentPage(itemId), 300);
            }, 100);
        } else {
            scrollToItemOnCurrentPage(itemId);
        }
    } else {
        showItemNotFoundMessage(itemId);
    }
}

function scrollToItemOnCurrentPage(itemId) {
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
        showItemNotFoundMessage(itemId);
    }
}

function cleanupScrollState(itemId) {
    isRedirectingToItem = false;
    targetItemId = null;
    
    const url = new URL(window.location);
    if (url.searchParams.get('item_id') === itemId) {
        url.searchParams.delete('item_id');
        window.history.replaceState({}, '', url);
    }
}

// =============================================
// ALERT FUNCTIONS
// =============================================
function showItemNotFoundMessage(itemId) {
    if (isShowingItemNotFoundAlert) return;
    
    isShowingItemNotFoundAlert = true;
    
    Swal.fire({
        icon: 'warning',
        title: 'Item Not Found',
        html: `The item (ID: <b>${itemId}</b>) could not be found in the current view.`,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3085d6',
        background: '#fff',
    }).then(() => {
        cleanupScrollState(itemId);
        isShowingItemNotFoundAlert = false;
    });
}

// =============================================
// INITIALIZATION FUNCTIONS
// =============================================
function initializeScrollFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemId = urlParams.get('item_id');
    
    if (itemId) {
        setTimeout(() => scrollToItem(itemId), 1000);
    }
}

function initRowsPerPageSelector() {
    const rowsPerPageSelect = document.getElementById("rowsPerPageSelect");
    
    if (rowsPerPageSelect) {
        const savedRowsPerPage = localStorage.getItem('allItemsTableRowsPerPage');
        if (savedRowsPerPage) {
            rowsPerPageSelect.value = savedRowsPerPage;
            rowsPerPage = parseInt(savedRowsPerPage);
        }
        
        rowsPerPageSelect.addEventListener('change', function() {
            rowsPerPage = parseInt(this.value);
            thisCurrentPage = 1;
            displayPage(thisCurrentPage);
            updateItemCounts();
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
        qtyFilter: document.getElementById("quantityFilterContainerAll"),
        dateFilter: document.getElementById("dateFilterContainerAll"),
        statusFilter: document.getElementById("statusFilterContainer"),
        columnFilter: document.getElementById("columnFilterContainer"),
        editItemModal: document.getElementById('editItemModal'),
        itemViewModal: document.getElementById('itemViewModal'),
        rowsPerPageSelect: document.getElementById("rowsPerPageSelect") 
    };
}

// =============================================
// TABLE DISPLAY & PAGINATION - OPTIMIZED
// =============================================
function displayPage(page = 1) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    requestAnimationFrame(() => {
        // Hide all rows first
        allRows.forEach(row => row.style.display = "none");
        
        // Show only rows for current page
        for (let i = start; i < Math.min(end, filteredRows.length); i++) {
            if (filteredRows[i]) {
                filteredRows[i].style.display = "";
            }
        }
        
        updatePagination(page);
        updateRowNumbers(); // ADD THIS LINE - it was missing!
        updatePageInfo();
        
        // Handle item highlighting if redirecting
        if (isRedirectingToItem && targetItemId && page === thisCurrentPage) {
            setTimeout(() => {
                const targetRow = document.querySelector(`tr[data-item-id="${targetItemId}"]`);
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
// TABLE MANAGEMENT - OPTIMIZED
// =============================================
function initializeTable() {
    allRows = Array.from(domElements.tableBody.querySelectorAll("tr"));
    
    allRows.forEach((row, index) => {
        row._data = {
            category: getCellData(row, 'category').toLowerCase().trim(),
            serialNumber: getCellData(row, 'serialNumber').toLowerCase(),
            itemName: getCellData(row, 'itemName').toLowerCase(),
            description: getCellData(row, 'description').toLowerCase(),
            brand: getCellData(row, 'brand').toLowerCase().trim(),
            model: getCellData(row, 'model').toLowerCase(),
            availableQuantity: parseInt(getCellData(row, 'availableQuantity') || 0),
            dateAcquired: getCellData(row, 'dateAcquired').trim(),
            status: getCellData(row, 'status').trim(),
            itemId: getCellData(row, 'itemId').toLowerCase()
        };
    });
    
    filteredRows = [...allRows];
    displayPage(1);
    updateItemCounts();
}

function updateRowNumbers() {
    const startIndex = (thisCurrentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, filteredRows.length);
    
    // Only update visible rows for better performance
    for (let i = startIndex; i < endIndex; i++) {
        const row = filteredRows[i];
        const cell = row.cells[0]; // Row number is always in first cell
        if (cell) {
            // Calculate display number (1-based) for absolute position in filtered results
            const displayNumber = i + 1;
            cell.textContent = displayNumber;
        }
    }
}


function showNoResultsMessage(show) {
    let noResultsRow = document.getElementById('noResultsMessage');
    
    if (show && !noResultsRow) {
        noResultsRow = document.createElement("tr");
        noResultsRow.id = 'noResultsMessage';
        noResultsRow.innerHTML = `<td colspan="17" style="text-align:center; color:#666; padding:20px;">No items found matching your filters.</td>`;
        domElements.tableBody.appendChild(noResultsRow);
    } else if (!show && noResultsRow) {
        noResultsRow.remove();
    }
}

// =============================================
// FILTERING & SEARCH - FIXED VERSION
// =============================================
function filterItemsAll() {
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
    console.log('Current filters:', currentFiltersAll);
    console.log('Current sort:', currentSort);
    
    // First apply all filters from the full dataset
    filteredRows = allRows.filter(row => {
        const data = row._data;
        
        // Date filter
        if (!passesDateFilter(data.dateAcquired)) return false;
        
        // Status filter
        if (currentFiltersAll.status.length > 0 && !currentFiltersAll.status.includes(data.status)) {
            return false;
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
    if (!currentFiltersAll.dateFrom && !currentFiltersAll.dateTo) return true;
    
    const itemDate = parseTableDate(dateAcquired);
    if (!itemDate) return false;
    
    if (currentFiltersAll.dateFrom) {
        const fromDate = new Date(currentFiltersAll.dateFrom);
        fromDate.setHours(0, 0, 0, 0);
        if (itemDate < fromDate) return false;
    }
    
    if (currentFiltersAll.dateTo) {
        const toDate = new Date(currentFiltersAll.dateTo);
        toDate.setHours(23, 59, 59, 999);
        if (itemDate > toDate) return false;
    }
    
    return true;
}

function passesSearchFilter(data, searchValue) {
    return data.itemId.includes(searchValue) ||
           data.itemName.includes(searchValue) || 
           data.model.includes(searchValue) || 
           data.brand.includes(searchValue) ||
           data.serialNumber.includes(searchValue) ||
           data.description.includes(searchValue) ||
           data.category.includes(searchValue);
}

function generateFilterKey() {
    const searchValue = domElements.searchInput?.value || '';
    return `${currentFiltersAll.dateFrom}|${currentFiltersAll.dateTo}|${currentFiltersAll.status.sort().join(',')}|${searchValue}|${currentSort.field}|${currentSort.order}`;
}

function updateDisplay() {
    showNoResultsMessage(filteredRows.length === 0);
    
    if (!isRedirectingToItem) {
        thisCurrentPage = 1;
    }
    
    displayPage(thisCurrentPage); // This will call updateRowNumbers()
    updateItemCounts();
}

// =============================================
// SORTING FUNCTIONS - SIMPLIFIED VERSION
// =============================================
function sortByQuantityAll(order = "asc") {
    console.log('=== SORTING BY QUANTITY ===');
    console.log('Current filters:', currentFiltersAll);
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
    console.log('Current filters:', currentFiltersAll);
    
    // Store the _data from current rows before clearing
    const rowDataMap = new Map();
    filteredRows.forEach((row, index) => {
        rowDataMap.set(index, row._data);
    });
    
    // Clear and rebuild table
    tableBody.innerHTML = '';
    filteredRows.forEach((row, index) => {
        tableBody.appendChild(row);
        // Restore the _data property
        row._data = rowDataMap.get(index);
    });
    
    // Update display
    updateRowNumbers();
    displayPage(thisCurrentPage);
}   

// =============================================
// FILTER CONTROLS - OPTIMIZED
// =============================================
// =============================================
// FILTER CONTROLS - OPTIMIZED
// =============================================
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
    setupQuantityFilters();
    setupDateFilters();
    setupStatusFilters();

    // Reset all filters
    document.getElementById("resetAllFiltersBtn")?.addEventListener("click", resetAllFiltersAll);

    // Search functionality
    domElements.searchInput?.addEventListener("input", () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => filterItemsAll(), 150);
    });

    function setupQuantityFilters() {
        document.getElementById("sortLowToHighAll")?.addEventListener("click", () => {
            sortByQuantityAll("asc");
            closeAllFilters(); // Close filter after applying
        });

        document.getElementById("sortHighToLowAll")?.addEventListener("click", () => {
            sortByQuantityAll("desc");
            closeAllFilters(); // Close filter after applying
        });
    }

    function setupDateFilters() {
        document.getElementById("filterByDateBtnAll")?.addEventListener("click", () => {
            currentFiltersAll.dateFrom = domElements.dateFrom.value;
            currentFiltersAll.dateTo = domElements.dateTo.value;
            filterItemsAll();
            closeAllFilters(); // Close filter after applying
        });

        document.getElementById("resetDateFilterBtnAll")?.addEventListener("click", () => {
            domElements.dateFrom.value = "";
            domElements.dateTo.value = "";
            currentFiltersAll.dateFrom = "";
            currentFiltersAll.dateTo = "";

            
            filterItemsAll();

            applyFiltersAndSort(); // <-- Add this
            updateTableDOM();      // <-- Add this
            updateItemCounts();    // <-- Add this
            closeAllFilters(); // Close filter after resetting
        });
    }

    function setupStatusFilters() {
        document.getElementById("filterByStatusBtn")?.addEventListener("click", () => {
            const statusCheckboxes = document.querySelectorAll('input[name="statusFilter"]:checked');
            currentFiltersAll.status = Array.from(statusCheckboxes).map(cb => cb.value);
            filterItemsAll();
            closeAllFilters(); // Close filter after applying
        });

        document.getElementById("resetStatusFilterBtn")?.addEventListener("click", () => {
            document.querySelectorAll('input[name="statusFilter"]').forEach(cb => cb.checked = false);
            currentFiltersAll.status = [];
            filterItemsAll();
            closeAllFilters(); // Close filter after resetting
        });
    }
}

function resetAllFiltersAll() {
    domElements.dateFrom.value = "";
    domElements.dateTo.value = "";
    currentFiltersAll.dateFrom = "";
    currentFiltersAll.dateTo = "";
    document.querySelectorAll('input[name="statusFilter"]').forEach(cb => cb.checked = false);
    currentFiltersAll.status = [];
    domElements.searchInput.value = "";
    
    // Clear sort when resetting all filters
    currentSort.field = null;
    currentSort.order = null;
    
    filterCache.clear();
    dateCache.clear();
    filterItemsAll();
}
function initSearchFunctionalityAll() {
    // Handled in initFilterControlsAll
}

// =============================================
// COLUMN FILTERING - OPTIMIZED
// =============================================
const columnFilterKey = "columnFilterSettingsAll";

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

function initColumnFiltering() {
    document.querySelectorAll("#columnFilterContainer input[type='checkbox']").forEach(checkbox => {
        checkbox.addEventListener("change", function () {
            const colIndex = this.getAttribute("data-column");
            const table = document.querySelector(".itemTable");

            table.querySelectorAll(`thead th:nth-child(${parseInt(colIndex) + 1}), tbody tr td:nth-child(${parseInt(colIndex) + 1})`)
                .forEach(element => element.style.display = this.checked ? "" : "none");

            saveColumnSettings();
        });
    });

    document.getElementById("resetColumnFilterBtn")?.addEventListener("click", () => {
        document.querySelectorAll("#columnFilterContainer input[type='checkbox']").forEach(cb => {
            if (!cb.checked) {
                cb.checked = true;
                cb.dispatchEvent(new Event("change"));
            }
        });
        localStorage.removeItem(columnFilterKey);
    });
}

// Initialize column filtering
initColumnFiltering();

// =============================================
// TABLE ACTIONS - OPTIMIZED
// =============================================
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
            sessionStorage.setItem('deletedItemAllName', itemName);
            window.location.href = `/templates/inventory/function/deleteSpecificItem.php?id=${itemId}&source=${source}`;
        }
    });
}

function handleEditItem(editBtn) {
    const getData = attr => editBtn.getAttribute(attr) || '';
    
    // Set form values
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
    document.getElementById('edit-available-item-qty').value = getData('data-available-qty');
    document.getElementById('edit-item-status').value = getData('data-item-status');
    document.getElementById('edit-remarks').value = getData('data-remarks');
    document.getElementById('edit-item-photo').value = '';

    // Handle date
    const dateAcquired = getData('data-date-acquired');
    document.getElementById('edit-item-date-acquired').value = dateAcquired && !isNaN(Date.parse(dateAcquired)) 
        ? new Date(dateAcquired).toISOString().split('T')[0] : '';

    // Handle category
    const categoryId = getData('data-category-id');
    const categorySelect = document.getElementById('edit-item-category-id');
    Array.from(categorySelect.options).forEach(opt => opt.selected = opt.value === categoryId);

    // Handle photo
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
    // Set all view modal content
    const viewFields = {
        "view-item-name": viewBtn.dataset.name,
        "view-item-category": viewBtn.dataset.category || 'None',
        "view-item-description": viewBtn.dataset.description || 'None',
        "view-item-brand": viewBtn.dataset.brand || 'None',
        "view-item-model": viewBtn.dataset.model || 'None',
        "view-item-serial": viewBtn.dataset.serial || 'None',
        "view-item-quantity": viewBtn.dataset.qty,
        "view-available-quantity": viewBtn.dataset.availableQty || viewBtn.dataset.qty,
        "view-item-unit": viewBtn.dataset.unit || 'None',
        "view-item-unit-cost": viewBtn.dataset.unitcost,
        "view-item-total-cost": viewBtn.dataset.totalcost,
        "view-item-status": viewBtn.dataset.itemstatus,
        "view-item-remarks": viewBtn.dataset.remarks || 'None'
    };

    Object.entries(viewFields).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    });

    // Format dates
    const dateAcquired = viewBtn.dataset.dateAcquired;
    document.getElementById("view-item-date-acquired").textContent = dateAcquired
        ? new Date(dateAcquired).toLocaleDateString('en-US', {
            year: 'numeric', month: 'long', day: '2-digit'
        }).replace(',', '').replace(' ', '-')
        : 'N/A';

    document.getElementById("view-item-created-at").textContent = viewBtn.dataset.created || 'N/A';

    // Handle photo
    const photo = viewBtn.dataset.photo;
    document.getElementById("view-item-photo").src =
        photo && photo !== '' ? '/' + photo : '/images/user-profile/default-image.jpg';

    // Save scroll position and show modal
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

// =============================================
// UTILITY FUNCTIONS - OPTIMIZED
// =============================================
function updateItemCounts() {
    const totalItems = allRows.length;
    const filteredItems = filteredRows.length;
    const isFiltered = filteredItems !== totalItems;

    // Show total items count
    updateCountElement('totalItemsCount', totalItems);
    updateCountElement('totalItemsCount2', totalItems);
    updateCountElement('totalCount', totalItems);
    updateCountElement('totalItems', totalItems);

    // Show filtered items count with pipe separator (only when filtered)
    const filteredCountElement = document.getElementById('filteredItemsCount');
    if (filteredCountElement) {
        if (isFiltered) {
            const hasStatusFilter = currentFiltersAll.status.length > 0;
            const hasDateFilter = currentFiltersAll.dateFrom || currentFiltersAll.dateTo;
            const hasSearchFilter = domElements.searchInput?.value;
            const hasSort = currentSort.field;
            
            let filterDescription = '';
            
            // Get actual values
            const searchTerm = domElements.searchInput?.value || '';
            const dateFrom = currentFiltersAll.dateFrom || '';
            const dateTo = currentFiltersAll.dateTo || '';
            
            // Format dates to "Mon DD YYYY" format (e.g., "Nov 25 2025")
            const formatDate = (dateString) => {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
            };
            
            const fromDate = formatDate(dateFrom);
            const toDate = formatDate(dateTo);
            
            // Handle combinations with actual values
            if (hasStatusFilter && hasDateFilter && hasSearchFilter) {
                const statusText = currentFiltersAll.status.length === 1 ? 
                    `status ${currentFiltersAll.status[0]}` : 
                    `${currentFiltersAll.status.length} statuses`;
                const dateText = dateFrom && dateTo ? 
                    `date ${fromDate} to ${toDate}` : 
                    (dateFrom ? `date from ${fromDate}` : `date until ${toDate}`);
                filterDescription = `items for ${statusText}, ${dateText}, and search value "${searchTerm}"`;
            }
            else if (hasStatusFilter && hasDateFilter) {
                const statusText = currentFiltersAll.status.length === 1 ? 
                    `status ${currentFiltersAll.status[0]}` : 
                    `${currentFiltersAll.status.length} statuses`;
                const dateText = dateFrom && dateTo ? 
                    `date ${fromDate} to ${toDate}` : 
                    (dateFrom ? `date from ${fromDate}` : `date until ${toDate}`);
                filterDescription = `items for ${statusText} and ${dateText}`;
            }
            else if (hasStatusFilter && hasSearchFilter) {
                const statusText = currentFiltersAll.status.length === 1 ? 
                    `status ${currentFiltersAll.status[0]}` : 
                    `${currentFiltersAll.status.length} statuses`;
                filterDescription = `items for ${statusText} and search "${searchTerm}"`;
            }
            else if (hasDateFilter && hasSearchFilter) {
                const dateText = dateFrom && dateTo ? 
                    `date ${fromDate} to ${toDate}` : 
                    (dateFrom ? `date from ${fromDate}` : `date until ${toDate}`);
                filterDescription = `items for ${dateText} and search "${searchTerm}"`;
            }
            else if (hasStatusFilter) {
                const statusText = currentFiltersAll.status.length === 1 ? 
                    `status ${currentFiltersAll.status[0]}` : 
                    `${currentFiltersAll.status.length} statuses`;
                filterDescription = `items for ${statusText}`;
            }
            else if (hasDateFilter) {
                const dateText = dateFrom && dateTo ? 
                    `date ${fromDate} to ${toDate}` : 
                    (dateFrom ? `date from ${fromDate}` : `date until ${toDate}`);
                filterDescription = `items for ${dateText}`;
            }
            else if (hasSearchFilter) {
                filterDescription = `items for search value "${searchTerm}"`;
            }
            else if (hasSort) {
                filterDescription = `items (sorted)`;
            }
            else {
                filterDescription = `matching items`;
            }
            
            filteredCountElement.innerHTML = `| ${filteredItems} ${filterDescription}`;
            filteredCountElement.style.display = 'inline';
        } else {
            filteredCountElement.style.display = 'none';
        }
    }

    updateActiveFiltersDisplay();
    updatePageInfo();
}
function updatePageInfo() {
    const startIndex = (thisCurrentPage - 1) * rowsPerPage + 1;
    const endIndex = Math.min(thisCurrentPage * rowsPerPage, filteredRows.length);
    const filteredItems = filteredRows.length;
    
    const pageInfoElement = document.getElementById('pageInfo');
    if (pageInfoElement) {
        pageInfoElement.textContent = filteredItems > 0 
            ? `Showing ${startIndex} to ${endIndex} of ${filteredItems} entries`
            : 'No entries to show';
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
    
    // Date filter details
    if (currentFiltersAll.dateFrom || currentFiltersAll.dateTo) {
        if (currentFiltersAll.dateFrom && currentFiltersAll.dateTo) {
            activeFilters.push(`Date: ${currentFiltersAll.dateFrom} to ${currentFiltersAll.dateTo}`);
        } else if (currentFiltersAll.dateFrom) {
            activeFilters.push(`Date: from ${currentFiltersAll.dateFrom}`);
        } else if (currentFiltersAll.dateTo) {
            activeFilters.push(`Date: until ${currentFiltersAll.dateTo}`);
        }
    }
    
    // Status filter details
    if (currentFiltersAll.status.length > 0) {
        if (currentFiltersAll.status.length === 1) {
            activeFilters.push(`Status: ${currentFiltersAll.status[0]}`);
        } else {
            activeFilters.push(`Status: ${currentFiltersAll.status.length} selected`);
        }
    }
    
    // Search filter details
    const searchValue = domElements.searchInput?.value || '';
    if (searchValue) {
        activeFilters.push(`Search: "${searchValue}"`);
    }
    
    // Sort details
    if (currentSort.field) {
        const sortDirection = currentSort.order === "asc" ? "Low to High" : "High to Low";
        activeFilters.push(`Sorted by: Quantity (${sortDirection})`);
    }
    
    // Update the display
    if (activeFilters.length > 0) {
        activeFiltersElement.innerHTML = `<strong>Active filters:</strong> ${activeFilters.join(' • ')}`;
        activeFiltersElement.style.color = '#495057';
    } else {
        activeFiltersElement.innerHTML = 'No active filters';
        activeFiltersElement.style.color = '#6c757d';
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
// FORM FUNCTIONS
// =============================================
function calculateEditTotalCost() {
    const qtyInput = document.getElementById("edit-item-qty");
    const unitCostInput = document.getElementById("edit-item-unit-cost");
    const totalCostInput = document.getElementById("edit-item-total-cost");

    function updateTotalCost() {
        const qty = parseFloat(qtyInput.value) || 0;
        const unitCost = parseFloat(unitCostInput.value) || 0;
        totalCostInput.value = (qty * unitCost).toFixed(2);
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

// =============================================
// PRINT FUNCTIONALITY - OPTIMIZED
// =============================================
function printAllCurrentTableView() {
    const printWindow = window.open('', '_blank');
    const currentDate = new Date().toLocaleDateString();
    
    const visibleRows = Array.from(document.querySelectorAll('#inventoryTableBody tr'))
        .filter(row => row.style.display !== 'none');
    
    const headers = Array.from(document.querySelectorAll('.itemTable thead th'))
        .map(th => th.textContent.trim())
        .filter((header, index) => {
            if (index === columnMap.image || index === columnMap.actions) return false;
            const colCheckbox = document.querySelector(`#columnFilterContainer input[data-column="${index}"]`);
            return !colCheckbox || colCheckbox.checked;
        });

    // Calculate total cost
    let totalCost = visibleRows.reduce((sum, row) => {
        const actionBtn = row.querySelector('.action-btn.view');
        if (actionBtn?.dataset.totalcost) {
            return sum + (parseFloat(actionBtn.dataset.totalcost) || 0);
        } else if (actionBtn?.dataset.qty && actionBtn?.dataset.unitcost) {
            return sum + (parseFloat(actionBtn.dataset.qty) || 0) * (parseFloat(actionBtn.dataset.unitcost) || 0);
        }
        return sum;
    }, 0);

    const formattedTotalCost = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(totalCost);

    const tableHTML = generatePrintHTML(headers, visibleRows, currentDate, formattedTotalCost);
    
    printWindow.document.write(tableHTML);
    printWindow.document.close();
}

function generatePrintHTML(headers, visibleRows, currentDate, formattedTotalCost) {
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>All Items - Inventory Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #000; }
                .header-container { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 15px; }
                .logo-container { flex: 0 0 auto; margin-left: 150px; }
                .logo { width: 120px; height: 120px; object-fit: contain; }
                .header-content { flex: 1; text-align: center; }
                .header-content h1 { margin: 0 0 8px 0; font-size: 24pt; color: #2c3e50; }
                .header-content .subtitle { font-size: 14pt; color: #666; margin-bottom: 5px; }
                .header-content .department { font-size: 12pt; color: #888; font-weight: bold; }
                .print-info { display: flex; justify-content: space-between; margin-bottom: 20px; padding: 12px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; font-size: 10pt; }
                .info-item { text-align: center; flex: 1; }
                .info-item strong { display: block; color: #2c3e50; margin-bottom: 3px; }
                .total-cost { color: #1d6f42; font-weight: bold; font-size: 11pt; }
                table { width: 100%; border-collapse: collapse; font-size: 9pt; page-break-inside: auto; margin-bottom: 20px; }
                th, td { border: 1px solid #333; padding: 8px; text-align: center; page-break-inside: avoid; }
                th { background-color: #2c3e50; color: white; font-weight: bold; border-bottom: 2px solid #000; }
                tr:nth-child(even) { background-color: #f8f9fa; }
                .number, .cost-cell { text-align: right; font-family: "Courier New", monospace; }
                .cost-cell { font-weight: bold; }
                .print-footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 9pt; color: #666; text-align: center; }
                .no-print { display: none; }
                @page { size: landscape; margin: 1cm; }
                @media print {
                    body { margin: 0; padding: 15px; }
                    .header-container { margin-bottom: 15px; }
                    .print-info { background-color: #f8f9fa !important; }
                }
            </style>
        </head>
        <body>
            <div class="header-container">
                <div class="logo-container">
                    <img src="/images/assets/baliwasan.png" alt="BSCI Logo" class="logo" onerror="this.style.display='none'">
                </div>
                <div class="header-content">
                    <h1>COMPLETE INVENTORY REPORT</h1>
                    <div class="subtitle">All Items Summary</div>
                    <div class="department">Baliwasan Central School Inventory System</div>
                </div>
            </div>
            
            <div class="print-info">
                <div class="info-item"><strong>Generated Date</strong><span>${currentDate}</span></div>
                <div class="info-item"><strong>Total Items</strong><span>${visibleRows.length}</span></div>
                <div class="info-item"><strong>Total Inventory Value</strong><span class="total-cost">${formattedTotalCost}</span></div>
            </div>
            
            <table>
                <thead><tr>${headers.map(header => `<th>${header}</th>`).join('')}</tr></thead>
                <tbody>
                    ${visibleRows.map(row => {
                        const cells = Array.from(row.cells);
                        return `<tr>${cells.map((cell, index) => {
                            if (index === columnMap.image || index === columnMap.actions) return '';
                            const colCheckbox = document.querySelector(`#columnFilterContainer input[data-column="${index}"]`);
                            if (colCheckbox && !colCheckbox.checked) return '';
                            
                            let cellContent = cell.textContent.trim().replace(/—/g, 'N/A');
                            let cellClass = '';
                            
                            if (index === columnMap.quantity || index === columnMap.availableQuantity) {
                                cellClass = 'number';
                            } else if (cellContent.includes('₱') || cell.textContent.includes('PHP')) {
                                cellClass = 'cost-cell';
                            }
                            
                            return `<td class="${cellClass}">${cellContent || ''}</td>`;
                        }).join('')}</tr>`;
                    }).join('')}
                </tbody>
            </table>
            
            <div class="print-footer">
                <p>Report generated on ${currentDate} | Total Items: ${visibleRows.length} | Total Inventory Value: ${formattedTotalCost}</p>
            </div>
            
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(() => window.close(), 500);
                };
            </script>
        </body>
        </html>
    `;
}

// =============================================
// EVENT LISTENERS
// =============================================
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemId = urlParams.get('item_id');
    if (itemId && !isRedirectingToItem) {
        setTimeout(() => scrollToItem(itemId), 1200);
    }
});

window.addEventListener('popstate', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemId = urlParams.get('item_id');
    if (itemId && !isRedirectingToItem) {
        setTimeout(() => scrollToItem(itemId), 800);
    }
});


// =============================================
// DRAG TO SCROLL FUNCTIONALITY 
// =============================================
function initDragToScroll() {
    const tableContainer = document.querySelector('.table-scroll-wrapper');
    if (!tableContainer) return;

    let isDragging = false;
    let startX;
    let scrollLeft;

    function handleDragStart(e) {
      
        if (e.target.closest('button') || e.target.closest('input') || e.target.closest('select') || e.target.closest('a')) {
            return;
        }
        
        isDragging = true;
        startX = e.pageX;
        scrollLeft = tableContainer.scrollLeft;
        tableContainer.style.cursor = 'grabbing';
        tableContainer.style.userSelect = 'none';
        
        e.preventDefault();
    }

    function handleDragEnd() {
        isDragging = false;
        tableContainer.style.cursor = 'grab';
        tableContainer.style.userSelect = 'auto';
    }

    function handleDragMove(e) {
        if (!isDragging) return;
        
        const x = e.pageX;
        const walk = x - startX;
        tableContainer.scrollLeft = scrollLeft - walk;
    }

    function handleMouseEnter() {
        tableContainer.style.cursor = 'grab';
    }

    function handleMouseLeave() {
        if (!isDragging) {
            tableContainer.style.cursor = 'default';
        }
    }


    tableContainer.addEventListener('mousedown', handleDragStart);
    document.addEventListener('mousemove', handleDragMove);
    document.addEventListener('mouseup', handleDragEnd);
    
    tableContainer.addEventListener('mouseenter', handleMouseEnter);
    tableContainer.addEventListener('mouseleave', handleMouseLeave);

    tableContainer.style.cursor = 'grab';
}