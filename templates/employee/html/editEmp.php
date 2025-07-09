

<div class="addEmployee" style="display: none" id="editEmployee">
  <div class="esc">
    <button id="btnEditEsc" onclick="escEditEmployee()">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <div class="con">
    <h4>Edit Employee</h4>

    <form method="POST" action="" enctype="multipart/form-data" id="edit-employee-form">

      <input type="hidden" name="info_id" id="edit-info-id" />
      <input type="hidden" name="user_id" id="edit-user-id" />


      <div class="inpart">
        <label for="editEmployee-photo">Profile Photo</label>
        <div class="info">
          <p>Only PNG, JPG, and GIF file types are allowed.</p>
        </div>
        <div class="photo-upload-wrapper">
          <label class="custom-file-upload">
            <input type="file" id="editEmployee-photo" name="photo" accept="image/*" onchange="previewEditPhoto(event)" />
            <i class="fa-solid fa-upload"></i> Choose Photo
          </label>
          <div class="edit-photo-preview" id="edit-photo-preview" s>
          <img id="edit-photoOutput" src="" alt="Preview" />


          </div>
        </div>
      </div>

    
      <div class="inpart">
        <label>First Name</label>
        <div class="inputs">
          <input type="text" id="edit-firstName" name="firstName" required />
        </div>
      </div>

      <div class="inpart">
        <label>Middle Name</label>
        <div class="inputs">
          <input type="text" id="edit-middleName" name="middleName" />
        </div>
      </div>

      <div class="inpart">
        <label>Last Name</label>
        <div class="inputs">
          <input type="text" id="edit-lastName" name="lastName" required />
        </div>
      </div>

      <div class="inpart">
        <label>Contact #</label>
        <div class="inputs">
          <input type="text" id="edit-contact" name="contact" required />
        </div>
      </div>

      <div class="inpart">
        <div class="info">
          <p><strong>Note:</strong> Address should include street name, barangay, and city or municipality.</p>
        </div>
        <label>Address</label>
        <div class="inputs">
          <input type="text" id="edit-address" name="address" required />
        </div>
      </div>

  
      <div class="inpart">
        <label>Position</label>
        <div class="inputs">
          <select id="edit-position" name="employee_position" required>
            <?php if (!empty($positions)): ?>
              <?php foreach ($positions as $pos): ?>
                <option value="<?= htmlspecialchars($pos['position_id']) ?>">
                  <?= htmlspecialchars($pos['position_title']) ?>
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="">No positions available</option>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <div class="inpart">
        <label>Office</label>
        <div class="inputs">
          <select id="edit-office" name="employee_office">
            <?php if (!empty($offices)): ?>
              <?php foreach ($offices as $office): ?>
                <option value="<?= htmlspecialchars($office['office_id']) ?>">
                  <?= htmlspecialchars($office['office_name']) ?>
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="">No offices available</option>
            <?php endif; ?>
          </select>
        </div>
      </div>


      <div class="inpart">
  <label for="edit-accountType">Account Role</label>
  <div class="inputs">
    <select id="edit-accountType" name="account_role" required>
      <option value="" disabled selected>Select a role</option>
      <option value="Admin">Admin</option>
      <option value="Employee">Employee</option>
      <option value="Deactivate">Deactivate</option>
    </select>
  </div>
</div>

      <div class="btnSave">
        <button type="submit" name="submit_edit_employee">Save Changes</button>
      </div>
    </form>
  </div>
</div>

  


<script src="/javascript/editEmployee.js"></script>
