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


$categoryId = $_GET['category_id'] ?? null;
if (!$categoryId) {
    die("Category ID is required.");
}


$brandFilter = $_GET['brand'] ?? null;
$modelFilter = $_GET['model'] ?? null;
$minQty = isset($_GET['min_quantity']) && $_GET['min_quantity'] !== '' ? (int)$_GET['min_quantity'] : null;
$maxQty = isset($_GET['max_quantity']) && $_GET['max_quantity'] !== '' ? (int)$_GET['max_quantity'] : null;
$minCost = isset($_GET['min_cost']) && $_GET['min_cost'] !== '' ? (float)$_GET['min_cost'] : null;
$maxCost = isset($_GET['max_cost']) && $_GET['max_cost'] !== '' ? (float)$_GET['max_cost'] : null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;


$categoryQuery = $conn->prepare("SELECT category_name FROM deped_inventory_item_category WHERE category_id = ?");
$categoryQuery->bind_param("i", $categoryId);
$categoryQuery->execute();
$categoryResult = $categoryQuery->get_result();
$categoryRow = $categoryResult->fetch_assoc();
$categoryName = $categoryRow ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $categoryRow['category_name']) : "Category_" . $categoryId;


$query = "SELECT item_name, brand, model, serial_number, quantity, unit, unit_cost, total_cost, description, date_acquired 
          FROM deped_inventory_items 
          WHERE category_id = ?";
$params = [$categoryId];
$types = 'i';


if (!empty($brandFilter) && $brandFilter !== 'all') {
    $query .= " AND brand = ?";
    $types .= 's';
    $params[] = $brandFilter;
}


if (!empty($modelFilter) && $modelFilter !== 'all') {
    $query .= " AND model = ?";
    $types .= 's';
    $params[] = $modelFilter;
}


if ($minQty !== null) {
    $query .= " AND quantity >= ?";
    $types .= 'i';
    $params[] = $minQty;
}
if ($maxQty !== null) {
    $query .= " AND quantity <= ?";
    $types .= 'i';
    $params[] = $maxQty;
}


if ($minCost !== null) {
    $query .= " AND unit_cost >= ?";
    $types .= 'd';
    $params[] = $minCost;
}
if ($maxCost !== null) {
    $query .= " AND unit_cost <= ?";
    $types .= 'd';
    $params[] = $maxCost;
}


if ($startDate) {
    $query .= " AND date_acquired >= ?";
    $types .= 's';
    $params[] = $startDate;
}
if ($endDate) {
    $query .= " AND date_acquired <= ?";
    $types .= 's';
    $params[] = $endDate;
}


$stmt = $conn->prepare($query);
if (count($params) > 1) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $params[0]);
}
$stmt->execute();
$result = $stmt->get_result();


$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();


$sheet->setCellValue('A1', 'Filter Summary:');
$summary = [];
if (!empty($brandFilter) && $brandFilter !== 'all') $summary[] = "Brand = $brandFilter";
if (!empty($modelFilter) && $modelFilter !== 'all') $summary[] = "Model = $modelFilter";
if ($minQty !== null) $summary[] = "Min Qty = $minQty";
if ($maxQty !== null) $summary[] = "Max Qty = $maxQty";
if ($minCost !== null) $summary[] = "Min Cost = $minCost";
if ($maxCost !== null) $summary[] = "Max Cost = $maxCost";
if ($startDate) $summary[] = "From Date = $startDate";
if ($endDate) $summary[] = "To Date = $endDate";
$sheet->setCellValue('B1', $summary ? implode(', ', $summary) : 'None');


$headers = ['Item Name', 'Brand', 'Model', 'Serial Number', 'Quantity', 'Unit', 'Unit Cost', 'Total Cost', 'Description', 'Date Acquired'];
$sheet->fromArray($headers, null, 'A2');


$sheet->getStyle('A2:J2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);


$rowIndex = 3;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        ucfirst($row['item_name']),
        ucfirst($row['brand']),
        ucfirst($row['model']),
        $row['serial_number'],
        $row['quantity'],
        ($row['unit'] == '0' || $row['unit'] === null || trim($row['unit']) === '') ? 'None' : ucfirst($row['unit']),
        $row['unit_cost'],
        $row['total_cost'],
        $row['description'],
        $row['date_acquired'],
    ], null, "A$rowIndex");

    $sheet->getStyle("A$rowIndex:J$rowIndex")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $rowIndex++;
}


$sheet->getStyle("G3:G$rowIndex")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
$sheet->getStyle("H3:H$rowIndex")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
$sheet->getStyle("J3:J$rowIndex")->getNumberFormat()->setFormatCode('yyyy-mm-dd');


foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}


$filename = $categoryName . "_Inventory_" . date("Y-m-d") . ".xlsx";


header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');


$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;