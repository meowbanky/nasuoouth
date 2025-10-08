<?php
session_start();
require_once('class/DataBaseHandler.php');
require_once('class/services/NotificationService.php');
require_once('sendSmsNewMember.php');
use class\services\NotificationService;


// Assuming you've sanitized and validated your inputs
$dbHandler = new DataBaseHandler();

$notification = new NotificationService($dbHandler->pdo);

$period = filter_input(INPUT_GET, 'period', FILTER_SANITIZE_NUMBER_INT);
$staff_id = filter_input(INPUT_GET, 'staff_id', FILTER_SANITIZE_NUMBER_INT);


if ($staff_id === '') {
    $staff_id = 0;
    $equality = '>=';
} else {
    $equality = '=';
}
$memberCount = 0;

$activeMembers = $dbHandler->activeMembers(1, $staff_id, $equality);
$totalMembers = $dbHandler->countActivemembers(1, $staff_id, $equality);
foreach ($totalMembers as $totalMember) {
    $memberCount = $totalMember['count'];
}
?>
<div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
<?php
//$period = 241;
$i = 1;
$periodDisplay = $dbHandler->getSingleItem('tbpayrollperiods', 'PayrollPeriod', 'Periodid', $period);
foreach ($activeMembers as $activeMember) {
    $progressPercentage  = intval($i / $memberCount * 100) . "%";
    echo str_repeat(' ', 1024 * 64);
    //echo $activeMember['staff_id'] . '-';
    $savings = $dbHandler->getBalance($activeMember['staff_id'], 'tlb_mastertransaction', 'Contribution', 'Contribution', 'periodid', $period, '=');
    $loanrepayment = $dbHandler->getBalance($activeMember['staff_id'], 'tlb_mastertransaction', 'loanRepayment', 'loanRepayment', 'periodid', $period, '=');
    $total = (float)$savings + (float)$loanrepayment;
    $cumulativeSavings = $dbHandler->getBalance($activeMember['staff_id'], 'tlb_mastertransaction', 'Contribution', 'Contribution', 'periodid', $period, '<=');
    $cumulativeLoan = $dbHandler->getBalance($activeMember['staff_id'], 'tlb_mastertransaction', 'loanAmount', 'loanAmount', 'periodid', $period, '<=');
    $cumulativeInterest = $dbHandler->getBalance($activeMember['staff_id'], 'tlb_mastertransaction', 'interest', 'interest', 'periodid', $period, '<=');
    $totalLoan = $cumulativeLoan + $cumulativeInterest;
    $cumulativeRepayment = $dbHandler->getBalance($activeMember['staff_id'], 'tlb_mastertransaction', 'loanRepayment', 'loanRepayment', 'periodid', $period, '<=');
    $loanBalance = $totalLoan - $cumulativeRepayment;
    $message = 'Your NASUWEL ACCT. BAL., MONTHLY CONTR. : ' . number_format($total, 2, '.', ',') . ' WELFARE SAVINGS: ' . number_format($cumulativeSavings, 2, '.', ',') . ' LOAN BAL: ' . number_format($loanBalance, 2, '.', ',') . '  AS AT: ' . $periodDisplay;

    echo '<script>
					    parent.document.getElementById("progress").innerHTML="<div style=\"width:' . $progressPercentage . ';background:linear-gradient(to bottom, rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%); text-align:center;color:white;height:35px;display:block;\">' . $progressPercentage . '</div>";
					    </script>';

//    $mobilePhone = $activeMember['MobilePhone'];
//    $response = doSendMessage($mobilePhone, $message);

    $notification->sendTransactionNotification($activeMember['staff_id'],$period);
    ob_flush();
    flush();

    $i++;
}
