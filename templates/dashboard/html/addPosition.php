<?php
require __DIR__ . '/../function/addPositionFunction.php';
require_once __DIR__ . '/../../../config/authProtect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add Position</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="/styles/dashboard.css">
  <link rel="stylesheet" href="/styles/addOffPos.css">
  <style>
   
  </style>
</head>
<body>

  <div class="addEmployee" id="addPosition" style="display: none;">
    <div class="esc">
      <button id="btnEscPosition" onclick="escPosition()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="con">
      <h4>Add Position(s)</h4>
      
      <div class="info">
        Note: You can add multiple positions at once. 
        Fill in the first position details, then click "Add Another Position" 
        to include additional positions before submitting.
      </div>

      <form method="POST" autocomplete="off" id="position-form">
        <div id="positions-container">
          <div class="position-entry" data-index="0">
            <div class="inpart">
              <label for="position-title-0">Position Title</label>
              <div class="inputs">
                <input type="text" id="position-title-0" name="positions[0][title]" placeholder="e.g., Administrative Officer" required />
              </div>
            </div>

            <div class="inpart">
              <label for="position-description-0">Description (optional)</label>
              <div class="inputs">
                <input type="text" id="position-description-0" name="positions[0][description]" placeholder="Short description..." />
              </div>
            </div>
          </div>
        </div>

        <div class="btn-group">
          <button type="button" class="add-more" onclick="addMorePosition()">
            <i class="fas fa-plus"></i> Add Another Position
          </button>
        </div>

        <div class="btnSave">
          <button type="submit" name="submit_position" id="submitPositionBtn">Submit All Positions</button>
        </div>
      </form>
    </div>
  </div>



  <script src="/javascript/addPosition.js"></script>
</body>
</html>