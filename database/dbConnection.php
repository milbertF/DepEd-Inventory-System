<?php

require_once __DIR__ . '/../config/security.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


$host = 'localhost';          
$db   = 'deped_inventory_db';  
$user = 'root';        
$pass = '';   


$conn = new mysqli($host, $user, $pass, $db);


$conn->set_charset('utf8mb4');


if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection failed. Please contact JD POGI.");
}
?>
