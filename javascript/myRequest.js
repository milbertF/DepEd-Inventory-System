document.addEventListener('DOMContentLoaded', function() {
    // Initialize everything when DOM is loaded
    initializeRequestTable();
    initRequestPaginationEventListeners();
    initViewRequestModal();
    
    // Add any existing search term from URL
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('search');
    if (searchTerm && document.getElementById('searchRequest')) {
        document.getElementById('searchRequest').value = searchTerm;
        setTimeout(() => filterRequests(), 100);
    }

    // Initialize scroll from URL
    initializeRequestScrollFromURL();
});

// Global variables for request table management
let requestCurrentFilters = {
    status: ['pending'],
    search: ''
};

let allRequestRows = [];
let filteredRequestRows = [];
let requestCurrentPage = 1;
let requestRowsPerPage = parseInt(localStorage.getItem('requestTableRowsPerPage')) || 10;
let requestDebounceTimer = null;

// Scroll functionality variables
let isRedirectingToRequest = false;
let targetRequestId = null;
let isShowingRequestNotFoundAlert = false;

// Scroll to specific request function
window.scrollToRequest = function(requestId) {
    if (!requestId || isRedirectingToRequest) return;
    
    isRedirectingToRequest = true;
    targetRequestId = requestId;
    
    resetRequestFiltersForScroll(requestId);
};

// Reset filters to show all requests for scrolling
function resetRequestFiltersForScroll(requestId) {
    const searchInput = document.getElementById('searchRequest');
    if (searchInput) searchInput.value = '';
    
    const statusCheckboxes = document.querySelectorAll('.status-checkbox');
    statusCheckboxes.forEach(checkbox => checkbox.checked = true);
    
    requestCurrentFilters.status = ['pending', 'approved', 'declined', 'released', 'returned', 'canceled', 'received'];
    requestCurrentFilters.search = '';
    
    saveStatusFilters();
    filterRequests();
    
    setTimeout(() => attemptRequestScroll(requestId), 300);
}

// Attempt to scroll to request after filters are applied
function attemptRequestScroll(requestId) {
    const allVisibleRows = Array.from(document.querySelectorAll('.requestTable tbody tr[data-status]'));
    const searchId = String(requestId).trim();
    
    let foundRow = null;
    let foundIndex = -1;
    
    allVisibleRows.forEach((row, index) => {
        try {
            const requestData = JSON.parse(row.getAttribute('data-request'));
            const rowId = String(requestData.request_id).trim();
            
            if (rowId === searchId) {
                foundRow = row;
                foundIndex = index;
            }
        } catch (e) {}
    });
    
    if (foundIndex !== -1 && foundRow) {
        const targetPage = Math.floor(foundIndex / requestRowsPerPage) + 1;
        
        if (requestCurrentPage !== targetPage) {
            requestCurrentPage = targetPage;
            setTimeout(() => {
                displayRequestPage(targetPage);
                setTimeout(() => scrollToRequestOnCurrentPage(requestId), 500);
            }, 200);
        } else {
            scrollToRequestOnCurrentPage(requestId);
        }
    } else {
        showRequestNotFoundMessage(requestId);
    }
}

// Scroll to request on current page and highlight it
function scrollToRequestOnCurrentPage(requestId) {
    const searchId = String(requestId).trim();
    let targetRow = document.querySelector(`tr[data-request*='"request_id":"${searchId}"']`);
    
    if (!targetRow) {
        const allRows = Array.from(document.querySelectorAll('.requestTable tbody tr[data-request]'));
        for (const row of allRows) {
            try {
                const requestData = JSON.parse(row.getAttribute('data-request'));
                const rowId = String(requestData.request_id).trim();
                if (rowId === searchId) {
                    targetRow = row;
                    break;
                }
            } catch (e) {}
        }
    }
    
    if (targetRow && targetRow.style.display !== 'none') {
        document.querySelectorAll('.highlight-request, .highlight-request-strong').forEach(row => {
            row.classList.remove('highlight-request', 'highlight-request-strong');
        });
        
        targetRow.classList.add('highlight-request');
        
        setTimeout(() => {
            targetRow.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center',
                inline: 'nearest'
            });
            targetRow.classList.add('highlight-request-strong');
        }, 100);
        
        setTimeout(() => targetRow.classList.remove('highlight-request-strong'), 3000);
        setTimeout(() => {
            targetRow.classList.remove('highlight-request');
            cleanupRequestScrollState(requestId);
        }, 6000);
    } else {
        showRequestNotFoundMessage(requestId);
    }
}

// Clean up scroll state after completion
function cleanupRequestScrollState(requestId) {
    isRedirectingToRequest = false;
    targetRequestId = null;
    
    const url = new URL(window.location);
    if (url.searchParams.get('request_id') === requestId) {
        url.searchParams.delete('request_id');
        window.history.replaceState({}, '', url);
    }
}

// Show notification when request is not found
function showRequestNotFoundMessage(requestId) {
    if (isShowingRequestNotFoundAlert) return;
    
    isShowingRequestNotFoundAlert = true;
    
    Swal.fire({
        icon: 'warning',
        title: 'Request Not Found',
        html: `The request (ID: <b>${requestId}</b>) could not be found in the current view.`,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3085d6',
        background: '#fff',
    }).then(() => {
        cleanupRequestScrollState(requestId);
        isShowingRequestNotFoundAlert = false;
    });
}

// Initialize scroll from URL parameters
function initializeRequestScrollFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const requestId = urlParams.get('request_id');
    
    if (requestId) {
        setTimeout(() => scrollToRequest(requestId), 1000);
    }
}

// Initialize the request table
function initializeRequestTable() {
    allRequestRows = Array.from(document.querySelectorAll('.requestTable tbody tr'));
    
    const existingNoResults = document.getElementById('noRequestsMessage');
    if (existingNoResults) existingNoResults.remove();
    
    initRequestRowsPerPageSelector();
    loadStatusFilters();
    filterRequests();
}

// Load saved status filters from localStorage
function loadStatusFilters() {
    const savedFilters = localStorage.getItem('requestStatusFilters');
    if (savedFilters) {
        const savedStatuses = JSON.parse(savedFilters);
        requestCurrentFilters.status = savedStatuses;
        
        const statusCheckboxes = document.querySelectorAll('.status-checkbox');
        statusCheckboxes.forEach(checkbox => {
            const status = checkbox.getAttribute('data-status');
            checkbox.checked = savedStatuses.includes(status);
        });
    } else {
        const pendingCheckbox = document.querySelector('.status-checkbox[data-status="pending"]');
        if (pendingCheckbox) pendingCheckbox.checked = true;
        requestCurrentFilters.status = ['pending'];
    }
}

// Save status filters to localStorage
function saveStatusFilters() {
    localStorage.setItem('requestStatusFilters', JSON.stringify(requestCurrentFilters.status));
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

// Filter requests based on current search and status filters
function filterRequests() {
    const searchValue = (document.getElementById('searchRequest')?.value || '').toLowerCase().trim();
    const statusCheckboxes = document.querySelectorAll('.status-checkbox:checked');
    const selectedStatuses = Array.from(statusCheckboxes).map(cb => cb.getAttribute('data-status'));
    
    requestCurrentFilters.status = selectedStatuses;
    requestCurrentFilters.search = searchValue;
    saveStatusFilters();
    
    filteredRequestRows = [];
    
    allRequestRows.forEach(row => {
        const requestData = JSON.parse(row.getAttribute('data-request'));
        const rowData = {
            requestId: String(requestData.request_id || '').toLowerCase(),
            requesterName: String(requestData.requester_name || '').toLowerCase(),
            positionTitle: String(requestData.position_title || '').toLowerCase(),
            officeName: String(requestData.office_name || '').toLowerCase(),
            items: requestData.items || [],
            status: String(requestData.status || '').toLowerCase(),
            itemsSearchText: (requestData.items || []).map(item => 
                `${item.item_name || ''} ${item.item_id || ''} ${item.serial_number || ''} ${item.category_name || ''} ${item.brand || ''} ${item.model || ''} ${item.description || ''}`
            ).join(' ').toLowerCase()
        };
        
        let statusMatch = true;
        if (selectedStatuses.length > 0) {
            statusMatch = rowData.items.some(item => {
                const itemStatus = (item.status || 'pending').toLowerCase();
                return selectedStatuses.includes(itemStatus);
            });
        }
        
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
        
        if (statusMatch && searchMatch) filteredRequestRows.push(row);
    });
    
    requestCurrentPage = 1;
    updateRequestDisplay();
}

// Display specific page of requests
function displayRequestPage(page = 1) {
    const start = (page - 1) * requestRowsPerPage;
    const end = start + requestRowsPerPage;

    requestAnimationFrame(() => {
        allRequestRows.forEach(row => row.style.display = "none");
        
        for (let i = start; i < Math.min(end, filteredRequestRows.length); i++) {
            if (filteredRequestRows[i]) filteredRequestRows[i].style.display = "";
        }
        
        updateRequestPagination(page);
        updateRequestCounts();
        updateRequestRowNumbers();
        
        if (isRedirectingToRequest && targetRequestId && page === requestCurrentPage) {
            setTimeout(() => {
                const searchId = String(targetRequestId).trim();
                let targetRow = document.querySelector(`tr[data-request*='"request_id":"${searchId}"']`);
                if (!targetRow) {
                    const allRows = Array.from(document.querySelectorAll('.requestTable tbody tr[data-request]'));
                    for (const row of allRows) {
                        try {
                            const requestData = JSON.parse(row.getAttribute('data-request'));
                            const rowId = String(requestData.request_id).trim();
                            if (rowId === searchId) {
                                targetRow = row;
                                break;
                            }
                        } catch (e) {}
                    }
                }
                
                if (targetRow && targetRow.style.display !== 'none') {
                    targetRow.classList.add('highlight-request');
                }
            }, 200);
        }
    });
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
    
    if (page > 1) {
        html += `<a href="#" class="prev-next" data-page="1" title="First page"><i class="fas fa-angle-double-left"></i></a>`;
        html += `<a href="#" class="prev-next" data-page="${page - 1}" title="Previous page"><i class="fas fa-chevron-left"></i></a>`;
    }
    
    for (let i = Math.max(1, page - range); i <= Math.min(totalPages, page + range); i++) {
        html += `<a href="#" class="${i === page ? 'active' : ''}" data-page="${i}">${i}</a>`;
    }
    
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

// Update request counts display
function updateRequestCounts() {
    const totalRequests = allRequestRows.length;
    const filteredRequests = filteredRequestRows.length;
    const shouldShowSimpleCount = shouldShowSimpleCountDisplay();
    
    const itemCountDisplay = document.querySelector('.item-count-display');
    if (itemCountDisplay) {
        if (shouldShowSimpleCount) {
            itemCountDisplay.innerHTML = `<span id="totalRequestsCount">${filteredRequests}</span> total requests`;
        } else {
            itemCountDisplay.innerHTML = `
                <span id="totalRequestsCount">${totalRequests}</span> total requests
                | Showing <span id="visibleRequestsCount">${filteredRequests}</span> of <span id="totalRequestsCount2">${totalRequests}</span>
            `;
        }
    }
    
    const pageInfoElement = document.getElementById('pageInfo');
    if (pageInfoElement) pageInfoElement.textContent = '';
}

// Check if we should show simple count display
function shouldShowSimpleCountDisplay() {
    const searchValue = requestCurrentFilters.search;
    const statusFilters = requestCurrentFilters.status;
    const hasNoSearch = !searchValue || searchValue === '';
    const hasNoStatusFilters = statusFilters.length === 0;
    return hasNoSearch && hasNoStatusFilters;
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
    } else if (!show && noResultsRow) noResultsRow.remove();
}

// Update entire request display
function updateRequestDisplay() {
    showNoRequestsMessage(filteredRequestRows.length === 0);
    displayRequestPage(requestCurrentPage);
    updateRequestCounts();
}

// Initialize event listeners for pagination and search
function initRequestPaginationEventListeners() {
    const searchInput = document.getElementById('searchRequest');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(requestDebounceTimer);
            requestDebounceTimer = setTimeout(() => filterRequests(), 300);
        });
        
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(requestDebounceTimer);
                filterRequests();
            }
        });
    }
    
    const statusCheckboxes = document.querySelectorAll('.status-checkbox');
    statusCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => filterRequests());
    });
}

// Reset all filters to default state
function resetRequestFilters() {
    const searchInput = document.getElementById('searchRequest');
    if (searchInput) searchInput.value = '';
    
    const statusCheckboxes = document.querySelectorAll('.status-checkbox');
    statusCheckboxes.forEach(checkbox => checkbox.checked = checkbox.getAttribute('data-status') === 'pending');
    
    requestCurrentPage = 1;
    requestRowsPerPage = 10;
    
    const rowsPerPageSelect = document.getElementById("requestRowsPerPage");
    if (rowsPerPageSelect) rowsPerPageSelect.value = '10';
    
    localStorage.setItem('requestTableRowsPerPage', '10');
    localStorage.removeItem('requestStatusFilters');
    filterRequests();
}

// Modal functionality variables
let selectedActions = new Map();
let currentRequestData = null;

// Open view modal for specific request
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

// Close view modal
function closeViewModal() {
    document.getElementById('viewRequestModal').style.display = 'none';
    document.body.classList.remove('modal-open');
    currentRequestData = null;
    selectedActions.clear();
}

// Populate request details in modal
function populateRequestDetails(request) {
    requestAnimationFrame(() => {
        selectedActions.clear();
        currentRequestData = request;
        
        document.getElementById('viewRequestId').textContent = request.request_id;
        
        const statusElement = document.getElementById('viewRequestStatus');
        statusElement.textContent = request.status;
        statusElement.className = 'status-badge';
        statusElement.classList.add(`status-${request.status.toLowerCase()}`);
        
        document.getElementById('viewRequesterName').textContent = request.requester_name;
        document.getElementById('viewRequesterPosition').textContent = request.position_title;
        document.getElementById('viewRequesterOffice').textContent = request.office_name;
        document.getElementById('viewDateRequested').textContent = formatDate(request.created_at);
        
        const itemsBody = document.getElementById('viewRequestItemsBody');
        itemsBody.innerHTML = '';
        
        if (request.items && request.items.length > 0) {
            request.items.forEach((item, index) => {
                const itemId = item.req_item_id;
                if (!itemId) return;
                
                const row = document.createElement('tr');
                row.setAttribute('data-item-id', itemId);
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
            
            initItemActionButtons();
        } else {
            itemsBody.innerHTML = '<tr><td colspan="12" style="text-align: center; padding: 20px;">No items found in this request</td></tr>';
        }
        
        addBulkConfirmSection();
    });
}

// Initialize action buttons in modal
function initItemActionButtons() {
    document.querySelectorAll('.item-action-btn').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.closest('.item-action-buttons').getAttribute('data-item-id');
            const action = this.getAttribute('data-action');
            
            if (selectedActions.get(itemId) === action) {
                selectedActions.delete(itemId);
                this.classList.remove('selected');
            } else {
                selectedActions.set(itemId, action);
                const buttonGroup = this.closest('.item-action-buttons');
                buttonGroup.querySelectorAll('.item-action-btn').forEach(btn => btn.classList.remove('selected'));
                this.classList.add('selected');
            }
            
            updateBulkConfirmButton();
        });
    });
}

// Add bulk confirm section to modal
function addBulkConfirmSection() {
    const existingBulkSection = document.querySelector('.bulk-confirm-section');
    if (existingBulkSection) existingBulkSection.remove();
    
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
    
    const itemsTable = document.querySelector('.items-table-container');
    itemsTable.parentNode.insertBefore(bulkSection, itemsTable.nextSibling);
    initBulkConfirmHandler();
}

// Initialize bulk confirm handler
function initBulkConfirmHandler() {
    const confirmBulkBtn = document.getElementById('confirmBulkActions');
    if (confirmBulkBtn) {
        confirmBulkBtn.addEventListener('click', confirmAllSelectedActions);
    }
}

// Update bulk confirm button state
function updateBulkConfirmButton() {
    const confirmBulkBtn = document.getElementById('confirmBulkActions');
    const selectedCount = document.getElementById('selectedActionsCount');
    
    if (selectedCount) selectedCount.textContent = selectedActions.size;
    if (confirmBulkBtn) confirmBulkBtn.disabled = selectedActions.size === 0;
}

// Confirm all selected actions in modal
function confirmAllSelectedActions() {
    const actionsArray = Array.from(selectedActions.entries());
    
    Swal.fire({
        title: `Confirm ${selectedActions.size} Actions`,
        html: `Are you sure you want to apply ${selectedActions.size} action(s)?<br><br>
               <div class="swal-items-container">
                   <div class="swal-items-grid">
                   ${actionsArray.map(([itemId, action]) => {
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
                didOpen: () => Swal.showLoading()
            });
            
            fetch('/templates/request/function/updateItemRequestStatus.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    request_id: currentRequestData.request_id,
                    actions: actionsArray.map(([itemId, action]) => ({
                        req_item_id: itemId,  
                        action: action
                    }))
                })
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
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
                    let errorMessage = data.message || 'Failed to update items';
                    if (data.errors && data.errors.length > 0) errorMessage += '\n\nErrors:\n' + data.errors.join('\n');
                    throw new Error(errorMessage);
                }
            })
            .catch(error => {
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

// Get display text for actions
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

// Get action buttons based on item status
function getItemActionButtons(currentStatus, itemId) {
    const status = currentStatus ? currentStatus.toLowerCase() : 'pending';
    
    const actionConfigs = {
        'pending': [{ action: 'cancel', icon: 'fa-ban', class: 'cancel', title: 'Cancel' }],
        'approved': [{ action: 'cancel', icon: 'fa-ban', class: 'cancel', title: 'Cancel ' }],
        'released': [
            { action: 'received', icon: 'fa-check-circle', class: 'received', title: ' Received' },
            { action: 'cancel', icon: 'fa-ban', class: 'cancel', title: 'Cancel ' }
        ],
        'returned': [],
        'declined': [],
        'void': []
    };
    
    const actions = actionConfigs[status] || [];
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

// Format date for display
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

// Escape HTML to prevent XSS
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

// Show error message
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonColor: '#3085d6'
    });
}

// Initialize view request modal
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
        if (e.target === this) closeViewModal();
    });
}

// Export functions for global access
window.requestTable = {
    filterRequests,
    resetRequestFilters,
    displayRequestPage,
    scrollToRequest
};