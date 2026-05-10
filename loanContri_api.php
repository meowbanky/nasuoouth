<?php
header('Content-Type: application/json');
require_once('class/DataBaseHandler.php');
$dbHandler = new DataBaseHandler();
$conn = $dbHandler->pdo;

// Define response structure
$response = ['status' => 'error', 'message' => '', 'data' => []];

try {
    if (!isset($_POST['action'])) {
        throw new Exception("Invalid request");
    }

    $action = $_POST['action'];
    $period_id = $_POST['period_id'] ?? null; // Get period_id from request

    if ($action === 'fetch_comparison') {
        
        if (!$period_id) {
             // Fallback or error if period is required
             // For now, let's assume if no period, we default to latest or show all (but logic requires period for accuracy)
             // Better to return empty or error if strict. Let's return empty to be safe or just proceed if logic permits.
             // Given the requirement "Running totals", a period cutoff is essential.
             throw new Exception("Period ID is required for comparison.");
        }
        
        // 1. Main Query
        // Logic copied from loanContri_Compare.php
        $query = "SELECT 
                    tbl_personalinfo.staff_id, 
                    concat(tbl_personalinfo.Lname,' ',tbl_personalinfo.Fname,' ',ifnull(tbl_personalinfo.Mname,'')) as namee,
                    ((sum(tlb_mastertransaction.loanAmount)+sum(tlb_mastertransaction.interest))-(sum(tlb_mastertransaction.loanRepayment))) AS loanBalance,
                    (SELECT loan FROM tbl_contributions WHERE staff_id = tbl_personalinfo.staff_id AND period_id = :period_id1 LIMIT 1) as standard_repayment
                  FROM tlb_mastertransaction 
                  LEFT JOIN tbl_personalinfo ON tbl_personalinfo.staff_id = tlb_mastertransaction.staff_id 
                  WHERE tbl_personalinfo.`Status` = 1 AND tlb_mastertransaction.periodid <= :period_id2
                  GROUP BY tbl_personalinfo.staff_id 
                  HAVING loanBalance > 0 OR standard_repayment > 0 -- Optimization: valid records only
                  ORDER BY tbl_personalinfo.staff_id DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute([':period_id1' => $period_id, ':period_id2' => $period_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dataList = [];
        $totalLoanBalance = 0;
        $totalRepayment = 0; // Sum of standard repayments
        $pendingReductions = 0;

        foreach ($results as $row) {
            $bal = floatval($row['loanBalance']);
            $rep = floatval($row['standard_repayment']);

            // Determine Status
            $status = 'Normal';
            if ($bal < $rep && $bal > 0) { 
                $status = 'Reduce Repayment';
                $pendingReductions++;
            } elseif ($bal <= 0) {
                $status = 'Clear'; // Should technically stop deducting
            }

            // Accumulate Totals
            $totalLoanBalance += $bal;
            $totalRepayment += $rep;

            $dataList[] = [
                'staff_no' => $row['staff_id'],
                'name' => trim($row['namee']),
                'loan_balance' => $bal,
                'loan_repayment' => $rep,
                'status' => $status
            ];
        }

        $response['status'] = 'success';
        $response['data'] = [
            'list' => $dataList,
            'summary' => [
                'total_loan_balance' => $totalLoanBalance,
                'total_repayment' => $totalRepayment,
                'pending_reductions' => $pendingReductions
            ]
        ];

    } else {
        throw new Exception("Unknown action");
    }

} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
