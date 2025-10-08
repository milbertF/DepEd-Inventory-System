<?php
require __DIR__ . '/../function/fetchEmployees.php';
require __DIR__ . '/../function/fetchItemsByCategory.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="/styles/addOffPos.css" />
  <link rel="stylesheet" href="/styles/addRequest.css" />
  <link rel="stylesheet" href="/styles/addRequestItemModal.css" />
</head>
<body>
<div class="addEmployee" style="display: none;" id="addRequest">
  <div class="esc" style="justify-content: flex-end; margin-left:250px">
    <button id="btnEsc" onclick="escRequest()"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <div class="con" style="width:40rem;">
    <h4>Request Item</h4>

    <form method="POST" enctype="multipart/form-data" id="request-form" autocomplete="off">
      <!-- ================= DIRECTLY START AT STAGE 2 ================= -->
      <div class="stage active" id="stage-2">
        <div class="btnSave">
          <button type="button" style="font-weight:400;" onclick="openItemModal()">
            <i class="fa-solid fa-box"></i> Choose Item
          </button>
        </div>

        <h5 style="margin-top:10px">Requested Items</h5>
        <table class="request-table" id="request-items-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Brand</th>
              <th>Model</th>
              <th>Quantity</th>
              <th>Date Needed</th>
              <th>Remove</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>

        <div id="hidden-items"></div>
        <div class="btnSave">
          <button type="submit" name="submit_request">Submit Request</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!--  ITEM MODAL -->
<?php require __DIR__ . '/addRequestItemModal.php'; ?>

<script src="/javascript/addRequest.js"></script>
<script>

const allItems = <?= json_encode($items) ?>;



</script>
</body>
</html>
