<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/editCategoryFunction.php';

require __DIR__ . '/../function/fetchCategory.php';



?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BCSI-Inventory</title>
    <link rel="stylesheet" href="/styles/items.css" />
 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/styles/empItemTable.css" />
</head>

<body>
<?php require __DIR__ . '/editCategory.php'; ?>
    <div class="wrapMain">
        <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
        <div class="con">
            <h3>Inventory</h3>

            <?php require __DIR__ . '/../../quick-access/access.php'; ?>

            <div class="tableContainer">
            <div class="searchContainer">
          <input type="text" id="searchCategory" placeholder="Search Category..." 
                 value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" />
        </div>
        

                <table class="itemTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category Name</th>
                       
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <tr>
                                <td><?= ($page - 1) * $limit + $index + 1 ?></td>

                                    <td><?= htmlspecialchars($category['category_name']) ?></td>
                                   
                                    <td>
                                 

<button class="action-btn view" 
        onclick="window.location.href='/itemsByCategory?category_id=<?= $category['category_id'] ?>'">
  <i class="fas fa-external-link-alt"></i>
  <span class="tooltip">View Items</span>
</button>


<button class="action-btn edit" 
        data-id="<?= $category['category_id'] ?>"
        data-name="<?= htmlspecialchars($category['category_name']) ?>">
  <i class="fas fa-edit"></i>
  <span class="tooltip">Edit Category</span>
</button>



<button class="action-btn delete" 
        data-id="<?= $category['category_id'] ?>"
        data-name="<?= htmlspecialchars($category['category_name']) ?>">
  <i class="fas fa-trash-alt"></i>
  <span class="tooltip">Delete Category</span>
</button>



                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No categories found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php 
         
            $baseUrl = '?';
            if (isset($_GET['search'])) {

              $baseUrl .= 'search=' . urlencode($_GET['search']) . '&';
            }
            ?>
            
            <?php if ($page > 1): ?>
              <a href="<?= $baseUrl ?>page=<?= $page - 1 ?>" class="prev-next" title="Previous">
                <i class="fas fa-chevron-left"></i>
              </a>
            <?php else: ?>
              <a class="prev-next disabled" title="Previous">
                <i class="fas fa-chevron-left"></i>
              </a>
            <?php endif; ?>

            <?php if ($page > 3): ?>
              <a href="<?= $baseUrl ?>page=1">1</a>
              <?php if ($page > 4): ?>
                <span class="ellipsis">...</span>
              <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
              <a href="<?= $baseUrl ?>page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages - 2): ?>
              <?php if ($page < $totalPages - 3): ?>
                <span class="ellipsis">...</span>
              <?php endif; ?>
              <a href="<?= $baseUrl ?>page=<?= $totalPages ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
              <a href="<?= $baseUrl ?>page=<?= $page + 1 ?>" class="prev-next" title="Next">
                <i class="fas fa-chevron-right"></i>
              </a>
            <?php else: ?>
              <a class="prev-next disabled" title="Next">
                <i class="fas fa-chevron-right"></i>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="viewAllContainer">
    <a href="/allItems" class="viewAll">
        <i class="fas fa-boxes"></i> View All Items
    </a>

    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin'): ?>
    <a href="/recentlyDeleted" class="viewAll">
        <i class="fas fa-trash-alt"></i> Recently Deleted Items
    </a>
<?php endif; ?>

</div>
      </div>
    </div>
  </div>

    <script src="/javascript/header.js"></script>
    <script src="/javascript/sidebar.js"></script>
    <script src="/javascript/inventory.js"></script>
    <script src="/javascript/script.js"></script>
</body>

</html>
