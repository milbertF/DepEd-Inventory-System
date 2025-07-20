<?php
require __DIR__ . '/../../../database/dbConnection.php';

$categories = [];

$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;


$totalQuery = "SELECT COUNT(*) AS total FROM deped_inventory_item_category";
$totalResult = $conn->query($totalQuery);
$total = ($totalResult && $row = $totalResult->fetch_assoc()) ? (int)$row['total'] : 0;

$totalPages = ceil($total / $limit);


$categoryQuery = "SELECT category_id, category_name, created_at 
                  FROM deped_inventory_item_category 
                  ORDER BY category_name ASC 
                  LIMIT ? OFFSET ?";
$stmt = $conn->prepare($categoryQuery);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'category_id' => $row['category_id'] ?? '',
            'category_name' => $row['category_name'] ?? '',
            'created_at' => $row['created_at'] ?? ''
        ];
    }
}
?>
