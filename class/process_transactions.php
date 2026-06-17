<?php
require_once __DIR__ . '/services/NotificationService.php';
require_once __DIR__ . '/DataBaseHandler.php';
require_once __DIR__ . '/db_constants.php';

use class\services\NotificationService;

class TransactionProcessor
{
    private PDO $pdo;
    private NotificationService $notification;

    public function __construct(PDO $pdo, NotificationService $notification)
    {
        $this->pdo          = $pdo;
        $this->notification = $notification;
    }

    private function getDeductions(int $periodId, string $staffFilter): array
    {
        $sql = "SELECT tbl_contributions.staff_id,
                       tbl_contributions.contribution,
                       IFNULL(tbl_contributions.special_savings, 0) AS special_savings,
                       tbl_contributions.loan AS contLoan
                FROM tbl_contributions
                INNER JOIN tbl_personalinfo ON tbl_personalinfo.staff_id = tbl_contributions.staff_id
                WHERE tbl_personalinfo.Status = 1
                AND tbl_contributions.period_id = :period_id";

        if ($staffFilter !== 'ALL' && $staffFilter !== '') {
            $sql .= " AND tbl_contributions.staff_id = :staff_id";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        if ($staffFilter !== 'ALL' && $staffFilter !== '') {
            $stmt->bindValue(':staff_id', $staffFilter, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Returns the cumulative outstanding loan balance for a member across all periods.
    // Uses COALESCE per-column so NULL interest/loanAmount values don't collapse the whole expression to NULL.
    private function getMemberLoanBalance(string $staffId): float
    {
        $sql = "SELECT
                    COALESCE(SUM(loanAmount), 0)
                    + COALESCE(SUM(interest), 0)
                    - COALESCE(SUM(loanRepayment), 0) AS loanBalance
                FROM tlb_mastertransaction
                WHERE staff_id = :staff_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':staff_id' => $staffId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['loanBalance'] ?? 0);
    }

    private function isAlreadyProcessed(string $staffId, int $periodId): bool
    {
        $sql = "SELECT COUNT(*) FROM tlb_mastertransaction
                WHERE staff_id = :staff_id
                AND periodid   = :periodid
                AND Contribution > 0
                AND completed  = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':staff_id' => $staffId, ':periodid' => $periodId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function insertTransaction(string $staffId, int $periodId, float $contribution, float $loanRepayment): void
    {
        $sql = "INSERT INTO tlb_mastertransaction (periodid, staff_id, Contribution, loanRepayment, completed)
                VALUES (:periodid, :staff_id, :contribution, :loanRepayment, :completed)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':periodid'      => $periodId,
            ':staff_id'      => $staffId,
            ':contribution'  => $contribution,
            ':loanRepayment' => $loanRepayment,
            ':completed'     => 1,
        ]);
    }

    private function insertRefund(string $staffId, int $periodId, float $amount): void
    {
        $sql = "INSERT INTO tbl_refund (periodid, staff_id, amount) VALUES (:periodid, :staff_id, :amount)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':periodid' => $periodId, ':staff_id' => $staffId, ':amount' => $amount]);
    }

    // Core rule: never repay more than the outstanding balance.
    // Any contLoan amount above the balance becomes a refund.
    // Negative balances (from prior data errors) are treated as fully paid.
    private function calculateRepayment(float $loanBalance, float $contLoan): array
    {
        $effectiveBalance = max(0.0, $loanBalance);
        $actualRepayment  = min($effectiveBalance, $contLoan);
        $refund           = $contLoan - $actualRepayment;
        return ['repayment' => $actualRepayment, 'refund' => $refund];
    }

    private function outputProgress(int $processed, int $total): void
    {
        if ($total === 0) {
            return;
        }
        $pct = intval($processed / $total * 100) . '%';
        echo str_repeat(' ', 1024 * 64);
        echo '<script>
            parent.document.getElementById("progress").innerHTML="<div style=\"width:' . $pct . ';background:linear-gradient(to bottom,rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%);text-align:center;color:white;height:35px;display:block;\">' . $pct . '</div>";
            parent.document.getElementById("information").innerHTML="<div style=\"text-align:center;font-weight:bold\">Processing ' . $processed . ' of ' . $total . '.</div>";
        </script>';
        ob_flush();
        flush();
    }

    public function process(int $periodId, string $staffFilter, bool $sendSms): void
    {
        $deductions = $this->getDeductions($periodId, $staffFilter);
        $total      = count($deductions);

        foreach ($deductions as $i => $deduction) {
            $staffId      = $deduction['staff_id'];
            $contribution = (float)$deduction['contribution'] + (float)$deduction['special_savings'];
            $contLoan     = (float)($deduction['contLoan'] ?? 0);

            if (!$this->isAlreadyProcessed($staffId, $periodId)) {
                $loanBalance = $this->getMemberLoanBalance($staffId);

                ['repayment' => $repayment, 'refund' => $refund] = $this->calculateRepayment($loanBalance, $contLoan);

                $this->insertTransaction($staffId, $periodId, $contribution, $repayment);

                if ($refund > 0) {
                    $this->insertRefund($staffId, $periodId, $refund);
                }

                if ($sendSms) {
                    $this->notification->sendTransactionNotification($staffId, $periodId);
                }
            }

            $this->outputProgress($i + 1, $total);
        }
    }
}

if (isset($_GET['PeriodID'])) {
    $periodId    = (int)filter_input(INPUT_GET, 'PeriodID', FILTER_SANITIZE_NUMBER_INT);
    $staffFilter = $_GET['staff_id_filter'] ?? 'ALL';
    $sendSms     = filter_var($_GET['send_sms'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $dbHandler    = new DataBaseHandler();
    $notification = new NotificationService($dbHandler->pdo);
    ?>
<div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
<div id="information" style="width:100%"></div>
<?php
    try {
        $processor = new TransactionProcessor($dbHandler->pdo, $notification);
        $processor->process($periodId, $staffFilter, $sendSms);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
