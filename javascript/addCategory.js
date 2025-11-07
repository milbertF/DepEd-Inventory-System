function addCategory() {
    const addCategory = document.getElementById("addCategory");
    addCategory.style.display = (addCategory.style.display === 'none') ? 'flex' : 'none';
  }
  
  function escCategory() {
    const modal = document.getElementById("addCategory");
    modal.style.display = "none";
  
    const form = document.getElementById("category-form");
    form.reset();
  
    const container = document.getElementById('categories-container');
    while (container.children.length > 1) {
      container.removeChild(container.lastChild);
    }
  
    categoryCount = 1;
  }
  
  let categoryCount = 1;
  
  function addMoreCategory() {
    const container = document.getElementById('categories-container');
    const newEntry = document.createElement('div');
    newEntry.className = 'category-entry';
    newEntry.dataset.index = categoryCount;
  
    newEntry.innerHTML = `
    <button type="button" class="remove-category" onclick="removeCategory(this)">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="inpart">
      <label for="category-name-${categoryCount}">Category Name</label>
      <div class="inputs">
        <input type="text" id="category-name-${categoryCount}" name="categories[${categoryCount}][name]" placeholder="e.g., Electronics, Furniture" required />
      </div>
    </div>
  `;
  
  
    container.appendChild(newEntry);
    categoryCount++;
  }
  
  
  function removeCategory(button) {
    const entry = button.closest('.category-entry');
    entry.remove();
  }
  