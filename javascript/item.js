document.addEventListener('DOMContentLoaded', function () {
  const tableBody = document.querySelector('.itemTable tbody');
  const searchInput = document.getElementById('searchCategory');
  const editModal = document.getElementById('editCategory');
  const editCategoryIdInput = document.getElementById('edit-category-id');
  const editCategoryNameInput = document.getElementById('edit-category-name');
  let debounceTimer;

  if (tableBody) {
    tableBody.addEventListener('click', function (e) {
      const deleteBtn = e.target.closest('.action-btn.delete');
      const editBtn = e.target.closest('.action-btn.edit');


      if (deleteBtn) {
        const categoryId = deleteBtn.getAttribute('data-id');
        const categoryName = deleteBtn.getAttribute('data-name');

        Swal.fire({
          title: 'Are you sure?',
          text: `You are about to delete "${categoryName}".`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, delete it!',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            sessionStorage.setItem('deletedCategoryName', categoryName);
            window.location.href = `/templates/inventory/function/deleteCategory.php?id=${categoryId}`;
          }
        });
      }

   
      if (editBtn && editModal && editCategoryIdInput && editCategoryNameInput) {
        const categoryId = editBtn.getAttribute('data-id');
        const categoryName = editBtn.getAttribute('data-name');

        editCategoryIdInput.value = categoryId;
        editCategoryNameInput.value = categoryName;
        editModal.style.display = 'flex';
      }
    });
  }


  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const searchTerm = searchInput.value.trim();
        const searchUrl = `/templates/inventory/function/searchCategory.php?search=${encodeURIComponent(searchTerm)}`;

        fetch(searchUrl)
          .then(response => response.text())
          .then(html => {
            tableBody.innerHTML = html;
          })
          .catch(error => console.error('Search failed:', error));
      }, 400);
    });
  }


  const deletedName = sessionStorage.getItem('deletedCategoryName');
  if (deletedName) {
    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      html: `Category <b>${deletedName}</b> was deleted successfully.`,
      confirmButtonColor: '#3085d6'
    }).then(() => {
      const url = new URL(window.location.href);
      url.searchParams.delete('deleted');
      url.searchParams.delete('id');
      window.history.replaceState({}, document.title, url.pathname);
    });

    sessionStorage.removeItem('deletedCategoryName');
  }
});


function escEditCategory() {
  const editModal = document.getElementById('editCategory');
  if (editModal) {
    editModal.style.display = 'none';
  }
}
