<?php
function isActivePage($page)
{
    $current_page_uri = $_SERVER['PHP_SELF'];
    $page_name = basename($current_page_uri);
    return $page_name === $page ? 'active' : '';
}
?>




<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="sidebar-header text-center">
            <img src="img/nasu.jpg" class="img-fluid rounded-circle" alt="NASU Logo" style="max-width: 50%;">
            <h5>Welcome, <?php echo htmlspecialchars($_SESSION['FirstName']); ?></h5>
        </div>
        <hr>
        <a class="list-group-item list-group-item-action <?= isActivePage('dashboard.php'); ?>" aria-current="page" href="dashboard.php">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        <a class="list-group-item list-group-item-action <?= isActivePage('registrationForm.php'); ?>" aria-current="page" href="registrationForm.php">
            <i class="fas fa-user-plus"></i>
            Registration
        </a>
        <a class="list-group-item list-group-item-action <?= isActivePage('transaction_period.php'); ?>" aria-current="page" href="transaction_period.php">
            <i class="fas fa-calendar-alt"></i>
            Period
        </a>
        <a class="list-group-item list-group-item-action <?= isActivePage('add_loan.php'); ?>" aria-current="page" href="add_loan.php">
            <i class="fas fa-plus-circle"></i>
            Add Loan
        </a>
        <a class="list-group-item list-group-item-action <?= isActivePage('contribution.php'); ?>" aria-current="page" href="contribution.php">
            <i class="fas fa-edit"></i>
            Edit Contributions
        </a>
        <a class="list-group-item list-group-item-action <?= isActivePage('status.php'); ?>" aria-current="page" href="status.php">
            <i class="fas fa-info-circle"></i>
            Check Status
        </a>
        <a class="list-group-item list-group-item-action <?= isActivePage('masterTrans.php'); ?>" aria-current="page" href="masterTrans.php">
            <i class="fas fa-exchange-alt"></i>
            Master Transaction
        </a>
        <a class="list-group-item list-group-item-action <?= isActivePage('index.php'); ?>" aria-current="page" href="index.php">
            <i class="fas fa-sign-out-alt"></i>
            Log out
        </a>
        <?php include("marquee.php");
        ?>
        </li>
        </ul>
    </div>
</nav>