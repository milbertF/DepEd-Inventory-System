function addItem() {
    const addItem = document.getElementById("addItem");
    addItem.style.display = (addItem.style.display === 'none') ? 'flex' : 'none';
  }

  function escItem() {
    const addItem = document.getElementById("addItem");
    addItem.style.display = "none";
  

    const form = addItem.querySelector('form');
    if (form) {
      form.reset();
    }
  

    const output = document.getElementById('itemPhotoOutput');
    if (output) {
      output.src = '';
      output.style.display = 'none';
    }
  }
  

  function previewItemPhoto(event) {
    const output = document.getElementById('itemPhotoOutput');
    output.src = URL.createObjectURL(event.target.files[0]);
    output.style.display = "block";
    output.onload = () => URL.revokeObjectURL(output.src);
  }


  document.getElementById('unit-cost').addEventListener('input', calculateTotalCost);
  document.getElementById('item-quantity').addEventListener('input', calculateTotalCost);

  function calculateTotalCost() {
    const qty = parseFloat(document.getElementById('item-quantity').value) || 0;
    const unit = parseFloat(document.getElementById('unit-cost').value) || 0;
    document.getElementById('total-cost').value = (qty * unit).toFixed(2);
  }



  let serialCount = 1; // Tracks modal entries only (starts counting from 1)

  function addMoreSerial() {
    const container = document.getElementById('serial-inputs-container');
    serialCount++;
    
    const newEntry = document.createElement('div');
    newEntry.className = 'serial-entry';
    
    newEntry.innerHTML = `
      <button type="button" class="remove-serial" onclick="removeSerial(this)">
        <i class="fas fa-times"></i>
      </button>
      <div class="inpart">
        <label>Serial Number #${serialCount + 1}</label> <!-- +1 to account for main form input -->
        <div class="inputs">
          <input type="text" name="modal_serials[${serialCount}]" placeholder="Enter serial number" required>
        </div>
      </div>
    `;
    
    container.appendChild(newEntry);
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
    entries.forEach((entry, index) => {
      // Start numbering from 2 (since 1 is the main form input)
      entry.querySelector('label').textContent = `Serial Number #${index + 2}`;
      entry.querySelector('input').name = `modal_serials[${index + 1}]`;
    });
    serialCount = entries.length;
  }
  
  // Modified save function to combine both serials
  document.getElementById('save-serials').addEventListener('click', function() {
    // Get the main form serial
    const mainSerial = document.getElementById('serial-number').value.trim();
    
    // Get all modal serials
    const modalInputs = document.querySelectorAll('#serial-inputs-container input');
    const modalSerials = Array.from(modalInputs).map(input => input.value.trim()).filter(val => val);
    
    // Combine all serials (main + modal)
    const allSerials = [mainSerial, ...modalSerials];
    
    // Update the display list
    const serialList = document.getElementById('serial-numbers-list');
    serialList.innerHTML = allSerials.map((serial, index) => `
      <div class="serial-tag">
        Serial #${index + 1}: ${serial}
        <button type="button" onclick="this.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `).join('');
    
    // Close the modal
    closeSerialModal();
  });


function openSerialModal() {
  const serialModal = document.getElementById('serialModal');
  if (serialModal) {
    serialModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
}

function closeSerialModal() {
  const serialModal = document.getElementById('serialModal');
  if (serialModal) {
    serialModal.style.display = 'none';
    document.body.style.overflow = '';
  }
}