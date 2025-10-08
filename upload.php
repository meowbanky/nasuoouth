<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
} else {
}

require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - Upload</title>
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
                        <h5 class="card-header">Upload Deduction List</h5>
                        <div class="card-body">
                            <form action="import.php" name="importForm" id="importForm" method="post" enctype="multipart/form-data">
                                <div class="form-group mb-3">
                                    <label for="file" class="control-label">Select Excel file:</label>
                                    <input type="file" name="file" class="form-control-file border-primary" id="file">
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Import</button>
                            </form>


                        </div>

                        <div class="card-body">
                            <div id="statusDetails"> </div>
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

        $('#importForm').on('submit', async function(event) {
            event.preventDefault(); // Prevent the form from submitting immediately

            // Initially, no errors
            let hasErrors = false;

            // Clear previous error messages
            $('.error-message').remove();
            $('.form-control').removeClass('is-invalid');

            // Check if period field is filled
            if ($('#file').val().trim() === '') {
                $('#file').addClass('is-invalid').after('<div class="error-message text-danger">Select File.</div>');
                hasErrors = true;
            }

            // If any initial validation failed, stop here
            if (hasErrors) {
                $('html, body').animate({
                    scrollTop: $('.is-invalid').first().offset().top - 100
                }, 200);
                return; // Stop execution
            }

            var formData = new FormData(this);
            // Show confirmation dialog
            if (confirm('Are you sure you want to Upload ' + $('#file').val() + ' ?')) {
                // Show overlay
                $('#overlay').fadeIn();
                try {
                    $.ajax({
                        type: 'POST',
                        url: 'import.php',
                        data: formData,
                        processData: false, // Prevent jQuery from processing the data
                        contentType: false, // Prevent jQuery from setting the Content-Type
                        success: function(response) {
                            // Handle success
                            $('#overlay').fadeOut('fast', function() {
                                $("#statusDetails").html(response);
                            });
                        },
                        complete: function() {
                            $('#overlay').fadeOut();
                            $('#importForm')[0].reset(); // Reset the form
                            $('#file').val('');
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