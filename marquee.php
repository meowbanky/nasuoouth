<?php require_once('Connections/db_constants.php'); ?>

<?php

if (!function_exists("GetSQLValueString")) {

  function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")

  {



    $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);



    switch ($theType) {

      case "text":

        $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";

        break;

      case "long":

      case "int":

        $theValue = ($theValue != "") ? intval($theValue) : "NULL";

        break;

      case "double":

        $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";

        break;

      case "date":

        $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";

        break;

      case "defined":

        $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;

        break;
    }

    return $theValue;
  }
}



mysqli_select_db($conn, $database);

$query_activeMembers = "SELECT count(*) FROM tbl_personalinfo WHERE `Status` = 1";

$activeMembers = mysqli_query($conn, $query_activeMembers) or die(mysqli_error($conn));

$row_activeMembers = mysqli_fetch_assoc($activeMembers);

$totalRows_activeMembers = mysqli_num_rows($activeMembers);



$maxRows_gender = 10;

$pageNum_gender = 0;

if (isset($_GET['pageNum_gender'])) {

  $pageNum_gender = $_GET['pageNum_gender'];
}

$startRow_gender = $pageNum_gender * $maxRows_gender;



mysqli_select_db($conn, $database);

$query_gender = "SELECT count(gender),gender FROM tbl_personalinfo WHERE `Status` = 1 GROUP BY gender";

$query_limit_gender = sprintf("%s LIMIT %d, %d", $query_gender, $startRow_gender, $maxRows_gender);

$gender = mysqli_query($conn, $query_limit_gender) or die(mysqli_error($conn));

$row_gender = mysqli_fetch_assoc($gender);



if (isset($_GET['totalRows_gender'])) {

  $totalRows_gender = $_GET['totalRows_gender'];
} else {

  $all_gender = mysqli_query($conn, $query_gender);

  $totalRows_gender = mysqli_num_rows($all_gender);
}

$totalPages_gender = ceil($totalRows_gender / $maxRows_gender) - 1;



mysqli_select_db($conn, $database);

$query_contribution = "SELECT SUM(tlb_mastertransaction.Contribution) FROM tlb_mastertransaction INNER JOIN tbl_personalinfo ON tlb_mastertransaction.staff_id = tbl_personalinfo.staff_id WHERE tbl_personalinfo.status = 1";

$contribution = mysqli_query($conn, $query_contribution) or die(mysqli_error($conn));

$row_contribution = mysqli_fetch_assoc($contribution);

$totalRows_contribution = mysqli_num_rows($contribution);



mysqli_select_db($conn, $database);

$query_loanDebt = "SELECT (SUM(tlb_mastertransaction.loanAmount)+SUM(tlb_mastertransaction.interest))-(SUM(tlb_mastertransaction.loanRepayment)) as 'LoanDebt' FROM tlb_mastertransaction INNER JOIN tbl_personalinfo ON tlb_mastertransaction.staff_id = tbl_personalinfo.staff_id WHERE tbl_personalinfo.status = 1";

$loanDebt = mysqli_query($conn, $query_loanDebt) or die(mysqli_error($conn));

$row_loanDebt = mysqli_fetch_assoc($loanDebt);

$totalRows_loanDebt = mysqli_num_rows($loanDebt);

?>



<marquee direction="left">



  <strong>
      <font color="#FF0000">SMS BALANCE:

        <?php
        try {
          //echo number_format(curlPost('http://api.ebulksms.com/balance/cov@emmaggi.com/9e6ce612af1fa2dc982e668176e806435830e5ff'));
          $response = curlPost('https://api.ng.termii.com/api/get-balance?api_key=TLJJ8KJkyaxODiQB8Fpvv4Umni0YaiWDRAMFzUcPMgLQCmjGjsBPYDC0EfRuYz');

          $jsonobj = $response;

          $obj = json_decode($jsonobj);

          echo number_format($obj->balance);
        } catch (Exception $e) {
          echo '0';
        }

        function curlPost($url)
        {
          $ch = curl_init($url);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
          $response = curl_exec($ch);
          $error = curl_error($ch);
          curl_close($ch);
          if ($error !== '') {
            throw new \Exception($error);
          }

          return $response;
        }
        ?></font>

    </strong>

  <strong> Active Members: - <?php echo $row_activeMembers['count(*)']; ?></strong>||

  <?php do { ?>

    <strong><?php echo $row_gender['gender']; ?>:<?php echo $row_gender['count(gender)']; ?></strong>||

  <?php } while ($row_gender = mysqli_fetch_assoc($gender)); ?>

  <strong>Savings:<?php echo number_format($row_contribution['SUM(tlb_mastertransaction.Contribution)'], 2); ?></strong>||



  <strong>Loan:<?php echo number_format($row_loanDebt['LoanDebt'], 2); ?></strong>



</marquee>





<?php

mysqli_free_result($activeMembers);



mysqli_free_result($gender);



mysqli_free_result($contribution);



mysqli_free_result($loanDebt);

?>