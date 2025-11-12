


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
