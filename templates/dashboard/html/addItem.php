<?php
require __DIR__ . '/../function/fetchCategory.php';
require __DIR__ . '/../function/addItemFunction.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="/styles/addOffPos.css" />
  <style>
    .serial-tag {
      display: none; /* hide long serial list on form */
      background: #f0f0f0;
      padding: 5px 10px;
      border-radius: 4px;
      margin: 5px 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .serial-tag button {
      background: none;
      border: none;
      color: #ff4444;
      cursor: pointer;
    }
    .remove-serial {
      background: none;
      border: none;
      color: #ff4444;
      position: absolute;
      right: 10px;
      top: 10px;
      cursor: pointer;
    }
    .serial-entry {
      position: relative;
      margin-bottom: 15px;
      padding-right: 30px;
    }
    #serial-numbers-list {
      display: none; /* hide long serial list on form */
    }
    #multiple-serial-indicator {
      display: none; /* hide long serial list on form */
      font-size: 0.875rem;
      color: #555;
      margin-top: 5px;
      display: none;
    }
  </style>
</head>

<body>
<div class="addEmployee" style="display: none;" id="addItem">
  <div class="esc">
    <button id="btnEsc" onclick="escItem()">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="con">
    <h4>Add Item</h4>

    <form method="POST" enctype="multipart/form-data" id="item-form" autocomplete="off">
      <div class="inpart">
        <label for="item-photo">Item Photo</label>
        <div class="info">
          <p>Only PNG, JPG, and GIF are allowed.</p>
        </div>
        <div class="photo-upload-wrapper">
          <label class="custom-file-upload">
            <input type="file" id="item-photo" name="photo" accept="image/*" onchange="previewItemPhoto(event)" />
            <i class="fa-solid fa-upload"></i> Choose Photo
          </label>
          <div class="photo-preview" id="item-photo-preview">
            <img id="itemPreviewOutput" src="" alt="Preview" style="display:none; max-width: 100px; border-radius: 0.5rem;" />
          </div>
        </div>
      </div>

      <div class="inpart">
        <label for="item-name">Item Name <span>*</span></label>
        <div class="inputs">
          <input type="text" id="item-name" name="item_name" required />
        </div>
      </div>

      <div class="inpart">
        <label for="category-id">Category <span>*</span></label>
        <div class="inputs">
          <select id="category-id" name="category_id" required>
            <option value="" disabled selected>Select a category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['category_id'] ?>"><?= $cat['category_name'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="inpart">
        <label for="item-description">Description</label>
        <div class="inputs">
          <input id="item-description" name="description" rows="3" placeholder="e.g., Canon PIXMA G3010 or Color etc"></input>
        </div>
      </div>

      <div class="inpart">
        <label for="item-brand">Brand</label>
        <div class="inputs">
          <input type="text" id="item-brand" name="brand" placeholder="e.g., Canon, Acer" />
        </div>
      </div>

      <div class="inpart">
        <label for="item-model">Model / Variant</label>
        <div class="inputs">
          <input type="text" id="item-model" name="model" placeholder="e.g., G3010, V2.1" />
        </div>
      </div>

      <div class="info">
        <p>For items with identical details (same item details), you can add multiple serial numbers to track individual units.</p>
      </div>
      <div id="multiSerialNotice" style="display: none; color: #d00; font-weight: bold;">
  This item has multiple serial numbers.
</div>
      <div class="inpart">
        <label for="serial-number">Serial Number #1</label>
        <div class="inputs">
          <input type="text" id="serial-number" name="serial_number" placeholder="Enter first serial number" />
        </div>
      </div>

      <div class="inpart">
        <label>Additional Serial Numbers</label>
        <div class="inputs">
          <button type="button" id="add-multiple-serials" onclick="openSerialModal()" style="min-width:100%; border:none;" class="custom-file-upload">
             Add More Serial  Numbers 
          </button>
          <div id="serial-numbers-list" style="margin-top: 8px;"></div>
          <div  id="multiple-serial-indicator"></div>
        </div>
      </div>

      <div class="inpart">
        <label for="date-acquired">Date Acquired <span>*</span></label>
        <div class="inputs">
          <input type="date" id="date-acquired" name="date_acquired" required />
        </div>
      </div>
      <div class="info" style="margin-top: 1.5rem; ">
 
  If you have multiple serial numbers, the quantity entered below will be applied to each serial number.
  </p>
</div>


      <div class="inpart">
        <label for="item-quantity">Quantity <span>*</span></label>
        <div class="inputs">
          <input type="number" id="item-quantity" name="quantity" placeholder="Enter quantity" />
        </div>
      </div>

      <div class="inpart">
        <label for="item-unit">Unit of Measurement</label>
        <div class="inputs">
          <input type="text" id="item-unit" name="unit" placeholder="e.g., pcs, boxes, sets"  />
        </div>
      </div>

      <div class="inpart">
        <label for="unit-cost">Unit Cost (₱) <span>*</span></label>
        <div class="inputs">
          <input type="number" id="unit-cost" name="unit_cost" min="0" step="0.01" required />
        </div>
      </div>

      <div class="inpart">
        <label for="total-cost">Total Cost (₱)</label>
        <div class="inputs">
          <input type="number" id="total-cost" name="total_cost" min="0" step="0.01" readonly />
        </div>
      </div>

      <div id="hidden-serial-inputs"></div>

      <div class="btnSave">
        <button type="submit" name="submit_item">Save Item</button>
      </div>
    </form>
  </div>
</div>

<div id="serialModal" class="addEmployee" style="display: none; z-index: 1001;">
  <div class="esc">
    <button onclick="closeSerialModal()">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>
  <div class="con">
    <h4>Add Additional Serial Numbers</h4>
    <div class="info">
      <p>Enter additional serial numbers (starting from #2). Each will be saved as a separate inventory item.</p>
    </div>
    
    <div id="serial-inputs-container">
      <div class="serial-entry">
        <div class="inpart">
          <label>Serial Number #2</label>
          <div class="inputs">
            <input type="text" class="modal-serial-input" placeholder="Enter serial number" />
          </div>
        </div>
      </div>
    </div>
    
    <div class="btn-group">
      <button type="button" class="add-more" onclick="addMoreSerial()">
        <i class="fas fa-plus"></i> Add Another Serial
      </button>
    </div>
    
    <div class="btnSave">
      <button type="button" id="save-serials">Save All Serials</button>
    </div>
  </div>
</div>

<script src="/javascript/addItem.js"></script>






</body>
</html>
