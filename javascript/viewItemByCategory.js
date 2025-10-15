let currentFilters = {
    brand: [],
    quantity: "",
    dateFrom: "",
    dateTo: "",
    status: []
};

let allRows = [];
let filteredRows = [];
let thisCurrentPage = 1;
let rowsPerPage = parseInt(localStorage.getItem('itemsTableRowsPerPage')) || 10;
let debounceTimer = null;
let filterCache = new Map();
let maxCacheSize = 100;

// Performance monitoring
let perf = {
    filterTime: 0,
    renderTime: 0
};

let domElements = {};

// Data indexing for faster filtering
let dataIndex = {
    brands: new Set(),
    statuses: new Set(),
    searchIndex: new Map()
};

document.addEventListener('DOMContentLoaded', function() {
    initializeDOMCache();
    initializeTable();
    initFilterControls();
    initTableActions();
    calculateEditTotalCost();
    initDateValidation();

    restoreColumnSettings();
    initRowsPerPageSelector();
});

// function for rows per page selector
function initRowsPerPageSelector() {
    const rowsPerPageSelect = document.getElementById("rowsPerPageSelect");
    
    if (rowsPerPageSelect) {
        // Load saved preference FIRST, before setting up event listener
        const savedRowsPerPage = localStorage.getItem('itemsTableRowsPerPage');
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
            localStorage.setItem('itemsTableRowsPerPage', this.value);
        });
    }
}

function initializeDOMCache() {
    domElements = {
        tableBody: document.getElementById("inventoryTableBody"),
        pagination: document.getElementById("pagination"),
        searchInput: document.getElementById("searchItem"),
        brandSelect: document.getElementById("brandSelect"),
        dateFrom: document.getElementById("dateFrom"),
        dateTo: document.getElementById("dateTo"),
        brandFilter: document.getElementById("brandFilterContainer"),
        qtyFilter: document.getElementById("quantityFilterContainer"),
        dateFilter: document.getElementById("dateFilterContainer"),
        statusFilter: document.getElementById("statusFilterContainer"),
        columnFilter: document.getElementById("columnFilterContainer"),
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

//  function to show current page information
function updatePageInfo() {
    const startIndex = (thisCurrentPage - 1) * rowsPerPage + 1;
    const endIndex = Math.min(thisCurrentPage * rowsPerPage, filteredRows.length);
    const totalItems = filteredRows.length;
    
    // Update page info display if element exists
    const pageInfoElement = document.getElementById('pageInfo'); // ← This element doesn't exist!
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
    const startTime = performance.now();
    
    allRows = Array.from(domElements.tableBody.querySelectorAll("tr"));
    
    // Build search index for faster filtering
    buildSearchIndex();
    
    filteredRows = [...allRows];
    displayPage(1);
    updateItemCounts(); 
    perf.renderTime = performance.now() - startTime;
}

function buildSearchIndex() {
    allRows.forEach((row, index) => {
        const cells = row.cells;
        const rowData = {
            brand: (cells[6]?.textContent || '').toLowerCase().trim(), 
            quantity: parseInt(cells[9]?.textContent || 0), 
            dateAcquired: cells[11]?.textContent.trim(), 
            status: cells[12]?.textContent.trim(),
            itemName: (cells[4]?.textContent || '').toLowerCase(), 
            model: (cells[7]?.textContent || '').toLowerCase(), 
            serialNumber: (cells[3]?.textContent || '').toLowerCase(), 
            description: (cells[5]?.textContent || '').toLowerCase(), 
            itemId: (cells[1]?.textContent || '').toLowerCase(), 
            dateValue: parseTableDate(cells[11]?.textContent.trim()) 
        };
        

        row._data = rowData;
        
        // Update indexes
        if (rowData.brand) dataIndex.brands.add(rowData.brand);
        if (rowData.status) dataIndex.statuses.add(rowData.status);
        
        // Build search index for terms longer than 2 characters
        const searchableText = `${rowData.itemId} ${rowData.itemName} ${rowData.model} ${rowData.brand} ${rowData.serialNumber} ${rowData.description}`;
        const terms = searchableText.split(/\s+/).filter(term => term.length > 2);
        
        terms.forEach(term => {
            if (!dataIndex.searchIndex.has(term)) {
                dataIndex.searchIndex.set(term, new Set());
            }
            dataIndex.searchIndex.get(term).add(index);
        });
    });
}
// Optimized filtering with indexed search
// Optimized filtering with indexed search
function filterItems() {
    const filterKey = generateFilterKey();
    
    if (filterCache.has(filterKey)) {
        filteredRows = filterCache.get(filterKey);
        updateDisplay();
        return;
    }
    
    const selectedBrands = domElements.brandSelect ? 
        Array.from(domElements.brandSelect.selectedOptions).map(opt => opt.value.toLowerCase().trim()) : [];
    const searchValue = (domElements.searchInput?.value || '').toLowerCase();
    
    const startTime = performance.now();
    
    requestAnimationFrame(() => {
        console.log('=== FILTERING STARTED (Category View) ===');
        console.log('Date filters:', {
            dateFrom: currentFilters.dateFrom,
            dateTo: currentFilters.dateTo
        });
        
        let results;
        
        // Use indexed search for better performance with large datasets
        if (searchValue && searchValue.length > 2) {
            results = performIndexedSearch(selectedBrands, searchValue);
        } else {
            results = performLinearSearch(selectedBrands, searchValue);
        }
        
        filteredRows = results;
        
        console.log('=== FILTERING COMPLETE (Category View) ===');
        console.log('Results:', filteredRows.length, 'of', allRows.length);
        
        // Update cache with size limit
        if (filterCache.size >= maxCacheSize) {
            const firstKey = filterCache.keys().next().value;
            filterCache.delete(firstKey);
        }
        filterCache.set(filterKey, filteredRows);
        
        perf.filterTime = performance.now() - startTime;
        
        updateDisplay();
        updateItemCounts(); 
    });
}
function performIndexedSearch(selectedBrands, searchValue) {
    const searchTerms = searchValue.split(/\s+/).filter(term => term.length > 2);
    let matchingIndexes = new Set();
    
    // Start with all indexes for AND search
    if (searchTerms.length > 0) {
        searchTerms.forEach((term, index) => {
            const termMatches = dataIndex.searchIndex.get(term) || new Set();
            if (index === 0) {
                termMatches.forEach(i => matchingIndexes.add(i));
            } else {
                // Intersect with previous matches
                const currentMatches = new Set(matchingIndexes);
                matchingIndexes.clear();
                termMatches.forEach(i => {
                    if (currentMatches.has(i)) matchingIndexes.add(i);
                });
            }
        });
    } else {
        // No search terms, include all items
        allRows.forEach((_, index) => matchingIndexes.add(index));
    }
    
    // Apply other filters
    const results = [];
    for (const index of matchingIndexes) {
        const row = allRows[index];
        const data = row._data;
        
        if (passesFilters(data, selectedBrands)) {
            results.push(row);
        }
    }
    
    return results;
}

function performLinearSearch(selectedBrands, searchValue) {
    return allRows.filter(row => {
        const data = row._data;
        
        if (!passesFilters(data, selectedBrands)) {
            return false;
        }
        
        // Apply search filter if present - ADD itemId HERE
        if (searchValue) {
            return (
                data.itemId.includes(searchValue) || // Added itemId search
                data.itemName.includes(searchValue) || 
                data.model.includes(searchValue) || 
                data.brand.includes(searchValue) ||
                data.serialNumber.includes(searchValue) ||
                data.description.includes(searchValue)
            );
        }
        
        return true;
    });
}
function passesFilters(data, selectedBrands) {
    // Brand filter
    if (selectedBrands.length > 0 && !selectedBrands.some(brand => data.brand.includes(brand))) {
        return false;
    }
    
    // Quantity filter
    if (currentFilters.quantity === "out" && data.quantity !== 0) return false;
    if (currentFilters.quantity === "available" && data.quantity <= 0) return false;
    
    // Date filter
    if (currentFilters.dateFrom || currentFilters.dateTo) {
        if (!data.dateValue) return false;
        
        const rowDate = data.dateValue;
        
        if (currentFilters.dateFrom) {
            const fromDate = new Date(currentFilters.dateFrom);
            fromDate.setHours(0, 0, 0, 0); // Start of day
            if (rowDate < fromDate) return false;
        }
        
        if (currentFilters.dateTo) {
            const toDate = new Date(currentFilters.dateTo);
            toDate.setHours(23, 59, 59, 999); // End of day
            if (rowDate > toDate) return false;
        }
    }
    
    // Status filter
    if (currentFilters.status.length > 0 && !currentFilters.status.includes(data.status)) {
        return false;
    }
    
    return true;
}

function generateFilterKey() {
    const selectedBrands = domElements.brandSelect ? 
        Array.from(domElements.brandSelect.selectedOptions).map(opt => opt.value).sort().join(',') : '';
    const searchValue = domElements.searchInput?.value || '';
    
    return `${selectedBrands}|${currentFilters.quantity}|${currentFilters.dateFrom}|${currentFilters.dateTo}|${currentFilters.status.sort().join(',')}|${searchValue}`;
}

function updateDisplay() {
    updateRowNumbers();
    showNoResultsMessage(filteredRows.length === 0);
    thisCurrentPage = 1;
    displayPage(thisCurrentPage);
}

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
        
        // Format 1: "Mar-15-2024" (M-d-Y) - FIXED VERSION
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
                // Ensure it's valid
                if (result.getDate() !== day || result.getMonth() !== month || result.getFullYear() !== year) {
                    result = null;
                }
            }
        }
        // Format 2: "2024-03-15" (ISO)
        else if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
            result = new Date(dateString);
        }
        // Format 3: Try native Date parsing for other formats
        else {
            result = new Date(dateString);
        }
        
        // Validate the result
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
function updateRowNumbers() {
    // Only update visible rows for better performance
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
        noResultsRow.innerHTML = `<td colspan="13" style="text-align:center; color:#666; padding:20px;">No items found matching your filters.</td>`;
        domElements.tableBody.appendChild(noResultsRow);
    } else if (!show && noResultsRow) {
        noResultsRow.remove();
    }
}

function displayPage(page = 1) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    
    const startTime = performance.now();

    requestAnimationFrame(() => {
        // Hide all rows first 
        allRows.forEach(row => {
            row.style.display = "none";
        });
        
        // Show only current page rows
        for (let i = start; i < Math.min(end, filteredRows.length); i++) {
            if (filteredRows[i]) {
                filteredRows[i].style.display = "";
            }
        }
        
        updatePagination(page);
        
        perf.renderTime = performance.now() - startTime;
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

// FILTER CONTROLS FOR CLOSE-OPEN
function initFilterControls() {
    const filterToggles = [
        { id: "toggleBrandFilter", container: domElements.brandFilter },
        { id: "toggleQtyFilter", container: domElements.qtyFilter },
        { id: "toggleDateFilter", container: domElements.dateFilter },
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

    // Optimized event handlers with  debouncing
    const createOptimizedHandler = (callback) => {
        return () => {
            requestAnimationFrame(() => {
                callback();
            });
        };
    };

    // Brand filter
    document.getElementById("filterByBrandBtn")?.addEventListener("click", createOptimizedHandler(() => {
        filterItems();
        closeAllFilters();
    }));
    
    document.getElementById("resetBrandFilterBtn")?.addEventListener("click", createOptimizedHandler(() => {
        if (domElements.brandSelect) {
            Array.from(domElements.brandSelect.options).forEach(option => option.selected = false);
            filterItems();
        }
        closeAllFilters();
    }));

    // Search with  debouncing
    domElements.searchInput?.addEventListener("input", () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            filterItems();
        }, 200);
    });

    // Quantity filter
    const quantityHandlers = {
        "sortLowToHigh": () => sortByQuantity("asc"),
        "sortHighToLow": () => sortByQuantity("desc"),
        "showOutOfStock": () => { currentFilters.quantity = "out"; filterItems(); },
        "showAvailable": () => { currentFilters.quantity = "available"; filterItems(); },
        "resetQuantityFilter": () => { currentFilters.quantity = ""; filterItems(); }
    };

    Object.entries(quantityHandlers).forEach(([id, handler]) => {
        document.getElementById(id)?.addEventListener("click", createOptimizedHandler(() => {
            handler();
            closeAllFilters();
        }));
    });

    // Date filter
    document.getElementById("filterByDateBtn")?.addEventListener("click", createOptimizedHandler(() => {
        currentFilters.dateFrom = domElements.dateFrom.value;
        currentFilters.dateTo = domElements.dateTo.value;
        filterItems();
        closeAllFilters();
    }));

    document.getElementById("resetDateFilterBtn")?.addEventListener("click", createOptimizedHandler(() => {
        domElements.dateFrom.value = "";
        domElements.dateTo.value = "";
        currentFilters.dateFrom = "";
        currentFilters.dateTo = "";
        filterItems();
        closeAllFilters();
    }));

    // Status filter
    document.getElementById("filterByStatusBtn")?.addEventListener("click", createOptimizedHandler(() => {
        const statusCheckboxes = document.querySelectorAll('input[name="statusFilter"]:checked');
        currentFilters.status = Array.from(statusCheckboxes).map(cb => cb.value);
        filterItems();
        closeAllFilters();
    }));

    document.getElementById("resetStatusFilterBtn")?.addEventListener("click", createOptimizedHandler(() => {
        document.querySelectorAll('input[name="statusFilter"]').forEach(cb => cb.checked = true);
        currentFilters.status = [];
        filterItems();
        closeAllFilters();
    }));

    // Reset all filters
    document.getElementById("resetAllFiltersBtn")?.addEventListener("click", createOptimizedHandler(resetAllFilters));
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
    if (domElements.brandSelect) {
        Array.from(domElements.brandSelect.options).forEach(option => option.selected = false);
    }
    
    currentFilters.quantity = "";
    domElements.dateFrom.value = "";
    domElements.dateTo.value = "";
    currentFilters.dateFrom = "";
    currentFilters.dateTo = "";
    document.querySelectorAll('input[name="statusFilter"]').forEach(cb => cb.checked = true);
    currentFilters.status = [];
    domElements.searchInput.value = "";
    
    filterCache.clear();
    dateCache.clear();
    filterItems();
    updateItemCounts(); 
}


function initTableActions() {
    domElements.tableBody?.addEventListener('click', function(e) {
        const actionBtn = e.target.closest('.action-btn');
        if (!actionBtn) return;
        
        if (actionBtn.classList.contains('delete')) {
            handleDeleteItem(actionBtn);
        } else if (actionBtn.classList.contains('edit')) {
            handleEditItem(actionBtn);
        } else if (actionBtn.classList.contains('view')) {
            handleViewItem(actionBtn);
        } else if (actionBtn.classList.contains('add')) {
            handleAddQuantity(actionBtn);
        }
       
    });

    // Keep the form submission handler here 
    const addQuantityForm = document.getElementById('addQuantityForm');
    if (addQuantityForm) {
        addQuantityForm.addEventListener('submit', function(e) {
          
            
            // console.log('FORM SUBMISSION INTERCEPTED - DEBUG MODE');
            // console.log('Item ID:', document.getElementById('addQuantityItemId').value);
            // console.log('Quantity:', document.getElementById('quantity').value);
            
            // 
            // Swal.fire({
            //     icon: 'info',
            //     title: 'Debug Mode',
            //     text: 'Form would submit with item: ' + document.getElementById('addQuantityItemId').value
            // });
            
            
        });
    }
}

function handleAddQuantity(addBtn) {
    const itemId = addBtn.getAttribute('data-id');
    const itemName = addBtn.getAttribute('data-name');
    openAddQuantityModal(itemId, itemName);
}

function handleDeleteItem(deleteBtn) {
    const itemId = deleteBtn.getAttribute('data-id');
    const itemName = deleteBtn.getAttribute('data-name');
    const categoryId = deleteBtn.getAttribute('data-category-id'); // ← directly from button!

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
            sessionStorage.setItem('deletedItemName', itemName);
            sessionStorage.setItem('deletedItemCategory', categoryId || 'none');
            
            // Always include category_id in the redirect
            const url = `/templates/inventory/function/deleteItem.php?id=${itemId}&category_id=${categoryId}&source=category`;
            window.location.href = url;
        }
    });
}




// checkDeleteAlerts.js
// checkDeleteAlerts.js
function checkForDeletedItem() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemDeleted = urlParams.get('item_deleted');
    
    if (itemDeleted === '1') {
        // Get data from data attributes instead of PHP session
        const alertContainer = document.getElementById('deleteAlertData');
        if (alertContainer) {
            const deletedName = alertContainer.getAttribute('data-deleted-name');
            const isLastItem = alertContainer.getAttribute('data-is-last-item') === 'true';
            
            if (deletedName) {
                setTimeout(() => {
                    let message = `Item <b>${deletedName}</b> was deleted successfully.`;
                    
                    if (isLastItem) {
                        message += `<br><small><i class="fas fa-info-circle"></i> This was the last item in this category.</small>`;
                    }
                    
                    Swal.fire({
                        icon: 'success', 
                        title: 'Deleted!', 
                        html: message,
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        // FIX: Only remove item_deleted parameter, keep category_id
                        const url = new URL(window.location.href);
                        url.searchParams.delete('item_deleted');
                        // Keep all other parameters including category_id
                        window.history.replaceState({}, document.title, url.toString());
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
            // FIX: Only remove item_deleted parameter, keep category_id
            const url = new URL(window.location.href);
            url.searchParams.delete('item_deleted');
            // Keep all other parameters including category_id
            window.history.replaceState({}, document.title, url.toString());
        });
    }
}


window.addEventListener('load', function() {
    checkForDeletedItem();
});

window.addEventListener('pageshow', function() {
    checkForDeletedItem();
});


const editDataCache = new WeakMap();
function handleEditItem(editBtn) {
    let data = editDataCache.get(editBtn);
    if (!data) {
        data = {
            id: editBtn.getAttribute('data-id') || '',
            name: editBtn.getAttribute('data-name') || '',
            description: editBtn.getAttribute('data-description') || '',
            brand: editBtn.getAttribute('data-brand') || '',
            model: editBtn.getAttribute('data-model') || '',
            serial: editBtn.getAttribute('data-serial') || '',
            unit: editBtn.getAttribute('data-unit') || '',
            unitcost: editBtn.getAttribute('data-unitcost') || '',
            totalcost: editBtn.getAttribute('data-totalcost') || '',
            status: editBtn.getAttribute('data-item-status') || '',
            qty: editBtn.getAttribute('data-qty') || '',
            photo: editBtn.getAttribute('data-photo') || '',
            dateAcquired: editBtn.getAttribute('data-date-acquired') || '',
            categoryId: editBtn.getAttribute('data-category-id') || ''
        };
        editDataCache.set(editBtn, data);
    }
    
    requestAnimationFrame(() => {
        document.getElementById('edit-item-id').value = data.id;
        document.getElementById('edit-item-name').value = data.name;
        document.getElementById('edit-item-description').value = data.description;
        document.getElementById('edit-item-brand').value = data.brand;
        document.getElementById('edit-item-model').value = data.model;
        document.getElementById('edit-item-serial_number').value = data.serial;
        document.getElementById('edit-item-unit').value = data.unit;
        document.getElementById('edit-item-unit-cost').value = data.unitcost;
        document.getElementById('edit-item-total-cost').value = data.totalcost;
        document.getElementById('edit-item-status').value = data.status;
        document.getElementById('edit-item-qty').value = data.qty;
        document.getElementById('edit-item-photo').value = '';
        
        const dateAcquired = data.dateAcquired;
        document.getElementById('edit-item-date-acquired').value = 
            dateAcquired && !isNaN(Date.parse(dateAcquired)) ? 
            new Date(dateAcquired).toISOString().split('T')[0] : '';
        
        const categorySelect = document.getElementById('edit-item-category-id');
        if (categorySelect) {
            Array.from(categorySelect.options).forEach(opt => 
                opt.selected = opt.value === data.categoryId
            );
        }
        
        const photoOutput = document.getElementById('edit-itemPhotoOutput');
        if (data.photo) {
            photoOutput.src = data.photo;
            photoOutput.style.display = 'block';
        } else {
            photoOutput.src = '';
            photoOutput.style.display = 'none';
        }
        
        document.getElementById('editItemModal').style.display = 'flex';
    });
}

let scrollPosition = 0;

const viewDataCache = new WeakMap();
function handleViewItem(viewBtn) {
    let data = viewDataCache.get(viewBtn);
    if (!data) {
        data = {
            name: viewBtn.dataset.name,
            category: viewBtn.dataset.category || 'None',
            description: viewBtn.dataset.description || 'None',
            brand: viewBtn.dataset.brand || 'None',
            model: viewBtn.dataset.model || 'None',
            serial: viewBtn.dataset.serial || 'None',
            qty: viewBtn.dataset.qty,
            unit: viewBtn.dataset.unit || 'None',
            unitcost: viewBtn.dataset.unitcost,
            totalcost: viewBtn.dataset.totalcost,
            dateAcquired: viewBtn.dataset.dateAcquired,
            created: viewBtn.dataset.created || 'N/A',
            itemstatus: viewBtn.dataset.itemstatus,
            photo: viewBtn.dataset.photo
        };
        viewDataCache.set(viewBtn, data);
    }
    
    requestAnimationFrame(() => {
        document.getElementById("view-item-name").textContent = data.name;
        document.getElementById("view-item-category").textContent = data.category;
        document.getElementById("view-item-description").textContent = data.description;
        document.getElementById("view-item-brand").textContent = data.brand;
        document.getElementById("view-item-model").textContent = data.model;
        document.getElementById("view-item-serial").textContent = data.serial;
        document.getElementById("view-item-quantity").textContent = data.qty;
        document.getElementById("view-item-unit").textContent = data.unit;
        document.getElementById("view-item-unit-cost").textContent = data.unitcost;
        document.getElementById("view-item-total-cost").textContent = data.totalcost;

        document.getElementById("view-item-date-acquired").textContent =
            data.dateAcquired
                ? new Date(data.dateAcquired).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: '2-digit'
                }).replace(',', '').replace(' ', '-')
                : 'N/A';
        document.getElementById("view-item-created-at").textContent = data.created;
        document.getElementById("view-item-status").textContent = data.itemstatus;

        const photo = data.photo;
        document.getElementById("view-item-photo").src =
            photo && photo !== '' ? '/' + photo : '/images/user-profile/default-image.jpg';

        scrollPosition = window.scrollY;
        document.body.style.position = "fixed";
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.overflow = "hidden";
        document.body.style.width = "100%";

        document.getElementById("itemViewModal").style.display = "flex";
    });
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

const columnFilterKey = "columnFilterSettings";

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

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (domElements.editItemModal?.style.display === 'flex') {
            escEditItemModal();
        }
        if (domElements.itemViewModal?.style.display === 'flex') {
            closeItemView();
        } if (document.getElementById('addQuantityModal')?.style.display === 'flex') {
            escQuantityModal();
        }
    }
});


window.addEventListener('beforeunload', function() {
    filterCache.clear();
    dateCache.clear();
    dataIndex.searchIndex.clear();
});

if (typeof PerformanceObserver !== 'undefined') {
    const observer = new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => {
            if (entry.duration > 100) {
                console.warn(`Slow operation: ${entry.name} took ${entry.duration}ms`);
            }
        });
    });
    observer.observe({entryTypes: ['measure']});

    
    document.addEventListener('DOMContentLoaded', () => {

        document.querySelectorAll('.action-btn.add').forEach(btn => {
          btn.addEventListener('click', () => {
            const itemId = btn.getAttribute('data-id');
            const itemName = btn.getAttribute('data-name');
            openAddQuantityModal(itemId, itemName);
          });
        });
      });
      
      function openAddQuantityModal(itemId, itemName) {
        const modal = document.getElementById('addQuantityModal');
        const itemNameEl = document.getElementById('addQuantityItemName');
        const itemIdInput = document.getElementById('addQuantityItemId');
        
        if (itemNameEl && itemIdInput) {
            itemNameEl.textContent = itemName;
            itemIdInput.value = itemId;
            modal.style.display = 'flex';
            
            // Reset form and focus on quantity input
            document.getElementById('quantity').value = '';
            document.getElementById('quantity').focus();
        }
    }
      
      function escQuantityModal() {
        document.getElementById('addQuantityModal').style.display = 'none';
      }
      
     }

     function printCurrentTableView() {
        // Create a print-friendly version of the table
        const printWindow = window.open('', '_blank');
        const currentDate = new Date().toLocaleDateString();
        const categoryName = document.querySelector('title')?.textContent.replace('BSCI-', '') || 'Inventory';
        
        // Get only visible rows (current page)
        const visibleRows = Array.from(document.querySelectorAll('#inventoryTableBody tr'))
            .filter(row => row.style.display !== 'none');
        
        // Get table headers (excluding image and action columns)
        const headers = Array.from(document.querySelectorAll('.itemTable thead th'))
            .map(th => th.textContent.trim())
            .filter((header, index) => {
                // Skip image column (index 2) and actions column (index 13)
                if (index === 2 || index === 13) return false;
                
                // Check if column is visible (based on column filter)
                const colCheckbox = document.querySelector(`#columnFilterContainer input[data-column="${index}"]`);
                return !colCheckbox || colCheckbox.checked;
            });
    
        // Calculate total cost of all visible items
        let totalCost = 0;
        visibleRows.forEach(row => {
            const actionBtn = row.querySelector('.action-btn.view');
            if (actionBtn && actionBtn.dataset.totalcost) {
                const itemTotalCost = parseFloat(actionBtn.dataset.totalcost) || 0;
                totalCost += itemTotalCost;
            } else if (actionBtn && actionBtn.dataset.qty && actionBtn.dataset.unitcost) {
                // Fallback: Calculate from quantity and unit cost
                const quantity = parseFloat(actionBtn.dataset.qty) || 0;
                const unitCost = parseFloat(actionBtn.dataset.unitcost) || 0;
                totalCost += quantity * unitCost;
            }
        });
    
        // Format total cost with currency
        const formattedTotalCost = new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(totalCost);
    
        // Build table HTML with only visible columns and rows
        let tableHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${categoryName} - Inventory Report</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 20px;
                        color: #000;
                    }
                    .header-container {
                        display: flex;
                        align-items: center;
                        margin-bottom: 20px;
                        border-bottom: 2px solid #000;
                        padding-bottom: 15px;
                    }
                    .logo-container {
                        flex: 0 0 auto;
                        margin-left: 150px;
                    }
                    .logo {
                        width: 120px;
                        height: 120px;
                        object-fit: contain;
                    }
                    .header-content {
                        flex: 1;
                        text-align: center;
                    }
                    .header-content h1 {
                        margin: 0 0 8px 0;
                        font-size: 24pt;
                        color: #2c3e50;
                    }
                    .header-content .subtitle {
                        font-size: 14pt;
                        color: #666;
                        margin-bottom: 5px;
                    }
                    .header-content .department {
                        font-size: 12pt;
                        color: #888;
                        font-weight: bold;
                    }
                    .print-info {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 20px;
                        padding: 12px;
                        background-color: #f8f9fa;
                        border: 1px solid #dee2e6;
                        border-radius: 4px;
                        font-size: 10pt;
                    }
                    .info-item {
                        text-align: center;
                        flex: 1;
                    }
                    .info-item strong {
                        display: block;
                        color: #2c3e50;
                        margin-bottom: 3px;
                    }
                    .total-cost {
                        color: #1d6f42;
                        font-weight: bold;
                        font-size: 11pt;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 9pt;
                        page-break-inside: auto;
                        margin-bottom: 20px;
                    }
                    th, td {
                        border: 1px solid #333;
                        padding: 8px;
                        text-align: center;
                        page-break-inside: avoid;
                    }
                    th {
                        background-color: #2c3e50;
                        color: white;
                        font-weight: bold;
                        border-bottom: 2px solid #000;
                    }
                    tr:nth-child(even) {
                        background-color: #f8f9fa;
                    }
                    .currency {
                        text-align: right;
                        font-family: "Courier New", monospace;
                    }
                    .number {
                        text-align: right;
                        font-family: "Courier New", monospace;
                    }
                    .cost-cell {
                        text-align: right;
                        font-family: "Courier New", monospace;
                        font-weight: bold;
                    }
                    .print-footer {
                        margin-top: 30px;
                        padding-top: 15px;
                        border-top: 1px solid #ddd;
                        font-size: 9pt;
                        color: #666;
                        text-align: center;
                    }
                    .no-print {
                        display: none;
                    }
                    @page {
                        size: landscape;
                        margin: 1cm;
                    }
                    @media print {
                        body { 
                            margin: 0; 
                            padding: 15px;
                        }
                        .header-container { 
                            margin-bottom: 15px; 
                        }
                        .print-info {
                            background-color: #f8f9fa !important;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="header-container">
                    <div class="logo-container">
                        <img src="/images/assets/baliwasan.png" alt="BSCI Logo" class="logo" onerror="this.style.display='none'">
                    </div>
                    <div class="header-content">
                        <h1>INVENTORY REPORT</h1>
                        <div class="subtitle">Category: ${categoryName}</div>
                        <div class="department">Baliwasan Central School Inventory System</div>
                    </div>
                </div>
                
                <div class="print-info">
                    <div class="info-item">
                        <strong>Generated Date</strong>
                        <span>${currentDate}</span>
                    </div>
                    <div class="info-item">
                        <strong>Total Items</strong>
                        <span>${visibleRows.length}</span>
                    </div>
                    <div class="info-item">
                        <strong>Total Inventory Value</strong>
                        <span class="total-cost">${formattedTotalCost}</span>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
        `;
    
        // Add headers (already filtered to exclude image and action columns)
        headers.forEach(header => {
            tableHTML += `<th>${header}</th>`;
        });
    
        tableHTML += `
                        </tr>
                    </thead>
                    <tbody>
        `;
    
        // Add visible rows with only visible columns
        visibleRows.forEach(row => {
            const cells = Array.from(row.cells);
            tableHTML += '<tr>';
            
            cells.forEach((cell, index) => {
                // Skip image column (index 2) and actions column (index 13)
                if (index === 2 || index === 13) return;
                
                // Skip if column is hidden by filter
                const colCheckbox = document.querySelector(`#columnFilterContainer input[data-column="${index}"]`);
                if (colCheckbox && !colCheckbox.checked) {
                    return;
                }
                
                let cellContent = cell.textContent.trim();
                
                // Clean up cell content
                cellContent = cellContent.replace(/—/g, 'N/A').trim();
                
                // Apply formatting based on column type
                let formattedContent = cellContent;
                let cellClass = '';
                
                // Format currency columns (Unit Cost and Total Cost)
                if ((index === 8 || index === 10) && cellContent && cellContent !== 'N/A') {
                    const number = parseFloat(cellContent.replace(/[^\d.-]/g, ''));
                    if (!isNaN(number)) {
                        formattedContent = '₱' + number.toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        cellClass = 'currency';
                    }
                }
                // Format quantity column
                else if (index === 9) {
                    cellClass = 'number';
                }
                
                // Check if this is a cost-related column and format accordingly
                if (cellContent.includes('₱') || cell.textContent.includes('PHP')) {
                    cellClass = 'cost-cell';
                }
                
                tableHTML += `<td class="${cellClass}">${formattedContent || ''}</td>`;
            });
            
            tableHTML += '</tr>';
        });
    
        tableHTML += `
                    </tbody>
                </table>
                
                <div class="print-footer">
                    <p>Report generated on ${currentDate} | Category: ${categoryName} | Total Items: ${visibleRows.length} | Total Inventory Value: ${formattedTotalCost}</p>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() {
                            window.close();
                        }, 500);
                    };
                </script>
            </body>
            </html>
        `;
    
        printWindow.document.write(tableHTML);
        printWindow.document.close();
    }