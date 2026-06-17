<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
} else {
}

require_once('class/DataBaseHandler.php');

$dbHandler = new DataBaseHandler();
//Nok relationionship

$periods = $dbHandler->getOrderedItem('tbpayrollperiods', 'Periodid', 'PayrollPeriod');

// Get selected period from session, GET, or POST
$selectedPeriod = null;
if (isset($_SESSION['period']) && $_SESSION['period'] > 0) {
    $selectedPeriod = $_SESSION['period'];
} elseif (isset($_GET['PeriodID']) && $_GET['PeriodID'] > 0) {
    $selectedPeriod = $_GET['PeriodID'];
} elseif (isset($_POST['PeriodID']) && $_POST['PeriodID'] > 0) {
    $selectedPeriod = $_POST['PeriodID'];
}

// Fetch contributions for the selected period (if period is selected)
$contributions = [];
if ($selectedPeriod !== null && $selectedPeriod > 0) {
    $contributions = $dbHandler->getContributionsDetails($selectedPeriod);
}

// Handle AJAX request for contributions
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    $periodId = isset($_GET['PeriodID']) ? intval($_GET['PeriodID']) : 0;
    if ($periodId > 0) {
        $contributions = $dbHandler->getContributionsDetails($periodId);
        echo json_encode([
            'success' => true,
            'contributions' => $contributions
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid period ID'
        ]);
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - Process</title>
    <?php include "includes/header.php"; ?>
    <!-- Select2 for searchable dropdown -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* Make select2 match Bootstrap styling */
        .select2-container--default .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            color: #495057;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + 0.75rem + 2px);
        }
        .select2-container { width: 100% !important; }
    </style>
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
                                                                                                } ?>>
                                            <?php echo $period['PayrollPeriod']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="form-group mt-3">
                                    <label for="staff_id_filter">Process For:</label>
                                    <select name="staff_id_filter" id="staff_id_filter" class="form-control custom-select">
                                        <option value="ALL">All Members</option>
                                    </select>
                                </div>

                                <div class="form-group form-check mt-3 mb-3">
                                    <input type="checkbox" class="form-check-input" id="send_sms" name="send_sms" value="1" checked>
                                    <label class="form-check-label" for="send_sms">Send SMS Notifications</label>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary" name="Submit"
                                        value="Save">Process</button>
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
                                    <tbody id="contributionsTableBody">
                                        <?php if (empty($contributions)) { ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Please select a period to view
                                                contributions</td>
                                        </tr>
                                        <?php } else {
                                        $i = 1;
                                        $totalSavings = 0;
                                        $totalLoanRepay = 0;
                                        $total = 0;
                                        foreach ($contributions as $contribution) { ?>
                                        <tr>
                                            <th scope="row"><?php echo $i; ?></th>
                                            <td><?php echo $contribution['staff_id']; ?></td>
                                            <td><?php echo $contribution['name']; ?></td>
                                            <td class="text-right">
                                                ₦<?php echo number_format($contribution['contribution']); ?></td>
                                            <td class="text-right">₦<?php echo number_format($contribution['loan']); ?>
                                            </td>
                                            <td class="text-right">₦<?php echo number_format($contribution['total']); ?>
                                            </td>
                                        </tr>
                                        <?php $i++;
                                            $totalSavings = $totalSavings + $contribution['contribution'];
                                            $totalLoanRepay = $totalLoanRepay + $contribution['loan'];
                                            $total = $total + $contribution['total'];
                                        } 
                                        } ?>
                                    </tbody>
                                    <tfoot id="contributionsTableFoot">
                                        <?php if (!empty($contributions)) { ?>
                                        <tr>
                                            <th scope="row" colspan="2" class="text-right">

                                            </th>
                                            <th scope="row" class="text-right">Total</th>
                                            <td class="text-right">₦<?php echo number_format($totalSavings); ?></td>
                                            <td class="text-right">₦<?php echo number_format($totalLoanRepay); ?></td>
                                            <td class="text-right">₦<?php echo number_format($total); ?></td>
                                        </tr>
                                        <?php } ?>
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

    // Initialize Select2 on the member dropdown for searchability
    $('#staff_id_filter').select2({
        placeholder: 'All Members',
        allowClear: false
    });

    // Load contributions when period changes
    $('#PeriodID').on('change', function() {
        var periodId = $(this).val();
        if (periodId && periodId !== '') {
            loadContributions(periodId);
        } else {
            $('#contributionsTableBody').html(
                '<tr><td colspan="6" class="text-center">Please select a period to view contributions</td></tr>'
                );
            $('#contributionsTableFoot').html('');
        }
    });

    function loadContributions(periodId) {
        $.ajax({
            type: 'GET',
            url: 'process.php',
            data: {
                PeriodID: periodId,
                ajax: 1
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var tbody = '';
                    var tfoot = '';
                    var i = 1;
                    var totalSavings = 0;
                    var totalLoanRepay = 0;
                    var total = 0;

                    if (response.contributions.length > 0) {
                        var staffSelect = $('#staff_id_filter');
                        staffSelect.html('<option value="ALL">All Members</option>');
                        
                        response.contributions.forEach(function(contribution) {
                            staffSelect.append('<option value="' + contribution.staff_id + '">' + contribution.name + ' (' + contribution.staff_id + ')</option>');

                            tbody += '<tr>' +
                                '<th scope="row">' + i + '</th>' +
                                '<td>' + contribution.staff_id + '</td>' +
                                '<td>' + contribution.name + '</td>' +
                                '<td class="text-right">₦' + parseFloat(contribution
                                    .contribution).toLocaleString('en-US') + '</td>' +
                                '<td class="text-right">₦' + parseFloat(contribution.loan)
                                .toLocaleString('en-US') + '</td>' +
                                '<td class="text-right">₦' + parseFloat(contribution.total)
                                .toLocaleString('en-US') + '</td>' +
                                '</tr>';
                            totalSavings += parseFloat(contribution.contribution);
                            totalLoanRepay += parseFloat(contribution.loan);
                            total += parseFloat(contribution.total);
                            i++;
                        });

                        tfoot = '<tr>' +
                            '<th scope="row" colspan="2" class="text-right"></th>' +
                            '<th scope="row" class="text-right">Total</th>' +
                            '<td class="text-right">₦' + totalSavings.toLocaleString('en-US') +
                            '</td>' +
                            '<td class="text-right">₦' + totalLoanRepay.toLocaleString('en-US') +
                            '</td>' +
                            '<td class="text-right">₦' + total.toLocaleString('en-US') + '</td>' +
                            '</tr>';
                    } else {
                        tbody =
                            '<tr><td colspan="6" class="text-center">No contributions found for this period</td></tr>';
                        $('#staff_id_filter').html('<option value="ALL">All Members</option>');
                    }

                    $('#contributionsTableBody').html(tbody);
                    $('#contributionsTableFoot').html(tfoot);
                } else {
                    $('#contributionsTableBody').html(
                        '<tr><td colspan="6" class="text-center text-danger">Error loading contributions</td></tr>'
                        );
                    $('#contributionsTableFoot').html('');
                }
            },
            error: function() {
                $('#contributionsTableBody').html(
                    '<tr><td colspan="6" class="text-center text-danger">Error loading contributions</td></tr>'
                    );
                $('#contributionsTableFoot').html('');
            }
        });
    }

    // Load contributions on page load if period is selected
    var initialPeriod = $('#PeriodID').val();
    if (initialPeriod && initialPeriod !== '') {
        loadContributions(initialPeriod);
    }

    $('#statusForm').on('submit', async function(event) {
        event.preventDefault(); // Prevent the form from submitting immediately

        // Initially, no errors
        let hasErrors = false;

        // Clear previous error messages
        $('.error-message').remove();
        $('.form-control').removeClass('is-invalid');

        // Check if period field is filled
        if ($('#PeriodID').val().trim() === '') {
            $('#PeriodID').addClass('is-invalid').after(
                '<div class="error-message text-danger">Period is required.</div>');
            hasErrors = true;
        }

        // If any initial validation failed, stop here
        if (hasErrors) {
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 200);
            return; // Stop execution
        }


        // SweetAlert2 confirmation dialog
        const selectedPeriodText = $('#PeriodID option:selected').text();
        const selectedMemberText = $('#staff_id_filter option:selected').text();

        const result = await Swal.fire({
            title: 'Confirm Processing',
            html: `Are you sure you want to process transactions?<br>
                   <small class="text-muted"><strong>Period:</strong> ${selectedPeriodText}</small><br>
                   <small class="text-muted"><strong>For:</strong> ${selectedMemberText}</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Process',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d'
        });

        if (!result.isConfirmed) return;

        // Show overlay
        $('#overlay').fadeIn();
        try {
            var formData = $(this).serialize();
            $.ajax({
                type: 'GET',
                url: 'class/process_transactions.php',
                data: formData,
                xhrFields: {
                    onprogress: function(e) {
                        $('#sample_1').html(e.target.responseText);
                        console.log(e.target.responseText);
                    }
                },
                success: function(response) {
                    $('#overlay').fadeOut('fast', function() {
                        $("#statusDetails").html(response);
                    });
                    Swal.fire({
                        title: 'Done!',
                        text: 'Transactions processed successfully.',
                        icon: 'success',
                        confirmButtonColor: '#0d6efd'
                    });
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Form submission failed. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                },
                complete: function() {
                    $('#overlay').fadeOut();
                }
            });
        } catch (error) {
            console.error('An error occurred:', error);
        }

    });
})
</script>



</html>

</html>