<div class="addEmployee" style="display: none;" id="addQuantityModal">
  <div class="esc">
    <button id="btnEscQuantity" onclick="escQuantityModal()">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="con">
    <h4>
      Add Quantity for 
      <span style="font-weight: 500; color: var(--textColor, #333);">
        Item :
      </span>
      <span id="addQuantityItemName" style="color: var(--accentColor, #007bff);">
        Bond Paper A4
      </span>
    </h4>

    <form method="POST" autocomplete="off" id="addQuantityForm">
      <div class="inpart">
        <label for="quantity">Quantity to Add</label>
        <div class="inputs">
          <input 
            type="number" 
            id="quantity" 
            name="quantity" 
            placeholder="Enter quantity to add" 
            min="1" 
            required 
          />
        </div>
      </div>

      <div class="btnSave">
        <button type="submit" id="submitQuantityBtn">
          <i class="fas fa-plus"></i> Add Quantity
        </button>
      </div>
    </form>
  </div>
</div>

