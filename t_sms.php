<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
} else {
}

require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();
//Nok relationionship

$periods = $dbHandler->getOrderedItem('tbpayrollperiods', 'Periodid', 'PayrollPeriod');


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - SMS</title>
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
                        <h5 class="card-header">Send Transacton SMS</h5>
                        <div class="card-body">
                            <form method="POST" id="statusForm" name="statusForm">
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
                                    <label for="period">Period:</label>
                                    <select name="period" id="period" class="form-control custom-select">
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
                                    <button type="submit" class="btn btn-primary" name="Submit" value="Save">Send SMS</button>
                                    <button type="button" class="btn btn-danger hidden" name="export" id="export" value="Export">Export Excel</button>
                                    <button type="button" class="btn btn-warning hidden" name="emailButton" id="emailButton" value="Mail">Send as Mail</button>
                                 </div>
                            </form>
                        </div>

                        <div id="statusDetails"></div>
                        <!-- Additional form fields here -->

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

        $('#emailButton').on('click', function() {
            $('#overlay').fadeIn();
            const email = prompt("Please enter the employee's email address:");
            var table = document.getElementById('smstable').outerHTML;
            var select = document.getElementById('period');
            var selectedFilename = select.options[select.selectedIndex].text;
            var filename = selectedFilename;

            if (email) {
                $.ajax({
                    url: 'send_mail.php',
                    type: 'POST',
                    data: {
                        email: email,
                        html:  table,
                        filename: filename
                    },
                    success: function(response) {
                        $('#overlay').fadeOut('fast', function() {
                           alert(response)
                        });
                    },
                    error: function(xhr, status, error) {
                        $('#overlay').fadeOut('fast', function() {
                            alert('Error sending email: ' + error)
                        });
                    }
                });
            }
        });
        $("#search").on('keyup', function() {
            if ($("#search").val().trim() === '') {
                $("#name").val('');
                $("#staff_id").val('');
            }

        })
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
                $("#period").focus();
                return false;
            }
        });

        $('#period').on('change', function() {
            try {
                $('#export').addClass('hidden');
                $('#emailButton').addClass('hidden')
                let hasErrors = false;

                // Clear previous error messages
                $('.error-message').remove();
                $('.form-control').removeClass('is-invalid');

                // If any initial validation failed, stop here
                if (hasErrors) {
                    $('html, body').animate({
                        scrollTop: $('.is-invalid').first().offset().top - 100
                    }, 200);
                    return; // Stop execution
                }

                $('#overlay').fadeIn();

                var formData = $("#statusForm").serialize(); // Serializes form data for Ajax
                $.ajax({
                    type: 'GET',
                    url: 'getTransDetails.php', // Adjust if necessary
                    data: formData,
                    success: function(response) {
                        // Handle success
                        $('#overlay').fadeOut('fast', function() {
                            $("#statusDetails").html(response);
                            $('#export').removeClass('hidden');
                            $('#emailButton').removeClass('hidden');
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
        });


        $('#statusForm').on('submit', async function(event) {
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
            // // Check if period field is filled
            // if ($('#staff_id').val().trim() === '') {
            //     $('#staff_id').addClass('is-invalid').after('<div class="error-message text-danger">Staff No is required.</div>');
            //     hasErrors = true;
            // }

            // If any initial validation failed, stop here
            if (hasErrors) {
                $('html, body').animate({
                    scrollTop: $('.is-invalid').first().offset().top - 100
                }, 200);
                return; // Stop execution
            }


            try {

                if (confirm("Are you sure you want to send sms alert")) {
                    // Show overlay    
                    $('#overlay').fadeIn();
                    var formData = $(this).serialize(); // Serializes form data for Ajax
                    $.ajax({
                        type: 'GET',
                        url: 'sendTalert.php', // Adjust if necessary
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
                                $("#sample_1").html(response);
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
                }
            } catch (error) {
                console.error('An error occurred:', error);

            }
        });





    });
</script>

<script>

    $('#export').click(function(){
        exportTable();
    })

    function exportTable() {
        // Get the table HTML
        var table = document.getElementById('smstable').outerHTML;
        var select = document.getElementById('period');
        var selectedFilename = select.options[select.selectedIndex].text;


        var filename = selectedFilename;


        $.ajax({
            url: 'export_excel.php',
            type: 'POST',
            data: {html: table},
            xhrFields: {
                responseType: 'blob'
            },
            success: function (data) {
                var a = document.createElement('a');
                var url = window.URL.createObjectURL(data);
                a.href = url;
                a.download = filename + '.xlsx';
                document.body.append(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
            },
            error: function () {
                alert('Failed to export table');
            }
        });

        console.log(table);
    }
</script>



</html>