<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isActivePage($page)
{
    $current_page_uri = $_SERVER['PHP_SELF'];
    $page_name = basename($current_page_uri);
    return $page_name === $page ? 'active' : '';
}
?>


<div class="l-navbar" id="nav-bar">
    <nav class="nav">
        <div>
            <a href="#" class="nav_logo"> <i class='bx bx-layer nav_logo-icon'></i> <span class="nav_logo-name">OOUTH NASU WELFARE</span> </a>
            <div class="nav_list">
                <a href="dashboard.php" class="nav_link  <?= isActivePage('dashboard.php'); ?>"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Dashboard</span> </a>
                <a href="getlogindetails.php" class="nav_link <?php echo isActivePage('getlogindetails.php'); ?>"> <i class='bx bx-folder-open nav_icon'></i> <span class="nav_name">Get Login Details</span> </a>

                <a href="registrationForm.php" class="nav_link <?php echo isActivePage('registrationForm.php'); ?>"> <i class='bx bx-user nav_icon'></i> <span class="nav_name">Registration</span> </a>
                <a href="transaction_period.php" class="nav_link <?php echo isActivePage('transaction_period.php'); ?>"> <i class='bx bx-message-square-detail nav_icon'></i> <span class="nav_name">Period</span> </a>
                <a href="add_loan.php" class="nav_link <?php echo isActivePage('add_loan.php'); ?>"> <i class='bx bx-money nav_icon'></i> <span class="nav_name"> Add Loan</span> </a>
                <a href="contribution.php" class="nav_link <?php echo isActivePage('contribution.php'); ?>"> <i class='bx bx-donate-heart nav_icon'></i> <span class="nav_name">contribution</span> </a>
                <a href="process.php" class="nav_link <?php echo isActivePage('process.php'); ?>"> <i class='bx bx-cog nav_icon'></i> <span class="nav_name">Process Transactions</span> </a>
                <a href="status.php" class="nav_link <?php echo isActivePage('status.php'); ?>"> <i class='bx bx-info-circle nav_icon'></i> <span class="nav_name">Status</span> </a>
                <a href="masterTrans.php" class="nav_link <?php echo isActivePage('masterTrans.php'); ?>"> <i class='bx bx-bar-chart-alt-2 nav_icon'></i> <span class="nav_name">Transaction</span> </a>
                <a href="upload.php" class="nav_link <?php echo isActivePage('upload.php'); ?>"> <i class='bx bx-cloud-upload nav_icon'></i> <span class="nav_name">Upload</span> </a>
                <a href="loan_repayment_compare.php" class="nav_link <?php echo isActivePage('loan_repayment_compare.php'); ?>"> <i class='bx bx-git-compare nav_icon'></i> <span class="nav_name">Loan Compare</span> </a>
                <a href="t_sms.php" class="nav_link <?php echo isActivePage('t_sms.php'); ?>"> <i class='bx bx-message-rounded-dots nav_icon'></i> <span class="nav_name">Transaction Alert</span> </a>

                <a href="index.php" class="nav_link"> <i class='bx bx-log-out nav_icon'></i> <span class="nav_name">SignOut</span> </a>

            </div>
        </div>
    </nav>
</div>