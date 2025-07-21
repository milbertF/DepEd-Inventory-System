document.addEventListener('DOMContentLoaded', function () {
  const tableBody = document.querySelector('.officeTable tbody');
  const searchInput = document.getElementById('searchOffice');
  let debounceTimer;

  tableBody.addEventListener('click', function (e) {
    const deleteBtn = e.target.closest('.action-btn.delete');
    const editBtn = e.target.closest('.action-btn.edit');

    if (deleteBtn) {
      const officeId = deleteBtn.getAttribute('data-id');
      const officeName = deleteBtn.getAttribute('data-title');

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
    }

    if (editBtn) {
      const officeId = editBtn.getAttribute('data-id');
      const name = editBtn.getAttribute('data-title');
      const description = editBtn.getAttribute('data-description');

      document.getElementById('edit-office-id').value = officeId;
      document.getElementById('edit-office-name').value = name;
      document.getElementById('edit-office-description').value = description;

      document.getElementById('editOfficeModal').style.display = 'flex';
    }
  });

  searchInput.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      const searchTerm = searchInput.value.trim();
      const searchUrl = `/templates/office/function/searchOffice.php?search=${encodeURIComponent(searchTerm)}`;

      fetch(searchUrl)
        .then(response => response.text())
        .then(html => {
          tableBody.innerHTML = html;
        })
        .catch(error => console.error('Search failed:', error));
    }, 400);
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
});

// Close edit modal
function closeEditModal() {
  document.getElementById('editOfficeModal').style.display = 'none';
}
