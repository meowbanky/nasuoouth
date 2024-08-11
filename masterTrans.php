<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
} else {
}

require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();
//Nok relationionship

$periods = $dbHandler->getOrderedItem('tbpayrollperiods', 'Periodid', 'PayrollPeriod');

$interestRate = $dbHandler->setting('tbl_settings', 'interestRate');


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
    <title>NASU, OOUTH - Master Transaction</title>
    <?php include('includes/header.php'); ?>
    <style>

    </style>
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
                    <h5 class="card-header">Master Transaction</h5>
                    <div class="card-body">
                        <form method="POST" id="masterForm" name="masterForm">
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
                                <label for="periodFrom">Period From:</label>
                                <select name="periodFrom" id="periodFrom" class="form-control custom-select">
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
                                <label for="periodTo">Period To:</label>
                                <select name="periodTo" id="periodTo" class="form-control custom-select">
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
                                <button type="submit" class="btn btn-primary" name="Submit" value="Save">Search</button>
                            </div>
                        </form>
                    </div>

                    <div id="masterDetails"></div>
                    <!-- Additional form fields here -->

            </main>
        </div>
    </div>


    <!-- Bootstrap and your custom scripts here -->

</body>
<?php include("includes/nav_script.php"); ?>

<script>
    $(document).ready(function() {
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

                return false;
            }
        });

        $('#search').on('keyup', function() {
            if ($(this).val() == "") {
                $('#staff_id').val('');
                $('#name').val('');
            }

        })



        $('#masterForm').on('submit', async function(event) {
            event.preventDefault(); // Prevent the form from submitting immediately

            // Initially, no errors
            let hasErrors = false;

            // Clear previous error messages
            $('.error-message').remove();
            $('.form-control').removeClass('is-invalid');

            // Check if period field is filled
            if ($('#periodFrom').val().trim() === '') {
                $('#periodFrom').addClass('is-invalid').after('<div class="error-message text-danger">Period From is required.</div>');
                hasErrors = true;
            }

            if ($('#periodTo').val().trim() === '') {
                $('#periodTo').addClass('is-invalid').after('<div class="error-message text-danger">Period To is required.</div>');
                hasErrors = true;
            }

            if ($('#periodFrom').val() > $('#periodTo').val()) {
                $('#periodTo').addClass('is-invalid').after('<div class="error-message text-danger">Period To is Must be greater than Period From.</div>');
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

                var formData = $(this).serialize(); // Serializes form data for Ajax
                $.ajax({
                    type: 'GET',
                    url: 'getMasterTransDetail.php', // Adjust if necessary
                    data: formData,
                    success: function(response) {
                        $("#masterDetails").html(response);
                        // $('#loanForm')[0].reset(); // Clear form
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