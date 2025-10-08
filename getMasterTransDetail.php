<?php
require_once('class/DataBaseHandler.php');

$dbHandler = new DataBaseHandler();


if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['deleted'])) {
    $loans = [];
    // Assuming you've sanitized and validated your inputs
    $selectedMasters = $_GET['selectedMaster'] ?? [];


    // Delete selected loans
    foreach ($selectedMasters as $selectedMaster) {
        $splittedMaster = explode(",", $selectedMaster);
        $staff_id =  $splittedMaster[0];
        $periodid =  $splittedMaster[1];

        $dbHandler->deleteRows2Column("tlb_mastertransaction", "periodid", intval($periodid), "staff_id", intval($staff_id)); // Implement this method in your class

        $dbHandler->deleteRows2Column("tbl_loan", "periodid", intval($periodid), "staff_id", intval($staff_id)); // Implement this method in your class
    }



    //   header("Location: " . $_SERVER['PHP_SELF']); // Optional: Redirect to prevent form resubmission
}


// Basic input validation and sanitization
$periodFrom = filter_input(INPUT_GET, 'periodFrom', FILTER_SANITIZE_NUMBER_INT);
$periodTo = filter_input(INPUT_GET, 'periodTo', FILTER_SANITIZE_NUMBER_INT);
$staffId = filter_input(INPUT_GET, 'staff_id', FILTER_SANITIZE_NUMBER_INT); // Optional: For filtering by staff_id

// Ensure that periodFrom and periodTo are set and valid
if ($periodFrom === null || $periodTo === null || !is_numeric($periodFrom) || !is_numeric($periodTo)) {
    die("Invalid period inputs.");
}

// Fetch transaction details
$transactionDetails = $dbHandler->fetchTransactionDetails($periodFrom, $periodTo, $staffId);


if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['deleted'])) {
    echo "HOJOJ";
    $loans = [];
    // Assuming you've sanitized and validated your inputs
    $selectedMasters = $_GET['selectedMaster'] ?? [];

    echo $selectedMasters;

    // Delete selected loans
    foreach ($selectedMasters as $selectedMaster) {
        echo 'Deleting ' . $selectedMaster;
        // $dbHandler->deleteRows2Column("tlb_mastertransaction", "periodid", intval($selectedMaster), "staff_id",); // Implement this method in your class
    }
}

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
    echo "<table class='table table-striped'>";
    echo "<thead>";
    echo "<tr><th><button type='button' class='btn btn-outline-danger' name='delete' id='delete'>
                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-trash3' viewBox='0 0 16 16'>
                                <path d='M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5'></path>
                            </svg>
                            
                        </button><input type='checkbox' id='selectAllLoans'></th>
                        <th>Staff ID</th>
                        <th>Period</th><th>Name</th>
                        <th class='text-center'>Savings</th>
                        <th class='text-center'>Loan</th>
                        <th class='text-center'>Loan Repayments</th>
                         <th class='text-center'>Loan Balance</th>
                         <th class='text-center'>Refund</th>
                        <th class='text-center'>Total</th>
                        </tr>
                        </thead>";
    foreach ($transactionDetails as $detail) {
        $cumulativeLoan = $dbHandler->getBalance($detail['staff_id'], 'tlb_mastertransaction', 'loanAmount', 'loanAmount', 'periodid', $detail['periodids'], '<=');
        $cumulativeInterest = $dbHandler->getBalance($detail['staff_id'], 'tlb_mastertransaction', 'interest', 'interest', 'periodid', $detail['periodids'], '<=');
        $totalLoan = $cumulativeLoan + $cumulativeInterest;
        $cumulativeRepayment = $dbHandler->getBalance($detail['staff_id'], 'tlb_mastertransaction', 'loanRepayment', 'loanRepayment', 'periodid', $detail['periodids'], '<=');
        $loanBalance = $totalLoan - $cumulativeRepayment;
        echo "<tr>";
        echo "<td><input type='checkbox' name='selectedMaster[]' value='{$detail['staff_id']},{$detail['periodids']}'>";
        echo "<td>{$detail['staff_id']}</td>";
        echo "<td>{$detail['PayrollPeriod']}</td>";
        echo "<td>{$detail['namess']}</td>";
        echo "<td class='text-right'>₦" . number_format($detail['Contribution']) . "</td>";
        echo "<td class='text-right'>₦" . number_format($detail['loan']) . "</td>";
        echo "<td class='text-right'>₦" . number_format($detail['loanrepayments']) . "</td>";
        echo "<td class='text-right'>₦" . number_format($loanBalance) . "</td>";
        echo "<td class='text-right'>₦" . number_format($detail['refund']) . "</td>";
        echo "<td class='text-right'>₦" . number_format($detail['total']) . "</td>";
        echo "</tr>";
        $totalContribution += $detail['Contribution'];
        $totalLoans += $detail['loan'];
        $totalLoanRepayment += $detail['loanrepayments'];
        $GrandTotal += $detail['total'];
    }
    echo "<tfoot>
                <tr>
                    <th scope='row' colspan='4' class='text-right'>Total</th>
                    <th scope='row' class='text-right'>₦" . number_format($totalContribution) . "</th>
                    <th scope='row' class='text-right'>₦" . number_format($totalLoans) . "</th>
                     <th scope='row' class='text-right'>₦" . number_format($totalLoanRepayment) . "</th>
                      <th scope='row' class='text-right'>₦" . number_format($GrandTotal) . "</th>
                </tr>
            </tfoot>";
    echo "</table>";
    echo "</div>";
    echo "<input type='hidden' name='deleted' id='deleted'>";
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

            $('#selectAllLoans').click(function(event) {
                if (this.checked) {
                    // Iterate each checkbox and check
                    $(':checkbox').each(function() {
                        this.checked = true;
                    });
                } else {
                    $(':checkbox').each(function() {
                        this.checked = false;
                    });
                }
            });

            $('#delete').click(function(e) {
                // Check if at least one checkbox is checked
                if ($('input[type="checkbox"]:checked').length === 0) {
                    alert('Please select at least one Transactions// to delete.');
                    return; // Stop the function if no checkboxes are checked
                }
                Swal.fire({
                    title: "Are you sure you want to delete these Transactions?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#overlay').fadeIn();
                        e.preventDefault(); // Prevent default form submission

                        var formData = $('#masterDetailsForm').serialize(); // Serialize form data

                        $.ajax({
                            url: 'getMasterTransDetail.php', // Adjust this to your server-side script for deleting loans
                            type: 'GET',
                            data: formData,
                            success: function(response) {
                                // Hide overlay and alert the user
                                $('#overlay').fadeOut('fast', function() {
                                    Swal.fire({
                                        title: "Deleted!",
                                        text: "Your file has been deleted.",
                                        icon: "success"
                                    });
                                    location.reload(); // Reload the page to reflect changes
                                });
                            },
                            error: function() {
                                $('#overlay').fadeOut('fast', function() {
                                    displayAlert("Error Deleting Transactions", 'center', 'Error ')
                                });
                            }
                        });

                    }
                });

            });

        });
    </script>
</body>

</html>