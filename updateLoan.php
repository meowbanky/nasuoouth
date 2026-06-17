<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once('class/DataBaseHandler.php');

$loanId      = intval($_POST['loanId']      ?? 0);
$amountGranted = floatval($_POST['amountGranted'] ?? 0);
$interest    = floatval($_POST['interest']   ?? 0);

if ($loanId <= 0 || $amountGranted <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid loan data.']);
    exit;
}

try {
    $dbHandler = new DataBaseHandler();
    $dbHandler->updateLoan($loanId, $amountGranted, $interest);
    echo json_encode(['status' => 'success', 'message' => 'Loan updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update loan.']);
}
