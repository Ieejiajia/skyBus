<?php
error_reporting(0);
include_once("dbconnect.php");
$userid = $_GET['userid'];
$mobile = $_GET['mobile'];
$amount = $_GET['amount'];
$orderid = $_GET['orderid'];

$data = array(
    'id' =>  $_GET['billplz']['id'],
    'paid_at' => $_GET['billplz']['paid_at'] ,
    'paid' => $_GET['billplz']['paid'],
    'x_signature' => $_GET['billplz']['x_signature']
);

$paidstatus = $_GET['billplz']['paid'];
if ($paidstatus=="true"){
    $paidstatus = "Success";
}else{
    $paidstatus = "Failed";
}
$receiptid = $_GET['billplz']['id'];
$signing = '';
foreach ($data as $key => $value) {
    $signing.= 'billplz'.$key . $value;
    if ($key === 'paid') {
        break;
    } else {
        $signing .= '|';
    }
}
 
 
$signed= hash_hmac('sha256', $signing, 'S-BhpfnYdcl50AekPFCQ-49g');
if ($signed === $data['x_signature']) {

    if ($paidstatus == "Success"){
        
        $sqlcart = "SELECT BUSID,BQUANTITY FROM TICKET WHERE EMAIL = '$userid'";
        $cartresult = $conn->query($sqlcart);
        if ($cartresult->num_rows > 0)
        {
        while ($row = $cartresult->fetch_assoc())
        {
            $busid = $row["BUSID"];
            $bq = $row["BQUANTITY"];
            $sqlinserttickethistory = "INSERT INTO TICKETHISTORY(EMAIL,ORDERID,BILLID,BUSID,BQUANTITY) VALUES ('$userid','$orderid','$receiptid','$busid','$bq')";
            $conn->query($sqlinserttickethistory);
            
            $selectbus = "SELECT * FROM SCHEDULE WHERE ID = '$busid'";
            $busresult = $conn->query($selectbus);
             if ($busresult->num_rows > 0){
                  while ($rowb = $busresult->fetch_assoc()){
                    $prquantity = $rowb["QUANTITY"];
                    $prevsold = $rowb["SOLD"];
                    $newquantity = $prquantity - $bq;
                    $newsold = $prevsold + $bq;
                    $sqlupdatequantity = "UPDATE SCHEDULE SET QUANTITY = '$newquantity', SOLD = '$newsold' WHERE ID = '$busid'";
                    $conn->query($sqlupdatequantity);
                  }
             }
        }
        
       $sqldeletecart = "DELETE FROM TICKET WHERE EMAIL = '$userid'";
       $sqlinsert = "INSERT INTO PAYMENT(ORDERID,BILLID,USERID,TOTAL) VALUES ('$orderid','$receiptid','$userid','$amount')";
       
       $conn->query($sqldeletecart);
       $conn->query($sqlinsert);
    }
        echo '<br><br><body><div><h2><br><br><center>Receipt</center></h1><table border=1 width=80% align=center><tr><td>Order id</td><td>'.$orderid.'</td></tr><tr><td>Receipt ID</td><td>'.$receiptid.'</td></tr><tr><td>Email to </td><td>'.$userid. ' </td></tr><td>Amount </td><td>RM '.$amount.'</td></tr><tr><td>Payment Status </td><td>'.$paidstatus.'</td></tr><tr><td>Date </td><td>'.date("d/m/Y").'</td></tr><tr><td>Time </td><td>'.date("h:i a").'</td></tr></table><br><p><center>Press back button to return to skyBus</center></p></div></body>';
        //echo $sqlinsertcarthistory;';
        //echo $sqlinserttickethistory;
    } 
        else 
    {
    echo 'Payment Failed!';
    }
}

?>