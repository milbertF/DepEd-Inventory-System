
<?php


session_start();

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DIS-Dashboard</title>
  <link rel="stylesheet" href="/styles/dashboard.css">
</head>
<body>

  <?php require __DIR__ . '/addEmployee.php'; ?>
  <?php require  __DIR__ . '/addPosition.php'; ?>
  <?php require __DIR__ . '/addOffice.php'; ?>


  <div class="wrapMain">
  <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
    <div class="con">
      <h3>Dashboard</h3>
      <div class="quickAccess">
        <div class="access">
          <i class="fas fa-list"></i>
          <p>Add Items</p>
        </div>
        <div class="access" onclick="addEmployee()">
          <i class="fas fa-user-tie"></i>
          <p>Add Employee</p>
        </div>
        <div class="access" onclick="addPosition()">
          <i class="fa-solid fa-street-view"></i>
          <p>Add Position</p>
        </div>
        <div class="access" onclick="addOffice()">
          <i class="fa-solid fa-building"></i>
          <p>Add Office</p>
        </div>
      </div>
    </div>
  </div>

  <script src="/javascript/script.js"></script>
  <script src="/javascript/header.js"></script>
  <script src="/javascript/sidebar.js"></script>
  <script src="/javascript/settings.js"></script>
  <script src="/javascript/addEmployee.js"></script>

</body>
</html>
