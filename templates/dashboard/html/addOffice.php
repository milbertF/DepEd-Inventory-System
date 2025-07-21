<?php
include __DIR__ . '/../function/addOfficeFunction.php';
require_once __DIR__ . '/../../../config/authProtect.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- <link rel="stylesheet" href="/styles/dashboard.css"> -->
  <link rel="stylesheet" href="/styles/addOffPos.css">

</head>

<body>

  <div class="addEmployee" style="display: none;" id="addOffice">
    <div class="esc">
      <button id="btnEscOffice" onclick="escOffice()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="con">
      <h4>Add Office(s)</h4>

      <form method="POST" autocomplete="off" id="office-form">

        <div class="info">
          Note: You can add multiple offices at once.
          Fill in the first office details, then click "Add Another Office"
          to include additional offices before submitting.
        </div>

        <div id="offices-container">
          <div class="office-entry" data-index="0">
            <div class="inpart">
              <label for="office-name-0">Office Name</label>
              <div class="inputs">
                <input type="text" id="office-name-0" name="offices[0][name]" placeholder="e.g., Accounting Department" required />
              </div>
            </div>

            <div class="inpart">
              <label for="office-description-0">Description(optional)</label>
              <div class="inputs">
                <input type="text" id="office-description-0" name="offices[0][description]" placeholder="Short Description" />
              </div>
            </div>
          </div>
        </div>

        <div class="btn-group">
          <button type="button" class="add-more" onclick="addMoreOffice()">
            <i class="fas fa-plus"></i> Add Another Office
          </button>
        </div>

        <div class="btnSave">
          <button type="submit" name="submit_office" id="submitBtn">Submit All Offices</button>
        </div>
      </form>
    </div>
  </div>

  <script src="/javascript/addOffice.js"></script>
</body>

</html>