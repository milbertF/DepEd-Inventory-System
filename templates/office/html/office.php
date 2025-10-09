<?php
require  __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/editOffFunction.php';
$isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin';

?>

<style>
  <?php if (!$isAdmin): ?>
    /* Hide 4th and 5th columns for non-admin users */
    .positionTable th:nth-child(4),
    .positionTable td:nth-child(4),
    .positionTable th:nth-child(5),
    .positionTable td:nth-child(5),
    .officeTable th:nth-child(4),
    .officeTable td:nth-child(4),
    .officeTable th:nth-child(5),
    .officeTable td:nth-child(5) {
      display: none;
    }

    /* Make 2nd and 3rd columns take equal width */
    .positionTable th:nth-child(2),
    .positionTable td:nth-child(2),
    .positionTable th:nth-child(3),
    .positionTable td:nth-child(3),
    .officeTable th:nth-child(2),
    .officeTable td:nth-child(2),
    .officeTable th:nth-child(3),
    .officeTable td:nth-child(3) {
      width: 50%;
    }
  <?php else: ?>
    /* Default widths for admin users */
    .positionTable th:nth-child(2),
    .positionTable td:nth-child(2),
    .officeTable th:nth-child(2),
    .officeTable td:nth-child(2) {
      width: 25%;
    }

    .positionTable th:nth-child(3),
    .positionTable td:nth-child(3),
    .officeTable th:nth-child(3),
    .officeTable td:nth-child(3) {
      width: 30%;
    }

    .positionTable th:nth-child(4),
    .positionTable td:nth-child(4),
    .officeTable th:nth-child(4),
    .officeTable td:nth-child(4) {
      width: 20%;
    }

    .positionTable th:nth-child(5),
    .positionTable td:nth-child(5),
    .officeTable th:nth-child(5),
    .officeTable td:nth-child(5) {
      width: 20%;
      min-width: 180px;
    }
  <?php endif; ?>
</style>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BCSI-Office</title>
  <link rel="stylesheet" href="/styles/office.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

  <?php require __DIR__ . '/editOff.php'; ?>

  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
    <div class="con">
      <h3>Office</h3>

      <?php 
      
      require __DIR__ . '/../../quick-access/access.php'; 
      require __DIR__ . '/../function/fetchOff.php';
      
      ?>


<div class="tableContainer">
        <div class="searchContainer">
          <input type="text" id="searchOffice" placeholder="Search office..." 
                 value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" />
        </div>

        <table class="officeTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Office Name</th>
              <th>Description</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($offices)): ?>
              <?php foreach ($offices as $index => $office): ?>
                <tr>
                <td><?= ($page - 1) * $limit + $index + 1 ?></td>

                  <td><?= htmlspecialchars($office['office_name']) ?></td>
                  <td><?= htmlspecialchars($office['office_description']) ?: '<em>No description</em>' ?></td>
          
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
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" style="text-align: center;">No offices found.</td>
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
      </div>
    </div>
  </div>

  <script src="/javascript/header.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/script.js"></script>
  <script src="/javascript/office.js"></script>
</body>

</html>