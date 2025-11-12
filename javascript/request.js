document.addEventListener('DOMContentLoaded', function() {
    // Initialize everything
    initializeRequestTable();
    initRequestPaginationEventListeners();
    initViewRequestModal();
    
    // Add any existing search term from URL
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('search');
    if (searchTerm && document.getElementById('searchRequest')) {
        document.getElementById('searchRequest').value = searchTerm;
        // Trigger filter to apply the search term
        setTimeout(() => filterRequests(), 100);
    }
});

// =============================================
// REQUEST TABLE PAGINATION, SEARCH AND ENTRIES
// =============================================

let requestCurrentFilters = {
    status: ['pending'], // Default to showing pending
    search: ''
};

let allRequestRows = [];
let filteredRequestRows = [];
let requestCurrentPage = 1;
let requestRowsPerPage = parseInt(localStorage.getItem('requestTableRowsPerPage')) || 10;
let requestDebounceTimer = null;

// Initialize request table
function initializeRequestTable() {
    console.log('Initializing request table...');
    
    // Get all request rows
    allRequestRows = Array.from(document.querySelectorAll('.requestTable tbody tr'));
    console.log(`Found ${allRequestRows.length} request rows`);
    
    // Remove any existing no results message
    const existingNoResults = document.getElementById('noRequestsMessage');
    if (existingNoResults) {
        existingNoResults.remove();
    }
    
    // Initialize rows per page selector
    initRequestRowsPerPageSelector();
    
    // Load saved status filters from localStorage
    loadStatusFilters();
    
    // Apply initial filters
    filterRequests();
}

// Load status filters from localStorage
function loadStatusFilters() {
    const savedFilters = localStorage.getItem('requestStatusFilters');
    if (savedFilters) {
        const savedStatuses = JSON.parse(savedFilters);
        requestCurrentFilters.status = savedStatuses;
        
        // Update checkboxes to match saved state
        const statusCheckboxes = document.querySelectorAll('.status-checkbox');
        statusCheckboxes.forEach(checkbox => {
            const status = checkbox.getAttribute('data-status');
            checkbox.checked = savedStatuses.includes(status);
        });
        
        console.log('Loaded saved status filters:', savedStatuses);
    } else {
        // Default to pending only
        const pendingCheckbox = document.querySelector('.status-checkbox[data-status="pending"]');
        if (pendingCheckbox) {
            pendingCheckbox.checked = true;
        }
        requestCurrentFilters.status = ['pending'];
        console.log('Using default status filter: pending only');
    }
}

// Save status filters to localStorage
function saveStatusFilters() {
    localStorage.setItem('requestStatusFilters', JSON.stringify(requestCurrentFilters.status));
    console.log('Saved status filters:', requestCurrentFilters.status);
}

// Initialize rows per page selector
function initRequestRowsPerPageSelector() {
    const rowsPerPageSelect = document.getElementById("requestRowsPerPage");
    
    if (rowsPerPageSelect) {
        const savedRowsPerPage = localStorage.getItem('requestTableRowsPerPage');
        if (savedRowsPerPage) {
            rowsPerPageSelect.value = savedRowsPerPage;
            requestRowsPerPage = parseInt(savedRowsPerPage);
        }
        
        rowsPerPageSelect.addEventListener('change', function() {
            requestRowsPerPage = parseInt(this.value);
            requestCurrentPage = 1;
            displayRequestPage(requestCurrentPage);
            updateRequestCounts();
            localStorage.setItem('requestTableRowsPerPage', this.value);
        });
    }
}

// Filter requests based on current filters
function filterRequests() {
    const searchValue = (document.getElementById('searchRequest')?.value || '').toLowerCase().trim();
    const statusCheckboxes = document.querySelectorAll('.status-checkbox:checked');
    const selectedStatuses = Array.from(statusCheckboxes).map(cb => cb.getAttribute('data-status'));
    
    requestCurrentFilters.status = selectedStatuses;
    requestCurrentFilters.search = searchValue;
    
    // Save the current status filters
    saveStatusFilters();
    
    console.log('Filtering requests:', {
        status: selectedStatuses,
        search: searchValue
    });
    
    // Reset filtered rows
    filteredRequestRows = [];
    
    // Filter rows
    allRequestRows.forEach(row => {
        const requestData = JSON.parse(row.getAttribute('data-request'));
        const rowData = {
            requestId: String(requestData.request_id || '').toLowerCase(),
            requesterName: String(requestData.requester_name || '').toLowerCase(),
            positionTitle: String(requestData.position_title || '').toLowerCase(),
            officeName: String(requestData.office_name || '').toLowerCase(),
            items: requestData.items || [],
            status: String(requestData.status || '').toLowerCase(),
            // Create searchable text from all items
            itemsSearchText: (requestData.items || []).map(item => 
                `${item.item_name || ''} ${item.item_id || ''} ${item.serial_number || ''} ${item.category_name || ''} ${item.brand || ''} ${item.model || ''} ${item.description || ''}`
            ).join(' ').toLowerCase()
        };
        
        // Check status filter
        let statusMatch = true;
        if (selectedStatuses.length > 0) {
            statusMatch = rowData.items.some(item => {
                const itemStatus = (item.status || 'pending').toLowerCase();
                return selectedStatuses.includes(itemStatus);
            });
        }
        
        // Check search filter
        let searchMatch = true;
        if (searchValue) {
            const searchableText = `
                ${rowData.requestId}
                ${rowData.requesterName}
                ${rowData.positionTitle}
                ${rowData.officeName}
                ${rowData.itemsSearchText}
                ${rowData.status}
            `.toLowerCase();
            
            searchMatch = searchableText.includes(searchValue);
        }
        
        if (statusMatch && searchMatch) {
            filteredRequestRows.push(row);
        }
    });
    
    console.log(`Filtered to ${filteredRequestRows.length} requests`);
    
    requestCurrentPage = 1;
    updateRequestDisplay();
}

// Display specific page
function displayRequestPage(page = 1) {
    const start = (page - 1) * requestRowsPerPage;
    const end = start + requestRowsPerPage;

    // Hide all rows first
    allRequestRows.forEach(row => {
        row.style.display = "none";
    });
    
    // Show only rows for current page
    for (let i = start; i < Math.min(end, filteredRequestRows.length); i++) {
        if (filteredRequestRows[i]) {
            filteredRequestRows[i].style.display = "";
        }
    }
    
    updateRequestPagination(page);
    updateRequestCounts();
    updateRequestRowNumbers();
}

// Update pagination controls
function updateRequestPagination(page) {
    const pagination = document.getElementById('requestPagination');
    const totalPages = Math.ceil(filteredRequestRows.length / requestRowsPerPage);
    
    if (totalPages <= 1) {
        pagination.style.display = "none";
        pagination.innerHTML = "";
        return;
    }
    
    pagination.style.display = "flex";
    pagination.innerHTML = generateRequestPaginationHTML(page, totalPages);
    attachRequestPaginationEvents(page, totalPages);
}

// Generate pagination HTML
function generateRequestPaginationHTML(page, totalPages) {
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

// Attach pagination event listeners
function attachRequestPaginationEvents(page, totalPages) {
    const pagination = document.getElementById('requestPagination');
    pagination.querySelectorAll('a[data-page]').forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            const newPage = parseInt(this.getAttribute('data-page'));
            if (newPage >= 1 && newPage <= totalPages) {
                requestCurrentPage = newPage;
                displayRequestPage(requestCurrentPage);
            }
        });
    });
}

// Update request counts display - FIXED LOGIC
function updateRequestCounts() {
    const totalRequests = allRequestRows.length;
    const filteredRequests = filteredRequestRows.length;
    
    // Check if we should show simple count or "X of Y" format
    const shouldShowSimpleCount = shouldShowSimpleCountDisplay();
    
    console.log('Count update:', {
        totalRequests,
        filteredRequests,
        shouldShowSimpleCount,
        currentFilters: requestCurrentFilters
    });
    
    // Update the main display text
    updateMainCountDisplay(totalRequests, filteredRequests, shouldShowSimpleCount);
    
    // Remove the redundant page info entirely
    const pageInfoElement = document.getElementById('pageInfo');
    if (pageInfoElement) {
        pageInfoElement.textContent = '';
    }
}

// Check if we should show simple count - FIXED LOGIC
function shouldShowSimpleCountDisplay() {
    const searchValue = requestCurrentFilters.search;
    const statusFilters = requestCurrentFilters.status;
    
    // Show simple count ONLY when:
    // 1. NO search AND 
    // 2. NO status filters selected (showing all items)
    const hasNoSearch = !searchValue || searchValue === '';
    const hasNoStatusFilters = statusFilters.length === 0;
    
    return hasNoSearch && hasNoStatusFilters;
}

// Update the main count display text - FIXED LOGIC
function updateMainCountDisplay(totalRequests, filteredRequests, shouldShowSimpleCount) {
    const itemCountDisplay = document.querySelector('.item-count-display');
    if (itemCountDisplay) {
        if (shouldShowSimpleCount) {
            // When showing ALL items with NO search: Just "X total requests"
            itemCountDisplay.innerHTML = `
                <span id="totalRequestsCount">${filteredRequests}</span> total requests
            `;
        } else {
            // When searching OR any status filter selected: "X total requests | Showing Y of X"
            itemCountDisplay.innerHTML = `
                <span id="totalRequestsCount">${totalRequests}</span> total requests
                | Showing <span id="visibleRequestsCount">${filteredRequests}</span> of <span id="totalRequestsCount2">${totalRequests}</span>
            `;
        }
    }
}

// Update row numbers based on filtered results
function updateRequestRowNumbers() {
    const startIndex = (requestCurrentPage - 1) * requestRowsPerPage;
    
    filteredRequestRows.forEach((row, index) => {
        const absoluteIndex = startIndex + index;
        const numberCell = row.cells[0];
        if (numberCell && absoluteIndex < requestCurrentPage * requestRowsPerPage) {
            numberCell.textContent = absoluteIndex + 1;
        }
    });
}

// Show no results message
function showNoRequestsMessage(show) {
    let noResultsRow = document.getElementById('noRequestsMessage');
    const tableBody = document.querySelector('.requestTable tbody');
    
    if (show && !noResultsRow && tableBody) {
        noResultsRow = document.createElement("tr");
        noResultsRow.id = 'noRequestsMessage';
        noResultsRow.innerHTML = `<td colspan="9" style="text-align:center; color:#666; padding:20px;">No requests found matching your filters.</td>`;
        tableBody.appendChild(noResultsRow);
    } else if (!show && noResultsRow) {
        noResultsRow.remove();
    }
}

// Update entire request display
function updateRequestDisplay() {
    showNoRequestsMessage(filteredRequestRows.length === 0);
    displayRequestPage(requestCurrentPage);
    updateRequestCounts();
}

// Initialize event listeners for pagination and search
function initRequestPaginationEventListeners() {
    // Search input with debouncing
    const searchInput = document.getElementById('searchRequest');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(requestDebounceTimer);
            requestDebounceTimer = setTimeout(() => {
                console.log('Search triggered:', searchInput.value);
                filterRequests();
            }, 300);
        });
        
        // Also allow Enter key for immediate search
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(requestDebounceTimer);
                filterRequests();
            }
        });
    }
    
    // Status filter checkboxes
    const statusCheckboxes = document.querySelectorAll('.status-checkbox');
    statusCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            console.log('Status filter changed:', checkbox.getAttribute('data-status'), checkbox.checked);
            filterRequests();
        });
    });
}

// Reset all filters
function resetRequestFilters() {
    // Reset search
    const searchInput = document.getElementById('searchRequest');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Reset status checkboxes to default (only pending checked)
    const statusCheckboxes = document.querySelectorAll('.status-checkbox');
    statusCheckboxes.forEach(checkbox => {
        checkbox.checked = checkbox.getAttribute('data-status') === 'pending';
    });
    
    // Reset pagination
    requestCurrentPage = 1;
    requestRowsPerPage = 10;
    
    const rowsPerPageSelect = document.getElementById("requestRowsPerPage");
    if (rowsPerPageSelect) {
        rowsPerPageSelect.value = '10';
    }
    
    localStorage.setItem('requestTableRowsPerPage', '10');
    localStorage.removeItem('requestStatusFilters'); // Clear saved status filters
    
    // Re-apply filters
    filterRequests();
}

// =============================================
// REQUEST MODAL FUNCTIONS
// =============================================

let selectedActions = new Map();
let currentRequestData = null;

function openViewModal(requestId) {
    const button = document.querySelector(`button[data-request-id="${requestId}"]`);
    if (!button) {
        showError('Request not found');
        return;
    }
    
    const row = button.closest('tr');
    if (!row) {
        showError('Request data not found');
        return;
    }
    
    const requestData = JSON.parse(row.getAttribute('data-request'));
    
    populateRequestDetails(requestData);
    document.getElementById('viewRequestModal').style.display = 'flex';
    document.body.classList.add('modal-open');
}

function closeViewModal() {
    document.getElementById('viewRequestModal').style.display = 'none';
    document.body.classList.remove('modal-open');
    currentRequestData = null;
    selectedActions.clear();
}

function populateRequestDetails(request) {
    requestAnimationFrame(() => {
        // Reset selection when opening new modal
        selectedActions.clear();
        currentRequestData = request;
        
        // Basic request info
        document.getElementById('viewRequestId').textContent = request.request_id;
        
        const statusElement = document.getElementById('viewRequestStatus');
        statusElement.textContent = request.status;
        statusElement.className = 'status-badge';
        statusElement.classList.add(`status-${request.status.toLowerCase()}`);
        
        document.getElementById('viewRequesterName').textContent = request.requester_name;
        document.getElementById('viewRequesterPosition').textContent = request.position_title;
        document.getElementById('viewRequesterOffice').textContent = request.office_name;
        document.getElementById('viewDateRequested').textContent = formatDate(request.created_at);
        
        // Populate items with status and action buttons
        const itemsBody = document.getElementById('viewRequestItemsBody');
        itemsBody.innerHTML = '';
        
        if (request.items && request.items.length > 0) {
            request.items.forEach((item, index) => {
                // Use the actual req_item_id from database
                const itemId = item.req_item_id;
                
                if (!itemId) {
                    console.error('Missing req_item_id for item:', item);
                    return;
                }
                
                const row = document.createElement('tr');
                row.setAttribute('data-item-id', itemId);
                
                // Get appropriate action buttons based on item status
                const actionButtons = getItemActionButtons(item.status, itemId);
                
                row.innerHTML = `
                    <td>${escapeHtml(item.category_name || '-')}</td>
                    <td>${escapeHtml(item.item_id || '-')}</td>
                    <td>${escapeHtml(item.serial_number || '-')}</td>
                    <td>${escapeHtml(item.item_name)}</td>
                    <td>${escapeHtml(item.description || '-')}</td>
                    <td>${escapeHtml(item.brand || '-')}</td>
                    <td>${escapeHtml(item.model || '-')}</td>
                    <td>${item.requested_quantity}</td>
                    <td>${escapeHtml(item.purpose || 'N/A')}</td>
                    <td>${formatDate(item.date_needed)}</td>
                    <td>
                        <span class="status-badge status-${item.status ? item.status.toLowerCase() : 'pending'}">
                            ${item.status || 'Pending'}
                        </span>
                    </td>
                    <td>
                        <div class="item-action-buttons" data-item-id="${itemId}">
                            ${actionButtons}
                        </div>
                    </td>
                `;
                itemsBody.appendChild(row);
            });
            
            // Initialize action buttons
            initItemActionButtons();
        } else {
            itemsBody.innerHTML = '<tr><td colspan="12" style="text-align: center; padding: 20px;">No items found in this request</td></tr>';
        }
        
        // Add bulk confirm section
        addBulkConfirmSection();
    });
}

// Initialize action buttons with selection functionality
function initItemActionButtons() {
    document.querySelectorAll('.item-action-btn').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.closest('.item-action-buttons').getAttribute('data-item-id');
            const action = this.getAttribute('data-action');
            
            console.log('Button clicked:', { itemId, action });
            
            // Toggle selection
            if (selectedActions.get(itemId) === action) {
                // Deselect if already selected
                selectedActions.delete(itemId);
                this.classList.remove('selected');
            } else {
                // Select this action
                selectedActions.set(itemId, action);
                
                // Remove selected class from other buttons in same group
                const buttonGroup = this.closest('.item-action-buttons');
                buttonGroup.querySelectorAll('.item-action-btn').forEach(btn => {
                    btn.classList.remove('selected');
                });
                
                // Add selected class to clicked button
                this.classList.add('selected');
            }
            
            updateBulkConfirmButton();
        });
    });
}

// Add bulk confirm section to modal
function addBulkConfirmSection() {
    // Remove existing bulk section if any
    const existingBulkSection = document.querySelector('.bulk-confirm-section');
    if (existingBulkSection) {
        existingBulkSection.remove();
    }
    
    const bulkSection = document.createElement('div');
    bulkSection.className = 'bulk-confirm-section';
    bulkSection.innerHTML = `
        <div class="bulk-confirm-controls">
            <div class="bulk-selection-info">
                <span id="selectedActionsCount">0</span> actions selected
            </div>
            <button id="confirmBulkActions" class="confirm-bulk-btn" disabled>
                Confirm All Actions
            </button>
        </div>
    `;
    
    // Insert after the items table
    const itemsTable = document.querySelector('.items-table-container');
    itemsTable.parentNode.insertBefore(bulkSection, itemsTable.nextSibling);
    
    // Initialize bulk confirm handler
    initBulkConfirmHandler();
}

// Initialize bulk confirm handler
function initBulkConfirmHandler() {
    const confirmBulkBtn = document.getElementById('confirmBulkActions');
    
    if (confirmBulkBtn) {
        confirmBulkBtn.addEventListener('click', function() {
            confirmAllSelectedActions();
        });
    }
}

// Update bulk confirm button state
function updateBulkConfirmButton() {
    const confirmBulkBtn = document.getElementById('confirmBulkActions');
    const selectedCount = document.getElementById('selectedActionsCount');
    
    if (selectedCount) {
        selectedCount.textContent = selectedActions.size;
    }
    
    if (confirmBulkBtn) {
        confirmBulkBtn.disabled = selectedActions.size === 0;
    }
}

function confirmAllSelectedActions() {
    const actionsArray = Array.from(selectedActions.entries());
    
    console.log('Confirming actions:', actionsArray);
    console.log('Current request data:', currentRequestData);
    
    Swal.fire({
        title: `Confirm ${selectedActions.size} Actions`,
        html: `Are you sure you want to apply ${selectedActions.size} action(s)?<br><br>
               <div class="swal-items-container">
                   <div class="swal-items-grid">
                   ${actionsArray.map(([itemId, action]) => {
                       // Find the actual item data using req_item_id
                       const item = currentRequestData.items.find(item => item.req_item_id == itemId);
                       const itemName = item ? item.item_name : `Item ID: ${itemId}`;
                       return `
                           <div class="swal-item-name">${escapeHtml(itemName)}</div>
                           <div class="swal-action-badge swal-action-${action}">${getActionDisplayText(action)}</div>
                       `;
                   }).join('')}
                   </div>
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: `Confirm All`,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        width: '500px'
    }).then((result) => {
        if (result.isConfirmed) {
       
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we update the items',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
           
fetch('/templates/request/function/updateItemRequestStatus.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        request_id: currentRequestData.request_id,
        actions: actionsArray.map(([itemId, action]) => ({
            req_item_id: itemId,  
            action: action
        }))
    })
})
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || `${selectedActions.size} action(s) completed successfully`,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        closeViewModal();
                        location.reload();
                    });
                } else {
                    // Show detailed error message
                    let errorMessage = data.message || 'Failed to update items';
                    if (data.errors && data.errors.length > 0) {
                        errorMessage += '\n\nErrors:\n' + data.errors.join('\n');
                    }
                    throw new Error(errorMessage);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'An unexpected error occurred',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}

function getActionDisplayText(action) {
    const texts = {
        'approve': 'Approve',
        'decline': 'Decline',
        'release': 'Release',
        'return': 'Return',
        'void': 'Void'
    };
    return texts[action] || action;
}

function getItemActionButtons(currentStatus, itemId) {
    const status = currentStatus ? currentStatus.toLowerCase() : 'pending';
    
    const actionConfigs = {
        'pending': [
            { action: 'approve', icon: 'fa-check', class: 'approve', title: 'Approve' },
            { action: 'decline', icon: 'fa-times', class: 'decline', title: 'Decline' }
        ],
        'approved': [
            { action: 'release', icon: 'fa-box-open', class: 'release', title: 'Mark as Released' },
            { action: 'void', icon: 'fa-ban', class: 'void', title: 'Void' }
        ],
        'released': [
            { action: 'return', icon: 'fa-undo', class: 'return', title: 'Mark as Returned' },
            { action: 'void', icon: 'fa-ban', class: 'void', title: 'Void' }
        ],
        'returned': [
            { action: 'release', icon: 'fa-box-open', class: 'release', title: 'Release Again' },
            { action: 'void', icon: 'fa-ban', class: 'void', title: 'Void' }
        ],
        'declined': [
            { action: 'approve', icon: 'fa-check', class: 'approve', title: 'Approve' }
        ],
        'void': [
            { action: 'approve', icon: 'fa-check', class: 'approve', title: 'Approve' }
        ]
    };
    
    const actions = actionConfigs[status] || actionConfigs.pending;
    let buttonsHTML = '';
    
    actions.forEach(actionConfig => {
        buttonsHTML += `
            <button class="item-action-btn ${actionConfig.class}" 
                    data-item-id="${itemId}" 
                    data-action="${actionConfig.action}">
                <i class="fas ${actionConfig.icon}"></i>
                <span class="tooltip">${actionConfig.title}</span>
            </button>
        `;
    });
    
    return buttonsHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    } catch (e) {
        return 'N/A';
    }
}

function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return unsafe
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonColor: '#3085d6'
    });
}

function initViewRequestModal() {
    document.querySelectorAll('.action-btn.view').forEach(btn => {
        btn.addEventListener('click', function() {
            const requestId = this.getAttribute('data-request-id');
            openViewModal(requestId);
        });
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('viewRequestModal')?.style.display === 'flex') {
                closeViewModal();
            }
        }
    });
    
    document.getElementById('viewRequestModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeViewModal();
        }
    });
}

// Export functions for global access if needed
window.requestTable = {
    filterRequests,
    resetRequestFilters,
    displayRequestPage
};