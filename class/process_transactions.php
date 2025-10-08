<?php
//require_once('services/NotificationService.php');
require_once __DIR__ .'/services/NotificationService.php';
require_once __DIR__ .'/DataBaseHandler.php';

use class\services\NotificationService;

require_once __DIR__. '/db_constants.php' ; // Include the DatabaseHandler class


if (isset($_GET['PeriodID'])) {

    $PeriodID = filter_input(INPUT_GET, 'PeriodID', FILTER_SANITIZE_NUMBER_INT);

    $pdo;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    // Use constants from the included file
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS,  $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }

    $dbHandler = new DataBaseHandler();
    $notification = new NotificationService($dbHandler->pdo);
?>
    <div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
    <div id="information" style="width:100%"></div>

<?php
    try {
        // Create an instance of the DatabaseHandler class

        $deductionsQuery = "SELECT tbl_contributions.staff_id, tbl_contributions.contribution, IFNULL(tbl_contributions.special_savings, 0) AS special_savings, tbl_contributions.loan AS loon
                        FROM tbl_contributions
                        INNER JOIN tbl_personalinfo ON tbl_personalinfo.staff_id = tbl_contributions.staff_id
                        WHERE `Status` = 1";
        $deductionsStmt = $pdo->prepare($deductionsQuery);
        $deductionsStmt->execute();
        $deductions = $deductionsStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalTransactions = count($deductions);
        $processedTransactions = 0;

        foreach ($deductions as $deduction) {
            $balancesQuery = "SELECT tbl_personalinfo.staff_id, CONCAT(tbl_personalinfo.Lname, ', ', tbl_personalinfo.Fname, ' ', IFNULL(tbl_personalinfo.Mname, '')) AS namess,
                            SUM(tlb_mastertransaction.Contribution) AS Contribution, 
                            IFNULL((SUM(tlb_mastertransaction.loanAmount) + SUM(tlb_mastertransaction.interest)), 0) AS Loan, 
                            IFNULL(((SUM(tlb_mastertransaction.loanAmount) + SUM(tlb_mastertransaction.interest)) - SUM(tlb_mastertransaction.loanRepayment)), 0) AS Loanbalance,
                            SUM(tlb_mastertransaction.withdrawal) AS withdrawal, tbl_contributions.contribution AS contContribution, tbl_contributions.loan AS contLoan
                        FROM tlb_mastertransaction
                        RIGHT JOIN tbl_personalinfo ON tbl_personalinfo.staff_id = tlb_mastertransaction.staff_id
                        LEFT JOIN tbl_contributions ON tbl_contributions.staff_id = tbl_personalinfo.staff_id
                        WHERE tbl_personalinfo.staff_id = :staff_id
                        GROUP BY staff_id";
            $balancesStmt = $pdo->prepare($balancesQuery);
            $balancesStmt->bindParam(':staff_id', $deduction['staff_id']);
            $balancesStmt->execute();
            $row_balances = $balancesStmt->fetch(PDO::FETCH_ASSOC);

            $completedQuery = "SELECT tlb_mastertransaction.staff_id 
                            FROM tlb_mastertransaction 
                            WHERE staff_id = :staff_id 
                            AND periodid = :periodid 
                            AND Contribution > 0 
                            AND completed = 1";
            $completedStmt = $pdo->prepare($completedQuery);
            $completedStmt->bindParam(':staff_id', $deduction['staff_id']);
            $completedStmt->bindParam(':periodid', $PeriodID);
            $completedStmt->execute();
            $totalRows_completed = $completedStmt->rowCount();

            if ($totalRows_completed == 0) {
                if (($row_balances['Loanbalance'] == 0) && ($row_balances['contLoan'] == 0)) {
                    $insertSQL = "INSERT INTO tlb_mastertransaction (periodid, staff_id, Contribution, completed) 
                                VALUES (:periodid, :staff_id, :contribution, :completed)";
                    $insertStmt = $pdo->prepare($insertSQL);
                    $insertStmt->execute([
                        ':periodid' => $PeriodID,
                        ':staff_id' => $deduction['staff_id'],
                        ':contribution' => ($deduction['contribution'] + $deduction['special_savings']),
                        ':completed' => 1
                    ]);
                } elseif (($row_balances['Loanbalance'] == 0) && ($row_balances['contLoan'] > 0)) {
                    $refund = $row_balances['contLoan'];
                    $insertSQL = "INSERT INTO tlb_mastertransaction (periodid, staff_id, Contribution, completed) 
                                VALUES (:periodid, :staff_id, :contribution, :completed)";
                    $insertStmt = $pdo->prepare($insertSQL);
                    $insertStmt->execute([
                        ':periodid' => $PeriodID,
                        ':staff_id' => $deduction['staff_id'],
                        ':contribution' => ($deduction['contribution'] + $deduction['special_savings']),
                        ':completed' => 1
                    ]);
                    $insertSQL_refund = "INSERT INTO tbl_refund (periodid, staff_id, amount) 
                                        VALUES (:periodid, :staff_id, :refund)";
                    $insertStmt_refund = $pdo->prepare($insertSQL_refund);
                    $insertStmt_refund->execute([
                        ':periodid' => $PeriodID,
                        ':staff_id' => $deduction['staff_id'],
                        ':refund' => $refund
                    ]);
                } elseif ($row_balances['Loanbalance'] > 0) {
                    if ($row_balances['Loanbalance'] > 0) {
                        if ($row_balances['contLoan'] > 0) {
                            if ($row_balances['Loanbalance'] > $row_balances['contLoan']) {
                                $insertSQL = "INSERT INTO tlb_mastertransaction (periodid, staff_id, Contribution, loanRepayment, completed) 
                            VALUES (:periodid, :staff_id, :contribution, :loanRepayment, :completed)";
                                $insertStmt = $pdo->prepare($insertSQL);
                                $insertStmt->execute([
                                    ':periodid' => $PeriodID,
                                    ':staff_id' => $deduction['staff_id'],
                                    ':contribution' => ($deduction['contribution'] + $deduction['special_savings']),
                                    ':loanRepayment' => $row_balances['contLoan'],
                                    ':completed' => 1
                                ]);
                            } elseif ($row_balances['Loanbalance'] < $row_balances['contLoan']) {
                                $insertSQL = "INSERT INTO tlb_mastertransaction (periodid, staff_id, Contribution, loanRepayment, completed) 
                            VALUES (:periodid, :staff_id, :contribution, :loanRepayment, :completed)";
                                $insertStmt = $pdo->prepare($insertSQL);
                                $insertStmt->execute([
                                    ':periodid' => $PeriodID,
                                    ':staff_id' => $deduction['staff_id'],
                                    ':contribution' => ($deduction['contribution'] + $deduction['special_savings']),
                                    ':loanRepayment' => $row_balances['Loanbalance'],
                                    ':completed' => 1
                                ]);

                                $refund = ($row_balances['contLoan'] - $row_balances['Loanbalance']);
                                $insertSQL_refund = "INSERT INTO tbl_refund (periodid, staff_id, amount) 
                                    VALUES (:periodid, :staff_id, :refund)";
                                $insertStmt_refund = $pdo->prepare($insertSQL_refund);
                                $insertStmt_refund->execute([
                                    ':periodid' => $PeriodID,
                                    ':staff_id' => $deduction['staff_id'],
                                    ':refund' => $refund
                                ]);
                            } elseif ($row_balances['Loanbalance'] == $row_balances['contLoan']) {
                                $insertSQL = "INSERT INTO tlb_mastertransaction (periodid, staff_id, Contribution, loanRepayment, completed) 
                            VALUES (:periodid, :staff_id, :contribution, :loanRepayment, :completed)";
                                $insertStmt = $pdo->prepare($insertSQL);
                                $insertStmt->execute([
                                    ':periodid' => $PeriodID,
                                    ':staff_id' => $deduction['staff_id'],
                                    ':contribution' => ($deduction['contribution'] + $deduction['special_savings']),
                                    ':loanRepayment' => $row_balances['Loanbalance'],
                                    ':completed' => 1
                                ]);
                            }
                        } elseif ($row_balances['contLoan'] == 0) {
                            $insertSQL = "INSERT INTO tlb_mastertransaction (periodid, staff_id, Contribution, loanRepayment, completed) 
                        VALUES (:periodid, :staff_id, :contribution, :loanRepayment, :completed)";
                            $insertStmt = $pdo->prepare($insertSQL);
                            $insertStmt->execute([
                                ':periodid' => $PeriodID,
                                ':staff_id' => $deduction['staff_id'],
                                ':contribution' => ($deduction['contribution'] + $deduction['special_savings']),
                                ':loanRepayment' => 0.0,
                                ':completed' => 1
                            ]);
                        }
                    }
                }
            }

            $notification->sendTransactionNotification($deduction['staff_id'],$PeriodID);
            $processedTransactions++;

            // Calculate progress percentage
            $progressPercentage = intval($processedTransactions / $totalTransactions * 100) . "%";


            // Output the progress percentage

            // echo "Processing... " . number_format($progressPercentage, 2) . "% complete.<br>";
            // Javascript for updating the progress bar and information
            echo str_repeat(' ', 1024 * 64);
            echo '<script>
					    parent.document.getElementById("progress").innerHTML="<div style=\"width:' . $progressPercentage . ';background:linear-gradient(to bottom, rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%); text-align:center;color:white;height:35px;display:block;\">' . $progressPercentage . '</div>";
					    parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; font-weight:bold\">Processing ' . $processedTransactions . ' of ' . $totalTransactions . ' is processed.</div>";</script>';

            ob_flush();
            flush();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>