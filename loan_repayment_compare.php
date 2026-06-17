<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
} else {
}

require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();
//Nok relationionship

// Fetch periods for the dropdown
$periods = $dbHandler->getOrderedItem('tbpayrollperiods', 'Periodid', 'PayrollPeriod');


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - Loan to Repayment Compare</title>
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
    <main class="top-margin">

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
                    <h5 class="card-header">Loan to Repayment Compare</h5>
                    <div class="card-body">


                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="periodSelect" class="form-label">Select Period:</label>
                                <select id="periodSelect" class="form-control">
                                    <?php foreach ($periods as $period) : ?>
                                        <option value="<?= $period['Periodid']; ?>"><?= $period['PayrollPeriod']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Added Filters -->
                            <div class="col-md-4">
                                <label for="tableSearch" class="form-label">Search:</label>
                                <div class="position-relative">
                                    <input type="text" id="tableSearch" class="form-control" placeholder="Name or Staff No...">
                                    <span id="clearSearch" class="text-muted" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer; display: none; font-size: 1.2rem;">&times;</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="filterStatus" class="form-label">Filter Status:</label>
                                <select id="filterStatus" class="form-control">
                                    <option value="All">All</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Reduce Repayment">Reduce Repayment</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class='table-responsive'>
                        <table class='table table-striped'>
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Staff No.</th>
                                    <th scope="col">Name</th>
                                    <th scope="col" class="text-right">Loan Balance</th>
                                    <th scope="col" class="text-right">Loan Repayment</th>
                                    <th scope="col" class="text-right"> Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                            <tbody id="loanCompareBody">
                                <!-- Data injected via AJAX -->
                                <tr><td colspan="6" class="text-center">Loading...</td></tr>
                            </tbody>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th scope="row" colspan="2" class="text-right">

                                    </th>
                                    <th scope="row" class="text-right"></th>
                                    <td class="text-right"></td>
                                    <td class="text-right"></td>
                                    <td class="text-right"></td>
                                </tr>
                            </tfoot>
                        </table>

                    </div>

                </div>
    </main>

    <?php include("includes/nav_script.php"); ?>
</body>


<script>
    $(document).ready(function() {
        
        let allData = []; // Store fetched data

        // Fetch Data on Load
        var initialPeriod = $('#periodSelect').val();
        fetchLoanComparison(initialPeriod);

        // Events
        $('#periodSelect').on('change', function() {
            fetchLoanComparison($(this).val());
        });

        $('#tableSearch').on('keyup', function() {
            // Show/Hide Clear Button
            if ($(this).val().length > 0) {
                $('#clearSearch').show();
            } else {
                $('#clearSearch').hide();
            }
            filterData();
        });

        // Clear Search Logic
        $('#clearSearch').on('click', function() {
            $('#tableSearch').val('');
            $(this).hide();
            filterData();
        });

        $('#filterStatus').on('change', filterData);

        function fetchLoanComparison(periodId) {
            if (!periodId) return;

            $('#loanCompareBody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');

            $.ajax({
                url: 'loanContri_api.php',
                type: 'POST',
                data: { 
                    action: 'fetch_comparison',
                    period_id: periodId 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        allData = response.data.list; // Store data
                        filterData(); // Initial render
                    } else {
                        Swal.fire({icon:'error', title:'Error', text: 'Error fetching data: ' + response.message});
                    }
                },
                error: function() {
                    console.error('Failed to fetch data');
                    $('#loanCompareBody').html('<tr><td colspan="6" class="text-center text-danger">Error loading data.</td></tr>');
                }
            });
        }

        function filterData() {
            const term = $('#tableSearch').val().toLowerCase();
            const stat = $('#filterStatus').val();

            const filtered = allData.filter(item => {
                const matchesSearch = item.name.toLowerCase().includes(term) || item.staff_no.toString().includes(term);
                const matchesStatus = stat === 'All' || item.status === stat;
                return matchesSearch && matchesStatus;
            });

            renderTable(filtered);
        }

        function renderTable(data) {
            var tbody = $('#loanCompareBody');
            tbody.empty();

            if (data.length === 0) {
                tbody.html('<tr><td colspan="6" class="text-center">No records found.</td></tr>');
                return;
            }

            $.each(data, function(index, item) {
                var remarkHtml = '';
                if (item.status === 'Reduce Repayment') {
                    remarkHtml = '<span style="color:var(--accent-red);font-weight:600;">Reduce Repayment</span>';
                } else {
                    remarkHtml = '<span style="color:var(--accent);font-weight:600;">Normal</span>';
                }

                // Format numbers - consistent with loanContri_Compare.php
                const fmt = (val) => '₦ ' + parseFloat(val || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 });
                var loanBalance = fmt(item.loan_balance);
                var loanRepayment = fmt(item.loan_repayment);

                var row = `<tr>
                    <th scope="row">${index + 1}</th>
                    <td>${item.staff_no}</td>
                    <td>${item.name}</td>
                    <td class="text-right">${loanBalance}</td>
                    <td class="text-right">${loanRepayment}</td>
                    <td class="text-right">${remarkHtml}</td>
                </tr>`;
                
                tbody.append(row);
            });
        }

        var staff_id = $("#staff_id").val();
        // fetchloanComparesDetails(staff_id); // Removed dead call to missing file

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
                // fetchloanComparesDetails(ui.item.value)
                return false;
            }
        });

        /*
        function fetchloanComparesDetails(staffId) {
             // Function removed: getloanComparesDetails.php does not exist
        }
        */

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
                    Swal.fire({icon:'warning', text:error});
                }
            });
        }


        $('#selectName').on('change', function() {
            var staff_id = $(this).val();
            // fetchloanComparesDetails(staff_id);
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
                        Swal.fire({icon:'warning', text:'Form submitted successfully.'});
                        $('#loanForm')[0].reset(); // Clear form
                        fetchLoanDetails(period)
                    },
                    error: function() {
                        // Handle error
                        Swal.fire({icon:'warning', text:'Form submission failed.'});
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