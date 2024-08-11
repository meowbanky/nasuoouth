<?php require_once('Connections/hms.php');


$col_status = "-1";
if (isset($_GET['id'])) {
  $col_status = $_GET['id'];
}

$col_period = "-1";
if (isset($_GET['period'])) {
  $col_period = $_GET['period'];
}

mysqli_select_db($hms,$database_hms);
$query_status = sprintf("SELECT tbl_personalinfo.staff_id, concat(tbl_personalinfo.Lname,' , ', tbl_personalinfo.Fname,' ', ifnull( tbl_personalinfo.Mname,'')) as namess, sum(tlb_mastertransaction.Contribution) as Contribution, (sum(tlb_mastertransaction.loanAmount)+ sum(tlb_mastertransaction.interest)) as Loan, ((sum(tlb_mastertransaction.loanAmount)+ sum(tlb_mastertransaction.interest))- sum(tlb_mastertransaction.loanRepayment)) as Loanbalance, sum(tlb_mastertransaction.withdrawal) as withdrawal FROM tlb_mastertransaction INNER JOIN tbl_personalinfo ON tbl_personalinfo.staff_id = tlb_mastertransaction.staff_id where staff_id = %s AND tlb_mastertransaction.periodid <= %s GROUP BY staff_id", GetSQLValueString($col_status, "text",$hms),GetSQLValueString($col_period, "int",$hms));
$status = mysqli_query($hms,$query_status) or die(mysqli_error($hms));
$row_status = mysqli_fetch_assoc($status);
$totalRows_status = mysqli_num_rows($status);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body><table width="100%" border="1" class="greyBgdHeader">
                                 <tr class="table_header_new">
                                   <th width="14%" scope="col"><strong>Staff ID</strong></th>
                                   <th width="23%" scope="col">Name</th>
                                   <th width="15%" scope="col">Contribution</th>
                                   <th width="15%" scope="col">Loan</th>
                                   <th width="15%" scope="col">Loan Balance</th>
                                   <th width="18%" scope="col">Withdrawal</th>
                                   </tr>
                                <?php do { ?>   <tr>
                                   <th align="left" scope="col"><?php echo $row_status['staff_id']; ?></th>
                                   <th align="left" scope="col"><?php echo $row_status['namess']; ?></th>
                                   <th align="right" scope="col"><?php echo number_format($row_status['Contribution'] ,2,'.',','); ?></th>
                                   <th align="right" scope="col"><?php echo number_format($row_status['Loan'] ,2,'.',','); ?></th>
                                   <th align="right" scope="col"><?php echo number_format($row_status['Loanbalance'] ,2,'.',','); ?></th>
                                   <th align="right" scope="col"><?php echo number_format($row_status['withdrawal'] ,2,'.',','); ?></th>
                                   </tr> <?php } while ($row_status = mysqli_fetch_assoc($status)); ?>
                               </table>
</body>
</html>
<?php
mysqli_free_result($status);
?>
