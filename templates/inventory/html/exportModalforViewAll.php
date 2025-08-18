<style>
  #exportModal { display: flex; align-items: center; }
  #exportModal .con { max-height: fit-content; margin: 0; transform: none; top: auto; position: relative; }
  .err-text { color: #e11d48; font-size: 0.9rem; margin: 0.35rem 0 0.25rem; }
  .btnSave button[disabled] { opacity: .6; cursor: not-allowed; }
</style>

<div class="addEmployee" id="exportModal" style="display:none;">
  <div class="esc">
    <button type="button" onclick="document.getElementById('exportModal').style.display='none'">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <div class="con">
    <h4>Export Filter</h4>

    <!-- All-items export -->
    <form id="exportForm" action="/templates/inventory/function/exportAllItemsToExcel.php" method="GET" novalidate>

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
        <!-- No visible error required for dates -->
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