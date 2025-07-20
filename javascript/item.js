

  document.querySelectorAll('.action-btn.delete').forEach(button => {
    button.addEventListener('click', function () {
      const categoryId = this.getAttribute('data-id');
      const categoryName = this.getAttribute('data-name');

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
    });
  });


  const deletedCategoryName = sessionStorage.getItem('deletedCategoryName');
  if (deletedCategoryName) {
    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      html: `Category <b>${deletedCategoryName}</b> was deleted successfully.`,
      confirmButtonColor: '#3085d6'
    }).then(() => {
      const url = new URL(window.location.href);
      url.searchParams.delete('deleted');
      url.searchParams.delete('id');
      window.history.replaceState({}, document.title, url.pathname);
    });

    sessionStorage.removeItem('deletedCategoryName');
  }




document.querySelectorAll('.action-btn.edit').forEach(button => {
    button.addEventListener('click', function () {
      const categoryId = this.getAttribute('data-id');
      const categoryTitle = this.getAttribute('data-title');
  
      document.getElementById('edit-category-id').value = categoryId;
      document.getElementById('edit-category-name').value = categoryTitle;
  
      document.getElementById('editCategory').style.display = 'flex';
    });
  });
  
  

  function escEditCategory() {
    document.getElementById('editCategory').style.display = 'none';
  }
  


  document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchCategory');
    const tableRows = document.querySelectorAll('.itemTable tbody tr');
  
    searchInput.addEventListener('input', function () {
      const query = this.value.toLowerCase();
  
      tableRows.forEach(row => {
        const categoryName = row.children[1]?.textContent.toLowerCase() || '';
        const dateAdded = row.children[2]?.textContent.toLowerCase() || '';
        const matches = categoryName.includes(query) || dateAdded.includes(query);
        row.style.display = matches ? '' : 'none';
      });
    });
  });
  