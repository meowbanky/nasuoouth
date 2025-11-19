<?php
// Load error configuration (suppresses deprecation warnings from vendor packages)
require_once __DIR__ . '/config/error_config.php';

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['html'])) {
    $tableHtml = $_POST['html'];
    $email = $_POST['email'];
    $filename = $_POST['filename'];

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
    
    // Count total records (excluding header row)
    $totalRecords = $rows->length > 0 ? $rows->length - 1 : 0;
    
    // Get report date
    $reportDate = date('F j, Y');
    $reportTime = date('h:i A');

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
            if ($rowIndex == 1) {  // Bold the header row
                $sheet->getStyle($colIndex . $rowIndex)->getFont()->setBold(true);
            }
            $colIndex++;
        }
        $rowIndex++;
    }

    // Write the spreadsheet to a temporary file
    $writer = new Xlsx($spreadsheet);
    $fileName = tempnam(sys_get_temp_dir(), 'xlsx');
    $writer->save($fileName);

    // Prepare to send the file as an email attachment
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];  // Set the SMTP server to send through
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];  // SMTP username
        $mail->Password = $_ENV['SMTP_PASS'];  // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];

        // Recipients
        $mail->setFrom('nasuoouth@emmaggi.com', 'OOUTH NASU');
        $mail->addAddress($email, '');

        // Attachments
        $mail->addAttachment($fileName, $filename.'.xlsx');  // Add attachments

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
                    📊 Comprehensive Report
                </h2>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #495057; margin-top: 0; font-size: 18px;">Report Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; color: #6c757d; font-weight: bold; width: 40%;">Report Name:</td>
                            <td style="padding: 8px 0; color: #212529;">' . htmlspecialchars($filename) . '</td>
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
                        Please find attached the comprehensive report for <strong>' . htmlspecialchars($filename) . '</strong>. 
                        This report contains detailed information as requested and has been generated from the OOUTH NASU 
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
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'OOUTH NASU - ' . htmlspecialchars($filename) . ' Report - ' . $reportDate;
        $mail->Body = $emailBody;

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

    // Remove the temporary file
    unlink($fileName);
    exit;
}
?>