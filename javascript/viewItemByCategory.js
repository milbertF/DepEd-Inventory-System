document.addEventListener('DOMContentLoaded', function() {
     
    const brandToggle = document.getElementById("toggleBrandFilter");
    const brandFilter = document.getElementById("brandFilterContainer");
    
    const qtyToggle = document.getElementById("toggleQtyFilter");
    const qtyFilter = document.getElementById("quantityFilterContainer");
    
    const dateToggle = document.getElementById("toggleDateFilter");
    const dateFilter = document.getElementById("dateFilterContainer");


    function closeAllFilters() {
      brandFilter.classList.add("hidden");
      qtyFilter.classList.add("hidden");
      dateFilter.classList.add("hidden");
    }

   
    brandToggle.addEventListener("click", function(e) {
      e.stopPropagation();
      if (brandFilter.classList.contains("hidden")) {
        closeAllFilters();
        brandFilter.classList.remove("hidden");
      } else {
        brandFilter.classList.add("hidden");
      }
    });

    
    qtyToggle.addEventListener("click", function(e) {
      e.stopPropagation();
      if (qtyFilter.classList.contains("hidden")) {
        closeAllFilters();
        qtyFilter.classList.remove("hidden");
      } else {
        qtyFilter.classList.add("hidden");
      }
    });

 
    dateToggle.addEventListener("click", function(e) {
      e.stopPropagation();
      if (dateFilter.classList.contains("hidden")) {
        closeAllFilters();
        dateFilter.classList.remove("hidden");
      } else {
        dateFilter.classList.add("hidden");
      }
    });


    document.addEventListener('click', function() {
      closeAllFilters();
    });


    [brandFilter, qtyFilter, dateFilter].forEach(filter => {
      filter.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    });


    document.getElementById("sortLowToHigh").addEventListener("click", function() {
    
      qtyFilter.classList.add("hidden");
      console.log("Sorting Low to High");
  
    });

    document.getElementById("sortHighToLow").addEventListener("click", function() {

      qtyFilter.classList.add("hidden");
      console.log("Sorting High to Low");
  
    });

    document.getElementById("showOutOfStock").addEventListener("click", function() {
     
      qtyFilter.classList.add("hidden");
      console.log("Showing Out of Stock items");
 
    });


    document.getElementById("filterByBrandBtn").addEventListener("click", function() {

      brandFilter.classList.add("hidden");
      console.log("Applying brand filter");
    
    });

    document.getElementById("resetBrandFilterBtn").addEventListener("click", function() {
  
      document.getElementById("brandSelect").selectedIndex = -1;
      brandFilter.classList.add("hidden");
      console.log("Resetting brand filter");
     
    });


    document.getElementById("filterByDateBtn").addEventListener("click", function() {
    
      dateFilter.classList.add("hidden");
      console.log("Applying date filter");
     
    });

    document.getElementById("resetDateFilterBtn").addEventListener("click", function() {
  
      document.getElementById("dateFrom").value = "";
      document.getElementById("dateTo").value = "";
      dateFilter.classList.add("hidden");
      console.log("Resetting date filter");
    
    });
  });


  document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('.itemTable tbody');
  

    if (tableBody) {
      tableBody.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('.action-btn.delete');
  
        if (deleteBtn) {
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
          }).then((result) => {
            if (result.isConfirmed) {
              sessionStorage.setItem('deletedItemName', itemName);
              window.location.href = `/templates/inventory/function/deleteItem.php?id=${itemId}&category_id=${categoryId}`;
            }
          });
        }
  
      
        const editBtn = e.target.closest('.action-btn.edit');
        if (editBtn) {
          const itemId = editBtn.getAttribute('data-id');
          const itemPhoto = editBtn.getAttribute('data-photo');
          const itemName = editBtn.getAttribute('data-name');
          const itemDescription = editBtn.getAttribute('data-description');
          const itemBrand = editBtn.getAttribute('data-brand');
          const itemModel = editBtn.getAttribute('data-model');
          const itemSerial = editBtn.getAttribute('data-serial');
          const itemUnit = editBtn.getAttribute('data-unit');
          const itemUnitCost = editBtn.getAttribute('data-unitcost');
          const itemTotalCost = editBtn.getAttribute('data-totalcost');
          const itemQty = editBtn.getAttribute('data-qty');
          const itemDateAcquired = editBtn.getAttribute('data-date-acquired');
          const itemCategoryId = editBtn.getAttribute('data-category-id');
  
       
          document.getElementById('edit-item-id').value = itemId || '';
          document.getElementById('edit-item-name').value = itemName || '';
          document.getElementById('edit-item-description').value = itemDescription || '';
          document.getElementById('edit-item-brand').value = itemBrand || '';
          document.getElementById('edit-item-model').value = itemModel || '';
          document.getElementById('edit-item-serial_number').value = itemSerial || '';
          document.getElementById('edit-item-unit').value = itemUnit || '';
          document.getElementById('edit-item-unit-cost').value = itemUnitCost || '';
          document.getElementById('edit-item-total-cost').value = itemTotalCost || '';
          document.getElementById('edit-item-qty').value = itemQty || '';
          document.getElementById('edit-item-photo').value = '';
  
          const dateInput = document.getElementById('edit-item-date-acquired');
          let formattedDate = '';
          if (itemDateAcquired && !isNaN(Date.parse(itemDateAcquired))) {
            formattedDate = new Date(itemDateAcquired).toISOString().split('T')[0];
          }
          dateInput.value = formattedDate;
  
      
          const categorySelect = document.getElementById('edit-item-category-id');
          if (categorySelect) {
            Array.from(categorySelect.options).forEach(opt => {
              opt.selected = opt.value === itemCategoryId;
            });
          }
  
        
          const photoOutput = document.getElementById('edit-itemPhotoOutput');
          if (itemPhoto) {
            photoOutput.src = itemPhoto;
            photoOutput.style.display = 'block';
          } else {
            photoOutput.src = '';
            photoOutput.style.display = 'none';
          }
  
          if (typeof calculateTotalCost === 'function') {
            calculateTotalCost();
          }
  
          document.getElementById('editItemModal').style.display = 'flex';
        }
      });
    }
  

    const deletedItemName = sessionStorage.getItem('deletedItemName');
    if (deletedItemName) {
      Swal.fire({
        icon: 'success',
        title: 'Deleted!',
        html: `Item <b>${deletedItemName}</b> was deleted successfully.`,
        confirmButtonColor: '#3085d6'
      }).then(() => {
        const url = new URL(window.location.href);
        url.searchParams.delete('deleted');
        url.searchParams.delete('id');
        window.history.replaceState({}, document.title, url.pathname + '?' + url.searchParams.toString());
      });
      sessionStorage.removeItem('deletedItemName');
    }
  

    const qtyInput = document.getElementById('edit-item-qty');
    const unitCostInput = document.getElementById('edit-item-unit-cost');
    if (qtyInput && unitCostInput) {
      qtyInput.addEventListener('input', calculateTotalCost);
      unitCostInput.addEventListener('input', calculateTotalCost);
    }
  });

  function calculateTotalCost() {
    const qty = parseFloat(document.getElementById('edit-item-qty').value) || 0;
    const unitCost = parseFloat(document.getElementById('edit-item-unit-cost').value) || 0;
    const totalCost = qty * unitCost;
    document.getElementById('edit-item-total-cost').value = totalCost.toFixed(2);
  }
  

  function previewItemPhoto(event) {
    const output = document.getElementById('edit-itemPhotoOutput');
    const file = event.target.files[0];
    if (file) {
      output.src = URL.createObjectURL(file);
      output.style.display = 'block';
    } else {
      output.src = '';
      output.style.display = 'none';
    }
  }

  function escEditItemModal() {
    document.getElementById('edit-item-form').reset();
    const preview = document.getElementById('edit-itemPhotoOutput');
    preview.src = '';
    preview.style.display = 'none';
    document.getElementById('editItemModal').style.display = 'none';
  }
  

  document.addEventListener("DOMContentLoaded", () => {
    const viewButtons = document.querySelectorAll(".action-btn.view");
  
    viewButtons.forEach(button => {
      button.addEventListener("click", () => {
     

        document.getElementById("view-item-name").textContent = button.dataset.name;
        document.getElementById("view-item-category").textContent = button.dataset.category;
        document.getElementById("view-item-description").textContent = button.dataset.description || 'None';
        document.getElementById("view-item-brand").textContent = button.dataset.brand || 'None';
        document.getElementById("view-item-model").textContent = button.dataset.model || 'None';
        document.getElementById("view-item-serial").textContent = button.dataset.serial || 'None';
        document.getElementById("view-item-quantity").textContent = button.dataset.qty;
        document.getElementById("view-item-unit").textContent = button.dataset.unit || 'None';
        document.getElementById("view-item-unit-cost").textContent = button.dataset.unitcost;
        document.getElementById("view-item-total-cost").textContent = button.dataset.totalcost;
        document.getElementById("view-item-date-acquired").textContent = 
        button.dataset.dateAcquired 
            ? new Date(button.dataset.dateAcquired).toLocaleDateString('en-US', {
                  year: 'numeric',
                  month: 'long',
                  day: '2-digit'
              }).replace(',', '').replace(' ', '-')
            : 'N/A';
        document.getElementById("view-item-created-at").textContent = button.dataset.created || 'N/A';
  
        const photo = button.dataset.photo;
        document.getElementById("view-item-photo").src = photo && photo !== '' ? '/' + photo : '/images/user-profile/default-image.jpg';
  

        document.getElementById("itemViewModal").style.display = "flex";
      });
    });
  });
  
  function closeItemView() {
    document.getElementById("itemViewModal").style.display = "none";
  }
  


 