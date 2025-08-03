




<style>

  #exportModal {
    display: flex;

    align-items: center;
  }
  
  #exportModal .con {
    max-height: fit-content;
    margin: 0;
    transform: none;
    top: auto;
    position: relative;
  }
</style>

<div class="addEmployee" id="exportModal" style="display: none;">
  <div class="esc">
    <button onclick="document.getElementById('exportModal').style.display='none'">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <div class="con">
    <h4>Export Filter</h4>

    <form action="/templates/inventory/function/exportCategoryToExcel.php" method="GET">
      <input type="hidden" name="category_id" value="<?= htmlspecialchars($categoryId) ?>">

      <!-- Brand -->
      <div class="inpart">
        <label for="brand">Brand:</label>
        <div class="inputs">
          <select name="brand" id="brand">
            <option value="all">All Brands</option>
            <?php foreach ($brands as $brand): ?>
              <option value="<?= htmlspecialchars($brand) ?>"><?= htmlspecialchars($brand) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Model -->
      <div class="inpart">
        <label for="model">Model:</label>
        <div class="inputs">
          <select name="model" id="model">
            <option value="all">All Models</option>
            <?php foreach ($models as $model): ?>
              <option value="<?= htmlspecialchars($model) ?>"><?= htmlspecialchars($model) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Quantity Range -->
      <div class="inpart">
        <label>Quantity Range:</label>
        <div style="display: flex; gap: 0.5rem;">
          <div class="inputs" style="flex: 1;">
            <input type="number" name="min_quantity" placeholder="Min (e.g. 5)" min="0">
          </div>
          <div class="inputs" style="flex: 1;">
            <input type="number" name="max_quantity" placeholder="Max (e.g. 100)" min="0">
          </div>
        </div>
      </div>


      <div class="inpart">
        <label>Unit Cost Range:</label>
        <div style="display: flex; gap: 0.5rem;">
          <div class="inputs" style="flex: 1;">
            <input type="number" step="0.01" name="min_cost" placeholder="Min Cost" min="0">
          </div>
          <div class="inputs" style="flex: 1;">
            <input type="number" step="0.01" name="max_cost" placeholder="Max Cost" min="0">
          </div>
        </div>
      </div>

 <!-- Date Range -->
<div class="inpart">
  <label>Date Acquired Range:</label>
  <div style="display: flex; gap: 0.5rem;">
    
    <!-- From -->
    <div style="flex: 1;">
      <div style="font-size: 0.85rem; margin-bottom: 0.2rem;">From</div>
      <div class="inputs">
        <input type="date" name="start_date">
      </div>
    </div>
    
    <!-- To -->
    <div style="flex: 1;">
      <div style="font-size: 0.85rem; margin-bottom: 0.2rem;">To</div>
      <div class="inputs">
        <input type="date" name="end_date">
      </div>
    </div>

  </div>
</div>


      <!-- Button -->
      <div class="btnSave">
        <button type="submit">
          <i class="fas fa-file-excel"></i> Export to Excel
        </button>
      </div>
    </form>
  </div>
</div>
