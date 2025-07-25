
document.addEventListener('DOMContentLoaded', function () {
  const tableBody = document.querySelector('.positionTable tbody');
  const searchInput = document.getElementById('searchPosition');
  let debounceTimer;

  tableBody.addEventListener('click', function (e) {
    const deleteBtn = e.target.closest('.action-btn.delete');
    const editBtn = e.target.closest('.action-btn.edit');

    if (deleteBtn) {
      const positionId = deleteBtn.getAttribute('data-id');
      const positionTitle = deleteBtn.getAttribute('data-title');

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
    }

    if (editBtn) {
      const positionId = editBtn.getAttribute('data-id');
      const title = editBtn.getAttribute('data-title');
      const desc = editBtn.getAttribute('data-description');

      document.getElementById('edit-position-id').value = positionId;
      document.getElementById('edit-position-title').value = title;
      document.getElementById('edit-position-description').value = desc;

      document.getElementById('editPositionModal').style.display = 'flex';
    }
  });


  searchInput.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      const searchTerm = searchInput.value.trim();
      const searchUrl = `/templates/position/function/searchPosition.php?search=${encodeURIComponent(searchTerm)}`;

      fetch(searchUrl)
        .then(response => response.text())
        .then(html => {
          tableBody.innerHTML = html;
        })
        .catch(error => console.error('Search failed:', error));
    }, 400);
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
});


function closeEditModal() {
  document.getElementById('editPositionModal').style.display = 'none';
}
