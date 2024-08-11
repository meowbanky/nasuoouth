<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
} else {
}

require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();
//Nok relationionship

$periods = $dbHandler->getOrderedItem('tbpayrollperiods', 'Periodid', 'PayrollPeriod');

$contributions = $dbHandler->getContributionsDetails();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - Process</title>
    <?php include "includes/header.php"; ?>
    <!-- Additional custom CSS can go here -->
</head>

<body id="body-pd">
    <?php include('includes/header_nav.php'); ?>
    <div id="overlay" style="display:none;">
        <div class="overlay-content">
            <!-- Bootstrap Spinner -->
            <div class="spinner-border text-light" role="status">

            </div>
            <h4 class="text-light mt-3">Working...</h4>



            <div id="sample_1">
                <div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
                <div id="information" style="width:100%">
                </div>
            </div>



        </div>
    </div>

    <?php include "includes/sidebar2.php"; ?>


    <div class="container-fluid top-margin">

        <div class="container-fluid">
            <!-- Sidebar/User Info and Form Section -->
            <div class="row">

                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

                    <?php if (isset($_SESSION['success_message'])) : ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success_message'];
                            unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])) : ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error_message'];
                            unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="card">
                        <h5 class="card-header">Process Transactions</h5>
                        <div class="card-body">
                            <form method="POST" id="statusForm" name="statusForm">

                                <div class="form-group">
                                    <label for="period">Period:</label>
                                    <select name="PeriodID" id="PeriodID" class="form-control custom-select">
                                        <option value="">Select Period</option>
                                        <?php foreach ($periods as $period) { ?>
                                            <option value="<?php echo $period['Periodid']; ?>" <?php if (isset($_SESSION['period'])) {
                                                                                                    if ($_SESSION['period'] == $period['Periodid']) {
                                                                                                        echo "selected";
                                                                                                    }
                                                                                                } ?>><?php echo $period['PayrollPeriod']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary" name="Submit" value="Save">Process</button>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Staff No.</th>
                                            <th scope="col">Name</th>
                                            <th scope="col" class="text-right">Savings</th>
                                            <th scope="col" class="text-right">Loan Repayment</th>
                                            <th scope="col" class="text-right"> Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1;
                                        $totalSavings = 0;
                                        $totalLoanRepay = 0;
                                        $total = 0;
                                        foreach ($contributions as $contribution) { ?>
                                            <tr>
                                                <th scope="row"><?php echo $i; ?></th>
                                                <td><?php echo $contribution['staff_id']; ?></td>
                                                <td><?php echo $contribution['namess']; ?></td>
                                                <td class="text-right">₦<?php echo number_format($contribution['contribution']); ?></td>
                                                <td class="text-right">₦<?php echo number_format($contribution['loan']); ?></td>
                                                <td class="text-right">₦<?php echo number_format($contribution['total']); ?></td>
                                            </tr>
                                        <?php $i++;
                                            $totalSavings = $totalSavings + $contribution['contribution'];
                                            $totalLoanRepay = $totalLoanRepay + $contribution['loan'];
                                            $total = $total + $contribution['total'];
                                        } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th scope="row" colspan="2" class="text-right">

                                            </th>
                                            <th scope="row" class="text-right">Total</th>
                                            <td class="text-right">₦<?php echo number_format($totalSavings); ?></td>
                                            <td class="text-right">₦<?php echo number_format($totalLoanRepay); ?></td>
                                            <td class="text-right">₦<?php echo number_format($total); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>

                            </div>

                        </div>

                    </div>
                </main>
            </div>
        </div>
    </div>


    <!-- Bootstrap and your custom scripts here -->

</body>
<?php include("includes/nav_script.php"); ?>

<script>
    $(document).ready(function() {

        $('#statusForm').on('submit', async function(event) {
            event.preventDefault(); // Prevent the form from submitting immediately

            // Initially, no errors
            let hasErrors = false;

            // Clear previous error messages
            $('.error-message').remove();
            $('.form-control').removeClass('is-invalid');

            // Check if period field is filled
            if ($('#PeriodID').val().trim() === '') {
                $('#PeriodID').addClass('is-invalid').after('<div class="error-message text-danger">Period is required.</div>');
                hasErrors = true;
            }

            // If any initial validation failed, stop here
            if (hasErrors) {
                $('html, body').animate({
                    scrollTop: $('.is-invalid').first().offset().top - 100
                }, 200);
                return; // Stop execution
            }


            if (confirm('Are you sure you want to run ' + $('#period').find('option:selected').text() + ' Transaction?')) {
                // Show overlay
                $('#overlay').fadeIn();
                try {

                    var formData = $(this).serialize(); // Serializes form data for Ajax
                    $.ajax({
                        type: 'GET',
                        url: 'class/process_transactions.php', // Adjust if necessary
                        data: formData,
                        xhrFields: {
                            onprogress: function(e) {
                                $('#sample_1').html(e.target.responseText);
                                console.log(e.target.responseText);
                            }
                        },
                        success: function(response) {
                            // Handle success
                            $('#overlay').fadeOut('fast', function() {
                                $("#statusDetails").html(response);
                            });

                        },
                        error: function() {
                            // Handle error
                            alert('Form submission failed.');
                        },
                        complete: function() {
                            // Always executed after the AJAX call completes
                            $('#overlay').fadeOut();
                        }
                    });
                } catch (error) {
                    console.error('An error occurred:', error);

                }



            }


        });
    })
</script>



</html>