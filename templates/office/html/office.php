<?php
require __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/editOffFunction.php';
$isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin';
?>

<style>
  <?php if (!$isAdmin): ?>
    /* Hide admin-only columns for non-admin users */
    .officeTable th:nth-child(4),
    .officeTable td:nth-child(4),
    .officeTable th:nth-child(5),
    .officeTable td:nth-child(5) {
      display: none;
    }

    /* Adjust width for non-admin */
    .officeTable th:nth-child(2),
    .officeTable td:nth-child(2),
    .officeTable th:nth-child(3),
    .officeTable td:nth-child(3) {
      width: 50%;
    }
  <?php else: ?>
    /* Default widths for admin users */
    .officeTable th:nth-child(2),
    .officeTable td:nth-child(2) {
      width: 25%;
    }

    .officeTable th:nth-child(3),
    .officeTable td:nth-child(3) {
      width: 30%;
    }

    .officeTable th:nth-child(4),
    .officeTable td:nth-child(4) {
      width: 20%;
    }

    .officeTable th:nth-child(5),
    .officeTable td:nth-child(5) {
      width: 20%;
      min-width: 180px;
    }
  <?php endif; ?>
  
  .table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
  }
  
  .item-count-display {
    background: #f8f9fa;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    color: #495057;
    margin-left: auto;
  }
</style>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSI - Office Management</title>
  <link rel="stylesheet" href="/styles/office.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  <?php require __DIR__ . '/editOff.php'; ?>

  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>

    <div class="con">
    <?php require __DIR__ . '/../../header/html/pageHeader.php'; ?>
      <?php 
        require __DIR__ . '/../../quick-access/access.php'; 
        require __DIR__ . '/../function/fetchOff.php'; 
      ?>

      <div class="tableContainer">
        <div class="searchContainer">
          <input type="text" id="searchOffice" placeholder="Search office..." />
        </div>

        <table class="officeTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Office Name</th>
              <th>Description</th>
              <?php if ($isAdmin): ?>
                <th>Actions</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody id="officeTableBody">
            <?php if (!empty($offices)): ?>
              <?php foreach ($offices as $index => $office): ?>
                <tr>
                  <td><?= $index + 1 ?></td>
                  <td><?= htmlspecialchars($office['office_name']) ?></td>
                  <td><?= htmlspecialchars($office['office_description']) ?: '<em>No description</em>' ?></td>
                  <?php if ($isAdmin): ?>
                    <td>
                      <button
                        class="action-btn edit"
                        data-id="<?= $office['office_id'] ?>"
                        data-title="<?= htmlspecialchars($office['office_name']) ?>"
                        data-description="<?= htmlspecialchars($office['office_description']) ?>">
                        <i class="fas fa-edit"></i>
                        <span class="tooltip">Edit office</span>
                      </button>

                      <button class="action-btn delete"
                        data-id="<?= $office['office_id'] ?>"
                        data-title="<?= htmlspecialchars($office['office_name']) ?>">
                        <i class="fas fa-trash-alt"></i>
                        <span class="tooltip">Delete Office</span>
                      </button>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="<?= $isAdmin ? 4 : 3 ?>" style="text-align: center;">No offices found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>


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
        
          <div></div> <!-- empty div para ma push tong counter sa right -->
          
          <div class="item-count-display" style="background: #f8f9fa; padding: 8px 16px; border-radius: 20px; font-weight: 500; color: #495057;">
            <span id="totalItemsCount"><?= count($offices) ?></span> total items
            <span id="filteredItemsCount" style="display: none;">
              | Showing <span id="visibleItemsCount">0</span> of <span id="totalItemsCount2"><?= count($offices) ?></span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="/javascript/header.js"></script>
  <script src="/javascript/office.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/script.js"></script>

</body>
</html>