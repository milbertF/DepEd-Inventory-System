<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/editPosFunction.php';
require __DIR__ . '/../function/deletePos.php';
require __DIR__ . '/../function/fetchPos.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BCSI-Position</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="/styles/position.css">
</head>

<body>
  <?php require __DIR__ . '/editPos.php'; ?>

  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
    <div class="con">
      <h3>Position</h3>

      <?php require __DIR__ . '/../../quick-access/access.php'; ?>
      
      <div class="tableContainer">
        <div class="searchContainer">
          <input type="text" id="searchPosition" placeholder="Search positions..." 
                 value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" />
        </div>

        <table class="positionTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Position Name</th>
              <th>Description</th>
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
                  <td>
                    <button class="action-btn edit"
                      data-id="<?= $position['position_id'] ?>"
                      data-title="<?= htmlspecialchars($position['position_title']) ?>"
                      data-description="<?= htmlspecialchars($position['position_description']) ?>">
                      <i class="fas fa-edit"></i>
                      <span class="tooltip">Edit Position</span>
                    </button>
                    <button class="action-btn delete"
                      data-id="<?= $position['position_id'] ?>"
                      data-title="<?= htmlspecialchars($position['position_title']) ?>">
                      <i class="fas fa-trash-alt"></i>
                      <span class="tooltip">Delete Position</span>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" style="text-align: center;">
                  <?= isset($_GET['search']) ? 'No positions match your search.' : 'No positions found.' ?>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php 
            // Build base URL with search parameter if it exists
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
      </div>
    </div>
  </div>

  <script src="/javascript/header.js"></script>
  <script src="/javascript/position.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/script.js"></script>
  



</body>
</html>