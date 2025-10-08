<?php
require_once 'vendor/autoload.php';
require_once('class/DataBaseHandler.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$newMembers = []; // Initialize the array
$founds = [];
// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Assuming you've sanitized and validated your inputs
    $dbHandler = new DataBaseHandler();

    // Check if the file has been uploaded
    if (isset($_FILES["file"])) {
        // Get the uploaded file
        $file = $_FILES["file"];

        // Check if the file is an Excel file
        if ($file["type"] == "application/vnd.ms-excel" || $file["type"] == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") {
            // Load the Excel file
            $reader = new Xlsx();
            $spreadsheet = $reader->load($file["tmp_name"]);

            // Get the first sheet
            $sheet = $spreadsheet->getActiveSheet();

            // Get the data from the sheet
            $data = $sheet->toArray();

            // Insert the data into the MySQL database
            foreach ($data as $row) {
                if (is_numeric($row[1])) {
                    if ($dbHandler->checkBeforeUpload($row[1])) {

                        $staff_id = $row[1];
                        #Get loan details
                        $contributions = $dbHandler->getSingleItem("tbl_contributions", "contribution", "staff_id", $staff_id);

                        $special_savings = $dbHandler->getSingleItem("tbl_contributions", "special_savings", "staff_id", $staff_id);

                        $loanStatus = $dbHandler->getLoanStatus($staff_id);
                        $loan = $dbHandler->getSingleItem("tbl_contributions", "loan", "staff_id", $staff_id);

                        $defaultContribution = $dbHandler->setting("tbl_settings", "contribution");

                        $getContributionGrandTotal = $dbHandler->getContributionGrandTotal();

                        $defaultCont = $defaultContribution;



                        $amountInput = str_replace(",", "", $row[3]);
                        $amount = (float)($amountInput); // Ensure amount is a number and replace commas for conversion

                        if ($amount >= $defaultCont && $loanStatus > 0) { // Check if amount is a valid number and greater than or equal to defaultCont
                            $loanRepayment = $amount - $defaultCont; // Calculate loan repayment
                            $special_savings = 0;
                        } else if ($amount >= $defaultCont && $loanStatus == 0) {
                            $special_savings = $amount - $defaultCont;
                            $loanRepayment  = 0;
                        } else{
                            $special_savings = 0;
                            $loanRepayment = 0;
                        }

                        #Save the data to database
                        $dbHandler->upsertContribution($staff_id, $defaultCont, $loanRepayment, $special_savings, null);
                        array_push($founds, $row[1]);
                    } else {
                        array_push($newMembers, $row[2] . '-' . $row[1]);
                        continue;
                    }
                }
            }

            echo "Data imported successfully!";
            $newMem = '<br>New Member Found';
            foreach ($newMembers as $newMember) {
                $newMem = $newMem . '<br>' . $newMember;
            }
            echo $newMem;
            $src = '';
            foreach ($founds as $found) {
                $src = $src . ',' . $found;
            };
            $position = strpos($src, ',');
            $src = substr_replace($src, '', $position, 1);

            $dbHandler->updateStaffIDNotFound($src);
        } else {
            echo "Invalid file type. Only Excel files are allowed.";
        }
    } else {
        echo "No file uploaded.";
    }
}