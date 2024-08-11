<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['html'])) {
    $tableHtml = $_POST['html'];

    // Set the content type to UTF-8
    header('Content-Type: text/html; charset=utf-8');

    // Create a new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Convert HTML Table to DOMDocument with UTF-8 encoding
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($tableHtml, 'HTML-ENTITIES', 'UTF-8'));

    // Extract Table Rows
    $rows = $dom->getElementsByTagName('tr');

    // Iterate Over Rows and Cells
    $rowIndex = 1; // Excel rows start at 1
    foreach ($rows as $row) {
        $colIndex = 'A'; // Excel columns start at A
        $cells = $row->getElementsByTagName('td');
        if ($cells->length == 0) {
            $cells = $row->getElementsByTagName('th');
        }
        foreach ($cells as $cell) {
            $sheet->setCellValue($colIndex . $rowIndex, $cell->nodeValue);
            $colIndex++;
        }
        $rowIndex++;
    }

    // Write the spreadsheet to a temporary file
    $writer = new Xlsx($spreadsheet);
    $fileName = tempnam(sys_get_temp_dir(), 'xlsx');
    $writer->save($fileName);

    // Return the file as a response
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="export.xlsx"');
    header('Cache-Control: max-age=0');
    readfile($fileName);

    // Remove the temporary file
    unlink($fileName);
    exit;
}
?>
