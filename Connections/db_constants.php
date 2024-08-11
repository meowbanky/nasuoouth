<?php
require_once 'class/db_constants.php';
$hostname = DB_HOST;
$database = DB_NAME;
$username = DB_USER;
$password = DB_PASS;
$conn = mysqli_connect($hostname, $username, $password) or trigger_error(mysqli_error($hms), E_USER_ERROR);

$mysqli = new mysqli($hostname, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
