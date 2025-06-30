<?php
include __DIR__ . '/../function/addEmployeeFunction.php';

include __DIR__ . '/../function/fetchOffPosFunction.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create Account</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

  <div class="addEmployee" style="display:none" id="addEmployee">
    <div class="esc">
      <button id="btnEsc" onclick="escEmployee()">
        <i class="fa-solid fa-xmark"></i>
      </button> 
    </div>

    <div class="con">
      <h4>Add Employee</h4>

      <div class="info">
        <p>Note: If username and password fields are left blank, default credentials will be set to employee's firstname and lastname.</p>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <!-- Account Info -->
        <div class="form-section">
          <div class="section-title">
            <i class="fas fa-user-circle"></i>
            <span>Account Information</span>
          </div>

          <div class="inpart">
            <label for="addEmployee-photo">Profile Photo</label>
            <div class="photo-upload-wrapper">
              <label class="custom-file-upload">
                <input type="file" id="addEmployee-photo" name="photo" accept="image/*" onchange="previewPhoto(event)" />
                <i class="fa-solid fa-upload"></i> Choose Photo
              </label>
              <div class="photo-preview" id="photo-preview">
                <img id="photoOutput" src="" alt="Preview" />
              </div>
            </div>
          </div>


          <div class="inpart">
            <label for="addEmployee-firstName">Email</label>
            <div class="inputs">
              <input type="text" id="addEmployee-email" name="email" required />
            </div>
          </div>

          <div class="inpart">
            <label for="addEmployee-firstName">First Name</label>
            <div class="inputs">
              <input type="text" id="addEmployee-firstName" name="firstName" required />
            </div>
          </div>

          <div class="inpart">
            <label for="addEmployee-middleName">Middle Name</label>
            <div class="inputs">
              <input type="text" id="addEmployee-middleName" name="middleName" />
            </div>
          </div>


          

          <div class="inpart">
            <label for="addEmployee-lastName">Last Name</label>
            <div class="inputs">
              <input type="text" id="addEmployee-lastName" name="lastName" required />
            </div>
          </div>

          <div class="inpart">
            <label for="addEmployee-contact">Contact #</label>
            <div class="inputs">
              <input type="text" id="addEmployee-contact" name="contact" required />
            </div>
          </div>

          <div class="inpart">
            <label for="addEmployee-address">Address</label>
            <div class="inputs">
              <input type="text" id="addEmployee-address" name="address" required />
            </div>
          </div>

          <div class="inpart">
            <label for="addEmployee-position">Position</label>
            <div class="inputs">
              <select id="addEmployee-position" name="employee_position" required>
                <?php if (!empty($positions)): ?>
                  <option value="" disabled selected hidden>Select a position</option>
                  <?php foreach ($positions as $pos): ?>
                    <option value="<?= htmlspecialchars($pos['position_id']) ?>">
                      <?= htmlspecialchars($pos['position_title']) ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="" disabled selected>No positions available</option>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <div class="inpart">
            <label for="addEmployee-office">Office</label>
            <div class="inputs">
              <select id="addEmployee-office" name="employee_office" required>
                <?php if (!empty($offices)): ?>
                  <option value="" disabled selected hidden>Select an office</option>
                  <?php foreach ($offices as $office): ?>
                    <option value="<?= htmlspecialchars($office['office_id']) ?>">
                      <?= htmlspecialchars($office['office_name']) ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="" disabled selected>No offices available</option>
                <?php endif; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Account Creds -->
        <div class="form-section">
          <div class="section-title">
            <i class="fas fa-key"></i>
            <span>Account Credentials</span>
          </div>

          <div class="inpart">
            <label for="addEmployee-username">Username</label>
            <div class="inputs">
              <input type="text" id="addEmployee-username" name="username" />
            </div>
          </div>
          
          <div class="inpart">
            <label for="addEmployee-accountType">Account Role</label>
            <div class="inputs">
              <select id="addEmployee-accountType" name="account_role" required>
                <option value="" disabled selected>Select a role</option>
                <option value="Admin">Admin</option>
                <option value="Employee">Employee</option>
              </select>
            </div>
          </div>

          <div class="inpart">
            <label for="addEmployee-password">Password</label>
            <div class="inputs">
              <input type="password" id="addEmployee-password" name="password" />
            </div>
          </div>

          <div class="inpart">
            <label for="addEmployee-confirmPassword">Confirm Password</label>
            <div class="inputs">
              <input type="password" id="addEmployee-confirmPassword" name="confirmPassword" />
            </div>
          </div>
        </div>

        <div class="btnSave">
          <button type="submit" name="submit_employee">Create Account</button>
        </div>
      </form>
    </div>
  </div>

  <script src="/javascript/addEmployee.js"></script>
</body>
</html>