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

  .err-text { 
    color: #e11d48; 
    font-size: 0.9rem; 
    margin: 0.35rem 0 0.25rem; 
  }

  .btnSave button[disabled] { 
    opacity: .6; 
    cursor: not-allowed; 
  }

  .export-check-options.clean {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px 15px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
  }

  .clean-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 4px;
    cursor: pointer;
    user-select: none;
  }

  .clean-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    border: 2px solid #cbd5e1;
    border-radius: 4px;
    appearance: none;
    cursor: pointer;
    position: relative;
    transition: 0.2s;
  }

  .clean-checkbox input[type="checkbox"]:hover {
    border-color: var(--accentColor, #c40000);
  }

  .clean-checkbox input[type="checkbox"]:checked {
    background: var(--accentColor, #c40000);
    border-color: var(--accentColor, #c40000);
  }

  .clean-checkbox input[type="checkbox"]:checked::before {
    content: "";
    position: absolute;
    left: 5px;
    width: 4px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
  }

  .clean-checkbox span {
    font-size: 0.95rem;
    font-weight: 500;
  }

  .export-label {
    display: block;
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 6px;
    color: #1e293b;
  }

  .btnSave button {
    width: 100% !important;
    padding: 0.75rem !important;
    background-color: var(--accentColor, #c40000) !important;
    color: white !important;
    border: none !important;
    border-radius: 4px !important;
    font-size: 1rem !important;
    cursor: pointer !important;
    transition: background-color 0.2s !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
  }

  .btnSave button:hover {
    background-color: #a30000 !important;
  }

  @media screen and (max-width: 480px) {
    .export-check-options {
      grid-template-columns: 1fr !important;
      gap: 0.5rem !important;
    }
    
    .export-check-item {
      height: 2.2rem !important;
      padding: 0.6rem 0.75rem !important;
    }
    
    .export-check-item input[type="checkbox"] {
      width: 1.3rem !important;
      height: 1.3rem !important;
    }
    
    .export-check-item input[type="checkbox"]:checked::before {
      top: 4px !important;
      left: 7px !important;
      width: 4px !important;
      height: 8px !important;
    }
    
    .check-label {
      font-size: 0.9rem !important;
      left: 3.2rem !important;
    }
  }
</style>

<div class="addEmployee" id="exportModal" style="display:none;">
  <div class="esc">
    <button type="button" onclick="document.getElementById('exportModal').style.display='none'">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <div class="con">
    <h4>Export Filter</h4>

    <!-- Category-specific export -->
    <form id="exportForm" action="/templates/inventory/function/exportCategoryToExcel.php" method="GET" novalidate>
      <input type="hidden" name="category_id" value="<?= htmlspecialchars($categoryId) ?>">

      <!-- Status Filter - MULTIPLE SELECTION -->
      <label class="export-label"> Item Status:</label>

      <div class="export-check-options clean">
        <label class="clean-checkbox">
          <input type="checkbox" name="status[]" value="Good">
          <span>Good</span>
        </label>

        <label class="clean-checkbox">
          <input type="checkbox" name="status[]" value="For Repair">
          <span>For Repair</span>
        </label>

        <label class="clean-checkbox">
          <input type="checkbox" name="status[]" value="Damaged">
          <span>Damaged</span>
        </label>

        <label class="clean-checkbox">
          <input type="checkbox" name="status[]" value="Lost">
          <span>Lost</span>
        </label>
      </div>

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
        <div id="quantityError" class="err-text"></div>
        <div style="display:flex; gap:0.5rem;">
          <div class="inputs" style="flex:1;">
            <input type="number" id="minQuantity" name="min_quantity" placeholder="Min (e.g. 5)" min="0" inputmode="numeric">
          </div>
          <div class="inputs" style="flex:1;">
            <input type="number" id="maxQuantity" name="max_quantity" placeholder="Max (e.g. 100)" min="0" inputmode="numeric">
          </div>
        </div>
      </div>

      <!-- Unit Cost Range -->
      <div class="inpart">
        <label>Unit Cost Range:</label>
        <div id="costError" class="err-text"></div>
        <div style="display:flex; gap:0.5rem;">
          <div class="inputs" style="flex:1;">
            <input type="number" id="minCost" name="min_cost" step="0.01" placeholder="Min Cost" min="0" inputmode="decimal">
          </div>
          <div class="inputs" style="flex:1;">
            <input type="number" id="maxCost" name="max_cost" step="0.01" placeholder="Max Cost" min="0" inputmode="decimal">
          </div>
        </div>
      </div>

      <!-- Date Range -->
      <div class="inpart">
        <label>Date Acquired Range:</label>
        <div style="display:flex; gap:0.5rem;">
          <div style="flex:1;">
            <div style="font-size:0.85rem; margin-bottom:0.2rem;">From</div>
            <div class="inputs">
              <input type="date" id="start_date" name="start_date">
            </div>
          </div>
          <div style="flex:1;">
            <div style="font-size:0.85rem; margin-bottom:0.2rem;">To</div>
            <div class="inputs">
              <input type="date" id="end_date" name="end_date">
            </div>
          </div>
        </div>
      </div>

      <!-- Button -->
      <div class="btnSave">
        <button id="exportBtn" type="submit">
          <i class="fas fa-file-excel"></i> Export to Excel
        </button>
      </div>
    </form>
  </div>
</div>

<script src="/javascript/exportModal.js"></script>