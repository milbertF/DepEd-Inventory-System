<div class="addEmployee" style="display: none;" id="addQuantityModal">
  <div class="esc">
    <button type="button" id="btnEscQuantity" onclick="escQuantityModal()">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="con">
    <h4>
      Add Quantity for 
      <span style="font-weight: 500; color: var(--textColor, #333);">
        Item:
      </span>
      <span id="addQuantityItemName" style="color: var(--accentColor, #007bff);">
        <!-- Item name will be populated by JavaScript -->
      </span>
    </h4>

    <form method="POST" autocomplete="off" id="addQuantityForm">
      <input type="hidden" name="item_id" id="addQuantityItemId">
      
      <div class="inpart">
        <label for="quantity">Quantity to Add</label>
        <div class="inputs">
          <input 
            type="number" 
            id="quantity" 
            name="total_quantity" 
            placeholder="Enter quantity to add" 
            min="1" 
            required 
          />
        </div>
      </div>

      <div class="btnSave">
        <button type="submit" name="add_quantity" id="submitQuantityBtn">
          <i class="fas fa-plus"></i> Add Quantity
        </button>
      </div>
    </form>
  </div>
</div>