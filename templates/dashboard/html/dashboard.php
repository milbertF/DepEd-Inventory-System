<?php
require  __DIR__ . '/../../header/html/header.php';

?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BCSI-Dashboard</title>
  <link rel="stylesheet" href="/styles/dashboard.css">
</head>

<body>

  <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
    <div class="con">
    <?php require __DIR__ . '/../../header/html/pageHeader.php'; ?>
      <?php require __DIR__ . '/../../quick-access/access.php'; ?>


      




      <div class="summaryCards">
        <div class="card">
          <h4>Total Items</h4>
          <p>1,240</p>
        </div>
        <div class="card">
          <h4>In Stock</h4>
          <p>980</p>
        </div>
        <div class="card">
          <h4>Low Stock</h4>
          <p>12</p>
        </div>
        <div class="card">
          <h4>Employees</h4>
          <p>28</p>
        </div>
      </div>

      <!-- Low Stock Table -->
      <div class="section">
        <h4>Low Stock Items</h4>
        <table class="dashboardTable">
          <thead>
            <tr>
              <th>Item</th>
              <th>Quantity</th>
              <th>Threshold</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Printer Ink</td>
              <td>2</td>
              <td>5</td>
              <td><span class="tag danger">Low</span></td>
            </tr>
            <tr>
              <td>A4 Bond Paper</td>
              <td>3</td>
              <td>10</td>
              <td><span class="tag warning">Below Limit</span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Activity -->
      <div class="section">
        <h4>Recent Activity</h4>
        <ul class="activityLog">
          <li><strong>July 01:</strong> Pogi si JD</li>


          <li><strong>June 30:</strong> Mark borrowed 1 projector.</li>
          <li><strong>June 29:</strong> Admin added 5 laptops to ICT Room.</li>
          <li><strong>June 28:</strong> Item "Speaker" marked as damaged.</li>
        </ul>
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