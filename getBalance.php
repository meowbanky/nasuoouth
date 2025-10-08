<?php
require_once 'class/DataBaseHandler.php'; // Adjust path as necessary
$dbHandler = new DataBaseHandler();

if (isset($_GET['staff_id'])) {
    $staff_id = $_GET['staff_id'];
    $results = $dbHandler->getLoanBalance($staff_id);
    if ($results == null) {
        $results = 0;
    }
    echo json_encode(number_format($results));
}
