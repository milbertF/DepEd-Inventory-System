
document.querySelectorAll('.action-btn.delete').forEach(button => {
    button.addEventListener('click', function () {
      const officeId = this.getAttribute('data-id');
      const officeName = this.getAttribute('data-title');
  
      Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete "${officeName}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          sessionStorage.setItem('deletedOfficeName', officeName);
          window.location.href = `/templates/office/function/deleteOff.php?id=${officeId}`;
        }
      });
    });
  });
  

  const deletedOfficeName = sessionStorage.getItem('deletedOfficeName');
  if (deletedOfficeName) {
    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      html: `Office <b>${deletedOfficeName}</b> was deleted successfully.`,
      confirmButtonColor: '#3085d6'
    }).then(() => {
      const url = new URL(window.location.href);
      url.searchParams.delete('deleted');
      url.searchParams.delete('id');
      window.history.replaceState({}, document.title, url.pathname);
    });
  
    sessionStorage.removeItem('deletedOfficeName');
  }
  

  document.querySelectorAll('.action-btn.edit').forEach(button => {
    button.addEventListener('click', function () {
      const officeId = this.getAttribute('data-id');
      const name = this.getAttribute('data-title');
      const location = this.getAttribute('data-description');
  
      document.getElementById('edit-office-id').value = officeId;
      document.getElementById('edit-office-name').value = name;
      document.getElementById('edit-office-location').value = location;
  
      document.getElementById('editOfficeModal').style.display = 'flex';
    });
  });
  
  function closeEditModal() {
    document.getElementById('editOfficeModal').style.display = 'none';
  }
  

  document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchOffice');
    const tableRows = document.querySelectorAll('.officeTable tbody tr');
  
    searchInput.addEventListener('input', function () {
      const query = this.value.toLowerCase();
  
      tableRows.forEach(row => {
        const name = row.children[1].textContent.toLowerCase();
        const location = row.children[2].textContent.toLowerCase();
        const matches = name.includes(query) || location.includes(query);
        row.style.display = matches ? '' : 'none';
      });
    });
  });
  