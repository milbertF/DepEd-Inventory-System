<?php
require __DIR__ . '/../../../database/dbConnection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$params = [];
$types = '';
$whereClause = '';

if (!empty($search)) {
    $whereClause = " WHERE office_name LIKE ? OR office_description LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
    $types = 'ss';
}

// Get total count
$countSql = "SELECT COUNT(*) FROM deped_inventory_employee_office $whereClause";
$stmtCount = $conn->prepare($countSql);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$stmtCount->bind_result($total);
$stmtCount->fetch();
$stmtCount->close();

$totalPages = ceil($total / $limit);

// Fetch data
$dataSql = "SELECT office_id, office_name, office_description, created_at 
            FROM deped_inventory_employee_office
            $whereClause
            ORDER BY office_name ASC
            LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($dataSql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

ob_start();
$rowNumber = $offset + 1;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $officeName = ucfirst(htmlspecialchars($row['office_name']));
        $officeDescRaw = $row['office_description'];
        $officeDesc = $officeDescRaw ? ucfirst(htmlspecialchars($officeDescRaw)) : '<em>No description</em>';

        echo "<tr>
                <td>{$rowNumber}</td>
                <td>{$officeName}</td>
                <td>{$officeDesc}</td>
                <td>
                    <button class='action-btn edit'
                      data-id='{$row['office_id']}'
                      data-title='" . htmlspecialchars($row['office_name']) . "'
                      data-description='" . htmlspecialchars($officeDescRaw) . "'>
                      <i class='fas fa-edit'></i>
                    </button>
                    <button class='action-btn delete'
                      data-id='{$row['office_id']}'
                      data-title='" . htmlspecialchars($row['office_name']) . "'>
                      <i class='fas fa-trash-alt'></i>
                    </button>
                </td>
              </tr>";
        $rowNumber++;
    }
} else {
    echo "<tr><td colspan='4' style='text-align:center;'>No matching offices found.</td></tr>";
}

$html = ob_get_clean();

// Pagination
$pagination = '';
if ($totalPages > 1) {
    $baseUrl = "?search=" . urlencode($search) . "&";
    $pagination .= '<div class="pagination">';

    if ($page > 1) {
        $pagination .= '<a href="' . $baseUrl . 'page=' . ($page - 1) . '" class="prev-next"><i class="fas fa-chevron-left"></i></a>';
    } else {
        $pagination .= '<a class="prev-next disabled"><i class="fas fa-chevron-left"></i></a>';
    }

    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
        $active = ($i == $page) ? 'active' : '';
        $pagination .= "<a href='{$baseUrl}page={$i}' class='{$active}'>{$i}</a>";
    }

    if ($page < $totalPages) {
        $pagination .= '<a href="' . $baseUrl . 'page=' . ($page + 1) . '" class="prev-next"><i class="fas fa-chevron-right"></i></a>';
    } else {
        $pagination .= '<a class="prev-next disabled"><i class="fas fa-chevron-right"></i></a>';
    }

    $pagination .= '</div>';
}

echo json_encode([
    'html' => $html,
    'pagination' => $pagination,
    'total' => $total
]);
