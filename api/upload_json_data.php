<?php
/**
 * Upload JSON Data Endpoint
 * Processes and uploads data from OOUTH Salary API to database
 * Follows import.php logic for tbl_contributions (contribution, loan, special_savings)
 */

// Start output buffering
ob_start();

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['UserID']) || (trim($_SESSION['UserID']) == '')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Set headers
header('Content-Type: application/json');

// Database connection
require_once(__DIR__ . '/../Connections/db_constants.php');

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    
    if (!$request) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($request['local_period']) || !isset($request['data']) || !is_array($request['data'])) {
        throw new Exception('Missing required fields: local_period and data');
    }
    
    $localPeriodId = (int)$request['local_period']; // Period ID for tbpayrollperiods
    $apiPeriodInfo = $request['api_period_info'] ?? null;
    $localPeriodInfo = $request['local_period_info'] ?? null;
    $resourceType = $request['resource_type'] ?? 'deduction';
    $resourceId = $request['resource_id'] ?? null;
    $resourceName = $request['resource_name'] ?? 'Unknown';
    $data = $request['data'];
    
    if (empty($data)) {
        throw new Exception('No data to upload');
    }
    
    if ($localPeriodId <= 0) {
        throw new Exception('Invalid local period ID');
    }
    
    // Select database
    mysqli_select_db($conn, $database);
    
    // Get default contribution from settings (following import.php logic)
    $settingSql = "SELECT contribution FROM tbl_settings LIMIT 1";
    $settingResult = mysqli_query($conn, $settingSql);
    if (!$settingResult || mysqli_num_rows($settingResult) == 0) {
        throw new Exception('Failed to load default contribution from settings');
    }
    $settingRow = mysqli_fetch_assoc($settingResult);
    $defaultContribution = floatval($settingRow['contribution']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    $successCount = 0;
    $errorCount = 0;
    $notFound = [];
    $processedStaffIds = [];
    $errors = [];
    
    // Process each record (following import.php logic)
    foreach ($data as $record) {
        $staffId = trim((string)$record['staff_id']);
        $amount = floatval($record['amount']);
        
        // Skip invalid or non-numeric IDs
        if (!is_numeric($staffId) || $staffId <= 0) {
            continue;
        }
        
        // Check if staff exists in tbl_personalinfo (following import.php logic)
        $sqlStaff = "SELECT staff_id FROM tbl_personalinfo WHERE staff_id = ?";
        $stmt = mysqli_prepare($conn, $sqlStaff);
        mysqli_stmt_bind_param($stmt, "s", $staffId);
        mysqli_stmt_execute($stmt);
        $staffResult = mysqli_stmt_get_result($stmt);
        $staffFound = mysqli_num_rows($staffResult) > 0;
        mysqli_stmt_close($stmt);
        
        if ($staffFound) {
            // Get loan status (balance) from tlb_mastertransaction
            $loanSql = "SELECT (SUM(IFNULL(loanAmount, 0)) + SUM(IFNULL(interest, 0))) - SUM(IFNULL(loanRepayment, 0)) AS balance 
                       FROM tlb_mastertransaction 
                       WHERE staff_id = ?";
            $loanStmt = mysqli_prepare($conn, $loanSql);
            mysqli_stmt_bind_param($loanStmt, "s", $staffId);
            mysqli_stmt_execute($loanStmt);
            $loanResult = mysqli_stmt_get_result($loanStmt);
            $loanRow = mysqli_fetch_assoc($loanResult);
            $loanStatus = floatval($loanRow['balance'] ?? 0);
            mysqli_stmt_close($loanStmt);
            
            // Calculate contribution, loan repayment, and special savings (following import.php logic)
            $contribution = $defaultContribution;
            $loanRepayment = 0;
            $specialSavings = 0;
            
            if ($amount >= $defaultContribution && $loanStatus > 0) {
                // Has active loan: extra goes to loan repayment
                $loanRepayment = $amount - $defaultContribution;
                $specialSavings = 0;
            } elseif ($amount >= $defaultContribution && $loanStatus == 0) {
                // No active loan: extra goes to special savings
                $specialSavings = $amount - $defaultContribution;
                $loanRepayment = 0;
            } else {
                // Amount less than default contribution
                $loanRepayment = 0;
                $specialSavings = 0;
            }
            
            $processedStaffIds[] = $staffId;
            
            // Upsert into tbl_contributions (following import.php logic)
            $checkSql = "SELECT COUNT(*) AS count FROM tbl_contributions WHERE staff_id = ? AND period_id = ?";
            $checkStmt = mysqli_prepare($conn, $checkSql);
            mysqli_stmt_bind_param($checkStmt, "si", $staffId, $localPeriodId);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_bind_result($checkStmt, $count);
            mysqli_stmt_fetch($checkStmt);
            mysqli_stmt_close($checkStmt);
            
            if ($count > 0) {
                // Update existing record
                $sql = "UPDATE tbl_contributions 
                        SET contribution = ?, loan = ?, special_savings = ? 
                        WHERE staff_id = ? AND period_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "dddsi", $contribution, $loanRepayment, $specialSavings, $staffId, $localPeriodId);
            } else {
                // Insert new record
                $sql = "INSERT INTO tbl_contributions (contribution, loan, staff_id, special_savings, period_id) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ddsdi", $contribution, $loanRepayment, $staffId, $specialSavings, $localPeriodId);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $successCount++;
                mysqli_stmt_close($stmt);
            } else {
                $errorCount++;
                $errors[] = "Failed to update contribution for {$staffId}: " . mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            // Staff not found - include name from API data if available
            $staffName = $record['name'] ?? 'Unknown';
            $notFound[] = [
                'staff_id' => $staffId,
                'name' => $staffName,
                'amount' => $amount
            ];
            $errorCount++;
        }
    }
    
    // Update records not in the uploaded data to 0 (following import.php logic)
    if (!empty($processedStaffIds)) {
        $src = implode(',', array_filter($processedStaffIds, 'is_numeric'));
        
        if (!empty($src)) {
            // Update contributions for staff not in uploaded data for this period
            $update1 = "UPDATE tbl_contributions 
                       SET contribution = 0, loan = 0, special_savings = 0 
                       WHERE period_id = ? AND staff_id IN (
                           SELECT staff_id 
                           FROM tbl_personalinfo 
                           WHERE staff_id NOT IN ($src)
                       )";
            $stmt1 = mysqli_prepare($conn, $update1);
            mysqli_stmt_bind_param($stmt1, "i", $localPeriodId);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);
        }
    }
    
    // Commit transaction if most records were successful
    if ($successCount > 0 && $errorCount < ($successCount / 2)) {
        mysqli_commit($conn);
        
        // Format not found staff with names
        $displayNF = 'All records processed successfully.';
        $notFoundDetails = [];
        
        if (!empty($notFound)) {
            $notFoundList = [];
            foreach ($notFound as $staff) {
                $notFoundList[] = "{$staff['staff_id']} ({$staff['name']}) - ₦" . number_format($staff['amount'], 2);
                $notFoundDetails[] = $staff;
            }
            $displayNF = 'Staff not found in database: ' . implode(', ', $notFoundList);
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Upload completed: {$successCount} records processed successfully",
            'details' => "{$successCount} succeeded, {$errorCount} failed",
            'not_found' => $displayNF,
            'data' => [
                'total' => count($data),
                'success' => $successCount,
                'errors' => $errorCount,
                'not_found_count' => count($notFound),
                'not_found_list' => $notFoundDetails,
                'error_messages' => $errors
            ]
        ]);
    } else {
        // Rollback if too many errors
        mysqli_rollback($conn);
        throw new Exception("Upload failed: Too many errors ({$errorCount} errors out of " . count($data) . " records). Transaction rolled back.");
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}

ob_end_flush();