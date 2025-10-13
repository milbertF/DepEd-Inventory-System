<?php
require_once __DIR__ . '/../../../config/restrictRoles.php';

restrictRoles(['Employee']);

require  __DIR__ . '/../../header/html/header.php';

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCSI-Request</title>
    <link rel="stylesheet" href="/styles/request.css">
</head>

<body>
    <div class="wrapMain">
        <?php require __DIR__ . '/../../sidebar/html/sidebar.php'; ?>
        <div class="con">
        <?php require __DIR__ . '/../../header/html/pageHeader.php'; ?>


            <?php require __DIR__ . '/../../quick-access/access.php'; ?>

        </div>

    </div>

    <script src="/javascript/header.js"></script>
    <script src="/javascript/sidebar.js"></script>
    <script src="/javascript/script.js"></script>
</body>

</html>