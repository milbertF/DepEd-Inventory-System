<div class="item-view-modal" id="itemViewModal" style="display: none;">
  <div class="view-modal-content">
    <button class="close-btn" onclick="closeItemView()">
      <i class="fas fa-times"></i>
    </button>

    <div class="modal-header">
      <h2 class="modal-title">Item Details</h2>
      <div class="modal-subtitle">Complete information of selected item</div>
    </div>

    <div class="item-info">
      <div class="photo-section">
        <div class="photo-container">
          <img id="view-item-photo" src="" alt="Item Photo" class="view-photo" />
          <div class="photo-frame"></div>
        </div>
      </div>

      <div class="info-grid">
        
        <div class="info-item">
          <span class="info-label">Name:</span>
          <span class="info-value" id="view-item-name"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Category:</span>
          <span class="info-value" id="view-item-category"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Description:</span>
          <span class="info-value" id="view-item-description"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Brand:</span>
          <span class="info-value" id="view-item-brand"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Model:</span>
          <span class="info-value" id="view-item-model"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Serial Number:</span>
          <span class="info-value" id="view-item-serial"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Quantity:</span>
          <span class="info-value" id="view-item-quantity"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Unit:</span>
          <span class="info-value" id="view-item-unit"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Unit Cost:</span>
          <span class="info-value" id="view-item-unit-cost"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Total Cost:</span>
          <span class="info-value" id="view-item-total-cost"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Date Acquired:</span>
          <span class="info-value" id="view-item-date-acquired"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Item Status:</span>
          <span class="info-value" id="view-item-status"></span>
        </div>
        <div class="info-item">
          <span class="info-label">Created At:</span>
          <span class="info-value" id="view-item-created-at"></span>
        </div>
      </div>
    </div>

    <div class="modal-footer">
     
    </div>
  </div>
</div>
