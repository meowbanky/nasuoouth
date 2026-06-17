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
    <main class="top-margin">
        <div class="d-flex justify-content-between align-items-center pb-2 mb-3" style="border-bottom:1px solid var(--border-light);">
            <h5 style="font-family:var(--font-mono);font-weight:700;margin:0;">Dashboard</h5>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card h-100">
                    <div class="card-body" style="padding:1rem;">
                        <div style="color:var(--accent-blue);font-size:1.4rem;margin-bottom:0.35rem;"><i class='bx bx-group'></i></div>
                        <div style="font-size:1.5rem;font-weight:700;font-family:var(--font-mono);"><?= htmlspecialchars($activeMemberCount) ?></div>
                        <div style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.05em;margin-top:0.25rem;">Active Members</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card h-100">
                    <div class="card-body" style="padding:1rem;">
                        <div style="color:var(--accent);font-size:1.4rem;margin-bottom:0.35rem;"><i class='bx bx-donate-heart'></i></div>
                        <div style="font-size:1rem;font-weight:700;font-family:var(--font-mono);">₦<?= number_format($totalWelfare) ?></div>
                        <div style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.05em;margin-top:0.25rem;">Contributions</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card h-100">
                    <div class="card-body" style="padding:1rem;">
                        <div style="color:var(--accent-amber);font-size:1.4rem;margin-bottom:0.35rem;"><i class='bx bx-money'></i></div>
                        <div style="font-size:1rem;font-weight:700;font-family:var(--font-mono);">₦<?= number_format($totalOutstanding) ?></div>
                        <div style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.05em;margin-top:0.25rem;">Outstanding Loans</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card h-100">
                    <div class="card-body" style="padding:1rem;">
                        <div style="color:var(--accent-blue);font-size:1.4rem;margin-bottom:0.35rem;"><i class='bx bx-male'></i></div>
                        <div style="font-size:1.5rem;font-weight:700;font-family:var(--font-mono);"><?= $maleCount ?></div>
                        <div style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.05em;margin-top:0.25rem;">Male</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card h-100">
                    <div class="card-body" style="padding:1rem;">
                        <div style="color:#F472B6;font-size:1.4rem;margin-bottom:0.35rem;"><i class='bx bx-female'></i></div>
                        <div style="font-size:1.5rem;font-weight:700;font-family:var(--font-mono);"><?= $feMaleCount ?></div>
                        <div style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.05em;margin-top:0.25rem;">Female</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Monthly Contributions — <?php echo $currentYearDate; ?></div>
                    <div class="card-body"><canvas id="contributionChartCur"></canvas></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Monthly Contributions — <?php echo $previousYearDate; ?></div>
                    <div class="card-body"><canvas id="contributionChartPre"></canvas></div>
                </div>
            </div>
        </div>
    </main>


</body>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const darkChartDefaults = {
    color: '#94A3B8',
    borderColor: '#334155',
    backgroundColor: 'rgba(34,197,94,0.08)',
    scales: {
        x: { ticks: { color: '#64748B' }, grid: { color: '#1E293B' } },
        y: { beginAtZero: true, ticks: { color: '#64748B' }, grid: { color: '#1E293B' } }
    },
    plugins: { legend: { labels: { color: '#94A3B8' } } }
};

const ctxCur = document.getElementById('contributionChartCur').getContext('2d');
new Chart(ctxCur, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels_curr); ?>,
        datasets: [{
            label: 'Contributions',
            data: <?php echo json_encode($data_curr); ?>,
            backgroundColor: 'rgba(34,197,94,0.12)',
            borderColor: '#22C55E',
            borderWidth: 2,
            pointBackgroundColor: '#22C55E',
            tension: 0.3,
            fill: true
        }]
    },
    options: { ...darkChartDefaults }
});

const ctxPre = document.getElementById('contributionChartPre').getContext('2d');
new Chart(ctxPre, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels_pre); ?>,
        datasets: [{
            label: 'Contributions',
            data: <?php echo json_encode($data_pre); ?>,
            backgroundColor: 'rgba(59,130,246,0.12)',
            borderColor: '#3B82F6',
            borderWidth: 2,
            pointBackgroundColor: '#3B82F6',
            tension: 0.3,
            fill: true
        }]
    },
    options: { ...darkChartDefaults }
});
</script>
<?php include("includes/nav_script.php"); ?>

</html>