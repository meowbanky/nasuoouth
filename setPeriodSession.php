<?php
session_start();
if(isset($_GET['period'])){
    $_SESSION['period'] = $_GET['period'];
}else{
    $_SESSION['perio'] = -1;
}
