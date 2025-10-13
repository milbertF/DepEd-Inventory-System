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
            brand: (cells[5]?.textContent || '').toLowerCase().trim(),
            quantity: parseInt(cells[8]?.textContent || 0),
            dateAcquired: cells[10]?.textContent.trim(),
            status: cells[11]?.textContent.trim(),
            itemName: (cells[3]?.textContent || '').toLowerCase(),
            model: (cells[6]?.textContent || '').toLowerCase(),
            serialNumber: (cells[1]?.textContent || '').toLowerCase(),
            description: (cells[4]?.textContent || '').toLowerCase(),
            dateValue: parseTableDate(cells[10]?.textContent.trim())
        };
        
        // Store data on row for backward compatibility
        row._data = rowData;
        
        // Update indexes
        if (rowData.brand) dataIndex.brands.add(rowData.brand);
        if (rowData.status) dataIndex.statuses.add(rowData.status);
        
        // Build search index for terms longer than 2 characters
        const searchableText = `${rowData.itemName} ${rowData.model} ${rowData.brand} ${rowData.serialNumber} ${rowData.description}`;
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
        let results;
        
        // Use indexed search for better performance with large datasets
        if (searchValue && searchValue.length > 2) {
            results = performIndexedSearch(selectedBrands, searchValue);
        } else {
            results = performLinearSearch(selectedBrands, searchValue);
        }
        
        filteredRows = results;
        
        // Update cache with size limit
        if (filterCache.size >= maxCacheSize) {
            const firstKey = filterCache.keys().next().value;
            filterCache.delete(firstKey);
        }
        filterCache.set(filterKey, filteredRows);
        
        perf.filterTime = performance.now() - startTime;
        
        // Log performance for large datasets
        if (allRows.length > 1000) {
            console.log(`Filtered ${allRows.length} items in ${perf.filterTime.toFixed(2)}ms`);
        }
        
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
        
        // Apply search filter if pwede
        if (searchValue) {
            return (
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
        
        if (currentFilters.dateFrom && data.dateValue < new Date(currentFilters.dateFrom)) return false;
        if (currentFilters.dateTo) {
            const toDate = new Date(currentFilters.dateTo);
            toDate.setHours(23, 59, 59, 999);
            if (data.dateValue > toDate) return false;
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

// KEEP ALL OTHER FUNCTIONS EXACTLY THE SAME (they're already optimized)
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
        }
    });
}

function handleDeleteItem(deleteBtn) {
    const itemId = deleteBtn.getAttribute('data-id');
    const itemName = deleteBtn.getAttribute('data-name');
    const categoryId = new URLSearchParams(window.location.search).get('category_id');

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
            
            let url = `/templates/inventory/function/deleteItem.php?id=${itemId}&source=category`;
            if (categoryId) url += `&category_id=${categoryId}`;
            window.location.href = url;
        }
    });
}


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
                        // Clean up URL completely like category deletion
                        const url = new URL(window.location.href);
                        url.searchParams.delete('item_deleted');
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
            url.searchParams.delete('item_deleted');
            window.history.replaceState({}, document.title, url.pathname);
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
        }
    }
});

// Memory cleanup for large datasets
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
        // Open modal when clicking the add button
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
        itemNameEl.textContent = itemName;
        modal.style.display = 'flex';
        modal.setAttribute('data-item-id', itemId);
      }
      
      function escQuantityModal() {
        document.getElementById('addQuantityModal').style.display = 'none';
      }
      
     }