document.addEventListener("DOMContentLoaded", function () {
    initFilterControlsAll();
    initSearchFunctionalityAll();
    initPaginationAll();
    initFilterActionsAll();
    initTableActions();
    checkForDeletedItem();

    initDateValidation();
    calculateEditTotalCost();
  
  });
  
 
  let currentFiltersAll = {};
  

  function initFilterControlsAll() {
    const qtyToggle = document.getElementById("toggleQtyFilterAll");
    const qtyFilter = document.getElementById("quantityFilterContainerAll");
  
    const dateToggle = document.getElementById("toggleDateFilterAll");
    const dateFilter = document.getElementById("dateFilterContainerAll");
  
    function closeAllFilters() {
      if (qtyFilter) qtyFilter.classList.add("hidden");
      if (dateFilter) dateFilter.classList.add("hidden");
    }
  
    if (qtyToggle && qtyFilter) {
      qtyToggle.addEventListener("click", () => {
        const isHidden = qtyFilter.classList.contains("hidden");
        closeAllFilters();
        if (isHidden) qtyFilter.classList.remove("hidden");
      });
    }
  
    if (dateToggle && dateFilter) {
      dateToggle.addEventListener("click", () => {
        const isHidden = dateFilter.classList.contains("hidden");
        closeAllFilters();
        if (isHidden) dateFilter.classList.remove("hidden");
      });
    }
  
   
    window.closeAllFiltersAll = closeAllFilters;
  }
  

  function initSearchFunctionalityAll() {
    const searchInput = document.getElementById("searchItem");
    if (searchInput) {
      let debounceTimer;
      searchInput.addEventListener("input", function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          fetchFilteredItemsAll(1);
        }, 300);
      });
    }
  }
  
 
  function initPaginationAll() {
    document.addEventListener("click", function (e) {
      if (e.target.classList.contains("page-link-all")) {
        e.preventDefault();
        const page = e.target.dataset.page;
        fetchFilteredItemsAll(page);
      }
    });
  }
  

  function initFilterActionsAll() {
    const lowBtn = document.getElementById("sortLowToHighAll");
    const highBtn = document.getElementById("sortHighToLowAll");
    const outBtn = document.getElementById("showOutOfStockAll");
  
    if (lowBtn) {
      lowBtn.addEventListener("click", () => {
        currentFiltersAll.sort_quantity = "asc";
        delete currentFiltersAll.out_of_stock;
        fetchFilteredItemsAll(1);
        window.closeAllFiltersAll?.();
      });
    }
  
    if (highBtn) {
      highBtn.addEventListener("click", () => {
        currentFiltersAll.sort_quantity = "desc";
        delete currentFiltersAll.out_of_stock;
        fetchFilteredItemsAll(1);
        window.closeAllFiltersAll?.();
      });
    }
  
    if (outBtn) {
      outBtn.addEventListener("click", () => {
        currentFiltersAll.out_of_stock = "1";
        delete currentFiltersAll.sort_quantity;
        fetchFilteredItemsAll(1);
        window.closeAllFiltersAll?.();
      });
    }
  

    const applyDate = document.getElementById("filterByDateBtnAll");
    const resetDate = document.getElementById("resetDateFilterBtnAll");
  
    if (applyDate) {
      applyDate.addEventListener("click", () => {
        fetchFilteredItemsAll(1);
        window.closeAllFiltersAll?.();
      });
    }
  
    if (resetDate) {
      resetDate.addEventListener("click", () => {
        document.getElementById("dateFrom").value = "";
        document.getElementById("dateTo").value = "";
        fetchFilteredItemsAll(1);
        window.closeAllFiltersAll?.();
      });
    }
  }
  
  function fetchFilteredItemsAll(page = 1, extraParams = {}) {

    currentFiltersAll = { ...currentFiltersAll, ...extraParams };
  
    const params = new URLSearchParams({ page, ...currentFiltersAll });
  

    const search = document.getElementById("searchItem")?.value.trim();
    if (search) params.set("search", search);
  
 
    const dateFrom = document.getElementById("dateFrom")?.value;
    const dateTo = document.getElementById("dateTo")?.value;
    if (dateFrom) params.set("date_from", dateFrom);
    if (dateTo) params.set("date_to", dateTo);
  
 
    const brandCheckboxes = document.querySelectorAll("input[name='brandsAll[]']:checked");
    brandCheckboxes.forEach(cb => params.append("brands[]", cb.value));
  
    fetch(`/templates/inventory/function/filterAllItems.php?${params}`)
      .then(async (res) => {
        const text = await res.text();
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error("Invalid JSON from PHP:", text);
          throw e;
        }
      })
      .then((data) => {
        const tableBody = document.querySelector(".itemTable tbody");
        if (tableBody) {
          tableBody.innerHTML = data.html;
        }
  
        const pagination = document.querySelector(".pagination");
        if (pagination) {
          pagination.innerHTML = generatePaginationAll(
            data.currentPage,
            data.totalPages,
            Object.fromEntries(params)
          );
        }
      })
      .catch((err) => console.error("Fetch error:", err));
  }
  

  function generatePaginationAll(currentPage, totalPages, currentParams = {}) {
    let html = "";
  
    const buildQueryString = (params) => {
      return Object.entries(params)
        .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
        .join("&");
    };
  
    const createPageLink = (page, label = null, active = false) => {
      const updatedParams = { ...currentParams, page };
      const queryString = buildQueryString(updatedParams);
      return `<a href="#" class="page-link-all ${active ? "active" : ""}" data-page="${page}">${
        label || page
      }</a>`;
    };
  
    if (totalPages <= 1) return html;
  

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
        let url = `/templates/inventory/function/deleteSpecificItem.php?id=${itemId}&source=category`;
        if (categoryId) url += `&category_id=${categoryId}`;
        window.location.href = url;
      }
    });
  }

  function checkForDeletedItem() {
    const deletedName = sessionStorage.getItem('deletedItemName');
    if (deletedName) {
      Swal.fire({ icon: 'success', title: 'Deleted!', html: `Item <b>${deletedName}</b> was deleted successfully.`, confirmButtonColor: '#3085d6' })
        .then(() => {
          const url = new URL(window.location.href);
          url.searchParams.delete('deleted');
          url.searchParams.delete('id');
          window.history.replaceState({}, document.title, url.pathname + '?' + url.searchParams.toString());
        });
      sessionStorage.removeItem('deletedItemName');
    }
  }
  

  

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
  



  // ---------------------
// DATE FILTER VALIDATION
// ---------------------
// ---------------------
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