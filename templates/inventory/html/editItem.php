<div class="addEmployee" style="display: none;" id="editItemModal">
  <div class="esc">
    <button id="edit-btnEsc" onclick="escEditItemModal()">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="con">
    <h4>Edit Item</h4>

    <form method="POST" enctype="multipart/form-data" id="edit-item-form" autocomplete="off">

      <div class="inpart">
        <label for="edit-item-photo">Item Photo</label>
        <div class="info">
          <p>Only PNG, JPG, and GIF are allowed.</p>
        </div>
        <div class="photo-upload-wrapper">
          <label class="custom-file-upload">
            <input type="file" id="edit-item-photo" name="photo" accept="image/*" onchange="previewItemPhoto(event)" />
            <i class="fa-solid fa-upload"></i> Choose Photo
          </label>
          <div class="photo-preview" id="edit-item-photo-preview">
          <img id="edit-itemPhotoOutput" src="" alt="Preview" style="display: none; max-width: 100px; border-radius: 0.5rem;" />


          </div>
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-name">Item Name <span>*</span></label>
        <div class="inputs">
          <input type="text" id="edit-item-name" name="item_name" required />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-category-id">Category <span>*</span></label>
        <div class="inputs">
          <select id="edit-item-category-id" name="category_id" required>
            <option value="" disabled>Select a category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['category_id'] ?>"><?= $cat['category_name'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-description">Description</label>
        <div class="inputs">
          <input id="edit-item-description" name="description" placeholder="e.g., Canon PIXMA G3010" />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-brand">Brand</label>
        <div class="inputs">
          <input type="text" id="edit-item-brand" name="brand" placeholder="e.g., Canon, Acer" />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-model">Model / Variant</label>
        <div class="inputs">
          <input type="text" id="edit-item-model" name="model" placeholder="e.g., G3010, V2.1" />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-serial-number">Serial Number</label>
        <div class="inputs">
          <input type="text" id="edit-item-serial_number" name="serial_number" placeholder="Unique identifier" />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-date-acquired">Date Acquired <span>*</span></label>
        <div class="inputs">
        <input type="date" id="edit-item-date-acquired" name="date_acquired" 
       value="<?= !empty($item['date_acquired']) && $item['date_acquired'] !== '0000-00-00' ? htmlspecialchars($item['date_acquired']) : '' ?>" />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-qty">Quantity <span>*</span></label>
        <div class="inputs">
          <input type="number" id="edit-item-qty" name="quantity" min="1" required />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-unit">Unit of Measurement</label>
        <div class="inputs">
          <input type="text" id="edit-item-unit" name="unit" placeholder="e.g., pcs, boxes, sets" />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-unit-cost">Unit Cost (₱) <span>*</span></label>
        <div class="inputs">
          <input type="number" id="edit-item-unit-cost" name="unit_cost" min="0" step="0.01" required />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-item-total-cost">Total Cost (₱)</label>
        <div class="inputs">
          <input type="number" id="edit-item-total-cost" name="total_cost" min="0" step="0.01" readonly />
        </div>
      </div>

      <input type="hidden" name="item_id" id="edit-item-id" />

      <div class="btnSave">
      <button type="submit" name="submit_edit_item">Update Item</button>

      </div>
    </form>
  </div>
</div>
