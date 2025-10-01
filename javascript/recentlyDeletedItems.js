document.addEventListener("DOMContentLoaded", function () {

    initTableActions();
    checkForDeletedItem();
    initSearchFunctionalityAll();
    checkForRecoveredItem();

  
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
         
        }
      }
    }
  });


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
  

  function fetchFilteredItemsAll(page = 1, extraParams = {}) {

  
    const params = new URLSearchParams({ page, });
  

    const search = document.getElementById("searchItem")?.value.trim();
    if (search) params.set("search", search);
  
 
    const dateFrom = document.getElementById("dateFrom")?.value;
    const dateTo = document.getElementById("dateTo")?.value;
    if (dateFrom) params.set("date_from", dateFrom);
    if (dateTo) params.set("date_to", dateTo);
  
 
    const brandCheckboxes = document.querySelectorAll("input[name='brandsAll[]']:checked");
    brandCheckboxes.forEach(cb => params.append("brands[]", cb.value));
  
    fetch(`/templates/inventory/function/searchItemInRecentlyDeleted.php?${params}`)
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

      const recoverBtn = e.target.closest('.action-btn.restore');
      if (recoverBtn) return handleRecoverItem(recoverBtn);
  

  
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
      text: `You are about to delete "${itemName}". This action cannot be undone and you will not be able to recover this item.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (result.isConfirmed) {
        sessionStorage.setItem('deletedItemName', itemName);
        let url = `/templates/inventory/function/finalDeleteItem.php?id=${itemId}&source=category`;
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
  

  let scrollPosition = 0;

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
  
    document.getElementById("view-item-date-acquired").textContent =
      viewBtn.dataset.dateAcquired
        ? new Date(viewBtn.dataset.dateAcquired).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: '2-digit'
          }).replace(',', '').replace(' ', '-')
        : 'N/A';
    document.getElementById("view-item-created-at").textContent = viewBtn.dataset.created || 'N/A';
  
    const photo = viewBtn.dataset.photo;
    document.getElementById("view-item-photo").src =
      photo && photo !== '' ? '/' + photo : '/images/user-profile/default-image.jpg';
  

    scrollPosition = window.scrollY;
    document.body.style.position = "fixed";
    document.body.style.top = `-${scrollPosition}px`;
    document.body.style.overflow = "hidden";
    document.body.style.width = "100%";
  
    document.getElementById("itemViewModal").style.display = "flex";
  }
  
  function closeItemView() {
    document.getElementById("itemViewModal").style.display = "none";
  

    document.body.style.position = "";
    document.body.style.top = "";
    document.body.style.overflow = "";
    document.body.style.width = "";
    window.scrollTo(0, scrollPosition);
  }
  

  function handleRecoverItem(recoverBtn) {
    const itemId = recoverBtn.getAttribute('data-id');
    const itemName = recoverBtn.getAttribute('data-name');
  
    Swal.fire({
      title: 'Recover Item?',
      text: `Are you sure you want to recover "${itemName}" back to the inventory?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, recover it!',
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (result.isConfirmed) {
        sessionStorage.setItem('recoveredItemName', itemName);
        let url = `/templates/inventory/function/recoverItem.php?id=${itemId}`;
        window.location.href = url;
      }
    });
  }
  
  function checkForRecoveredItem() {
    const recoveredName = sessionStorage.getItem('recoveredItemName');
    if (recoveredName) {
      Swal.fire({
        icon: 'success',
        title: 'Recovered!',
        html: `Item <b>${recoveredName}</b> has been restored to inventory.`,
        confirmButtonColor: '#3085d6'
      }).then(() => {
        const url = new URL(window.location.href);
        url.searchParams.delete('recovered');
        url.searchParams.delete('id');
        window.history.replaceState({}, document.title, url.pathname + '?' + url.searchParams.toString());
      });
      sessionStorage.removeItem('recoveredItemName');
    }
  }