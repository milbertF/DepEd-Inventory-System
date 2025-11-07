



  <div class="addEmployee" style="display: none;" id="editCategory">
    <div class="esc">
      <button id="btnEscEditCategory" onclick="escEditCategory()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="con">
      <h4>Edit Category</h4>

      <form method="POST" autocomplete="off" id="edit-category-form">
        <input type="hidden" id="edit-category-id" name="category_id" />

        <div class="inpart">
          <label for="edit-category-name">Category Name</label>
          <div class="inputs">
            <input type="text" id="edit-category-name" name="category_name" placeholder="Enter new category name" required />
          </div>
        </div> 

        <div class="btnSave">
          <button type="submit" name="submit_edit_category" id="editCategoryBtn">Save Changes</button>
        </div>
      </form>
    </div>
  </div>



