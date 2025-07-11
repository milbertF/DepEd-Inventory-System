<?php


require __DIR__ . '/../../dashboard/html/addEmployee.php'; 
require  __DIR__ . '/../../dashboard/html/addPosition.php';
require __DIR__ . '/../../dashboard/html/addOffice.php'; 
require __DIR__ . '/../function/fetchEmp.php';
require_once __DIR__ . '/../../../config/authProtect.php';
require  __DIR__ . '/../function/editEmpFunction.php';
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIS-Employee</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/styles/employee.css">
    <link rel="stylesheet" href="/styles/empModal.css">
    <link rel="stylesheet" href="/styles/addEmployee.css">

<body>
<?php require __DIR__ . '/empModal.php'; ?>
<?php require __DIR__ . '/editEmp.php'; ?>


    <div class="wrapMain">
        
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
        <div class="con">
            <h3>Employee</h3>
            
            
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
        <th style ="display:none;">Contact #</th>
        <th  style ="display:none;">Address</th>
        <th>Date Added</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($employees)): ?>
        <?php foreach ($employees as $index => $employee): ?>
          <tr>
            <td><?= $start + $index + 1 ?></td>

     
            <td>
              <img 
              src="<?= !empty($employee['profile_photo']) ? htmlspecialchars($employee['profile_photo']) : '/images/user-profile/default-image.jpg' ?>"

                alt="Profile Photo" 
                class="employee-photo"
              >
            </td>

       
            <td><?= htmlspecialchars(ucwords(strtolower("{$employee['first_name']} {$employee['middle_name']} {$employee['last_name']}"))) ?></td>

            <td><?= htmlspecialchars(strtolower($employee['role']) === 'deactivate' ? 'Deactivated' : ($employee['role'] ?? '—')) ?></td>

            <td><?= htmlspecialchars($employee['position_title'] ?? '—') ?></td>
            <td><?= htmlspecialchars($employee['office_name'] ?? '—') ?></td>
            <td  style ="display:none;"><?= htmlspecialchars($employee['contact_number'] ?? '—') ?></td>
            <td  style ="display:none;">
  <?= htmlspecialchars(implode(', ', array_map('ucwords', array_map('trim', explode(',', $employee['address'] ?? '—'))))) ?>
</td>

            <td><?= isset($employee['created_at']) ? date("M-d-Y", strtotime($employee['created_at'])) : 'N/A' ?></td>

            <td>

            <button 
    class="action-btn view" 
    data-id="<?= $employee['info_id'] ?>"
    data-user-id="<?= $employee['user_id'] ?>"
    title="View Employee"
  >
    <i class="fas fa-eye"></i>
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
>
  <i class="fas fa-edit"></i>
</button>





              <button 
                class="action-btn delete" 
                data-id="<?= $employee['info_id'] ?>" 
                data-name="<?= htmlspecialchars("{$employee['first_name']} {$employee['last_name']}") ?>">
                <i class="fas fa-trash-alt"></i>
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
    <script src="/javascript/employee.js"></script>
</body>
</html>
