<?php
session_start();
require_once('class/DataBaseHandler.php');
// Assuming you've sanitized and validated your inputs
$dbHandler = new DataBaseHandler();



if (($_SERVER['REQUEST_METHOD'] == 'GET') and (isset($_GET['staff_id'])) and (isset($_GET['period']))) {

    $staff_id = isset($_GET['staff_id']) ? $_GET['staff_id'] : -1;

    $period = isset($_GET['period']) ? $_GET['period'] : -1;
    #Get loan details
    $status = $dbHandler->getStatus($staff_id, $period);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Savings</th>
                <th>Loan</th>
                <th>Loan Balance</th>
                <th>Withdrawal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($status as $statu) { ?>
                <tr>
                    <th>₦ <?php echo number_format($statu['Contribution']); ?></th>
                    <th>₦ <?php echo number_format($statu['Loan']); ?></th>
                    <th>₦ <?php echo number_format($statu['Loanbalance']); ?></th>
                    <th>₦ <?php echo number_format($statu['withdrawal']); ?></th>
                </tr>
            <?php } ?>
        </tbody>

    </table>




</body>

</html>