<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
} else {
}

require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();
//Nok relationionship
$noks = $dbHandler->getSelectItems('nok_relationship', 'nok_id', 'relationship');
$states = $dbHandler->getSelectItems('state_nigeria', 'stateid', 'state');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - Member's Registration</title>
    <?php include "includes/header.php"; ?>
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
            <h4 class="text-light mt-3">Saving...</h4>
        </div>
    </div>
   
    <?php include "includes/sidebar2.php"; ?>


    <div class="height-100 bg-light top-margin">
        <!-- Sidebar/User Info and Form Section -->


        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 m-t-2">

            <div class="card">
                <h5 class="card-header">Registration Form</h5>
                <div class="card-body">

                    <!-- Form Fields -->
                    <div class="search-box">
                        <input type="text" name="search" id="search" class=" search-input" placeholder="Search..." autofocus>
                        <i class="fas fa-search search-icon"></i>

                    </div>
                    <hr>
                    <div id="registration">

                    </div>

                </div>
        </main>
    </div>



    <!-- Bootstrap and your custom scripts here -->

</body>

<script>
    $(document).ready(function() {
        $('#DOB').datepicker({
            autoclose: true,
            endDate: '0',
            todayHighlight: true
        });
        $("#registration").load('getRegistrationForm.php')

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
                $("#registration").load('getRegistrationForm.php?staff_id=' + ui.item.value)
                return false;
            }
        });

    });
</script>



<?php include("includes/nav_script.php"); ?>

</html>