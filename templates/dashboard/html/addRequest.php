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
<div class="addEmployee" style="display: none;" id="addRequest" >
  <div class="esc" style="  justify-content: flex-end;">
    <button id="btnEsc" onclick="escRequest()"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <div class="con" style="width:40rem;">

    <h4>Request Item</h4>
    <form method="POST" enctype="multipart/form-data" id="request-form" autocomplete="off">
      
    
      <div class="stage active" id="stage-1">
        <div class="inpart">
          <label for="requester-id">Requester <span>*</span></label>
          <div class="inputs">
            <select id="requester-id" name="requester_id" required onchange="fillRequesterDetails(this)">
              <option value="" disabled selected>Select requester</option>
              <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['info_id'] ?>"
                        data-first="<?= htmlspecialchars($emp['first_name']) ?>"
                        data-middle="<?= htmlspecialchars($emp['middle_name']) ?>"
                        data-last="<?= htmlspecialchars($emp['last_name']) ?>"
                        data-position="<?= htmlspecialchars($emp['position_title']) ?>"
                        data-office="<?= htmlspecialchars($emp['office_name']) ?>">
                  <?= htmlspecialchars($emp['full_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="inpart"><label>First Name</label><div class="inputs"><input type="text" id="requester-fname" readonly /></div></div>
        <div class="inpart"><label>Middle Name</label><div class="inputs"><input type="text" id="requester-mname" readonly /></div></div>
        <div class="inpart"><label>Last Name</label><div class="inputs"><input type="text" id="requester-lname" readonly /></div></div>
        <div class="inpart"><label>Position</label><div class="inputs"><input type="text" id="requester-position" readonly /></div></div>
        <div class="inpart"><label>Office</label><div class="inputs"><input type="text" id="requester-office" readonly /></div></div>
        <div class="btnSave"><button type="button" onclick="nextStage(1)">Next</button></div>
      </div>

      <!-- ================= STAGE 2: Items ================= -->
      <div class="stage" id="stage-2">

      <div class="btnSave">
  <button type="button" style="font-weight:400;" onclick="openItemModal()">
    <i class="fa-solid fa-box"></i>  Choose Item
  </button>
</div>

        <h5 style ="margin-top:10px">Requested Items</h5>
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
          <button style="margin-right:20px;" type="button" onclick="prevStage(2)">Back</button>
          <button type="submit" name="submit_request">Submit Request</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- ================= ITEM MODAL ================= -->

<?php require __DIR__ . '/addRequestItemModal.php'; ?>


<script src="/javascript/addRequest.js"></script>
<script>
// preload PHP items into JS
const allItems = <?= json_encode($items) ?>;

function fillRequesterDetails(select) {
  const selected = select.options[select.selectedIndex];
  if (!selected) return;
  document.getElementById('requester-fname').value = selected.getAttribute('data-first') || '';
  document.getElementById('requester-mname').value = selected.getAttribute('data-middle') || '';
  document.getElementById('requester-lname').value = selected.getAttribute('data-last') || '';
  document.getElementById('requester-position').value = selected.getAttribute('data-position') || '';
  document.getElementById('requester-office').value = selected.getAttribute('data-office') || '';
}

function nextStage(c){document.getElementById('stage-'+c).classList.remove('active');document.getElementById('stage-'+(c+1)).classList.add('active');}
function prevStage(c){document.getElementById('stage-'+c).classList.remove('active');document.getElementById('stage-'+(c-1)).classList.add('active');}

function openItemModal(){document.getElementById('itemModal').style.display='flex';}
function closeItemModal(){document.getElementById('itemModal').style.display='none';}

function filterItemsByCategory(catId){
  const tbody=document.getElementById('itemTableBody');
  tbody.innerHTML='';
  if(!catId) return;

  const filtered = allItems.filter(it => it.category_id == catId);
  filtered.forEach(item=>{
    tbody.innerHTML += `
      <tr>
        <td>${item.serial_number||'-'}</td>
        <td>${item.item_name}</td>
        <td>${item.description||'-'}</td>
        <td>${item.brand||'-'}</td>
        <td>${item.model||'-'}</td>
        <td>${item.quantity}</td>
        <td><button type="button" onclick="selectItem(${item.item_id}, '${item.item_name}', '${item.brand||''}', '${item.model||''}', ${item.quantity})">Select</button></td>
      </tr>`;
  });
}

function selectItem(id, name, brand, model, qty) {
  const dateNeeded = new Date().toISOString().split('T')[0];
  const tbody = document.querySelector('#request-items-table tbody');

  const row = document.createElement('tr');
  row.innerHTML = `
    <td data-label="Item">${name}<input type="hidden" name="item_id[]" value="${id}"></td>
    <td data-label="Brand">${brand || '-'}</td>
    <td data-label="Model">${model || '-'}</td>
    <td data-label="Quantity"><input type="number" name="quantity_requested[]" min="1" max="${qty}" required></td>
    <td data-label="Date Needed"><input type="date" name="date_needed[]" value="${dateNeeded}"></td>
    <td data-label="Remove"><button type="button" onclick="removeRow(this)">X</button></td>
  `;

  tbody.appendChild(row);
  closeItemModal();
}


function removeRow(btn){btn.closest('tr').remove();}
</script>
</body>
</html>
