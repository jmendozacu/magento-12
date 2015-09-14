<?php
ini_set('memory_limit', '1024M');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
require_once 'app/Mage.php';
umask(0);
Mage::app();
Mage::register('isSecureArea', true);
$customersArray = array();

$orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', 'complete')->addFieldToFilter('store_id',2);
foreach ($orders as $order) {
	$month = date("FY", strtotime($order->getCreatedAt()));	
	$email = '';
	if($order->getCustomerEmail() != ''){
		$email = $order->getCustomerEmail();	
	}
	if(isset($customersArray[$month][$email]['number_orders'])){
		$customersArray[$month][$email]['number_orders']++;	
	}else{
		$customersArray[$month][$email]['number_orders'] = 1;
	}
}

$customerArrayNew = array();
foreach ($customersArray as $key=>$values) {
	$customerArrayNew[$key] = array();

	$customerArrayNew[$key]['1order'] = 0;
	$customerArrayNew[$key]['2order'] = 0;
	$customerArrayNew[$key]['3plusorder'] = 0;

	//echo $key;exit;
	foreach ($values as $val) {
		if($val['number_orders'] == 1){
			$customerArrayNew[$key]['1order']++;
		}
		if($val['number_orders'] == 2){
			$customerArrayNew[$key]['2order']++;
		}
		if($val['number_orders'] > 2){
			$customerArrayNew[$key]['3plusorder']++;
		}
	}	
}

echo "<pre>"; print_r($customerArrayNew);
$fp = fopen("customers_orders.csv","w");
$array = array(
	'Month',
	'1 Order',
	'2 Orders',
	'3+ Orders',
);
$list = array($array);
foreach ($list as $fields) {
    fputcsv($fp, $fields);
}
foreach ($customerArrayNew as $key=>$value) {
	$prodArray = array(
		$key,
		$value['1order'],
		$value['2order'],
		$value['3plusorder'],		
	);
	//echo "<pre>"; print_r($prodArray);exit;
    $list = array($prodArray);
    foreach ($list as $fields) {
        fputcsv($fp, $fields);
    }
}
fclose($fp);
echo "Done";exit;
?>