<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/editPosFunction.php';
require __DIR__ . '/../function/deletePos.php';
require __DIR__ . '/../function/fetchPos.php';
$isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin';



?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BCSI - Position</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="/styles/position.css">
</head>

<body>
  <?php require __DIR__ . '/editPos.php'; ?>

  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
    <div class="con">
    <?php require __DIR__ . '/../../header/html/pageHeader.php'; ?>
      <?php require __DIR__ . '/../../quick-access/access.php'; ?>

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
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="positionTableBody">
            <?php if (!empty($positions)): ?>
              <?php foreach ($positions as $index => $position): ?>
                <tr>
                  <td><?= $index + 1 ?></td>
                  <td><?= htmlspecialchars($position['position_title']) ?></td>
                  <td><?= htmlspecialchars($position['position_description']) ?: '<em>No description</em>' ?></td>
                  <td>
                    <button class="action-btn edit"
                      data-id="<?= $position['position_id'] ?>"
                      data-title="<?= htmlspecialchars($position['position_title']) ?>"
                      data-description="<?= htmlspecialchars($position['position_description']) ?>">
                      <i class="fas fa-edit"></i><span class="tooltip">Edit Position</span>
                    </button>
                    <button class="action-btn delete"
                      data-id="<?= $position['position_id'] ?>"
                      data-title="<?= htmlspecialchars($position['position_title']) ?>">
                      <i class="fas fa-trash-alt"></i><span class="tooltip">Delete Position</span>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" style="text-align:center;">No positions found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>

        <!-- Add this near your pagination controls -->

      
        <div class="table-footer">
        <div class="pagination-controls">
  <div class="items-per-page-selector">
    <label for="rowsPerPageSelect">Show:</label>
    <select id="rowsPerPageSelect" class="form-select">
      <option value="10">10</option>
      <option value="20">20</option>
      <option value="30">30</option>
      <option value="50">50</option>
    </select>
    <span>entries</span>
  </div>
</div>

          <div></div> <!-- div para ma punta sa right and total items kekw -->
          <div class="item-count-display" style="background: #f8f9fa; padding: 8px 16px; border-radius: 20px; font-weight: 500; color: #495057;">
            <span id="totalItemsCount"><?= count($positions) ?></span> total positions
            <span id="filteredItemsCount" style="display: none;">
              | Showing <span id="visibleItemsCount">0</span> of <span id="totalItemsCount2"><?= count($positions) ?></span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

<!-- style for if ever admin or employee lang mag login ma adjust ang table -->
  <style>
  <?php if (!$isAdmin): ?>
    .positionTable th:nth-child(4),
    .positionTable td:nth-child(4) {
      display: none;
    }
    .positionTable th:nth-child(2),
    .positionTable td:nth-child(2),
    .positionTable th:nth-child(3),
    .positionTable td:nth-child(3) {
      width: 50%;
    }
  <?php else: ?>
    .positionTable th:nth-child(2),
    .positionTable td:nth-child(2) { width: 25%; }
    .positionTable th:nth-child(3),
    .positionTable td:nth-child(3) { width: 40%; }
    .positionTable th:nth-child(4),
    .positionTable td:nth-child(4) { width: 15%; min-width: 100px; }
  <?php endif; ?>
  

</style>


  <script src="/javascript/header.js"></script>
  <script src="/javascript/position.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/script.js"></script>
</body>
</html>