<?php
session_start();
require_once('class/DataBaseHandler.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming you've sanitized and validated your inputs
    $dbHandler = new DataBaseHandler();

    $periodid = isset($_POST['periodid']) ? $_POST['periodid'] : -1;

    #Get loan details from selected periods
    $loans = $dbHandler->getLoanDetails(intval($periodid));

    //  exit;
}



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleted'])) {

    $loans = [];
    // Assuming you've sanitized and validated your inputs
    $selectedLoans = $_POST['selectedLoans'] ?? [];

    // Delete selected loans
    foreach ($selectedLoans as $loanId) {
        $dbHandler->deleteRows("tbl_loan", "loanid", intval($loanId)); // Implement this method in your class
    }

    foreach ($selectedLoans as $loanId) {
        $dbHandler->deleteRows("tlb_mastertransaction", "loanID", intval($loanId)); // Implement this method in your class
    }


    //   header("Location: " . $_SERVER['PHP_SELF']); // Optional: Redirect to prevent form resubmission
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form method="POST" id="deleteLoanForm" name="deleteLoanForm">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">
                            <button type="button" class="btn btn-outline-danger" name="delete" id="delete">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                    <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"></path>
                                </svg>
                            </button>
                            <input type="checkbox" id="selectAllLoans">
                        </th>
                        <th scope="col">#</th>
                        <th scope="col">Staff No.</th>
                        <th scope="col">Name</th>
                        <th scope="col" class="text-right">Loan Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    $total = 0;
                    foreach ($loans as $loan) { ?>
                        <tr>
                            <td><input type="checkbox" name="selectedLoans[]" value="<?php echo $loan['loanid']; ?>"></td>
                            <th scope="row"><?php echo $i; ?></th>
                            <td><?php echo $loan['staff_id']; ?></td>
                            <td><?php echo $loan['name']; ?></td>
                            <td class="text-right">₦<?php echo number_format($loan['loanamount']); ?></td>
                        </tr>
                    <?php $i++;
                        $total += $loan['loanamount'];
                    } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="row" colspan="3" class="text-right">

                        </th>
                        <th scope="row" class="text-right">Total</th>
                        <td class="text-right">₦<?php echo number_format($total); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
            <input type="hidden" name="deleted" id="deleted">
    </form>


    <script>
        $(document).ready(function() {
            $('#selectAllLoans').click(function(event) {
                if (this.checked) {
                    // Iterate each checkbox and check
                    $(':checkbox').each(function() {
                        this.checked = true;
                    });
                } else {
                    $(':checkbox').each(function() {
                        this.checked = false;
                    });
                }
            });

            $('#delete').click(function(e) {
                // Check if at least one checkbox is checked
                if ($('input[type="checkbox"]:checked').length === 0) {
                    alert('Please select at least one loan to delete.');
                    return; // Stop the function if no checkboxes are checked
                }
                if (confirm("Are you sure you want to delete these loans")) {
                    $('#overlay').fadeIn();
                    e.preventDefault(); // Prevent default form submission

                    var formData = $('#deleteLoanForm').serialize(); // Serialize form data

                    $.ajax({
                        url: 'getLoanDetails.php', // Adjust this to your server-side script for deleting loans
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            $('#overlay').fadeOut('fast', function() {
                                alert('Selected loan have been deleted.');
                                location.reload(); // Reload the page to reflect changes
                            });
                        },
                        error: function() {
                            $('#overlay').fadeOut('fast', function() {
                                alert('An error occured');

                            });
                        }
                    });
                }

            });

        });
    </script>
</body>

</html>