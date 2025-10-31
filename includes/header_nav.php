<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>
<header class="header" id="header">

    <div class="header_toggle"> <i class='bx bx-menu' id="header-toggle"></i> </div>
    <div class="sidebar-header text-center">

        <h5>Welcome, <?php echo htmlspecialchars($_SESSION['FirstName'] ?? ''); ?></h5>
        <?php include("marquee.php"); ?>
    </div>
    <div class="header_img"> <img src="img/nasu.jpg" class="header_img" alt="NASU Logo"> </div>
</header>