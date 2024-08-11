<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
}

require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();
//Nok relationionship

$members = $dbHandler->getConcate3Column('tbl_personalinfo', 'Lname', 'Fname', 'Mname', 'namee', 'staff_id');




$itemsPerPage = 10; // Set the number of items you want per page
$totalItems = $dbHandler->countItems('tbpayrollperiods'); // Get total items
$totalPages = ceil($totalItems / $itemsPerPage); // Calculate total pages
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page from URL, default is 1
$page = max($page, 1); // Ensure the page is at least 1
$page = min($page, $totalPages); // Ensure the page doesn't exceed the max
$offset = ($page - 1) * $itemsPerPage; // Calculate the offset

//$periods = $dbHandler->getLimitedOrderedItem('tbpayrollperiods', 'Periodid', 'DESC', $itemsPerPage, $offset);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - Edit Contributions</title>
    <?php include('includes/header.php'); ?>
    <!-- Additional custom CSS can go here -->
</head>

<body id="body-pd">
    <?php include('includes/header_nav.php'); ?>
    <div id="overlay" style="display:none;">
        <div class="overlay-content">
            <!-- Bootstrap Spinner -->
            <div class="spinner-border text-light" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <h4 class="text-light mt-3">Working...</h4>
        </div>
    </div>


    <?php include "includes/sidebar2.php"; ?>
    <div class="container-fluid top-margin">
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
                    <h5 class="card-header">Edit Contribution</h5>
                    <div class="card-body">
                        <form method="POST" id="loanForm" name="loanForm">
                            <!-- Form Fields -->
                            <div class="search-box">
                                <input type="text" name="search" id="search" class=" search-input" placeholder="Search..." autofocus>
                                <i class="fas fa-search search-icon"></i>
                            </div>


                            <div class="form-group">
                                <label for="name">Name:</label>
                                <input type="text" class="form-control" name="name" id="name" readonly>
                            </div>

                            <div class="form-group">
                                <label for="staff_id">Staff No:</label>
                                <input type="text" class="form-control" name="staff_id" id="staff_id" readonly>
                            </div>

                            <div class="form-group">
                                <label for="selectName">Members List:</label>
                                <select name="selectName" id="selectName" class="form-control custom-select" size="10">
                                    <option value="">Select</option>
                                    <?php foreach ($members as $member) { ?>
                                        <option value="<?php echo $member['staff_id']; ?>"><?php echo $member['namee']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                        </form>

                    </div>

                    <div id="ContributionsDetails"></div>
                    <!-- Additional form fields here -->

                </div>
            </main>
        </div>
    </div>
    </div>
    </div>

    <!-- Bootstrap and your custom scripts here -->
    <?php include("includes/nav_script.php"); ?>
</body>


<script>
    $(document).ready(function() {

        let staff_id = $("#staff_id").val();
        fetchContributionsDetails(staff_id);

        $("#search").on('focus', function() {
            $("#search").select();
        })
        $("#search").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "fetch_names.php",
                    type: "GET",
                    dataType: "json",
                    data: {
                        term: request.term
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 2, // Set minimum length of input to start showing suggestions
            select: function(event, ui) {

                $("#name").val(ui.item.label);
                $("#staff_id").val(ui.item.value);
                fetchContributionsDetails(ui.item.value)
                return false;
            }
        });

        function fetchContributionsDetails(staffId) {
            // Example AJAX call to fetch the loan balance
            $('#overlay').fadeIn();
            $.ajax({
                url: 'getContributionsDetails.php', // Adjust this to your server-side script
                data: {
                    staff_id: staffId
                },
                //dataType: 'json',
                success: function(response) {
                    $('#overlay').fadeOut('fast', function() {
                        $('#ContributionsDetails').html(response); // Assuming response contains the loan balance

                    });
                },
                error: function() {
                    $('#overlay').fadeOut('fast', function() {
                        console.error('Failed to fetch Contribution Details');
                    })

                    // Handle errors here
                }
            });
        }

        function fetchLoanDetails(period) {
            $.ajax({
                url: 'getLoanDetails.php',
                type: 'POST',
                data: {
                    periodid: period
                },
                // dataType: 'json',
                success: function(response) {
                    $('#loanDetails').html(response);

                },
                error: function(xhr, status, error) {
                    // console.error('Failed to fetch loan balance');
                    alert(error);
                }
            });
        }


        $('#selectName').on('change', function() {
            var staff_id = $(this).val();
            fetchContributionsDetails(staff_id);
        });


        $('#staff_id').on('change', function() {
            var staff_id = $(this).val();
            $.ajax({
                url: 'getBalance.php', // Adjust the path to point to a PHP script where you use the fetchLoanBalance method
                type: 'GET',
                data: {
                    staff_id: staff_id
                },
                success: function(response) {
                    // Assuming `response` is the loan balance
                    $('#loan_balance').val(response); // Update the loan balance input field with the fetched balance
                },
                error: function(xhr, status, error) {
                    console.log("Error fetching loan balance: " + error);
                }
            });
        });




        $('#loanForm').on('submit', async function(event) {
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
            // Check if period field is filled
            if ($('#staff_id').val().trim() === '') {
                $('#staff_id').addClass('is-invalid').after('<div class="error-message text-danger">Staff No is required.</div>');
                hasErrors = true;
            }

            // Check if period field is filled
            if ($('#amountGranted').val().trim() === '') {
                $('#amountGranted').addClass('is-invalid').after('<div class="error-message text-danger">Amount Granted is required.</div>');
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
                var period = $("#period").val();
                var formData = $(this).serialize(); // Serializes form data for Ajax
                $.ajax({
                    type: 'POST',
                    url: 'saveLoanRequest.php', // Adjust if necessary
                    data: formData,
                    success: function(response) {
                        // Handle success
                        alert('Form submitted successfully.');
                        $('#loanForm')[0].reset(); // Clear form
                        fetchLoanDetails(period)
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
                $('#overlay').fadeOut();
            }
        });





    });
</script>



</html>