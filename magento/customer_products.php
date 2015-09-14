<?php
ini_set('memory_limit', '1024M');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
require_once 'app/Mage.php';
umask(0);
Mage::app();
Mage::register('isSecureArea', true);
$customersArray = array();

$orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', 'complete');
foreach ($orders as $order) {
	$email = ''; $telephone = ''; $firstname = ''; $lastname = '';
	if($order->getCustomerEmail() != ''){
		$email = $order->getCustomerEmail();	
	}
	if($order->getCustomerFirstname() != ''){
		$firstname = $order->getCustomerFirstname();	
	}
	if($order->getCustomerLastname() != ''){
		$lastname = $order->getCustomerLastname();	
	}
	
	$billingAddress = $order->getBillingAddress();
	if($billingAddress->getEmail() != ''){
		$email = $billingAddress->getEmail();
	}
	if($billingAddress->getTelephone() != ''){
		$telephone = $billingAddress->getTelephone();
	}
	if($billingAddress->getFirstname() != ''){
		$firstname = $billingAddress->getFirstname();
	}
	if($billingAddress->getLastname() != ''){
		$lastname = $billingAddress->getLastname();
	}
	if(!isset($customersArray[$email])){
		$customersArray[$email] = array();	
	}

	$customersArray[$email]['email'] = $email;
	$customersArray[$email]['telephone'] = $telephone;
	$customersArray[$email]['firstname'] = $firstname;
	$customersArray[$email]['lastname'] = $lastname;

	foreach($order->getAllItems() as $item){
		if(!in_array($item->getName(), $customersArray[$email]['products'])){
			$customersArray[$email]['products'][] = $item->getName();
		}
	}
}


$fp = fopen("customers_products.csv","w");
foreach ($customersArray as $data) {
	echo "<pre>"; print_r($data);
	$i = 0;
	foreach ($data['products'] as $product) {
		$prodArray = array();
		if($i == 0){
			$prodArray = array(
				$data['firstname'],
				$data['lastname'],
				$data['email'],
				$data['telephone'],
				$product
			);	
		}else{
			$prodArray = array(
				'',
				'',
				'',
				'',
				$product
			);	
		}		
		//$prodArray = array_merge($prodArray,$data['products']);
		//echo "<pre>"; print_r($prodArray);exit;
	    $list = array($prodArray);
	    foreach ($list as $fields) {
	        fputcsv($fp, $fields);
	    }	
	    $i++;
	}
}

fclose($fp);
echo "Done";exit;
//echo "<pre>"; print_r($customersArray);exit;
?>