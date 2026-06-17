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
    <title>NASU, OOUTH - Add Loan</title>
    <?php include "includes/header.php"; ?>
    <!-- Additional custom CSS can go here -->
</head>

<body id="body-pd">
    <?php include('includes/header_nav.php'); ?>
    <div id="overlay" style="display:none;">
        <div class="overlay-content">

            <div class="spinner-border text-light" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <h4 class="text-light mt-3">Working...</h4>
        </div>
    </div>

    <?php include "includes/sidebar2.php"; ?>


    <!-- Edit Loan Modal -->
    <div class="modal fade" id="editLoanModal" tabindex="-1" aria-labelledby="editLoanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLoanModalLabel">
                        <i class="fas fa-edit" style="color:var(--accent-blue);margin-right:0.5rem;"></i>Edit Loan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editLoanId">
                    <div class="form-group mb-3">
                        <label style="font-size:0.8rem;color:var(--text-muted);">Staff Name</label>
                        <input type="text" class="form-control" id="editLoanName" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label style="font-size:0.8rem;color:var(--text-muted);">Staff No.</label>
                        <input type="text" class="form-control" id="editLoanStaff" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label style="font-size:0.8rem;color:var(--text-muted);">Amount Granted (Principal)</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" class="form-control" id="editAmountGranted" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label style="font-size:0.8rem;color:var(--text-muted);">Interest</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" class="form-control" id="editInterest" readonly>
                        </div>
                    </div>
                    <div style="background:var(--bg-active);border:1px solid var(--border-accent);border-radius:var(--radius-sm);padding:0.75rem 1rem;font-family:var(--font-mono);font-size:0.85rem;">
                        <span style="color:var(--text-muted);">New Total:</span>
                        <span id="editTotal" style="color:var(--accent);font-weight:700;margin-left:0.5rem;">₦0.00</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditLoanBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                <input type="hidden" name="interestRate" id="interestRate" value="<?php echo $interestRate; ?>">
                <div class="card">
                    <h5 class="card-header">Add Loan</h5>
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
                                <label for="loan_balance">Loan Balance:</label>
                            </div>

                            <div class="input-group mb-3" id="loanBalanceInputGroup">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">₦</span>
                                </div>
                                <input type="text" class="form-control" name="loan_balance" id="loan_balance" readonly>
                            </div>

                            <div class="form-group">
                                <label for="amountGranted">Amount Granted:</label>
                                <input type="number" class="form-control" name="amountGranted" id="amountGranted" value="0.0">
                            </div>

                            <div class="form-group">
                                <label for="interest">Interest:</label>
                                <input type="number" class="form-control" name="interest" id="interest" readonly>
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
                                <button type="submit" class="btn btn-primary" name="Submit" value="Save">Save</button>
                            </div>
                        </form>
                    </div>

                    <div id="loanDetails"></div>
                    <!-- Additional form fields here -->

                </div>
    </main>

</body>

<?php include("includes/nav_script.php"); ?>
<script>
    $(document).ready(function() {

        var period = $("#period").val();
        fetchLoanDetails(period);

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
                fetchLoanBalance(ui.item.value)
                $("#amountGranted").select();
                return false;
            }
        });

        function fetchLoanBalance(staffId) {
            // Example AJAX call to fetch the loan balance
            $.ajax({
                url: 'getBalance.php', // Adjust this to your server-side script
                data: {
                    staff_id: staffId
                },
                dataType: 'json',
                success: function(response) {
                    $('#loan_balance').val(response); // Assuming response contains the loan balance
                },
                error: function() {
                    console.error('Failed to fetch loan balance');
                    // Handle errors here
                }
            });
        }

        function fetchLoanDetails(period) {
            $('#overlay').fadeIn();
            $.ajax({
                url: 'getLoanDetails.php',
                type: 'POST',
                data: {
                    periodid: period
                },
                // dataType: 'json',
                success: function(response) {
                    $('#overlay').fadeOut('fast', function() {
                        $('#loanDetails').html(response);
                    });

                },
                error: function(xhr, status, error) {
                    // console.error('Failed to fetch loan balance');
                    $('#overlay').fade('fast', function() {
                        Swal.fire({icon:'warning', text:error});
                    });

                }
            });
        }

        $('#period').on('change', function() {
            var period = $(this).val();
            $.ajax({
                url: 'setPeriodSession.php', // Adjust the path to point to a PHP script where you use the fetchLoanBalance method
                type: 'GET',
                data: {
                    period: period
                },
                success: function(response) {
                    fetchLoanDetails(period);
                },
                error: function(xhr, status, error) {
                    $('#overlay').fade('fast', function() {
                        Swal.fire({icon:'warning', text:error});
                    });

                    //console.log("Error fetching loan balance: " + error);
                }
            });



        });


        $('#amountGranted').on('blur', function() {
            var amountGranted = parseFloat($(this).val());

            var interestRate = parseFloat($('#interestRate').val());

            var interest = amountGranted * interestRate;

            $('#interest').val(interest.toFixed(2));

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
                // Nigerian Naira (NGN) formatting
                var formatter = new Intl.NumberFormat('en-NG', {
                    style: 'currency',
                    currency: 'NGN'
                });

                var period = $("#period").val();
                var name = $("#name").val();
                var loan_interest = (parseFloat($("#amountGranted").val()) + parseFloat($("#interest").val())).toFixed(2)
                var amountGranted = formatter.format(loan_interest);
                var formData = $(this).serialize(); // Serializes form data for Ajax
                $.ajax({
                    type: 'POST',
                    url: 'saveLoanRequest.php', // Adjust if necessary
                    data: formData,
                    success: function(response) {
                        // Handle success
                        displayAlert(amountGranted + ' loan granted to ' + name + ' successfully', 'center', 'success')
                        $('#loanForm')[0].reset(); // Clear form
                        fetchLoanDetails(period)
                    },
                    error: function() {
                        // Handle error
                        displayAlert('Error saving', 'center', 'error')
                    },
                    complete: function() {
                        // Always executed after the AJAX call completes
                        $('#overlay').fadeOut();
                    }
                });
            } catch (error) {
                displayAlert('Error saving', 'center', 'error')
                console.error('An error occurred:', error);
                $('#overlay').fadeOut();
            }
        });

    });
</script>



<script>
// Edit loan — event delegation (table loaded via AJAX into #loanDetails)
$(document).on('click', '.edit-loan-btn', function () {
    var btn = $(this);
    $('#editLoanId').val(btn.data('loanid'));
    $('#editLoanName').val(btn.data('name'));
    $('#editLoanStaff').val(btn.data('staff_id'));
    $('#editAmountGranted').val(parseFloat(btn.data('principal')));
    $('#editInterest').val(parseFloat(btn.data('interest')));
    updateEditTotal();
    var modal = new bootstrap.Modal(document.getElementById('editLoanModal'));
    modal.show();
});

function updateEditTotal() {
    var principal = parseFloat($('#editAmountGranted').val()) || 0;
    var interest  = parseFloat($('#editInterest').val())  || 0;
    var total = principal + interest;
    $('#editTotal').text('₦' + total.toLocaleString('en-NG', { minimumFractionDigits: 2 }));
}

$('#editAmountGranted').on('input', function () {
    var principal    = parseFloat($(this).val()) || 0;
    var interestRate = parseFloat($('#interestRate').val()) || 0;
    var interest     = principal * interestRate;
    $('#editInterest').val(interest.toFixed(2));
    updateEditTotal();
});

$('#saveEditLoanBtn').on('click', function () {
    var loanId        = $('#editLoanId').val();
    var amountGranted = parseFloat($('#editAmountGranted').val());
    var interest      = parseFloat($('#editInterest').val());

    if (!amountGranted || amountGranted <= 0) {
        Swal.fire({ icon: 'warning', title: 'Invalid', text: 'Enter a valid loan amount.' });
        return;
    }

    var btn = $(this).prop('disabled', true).text('Saving…');

    $.ajax({
        url: 'updateLoan.php',
        type: 'POST',
        data: { loanId: loanId, amountGranted: amountGranted, interest: interest },
        dataType: 'json',
        success: function (res) {
            bootstrap.Modal.getInstance(document.getElementById('editLoanModal')).hide();
            if (res.status === 'success') {
                displayAlert('Loan updated successfully', 'center', 'success');
                var period = $('#period').val();
                if (period) {
                    $('#overlay').fadeIn();
                    $.post('getLoanDetails.php', { periodid: period }, function (html) {
                        $('#overlay').fadeOut('fast', function () { $('#loanDetails').html(html); });
                    });
                }
            } else {
                displayAlert(res.message || 'Update failed', 'center', 'error');
            }
        },
        error: function () {
            displayAlert('Error updating loan', 'center', 'error');
        },
        complete: function () {
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Changes');
        }
    });
});
</script>
</html>