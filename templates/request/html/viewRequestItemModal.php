<div class="item-view-modal" id="viewRequestModal" style="display: none;">
    <div class="view-modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Request Details</h3>
            <p class="modal-subtitle">Complete request information</p>
            <button class="close-btn" onclick="closeViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="item-info">
      
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Request ID:</span>
                    <span class="info-value" id="viewRequestId">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Requested By:</span>
                    <span class="info-value" id="viewRequesterName">-</span>
                </div>
               
              
                <div class="info-item">
                    <span class="info-label">Position:</span>
                    <span class="info-value" id="viewRequesterPosition">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Office:</span>
                    <span class="info-value" id="viewRequesterOffice">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date Requested:</span>
                    <span class="info-value" id="viewDateRequested">-</span>
                </div>
                <div class="info-item" style = "display:none;">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span id="viewRequestStatus" class="status-badge">-</span>
                    </span>
                </div>
            </div>

       
            <h4 class="items-section-title">Requested Items</h4>
            
            <div class="items-table-container">
                <table class="items-details-table">
                    <thead >
                        <tr class="table-header-row" >
                      
                            <th >Category</th>
                            <th >Item ID</th>
                            <th>Serial Number</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Quantity</th>
                            <th>Purpose</th>
                            <th>Date Needed</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="viewRequestItemsBody">
                     
                    </tbody>
                </table>
            </div>
        </div>

        
    </div>
</div>
