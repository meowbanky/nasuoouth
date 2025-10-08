<?php
// Include database connection
require_once('DataBaseHandler.php');

$dbHandler = new DataBaseHandler();

if (isset($_POST['staffNo'])) {
    $staffNo = $_POST['staffNo'];

    $exists = $dbHandler->checkIfStaffNoExists($staffNo);

    echo json_encode(['exists' => $exists]);
}
