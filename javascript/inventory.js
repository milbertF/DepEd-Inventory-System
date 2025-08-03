document.addEventListener('DOMContentLoaded', function () {
  const tableBody = document.querySelector('.itemTable tbody');
  const searchInput = document.getElementById('searchCategory');
  const editModal = document.getElementById('editCategory');
  const editCategoryIdInput = document.getElementById('edit-category-id');
  const editCategoryNameInput = document.getElementById('edit-category-name');
  const pagination = document.querySelector('.pagination');
  let debounceTimer;

  // --- Handle Edit & Delete Actions ---
  if (tableBody) {
    tableBody.addEventListener('click', function (e) {
      const deleteBtn = e.target.closest('.action-btn.delete');
      const editBtn = e.target.closest('.action-btn.edit');

      // --- Delete Category ---
      if (deleteBtn) {
        const categoryId = deleteBtn.dataset.id;
        const categoryName = deleteBtn.dataset.name;

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

      // --- Edit Category ---
      if (editBtn && editModal && editCategoryIdInput && editCategoryNameInput) {
        const categoryId = editBtn.dataset.id;
        const categoryName = editBtn.dataset.name;

        editCategoryIdInput.value = categoryId;
        editCategoryNameInput.value = categoryName;
        editModal.style.display = 'flex';
      }
    });
  }

  // --- Live Search with Debounce & JSON Handling ---
  if (searchInput && tableBody) {
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const searchTerm = searchInput.value.trim();
        const searchUrl = `/templates/inventory/function/searchCategory.php?search=${encodeURIComponent(searchTerm)}`;

        fetch(searchUrl)
          .then(response => response.json())
          .then(data => {
            tableBody.innerHTML = data.html;

        
            if (pagination) {
              pagination.style.display = searchTerm ? 'none' : 'flex';
            }
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

// --- ESC Close Function ---
function escEditCategory() {
  const editModal = document.getElementById('editCategory');
  if (editModal) {
    editModal.style.display = 'none';
  }
}
