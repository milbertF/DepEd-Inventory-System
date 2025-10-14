<?php


require_once __DIR__ . '/../../../config/restrictRoles.php';

restrictRoles(['Employee']);




require  __DIR__ . '/../../header/html/header.php';
require __DIR__ . '/../function/fetchEmp.php';
require_once __DIR__ . '/../../../config/authProtect.php';
require  __DIR__ . '/../function/editEmpFunction.php';
require __DIR__ . '/../../settings/settings.php';

?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BCSI-Employee</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="/styles/employee.css">
  <link rel="stylesheet" href="/styles/empItemTable.css">
  <link rel="stylesheet" href="/styles/empModal.css">
  <link rel="stylesheet" href="/styles/addEmployee.css">

<body>
  <?php require __DIR__ . '/empModal.php'; ?>
  <?php require __DIR__ . '/editEmp.php'; ?>


  <div class="wrapMain">

    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
    <div class="con">
    <?php require __DIR__ . '/../../header/html/pageHeader.php'; ?>


      <?php require __DIR__ . '/../../quick-access/access.php'; ?>
      <div class="tableContainer">

        <div class="searchContainer">
          <input type="text" id="searchEmployee" placeholder="Search employees..." />
        </div>

        <table class="employeeTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Photo</th>
              <th>Full Name</th>
              <th>Account Role</th>
              <th>Position</th>
              <th>Office</th>
              <th style="display:none;">Contact #</th>
              <th style="display:none;">Address</th>
              <th>Date Added</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($employees)): ?>
              <?php foreach ($employees as $index => $employee): ?>
                <tr>
                <td><?= ($page - 1) * $limit + $index + 1 ?></td>



                  <td>
                    <img
                      src="<?= !empty($employee['profile_photo']) ? htmlspecialchars($employee['profile_photo']) : '/images/user-profile/default-image.jpg' ?>"

                      alt="Profile Photo"
                      class="employee-photo">
                  </td>


                  <td><?= htmlspecialchars(ucwords(strtolower("{$employee['first_name']} {$employee['middle_name']} {$employee['last_name']}"))) ?></td>

                  <td>
  <?php
    $role = $employee['role'] ?? '—';
    if (strtolower($role) === 'deactivate') {
        echo 'Deactivated';
    } else {
    
        $formattedRole = ucwords(preg_replace('/([a-z])([A-Z])/', '$1 $2', $role));
        echo htmlspecialchars($formattedRole);
    }
  ?>
</td>


                  <td><?= htmlspecialchars($employee['position_title'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($employee['office_name'] ?? '—') ?></td>
                  <td style="display:none;"><?= htmlspecialchars($employee['contact_number'] ?? '—') ?></td>
                  <td style="display:none;">
                    <?= htmlspecialchars(implode(', ', array_map('ucwords', array_map('trim', explode(',', $employee['address'] ?? '—'))))) ?>
                  </td>

                  <td><?= isset($employee['created_at']) ? date("M-d-Y", strtotime($employee['created_at'])) : 'N/A' ?></td>

                  <td>

                  <button
  class="action-btn view"
  data-id="<?= $employee['info_id'] ?>"
  data-user-id="<?= $employee['user_id'] ?>"
  data-role="<?= htmlspecialchars($formattedRole) ?>"
  title="View Employee">
  <i class="fas fa-eye"></i>
  <span class="tooltip">View Profile</span>
</button>
                    <button
                      class="action-btn edit"
                      data-id="<?= $employee['info_id'] ?>"
                      data-user-id="<?= $employee['user_id'] ?>"
                      data-role="<?= $employee['role'] ?>"
                      data-photo="<?= htmlspecialchars($employee['profile_photo'] ?? '') ?>"
                      data-address="<?= htmlspecialchars($employee['address'] ?? '') ?>"
                      data-position-id="<?= $employee['position_id'] ?>"
                      data-office-id="<?= $employee['office_id'] ?>"
                      title="Edit Employee">
                      <i class="fas fa-edit"></i>
                      <span class="tooltip">Edit Employee</span>
                    </button>





                    <button
                      class="action-btn delete"
                      data-id="<?= $employee['info_id'] ?>"
                      data-name="<?= htmlspecialchars("{$employee['first_name']} {$employee['last_name']}") ?>"
                      title="Delete Employee">
                      <i class="fas fa-trash-alt"></i>
                      <span class="tooltip">Delete Employee</span>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" style="text-align: center;">No employees found.</td>
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

    if ($page > 3): ?>
      <a href="?page=1">1</a>
      <?php if ($page > 4): ?>
        <span class="ellipsis">...</span>
      <?php endif; ?>
    <?php endif; ?>

    <?php 
    
    for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
      <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>

    <?php 
   
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




    </div>
  </div>







  <script src="/javascript/header.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/script.js"></script>
  <script src="/javascript/employee.js"></script>
</body>

</html>