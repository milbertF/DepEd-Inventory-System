document.addEventListener('DOMContentLoaded', function() {
    const statusCheckboxes = document.querySelectorAll('.status-checkbox');
    const tableRows = document.querySelectorAll('.requestTable tbody tr');
    
    function loadCheckboxStates() {
        statusCheckboxes.forEach(checkbox => {
            const savedState = localStorage.getItem(`filter-${checkbox.dataset.status}`);
            if (savedState !== null) {
                checkbox.checked = savedState === 'true';
            }
        });
    }
    
    function saveCheckboxStates() {
        statusCheckboxes.forEach(checkbox => {
            localStorage.setItem(`filter-${checkbox.dataset.status}`, checkbox.checked);
        });
    }
    
    function updateFilter() {
        const selectedStatuses = Array.from(statusCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.dataset.status);
      
        const showAll = selectedStatuses.length === 0;
        
        tableRows.forEach(row => {
            if (showAll) {
                row.style.display = '';
                return;
            }
            
            const requestData = JSON.parse(row.getAttribute('data-request'));
            const items = requestData.items || [];
            
            const hasMatchingStatus = items.some(item => {
                const itemStatus = item.status ? item.status.toLowerCase() : 'pending';
                return selectedStatuses.includes(itemStatus);
            });
            
            if (hasMatchingStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        saveCheckboxStates();
    }

    loadCheckboxStates();
    
    statusCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateFilter();
        });
    });
    
    updateFilter();
});

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

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initViewRequestModal);
} else {
    initViewRequestModal();
}