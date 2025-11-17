document.addEventListener('DOMContentLoaded', function () {
  const tableBody = document.getElementById('categoryTableBody');
  const searchInput = document.getElementById('searchCategory');
  const pagination = document.getElementById('pagination');
  const editModal = document.getElementById('editCategory');
  const editCategoryIdInput = document.getElementById('edit-category-id');
  const editCategoryNameInput = document.getElementById('edit-category-name');
  const rowsPerPageSelect = document.getElementById("rowsPerPageSelect");

  let allRows = Array.from(tableBody.querySelectorAll('tr'))
  .filter(row => !row.textContent.includes('No categories found.'));

  let filteredRows = [...allRows];
  let currentPage = 1;
  let rowsPerPage = parseInt(localStorage.getItem('categoryTableRowsPerPage')) || 10; 
  let debounceTimer = null;

  // Initialize
  initializeTable();

  // event listener for rows per page change
  if (rowsPerPageSelect) {
    // Load saved preference FIRST, before setting up event listener
    const savedRowsPerPage = localStorage.getItem('categoryTableRowsPerPage');
    if (savedRowsPerPage) {
      rowsPerPageSelect.value = savedRowsPerPage;
      rowsPerPage = parseInt(savedRowsPerPage);
    }
    
    // Then set up the event listener
    rowsPerPageSelect.addEventListener('change', function() {
      rowsPerPage = parseInt(this.value);
      currentPage = 1; // Reset to first page when changing rows per page
      displayPage(currentPage);
      updateItemCounts();
      
      // Save preference to localStorage
      localStorage.setItem('categoryTableRowsPerPage', this.value);
    });
  }

  function initializeTable() {
    // Pre-calculate row data for faster filtering
    allRows.forEach((row, index) => {
      row._data = {
        name: (row.cells[1]?.textContent || '').toLowerCase().trim(),
        description: (row.cells[2]?.textContent || '').toLowerCase().trim()
      };
    });
    
    displayPage(1);
    updateItemCounts();
  }

  function displayPage(page = 1) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    // Hide all rows first (more efficient)
    allRows.forEach(row => row.style.display = "none");
    
    // Show only current page rows
    for (let i = start; i < Math.min(end, filteredRows.length); i++) {
      filteredRows[i].style.display = "";
    }

    updatePagination(page);
    updateRowNumbers();
    updatePageInfo();
  }

  // function to show current page information
  function updatePageInfo() {
    const startIndex = (currentPage - 1) * rowsPerPage + 1;
    const endIndex = Math.min(currentPage * rowsPerPage, filteredRows.length);
    const totalItems = filteredRows.length;
    
    // Update page info display if element exists
    const pageInfoElement = document.getElementById('pageInfo');
    if (pageInfoElement) {
      if (totalItems > 0) {
        pageInfoElement.textContent = `Showing ${startIndex} to ${endIndex} of ${totalItems} entries`;
      } else {
        pageInfoElement.textContent = 'No entries to show';
      }
    }
  }

  function updatePagination(page) {
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    
    if (totalPages <= 1) {
      pagination.style.display = "none";
      pagination.innerHTML = "";
      return;
    }
    
    pagination.style.display = "flex";
    
    const currentHTML = pagination.innerHTML;
    const newHTML = generatePaginationHTML(page, totalPages);
    
    if (currentHTML !== newHTML) {
      pagination.innerHTML = newHTML;
      attachPaginationEvents(page, totalPages);
    }
  }

  function generatePaginationHTML(page, totalPages) {
    let html = '';
    const range = 2;
    
    // First page button (<<) - always show if not on first page
    if (page > 1) {
        html += `<a href="#" class="prev-next" data-page="1" title="First page"><i class="fas fa-angle-double-left"></i></a>`;
    }
    
    // Previous button (<)
    if (page > 1) {
        html += `<a href="#" class="prev-next" data-page="${page - 1}" title="Previous page"><i class="fas fa-chevron-left"></i></a>`;
    }
    
    // Page numbers - always show current page and surrounding pages
    for (let i = Math.max(1, page - range); i <= Math.min(totalPages, page + range); i++) {
        const activeClass = i === page ? "active" : "";
        html += `<a href="#" class="${activeClass}" data-page="${i}">${i}</a>`;
    }
    
    // Next button (>)
    if (page < totalPages) {
        html += `<a href="#" class="prev-next" data-page="${page + 1}" title="Next page"><i class="fas fa-chevron-right"></i></a>`;
    }
    
    // Last page button (>>) - always show if not on last page
    if (page < totalPages) {
        html += `<a href="#" class="prev-next" data-page="${totalPages}" title="Last page"><i class="fas fa-angle-double-right"></i></a>`;
    }
    
    return html;
  }

  function attachPaginationEvents(page, totalPages) {
    pagination.querySelectorAll('a[data-page]').forEach(link => {
      link.addEventListener("click", function(e) {
        e.preventDefault();
        const newPage = parseInt(this.getAttribute('data-page'));
        if (newPage >= 1 && newPage <= totalPages) {
          currentPage = newPage;
          displayPage(currentPage);
        }
      });
    });
  }

  function updateRowNumbers() {
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, filteredRows.length);
    
    for (let i = startIndex; i < endIndex; i++) {
      const row = filteredRows[i];
      const cell = row.cells[0];
      if (cell.textContent !== (i + 1).toString()) {
        cell.textContent = i + 1;
      }
    }
  }

  function searchTable(query) {
    const filterKey = query.toLowerCase().trim();

    filteredRows = allRows.filter(row => {
      const data = row._data;
      return data.name.includes(filterKey) || data.description.includes(filterKey);
    });
    
    updateDisplay();
  }

  function updateDisplay() {
    showNoResultsMessage(filteredRows.length === 0);
    currentPage = 1;
    displayPage(currentPage);
    updateItemCounts();
  }

  function showNoResultsMessage(show) {
    let noResultsRow = document.getElementById('noResultsMessage');
    
    if (show && !noResultsRow) {
      noResultsRow = document.createElement("tr");
      noResultsRow.id = 'noResultsMessage';
      noResultsRow.innerHTML = `<td colspan="4" style="text-align:center; color:#666; padding:20px;">No categories match your search.</td>`;
      tableBody.appendChild(noResultsRow);
    } else if (!show && noResultsRow) {
      noResultsRow.remove();
    }
  }

  // Optimized search with debouncing
  searchInput.addEventListener("input", () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      searchTable(searchInput.value);
    }, 200);
  });

  function updateItemCounts() {
    const totalItems = allRows.length;
    const filteredItems = filteredRows.length;
    const isFiltered = filteredItems !== totalItems;

    // Show total items count
    updateCountElement('totalItemsCount', totalItems);
    updateCountElement('totalItemsCount2', totalItems);
    updateCountElement('totalCount', totalItems);
    updateCountElement('totalItems', totalItems);

    // Show filtered items count with pipe separator (only when filtered)
    const filteredCountElement = document.getElementById('filteredItemsCount');
    if (filteredCountElement) {
        if (isFiltered) {
            const hasSearchFilter = searchInput?.value || '';
            
            let filterDescription = '';
            
            // Get search term
            const searchTerm = searchInput?.value || '';
            
            // Handle search filter
            if (hasSearchFilter) {
                filterDescription = `categories for search value "${searchTerm}"`;
            } else {
                filterDescription = `matching categories`;
            }
            
            filteredCountElement.innerHTML = `| ${filteredItems} ${filterDescription}`;
            filteredCountElement.style.display = 'inline';
        } else {
            filteredCountElement.style.display = 'none';
        }
    }

    updateActiveFiltersDisplay();
    updatePageInfo();
}
  function updateCountElement(elementId, count) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = count;
    }
  }

  function updateActiveFiltersDisplay() {
    const activeFiltersElement = document.getElementById('activeFilters');
    if (!activeFiltersElement) return;
    
    const activeFilters = [];
    
    // Check search
    const searchValue = searchInput?.value || '';
    if (searchValue) {
        activeFilters.push(`Search: "${searchValue}"`);
    }
    
    // Update display
    if (activeFilters.length > 0) {
        activeFiltersElement.innerHTML = `<strong>Active filters:</strong> ${activeFilters.join(' • ')}`;
        activeFiltersElement.style.color = '#495057';
    } else {
        activeFiltersElement.innerHTML = 'No active filters';
        activeFiltersElement.style.color = '#6c757d';
    }
  }

  // Table actions 
  tableBody.addEventListener('click', function (e) {
    const deleteBtn = e.target.closest('.action-btn.delete');
    const editBtn = e.target.closest('.action-btn.edit');

    if (deleteBtn) {
      const categoryId = deleteBtn.getAttribute('data-id');
      const categoryName = deleteBtn.getAttribute('data-name');

      Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete "${categoryName}". All items under this category will also be permanently deleted and cannot be recovered.`,
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

  // Check for deleted item confirmation
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
  if (editModal) editModal.style.display = 'none';
}