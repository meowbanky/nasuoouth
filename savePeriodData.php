<?php
session_start();
require_once('class/DataBaseHandler.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming you've sanitized and validated your inputs
    $dbHandler = new DataBaseHandler();

    $period = $_POST['period'];
    // Save to database
    $saved = $dbHandler->savePeriod($period, $_SESSION['FirstName']);


    if ($saved) {

        $_SESSION['message'] = "Period Saved successful.";
    } else {
        $_SESSION['error'] = "There was a problem with the registration.";
    }
    echo $saved;
    // header('Location: registrationForm.php'); // Redirect back to the form or to a confirmation page
    exit;
}
