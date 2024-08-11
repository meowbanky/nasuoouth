<?php
session_start();
require_once('class/DataBaseHandler.php');
// Assuming you've sanitized and validated your inputs
$dbHandler = new DataBaseHandler();

$staff_id = isset($_GET['staff_id']) ? $_GET['staff_id'] : -1;
#Get loan details
$contributions = $dbHandler->getSingleItem("tbl_contributions", "contribution", "staff_id", $staff_id);

$special_savings = $dbHandler->getSingleItem("tbl_contributions", "special_savings", "staff_id", $staff_id);

$loan = $dbHandler->getSingleItem("tbl_contributions", "loan", "staff_id", $staff_id);

$loanBalance = $dbHandler->getLoanBalance($staff_id);

$defaultContribution = $dbHandler->setting("tbl_settings", "contribution");

$getContributionGrandTotal = $dbHandler->getContributionGrandTotal();

if (($_SERVER['REQUEST_METHOD'] == 'GET') and (isset($_GET['save']))) {

    #Save the data to database
    $dbHandler->upsertContribution($_GET['staff_id'], $_GET['contributions'], $_GET['loanRepayment'], $_GET['special_savings']);
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
    <input type="hidden" id="defaultContribution" name="defaultContribution" value="<?php echo $defaultContribution; ?>">
    <form method="GET" id="saveContributionForm" name="saveContributionForm">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>

                </thead>
                <tbody>
                    <tr>
                        <th scope="row" colspan="2">Total:</th>
                        <td class="text-right"><span class="money">₦</span><input name="amount" id="amount" class="text-right" type="text" value="<?php echo number_format(($contributions) + $special_savings + $loan);
                                                                                                                                                    ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2">Contribution:</th>
                        <td class="text-right"><span class="money">₦</span> <input class="text-right" type="text" name="contributions" id="contributions" value="<?php echo number_format($contributions); ?>" readonly></td>
                    </tr>
                    <tr>
                        <th scope=" row" colspan="2">Special Savings:</th>
                        <td class="text-right"><span class="money">₦</span> <input class="text-right" type="text" name="special_savings" id="special_savings" value="<?php echo number_format($special_savings); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2">Loan Repayment:</th>
                        <td class="text-right"><span class="money">₦</span> <input class="text-right" type="text" name="loanRepayment" id="loanRepayment" value="<?php echo number_format($loan); ?>"> </td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2">Loan Balance:</th>
                        <td class="text-right"><span class="money">₦</span><?php if ($loanBalance == null) {
                                                                                $loanBalance = 0;
                                                                            }
                                                                            echo number_format($loanBalance); ?></td>
                    </tr>

                </tbody>
                <tfoot>
                    <tr>
                        <th scope="row" class="text-right">
                            <button type="button" class="btn btn-outline-success" name="saveBtn" id="saveBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                    <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"></path>
                                </svg>
                                Save
                            </button>
                        </th>
                        <th scope="row" class="text-right">Total Contribution</th>
                        <td class="text-right">₦<?php echo number_format(($contributions) + $special_savings + $loan);
                                                ?></td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2">Grand Total:</th>
                        <td class="text-right">₦ <?php echo number_format($getContributionGrandTotal == null ? 0 : $getContributionGrandTotal); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <input type="hidden" name="save" id="save">
        <input type="hidden" name="staff_id" id="staff_id" value="<?php echo $staff_id; ?>">
    </form>


    <script>
        $(document).ready(function() {

            function getTotal() {

                var defaultCont = parseFloat($("#defaultContribution").val().replace(",", ""));

                $("#contributions").val(defaultCont.toFixed(2));

                var amountInput = $("#amount");
                var amount = parseFloat(amountInput.val().replace(",", "")); // Ensure amount is a number and replace commas for conversion

                if (!isNaN(amount) && amount >= defaultCont) { // Check if amount is a valid number and greater than or equal to defaultCont
                    var loanRepayment = amount - defaultCont; // Calculate loan repayment
                    $("#loanRepayment").val(loanRepayment.toFixed(2)); // Set loan repayment value with two decimal places
                } else {
                    alert("Amount should be greater than or equal to " + defaultCont.toFixed(2)); // Alert message with two decimal places
                    //amountInput.value = ""; // Clear the Amount input field
                    // amountInput.focus(); // Set focus to the Amount input field
                }
            }

            $("#amount").on("blur", function() {
                getTotal();
            })

            $('#saveBtn').click(function(e) {

                $('#overlay').fadeIn();
                e.preventDefault(); // Prevent default form submission

                var formData = $('#saveContributionForm').serialize(); // Serialize form data

                $.ajax({
                    url: 'getContributionsDetails.php', // Adjust this to your server-side script for deleting loans
                    type: 'GET',
                    data: formData,
                    success: function(response) {
                        displayAlert('Contributions saved successfully', 'center', 'success')
                        // Optionally, alert the user
                        // alert('Selected loans have been deleted.');
                        // Remove checked items from list   
                        //  location.reload();
                    },
                    error: function() {
                        displayAlert('Error saving Info', 'center', 'error')
                    }
                });

                $('#overlay').fadeOut();
            });

        });
    </script>
</body>

</html>