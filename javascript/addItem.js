function addItem() {
  const addItem = document.getElementById("addItem");
  addItem.style.display = (addItem.style.display === 'none') ? 'flex' : 'none';
}
function escItem() {
  const addItem = document.getElementById('addItem');
  addItem.style.display = 'none';

  const form = document.getElementById('item-form');
  if (form) {
    form.reset();


    const fileInput = document.getElementById('item-photo');
    if (fileInput) fileInput.value = '';

    const photoOutput = document.getElementById('itemPreviewOutput');
    if (photoOutput) {
      photoOutput.src = '';
      photoOutput.style.display = 'none';
    }

  
    const firstSerial = document.getElementById('serial-number');
    if (firstSerial) firstSerial.value = '';

    const serialContainer = document.getElementById('serial-inputs-container');
    if (serialContainer) {
      serialContainer.innerHTML = `
        <div class="serial-entry">
          <div class="inpart">
            <label>Serial Number #2</label>
            <div class="inputs">
              <input type="text" class="modal-serial-input" placeholder="Enter serial number" />
            </div>
          </div>
        </div>
      `;
    }

    const serialList = document.getElementById('serial-numbers-list');
    if (serialList) serialList.innerHTML = '';

    const hiddenInputs = document.getElementById('hidden-serial-inputs');
    if (hiddenInputs) hiddenInputs.innerHTML = '';

    const serialBtn = document.getElementById('add-multiple-serials');
    if (serialBtn) serialBtn.textContent = 'Add More Serial Numbers';

    const indicator = document.getElementById('multiple-serial-indicator');
    if (indicator) indicator.style.display = 'none';

    const notice = document.getElementById('multiSerialNotice');
    if (notice) notice.style.display = 'none';

    const totalCost = document.getElementById('total-cost');
    if (totalCost) totalCost.value = '';


    nextSerialNumber = 3;
  }

  document.getElementById('serialModal').style.display = 'none';
  document.body.style.overflow = '';
}


let nextSerialNumber = 2;

function addMoreSerial() {
  const container = document.getElementById('serial-inputs-container');

  const newEntry = document.createElement('div');
  newEntry.className = 'serial-entry';

  newEntry.innerHTML = `
    <button type="button" class="remove-serial" onclick="removeSerial(this)">
      <i class="fas fa-times"></i>
    </button>
    <div class="inpart">
      <label>Serial Number #${nextSerialNumber}</label>
      <div class="inputs">
        <input type="text" class="modal-serial-input" placeholder="Enter serial number" />
      </div>
    </div>
  `;

  container.appendChild(newEntry);
  nextSerialNumber++;
}

function removeSerial(button) {
  const container = document.getElementById('serial-inputs-container');
  if (container.children.length > 1) {
    button.closest('.serial-entry').remove();
    updateSerialNumbers();
  }
}

function updateSerialNumbers() {
  const entries = document.querySelectorAll('#serial-inputs-container .serial-entry');
  let currentNumber = 2;
  entries.forEach(entry => {
    entry.querySelector('label').textContent = `Serial Number #${currentNumber++}`;
  });
  nextSerialNumber = currentNumber;
}

function openSerialModal() {
  const serialList = document.querySelectorAll('#serial-numbers-list .serial-tag');
  const container = document.getElementById('serial-inputs-container');
  container.innerHTML = ''; 

  let count = 2;

  if (serialList.length > 0) {
    serialList.forEach(tag => {
      const serialText = tag.textContent.trim();
      const match = serialText.match(/Serial #\d+:\s*(.*)/);
      const serialValue = match ? match[1] : '';

      const newEntry = document.createElement('div');
      newEntry.className = 'serial-entry';
      newEntry.innerHTML = `
        <button type="button" class="remove-serial" onclick="removeSerial(this)">
          <i class="fas fa-times"></i>
        </button>
        <div class="inpart">
          <label>Serial Number #${count}</label>
          <div class="inputs">
            <input type="text" class="modal-serial-input" placeholder="Enter serial number" value="${serialValue}" />
          </div>
        </div>
      `;
      container.appendChild(newEntry);
      count++;
    });
  } else {
    container.innerHTML = `
      <div class="serial-entry">
        <div class="inpart">
          <label>Serial Number #2</label>
          <div class="inputs">
            <input type="text" class="modal-serial-input" placeholder="Enter serial number" />
          </div>
        </div>
      </div>
    `;
    count = 3;
  }

  nextSerialNumber = count;
  document.getElementById('serialModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeSerialModal() {
  document.getElementById('serialModal').style.display = 'none';
  document.body.style.overflow = '';
}

document.getElementById('save-serials').addEventListener('click', function () {
  const modalInputs = document.querySelectorAll('#serial-inputs-container .modal-serial-input');
  const firstSerialInput = document.getElementById('serial-number'); // main input
  const notice = document.getElementById('multiSerialNotice');
  const serialList = document.getElementById('serial-numbers-list');
  const hiddenInputsContainer = document.getElementById('hidden-serial-inputs');
  const indicator = document.getElementById('multiple-serial-indicator');
  const addBtn = document.getElementById('add-multiple-serials');

  const modalSerials = Array.from(modalInputs)
    .map(input => input.value.trim())
    .filter(val => val !== '');

  const uniqueSerials = [...new Set(modalSerials.filter(serial => serial !== firstSerialInput.value.trim()))];

  serialList.innerHTML = '';
  hiddenInputsContainer.innerHTML = '';


  if (uniqueSerials.length > 0) {
    addBtn.textContent = 'Edit or Add More Serial Numbers';
  } else {
    addBtn.textContent = 'Add More Serial Numbers';
  }

  const totalSerials = (firstSerialInput.value.trim() ? 1 : 0) + uniqueSerials.length;

  indicator.style.display = totalSerials > 1 ? 'block' : 'none';
  notice.style.display = totalSerials > 1 ? 'block' : 'none';

  let serialNumber = 2;
  uniqueSerials.forEach(serial => {
    serialList.innerHTML += `
      <div class="serial-tag">Serial #${serialNumber++}: ${serial}</div>
    `;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'additional_serials[]';
    input.value = serial;
    hiddenInputsContainer.appendChild(input);
  });

  closeSerialModal();
});

function calculateTotalCost() {
  const unitCost = parseFloat(document.getElementById('unit-cost').value) || 0;
  const quantity = parseInt(document.getElementById('item-quantity').value) || 0;
  document.getElementById('total-cost').value = (unitCost * quantity).toFixed(2);
}

function previewItemPhoto(event) {
  const output = document.getElementById('itemPreviewOutput');
  const file = event.target.files[0];
  if (file) {
    output.src = URL.createObjectURL(file);
    output.style.display = "block";
    output.onload = () => URL.revokeObjectURL(output.src);
  } else {
    output.src = "";
    output.style.display = "none";
  }

  

  
}

function calculateEditTotalCost() {
  const qty = parseFloat(document.getElementById('edit-item-qty').value) || 0;
  const unitCost = parseFloat(document.getElementById('edit-item-unit-cost').value) || 0;
  const totalCost = qty * unitCost;
  document.getElementById('edit-item-total-cost').value = totalCost.toFixed(2);
}


document.addEventListener('DOMContentLoaded', function () {
  const unitCostInput = document.getElementById('unit-cost');
  const quantityInput = document.getElementById('item-quantity');

  if (unitCostInput) unitCostInput.addEventListener('input', calculateTotalCost);
  if (quantityInput) quantityInput.addEventListener('input', calculateTotalCost);
});

