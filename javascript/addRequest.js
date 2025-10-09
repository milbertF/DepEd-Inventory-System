function addRequest() {
  const addRequest = document.getElementById("addRequest");


  const isHidden = addRequest.style.display === 'none' || addRequest.style.display === '';
  addRequest.style.display = isHidden ? 'flex' : 'none';


  document.body.style.overflow = isHidden ? 'hidden' : '';
}

function escRequest() {
  const addRequest = document.getElementById('addRequest');
  addRequest.style.display = 'none';
  document.body.style.overflow = '';

  const form = document.getElementById('request-form');
  if (form) {
    form.reset();
  }

  const tbody = document.querySelector('#request-items-table tbody');
  if (tbody) {
    tbody.innerHTML = '';
  }


  addedItems.clear();
  updateSubmitButtonState();
}



function openItemModal() {
  const itemModal = document.getElementById('itemModal');
  itemModal.style.display = 'flex';


  document.getElementById('categoryFilter').value = '';
  document.getElementById('itemSearch').value = '';
  
  filteredItems = [...allItems];
  currentPage = 1;


  renderTable();
}


function closeItemModal() {
  document.getElementById('itemModal').style.display = 'none';
  document.getElementById('categoryFilter').value = '';
  document.getElementById('itemSearch').value = '';
  filteredItems = [...allItems];
  currentPage = 1;
}

function updateSubmitButtonState() {
  const submitBtn = document.getElementById('submitRequestBtn');
  const tbody = document.querySelector('#request-items-table tbody');
  const hasItems = tbody && tbody.children.length > 0;
  submitBtn.disabled = !hasItems;
}





let filteredItems = [...allItems];
let currentPage = 1;
const itemsPerPage = 10;
let searchTimeout = null;

function renderTable() {
  const tbody = document.getElementById('itemTableBody');
  tbody.innerHTML = '';

  const sortedItems = [...filteredItems].sort((a, b) => {
    const qtyA = Number(a.quantity);
    const qtyB = Number(b.quantity);
    if (qtyA === 0 && qtyB > 0) return 1;
    if (qtyA > 0 && qtyB === 0) return -1;
    return 0;
  });

  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const itemsToShow = sortedItems.slice(startIndex, endIndex);

  if (itemsToShow.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;">No items found</td>
      </tr>`;
    return;
  }

  itemsToShow.forEach(item => {
    const qty = Number(item.quantity);
    const isOutOfStock = qty === 0;

  
    const isAddedInRequest = [...document.querySelectorAll('#request-items-table tbody input[name="item_id[]"]')]
      .some(input => input.value == item.item_id);

    const btnDisabled = isOutOfStock;
    const btnText = isOutOfStock ? 'Out of Stock' : (isAddedInRequest ? 'Remove' : 'Select');
    const btnClass = isOutOfStock ? 'disabled-btn' : (isAddedInRequest ? 'select-btn delete-item' : 'active-btn');


    const row = document.createElement('tr');
    row.className = isOutOfStock ? 'out-of-stock-row' : '';
    row.innerHTML = `
      <td>${item.serial_number || '-'}</td>
      <td>${item.item_name}</td>
      <td>${item.description || '-'}</td>
      <td>${item.brand || '-'}</td>
      <td>${item.model || '-'}</td>
      <td>${qty}</td>
      <td>
        <button 
          type="button" 
          class="item-select-btn ${btnClass}" 
          ${btnDisabled ? 'disabled' : ''}
        >
          ${btnText}
        </button>
      </td>
    `;
    tbody.appendChild(row);

    const btn = row.querySelector('button');

    btn.onclick = () => {
      const alreadyAdded = [...document.querySelectorAll('#request-items-table tbody input[name="item_id[]"]')]
        .some(input => input.value == item.item_id);

      if (alreadyAdded) {
        const requestRow = [...document.querySelectorAll('#request-items-table tbody tr')]
          .find(r => r.querySelector('input[name="item_id[]"]').value == item.item_id);
        if (requestRow) requestRow.remove();
        addedItems.delete(item.item_id);

     
        btn.textContent = 'Select';
        btn.className = 'item-select-btn active-btn';
        btn.style.fontSize = '13px';
      } else if (!isOutOfStock) {
        selectItem(item.item_id,item.quantity, item.serial_number, item.item_name, item.description, item.brand, item.model, qty, btn);
      }
    };
  });

  renderPagination();
}

function renderPagination() {
  const pagination = document.getElementById('paginationControls');
  pagination.innerHTML = '';
  const totalPages = Math.ceil(filteredItems.length / itemsPerPage) || 1;

  pagination.innerHTML += `<a class="prev-next ${currentPage === 1 ? 'disabled' : ''}" onclick="changePage(-1)">&#8592;</a>`;

  if (currentPage > 3) {
    pagination.innerHTML += `<a onclick="goToPage(1)">1</a>`;
    if (currentPage > 4) pagination.innerHTML += `<span class="ellipsis">...</span>`;
  }

  for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
    pagination.innerHTML += `<a class="${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</a>`;
  }

  if (currentPage < totalPages - 2) {
    if (currentPage < totalPages - 3) pagination.innerHTML += `<span class="ellipsis">...</span>`;
    pagination.innerHTML += `<a onclick="goToPage(${totalPages})">${totalPages}</a>`;
  }

  pagination.innerHTML += `<a class="prev-next ${currentPage === totalPages ? 'disabled' : ''}" onclick="changePage(1)">&#8594;</a>`;
}

function changePage(direction) {
  const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
  currentPage += direction;
  if (currentPage < 1) currentPage = 1;
  if (currentPage > totalPages) currentPage = totalPages;
  renderTable();
}

function goToPage(pageNum) {
  currentPage = pageNum;
  renderTable();
}


function applyFilters(event) {
  const catId = document.getElementById('categoryFilter').value;
  const searchValue = document.getElementById('itemSearch').value.toLowerCase();

  const matchesCategory = (item) => !catId || item.category_id == catId;
  const matchesSearch = (item) => {
    return !searchValue ||
      (item.item_name && item.item_name.toLowerCase().includes(searchValue)) ||
      (item.serial_number && item.serial_number.toLowerCase().includes(searchValue)) ||
      (item.description && item.description.toLowerCase().includes(searchValue)) ||
      (item.brand && item.brand.toLowerCase().includes(searchValue)) ||
      (item.model && item.model.toLowerCase().includes(searchValue));
  };


  if (event && event.target.id === 'categoryFilter') {
    filteredItems = allItems.filter(item => matchesCategory(item) && matchesSearch(item));
    currentPage = 1;
    renderTable();
  } else {

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      filteredItems = allItems.filter(item => matchesCategory(item) && matchesSearch(item));
      currentPage = 1;
      renderTable();
    }, 300);
  }
}


const addedItems = new Set(); 

function selectItem(id, quantity, serial_number, name, description, brand, model, qty, button) {
  const tbody = document.querySelector('#request-items-table tbody');

  if (addedItems.has(id)) {
    return;
  }

  const today = new Date();
  const localDate = today.toISOString().split('T')[0];


  const row = document.createElement('tr');
  row.innerHTML = `
  <td data-label="Available Quantity" class="qty-highlight">${quantity || '-'}</td>

  <td data-label="Serial #">${serial_number || '-'}</td>
    <td data-label="Item">${name}<input type="hidden" name="item_id[]" value="${id}"></td>
 
    <td data-label="Description">${description || '-'}</td>
    <td data-label="Brand">${brand || '-'}</td>
    <td data-label="Model">${model || '-'}</td>
    <td data-label="Quantity"><input type="number" name="quantity_requested[]" min="1" max="${qty}" required></td>
    <td data-label="Date Needed"><input type="date" name="date_needed[]" min="${localDate}" required></td>
    <td data-label="Delete">
      <button type="button" class="action-btn delete-item">
        <i class="fa fa-trash-alt"></i>
      </button>
    </td>
  `;
  tbody.appendChild(row);
  updateSubmitButtonState();



  button.classList.add('delete-item'); 
  button.innerHTML = 'Remove';
  addedItems.add(id);


  const removeFunc = () => {
   
    row.remove();
    updateSubmitButtonState();


    button.classList.remove('delete-item');
 
    button.textContent = 'Select';
    button.style.fontSize = '13px'; 
    button.onclick = () => selectItem(id,quantity, serial_number, name, description, brand, model, qty, button);


    addedItems.delete(id);
  };


  button.onclick = removeFunc;


  const rowRemoveBtn = row.querySelector('button');
  rowRemoveBtn.onclick = removeFunc;
}



function removeRow(btn, id) {
  btn.closest('tr').remove();


  const selectBtn = document.querySelector(`button[onclick*="selectItem(${id},"]`);
  if (selectBtn) {
    selectBtn.disabled = false;
    selectBtn.textContent = "Select";
    selectBtn.classList.remove("selected-btn");
  }
}

document.getElementById('request-form').addEventListener('submit', function (e) {
  const submitBtn = document.getElementById('submitRequestBtn');
  

  if (submitBtn.disabled) {
    e.preventDefault();
    return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Submitting...';


});
