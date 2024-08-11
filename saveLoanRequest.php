<?php
session_start();
require_once('class/DataBaseHandler.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming you've sanitized and validated your inputs
    $dbHandler = new DataBaseHandler();

    $staff_id = $_POST['staff_id'];
    $amountGranted = $_POST['amountGranted'];
    $interest = $_POST['interest'];
    $period = $_POST['period'];

    //INSERT into Loan table
    $insertLoan = $dbHandler->insertLoan($staff_id, $period, $amountGranted, $interest);

    $insertMaster = $dbHandler->insertMasterTransaction($period, $staff_id, $insertLoan, $amountGranted, $interest);
    // Save to database
}
