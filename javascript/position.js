


  document.querySelectorAll('.action-btn.delete').forEach(button => {
    button.addEventListener('click', function () {
      const positionId = this.getAttribute('data-id');
      const positionTitle = this.getAttribute('data-title');
  
      Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete "${positionTitle}".`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
      
          sessionStorage.setItem('deletedPositionTitle', positionTitle);
          window.location.href = `/templates/position/function/deletePos.php?id=${positionId}`;
        }
      });
    });
  });



  const deletedTitle = sessionStorage.getItem('deletedPositionTitle');
  if (deletedTitle) {
    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      html: `Position <b>${deletedTitle}</b> was deleted successfully.`,
      confirmButtonColor: '#3085d6'
    }).then(() => {
  
      const url = new URL(window.location.href);
      url.searchParams.delete('deleted');
      url.searchParams.delete('id');
      window.history.replaceState({}, document.title, url.pathname);
    });
  
    sessionStorage.removeItem('deletedPositionTitle');
  }
  

  document.querySelectorAll('.action-btn.edit').forEach(button => {
    button.addEventListener('click', function () {
      const positionId = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      const desc = this.getAttribute('data-description');
  
      document.getElementById('edit-position-id').value = positionId;
      document.getElementById('edit-position-title').value = title;
      document.getElementById('edit-position-description').value = desc;
  
      document.getElementById('editPositionModal').style.display = 'flex';
    });
  });
  
  function closeEditModal() {
    document.getElementById('editPositionModal').style.display = 'none';
  }


  document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('searchPosition');
  const tableRows = document.querySelectorAll('.positionTable tbody tr');

  searchInput.addEventListener('input', function () {
    const query = this.value.toLowerCase();

    tableRows.forEach(row => {
      const title = row.children[1].textContent.toLowerCase();
      const description = row.children[2].textContent.toLowerCase();
      const matches = title.includes(query) || description.includes(query);
      row.style.display = matches ? '' : 'none';
    });
  });
});