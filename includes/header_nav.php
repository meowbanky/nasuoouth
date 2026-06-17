<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="nav-backdrop" id="nav-backdrop"></div>

<header class="header" id="header">
    <div class="header_toggle">
        <i class='bx bx-menu' id="header-toggle" aria-label="Toggle navigation"></i>
    </div>
    <div class="sidebar-header">
        <h5>Welcome, <?php echo htmlspecialchars($_SESSION['FirstName'] ?? 'User'); ?></h5>
        <?php
        $marquee = __DIR__ . '/../includes/marquee.php';
        if (file_exists($marquee)) include $marquee;
        ?>
    </div>
    <div class="header_img">
        <img src="img/nasu.jpg" alt="NASU Logo">
    </div>
</header>
