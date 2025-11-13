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
let isRedirectingToItem = false;
let targetItemId = null;

// Performance monitoring
let perf = {
    filterTime: 0,
    renderTime: 0
};

let domElements = {};
let columnMap = {};
let currentSort = { field: null, order: null }; // Track current sort state

// Data indexing for faster filtering
let dataIndex = {
    brands: new Set(),
    statuses: new Set(),
    searchIndex: new Map()
};

// =============================================
// INITIALIZATION
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    initializeDOMCache();
    buildColumnMap();
    initializeTable();
    initFilterControls();
    initTableActions();
    calculateEditTotalCost();
    initDateValidation();
    restoreColumnSettings();
    initRowsPerPageSelector();
    
    // Check for delete alerts
    
});

function initRowsPerPageSelector() {
    const rowsPerPageSelect = document.getElementById("rowsPerPageSelect");
    
    if (rowsPerPageSelect) {
        const savedRowsPerPage = localStorage.getItem('itemsTableRowsPerPage');
        if (savedRowsPerPage) {
            rowsPerPageSelect.value = savedRowsPerPage;
            rowsPerPage = parseInt(savedRowsPerPage);
        }
        
        rowsPerPageSelect.addEventListener('change', function() {
            rowsPerPage = parseInt(this.value);
            thisCurrentPage = 1;
            displayPage(thisCurrentPage);
            updateItemCounts();
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
        activeFilters.push(`Date: ${currentFilters.dateFrom || 'any'} to ${currentFilters.dateTo || 'any'}`);
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
        description: 5, brand: 6, model: 7, unitCost: 8, totalQuantity: 9,
        availableQuantity: 10, totalCost: 11, dateAcquired: 12, status: 13,
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
// TABLE INITIALIZATION WITH SEARCH INDEX
// =============================================
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
    console.log('=== BUILDING SEARCH INDEX ===');
    
    allRows.forEach((row, index) => {
        const rowData = {
            brand: getCellData(row, 'brand').toLowerCase().trim(),
            serialNumber: getCellData(row, 'serialNumber').toLowerCase(),
            itemName: getCellData(row, 'itemName').toLowerCase(),
            description: getCellData(row, 'description').toLowerCase(),
            model: getCellData(row, 'model').toLowerCase(),
            availableQuantity: parseInt(getCellData(row, 'availableQuantity') || 0),
            totalQuantity: parseInt(getCellData(row, 'totalQuantity') || 0),
            dateAcquired: getCellData(row, 'dateAcquired').trim(),
            status: getCellData(row, 'status').trim(),
            itemId: getCellData(row, 'itemId').toLowerCase(),
            dateValue: parseTableDate(getCellData(row, 'dateAcquired').trim())
        };
        
        console.log(`Row ${index} - Extracted status: "${rowData.status}", Available Qty: ${rowData.availableQuantity}`);
        
        row._data = rowData;
        
        // Update indexes
        if (rowData.brand) dataIndex.brands.add(rowData.brand);
        if (rowData.status) {
            dataIndex.statuses.add(rowData.status);
        }
        
        // Build search index
        const searchableText = `${rowData.itemId} ${rowData.itemName} ${rowData.model} ${rowData.brand} ${rowData.serialNumber} ${rowData.description}`;
        const terms = searchableText.split(/\s+/).filter(term => term.length > 2);
        
        terms.forEach(term => {
            if (!dataIndex.searchIndex.has(term)) {
                dataIndex.searchIndex.set(term, new Set());
            }
            dataIndex.searchIndex.get(term).add(index);
        });
    });
    
    console.log('=== FINAL STATUS INDEX ===', Array.from(dataIndex.statuses));
}

// =============================================
// FILTERING & SEARCH - OPTIMIZED PATTERN
// =============================================
function filterItems() {
    const filterKey = generateFilterKey();
    
    if (filterCache.has(filterKey)) {
        filteredRows = filterCache.get(filterKey);
        updateDisplay();
        return;
    }
    
    const startTime = performance.now();
    
    requestAnimationFrame(() => {
        applyFiltersAndSort();
        
        // Cache management
        if (filterCache.size > maxCacheSize) filterCache.clear();
        filterCache.set(filterKey, filteredRows);
        
        perf.filterTime = performance.now() - startTime;
        updateDisplay();
    });
}

function applyFiltersAndSort() {
    const searchValue = (domElements.searchInput?.value || '').toLowerCase();
    const selectedBrands = domElements.brandSelect ? 
        Array.from(domElements.brandSelect.selectedOptions).map(opt => opt.value.toLowerCase().trim()) : [];
    
    console.log('=== APPLYING FILTERS AND SORT ===');
    console.log('Current filters:', currentFilters);
    console.log('Current sort:', currentSort);
    console.log('Selected brands:', selectedBrands);
    
    let results;
    
    // Use indexed search for better performance with search terms
    if (searchValue && searchValue.length > 2) {
        results = performIndexedSearch(selectedBrands, searchValue);
    } else {
        results = performLinearSearch(selectedBrands, searchValue);
    }
    
    filteredRows = results;
    
    console.log('After filtering:', filteredRows.length, 'rows');
    
    // Then apply current sort if exists
    if (currentSort.field) {
        applyCurrentSort();
    }
}

function performIndexedSearch(selectedBrands, searchValue) {
    const searchTerms = searchValue.split(/\s+/).filter(term => term.length > 2);
    let matchingIndexes = new Set();
    
    if (searchTerms.length > 0) {
        searchTerms.forEach((term, index) => {
            const termMatches = dataIndex.searchIndex.get(term) || new Set();
            if (index === 0) {
                termMatches.forEach(i => matchingIndexes.add(i));
            } else {
                const currentMatches = new Set(matchingIndexes);
                matchingIndexes.clear();
                termMatches.forEach(i => {
                    if (currentMatches.has(i)) matchingIndexes.add(i);
                });
            }
        });
    } else {
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
        
        // Apply search filter if present
        if (searchValue) {
            return (
                data.itemId.includes(searchValue) ||
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
    if (currentFilters.quantity === "out" && data.availableQuantity !== 0) return false;
    if (currentFilters.quantity === "available" && data.availableQuantity <= 0) return false;
    
    // Date filter
    if (!passesDateFilter(data.dateAcquired)) return false;
    
    // Status filter
    if (currentFilters.status.length > 0 && !currentFilters.status.includes(data.status)) {
        return false;
    }
    
    return true;
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

function generateFilterKey() {
    const selectedBrands = domElements.brandSelect ? 
        Array.from(domElements.brandSelect.selectedOptions).map(opt => opt.value).sort().join(',') : '';
    const searchValue = domElements.searchInput?.value || '';
    
    return `${selectedBrands}|${currentFilters.quantity}|${currentFilters.dateFrom}|${currentFilters.dateTo}|${currentFilters.status.sort().join(',')}|${searchValue}|${currentSort.field}|${currentSort.order}`;
}

// =============================================
// SORTING FUNCTIONS - OPTIMIZED
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
// TABLE DISPLAY & PAGINATION
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
        updatePageInfo();
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
        noResultsRow.innerHTML = `<td colspan="17" style="text-align:center; color:#666; padding:20px;">No items found matching your filters.</td>`;
        domElements.tableBody.appendChild(noResultsRow);
    } else if (!show && noResultsRow) {
        noResultsRow.remove();
    }
}

function updateDisplay() {
    updateRowNumbers();
    showNoResultsMessage(filteredRows.length === 0);
    
    if (!isRedirectingToItem) {
        thisCurrentPage = 1;
    }
    
    displayPage(thisCurrentPage);
    updateItemCounts();
}

// =============================================
// FILTER CONTROLS WITH CLOSE ON APPLY
// =============================================
// =============================================
// FILTER CONTROLS WITH CLOSE ON APPLY
// =============================================
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
    setupBrandFilters();
    setupQuantityFilters();
    setupDateFilters();
    setupStatusFilters();

    // Reset all filters
    document.getElementById("resetAllFiltersBtn")?.addEventListener("click", resetAllFilters);

    // Search functionality
    domElements.searchInput?.addEventListener("input", () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => filterItems(), 150);
    });

    function setupBrandFilters() {
        document.getElementById("filterByBrandBtn")?.addEventListener("click", () => {
            filterItems();
            closeAllFilters(); // Close filter after applying
        });

        document.getElementById("resetBrandFilterBtn")?.addEventListener("click", () => {
            if (domElements.brandSelect) {
                Array.from(domElements.brandSelect.options).forEach(option => option.selected = false);
                filterItems();
            }
            closeAllFilters(); // Close filter after resetting
        });
    }

    function setupQuantityFilters() {
        document.getElementById("sortLowToHigh")?.addEventListener("click", () => {
            sortByQuantity("asc");
            closeAllFilters(); // Close filter after applying
        });

        document.getElementById("sortHighToLow")?.addEventListener("click", () => {
            sortByQuantity("desc");
            closeAllFilters(); // Close filter after applying
        });
    }

    function setupDateFilters() {
        document.getElementById("filterByDateBtn")?.addEventListener("click", () => {
            currentFilters.dateFrom = domElements.dateFrom.value;
            currentFilters.dateTo = domElements.dateTo.value;
            filterItems();
            closeAllFilters(); // Close filter after applying
        });

        document.getElementById("resetDateFilterBtn")?.addEventListener("click", () => {
            domElements.dateFrom.value = "";
            domElements.dateTo.value = "";
            currentFilters.dateFrom = "";
            currentFilters.dateTo = "";
            filterItems();
            closeAllFilters(); // Close filter after resetting
        });
    }

    function setupStatusFilters() {
        document.getElementById("filterByStatusBtn")?.addEventListener("click", () => {
            const statusCheckboxes = document.querySelectorAll('input[name="statusFilter"]:checked');
            currentFilters.status = Array.from(statusCheckboxes).map(cb => cb.value);
            filterItems();
            closeAllFilters(); // Close filter after applying
        });

        document.getElementById("resetStatusFilterBtn")?.addEventListener("click", () => {
            document.querySelectorAll('input[name="statusFilter"]').forEach(cb => cb.checked = false);
            currentFilters.status = [];
            filterItems();
            closeAllFilters(); // Close filter after resetting
        });
    }
}

function resetAllFilters() {
    // Reset brand filter
    if (domElements.brandSelect) {
        Array.from(domElements.brandSelect.options).forEach(option => option.selected = false);
    }
    
    // Reset other filters
    domElements.dateFrom.value = "";
    domElements.dateTo.value = "";
    currentFilters.dateFrom = "";
    currentFilters.dateTo = "";
    document.querySelectorAll('input[name="statusFilter"]').forEach(cb => cb.checked = false);
    currentFilters.status = [];
    domElements.searchInput.value = "";
    
    // Clear sort when resetting all filters
    currentSort.field = null;
    currentSort.order = null;
    
    filterCache.clear();
    dateCache.clear();
    filterItems();
}

// =============================================
// COLUMN FILTERING
// =============================================
const columnFilterKey = "columnFilterSettings";

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
// TABLE ACTIONS
// =============================================
function initTableActions() {
    document.querySelector('.itemTable')?.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.action-btn.delete');
        if (deleteBtn) return handleDeleteItem(deleteBtn);

        const editBtn = e.target.closest('.action-btn.edit');
        if (editBtn) return handleEditItem(editBtn);

        const viewBtn = e.target.closest('.action-btn.view');
        if (viewBtn) return handleViewItem(viewBtn);

        const addBtn = e.target.closest('.action-btn.add');
        if (addBtn) return handleAddQuantity(addBtn);
    });
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
// =============================================
// DELETE ALERT FUNCTIONALITY
// =============================================
function checkForDeletedItem() {
    const urlParams = new URLSearchParams(window.location.search);
    const itemDeleted = urlParams.get('item_deleted');
    
    if (itemDeleted === '1') {
  
        const alertContainer = document.getElementById('deleteAlertData');
        if (alertContainer) {
            const deletedName = alertContainer.getAttribute('data-deleted-name');
            const isLastItem = alertContainer.getAttribute('data-is-last-item') === 'true';
            
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
                        url.searchParams.delete('item_deleted');
               
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
})
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
            remarks: editBtn.getAttribute('data-remarks') || '',
            qty: editBtn.getAttribute('data-qty') || '',
            availableQty: editBtn.getAttribute('data-available-qty') || '',
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
        document.getElementById('edit-remarks').value = data.remarks;
        document.getElementById('edit-item-qty').value = data.qty;
        document.getElementById('edit-available-item-qty').value = data.availableQty;
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
            remarks: viewBtn.dataset.remarks|| 'None',
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
        document.getElementById("view-available-quantity").textContent = viewBtn.dataset.availableQty;
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
        document.getElementById("view-item-remarks").textContent = data.remarks;

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

// =============================================
// UTILITY FUNCTIONS
// =============================================
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

// =============================================
// DATE PARSING
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
// ADD QUANTITY MODAL FUNCTIONS
// =============================================
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

// =============================================
// PRINT FUNCTIONALITY
// =============================================
function printCurrentTableView() {
    const printWindow = window.open('', '_blank');
    const currentDate = new Date().toLocaleDateString();
    const categoryName = document.querySelector('title')?.textContent.replace('BSCI-', '') || 'Inventory';
    
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

    const tableHTML = generatePrintHTML(headers, visibleRows, currentDate, formattedTotalCost, categoryName);
    
    printWindow.document.write(tableHTML);
    printWindow.document.close();
}

function generatePrintHTML(headers, visibleRows, currentDate, formattedTotalCost, categoryName) {
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${categoryName} - Inventory Report</title>
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
                    <h1>INVENTORY REPORT</h1>
                    <div class="subtitle">Category: ${categoryName}</div>
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
                            
                            if (index === columnMap.totalQuantity || index === columnMap.availableQuantity) {
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
                <p>Report generated on ${currentDate} | Category: ${categoryName} | Total Items: ${visibleRows.length} | Total Inventory Value: ${formattedTotalCost}</p>
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

// Performance monitoring
if (typeof PerformanceObserver !== 'undefined') {
    const observer = new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => {
            if (entry.duration > 100) {
                console.warn(`Slow operation: ${entry.name} took ${entry.duration}ms`);
            }
        });
    });
    observer.observe({entryTypes: ['measure']});
}