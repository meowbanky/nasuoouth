<?php
// Load error configuration
require_once __DIR__ . '/config/error_config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['UserID'])) {
    header("location: index.php");
    exit();
}

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/class/DataBaseHandler.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Get period ID from request
$periodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : null;
$periodId = isset($_POST['period_id']) ? (int)$_POST['period_id'] : $periodId;

if (!$periodId) {
    die("Error: Period ID is required");
}

try {
    $db = new DatabaseHandler();
    
    // Get period information
    $periodQuery = "SELECT Periodid, PayrollPeriod, PhysicalYear, PhysicalMonth 
                    FROM tbpayrollperiods 
                    WHERE Periodid = ?";
    $periodStmt = $db->pdo->prepare($periodQuery);
    $periodStmt->execute([$periodId]);
    $periodInfo = $periodStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$periodInfo) {
        die("Error: Period not found");
    }
    
    // Query to get monthly report data
    // This query gets:
    // - Member name
    // - Period name
    // - Month contribution (for this period)
    // - Contribution balance (cumulative up to this period)
    // - Month loan repayment (for this period)
    // - Loan balance (cumulative loan - repayments up to this period)
    // - Total Contribution (cumulative contributions)
    
    // Use tbl_personalinfo table from the correct SQL dump
    // Columns: staff_id, Fname, Mname, Lname
    $query = "
        SELECT 
            p.staff_id AS StaffID,
            CONCAT(
                IFNULL(p.Lname, ''), ', ',
                IFNULL(p.Fname, ''), ' ',
                IFNULL(p.Mname, '')
            ) AS MemberName,
            per.PayrollPeriod AS PeriodName,
            -- Month contribution (for this specific period)
            COALESCE(t_period.Contribution, 0) AS MonthContribution,
            -- Contribution balance (cumulative up to this period)
            COALESCE(
                (SELECT SUM(t2.Contribution) 
                 FROM tlb_mastertransaction t2 
                 WHERE t2.staff_id = p.staff_id 
                 AND t2.periodid <= ?),
                0
            ) AS ContributionBalance,
            -- Month loan repayment (for this specific period)
            COALESCE(t_period.loanRepayment, 0) AS MonthLoanRepayment,
            -- Loan balance (cumulative loan amount - repayments up to this period)
            COALESCE(
                (SELECT SUM(t2.loanAmount + t2.interest) 
                 FROM tlb_mastertransaction t2 
                 WHERE t2.staff_id = p.staff_id 
                 AND t2.periodid <= ?) -
                (SELECT SUM(t2.loanRepayment) 
                 FROM tlb_mastertransaction t2 
                 WHERE t2.staff_id = p.staff_id 
                 AND t2.periodid <= ?),
                0
            ) AS LoanBalance,
            -- Total Contribution (same as ContributionBalance)
            COALESCE(
                (SELECT SUM(t2.Contribution) 
                 FROM tlb_mastertransaction t2 
                 WHERE t2.staff_id = p.staff_id 
                 AND t2.periodid <= ?),
                0
            ) AS TotalContribution
        FROM 
            tlb_mastertransaction t_period
        INNER JOIN 
            tbl_personalinfo p ON p.staff_id = t_period.staff_id
        INNER JOIN 
            tbpayrollperiods per ON t_period.periodid = per.Periodid
        WHERE 
            t_period.periodid = ?
            AND p.Status = 1
        ORDER BY 
            p.Lname, p.Fname
    ";
    
    $stmt = $db->pdo->prepare($query);
    $stmt->execute([$periodId, $periodId, $periodId, $periodId, $periodId]);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if data exists
    if (empty($reportData)) {
        die("No data found for period ID: $periodId. Please verify that transactions exist for this period.");
    }
    
    // Create new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Monthly Report');
    
    // Set report header
    $sheet->setCellValue('A1', 'OOUTH NASU - MONTHLY REPORT');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension('1')->setRowHeight(25);
    
    // Period information
    $sheet->setCellValue('A2', 'Period: ' . $periodInfo['PayrollPeriod']);
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension('2')->setRowHeight(20);
    
    $sheet->setCellValue('A3', 'Generated: ' . date('F j, Y h:i A'));
    $sheet->mergeCells('A3:H3');
    $sheet->getStyle('A3')->getFont()->setSize(10);
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension('3')->setRowHeight(18);
    
    // Column headers
    $headers = [
        'A' => 'S/N',
        'B' => 'Staff ID',
        'C' => 'Member Name',
        'D' => 'Period Name',
        'E' => 'Month Contribution',
        'F' => 'Contribution Balance',
        'G' => 'Month Loan Repayment',
        'H' => 'Loan Balance',
        'I' => 'Total Contribution'
    ];
    
    $row = 5;
    foreach ($headers as $col => $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($col . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }
    $sheet->getRowDimension($row)->setRowHeight(25);
    
    // Data rows
    $row = 6;
    $sn = 1;
    $totalMonthContribution = 0;
    $totalContributionBalance = 0;
    $totalMonthLoanRepayment = 0;
    $totalLoanBalance = 0;
    $grandTotalContribution = 0;
    
    foreach ($reportData as $data) {
        $sheet->setCellValue('A' . $row, $sn);
        $sheet->setCellValue('B' . $row, $data['StaffID'] ?? '');
        $sheet->setCellValue('C' . $row, trim($data['MemberName']));
        $sheet->setCellValue('D' . $row, $data['PeriodName'] ?? $periodInfo['PayrollPeriod']);
        
        // Format currency values
        $monthContribution = (float)($data['MonthContribution'] ?? 0);
        $contributionBalance = (float)($data['ContributionBalance'] ?? 0);
        $monthLoanRepayment = (float)($data['MonthLoanRepayment'] ?? 0);
        $loanBalance = (float)($data['LoanBalance'] ?? 0);
        $totalContribution = (float)($data['TotalContribution'] ?? 0);
        
        $sheet->setCellValue('E' . $row, $monthContribution);
        $sheet->setCellValue('F' . $row, $contributionBalance);
        $sheet->setCellValue('G' . $row, $monthLoanRepayment);
        $sheet->setCellValue('H' . $row, $loanBalance);
        $sheet->setCellValue('I' . $row, $totalContribution);
        
        // Format currency columns
        foreach (['E', 'F', 'G', 'H', 'I'] as $col) {
            $sheet->getStyle($col . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }
        
        // Add borders
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
            $sheet->getStyle($col . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }
        
        // Alternate row colors
        if ($row % 2 == 0) {
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F2F2F2');
            }
        }
        
        // Accumulate totals
        $totalMonthContribution += $monthContribution;
        $totalContributionBalance += $contributionBalance;
        $totalMonthLoanRepayment += $monthLoanRepayment;
        $totalLoanBalance += $loanBalance;
        $grandTotalContribution += $totalContribution;
        
        $row++;
        $sn++;
    }
    
    // Add totals row
    $totalRow = $row;
    $sheet->setCellValue('A' . $totalRow, 'TOTAL');
    $sheet->mergeCells('A' . $totalRow . ':D' . $totalRow);
    $sheet->setCellValue('E' . $totalRow, $totalMonthContribution);
    $sheet->setCellValue('F' . $totalRow, $totalContributionBalance);
    $sheet->setCellValue('G' . $totalRow, $totalMonthLoanRepayment);
    $sheet->setCellValue('H' . $totalRow, $totalLoanBalance);
    $sheet->setCellValue('I' . $totalRow, $grandTotalContribution);
    
    // Style totals row
    $sheet->getStyle('A' . $totalRow . ':I' . $totalRow)->getFont()->setBold(true);
    $sheet->getStyle('A' . $totalRow . ':I' . $totalRow)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('D9E1F2');
    foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
        $sheet->getStyle($col . $totalRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($col . $totalRow)->getNumberFormat()
            ->setFormatCode('#,##0.00');
    }
    $sheet->getRowDimension($totalRow)->setRowHeight(20);
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(12);
    $sheet->getColumnDimension('C')->setWidth(35);
    $sheet->getColumnDimension('D')->setWidth(25);
    $sheet->getColumnDimension('E')->setWidth(18);
    $sheet->getColumnDimension('F')->setWidth(20);
    $sheet->getColumnDimension('G')->setWidth(20);
    $sheet->getColumnDimension('H')->setWidth(18);
    $sheet->getColumnDimension('I')->setWidth(20);
    
    // Freeze header row
    $sheet->freezePane('A6');
    
    // Output the file
    $filename = 'OOUTH_NASU_Monthly_Report_' . $periodInfo['PayrollPeriod'] . '_' . date('Y-m-d') . '.xlsx';
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    die("Error generating report: " . $e->getMessage());
}
?>