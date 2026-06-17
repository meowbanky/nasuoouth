<?php session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location:index.php");
}

require_once('class/DataBaseHandler.php');

$currentYearDate  = intval(date('Y'));
$previousYearDate = intval(date('Y') - 1);

$dbHandler = new DataBaseHandler();

$activeMemberCount   = $dbHandler->getActiveMembersCount('*', 'status', 1);
$maleCount           = $dbHandler->getActiveMembersCount('*', 'gender', 'male');
$feMaleCount         = $dbHandler->getActiveMembersCount('*', 'gender', 'female');
$currentYears        = $dbHandler->getMonthlyContributionsForCurrentYear($currentYearDate);
$previousYears       = $dbHandler->getMonthlyContributionsForCurrentYear($previousYearDate);
$totalWelfare        = $dbHandler->getSumWithoutFilter('tlb_mastertransaction', 'Contribution');
$totalLoan           = $dbHandler->getSumWithoutFilter('tlb_mastertransaction', 'loanAmount');
$totalInterest       = $dbHandler->getSumWithoutFilter('tlb_mastertransaction', 'interest');
$totalLoanRepayment  = $dbHandler->getSumWithoutFilter('tlb_mastertransaction', 'loanRepayment');
$totalOutstanding    = ($totalLoan + $totalInterest) - $totalLoanRepayment;

$labels_curr = []; $data_curr = [];
$labels_pre  = []; $data_pre  = [];
foreach ($currentYears  as $y) { $labels_curr[] = $y['label']; $data_curr[] = $y['total']; }
foreach ($previousYears as $y) { $labels_pre[]  = $y['label']; $data_pre[]  = $y['total']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NASU OOUTH — Dashboard</title>
    <?php include('includes/header.php'); ?>
</head>

<body id="body-pd">
    <?php include('includes/header_nav.php'); ?>
    <?php include 'includes/sidebar2.php'; ?>

    <main class="top-margin">

        <!-- Page header -->
        <div class="page-title">
            <h5><i class='bx bx-grid-alt' style="color:var(--accent);margin-right:0.5rem;"></i>Dashboard</h5>
            <span style="font-family:var(--font-mono);font-size:0.75rem;color:var(--text-muted);"><?= date('D, d M Y') ?></span>
        </div>

        <!-- ── KPI Stat Cards ───────────────────────── -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card green card-enter">
                    <div class="stat-icon green"><i class='bx bx-group'></i></div>
                    <div class="stat-value" data-target="<?= $activeMemberCount ?>" data-count>0</div>
                    <div class="stat-label">Active Members</div>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> Live</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card gold card-enter">
                    <div class="stat-icon gold"><i class='bx bx-donate-heart'></i></div>
                    <div class="stat-value" style="font-size:1rem;" data-currency="<?= $totalWelfare ?>">₦0</div>
                    <div class="stat-label">Total Contributions</div>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> All time</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card red card-enter">
                    <div class="stat-icon red"><i class='bx bx-money-withdraw'></i></div>
                    <div class="stat-value" style="font-size:1rem;" data-currency="<?= $totalOutstanding ?>">₦0</div>
                    <div class="stat-label">Outstanding Loans</div>
                    <div class="stat-trend down"><i class="fas fa-arrow-down"></i> Balance</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card blue card-enter">
                    <div class="stat-icon blue"><i class='bx bx-money'></i></div>
                    <div class="stat-value" style="font-size:1rem;" data-currency="<?= $totalLoan ?>">₦0</div>
                    <div class="stat-label">Total Loans Issued</div>
                    <div class="stat-trend up"><i class="fas fa-arrow-up"></i> All time</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card blue card-enter">
                    <div class="stat-icon blue"><i class='bx bx-male'></i></div>
                    <div class="stat-value" data-target="<?= $maleCount ?>" data-count>0</div>
                    <div class="stat-label">Male Members</div>
                    <div class="stat-trend up"><i class="fas fa-mars"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card purple card-enter">
                    <div class="stat-icon pink"><i class='bx bx-female'></i></div>
                    <div class="stat-value" data-target="<?= $feMaleCount ?>" data-count>0</div>
                    <div class="stat-label">Female Members</div>
                    <div class="stat-trend up"><i class="fas fa-venus"></i></div>
                </div>
            </div>
        </div>

        <!-- ── Charts ──────────────────────────────── -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card card-enter" style="animation-delay:0.3s;">
                    <div class="card-header">
                        <i class='bx bx-line-chart' style="color:var(--accent);"></i>
                        Monthly Contributions &mdash; <?= $currentYearDate ?>
                    </div>
                    <div class="card-body"><canvas id="chartCur" height="200"></canvas></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-enter" style="animation-delay:0.35s;">
                    <div class="card-header">
                        <i class='bx bx-bar-chart-alt-2' style="color:var(--accent-blue);"></i>
                        Monthly Contributions &mdash; <?= $previousYearDate ?>
                    </div>
                    <div class="card-body"><canvas id="chartPre" height="200"></canvas></div>
                </div>
            </div>
        </div>

        <!-- ── Quick Links ─────────────────────────── -->
        <div class="row g-3 mt-1">
            <?php
            $links = [
                ['href'=>'process.php',        'icon'=>'bx-cog',              'label'=>'Process Transactions', 'color'=>'green'],
                ['href'=>'contribution.php',    'icon'=>'bx-donate-heart',     'label'=>'Edit Contributions',   'color'=>'gold'],
                ['href'=>'add_loan.php',        'icon'=>'bx-money',            'label'=>'Add Loan',             'color'=>'blue'],
                ['href'=>'registrationForm.php','icon'=>'bx-user-plus',        'label'=>'Register Member',      'color'=>'purple'],
                ['href'=>'masterTrans.php',     'icon'=>'bx-list-check',       'label'=>'Master Transaction',   'color'=>'green'],
                ['href'=>'society_status.php',  'icon'=>'bx-stats',            'label'=>'Society Status',       'color'=>'gold'],
            ];
            foreach ($links as $l):
            ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= $l['href'] ?>" class="stat-card <?= $l['color'] ?> card-enter d-flex flex-column align-items-center justify-content-center text-center" style="text-decoration:none;min-height:90px;cursor:pointer;">
                    <i class='bx <?= $l['icon'] ?> stat-icon <?= $l['color'] ?>' style="width:auto;height:auto;padding:0.5rem;font-size:1.35rem;margin-bottom:0.5rem;"></i>
                    <span style="font-size:0.75rem;font-weight:600;color:var(--text-secondary);line-height:1.3;"><?= $l['label'] ?></span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php include('includes/nav_script.php'); ?>

    <script>
    // ── Count-up animation ──────────────────────────
    function animateCount(el, target, duration) {
        let start = 0, step = target / (duration / 16);
        function update() {
            start = Math.min(start + step, target);
            el.textContent = Math.round(start).toLocaleString('en-NG');
            if (start < target) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }
    function animateCurrency(el, target, duration) {
        let start = 0, step = target / (duration / 16);
        function update() {
            start = Math.min(start + step, target);
            el.textContent = '₦' + Math.round(start).toLocaleString('en-NG');
            if (start < target) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }
    document.querySelectorAll('[data-count]').forEach(el => {
        animateCount(el, parseInt(el.dataset.target || 0), 800);
    });
    document.querySelectorAll('[data-currency]').forEach(el => {
        animateCurrency(el, parseFloat(el.dataset.currency || 0), 1000);
    });

    // ── Chart defaults ──────────────────────────────
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0D1B30',
                borderColor: 'rgba(255,255,255,0.1)',
                borderWidth: 1,
                titleColor: '#94A3B8',
                bodyColor: '#F1F5F9',
                padding: 10,
                callbacks: {
                    label: ctx => ' ₦' + ctx.parsed.y.toLocaleString('en-NG')
                }
            }
        },
        scales: {
            x: {
                ticks: { color: '#475569', font: { family: 'IBM Plex Mono', size: 10 } },
                grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#475569',
                    font: { family: 'IBM Plex Mono', size: 10 },
                    callback: v => '₦' + (v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'K' : v)
                },
                grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false }
            }
        },
        animation: { duration: 800, easing: 'easeInOutQuart' }
    };

    // Current year chart
    new Chart(document.getElementById('chartCur'), {
        type: 'line',
        data: {
            labels: <?= json_encode($labels_curr) ?>,
            datasets: [{
                data: <?= json_encode($data_curr) ?>,
                borderColor: '#22C55E',
                backgroundColor: ctx => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                    g.addColorStop(0, 'rgba(34,197,94,0.18)');
                    g.addColorStop(1, 'rgba(34,197,94,0.01)');
                    return g;
                },
                borderWidth: 2.5,
                pointBackgroundColor: '#22C55E',
                pointBorderColor: '#060C1A',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4,
                fill: true
            }]
        },
        options: chartDefaults
    });

    // Previous year chart
    new Chart(document.getElementById('chartPre'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_pre) ?>,
            datasets: [{
                data: <?= json_encode($data_pre) ?>,
                backgroundColor: ctx => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                    g.addColorStop(0, 'rgba(59,130,246,0.6)');
                    g.addColorStop(1, 'rgba(59,130,246,0.1)');
                    return g;
                },
                borderColor: 'rgba(59,130,246,0.8)',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: chartDefaults
    });
    </script>
</body>
</html>
