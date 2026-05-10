<?php
session_start();
require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['period'])) {
    $period = filter_input(INPUT_GET, 'period', FILTER_SANITIZE_NUMBER_INT);
    $status = $dbHandler->getSocietyStatus($period);
    
    if ($status) {
        $periodName = $dbHandler->getSingleItem('tbpayrollperiods', 'PayrollPeriod', 'Periodid', $period);
?>
        <div class="mt-4">
            <h4>Society Status as at <?php echo $periodName; ?></h4>
            <table class="table table-bordered table-striped mt-3">
                <thead class="table-dark">
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount (₦)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Contributions (Savings)</td>
                        <td class="text-end"><?php echo number_format($status['TotalContribution'], 2); ?></td>
                    </tr>
                    <tr>
                        <td>Total Withdrawals</td>
                        <td class="text-end"><?php echo number_format($status['TotalWithdrawal'], 2); ?></td>
                    </tr>
                    <tr class="table-info">
                        <th>Net Contribution Balance</th>
                        <th class="text-end"><?php echo number_format($status['NetContribution'], 2); ?></th>
                    </tr>
                    <tr>
                        <td>Total Loans + Interest</td>
                        <td class="text-end"><?php echo number_format($status['TotalLoan'], 2); ?></td>
                    </tr>
                    <tr>
                        <td>Total Loan Repayments</td>
                        <td class="text-end"><?php echo number_format($status['TotalLoanRepayment'], 2); ?></td>
                    </tr>
                    <tr class="table-warning">
                        <th>Outstanding Loan Balance</th>
                        <th class="text-end"><?php echo number_format($status['LoanBalance'], 2); ?></th>
                    </tr>
                </tbody>
            </table>
        </div>
<?php
    } else {
        echo "<div class='alert alert-info mt-4'>No data found for the selected period.</div>";
    }
}
?>
