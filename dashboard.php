<?php session_start();

if (!isset($_SESSION['UserID'])) {

    header("Location:index.php");
}

require_once('class/DataBaseHandler.php');

$currentYearDate = intval(date('Y'));

$previousYearDate = intval(date('Y') - 1);

$dbHandler = new DataBaseHandler();


$activeMemberCount = $dbHandler->getActiveMembersCount('*', 'status', 1);
$maleCount = $dbHandler->getActiveMembersCount('*', 'gender', 'male');
$feMaleCount = $dbHandler->getActiveMembersCount('*', 'gender', 'female');

$currentYears = $dbHandler->getMonthlyContributionsForCurrentYear($currentYearDate);

$previousYears = $dbHandler->getMonthlyContributionsForCurrentYear($previousYearDate);

$totalWelfare = $dbHandler->getSumWithoutFilter('tlb_mastertransaction', 'Contribution');

$totalLoan = $dbHandler->getSumWithoutFilter('tlb_mastertransaction', 'loanAmount');

$totalInterest = $dbHandler->getSumWithoutFilter('tlb_mastertransaction', 'interest');

$totalLoanRepayment = $dbHandler->getSumWithoutFilter('tlb_mastertransaction', 'loanRepayment');

$totalOutstanding = ($totalLoan + $totalInterest) - $totalLoanRepayment;

$labels_curr = [];
$data_curr = [];

$labels_pre = [];
$data_pre = [];

foreach ($currentYears as $currentYear) {
    $labels_curr[] = $currentYear['label'];
    $data_curr[] = $currentYear['total'];
}


foreach ($previousYears as $previousYear) {
    $labels_pre[] = $previousYear['label'];
    $data_pre[] = $previousYear['total'];
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU, OOUTH - Dashboard</title>
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
    <div class="container-fluid">
        <div class="row flex-nowrap">

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Dashboard content here -->
                <p>Welcome to your dashboard, <?php echo htmlspecialchars($_SESSION['FirstName'] ?? ''); ?>!</p>
                <!-- You can add more dashboard widgets or content here -->
                <!-- Dashboard Widgets -->
                <div class="row">
                    <!-- Summary Card Example -->
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-primary mb-3" style="max-width: 18rem;">
                            <div class="card-header">Members</div>
                            <div class="card-body">
                                <h5 class="card-title">Total Active Members</h5>
                                <p class="card-text"><?php echo htmlspecialchars($activeMemberCount) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Another Summary Card Example -->
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-success mb-3" style="max-width: 18rem;">
                            <div class="card-header">Contributions</div>
                            <div class="card-body">
                                <h5 class="card-title">Total Contributions</h5>
                                <p class="card-text">₦ <?php echo number_format($totalWelfare) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-warning mb-3" style="max-width: 18rem;">
                            <div class="card-header">Outstanding Loan</div>
                            <div class="card-body">
                                <h5 class="card-title">Active Loans</h5>
                                <p class="card-text"><?php echo number_format($totalOutstanding); ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Yet Another Summary Card Example -->
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-warning mb-3" style="max-width: 18rem;">
                            <div class="card-header">Male Gender</div>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <symbol id="bx--male" viewBox="0 0 24 24">
                                        <circle cx="12" cy="4" r="2" fill="currentColor" />
                                        <path fill="currentColor"
                                            d="M15 7H9a1 1 0 0 0-1 1v7h2v7h4v-7h2V8a1 1 0 0 0-1-1" />
                                    </symbol><i class='bx bx-male nav_icon'></i>
                                </h5>
                                <p class="card-text"><?= $maleCount ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card text-white bg-success mb-3" style="max-width: 18rem;">
                            <div class="card-header">Female Gender</div>
                            <div class="card-body">
                                <h5 class="card-title"><i class='bx bx-female nav_icon'></i></h5>
                                <p class="card-text"><?= $feMaleCount ?></p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Add more widgets or content here -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card-header">Current Year - <?php echo $currentYearDate; ?></div>
                        <canvas id="contributionChartCur" width="400" height="200"></canvas>

                    </div>
                    <div class="col-md-6">
                        <div class="card-header">Previous Year - <?php echo $previousYearDate; ?></div>
                        <canvas id="contributionChartPre" width="400" height="200"></canvas>

                    </div>
                </div>

            </main>
        </div>

    </div>


</body>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctxCur = document.getElementById('contributionChartCur').getContext('2d');
const contributionChartCur = new Chart(ctxCur, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels_curr); ?>, // Replace these with your actual data labels
        datasets: [{
            label: 'Monthly Contributions',
            data: <?php echo json_encode($data_curr); ?>, // Replace these with your actual data points
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<script>
const ctxPre = document.getElementById('contributionChartPre').getContext('2d');
const contributionChartPre = new Chart(ctxPre, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels_pre); ?>, // Replace these with your actual data labels
        datasets: [{
            label: 'Monthly Contributions',
            data: <?php echo json_encode($data_pre); ?>, // Replace these with your actual data points
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
<?php include("includes/nav_script.php"); ?>

</html>