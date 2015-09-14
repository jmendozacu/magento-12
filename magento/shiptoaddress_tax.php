<?php 
require_once 'app/Mage.php';
ini_set('memory_limit', '1024M');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
umask(0);
Mage::app();
Mage::register('isSecureArea', true);

// $toDate = date('Y-m-d H:i:s');
$toDate = date('Y-m-d');
$previousDate = date('Y-m-d', strtotime($toDate." -1 month"));


?>
<html>
<head>
	<style type="text/css">
		.inputfield{width: 10%;float: left;padding: 10px;margin-left: 10px;}
		.inputfield input{ width: 100%; margin-left: 10%;}
		.inputfield span{ margin-left: 10%;}
		.btnfield{ padding: 10px; margin-left: 10%;}
		.btnfield input{ margin-left: 2%; margin-top: 1.4%;}
		p{ margin-left: 10%;}
	</style>
	<script type="text/javascript">
	function validate(){
		var fromDate = document.getElementById('fromDate').value;
		var toDate = document.getElementById('toDate').value;
		if(fromDate == ''){
			document.getElementById('error_msg').innerHTML = 'Please enter from date';
			return false;
		}
		if(toDate == ''){
			document.getElementById('error_msg').innerHTML = 'Please enter to date';
			return false;	
		}
	}
</script>
</head>
<body>
<form action="" method="POST" onsubmit="return validate();">
	<div>
		<div class="inputfield"><span>From Date:</span><input id="fromDate" name="fromDate" type="text" value="<?php echo $previousDate;?>"></div>
		<div class="inputfield"><span>To Date:</span><input id="toDate" name="toDate" type="text" value="<?php echo $toDate;?>"></div>
		<div class="btnfield"><input type="submit" name="btnExport" value="Export"></div>
	</div>
</form>
<p id="error_msg"></p>
</body>
</html>
<?php 
	if(isset($_POST['btnExport'])){
		// echo '<pre>';print_r($_POST);exit;
		$fromDate = $_POST['fromDate'];	
		$toDateOnly = $_POST['toDate'];	
		$fromDate = date('Y-m-d 00:00:00A', strtotime($fromDate));
		$toDate = date('Y-m-d 23:59:59', strtotime($toDateOnly));

		$_orderCollection = Mage::getModel('sales/order')->getCollection()
		   ->addAttributeToSelect('*')
		   ->addFieldToFilter('status', 'complete')
		   ->addAttributeToFilter('created_at', array(
		    'from' => $fromDate,
		    'to' => $toDate
		    ))
		   ->addAttributeToSort('created_at', 'DESC')
		   ->load();

		$cnt = 1;
		$filename = 'order_report.csv';
		$file = fopen($filename,"w");
		fputcsv($file,array('Order Id','Customer Name','Ship to Address','Ship to city','Ship to zip','Ship to State','Ship to country','Date','Total Tax','Shipping Amount','Discount','Sub Total','Grand Total'));
		foreach ($_orderCollection as $order) {
			$orderId = $order->getRealOrderId();
			$customerName = $order->getCustomerName();
			$grandTotal = $order->getBaseGrandTotal();
			$shipping_address_data = $order->getShippingAddress()->getData();
			$shipToAddress = $shipping_address_data['street'];
			$shipToCity = $shipping_address_data['city'];
			$shipToZip = $shipping_address_data['postcode'];
			$shipToState = $shipping_address_data['region'];
			$shipToCountryID = $shipping_address_data['country_id'];
			$countryData = Mage::getModel('directory/country')->loadByCode($shipToCountryID);
			$shipToCountry = $countryData->getName();
			$totalTax = $order->getTaxAmount();
			$created_at = $order->getCreatedAt();
			$subTotal = $order->getBaseSubtotal();
			$shippingAmount = $order->getShippingAmount();
			$discount = $order->getDiscountAmount();
			fputcsv($file,array($orderId,$customerName,$shipToAddress,$shipToCity,$shipToZip,$shipToState,$shipToCountry,$created_at,$totalTax,$shippingAmount,$discount,$subTotal,$grandTotal));	
			$cnt++;
		}
		fclose($file);
		
		$file_url = 'order_report.csv';
		$base_url = Mage::getBaseUrl();
		header('Location:'.$base_url.$file_url);
		
	}



?>