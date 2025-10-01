
<div id="itemModal" class="addEmployee">
  <div class="borrow-item-modal-content">
    <span class="close" onclick="closeItemModal()">&times;</span>
    <h3>Select Item</h3>
    <div>
      <label>Category:</label>
      <select id="categoryFilter" onchange="filterItemsByCategory(this.value)">
        <option value="">-- Select Category --</option>
        <?php foreach($categories as $c): ?>
          <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
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
            <th>Select</th>
          </tr>
        </thead>
        <tbody id="itemTableBody"></tbody>
      </table>
    </div>
  </div>
</div>
