<?php
require __DIR__ . '/../../../database/dbConnection.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_edit_category'])) {
    $category_id = $_POST['category_id'] ?? '';
    $category_name = trim($_POST['category_name'] ?? '');

    if (empty($category_id) || empty($category_name)) {
        showSweetAlert('error', 'Missing Data', 'Category ID and Name are required.');
        return;
    }

   
    $check = $conn->prepare("SELECT category_id FROM deped_inventory_item_category 
        WHERE LOWER(category_name) = LOWER(?) AND category_id != ?");
    $check->bind_param("ss", $category_name, $category_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        showSweetAlert('info', 'Duplicate Category', 'Another category with the same name already exists.');
        return;
    }


    $stmt = $conn->prepare("UPDATE deped_inventory_item_category 
        SET category_name = ? 
        WHERE category_id = ?");
    $stmt->bind_param("ss", $category_name, $category_id);

    if ($stmt->execute()) {
        showSweetAlert('success', 'Updated Successfully', "Category <b>" . htmlspecialchars($category_name) . "</b> has been updated.", $_SERVER['HTTP_REFERER']);
    } else {
        showSweetAlert('error', 'Update Failed', 'An error occurred: ' . addslashes($conn->error));
    }
}
?>
