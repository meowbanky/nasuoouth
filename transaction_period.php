<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
} else {
}
require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();
//Nok relationionship
//$periods = $dbHandler->getOrderedItem('tbpayrollperiods', 'Periodid', 'PayrollPeriod');


$itemsPerPage = 10; // Set the number of items you want per page
$totalItems = $dbHandler->countItems('tbpayrollperiods'); // Get total items
$totalPages = ceil($totalItems / $itemsPerPage); // Calculate total pages
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page from URL, default is 1
$page = max($page, 1); // Ensure the page is at least 1
$page = min($page, $totalPages); // Ensure the page doesn't exceed the max
$offset = ($page - 1) * $itemsPerPage; // Calculate the offset

$periods = $dbHandler->getLimitedOrderedItem('tbpayrollperiods', 'Periodid', 'DESC', $itemsPerPage, $offset);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - Transaction Periods</title>
    <?php require "includes/header.php"; ?>
    <!-- Additional custom CSS can go here -->
</head>

<body id="body-pd">

    <div id="overlay" style="display:none;">
        <div class="overlay-content">
            <!-- Bootstrap Spinner -->
            <div class="spinner-border text-light" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <h4 class="text-light mt-3">Saving...</h4>
        </div>
    </div>

    <?php include('includes/header_nav.php'); ?>


    <?php include "includes/sidebar2.php"; ?>
    <div class="container-fluid top-margin">
        <!-- Sidebar/User Info and Form Section -->
        <div class="row">
            <!-- Sidebar/User Info -->


            <!-- More links can be added here -->




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
                    <h5 class="card-header">Create Transaction Period</h5>
                    <div class="card-body">
                        <form method="POST" id="transactionPeriodForm" name="transactionPeriodForm">
                            <!-- Form Fields -->
                            <div class="form-group">
                                <label for="period">Period:</label>

                                <div class="input-group">
                                    <input type="text" class="form-control" name="period" id="period" readonly>
                                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" name="Submit" value="Save">Save</button>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Period</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periods as $period) { ?>
                                    <tr>
                                        <td><?php echo $period['PayrollPeriod']; ?></td>

                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>

                    </div>

                </div>
            </main>
        </div>
    </div>
    </div>
    </div>

    <!-- Bootstrap and your custom scripts here -->

</body>
<?php include("includes/nav_script.php"); ?>
<script>
    $(document).ready(function() {
        $('#period').datepicker({
            format: "MM - yyyy",
            startView: "months",
            minViewMode: "months",
            autoclose: true


        });

        function checkPeriodExists() {
            return new Promise((resolve, reject) => {
                var period = $('#period').val();
                $.ajax({
                    url: 'class/check_period.php',
                    type: 'POST',
                    data: {
                        period: period
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.exists) {
                            alert('Period already exists.');
                            $('#period').addClass('is-invalid');
                            resolve(false); // The period exists, resolve the promise with false
                        } else {
                            $('#period').removeClass('is-invalid');
                            resolve(true); // The period doesn't exist, resolve the promise with true
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error("An error occurred: " + error)); // Reject the promise on error
                    }
                });
            });
        }

        $('#period').blur(function() {
            var period = $(this).val();
            $.ajax({
                url: 'class/check_period.php',
                type: 'POST',
                data: {
                    period: period
                },
                dataType: 'json',
                success: function(response) {
                    if (response.exists) {
                        alert('Period No already exists.');
                        // Invalidate the field, e.g., by adding a visual cue or message
                        $('#period').addClass('is-invalid');
                    } else {
                        $('#period').removeClass('is-invalid');
                    }
                }
            });
        });


        $('#transactionPeriodForm').on('submit', async function(event) {
            event.preventDefault(); // Prevent the form from submitting immediately

            // Initially, no errors
            let hasErrors = false;

            // Clear previous error messages
            $('.error-message').remove();
            $('.form-control').removeClass('is-invalid');

            // Check if period field is filled
            if ($('#period').val().trim() === '') {
                $('#period').addClass('is-invalid').after('<div class="error-message text-danger">Period is required.</div>');
                hasErrors = true;
            }

            // If any initial validation failed, stop here
            if (hasErrors) {
                $('html, body').animate({
                    scrollTop: $('.is-invalid').first().offset().top - 100
                }, 200);
                return; // Stop execution
            }

            // Show overlay
            $('#overlay').fadeIn();

            try {
                // Wait for the period existence check
                const periodIsValid = await checkPeriodExists();
                if (!periodIsValid) {
                    // If period already exists, show error and stop the submission
                    $('#period').addClass('is-invalid').after('<div class="error-message text-danger">Period already exists.</div>');
                    $('#overlay').fadeOut();
                    return;
                }
                var period = $('#period').val()
                // If there are no errors and period is valid, proceed with AJAX form submission
                var formData = $(this).serialize(); // Serializes form data for Ajax
                $.ajax({
                    type: 'POST',
                    url: 'savePeriodData.php', // Adjust if necessary
                    data: formData,
                    success: function(response) {
                        // Handle success
                        displayAlert(period + ' Period info Saved', 'center', 'success')
                        $('#transactionPeriodForm')[0].reset(); // Clear form
                    },
                    error: function() {
                        // Handle error
                        displayAlert('error saving', 'center', 'error')
                    },
                    complete: function() {
                        // Always executed after the AJAX call completes
                        $('#overlay').fadeOut();
                    }
                });
            } catch (error) {
                console.error('An error occurred:', error);
                displayAlert('error saving', 'center', 'error')
                $('#overlay').fadeOut();
            }
        });

    });
</script>



</html>