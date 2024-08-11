<?php
// Include database connection
require_once('DataBaseHandler.php');

$dbHandler = new DataBaseHandler();

if (isset($_POST['period'])) {
    $period = $_POST['period'];

    $exists = $dbHandler->checkIfPeriodExists($period);

    echo json_encode(['exists' => $exists]);
}
