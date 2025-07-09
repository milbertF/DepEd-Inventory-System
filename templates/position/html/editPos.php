<div class="addEmployee" id="editPositionModal" style="display: none;">
  <div class="esc">
    <button onclick="closeEditModal()">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="con">
    <h4>Edit Position</h4>

    <form method="POST" autocomplete="off" id="edit-position-form">
      <input type="hidden" name="position_id" id="edit-position-id">

      <div class="inpart">
        <label for="edit-position-title">Position Title</label>
        <div class="inputs">
          <input type="text" id="edit-position-title" name="position_title" required />
        </div>
      </div>

      <div class="inpart">
        <label for="edit-position-description">Description (optional)</label>
        <div class="inputs">
          <input type="text" id="edit-position-description" name="position_description" />
        </div>
      </div>

      <div class="btnSave">
        <button type="submit" name="update_position">Save Changes</button>
      </div>
    </form>
  </div>


</div>
