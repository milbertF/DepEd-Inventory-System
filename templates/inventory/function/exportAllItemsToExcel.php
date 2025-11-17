<?php
require __DIR__ . '/../../../database/dbConnection.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

ob_end_clean();

$minQty = isset($_GET['min_quantity']) && $_GET['min_quantity'] !== '' ? (int)$_GET['min_quantity'] : null;
$maxQty = isset($_GET['max_quantity']) && $_GET['max_quantity'] !== '' ? (int)$_GET['max_quantity'] : null;
$minCost = isset($_GET['min_cost']) && $_GET['min_cost'] !== '' ? (float)$_GET['min_cost'] : null;
$maxCost = isset($_GET['max_cost']) && $_GET['max_cost'] !== '' ? (float)$_GET['max_cost'] : null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$statuses = isset($_GET['status']) ? (array)$_GET['status'] : [];

// Build query
$query = "SELECT item_name, brand, model, serial_number, total_quantity, available_quantity, unit, unit_cost, total_cost, description, date_acquired, item_status
          FROM deped_inventory_items WHERE 1=1";
$params = [];
$types = '';

// Status filter
if (!empty($statuses)) {
    $placeholders = str_repeat('?,', count($statuses) - 1) . '?';
    $query .= " AND item_status IN ($placeholders)";
    $types .= str_repeat('s', count($statuses));
    $params = array_merge($params, $statuses);
}

// Quantity filter
if ($minQty !== null) { $query .= " AND total_quantity >= ?"; $types .= 'i'; $params[] = $minQty; }
if ($maxQty !== null) { $query .= " AND total_quantity <= ?"; $types .= 'i'; $params[] = $maxQty; }

// Unit cost filter
if ($minCost !== null) { $query .= " AND unit_cost >= ?"; $types .= 'd'; $params[] = $minCost; }
if ($maxCost !== null) { $query .= " AND unit_cost <= ?"; $types .= 'd'; $params[] = $maxCost; }

// Date acquired filter
if ($startDate) { $query .= " AND date_acquired >= ?"; $types .= 's'; $params[] = $startDate; }
if ($endDate) { $query .= " AND date_acquired <= ?"; $types .= 's'; $params[] = $endDate; }

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'Filter Summary:');
$summary = [];
if (!empty($statuses)) $summary[] = "Status: " . implode(', ', $statuses);
if ($minQty !== null) $summary[] = "Min Qty = $minQty";
if ($maxQty !== null) $summary[] = "Max Qty = $maxQty";
if ($minCost !== null) $summary[] = "Min Cost = $minCost";
if ($maxCost !== null) $summary[] = "Max Cost = $maxCost";
if ($startDate) $summary[] = "From Date = $startDate";
if ($endDate) $summary[] = "To Date = $endDate";
$sheet->setCellValue('B1', $summary ? implode(', ', $summary) : 'None');

// Headers - Added "Status" column
$headers = ['Item Name','Brand','Model','Serial Number','Total Quantity','Available Quantity','Unit','Unit Cost','Total Cost','Description','Date Acquired','Status'];
$sheet->fromArray($headers, null, 'A2');

// Updated range from K2 to L2 to include Status column
$sheet->getStyle('A2:L2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// Fill data
$rowIndex = 3;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        $row['item_name'],
        $row['brand'],
        $row['model'],
        $row['serial_number'],
        $row['total_quantity'], // Total Quantity
        $row['available_quantity'], // Available Quantity
        $row['unit'] ?: 'None',
        $row['unit_cost'],
        $row['total_cost'],
        $row['description'],
        $row['date_acquired'],
        $row['item_status'] // Status
    ], null, "A$rowIndex");

    // Updated range from K to L to include Status column
    $sheet->getStyle("A$rowIndex:L$rowIndex")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $rowIndex++;
}

// Updated column ranges for number formatting
$sheet->getStyle("H3:H$rowIndex")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00); // Unit Cost
$sheet->getStyle("I3:I$rowIndex")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00); // Total Cost
$sheet->getStyle("K3:K$rowIndex")->getNumberFormat()->setFormatCode('yyyy-mm-dd'); // Date Acquired

// Updated range to include L column
foreach (range('A','L') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

$filename = "All_Items_Inventory_" . date("Y-m-d") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>