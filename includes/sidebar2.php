<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

function isActivePage($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>

<div class="l-navbar" id="nav-bar" role="navigation" aria-label="Main navigation">
    <nav class="nav">
        <div style="flex:1;display:flex;flex-direction:column;min-height:0;">
            <!-- Logo -->
            <a href="dashboard.php" class="nav_logo">
                <i class='bx bx-layer nav_logo-icon'></i>
                <span class="nav_logo-name">OOUTH NASU<br>WELFARE COOP</span>
            </a>

            <!-- Nav items -->
            <div class="nav_list" style="flex:1;overflow-y:auto;overflow-x:hidden;">
                <a href="dashboard.php"             class="nav_link <?= isActivePage('dashboard.php') ?>">
                    <i class='bx bx-grid-alt nav_icon'></i><span class="nav_name">Dashboard</span></a>

                <a href="getlogindetails.php"        class="nav_link <?= isActivePage('getlogindetails.php') ?>">
                    <i class='bx bx-folder-open nav_icon'></i><span class="nav_name">Login Details</span></a>

                <a href="registrationForm.php"       class="nav_link <?= isActivePage('registrationForm.php') ?>">
                    <i class='bx bx-user-plus nav_icon'></i><span class="nav_name">Registration</span></a>

                <a href="transaction_period.php"     class="nav_link <?= isActivePage('transaction_period.php') ?>">
                    <i class='bx bx-calendar nav_icon'></i><span class="nav_name">Period</span></a>

                <a href="add_loan.php"               class="nav_link <?= isActivePage('add_loan.php') ?>">
                    <i class='bx bx-money nav_icon'></i><span class="nav_name">Add Loan</span></a>

                <a href="contribution.php"           class="nav_link <?= isActivePage('contribution.php') ?>">
                    <i class='bx bx-donate-heart nav_icon'></i><span class="nav_name">Contribution</span></a>

                <a href="process.php"                class="nav_link <?= isActivePage('process.php') ?>">
                    <i class='bx bx-cog nav_icon'></i><span class="nav_name">Process</span></a>

                <a href="status.php"                 class="nav_link <?= isActivePage('status.php') ?>">
                    <i class='bx bx-info-circle nav_icon'></i><span class="nav_name">Member Status</span></a>

                <a href="masterTrans.php"            class="nav_link <?= isActivePage('masterTrans.php') ?>">
                    <i class='bx bx-list-check nav_icon'></i><span class="nav_name">Master Transactions</span></a>

                <a href="society_status.php"         class="nav_link <?= isActivePage('society_status.php') ?>">
                    <i class='bx bx-stats nav_icon'></i><span class="nav_name">Society Status</span></a>

                <a href="api_upload.php"             class="nav_link <?= isActivePage('api_upload.php') ?>">
                    <i class='bx bx-cloud-upload nav_icon'></i><span class="nav_name">API Upload</span></a>

                <a href="loan_repayment_compare.php" class="nav_link <?= isActivePage('loan_repayment_compare.php') ?>">
                    <i class='bx bx-git-compare nav_icon'></i><span class="nav_name">Loan Compare</span></a>

                <a href="t_sms.php"                  class="nav_link <?= isActivePage('t_sms.php') ?>">
                    <i class='bx bx-message-rounded-dots nav_icon'></i><span class="nav_name">Transaction SMS</span></a>

                <a href="bulksms.php"                class="nav_link <?= isActivePage('bulksms.php') ?>">
                    <i class='bx bx-broadcast nav_icon'></i><span class="nav_name">Bulk SMS</span></a>
            </div>
        </div>

        <!-- Sign out -->
        <div class="nav-signout">
            <a href="index.php" class="nav_link" style="color:var(--accent-red);" aria-label="Sign out">
                <i class='bx bx-log-out nav_icon'></i>
                <span class="nav_name">Sign Out</span>
            </a>
        </div>
    </nav>
</div>
