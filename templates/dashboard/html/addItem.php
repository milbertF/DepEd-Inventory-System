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
            <img id="itemPhotoOutput" src="" alt="Preview" style="display:none; max-width: 100px; border-radius: 0.5rem;" />
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
            <option value="" disabled selected >Select a category</option>
            
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['category_id'] ?>"><?= $cat['category_name'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>


      <div class="inpart">
        <label for="item-description">Description</label>
        <div class="inputs">
          <input id="item-description" name="description" rows="3" placeholder="e.g., Canon PIXMA G3010"></input>
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

      <div class="inpart">
        <label for="serial-number">Serial Number</label>
        <div class="inputs">
          <input type="text" id="serial-number" name="serial_number" placeholder="Unique identifier" />
        </div>
      </div>


   
      <div class="inpart">
  <label>Multiple Serial Numbers</label>
  <div class="inputs">
    <button type="button" id="add-multiple-serials" onclick="openSerialModal()"  style ="min-width:100%  ; border:none;" class="custom-file-upload">
      Add Multiple Serial Numbers
    </button>
    <div id="serial-numbers-list" style="margin-top: 8px;"></div>
  </div>
</div>

      <div class="inpart">
  <label for="date-acquired">Date Acquired <span>*</span></label>
  <div class="inputs">
    <input type="date" id="date-acquired" name="date_acquired" />
  </div>
</div>


      <div class="inpart">
        <label for="item-quantity">Quantity <span>*</span></label>
        <div class="inputs">
          <input type="number" id="item-quantity" name="quantity" min="1" required />
        </div>
      </div>

      <div class="inpart">
        <label for="item-unit">Unit of Measurement </label>
        <div class="inputs">
          <input type="text" id="item-unit" name="unit" placeholder="e.g., pcs, boxes, sets" />
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

      <div class="btnSave">
  <button type="submit" name="submit_item">Save</button>
 

  </button>
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
    <h4>Add Multiple Serial Numbers</h4>
    <div class="info">
      Enter all serial numbers for this item. Each serial will be saved as a separate entry.
    </div>
    
    <div id="serial-inputs-container">
   
      <div class="serial-entry">
        <div class="inpart">
          <label>Serial Number #2</label>
          <div class="inputs">
            <input type="text" name="serials[0]" placeholder="Enter serial number" required>
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
