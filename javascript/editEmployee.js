document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.action-btn.edit').forEach(button => {
      button.addEventListener('click', function () {
        const infoId = this.getAttribute('data-id');
        const userId = this.getAttribute('data-user-id'); 
        const role = button.getAttribute('data-role'); 
        const photoPath = this.getAttribute('data-photo')?.trim() || '/images/user-profile/default-image.jpg';
        const address = this.getAttribute('data-address') || '';
  
        const row = this.closest('tr');
        const fullNameParts = row.cells[2].innerText.trim().split(' ');
        const position = row.cells[3].innerText.trim();
        const office = row.cells[4].innerText.trim();
   
        const contact = row.cells[6].innerText.trim(); 

  
        document.getElementById('edit-info-id').value = infoId;
        document.getElementById('edit-user-id').value = userId;
        document.getElementById('edit-accountType').value = role || "";
        document.getElementById('edit-firstName').value = fullNameParts[0] || '';
        document.getElementById('edit-middleName').value = fullNameParts.length === 3 ? fullNameParts[1] : '';
        document.getElementById('edit-lastName').value = fullNameParts[fullNameParts.length - 1] || '';
        document.getElementById('edit-contact').value = contact;
        document.getElementById('edit-address').value = address;
  
        const photoOutput = document.getElementById('edit-photoOutput');
        photoOutput.src = photoPath;
        photoOutput.onerror = function () {
          this.src = '/images/user-profile/default-image.jpg';
        };
  
        const positionSelect = document.getElementById('edit-position');
        Array.from(positionSelect.options).forEach(opt => {
          opt.selected = (opt.value === this.getAttribute('data-position-id') || opt.textContent.trim() === position);
        });
  
        const officeSelect = document.getElementById('edit-office');
        Array.from(officeSelect.options).forEach(opt => {
          opt.selected = (opt.value === this.getAttribute('data-office-id') || opt.textContent.trim() === office);
        });
  
        document.getElementById('editEmployee').style.display = 'flex';
      });
    });
  });
  

  function escEditEmployee() {
    document.getElementById('edit-employee-form').reset();
    document.getElementById('editEmployee').style.display = 'none';
  }
  

  function previewEditPhoto(event) {
    const file = event.target.files[0];
    const preview = document.getElementById("edit-photoOutput");
    if (file) {
      preview.src = URL.createObjectURL(file);
      preview.style.display = "block";
    } else {
      preview.src = "";
      preview.style.display = "none";
    }
  }
  