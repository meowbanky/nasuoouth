<?php
session_start();
require_once('class/DataBaseHandler.php');
require_once('sendSmsNewMember.php');

// Assuming you've sanitized and validated your inputs
$dbHandler = new DataBaseHandler();

$memberCount = 0;

$activeMembers = $dbHandler->activelogDetails();

?>
    <div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
<?php
//$period = 241;
$i = 1;
foreach ($activeMembers as $activeMember) {

    echo str_repeat(' ', 1024 * 64);
    $message = 'Dear '.$activeMember['firstname'].' your NASUWEL login details, username: '.$activeMember['userid']. ' and password: '.$activeMember['PlainPassword'].
        '<br> You can login via <a href="https://emmaggi.com/oouthnasu/index.php">click here to login</a> or by downloading the android app  <a href="https://emmaggi.com/oouthnasu/OOUTHNASU.apk">here</a>';
    ;


    $mobilePhone = $activeMember['MobilePhone'];
    $response = doSendMessage($mobilePhone, $message);
    ob_flush();
    flush();

    $i++;
}
