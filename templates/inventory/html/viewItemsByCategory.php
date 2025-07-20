<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/fetchItemsByCategory.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Items under <?= htmlspecialchars($category['category_name']) ?></title>
  <link rel="stylesheet" href="/styles/items.css" />
  <link rel="stylesheet" href="/styles/viewItemByCategoryTable.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>

    <div class="con">
      <h3>Items under category: <?= htmlspecialchars($category['category_name']) ?></h3>

      <?php require __DIR__ . '/../../quick-access/access.php'; ?>
            
      <div class="tableContainer">
        <?php if (count($items) === 0): ?>
          <p style="text-align: center;">No items found in this category.</p>
        <?php else: ?>

            <div class="searchContainer">
                    <input type="text" id="searchItem" placeholder="Search Item.." />
                </div>
          <table class="itemTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Image</th>
                <th>Item Name</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Date Added</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $index => $item): ?>
                <tr>
                  <td><?= ($page - 1) * $limit + $index + 1 ?></td>
                  <td>
  <img
    src="<?= !empty($item['item_photo']) ? htmlspecialchars($item['item_photo']) : '/images/user-profile/default-image.jpg' ?>"
    alt="Item Photo"
    class="item-photo"
  />
</td>

                  
                  <td><?= htmlspecialchars($item['item_name']) ?></td>
                  <td><?= htmlspecialchars($item['brand']) ?></td>
                  <td><?= htmlspecialchars($item['model']) ?></td>
                  <td><?= htmlspecialchars($item['quantity']) ?></td>
                  <td><?= htmlspecialchars($item['unit']) ?></td>
                  <td><?= isset($item['created_at']) ? date("M-d-Y", strtotime($item['created_at'])) : 'N/A' ?></td>
                  <td>
                    <button class="action-btn view" title="View Items"
                     >
                      <i class="fas fa-eye"></i>
                    </button>

                    <button class="action-btn edit" title="Edit Item"
                     
                     >
                      <i class="fas fa-edit"></i>
                    </button>

                    <button class="action-btn delete" title="Delete Item"
                    >
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <?php if ($totalPages > 1): ?>
            <div class="pagination">
              <?php if ($page > 1): ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $page - 1 ?>" class="prev-next" title="Previous">
                  <i class="fas fa-chevron-left"></i>
                </a>
              <?php else: ?>
                <a class="prev-next disabled" title="Previous">
                  <i class="fas fa-chevron-left"></i>
                </a>
              <?php endif; ?>

              <?php if ($page > 3): ?>
                <a href="?category_id=<?= $categoryId ?>&page=1">1</a>
                <?php if ($page > 4): ?>
                  <span class="ellipsis">...</span>
                <?php endif; ?>
              <?php endif; ?>

              <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
                  <?= $i ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $totalPages - 2): ?>
                <?php if ($page < $totalPages - 3): ?>
                  <span class="ellipsis">...</span>
                <?php endif; ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a>
              <?php endif; ?>

              <?php if ($page < $totalPages): ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $page + 1 ?>" class="prev-next" title="Next">
                  <i class="fas fa-chevron-right"></i>
                </a>
              <?php else: ?>
                <a class="prev-next disabled" title="Next">
                  <i class="fas fa-chevron-right"></i>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>

        <?php endif; ?>

        <div class="backBtnContainer">
        <a href="/items" class="backBtn">
  <i class="fas fa-arrow-left"></i> Back to Inventory
</a>

        </div>
      </div>
    </div>
  </div>

  <script src="/javascript/header.js"></script>
  <script src="/javascript/sidebar.js"></script>

  <script src="/javascript/script.js"></script>
</body>
</html>
