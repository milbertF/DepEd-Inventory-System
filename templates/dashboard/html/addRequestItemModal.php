<style>
  /* ============================
   CATEGORY & SEARCH FILTERS
   ============================ */
.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 10px;
  padding: 0 4px;
}

.filter-group,
.search-container {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-group label,
.search-container label {
  font-size: 15px;
  font-weight: 500;
  color: #333;
  white-space: nowrap;
}

/* ✅ Category dropdown and search input styling */
.filter-group select,
.search-container input {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  outline: none;
  font-size: 14px;
  width: 220px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.filter-group select:hover,
.search-container input:hover {
  background-color: #f1f5f9;
}

.filter-group select:focus,
.search-container input:focus {
  border-color: #93c5fd;
  box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
  background-color: white;
}

</style>


<div id="itemModal" class="addEmployee">
  <div class="borrow-item-modal-content">
    <span class="close" onclick="closeItemModal()">&times;</span>
    <h3>Select Item</h3>

    <div class="filter-bar">
      <div>
        <label>Category:</label>
   
        <select id="categoryFilter" onchange="applyFilters(event)">
          <option value="">All</option>
          <?php foreach($categories as $c): ?>
            <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="search-container">
        <label>Search:</label>
      
        <input 
          type="text" 
          id="itemSearch" 
          placeholder="Search by name, serial, description..." 
          oninput="applyFilters(event)">
      </div>
    </div>

    <div class="borrow-item-table-container">
      <table class="borrow-item-table">
        <thead>
          <tr>
       
            <th>Serial #</th>
            <th>Name</th>
            <th>Description</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Available Qty</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="itemTableBody"></tbody>
      </table>
    </div>


    <div class="pagination" id="paginationControls"></div>
  </div>
</div>

<script>
  const allItems = <?= json_encode($items) ?>;
</script>
