<?php
require __DIR__ . '/../../../database/dbConnection.php';

if (isset($_GET['id'])) {
    $info_id = $_GET['id'];

   
    $stmt = $conn->prepare("SELECT user_id FROM deped_inventory_user_info WHERE info_id = ?");
    $stmt->bind_param("i", $info_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];

        $stmt_info = $conn->prepare("DELETE FROM deped_inventory_user_info WHERE info_id = ?");
        $stmt_info->bind_param("i", $info_id);

     
        $stmt_user = $conn->prepare("DELETE FROM deped_inventory_users WHERE user_id = ?");
        $stmt_user->bind_param("i", $user_id);

        if ($stmt_info->execute() && $stmt_user->execute()) {
            header("Location: /employee?deleted=1");
            exit;
        } else {
            header("Location: /employee?deleted=0");
            return;
        }
    } else {
       
        header("Location: /employee?deleted=0");
        return;
    }
}
?>
