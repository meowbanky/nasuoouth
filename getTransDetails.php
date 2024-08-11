<?php
require_once('class/DataBaseHandler.php');

$dbHandler = new DataBaseHandler();




// Basic input validation and sanitization
$period = filter_input(INPUT_GET, 'period', FILTER_SANITIZE_NUMBER_INT);
$staffId = filter_input(INPUT_GET, 'staff_id', FILTER_SANITIZE_NUMBER_INT); // Optional: For filtering by staff_id

// Ensure that periodFrom and periodTo are set and valid
if ($period === null || $period === null || !is_numeric($period) || !is_numeric($period)) {
    die("Invalid period inputs.");
}

// Fetch transaction details
$transactionDetails = $dbHandler->fetchSmsTable($period, $staffId);

$periodDetails = $dbHandler->getSingleItem('tbpayrollperiods', 'PayrollPeriod', 'Periodid', $period);


// Check if transaction details are available
if (empty($transactionDetails)) {
    echo "No transaction details found for the specified period.";
} else {
    // Iterate over the fetched data and display
    $totalContribution = 0;
    $totalLoans = 0;
    $totalLoanRepayment = 0;
    $GrandTotal = 0;

    echo "<form method='GET' id='masterDetailsForm' name='masterDetailsForm'>";
    echo "<div class='table-responsive'>";
    echo "<table id='smstable' class='table table-striped'>";
    echo "<thead>";
    echo "<tr><th></th>
                        <th>Staff ID</th>
                        <th>Period</th>
                        <th>Name</th>
                        <th>Phone No.</th>
                        <th class='text-center'>Savings</th>
                        <th class='text-center'>Loan</th>
                        <th class='text-center'>Loan Repayments</th>
                        <th class='text-center'>Loan Balance</th>
                        </tr>
                        </thead>";
    foreach ($transactionDetails as $detail) {
        $phoneNo = $dbHandler->getSingleItem('tbl_personalinfo', 'MobilePhone', 'staff_id', $detail['staff_id']);

        echo "<tr>";
        echo "<td>";
        echo "<td>{$detail['staff_id']}</td>";
        echo "<td>{$periodDetails}</td>";
        echo "<td>{$detail['namess']}</td>";
        echo "<td>$phoneNo</td>";
        echo "<td class='text-right'><span>&#8358;</span>" . number_format($detail['Contribution']) . "</td>";
        echo "<td class='text-right'><span>&#8358;</span>" . number_format($detail['loan']) . "</td>";
        echo "<td class='text-right'><span>&#8358;</span> " . number_format($detail['loanrepayments']) . "</td>";
        echo "<td class='text-right'><span>&#8358;</span> " . number_format($detail['loan_balance']) . "</td>";
        echo "</tr>";
        $totalContribution += $detail['Contribution'];
        $totalLoans += $detail['loan'];
        $totalLoanRepayment += $detail['loanrepayments'];
        $GrandTotal += $detail['total'];
    }
    echo "</table>";
    echo "</div>";
    echo  "</form>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <script>
        $(document).ready(function() {





        });
    </script>
</body>

</html>