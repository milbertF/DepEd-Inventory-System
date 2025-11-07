<?php

require __DIR__ . '/../function/addCategoryFunction.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="/styles/addOffPos.css">
</head>
<body>

  <div class="addEmployee" style="display: none;" id="addCategory">
    <div class="esc">
      <button id="btnEscCategory" onclick="escCategory()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="con">
      <h4>Add Category</h4>

      <form method="POST" autocomplete="off" id="category-form">
        <div class="info">
          Note: You can add multiple categories at once.
          Fill in the first category, then click "Add Another Category"
          to include more before submitting.
        </div>

        <div id="categories-container">
          <div class="category-entry" data-index="0">
            <div class="inpart">
              <label for="category-name-0">Category Name</label>
              <div class="inputs">
                <input type="text" id="category-name-0" name="categories[0][name]" placeholder="e.g., Electronics, Furniture" required />
              </div>
            </div>
          </div>     
        </div>

        <div class="btn-group">
          <button type="button" class="add-more" onclick="addMoreCategory()">
            <i class="fas fa-plus"></i> Add Another Category
          </button>
        </div>

        <div class="btnSave">
          <button type="submit" name="submit_category" id="submitCategoryBtn">Submit All Categories</button>
        </div>
      </form>
    </div>
  </div>

  <script src="/javascript/addCategory.js"></script>
</body>
</html>
