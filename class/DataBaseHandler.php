<?php
// Include the constants file
require_once('db_constants.php');

class DatabaseHandler
{
    public $pdo;
    private $options = [
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
    public function getSelectItems($tableName, $valueColumn, $displayTextColumn)
    {
        $stmt = $this->pdo->prepare("SELECT $valueColumn, $displayTextColumn FROM $tableName");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Function to fetch Balance of Any column
    public function getBalance($staff_id, $table, $olumn, $displayColumn,  $filterColumn, $filterValue, $equality = '<=')
    {
        $query = "SELECT SUM({$olumn}) as {$displayColumn}  FROM {$table} WHERE staff_id =? AND  {$filterColumn} {$equality}? GROUP BY staff_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$staff_id, $filterValue]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result[$displayColumn]; // Return the actual balance value
        } else {
            return 0; // Return 0 if no balance found for the provided staffId
        }
    }

    // Function to fetch Balance of Any column
    public function activeMembers($status, $staff_id, $equality = '>=')
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
    public function countActivemembers($status, $staff_id, $equality = '>=')
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM tbl_personalinfo
	                                WHERE `Status` = ? AND staff_id {$equality} ?");
        $stmt->execute([$status, $staff_id]);

        return $stmt->fetchAll();
    }

    // Function to fetch select items
    public function getLoanBalance($staff_id)
    {
        $stmt = $this->pdo->prepare("SELECT (sum(tlb_mastertransaction.loanAmount) + sum(tlb_mastertransaction.interest)) - sum(tlb_mastertransaction.loanRepayment) as balance
                                        FROM tlb_mastertransaction WHERE staff_id = ?");
        $stmt->execute([$staff_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['balance'];
        } else {
            return '0'; // No balance found for the provided staffId
        }
    }

    public function getLoanStatus($staff_id)
    {
        $stmt = $this->pdo->prepare("SELECT (sum(ifnull(loanAmount,0))+sum(ifnull(interest,0))) - sum(ifnull(loanRepayment,0)) balance FROM tlb_mastertransaction WHERE staff_id  = ?");
        $stmt->execute([$staff_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['balance'];
        } else {
            return '0'; // No balance found for the provided staffId
        }
    }
    // Function to fetch single Item
    public function getSingleItem($tableName, $returnColumn, $filter, $filterValue)
    {
        $stmt = $this->pdo->prepare("SELECT {$returnColumn} FROM {$tableName} WHERE {$filter} = ?");
        $stmt->execute([$filterValue]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result[$returnColumn];
        } else {
            return '0'; // No balance found for the provided staffId
        }
    }

    public function fetchNames($term)
    {
        $query = $this->pdo->prepare("SELECT tbl_personalinfo.staff_id, tbl_personalinfo.Fname, tbl_personalinfo.Mname, tbl_personalinfo.Lname, tbl_personalinfo.MobilePhone FROM tbl_personalinfo WHERE (tbl_personalinfo.staff_id LIKE ? OR tbl_personalinfo.Fname LIKE ? OR tbl_personalinfo.Mname LIKE ? OR tbl_personalinfo.Lname LIKE ? OR tbl_personalinfo.MobilePhone LIKE ?) LIMIT 5");
        // Execute with the parameters in a single array
        $query->execute(["%$term%", "%$term%", "%$term%", "%$term%", "%$term%"]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function countItems($table)
    {
        $sql = "SELECT COUNT(*) AS total FROM {$table}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
    public function insertLoan($staff_id, $periodId, $loanAmount, $interest)
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

    public function insertMasterTransaction($periodId, $staff_id, $loanId, $loanAmount, $interest)
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
    public function fetchTransactionDetails($periodFrom, $periodTo, $staffId = '')
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

    public function fetchSmsTable($period, $staffId = '')
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
                    ANY_VALUE(tbpayrollperiods.PayrollPeriod) AS PayrollPeriod,
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

    public function getSumWithoutFilter($table, $column)
    {

        $query = "SELECT (SUM({$column})) AS total FROM {$table}";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['total'] : 0;
    }

    public function getContributionGrandTotal($period_id = null)
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

        return $result ? $result['total'] : 0;
    }
    public function checkBeforeUpload($staff_id)
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


    public function updateStaffIDNotFound($notIn)
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



    public function upsertContribution($staff_id, $contributions, $loanRepayment, $specialSavings, $period_id = null)
    {
        $contributions = str_replace(",", "", $contributions);
        $loanRepayment = str_replace(",", "", $loanRepayment);
        $specialSavings = str_replace(",", "", $specialSavings);

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
                $stmtInsert->execute([
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
                $stmtUpdate->execute([
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
                $stmtInsert->execute([
                    ':contributions' => $contributions,
                    ':loanRepayment' => $loanRepayment,
                    ':staff_id' => $staff_id,
                    ':specialSavings' => $specialSavings
                ]);
            } else {
                // Update
                $updateSQL = "UPDATE tbl_contributions SET contribution = :contributions, loan = :loanRepayment, special_savings = :specialSavings WHERE staff_id = :staff_id";
                $stmtUpdate = $this->pdo->prepare($updateSQL);
                $stmtUpdate->execute([
                    ':contributions' => $contributions,
                    ':loanRepayment' => $loanRepayment,
                    ':specialSavings' => $specialSavings,
                    ':staff_id' => $staff_id
                ]);
            }
        }
    }
    public function setting($table, $column)
    {
        $sql = "SELECT {$column} as 'column' FROM {$table}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['column'];
    }

    public function getLimitedOrderedItem($table, $orderBy, $order, $limit, $offset)
    {
        $sql = "SELECT * FROM {$table} ORDER BY {$orderBy} {$order} LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    //Function to get loan details from period
    public function getLoanDetails($periodid)
    {
        $sql = "SELECT CONCAT(tbl_personalinfo.Lname,', ',tbl_personalinfo.Fname,' ',(ifnull(tbl_personalinfo.Mname,' '))) AS `name`, (tbl_loan.loanamount + tbl_loan.interest) as loanamount, 
        tbl_loan.periodid, tbl_loan.staff_id,tbl_loan.loanid FROM tbl_personalinfo INNER JOIN tbl_loan ON tbl_loan.staff_id = tbl_personalinfo.staff_id 
        WHERE tbl_loan.periodid = :periodid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':periodid', $periodid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    //Function to get contribution details
    public function getContributionsDetails()
    {
        $sql = "SELECT tbl_personalinfo.staff_id, concat(tbl_personalinfo.Lname,' , ', tbl_personalinfo.Fname,' ', ifnull( tbl_personalinfo.Mname,'')) AS namess, tbl_contributions.contribution, 
        tbl_contributions.loan, (tbl_contributions.contribution +tbl_contributions.loan) as total FROM tbl_contributions INNER JOIN tbl_personalinfo ON tbl_personalinfo.staff_id = tbl_contributions.staff_id WHERE status = 1 ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    //Delete rows from table

    public function deleteRows($table, $column, $id)
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
    public function getPassword($staff_id)
    {
        try {
            $sql = "SELECT PlainPassword FROM tblusers WHERE UserID = :staff_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    public function getStatus($staff_id, $period)
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
        }
    }
    public function deleteRows2Column($table, $column1, $value1, $column2, $value2)
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
    public function getOrderedItem($tableName, $valueColumn, $displayTextColumn)
    {
        $stmt = $this->pdo->prepare("SELECT $valueColumn, $displayTextColumn FROM $tableName order by $valueColumn DESC");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getMonthlyContributionsForCurrentYear($PhysicalYear)
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
            die("Error: " . $e->getMessage());
            return false;
        }
    }

    //get Active members
    public function getActiveMembersCount($column, $search, $value)
    {
        $sql = "SELECT COUNT({$column}) as activeMembers FROM tbl_personalinfo WHERE $search = '{$value}'";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['activeMembers'];
    }

    // Function to fetch Names - Concatenatoin
    public function getConcate3Column($tableName, $column1, $column2, $column3, $concat, $indexName)
    {
        $stmt = $this->pdo->prepare("SELECT CONCAT({$indexName},' - ' ,{$column1},', ',{$column2},' ',(ifnull({$column3},' '))) as {$concat} , {$indexName} FROM $tableName WHERE status = 1 order by $indexName ASC");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function checkIfStaffNoExists($staffNo)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tbl_personalinfo WHERE staff_id = :staffNo");
        $stmt->execute(['staffNo' => $staffNo]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    }

    public function checkIfPeriodExists($period)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tbpayrollperiods WHERE PayrollPeriod = :period");
        $stmt->execute(['period' => $period]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    }

    public function generateRandomPassword($length = 5)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomPassword = '';
        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomPassword;
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function getContributionSettings()
    {
        try {

            // Select the contribution setting from tbl_settings
            $query_settings_contri = "SELECT contribution FROM tbl_settings";
            $stmt = $this->pdo->query($query_settings_contri);

            if ($row_settings_contri = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $contributions = $row_settings_contri['contribution'];
            }
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }

        return $contributions;
    }

    public function savePeriod($period, $insertedBy)
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


    public function saveFormData($contributions, $plainPassword, $hashedPassword, $staffNo, $title, $firstName, $middleName, $lastName, $gender, $dob, $address, $address2, $city, $stateId, $mobilePhone, $emailAddress, $status, $nokName, $nokRelationship, $nokPhone, $nokAddress)
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