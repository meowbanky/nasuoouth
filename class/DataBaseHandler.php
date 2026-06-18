<?php
// Include the constants file
require_once('db_constants.php');

class DatabaseHandler
{
    public PDO $pdo;
    private array $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    public function __construct()
    {
        // Use constants from the included file
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $this->options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    // Function to fetch select items
    public function getSelectItems(string $tableName, string $valueColumn, string $displayTextColumn): array
    {
        $stmt = $this->pdo->prepare("SELECT $valueColumn, $displayTextColumn FROM $tableName");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Function to fetch Balance of Any column
    public function getBalance($staff_id, string $table, string $olumn, string $displayColumn, string $filterColumn, $filterValue, string $equality = '<='): float|int
    {
        $query = "SELECT SUM({$olumn}) as {$displayColumn}  FROM {$table} WHERE staff_id =? AND  {$filterColumn} {$equality}? GROUP BY staff_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$staff_id, $filterValue]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result[$displayColumn] ?? 0;
    }

    // Function to fetch Balance of Any column
    public function activeMembers($status, $staff_id, string $equality = '>='): array
    {
        $stmt = $this->pdo->prepare("SELECT tbl_personalinfo.staff_id, tbl_personalinfo.Lname, tbl_personalinfo.Mname, tbl_personalinfo.Fname,MobilePhone FROM tbl_personalinfo
	                                WHERE `Status` = ? AND staff_id {$equality} ?");
        $stmt->execute([$status, $staff_id]);

        return $stmt->fetchAll();
    }

    public function activelogDetails()
    {
        $stmt = $this->pdo->prepare("SELECT userid,MobilePhone, PlainPassword, firstname FROM tblusers INNER JOIN tbl_personalinfo ON tblusers.UserID = tbl_personalinfo.staff_id WHERE tbl_personalinfo.`Status` = 1 AND tbl_personalinfo.staff_id = 1140");
        $stmt->execute();

        return $stmt->fetchAll();
    }
    //get No of active members
    public function countActivemembers($status, $staff_id, string $equality = '>='): array
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM tbl_personalinfo
	                                WHERE `Status` = ? AND staff_id {$equality} ?");
        $stmt->execute([$status, $staff_id]);

        return $stmt->fetchAll();
    }

    // Function to fetch select items
    public function getLoanBalance($staff_id): string|float|int
    {
        $stmt = $this->pdo->prepare("SELECT (sum(tlb_mastertransaction.loanAmount) + sum(tlb_mastertransaction.interest)) - sum(tlb_mastertransaction.loanRepayment) as balance
                                        FROM tlb_mastertransaction WHERE staff_id = ?");
        $stmt->execute([$staff_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['balance'] ?? 0;
    }

    public function getLoanStatus($staff_id): string|float|int
    {
        $stmt = $this->pdo->prepare("SELECT (sum(ifnull(loanAmount,0))+sum(ifnull(interest,0))) - sum(ifnull(loanRepayment,0)) balance FROM tlb_mastertransaction WHERE staff_id  = ?");
        $stmt->execute([$staff_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['balance'] ?? 0;
    }
    // Function to fetch single Item
    public function getSingleItem(string $tableName, string $returnColumn, string $filter, $filterValue): string|int|float
    {
        $stmt = $this->pdo->prepare("SELECT {$returnColumn} FROM {$tableName} WHERE {$filter} = ?");
        $stmt->execute([$filterValue]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result[$returnColumn] ?? '';
        } else {
            return '';
        }
    }

    public function fetchNames(string $term): array
    {
        $query = $this->pdo->prepare("SELECT tbl_personalinfo.staff_id, tbl_personalinfo.Fname, tbl_personalinfo.Mname, tbl_personalinfo.Lname, tbl_personalinfo.MobilePhone FROM tbl_personalinfo WHERE (tbl_personalinfo.staff_id LIKE ? OR tbl_personalinfo.Fname LIKE ? OR tbl_personalinfo.Mname LIKE ? OR tbl_personalinfo.Lname LIKE ? OR tbl_personalinfo.MobilePhone LIKE ?) LIMIT 5");
        // Execute with the parameters in a single array
        $query->execute(["%$term%", "%$term%", "%$term%", "%$term%", "%$term%"]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function countItems(string $table): int
    {
        $sql = "SELECT COUNT(*) AS total FROM {$table}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)$result['total'];
    }
    public function insertLoan($staff_id, $periodId, $loanAmount, $interest): string|false
    {
        try {
            $sql = "INSERT INTO tbl_loan (staff_id, periodid, loanamount, interest) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$staff_id, $periodId, $loanAmount, $interest]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Optionally, log this error or handle it as needed
            throw new PDOException("Error inserting loan: " . $e->getMessage());
        }
    }

    public function insertMasterTransaction($periodId, $staff_id, $loanId, $loanAmount, $interest): void
    {
        try {
            $sql = "INSERT INTO tlb_mastertransaction (periodid, staff_id, loanid, loanAmount, interest) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$periodId, $staff_id, $loanId, $loanAmount, $interest]);
        } catch (PDOException $e) {
            // Optionally, log this error or handle it as needed
            throw new PDOException("Error inserting master transaction: " . $e->getMessage());
        }
    }
    public function updateLoan(int $loanId, float $loanAmount, float $interest): bool
    {
        $this->pdo->prepare("UPDATE tbl_loan SET loanamount = ?, interest = ? WHERE loanid = ?")
                  ->execute([$loanAmount, $interest, $loanId]);
        $this->pdo->prepare("UPDATE tlb_mastertransaction SET loanAmount = ?, interest = ? WHERE loanid = ?")
                  ->execute([$loanAmount, $interest, $loanId]);
        return true;
    }

    public function fetchTransactionDetails($periodFrom, $periodTo, $staffId = ''): array
    {
        // Base query
        $query = "SELECT
                    tbl_personalinfo.staff_id,
                    GROUP_CONCAT(tlb_mastertransaction.transactionid) AS transactionids,
                    CONCAT(tbl_personalinfo.Lname, ', ', tbl_personalinfo.Fname, ' ', IFNULL(tbl_personalinfo.Mname, '')) AS namess,
                    SUM(tlb_mastertransaction.Contribution) AS Contribution,
                    (SUM(tlb_mastertransaction.loanAmount) + SUM(tlb_mastertransaction.interest)) AS loan,
                    SUM(tlb_mastertransaction.loanRepayment) AS loanrepayments,
                    SUM(tlb_mastertransaction.withdrawal) AS withrawals,
                    (SUM(tlb_mastertransaction.Contribution) + SUM(tlb_mastertransaction.loanRepayment) + IFNULL(SUM(tbl_refund.amount), 0)) AS total,
                    tbpayrollperiods.PayrollPeriod,
                    GROUP_CONCAT(DISTINCT tlb_mastertransaction.periodid) AS periodids,
                    IFNULL(SUM(tbl_refund.amount), 0) AS refund,
                    ((SUM(tlb_mastertransaction.loanAmount) + SUM(tlb_mastertransaction.interest)) - SUM(tlb_mastertransaction.loanRepayment)) AS loan_balance
       
              FROM
                tbl_personalinfo
                INNER JOIN tlb_mastertransaction ON tbl_personalinfo.staff_id = tlb_mastertransaction.staff_id
                INNER JOIN tbpayrollperiods ON tbpayrollperiods.Periodid = tlb_mastertransaction.periodid
                LEFT JOIN tbl_refund ON tbl_refund.staff_id = tbl_personalinfo.staff_id AND tbl_refund.periodid = tbpayrollperiods.Periodid
              WHERE
                tbpayrollperiods.Periodid BETWEEN ? AND ?";

        // Initialize parameters array with periods
        $params = [$periodFrom, $periodTo];

        // Conditionally add staff_id to the query and parameters
        if (($staffId !== '')) {
            $query .= " AND tbl_personalinfo.staff_id = ?";
            $params[] = $staffId; // Append staff_id to parameters
        }

        // Add GROUP BY clause
        $query .= " GROUP BY tlb_mastertransaction.periodid, tbl_personalinfo.staff_id";

        // Prepare, execute, and return results
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchSmsTable($period, $staffId = ''): array
    {
        // Base query
        $query = "SELECT
                    tbl_personalinfo.staff_id,
                    GROUP_CONCAT(tlb_mastertransaction.transactionid) AS transactionids,
                    CONCAT(tbl_personalinfo.Lname, ', ', tbl_personalinfo.Fname, ' ', IFNULL(tbl_personalinfo.Mname, '')) AS namess,
                    SUM(tlb_mastertransaction.Contribution) AS Contribution,
                    ifnull((SUM(tlb_mastertransaction.loanAmount) + SUM(tlb_mastertransaction.interest)),0) AS loan,
                    SUM(tlb_mastertransaction.loanRepayment) AS loanrepayments,
                    SUM(tlb_mastertransaction.withdrawal) AS withrawals,
                    (SUM(tlb_mastertransaction.Contribution) + SUM(tlb_mastertransaction.loanRepayment) + IFNULL(SUM(tbl_refund.amount), 0)) AS total,
                    MAX(tbpayrollperiods.PayrollPeriod) AS PayrollPeriod,
                    GROUP_CONCAT(DISTINCT tlb_mastertransaction.periodid) AS periodids,
                    IFNULL(SUM(tbl_refund.amount), 0) AS refund,
                   ifnull( ((SUM(tlb_mastertransaction.loanAmount) + SUM(tlb_mastertransaction.interest)) - SUM(tlb_mastertransaction.loanRepayment)),0) AS loan_balance
       
              FROM
                tbl_personalinfo
                INNER JOIN tlb_mastertransaction ON tbl_personalinfo.staff_id = tlb_mastertransaction.staff_id
                INNER JOIN tbpayrollperiods ON tbpayrollperiods.Periodid = tlb_mastertransaction.periodid
                LEFT JOIN tbl_refund ON tbl_refund.staff_id = tbl_personalinfo.staff_id AND tbl_refund.periodid = tbpayrollperiods.Periodid
              WHERE
                tbpayrollperiods.Periodid <= ? and tbl_personalinfo.status = 1";

        // Initialize parameters array with periods
        $params = [$period];

        // Conditionally add staff_id to the query and parameters
        if (($staffId !== '')) {
            $query .= " AND tbl_personalinfo.staff_id = ?";
            $params[] = $staffId; // Append staff_id to parameters
        }

        // Add GROUP BY clause
        $query .= " GROUP BY tbl_personalinfo.staff_id";

        // Prepare, execute, and return results
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSumWithoutFilter(string $table, string $column): float|int
    {
        $query = "SELECT (SUM({$column})) AS total FROM {$table}";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (float)$result['total'] : 0;
    }

    public function getContributionGrandTotal($period_id = null): float|int
    {
        if ($period_id !== null && $period_id > 0) {
            // Query with period_id filter
            $query = "SELECT (SUM(tbl_contributions.contribution) + SUM(tbl_contributions.loan) + SUM(tbl_contributions.special_savings)) AS total 
                     FROM tbl_contributions 
                     INNER JOIN tbl_personalinfo ON tbl_personalinfo.staff_id = tbl_contributions.staff_id 
                     WHERE `Status` = :status AND tbl_contributions.period_id = :period_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':status' => 1, ':period_id' => $period_id]);
        } else {
            // Original query without period_id filter (for backward compatibility)
            $query = "SELECT (SUM(tbl_contributions.contribution) + SUM(tbl_contributions.loan) + SUM(tbl_contributions.special_savings)) AS total 
                     FROM tbl_contributions 
                     INNER JOIN tbl_personalinfo ON tbl_personalinfo.staff_id = tbl_contributions.staff_id 
                     WHERE `Status` = :status";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':status' => 1]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (float)$result['total'] : 0;
    }
    public function checkBeforeUpload($staff_id): bool
    {
        $queryCheck = "SELECT * FROM tbl_personalinfo WHERE staff_id = :staff_id";
        $stmtCheck = $this->pdo->prepare($queryCheck);
        $stmtCheck->execute([':staff_id' => $staff_id]);
        $rowCheck = $stmtCheck->fetch();

        if (!$rowCheck) {
            return false;
        } else {
            return true;
        }
    }
    public function loanCompare()
    {

        $query = $this->pdo->prepare("SELECT tbl_personalinfo.staff_id, concat(tbl_personalinfo.Lname,' , ',' ',tbl_personalinfo.Fname,' ',tbl_personalinfo.Mname) as namee, ((sum(tlb_mastertransaction.loanAmount) + (sum(tlb_mastertransaction.interest))) - sum(tlb_mastertransaction.loanRepayment)) as loanBalance, tbl_contributions.loan FROM tlb_mastertransaction INNER JOIN tbl_personalinfo ON tbl_personalinfo.staff_id = tlb_mastertransaction.staff_id INNER JOIN tbl_contributions ON tbl_contributions.staff_id = tbl_personalinfo.staff_id WHERE `Status` = 1 GROUP BY tbl_personalinfo.staff_id ORDER BY loanBalance desc");
        // Execute with the parameters in a single array
        $query->execute([]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function updateStaffIDNotFound($notIn): bool
    {
        $queryCheck = "UPDATE tbl_contributions SET contribution = 0,loan=0,special_savings=0 WHERE staff_id IN
        (SELECT tbl_personalinfo.staff_id FROM tbl_personalinfo WHERE staff_id NOT IN ({$notIn}))";
        $stmtCheck = $this->pdo->prepare($queryCheck);
        $stmtCheck->execute();
        $rowCheck = $stmtCheck->fetch();

        if (!$rowCheck) {
            return false;
        } else {
            return true;
        }
    }



    public function upsertContribution($staff_id, $contributions, $loanRepayment, $specialSavings, $period_id = null): bool
    {
        $contributions = str_replace(",", "", (string)$contributions);
        $loanRepayment = str_replace(",", "", (string)$loanRepayment);
        $specialSavings = str_replace(",", "", (string)$specialSavings);

        try {
            if ($period_id !== null && $period_id > 0) {
                // Check with both staff_id and period_id
                $queryCheck = "SELECT * FROM tbl_contributions WHERE staff_id = :staff_id AND period_id = :period_id";
                $stmtCheck = $this->pdo->prepare($queryCheck);
                $stmtCheck->execute([':staff_id' => $staff_id, ':period_id' => $period_id]);
                $rowCheck = $stmtCheck->fetch();

                if (!$rowCheck) {
                    // Insert with period_id
                    $insertSQL = "INSERT INTO tbl_contributions (contribution, loan, staff_id, special_savings, period_id) VALUES (:contributions, :loanRepayment, :staff_id, :specialSavings, :period_id)";
                    $stmtInsert = $this->pdo->prepare($insertSQL);
                    return $stmtInsert->execute([
                        ':contributions' => $contributions,
                        ':loanRepayment' => $loanRepayment,
                        ':staff_id' => $staff_id,
                        ':specialSavings' => $specialSavings,
                        ':period_id' => $period_id
                    ]);
                } else {
                    // Update with period_id filter
                    $updateSQL = "UPDATE tbl_contributions SET contribution = :contributions, loan = :loanRepayment, special_savings = :specialSavings WHERE staff_id = :staff_id AND period_id = :period_id";
                    $stmtUpdate = $this->pdo->prepare($updateSQL);
                    return $stmtUpdate->execute([
                        ':contributions' => $contributions,
                        ':loanRepayment' => $loanRepayment,
                        ':specialSavings' => $specialSavings,
                        ':staff_id' => $staff_id,
                        ':period_id' => $period_id
                    ]);
                }
            } else {
                // Original logic without period_id (for backward compatibility)
                $queryCheck = "SELECT * FROM tbl_contributions WHERE staff_id = :staff_id";
                $stmtCheck = $this->pdo->prepare($queryCheck);
                $stmtCheck->execute([':staff_id' => $staff_id]);
                $rowCheck = $stmtCheck->fetch();

                if (!$rowCheck) {
                    // Insert
                    $insertSQL = "INSERT INTO tbl_contributions (contribution, loan, staff_id, special_savings) VALUES (:contributions, :loanRepayment, :staff_id, :specialSavings)";
                    $stmtInsert = $this->pdo->prepare($insertSQL);
                    return $stmtInsert->execute([
                        ':contributions' => $contributions,
                        ':loanRepayment' => $loanRepayment,
                        ':staff_id' => $staff_id,
                        ':specialSavings' => $specialSavings
                    ]);
                } else {
                    // Update
                    $updateSQL = "UPDATE tbl_contributions SET contribution = :contributions, loan = :loanRepayment, special_savings = :specialSavings WHERE staff_id = :staff_id";
                    $stmtUpdate = $this->pdo->prepare($updateSQL);
                    return $stmtUpdate->execute([
                        ':contributions' => $contributions,
                        ':loanRepayment' => $loanRepayment,
                        ':specialSavings' => $specialSavings,
                        ':staff_id' => $staff_id
                    ]);
                }
            }
        } catch (PDOException $e) {
            error_log("Upsert contribution error: " . $e->getMessage());
            return false;
        }
    }
    public function setting(string $table, string $column)
    {
        $sql = "SELECT {$column} as 'column' FROM {$table}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['column'] ?? null;
    }

    public function getLimitedOrderedItem(string $table, string $orderBy, string $order, int $limit, int $offset): array
    {
        $sql = "SELECT * FROM {$table} ORDER BY {$orderBy} {$order} LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    //Function to get loan details from period
    public function getLoanDetails($periodid): array
    {
        $sql = "SELECT CONCAT(tbl_personalinfo.Lname,', ',tbl_personalinfo.Fname,' ',(ifnull(tbl_personalinfo.Mname,' '))) AS `name`,
        (tbl_loan.loanamount + tbl_loan.interest) as loanamount,
        tbl_loan.loanamount as loan_principal, tbl_loan.interest as loan_interest,
        tbl_loan.periodid, tbl_loan.staff_id, tbl_loan.loanid FROM tbl_personalinfo INNER JOIN tbl_loan ON tbl_loan.staff_id = tbl_personalinfo.staff_id
        WHERE tbl_loan.periodid = :periodid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':periodid', $periodid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    //Function to get contribution details
    public function getContributionsDetails($period_id = null): array
    {
        if ($period_id !== null && $period_id > 0) {
            // Filter by period_id if provided
            $sql = "SELECT CONCAT(tbl_personalinfo.Lname,', ',tbl_personalinfo.Fname,' ',(ifnull(tbl_personalinfo.Mname,' '))) AS `name`,
                    tbl_contributions.contribution, tbl_contributions.loan, tbl_contributions.special_savings, tbl_contributions.staff_id, tbl_contributions.cont_id as id,
                    (tbl_contributions.contribution + tbl_contributions.loan + tbl_contributions.special_savings) AS total
                    FROM tbl_personalinfo
                    INNER JOIN tbl_contributions ON tbl_contributions.staff_id = tbl_personalinfo.staff_id
                    WHERE tbl_contributions.period_id = :period_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':period_id' => $period_id]);
        } else {
            // Original query if period_id is not provided
            $sql = "SELECT CONCAT(tbl_personalinfo.Lname,', ',tbl_personalinfo.Fname,' ',(ifnull(tbl_personalinfo.Mname,' '))) AS `name`,
                    tbl_contributions.contribution, tbl_contributions.loan, tbl_contributions.special_savings, tbl_contributions.staff_id, tbl_contributions.id,
                    (tbl_contributions.contribution + tbl_contributions.loan + tbl_contributions.special_savings) AS total
                    FROM tbl_personalinfo
                    INNER JOIN tbl_contributions ON tbl_contributions.staff_id = tbl_personalinfo.staff_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    //Delete rows from table

    public function deleteRows(string $table, string $column, $id): void
    {
        try {
            $sql = "DELETE FROM {$table} WHERE {$column} = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    public function getPassword($staff_id): array
    {
        try {
            $sql = "SELECT PlainPassword FROM tblusers WHERE UserID = :staff_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
            return [];
        }
    }
    public function getStatus($staff_id, $period): array
    {
        try {
            $sql = "SELECT sum(tlb_mastertransaction.Contribution) as Contribution, (sum(tlb_mastertransaction.loanAmount)+sum(tlb_mastertransaction.interest)) as Loan, ((sum(tlb_mastertransaction.loanAmount)+ sum(tlb_mastertransaction.interest))- sum(tlb_mastertransaction.loanRepayment)) as Loanbalance, sum(tlb_mastertransaction.withdrawal) as withdrawal FROM tlb_mastertransaction 
                    where staff_id = :staff_id AND tlb_mastertransaction.periodid <= :periodid GROUP BY staff_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
            $stmt->bindParam(':periodid', $period, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
            return [];
        }
    }

    public function getSocietyStatus($period)
    {
        try {
            $sql = "SELECT 
                        SUM(IFNULL(tlb.Contribution, 0)) as TotalContribution, 
                        SUM(IFNULL(tlb.withdrawal, 0)) as TotalWithdrawal,
                        (SUM(IFNULL(tlb.Contribution, 0)) - SUM(IFNULL(tlb.withdrawal, 0))) as NetContribution,
                        (SUM(IFNULL(tlb.loanAmount, 0)) + SUM(IFNULL(tlb.interest, 0))) as TotalLoan, 
                        SUM(IFNULL(tlb.loanRepayment, 0)) as TotalLoanRepayment,
                        ((SUM(IFNULL(tlb.loanAmount, 0)) + SUM(IFNULL(tlb.interest, 0))) - SUM(IFNULL(tlb.loanRepayment, 0))) as LoanBalance
                    FROM tlb_mastertransaction tlb
                    INNER JOIN tbl_personalinfo ON tlb.staff_id = tbl_personalinfo.staff_id
                    WHERE tlb.periodid <= :periodid AND tbl_personalinfo.status = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':periodid', $period, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function deleteRows2Column(string $table, string $column1, $value1, string $column2, $value2): void
    {
        try {
            $sql = "DELETE FROM {$table} WHERE {$column1} = :column1 AND {$column2}=:column2 ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':column1', $value1, PDO::PARAM_INT);
            $stmt->bindParam(':column2', $value2, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    // Function to fetch ordered items
    public function getOrderedItem(string $tableName, string $valueColumn, string $displayTextColumn): array
    {
        $stmt = $this->pdo->prepare("SELECT $valueColumn, $displayTextColumn FROM $tableName order by $valueColumn DESC");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getMonthlyContributionsForCurrentYear($PhysicalYear): array|false
    {
        try {
            //$currentYear = date('Y'); // Get the current year

            $sql = "SELECT sum(tlb_mastertransaction.loanRepayment) +sum(tlb_mastertransaction.Contribution) as total, tbpayrollperiods.PayrollPeriod as label FROM
	        tlb_mastertransaction INNER JOIN tbpayrollperiods ON tlb_mastertransaction.periodid = tbpayrollperiods.Periodid WHERE tbpayrollperiods.PhysicalYear = :PhysicalYear GROUP BY tlb_mastertransaction.periodid";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':PhysicalYear', $PhysicalYear, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $results;
        } catch (PDOException $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }

    //get Active members
    public function getActiveMembersCount(string $column, string $search, $value): int
    {
        $sql = "SELECT COUNT({$column}) as activeMembers FROM tbl_personalinfo WHERE $search = '{$value}'";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['activeMembers'];
    }

    // Function to fetch Names - Concatenatoin
    public function getConcate3Column(string $tableName, string $column1, string $column2, string $column3, string $concat, string $indexName): array
    {
        $stmt = $this->pdo->prepare("SELECT CONCAT({$indexName},' - ' ,{$column1},', ',{$column2},' ',(ifnull({$column3},' '))) as {$concat} , {$indexName} FROM $tableName WHERE status = 1 order by $indexName ASC");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function checkIfStaffNoExists($staffNo): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tbl_personalinfo WHERE staff_id = :staffNo");
        $stmt->execute(['staffNo' => $staffNo]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    }

    public function checkIfPeriodExists($period): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tbpayrollperiods WHERE PayrollPeriod = :period");
        $stmt->execute(['period' => $period]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    }

    public function generateRandomPassword(int $length = 5): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomPassword = '';
        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomPassword;
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    public function getContributionSettings(): float|int|null
    {
        try {
            // Select the contribution setting from tbl_settings
            $query_settings_contri = "SELECT contribution FROM tbl_settings";
            $stmt = $this->pdo->query($query_settings_contri);
            $contributions = null;

            if ($row_settings_contri = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $contributions = $row_settings_contri['contribution'];
            }
            return $contributions;
        } catch (PDOException $e) {
            error_log("Error in getContributionSettings: " . $e->getMessage());
            return null;
        }
    }

    public function savePeriod($period, $insertedBy): bool|string
    {
        try {


            $this->pdo->beginTransaction();

            // Split the period into year and month
            $sArrary = explode("-", $period);
            $PhysicalYear = isset($sArrary[1]) ? $sArrary[1] : '';
            $PhysicalMonth = isset($sArrary[0]) ? $sArrary[0] : '';
            // SQL to insert main user data
            $sqlPeriod = "INSERT INTO tbpayrollperiods (PayrollPeriod,PhysicalYear, PhysicalMonth, InsertedBy, DateInserted) VALUES (?,?,?, ?,NOW())";
            $stmtPeriod = $this->pdo->prepare($sqlPeriod);
            $stmtPeriod->execute([$period, $PhysicalYear, $PhysicalMonth, $insertedBy]);
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            // Handle error
            error_log("Database error: " . $e->getMessage()); // Log error or handle as needed
            return $e->getMessage();
        }
    }


    public function saveFormData($contributions, $plainPassword, $hashedPassword, $staffNo, $title, $firstName, $middleName, $lastName, $gender, $dob, $address, $address2, $city, $stateId, $mobilePhone, $emailAddress, $status, $nokName, $nokRelationship, $nokPhone, $nokAddress): void
    {
        try {


            $this->pdo->beginTransaction();

            // Check if staff_id already exists in the personal info table
            $sqlCheckUser = "SELECT staff_id FROM tbl_personalinfo WHERE staff_id = ?";
            $stmtCheckUser = $this->pdo->prepare($sqlCheckUser);
            $stmtCheckUser->execute([$staffNo]);
            $existingUser = $stmtCheckUser->fetch();

            if ($existingUser) {
                // Update personal info
                $sqlUpdateUser = "UPDATE tbl_personalinfo SET title = ?, Fname = ?, Mname = ?, Lname = ?, gender = ?, DOB = ?, Address = ?, Address2 = ?, City = ?, state_id = ?, MobilePhone = ?, EmailAddress = ?, Status = ?, DateOfReg = now(), doi = now() WHERE staff_id = ?";
                $stmtUpdateUser = $this->pdo->prepare($sqlUpdateUser);
                $stmtUpdateUser->execute([$title, $firstName, $middleName, $lastName, $gender, $dob, $address, $address2, $city, $stateId, $mobilePhone, $emailAddress, $status, $staffNo]);

                // Update NOK info
                $sqlUpdateNok = "UPDATE tbl_nok SET NOkName = ?, NOKRelationship = ?, NOKPhone = ?, NOKAddress = ? WHERE staff_id = ?";
                $stmtUpdateNok = $this->pdo->prepare($sqlUpdateNok);
                $stmtUpdateNok->execute([$nokName, $nokRelationship, $nokPhone, $nokAddress, $staffNo]);
            } else {

                // SQL to insert main user data
                $sqlUser = "INSERT INTO tbl_personalinfo (staff_id, title, Fname, Mname, Lname, gender, DOB, Address, Address2, City, state_id, MobilePhone, EmailAddress, Status,DateOfReg,doi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,now(),now())";
                $stmtUser = $this->pdo->prepare($sqlUser);
                $stmtUser->execute([$staffNo, $title, $firstName, $middleName, $lastName, $gender, $dob, $address, $address2, $city, $stateId, $mobilePhone, $emailAddress, $status]);

                $userId = $this->pdo->lastInsertId(); // Get the last insert ID to use as a foreign key

                // SQL to insert NOK data, assuming 'user_id' is the foreign key column in your NOK table
                $sqlNok = "INSERT INTO tbl_nok (staff_id, NOkName, NOKRelationship, NOKPhone, NOKAddress) VALUES (?, ?, ?, ?, ?)";
                $stmtNok = $this->pdo->prepare($sqlNok);
                $stmtNok->execute([$staffNo, $nokName, $nokRelationship, $nokPhone, $nokAddress]);

                // SQL to insert username
                $sqlusername = "INSERT INTO tblusers (UserID, Username,UPassword,CPassword,PlainPassword,dateofRegistration) VALUES (?,?,?,?,?,now())";
                $stmtUsername = $this->pdo->prepare($sqlusername);
                $stmtUsername->execute([$staffNo, $staffNo, $hashedPassword, $hashedPassword, $plainPassword]);

                // SQL to insert contribution
                $sqlcontributions = "INSERT INTO tbl_contributions (staff_id, contribution) VALUES (?, ?)";
                $stmtContributions = $this->pdo->prepare($sqlcontributions);
                $stmtContributions->execute([$staffNo, $contributions]);
            }

            $this->pdo->commit();
            //return true;
            echo 1;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            // Handle error
            error_log("Database error: " . $e->getMessage()); // Log error or handle as needed
            //return false;
            //echo $e->getMessage();
            echo 2;
        }
    }
}