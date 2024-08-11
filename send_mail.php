<?php
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
        $mail->setFrom('nasuoouth@emmaggi.com', 'NASU-OOUTH BRANCH');
        $mail->addAddress($email, '');

        // Attachments
        $mail->addAttachment($fileName, $filename.'.xlsx');  // Add attachments

        // Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'Print';
        $mail->Body    = 'Kindly help me to print the attached file<br><br></br>Akinleye';

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
