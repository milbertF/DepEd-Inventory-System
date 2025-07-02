<?php
require __DIR__ . '/../../dashboard/html/addEmployee.php'; 
require __DIR__ . '/../../dashboard/html/addPosition.php';
require __DIR__ . '/../../dashboard/html/addOffice.php'; 
require __DIR__ . '/../function/fetchOff.php';
require __DIR__ . '/../function/editOffFunction.php';
require_once __DIR__ . '/../../../config/authProtect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DIS-Office</title>
  <link rel="stylesheet" href="/styles/office.css" />

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php require __DIR__ . '/editOff.php'; ?>

  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
    <div class="con">
      <h3>Office</h3>

      <?php require __DIR__ . '/../../quick-access/access.php'; ?>

      <div class="tableContainer">
        <div class="searchContainer">
          <input type="text" id="searchOffice" placeholder="Search offices..." />
        </div>

        <table class="officeTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Office Name</th>
              <th>Location</th>
              <th>Date Added</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($offices)): ?>
              <?php foreach ($offices as $index => $office): ?>
                <tr>
                  <td><?= $index + 1 ?></td>
                  <td><?= htmlspecialchars($office['office_name']) ?></td>
                  <td><?= htmlspecialchars($office['office_location']) ?: '<em>No description</em>' ?></td>
                  <td><?= isset($office['created_at']) ? date("M-d-Y", strtotime($office['created_at'])) : 'N/A' ?></td>
                  <td>
                    <button 
                      class="action-btn edit"
                      data-id="<?= $office['office_id'] ?>"
                      data-title="<?= htmlspecialchars($office['office_name']) ?>"
                      data-description="<?= htmlspecialchars($office['office_location']) ?>"
                    >
                      <i class="fas fa-edit"></i>
                    </button>

                    <button class="action-btn delete" 
                      data-id="<?= $office['office_id'] ?>" 
                      data-title="<?= htmlspecialchars($office['office_name']) ?>">
                      <i class="fas fa-trash-alt"></i>
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
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a 
        href="?page=<?= $i ?>" 
        class="<?= ($i == $page) ? 'active' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
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
