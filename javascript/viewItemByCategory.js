let currentFilters = {};

document.addEventListener('DOMContentLoaded', function() {
  initFilterControls();
  initTableActions();
  initSearchFunctionality();
  calculateEditTotalCost();
  initDateValidation();
  checkForDeletedItem();
 
});

document.addEventListener('click', function(e) {
    const link = e.target.closest('.pagination a[data-page]');
    if (link) {
      e.preventDefault();
      const page = parseInt(link.dataset.page);
      if (!isNaN(page)) {
        const searchTerm = document.getElementById("searchItem")?.value.trim();
        if (searchTerm) {
          fetchSearchResults(searchTerm, page);
        } else {
          fetchFilteredItems(currentFilters, page);
        }
      }
    }
  });
  


  

// ---------------------
// FILTERS INIT
// ---------------------
function initFilterControls() {
  const brandToggle = document.getElementById("toggleBrandFilter");
  const brandFilter = document.getElementById("brandFilterContainer");
  const qtyToggle = document.getElementById("toggleQtyFilter");
  const qtyFilter = document.getElementById("quantityFilterContainer");
  const dateToggle = document.getElementById("toggleDateFilter");
  const dateFilter = document.getElementById("dateFilterContainer");

  function closeAllFilters() {
    [brandFilter, qtyFilter, dateFilter].forEach(filter => filter.classList.add("hidden"));
  }

  [brandToggle, qtyToggle, dateToggle].forEach(toggle => {
    toggle?.addEventListener("click", function(e) {
      e.stopPropagation();
      const filter = this.id === "toggleBrandFilter" ? brandFilter :
                   this.id === "toggleQtyFilter" ? qtyFilter : dateFilter;

      if (filter.classList.contains("hidden")) {
        closeAllFilters();
        filter.classList.remove("hidden");
      } else {
        filter.classList.add("hidden");
      }
    });
  });

  document.addEventListener('click', closeAllFilters);
  [brandFilter, qtyFilter, dateFilter].forEach(filter => filter.addEventListener('click', e => e.stopPropagation()));

  document.getElementById("sortLowToHigh")?.addEventListener("click", () => {
    qtyFilter.classList.add("hidden");
    fetchFilteredItems({ sort_quantity: 'asc' });
  });

  document.getElementById("sortHighToLow")?.addEventListener("click", () => {
    qtyFilter.classList.add("hidden");
    fetchFilteredItems({ sort_quantity: 'desc' });
  });

  document.getElementById("showOutOfStock")?.addEventListener("click", () => {
    qtyFilter.classList.add("hidden");
    fetchFilteredItems({ out_of_stock: true });
  });

  document.getElementById("filterByBrandBtn")?.addEventListener("click", () => {
    const selectedBrands = Array.from(document.getElementById("brandSelect").selectedOptions).map(opt => opt.value);
    brandFilter.classList.add("hidden");
    fetchFilteredItems({ brands: selectedBrands });
  });

  document.getElementById("resetBrandFilterBtn")?.addEventListener("click", () => {
    document.getElementById("brandSelect").selectedIndex = -1;
    brandFilter.classList.add("hidden");
    fetchFilteredItems(); // reset
  });

  document.getElementById("filterByDateBtn")?.addEventListener("click", () => {
    const dateFrom = document.getElementById("dateFrom").value;
    const dateTo = document.getElementById("dateTo").value;
    dateFilter.classList.add("hidden");
    fetchFilteredItems({ date_from: dateFrom, date_to: dateTo });
  });

  document.getElementById("resetDateFilterBtn")?.addEventListener("click", () => {
    document.getElementById("dateFrom").value = "";
    document.getElementById("dateTo").value = "";
    dateFilter.classList.add("hidden");
    fetchFilteredItems(); // reset
  });
}

// ---------------------
// FETCH FILTERED ITEMS
// ---------------------
function fetchFilteredItems(filters = {}, page = 1) {
    currentFilters = filters;
    const categoryId = document.getElementById("categoryId").value;
    const tableBody = document.querySelector(".itemTable tbody");
    const pagination = document.querySelector(".pagination");
  
    const params = new URLSearchParams({ category_id: categoryId, page });
    if (filters.brands) filters.brands.forEach(b => params.append('brands[]', b));
    if (filters.sort_quantity) params.append('sort_quantity', filters.sort_quantity);
    if (filters.out_of_stock) params.append('out_of_stock', 1);
    if (filters.date_from) params.append('date_from', filters.date_from);
    if (filters.date_to) params.append('date_to', filters.date_to);
  
    fetch(`/templates/inventory/function/filterItems.php?${params.toString()}`)
      .then(res => {
        if (!res.ok) throw new Error(`Server returned ${res.status}`);
        return res.json();
      })
      .then(data => {
        tableBody.innerHTML = data.html;
        if (pagination) {
          pagination.style.display = data.showPagination ? 'flex' : 'none';
          pagination.innerHTML = generatePagination(data.currentPage, data.totalPages);
          
          // Add event listeners to the new pagination buttons
          document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
              e.preventDefault();
              const page = parseInt(this.dataset.page);
              if (!isNaN(page)) {
                fetchFilteredItems(currentFilters, page);
              }
            });
          });
        }
      })
      .catch(error => {
        console.error("Fetch Filter Error:", error);
        tableBody.innerHTML = `<tr><td colspan="10">Error loading data. Check console.</td></tr>`;
      });
  }
  

  function generatePagination(currentPage, totalPages, currentParams = {}) {
    let html = '';
  
    const buildQueryString = (params) => {
      return Object.entries(params)
        .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
        .join('&');
    };
  
    const createPageLink = (page, label = null, active = false) => {
      const updatedParams = { ...currentParams, page };
      const queryString = buildQueryString(updatedParams);
      const href = `?${queryString}`;
      return `<a href="#" class="${active ? 'active' : ''}" data-page="${page}">${label || page}</a>`;
    };
  
    if (totalPages <= 1) return html;
  
    // Chevron Left (Previous)
    if (currentPage > 1) {
      html += createPageLink(currentPage - 1, '<i class="fas fa-chevron-left"></i>');
    } else {
      html += `<a class="prev-next disabled"><i class="fas fa-chevron-left"></i></a>`;
    }
  
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);
  
    if (start > 1) {
      html += createPageLink(1);
      if (start > 2) html += '<span class="page-dots">...</span>';
    }
  
    for (let i = start; i <= end; i++) {
      html += createPageLink(i, null, i === currentPage);
    }
  
    if (end < totalPages) {
      if (end < totalPages - 1) html += '<span class="page-dots">...</span>';
      html += createPageLink(totalPages);
    }
  
    // Chevron Right (Next)
    if (currentPage < totalPages) {
      html += createPageLink(currentPage + 1, '<i class="fas fa-chevron-right"></i>');
    } else {
      html += `<a class="prev-next disabled"><i class="fas fa-chevron-right"></i></a>`;
    }
  
    return html;
  }
  


function initTableActions() {
  document.querySelector('.itemTable')?.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('.action-btn.delete');
    if (deleteBtn) return handleDeleteItem(deleteBtn);

    const editBtn = e.target.closest('.action-btn.edit');
    if (editBtn) return handleEditItem(editBtn);

    const viewBtn = e.target.closest('.action-btn.view');
    if (viewBtn) return handleViewItem(viewBtn);
  });
}

// Handle the delete action
function handleDeleteItem(deleteBtn) {
  const itemId = deleteBtn.getAttribute('data-id');
  const itemName = deleteBtn.getAttribute('data-name');
  const categoryId = new URLSearchParams(window.location.search).get('category_id');

  Swal.fire({
    title: 'Are you sure?',
    text: `You are about to delete "${itemName}".`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  }).then(result => {
    if (result.isConfirmed) {

      sessionStorage.setItem('deletedItemName', itemName);
      sessionStorage.setItem('deletedItemCategory', categoryId || 'none');
      
  
      let url = `/templates/inventory/function/deleteItem.php?id=${itemId}&source=category`;
      if (categoryId) url += `&category_id=${categoryId}`;
      window.location.href = url;
    }
  });
}


function checkForDeletedItem() {
  const deletedName = sessionStorage.getItem('deletedItemName');
  const deletedCategory = sessionStorage.getItem('deletedItemCategory');
  
  if (deletedName) {

    const currentCategory = new URLSearchParams(window.location.search).get('category_id') || 'none';
    
    if (deletedCategory === currentCategory || deletedCategory === 'none') {
      Swal.fire({
        icon: 'success', 
        title: 'Deleted!', 
        html: `Item <b>${deletedName}</b> was deleted successfully.`,
        confirmButtonColor: '#3085d6'
      }).then(() => {

        const url = new URL(window.location.href);
        url.searchParams.delete('deleted');
        url.searchParams.delete('id');
        window.history.replaceState({}, document.title, url.toString());
      });
    }
    

    sessionStorage.removeItem('deletedItemName');
    sessionStorage.removeItem('deletedItemCategory');
  }
}


document.addEventListener('DOMContentLoaded', checkForDeletedItem);

function handleEditItem(editBtn) {
  const getData = attr => editBtn.getAttribute(attr) || '';
  document.getElementById('edit-item-id').value = getData('data-id');
  document.getElementById('edit-item-name').value = getData('data-name');
  document.getElementById('edit-item-description').value = getData('data-description');
  document.getElementById('edit-item-brand').value = getData('data-brand');
  document.getElementById('edit-item-model').value = getData('data-model');
  document.getElementById('edit-item-serial_number').value = getData('data-serial');
  document.getElementById('edit-item-unit').value = getData('data-unit');
  document.getElementById('edit-item-unit-cost').value = getData('data-unitcost');
  document.getElementById('edit-item-total-cost').value = getData('data-totalcost');
  document.getElementById('edit-item-qty').value = getData('data-qty');
  document.getElementById('edit-item-photo').value = '';

  const dateAcquired = getData('data-date-acquired');
  document.getElementById('edit-item-date-acquired').value = dateAcquired && !isNaN(Date.parse(dateAcquired)) ? new Date(dateAcquired).toISOString().split('T')[0] : '';

  const categoryId = getData('data-category-id');
  const categorySelect = document.getElementById('edit-item-category-id');
  Array.from(categorySelect.options).forEach(opt => opt.selected = opt.value === categoryId);

  const photoOutput = document.getElementById('edit-itemPhotoOutput');
  const photo = getData('data-photo');
  if (photo) {
    photoOutput.src = photo;
    photoOutput.style.display = 'block';
  } else {
    photoOutput.src = '';
    photoOutput.style.display = 'none';
  }

  if (typeof calculateTotalCost === 'function') calculateTotalCost();
  document.getElementById('editItemModal').style.display = 'flex';
}

function handleViewItem(viewBtn) {
  document.getElementById("view-item-name").textContent = viewBtn.dataset.name;
  document.getElementById("view-item-category").textContent = viewBtn.dataset.category || 'None';
  document.getElementById("view-item-description").textContent = viewBtn.dataset.description || 'None';
  document.getElementById("view-item-brand").textContent = viewBtn.dataset.brand || 'None';
  document.getElementById("view-item-model").textContent = viewBtn.dataset.model || 'None';
  document.getElementById("view-item-serial").textContent = viewBtn.dataset.serial || 'None';
  document.getElementById("view-item-quantity").textContent = viewBtn.dataset.qty;
  document.getElementById("view-item-unit").textContent = viewBtn.dataset.unit || 'None';
  document.getElementById("view-item-unit-cost").textContent = viewBtn.dataset.unitcost;
  document.getElementById("view-item-total-cost").textContent = viewBtn.dataset.totalcost;

  document.getElementById("view-item-date-acquired").textContent = viewBtn.dataset.dateAcquired ? new Date(viewBtn.dataset.dateAcquired).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: '2-digit' }).replace(',', '').replace(' ', '-') : 'N/A';
  document.getElementById("view-item-created-at").textContent = viewBtn.dataset.created || 'N/A';

  const photo = viewBtn.dataset.photo;
  document.getElementById("view-item-photo").src = photo && photo !== '' ? '/' + photo : '/images/user-profile/default-image.jpg';

  document.getElementById("itemViewModal").style.display = "flex";
}


function initSearchFunctionality() {
    const searchInput = document.getElementById("searchItem");
    const tableBody = document.querySelector(".itemTable tbody");
    const categoryId = document.getElementById("categoryId").value;
    const pagination = document.querySelector(".pagination");
    let debounceTimer;
  
    function getFilterParams() {
      const brand = document.getElementById("brandFilter")?.value || '';
      const model = document.getElementById("modelFilter")?.value || '';
      const qty = document.getElementById("qtyFilter")?.value || '';
      const date = document.getElementById("dateFilter")?.value || '';
  
      const params = new URLSearchParams();
      params.append("category_id", categoryId);
      params.append("brand", brand);
      params.append("model", model);
      params.append("quantity", qty);
      params.append("date", date);
      return params;
    }
  
    function fetchSearchResults(term = '', page = 1) {
      const params = getFilterParams();
      params.append("search", term);
      params.append("page", page);
  
      fetch(`/templates/inventory/function/searchItemByCategory.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
          tableBody.innerHTML = data.html;
          if (pagination) {
            pagination.style.display = data.showPagination ? 'flex' : 'none';
            pagination.innerHTML = generatePagination(data.currentPage, data.totalPages);
          }
        })
        .catch(err => console.error("Fetch error:", err));
    }
  
    function getCurrentSearchTerm() {
      return searchInput?.value.trim() || '';
    }
  
 
    searchInput?.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        fetchSearchResults(getCurrentSearchTerm(), 1);
      }, 400);
    });
  
   
    searchInput?.addEventListener("keydown", e => {
      if (e.key === "Escape") {
        searchInput.value = "";
        fetchSearchResults('', 1);
      }
    });
  
   
    document.addEventListener("click", e => {
      const target = e.target.closest(".pagination a");
      if (target) {
        e.preventDefault();
        const page = parseInt(target.dataset.page);
        if (!isNaN(page)) {
          fetchSearchResults(getCurrentSearchTerm(), page);
        }
      }
    });
  }
  



function escEditItemModal() {
  document.getElementById('edit-item-form').reset();
  const preview = document.getElementById('edit-itemPhotoOutput');
  preview.src = '';
  preview.style.display = 'none';
  document.getElementById('editItemModal').style.display = 'none';
}

function closeItemView() {
  document.getElementById("itemViewModal").style.display = "none";
}

function previewItemEditPhoto(event) {
  const output = document.getElementById('edit-itemPhotoOutput');
  const file = event.target.files[0];
  if (file) {
    output.src = URL.createObjectURL(file);
    output.style.display = "block";
    output.onload = () => URL.revokeObjectURL(output.src);
  } else {
    output.src = "";
    output.style.display = "none";
  }
}


function calculateEditTotalCost() {
  const qtyInput = document.getElementById("edit-item-qty");
  const unitCostInput = document.getElementById("edit-item-unit-cost");
  const totalCostInput = document.getElementById("edit-item-total-cost");

  function updateTotalCost() {
    const qty = parseFloat(qtyInput.value) || 0;
    const unitCost = parseFloat(unitCostInput.value) || 0;
    const total = qty * unitCost;
    totalCostInput.value = total.toFixed(2);
  }

  qtyInput.addEventListener("input", updateTotalCost);
  unitCostInput.addEventListener("input", updateTotalCost);
}





function initDateValidation() {
  const dateFromInput = document.getElementById("dateFrom");
  const dateToInput = document.getElementById("dateTo");

  if (!dateFromInput || !dateToInput) return;


  dateFromInput.addEventListener("change", () => {
    if (dateFromInput.value) {
      dateToInput.min = dateFromInput.value;

    
      if (dateToInput.value && dateToInput.value < dateFromInput.value) {
        dateToInput.value = "";
      }
    } else {
 
      dateToInput.min = "";
    }
  });


  dateToInput.addEventListener("input", () => {
    if (dateFromInput.value && dateToInput.value < dateFromInput.value) {
      dateToInput.value = dateFromInput.value;
    }
  });
}


