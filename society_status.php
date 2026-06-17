<?php session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location:index.php");
    exit();
}

require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();

$periods = $dbHandler->getOrderedItem('tbpayrollperiods', 'Periodid', 'PayrollPeriod');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - Society Status</title>
    <?php include "includes/header.php"; ?>
</head>

<body id="body-pd">
    <?php include('includes/header_nav.php'); ?>
    
    <div id="overlay" style="display:none;">
        <div class="overlay-content text-center">
            <div class="spinner-border text-light" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <h4 class="text-light mt-3">Fetching Society Status...</h4>
        </div>
    </div>

    <?php include "includes/sidebar2.php"; ?>

    <main class="top-margin">
                <div class="card">
                    <div class="card-header">
                        Society Financial Status
                    </div>
                    <div class="card-body">
                        <form id="societyStatusForm">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="period" class="form-label">Select Period (As at):</label>
                                        <select name="period" id="period" class="form-control custom-select">
                                            <option value="">-- Choose Period --</option>
                                            <?php foreach ($periods as $period) { ?>
                                                <option value="<?php echo $period['Periodid']; ?>" <?php if (isset($_SESSION['period']) && $_SESSION['period'] == $period['Periodid']) echo "selected"; ?>>
                                                    <?php echo $period['PayrollPeriod']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-3">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search me-2"></i>Query Status
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="statusDetails" class="mt-4">
                    <div class="text-center p-5" style="color:var(--text-muted);">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <p>Select a period and click "Query Status" to view the society's financial summary.</p>
                    </div>
                </div>
    </main>

    <?php include("includes/nav_script.php"); ?>

    <script>
        $(document).ready(function() {
            $('#societyStatusForm').on('submit', function(event) {
                event.preventDefault();
                
                let period = $('#period').val();
                
                if (period === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: 'Please select a period to query.'
                    });
                    return;
                }

                $('#overlay').fadeIn();

                $.ajax({
                    type: 'GET',
                    url: 'fetch_society_status.php',
                    data: { period: period },
                    success: function(response) {
                        $('#statusDetails').hide().html(response).fadeIn('slow');
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to fetch society status.'
                        });
                    },
                    complete: function() {
                        $('#overlay').fadeOut();
                    }
                });
            });

            // Auto-trigger if a period is already selected from session
            if ($('#period').val() !== '') {
                $('#societyStatusForm').trigger('submit');
            }
        });
    </script>
</body>

</html>
