function addOffice() {
    const addOffice = document.getElementById("addOffice");
    addOffice.style.display = (addOffice.style.display === 'none') ? 'flex' : 'none';
  }

  function escOffice() {
 
    document.getElementById("addOffice").style.display = "none";
    

    const form = document.getElementById("office-form");
    form.reset();
    
  
    const container = document.getElementById('offices-container');
    while (container.children.length > 1) {
        container.removeChild(container.lastChild);
    }
    
    officeCount = 1;
}
  
  let officeCount = 1;
      
  function addMoreOffice() {
    const container = document.getElementById('offices-container');
    const newEntry = document.createElement('div');
    newEntry.className = 'office-entry';
    newEntry.dataset.index = officeCount;
    
    newEntry.innerHTML = `
      <button type="button" class="remove-office" onclick="removeOffice(this)">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="inpart">
        <label for="office-name-${officeCount}">Office Name</label>
        <div class="inputs">
          <input type="text" id="office-name-${officeCount}" name="offices[${officeCount}][name]" placeholder="e.g., Accounting Department" required />
        </div>
      </div>
  
      <div class="inpart">
        <label for="office-description-${officeCount}">description (optional)</label>
        <div class="inputs">
          <input type="text" id="office-description-${officeCount}" name="offices[${officeCount}][description]" placeholder="e.g., Building A, 2nd Floor" />
        </div>
      </div>
    `;
    
    container.appendChild(newEntry);
    officeCount++;
  }
      
  function removeOffice(button) {
    const entry = button.closest('.office-entry');
    entry.remove();
  }
      
