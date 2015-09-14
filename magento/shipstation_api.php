<?php 
require_once 'app/Mage.php';
ini_set('memory_limit', '1024M');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
umask(0);
Mage::app();
Mage::register('isSecureArea', true);

$processingOrders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', Mage_Sales_Model_Order::STATE_PROCESSING);
foreach ($processingOrders as $order) {
	$isDropShipOrder = false;
	$items = $order->getAllVisibleItems();
	foreach($items as $item){		
		$sku = $item->getSku();
		$prefix = substr($sku,0,3); // 'DS-' for DropShip Products
		if($prefix == 'DS-'){
			$isDropShipOrder = true;
			//echo $sku.'<br>';
		}
	}
	if($isDropShipOrder){
		$orderIncrementId = $order->getIncrementId();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://ssapi.shipstation.com/orders?orderNumber=".$orderIncrementId);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Content-Type: application/json",
		  "Authorization: ".'Basic '.base64_encode('9831033f9ba54666a0911f5abb29a52a:cc14745fd57a4ba8b24ae4132a963f3f')
		));
		$responseJson = curl_exec($ch);
		curl_close($ch);
		$response = json_decode($responseJson);
		//echo "<pre>"; print_r($response);
		if(isset($response->orders[0]->orderId) && $response->orders[0]->orderId != ''){
			$orderId = $response->orders[0]->orderId;
			$tagId = 22624;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://ssapi.shipstation.com/orders/addtag");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			$requestJson = json_encode(array('orderId'=>$orderId,'tagId'=>$tagId));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			  "Content-Type: application/json",
			  "Authorization: ".'Basic '.base64_encode('9831033f9ba54666a0911f5abb29a52a:cc14745fd57a4ba8b24ae4132a963f3f')
			));
			$responseJson = curl_exec($ch);
			curl_close($ch);
			$response = json_decode($responseJson);
			//echo "<pre>"; print_r($response);
			Mage::log('DropShip Order Id: '.$orderIncrementId,null,'shipstation_tag.log');
			Mage::log('DropShip Tag : '.$response->message,null,'shipstation_tag.log');
			//exit;
		}
	}
}
echo "Done";
?>