<?php
// Load error configuration (suppresses deprecation warnings from vendor packages)
require_once __DIR__ . '/config/error_config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['UserID'])) {
    die("Unauthorized: Please login first");
}

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/class/DataBaseHandler.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\PageMargins;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get parameters from POST request
$periodId = isset($_POST['period_id']) ? (int)$_POST['period_id'] : null;
$email = isset($_POST['email']) ? $_POST['email'] : '';

if (!$periodId) {
    die("Error: Period ID is required");
}

if (empty($email)) {
    die("Error: Email address is required");
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
    $query = "
        SELECT 
            p.staff_id AS StaffID,
            CONCAT(
                IFNULL(p.Lname, ''), ', ',
                IFNULL(p.Fname, ''), ' ',
                IFNULL(p.Mname, '')
            ) AS MemberName,
            per.PayrollPeriod AS PeriodName,
            COALESCE(t_period.Contribution, 0) AS MonthContribution,
            COALESCE(
                (SELECT SUM(t2.Contribution) 
                 FROM tlb_mastertransaction t2 
                 WHERE t2.staff_id = p.staff_id 
                 AND t2.periodid <= ?),
                0
            ) AS ContributionBalance,
            COALESCE(t_period.loanRepayment, 0) AS MonthLoanRepayment,
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
            COALESCE(t_period.Contribution, 0) + COALESCE(t_period.loanRepayment, 0) AS TotalContribution
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
            p.staff_id
    ";
    
    $stmt = $db->pdo->prepare($query);
    $stmt->execute([$periodId, $periodId, $periodId, $periodId]);
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
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension('1')->setRowHeight(25);
    
    // Period information - format to short date
    $periodDate = '';
    if (!empty($periodInfo['PhysicalMonth']) && !empty($periodInfo['PhysicalYear'])) {
        $month = trim($periodInfo['PhysicalMonth']);
        $year = trim($periodInfo['PhysicalYear']);
        $monthNum = date('n', strtotime($month . ' 1'));
        if ($monthNum) {
            $shortMonth = date('M', mktime(0, 0, 0, $monthNum, 1));
            $periodDate = $shortMonth . ' ' . $year;
        } else {
            $periodDate = substr($month, 0, 3) . ' ' . $year;
        }
    } else {
        $periodDate = $periodInfo['PayrollPeriod'];
        if (preg_match('/(\w+)\s*-\s*(\d{4})/i', $periodDate, $matches)) {
            $month = $matches[1];
            $year = $matches[2];
            $monthNum = date('n', strtotime($month . ' 1'));
            if ($monthNum) {
                $shortMonth = date('M', mktime(0, 0, 0, $monthNum, 1));
                $periodDate = $shortMonth . ' ' . $year;
            }
        }
    }
    $sheet->setCellValue('A2', 'Period: ' . $periodDate);
    $sheet->mergeCells('A2:I2');
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension('2')->setRowHeight(20);
    
    $sheet->setCellValue('A3', 'Generated: ' . date('M d, Y h:i A'));
    $sheet->mergeCells('A3:I3');
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
        
        // Format period name to short date format
        $periodName = $data['PeriodName'] ?? $periodInfo['PayrollPeriod'];
        $shortPeriodDate = '';
        if (!empty($periodInfo['PhysicalMonth']) && !empty($periodInfo['PhysicalYear'])) {
            $month = trim($periodInfo['PhysicalMonth']);
            $year = trim($periodInfo['PhysicalYear']);
            $monthNum = date('n', strtotime($month . ' 1'));
            if ($monthNum) {
                $shortMonth = date('M', mktime(0, 0, 0, $monthNum, 1));
                $shortPeriodDate = $shortMonth . ' ' . $year;
            } else {
                $shortPeriodDate = substr($month, 0, 3) . ' ' . $year;
            }
        } else {
            if (preg_match('/(\w+)\s*-\s*(\d{4})/i', $periodName, $matches)) {
                $month = $matches[1];
                $year = $matches[2];
                $monthNum = date('n', strtotime($month . ' 1'));
                if ($monthNum) {
                    $shortMonth = date('M', mktime(0, 0, 0, $monthNum, 1));
                    $shortPeriodDate = $shortMonth . ' ' . $year;
                } else {
                    $shortPeriodDate = substr($month, 0, 3) . ' ' . $year;
                }
            } else {
                $shortPeriodDate = $periodName;
            }
        }
        $sheet->setCellValue('D' . $row, $shortPeriodDate);
        
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
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(8);
    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getColumnDimension('D')->setWidth(12);
    $sheet->getColumnDimension('E')->setWidth(16);
    $sheet->getColumnDimension('F')->setWidth(18);
    $sheet->getColumnDimension('G')->setWidth(18);
    $sheet->getColumnDimension('H')->setWidth(16);
    $sheet->getColumnDimension('I')->setWidth(18);
    
    // Set page setup for landscape and fit to page width
    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);
    
    // Set margins
    $sheet->getPageMargins()->setTop(0.5);
    $sheet->getPageMargins()->setRight(0.5);
    $sheet->getPageMargins()->setBottom(0.5);
    $sheet->getPageMargins()->setLeft(0.5);
    
    // Repeat header row on each page
    $sheet->getPageSetup()->setRowsToRepeatAtTop([5]);
    
    // Freeze header row
    $sheet->freezePane('A6');
    
    // Save to temporary file
    $writer = new Xlsx($spreadsheet);
    $fileName = tempnam(sys_get_temp_dir(), 'xlsx');
    $writer->save($fileName);
    
    // Generate filename
    $filename = 'OOUTH_NASU_Monthly_Report_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $periodDate) . '_' . date('Y-m-d');
    
    // Get report date and time
    $reportDate = date('F j, Y');
    $reportTime = date('h:i A');
    $totalRecords = count($reportData);
    
    // Prepare to send the file as an email attachment
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];

        // Recipients
        $mail->setFrom('nasuoouth@emmaggi.com', 'OOUTH NASU');
        $mail->addAddress($email, '');

        // Attachments
        $mail->addAttachment($fileName, $filename . '.xlsx');

        // Create comprehensive email body
        $emailBody = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">OOUTH NASU</h1>
                <p style="color: #f0f0f0; margin: 10px 0 0 0; font-size: 16px;">Non-Academic Staff Union</p>
            </div>
            
            <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 10px 10px;">
                <h2 style="color: #667eea; margin-top: 0; font-size: 22px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                    📊 Monthly Report
                </h2>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #495057; margin-top: 0; font-size: 18px;">Report Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; color: #6c757d; font-weight: bold; width: 40%;">Report Name:</td>
                            <td style="padding: 8px 0; color: #212529;">Monthly Report - ' . htmlspecialchars($periodDate) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #6c757d; font-weight: bold;">Period:</td>
                            <td style="padding: 8px 0; color: #212529;">' . htmlspecialchars($periodDate) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #6c757d; font-weight: bold;">Date Generated:</td>
                            <td style="padding: 8px 0; color: #212529;">' . $reportDate . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #6c757d; font-weight: bold;">Time Generated:</td>
                            <td style="padding: 8px 0; color: #212529;">' . $reportTime . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #6c757d; font-weight: bold;">Total Records:</td>
                            <td style="padding: 8px 0; color: #212529; font-weight: bold; color: #667eea;">' . number_format($totalRecords) . ' record(s)</td>
                        </tr>
                    </table>
                </div>
                
                <div style="margin: 25px 0;">
                    <p style="color: #495057; font-size: 15px; margin-bottom: 15px;">
                        Dear Recipient,
                    </p>
                    <p style="color: #495057; font-size: 15px; text-align: justify;">
                        Please find attached the monthly report for <strong>' . htmlspecialchars($periodDate) . '</strong>. 
                        This report contains detailed contribution and loan information for all members as generated from the OOUTH NASU 
                        management system.
                    </p>
                    <p style="color: #495057; font-size: 15px; text-align: justify;">
                        The attached Excel file contains all the relevant data in a structured format for your review and records. 
                        Kindly review the document and let us know if you require any additional information or clarification.
                    </p>
                </div>
                
                <div style="background: #e7f3ff; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <p style="margin: 0; color: #495057; font-size: 14px;">
                        <strong>📎 Attachment:</strong> ' . htmlspecialchars($filename) . '.xlsx<br>
                        <strong>📋 Format:</strong> Microsoft Excel (.xlsx)<br>
                        <strong>📊 Records:</strong> ' . number_format($totalRecords) . ' entries
                    </p>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                    <p style="color: #495057; font-size: 15px; margin-bottom: 5px;">
                        Best regards,
                    </p>
                    <p style="color: #667eea; font-size: 16px; font-weight: bold; margin: 5px 0;">
                        OOUTH NASU Management Team
                    </p>
                    <p style="color: #6c757d; font-size: 13px; margin-top: 20px;">
                        This is an automated email from the OOUTH NASU Management System.<br>
                        Please do not reply to this email.
                    </p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px; padding: 15px; color: #6c757d; font-size: 12px;">
                <p style="margin: 0;">© ' . date('Y') . ' OOUTH NASU. All rights reserved.</p>
            </div>
        </body>
        </html>';
        
        // Set email subject and body
        $mail->isHTML(true);
        $mail->Subject = 'OOUTH NASU - Monthly Report - ' . htmlspecialchars($periodDate) . ' - ' . $reportDate;
        $mail->Body = $emailBody;

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

    // Remove the temporary file
    unlink($fileName);
    exit;
    
} catch (Exception $e) {
    die("Error generating report: " . $e->getMessage());
}
?>
