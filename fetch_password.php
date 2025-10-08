<?php
session_start();
require_once('class/DataBaseHandler.php');
// Assuming you've sanitized and validated your inputs
$dbHandler = new DataBaseHandler();



if (($_SERVER['REQUEST_METHOD'] == 'GET') and (isset($_GET['staff_id']))) {

    $staff_id = isset($_GET['staff_id']) ? $_GET['staff_id'] : -1;

    $status = $dbHandler->getPassword($staff_id);
}

foreach ($status as $statu)
                echo $statu['PlainPassword'];
?>