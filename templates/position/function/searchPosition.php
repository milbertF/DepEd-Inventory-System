<?php
require __DIR__ . '/../../../database/dbConnection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Base query
$positionQuery = "FROM deped_inventory_employee_position";
$params = [];
$types = '';

if (!empty($search)) {
    $positionQuery .= " WHERE position_title LIKE ? OR position_description LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
    $types = 'ss';
}

// Get total count
$countQuery = "SELECT COUNT(*) " . $positionQuery;
$stmtCount = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$stmtCount->bind_result($total);
$stmtCount->fetch();
$stmtCount->close();

$totalPages = ceil($total / $limit);

// Fetch paginated results
$dataQuery = "SELECT position_id, position_title, position_description, created_at " . $positionQuery . " ORDER BY position_title ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($dataQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

ob_start();
$rowNumber = $offset + 1;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $positionId = htmlspecialchars($row['position_id']);
        $title = ucfirst(htmlspecialchars($row['position_title']));
        $descRaw = $row['position_description'];
        $desc = $descRaw ? ucfirst(htmlspecialchars($descRaw)) : '<em>No description</em>';
        $createdDate = isset($row['created_at']) ? date("M d, Y", strtotime($row['created_at'])) : 'N/A';

        echo "<tr>
                <td>{$rowNumber}</td>
                <td>{$title}</td>
                <td>{$desc}</td>
                <td>
                    <button class='action-btn edit'
                      data-id='{$positionId}'
                      data-title='" . htmlspecialchars($row['position_title']) . "'
                      data-description='" . htmlspecialchars($descRaw) . "'>
                      <i class='fas fa-edit'></i>
                      <span class='tooltip'>Edit Position</span>
                    </button>
                    <button class='action-btn delete'
                      data-id='{$positionId}'
                      data-title='" . htmlspecialchars($row['position_title']) . "'>
                      <i class='fas fa-trash-alt'></i>
                      <span class='tooltip'>Delete Position</span>
                    </button>
                </td>
              </tr>";
        $rowNumber++;
    }
} else {
    echo "<tr><td colspan='4' style='text-align:center;'>" .
         (empty($search) ? 'No positions found.' : 'No matching positions found.') .
         "</td></tr>";
}

$html = ob_get_clean();


$pagination = '';
if ($totalPages > 1) {
    $baseUrl = "?search=" . urlencode($search) . "&";
    $pagination .= '<div class="pagination">';

    if ($page > 1) {
        $pagination .= '<a href="' . $baseUrl . 'page=' . ($page - 1) . '" class="prev-next" data-page="' . ($page - 1) . '"><i class="fas fa-chevron-left"></i></a>';
    } else {
        $pagination .= '<a class="prev-next disabled"><i class="fas fa-chevron-left"></i></a>';
    }

    for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++) {
        $activeClass = ($i == $page) ? 'active' : '';
        $pagination .= '<a href="' . $baseUrl . 'page=' . $i . '" class="' . $activeClass . '" data-page="' . $i . '">' . $i . '</a>';
    }

    if ($page < $totalPages) {
        $pagination .= '<a href="' . $baseUrl . 'page=' . ($page + 1) . '" class="prev-next" data-page="' . ($page + 1) . '"><i class="fas fa-chevron-right"></i></a>';
    } else {
        $pagination .= '<a class="prev-next disabled"><i class="fas fa-chevron-right"></i></a>';
    }

    $pagination .= '</div>';
}

echo json_encode([
    'html' => $html,
    'pagination' => $pagination
]);
?>
