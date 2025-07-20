<?php


require  __DIR__ . '/../../header/html/header.php';

require  __DIR__ . '/../function/editPosFunction.php';
require  __DIR__ . '/../function/deletePos.php';

?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DIS-Position</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>




  <link rel="stylesheet" href="/styles/position.css">
</head>

<body>




  <?php require __DIR__ . '/editPos.php'; ?>





  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
    <div class="con">
      <h3>Position</h3>



      <?php require __DIR__ . '/../../quick-access/access.php'; 

require  __DIR__ . '/../function/fetchPos.php';
      
      
      
      ?>
      
      <div class="tableContainer">

        <div class="searchContainer">
          <input type="text" id="searchPosition" placeholder="Search positions..." />
        </div>

        <table class="positionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Position Name</th>
              <th>Description</th>
              <th>Date Added</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($positions)): ?>
              <?php foreach ($positions as $index => $position): ?>
                <tr>
                <td><?= ($page - 1) * $limit + $index + 1 ?></td>

                  <td><?= htmlspecialchars($position['position_title']) ?></td>
                  <td><?= htmlspecialchars($position['position_description']) ?: '<em>No description</em>' ?></td>
                  <td><?= isset($position['created_at']) ? date("M-d-Y", strtotime($position['created_at'])) : 'N/A' ?></td>
                  <td>
                    <button
                      class="action-btn edit"
                      data-id="<?= $position['position_id'] ?>"
                      data-title="<?= htmlspecialchars($position['position_title']) ?>"
                      data-description="<?= htmlspecialchars($position['position_description']) ?>">
                      <i class="fas fa-edit"></i>
                    </button>

                    <button class="action-btn delete"
                      data-id="<?= $position['position_id'] ?>"
                      data-title="<?= htmlspecialchars($position['position_title']) ?>">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" style="text-align: center;">No positions found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1 ?>" class="prev-next" title="Previous">
        <i class="fas fa-chevron-left"></i>
      </a>
    <?php else: ?>
      <a class="prev-next disabled" title="Previous">
        <i class="fas fa-chevron-left"></i>
      </a>
    <?php endif; ?>

    <?php 
    // Show first page and ellipsis if needed
    if ($page > 3): ?>
      <a href="?page=1">1</a>
      <?php if ($page > 4): ?>
        <span class="ellipsis">...</span>
      <?php endif; ?>
    <?php endif; ?>

    <?php 
    // Show page numbers around current page
    for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
      <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>

    <?php 
    // Show last page and ellipsis if needed
    if ($page < $totalPages - 2): ?>
      <?php if ($page < $totalPages - 3): ?>
        <span class="ellipsis">...</span>
      <?php endif; ?>
      <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page + 1 ?>" class="prev-next" title="Next">
        <i class="fas fa-chevron-right"></i>
      </a>
    <?php else: ?>
      <a class="prev-next disabled" title="Next">
        <i class="fas fa-chevron-right"></i>
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>

      </div>

      <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>

      <?php endif; ?>


    </div>

  </div>



  <script src="/javascript/header.js"></script>
  <script src="/javascript/position.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/script.js"></script>

</body>

</html>