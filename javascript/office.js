document.addEventListener('DOMContentLoaded', function () {
  const tableBody = document.getElementById("officeTableBody");
  const pagination = document.getElementById("pagination");
  const searchInput = document.getElementById("searchOffice");
  const rowsPerPageSelect = document.getElementById("rowsPerPageSelect");
  
  // Change from const to let so we can update it
  let rowsPerPage = parseInt(localStorage.getItem('officeTableRowsPerPage')) || 10;
  let allRows = Array.from(tableBody.querySelectorAll("tr")).filter(row => {
    const text = row.textContent.toLowerCase().trim();
    return text && !text.includes("no offices found");
  });
  
  let filteredRows = [...allRows];
  let currentPage = 1;
  let debounceTimer = null;
  let filterCache = new Map();

  // Initialize
  initializeTable();

  //  event listener for rows per page change
  if (rowsPerPageSelect) {
    rowsPerPageSelect.addEventListener('change', function() {
      rowsPerPage = parseInt(this.value);
      currentPage = 1; // Reset to first page when changing rows per page
      displayPage(currentPage);
      updateItemCounts();
      
      // Save preference to localStorage
      localStorage.setItem('officeTableRowsPerPage', this.value);
    });
    
    // Load saved preference
    const savedRowsPerPage = localStorage.getItem('officeTableRowsPerPage');
    if (savedRowsPerPage) {
      rowsPerPageSelect.value = savedRowsPerPage;
      rowsPerPage = parseInt(savedRowsPerPage);
    }
    
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
    updatePageInfo(); //show current page info
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
      if (pagination) {
        pagination.style.display = "none";
        pagination.innerHTML = "";
      }
      return;
    }
    
    if (pagination) {
      pagination.style.display = "flex";
      
      const currentHTML = pagination.innerHTML;
      const newHTML = generatePaginationHTML(page, totalPages);
      
      if (currentHTML !== newHTML) {
        pagination.innerHTML = newHTML;
        attachPaginationEvents(page, totalPages);
      }
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
    if (!pagination) return;
    
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
      if (cell && cell.textContent !== (i + 1).toString()) {
        cell.textContent = i + 1;
      }
    }
  }

  function searchTable(query) {
    const filterKey = query.toLowerCase().trim();
    
    // Check cache first
    if (filterCache.has(filterKey)) {
      filteredRows = filterCache.get(filterKey);
      updateDisplay();
      return;
    }

    filteredRows = allRows.filter(row => {
      const data = row._data;
      return data.name.includes(filterKey) || data.description.includes(filterKey);
    });

    // Update cache
    if (filterCache.size > 20) filterCache.clear();
    filterCache.set(filterKey, filteredRows);
    
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
      const colSpan = document.querySelector('.officeTable thead tr').cells.length;
      noResultsRow.innerHTML = `<td colspan="${colSpan}" style="text-align:center; color:#666; padding:20px;">No offices match your search.</td>`;
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
                filterDescription = `offices for search value "${searchTerm}"`;
            } else {
                filterDescription = `matching offices`;
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

  // Table actions (delete and edit)
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

  // Check for deleted item confirmation
  const deletedOfficeName = sessionStorage.getItem('deletedOfficeName');
  if (deletedOfficeName) {
    Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      html: `Office <b>${deletedOfficeName}</b> was deleted successfully.`,
      confirmButtonColor: '#3085d6'
    }).then(() => {
      const url = new URL(window.location.href);
      window.history.replaceState({}, document.title, url.pathname);
    });

    sessionStorage.removeItem('deletedOfficeName');
  }
});

function closeEditModal() {
  document.getElementById('editOfficeModal').style.display = 'none';
}