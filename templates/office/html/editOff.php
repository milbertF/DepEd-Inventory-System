<div class="addEmployee" id="editOfficeModal" style="display: none;">
  <div class="esc">
    <button onclick="closeEditModal()">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="con">
    <h4>Edit Office</h4>

    <form method="POST" autocomplete="off" id="edit-office-form">
      <input type="hidden" name="office_id" id="edit-office-id">

      <div class="inpart">
        <label for="edit-office-name">Office Name</label>
        <div class="inputs">
          <input type="text" id="edit-office-name" name="office_name" required />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-office-location">Location (optional)</label>
        <div class="inputs">
          <input type="text" id="edit-office-location" name="office_location" />
        </div>
      </div>

      <div class="btnSave">
        <button type="submit" name="update_office">Save Changes</button>
      </div>
    </form>
  </div>
</div>
