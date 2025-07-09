
<?php


require __DIR__ . '/../../dashboard/html/addEmployee.php'; 
require  __DIR__ . '/../../dashboard/html/addPosition.php';
require __DIR__ . '/../../dashboard/html/addOffice.php'; 


require_once __DIR__ . '/../../../config/authProtect.php';
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIS-Request</title>
    <link rel="stylesheet" href="/styles/request.css">
</head>
<body>
    <div class="wrapMain">
    <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
        <div class="con">
            <h3>Request</h3>

            
            <?php require __DIR__ . '/../../quick-access/access.php'; ?>
            
        </div>
         
    </div>
   
    <script src="/javascript/header.js"></script>
    <script src="/javascript/sidebar.js"></script>
    <script src="/javascript/script.js"></script>
</body>
</html>