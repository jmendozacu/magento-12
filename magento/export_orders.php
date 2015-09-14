<?php
require_once('app/Mage.php');
Mage::app();
$store_id = 2;
$orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', 'complete')->addFieldToFilter('store_id',$store_id)->addAttributeToSelect('*');

$fp = fopen("thevapestore_orders.csv","w");

$allOrders = array();

$orderCnt = 0;
foreach ($orders->getData() as $orderdata) {	
	
	$order = Mage::getModel('sales/order')->load($orderdata['entity_id']);

	$billing_address = $order->getBillingAddress();
	$shipping_address = $order->getShippingAddress();
	
	if($billing_address['region'] == 'California'){

		$orderInfo = array();
		$orderInfo['id'] = $order->getEntityId();
		$orderInfo['increment_id'] = $order->getIncrementId();
		$orderInfo['status'] = $order->getStatus();
		$orderInfo['shipping_description'] = $order->getShippingDescription();
		$orderInfo['shipping_method'] = $order->getShippingMethod();

		$orderInfo['billing_firstname'] = $billing_address['firstname'];
		$orderInfo['billing_lastname'] = $billing_address['lastname'];
		$orderInfo['billing_email'] = $billing_address['email'];
		$orderInfo['billing_company'] = $billing_address['company'];
		$orderInfo['billing_address'] = $billing_address['street'];
		$orderInfo['billing_city'] = $billing_address['city'];
		$orderInfo['billing_state'] = $billing_address['region'];
		$orderInfo['billing_zip'] = $billing_address['postcode'];
		$orderInfo['billing_country'] = $billing_address['country_id'];

		$orderInfo['shipping_firstname'] = $shipping_address['firstname'];
		$orderInfo['shipping_lastname'] = $shipping_address['lastname'];
		$orderInfo['shipping_email'] = $shipping_address['email'];
		$orderInfo['shipping_company'] = $shipping_address['company'];
		$orderInfo['shipping_address'] = $shipping_address['street'];
		$orderInfo['shipping_city'] = $shipping_address['city'];
		$orderInfo['shipping_state'] = $shipping_address['region'];
		$orderInfo['shipping_zip'] = $shipping_address['postcode'];
		$orderInfo['shipping_country'] = $shipping_address['country_id'];
		$payment = $order->getPayment();
		$paymentInfo = $order->getPayment()->getData();
		$orderInfo['payment_method'] = $payment->getMethodInstance()->getTitle();

		$isPaymentCC = 0;
		if($paymentInfo['cc_type'] != ''){
			if($paymentInfo['cc_type'] == 'VI'){
				$orderInfo['payment_cc_type'] = 'Visa';
			}elseif($paymentInfo['cc_type'] == 'MC'){
				$orderInfo['payment_cc_type'] = 'MasterCard';
			}elseif($paymentInfo['cc_type'] == 'AE'){
				$orderInfo['payment_cc_type'] = 'AmericanExpress';
			}elseif($paymentInfo['cc_type'] == 'DI'){
				$orderInfo['payment_cc_type'] = 'Discover';
			}else{
				$orderInfo['payment_cc_type'] = $paymentInfo['cc_type'];	
			}
			$orderInfo['payment_cc_last4'] = 'XXXX-'.$paymentInfo['cc_last4'];
			$orderInfo['payment_cc_exp_month'] = $paymentInfo['cc_exp_month'];
			$orderInfo['payment_cc_exp_year'] = $paymentInfo['cc_exp_year'];
			$isPaymentCC = 1;
		}else{
			if(isset($paymentInfo['additional_information']['authorize_cards'])){
				foreach ($paymentInfo['additional_information']['authorize_cards'] as $info) {
					if($info['cc_type'] == 'VI'){
						$orderInfo['payment_cc_type'] = 'Visa';	
					}elseif($info['cc_type'] == 'MC'){
						$orderInfo['payment_cc_type'] = 'MasterCard';	
					}else{
						$orderInfo['payment_cc_type'] = $info['cc_type'];	
					}
					$orderInfo['payment_cc_last4'] = 'XXXX-'.$info['cc_last4'];
					$orderInfo['payment_cc_exp_month'] = $info['cc_exp_month'];
					$orderInfo['payment_cc_exp_year'] = $info['cc_exp_year'];
					$isPaymentCC = 1;
				}	
			}	
		}
		if(!$isPaymentCC){
			$orderInfo['payment_cc_type'] = '';
			$orderInfo['payment_cc_last4'] = '';
			$orderInfo['payment_cc_exp_month'] = '';
			$orderInfo['payment_cc_exp_year'] = '';
		}

		

		$orderInfo['subtotal'] = $order->getSubtotal();
		$orderInfo['tax_amount'] = $order->getTaxAmount();
		$orderInfo['shipping_amount'] = $order->getShippingAmount();		
		$orderInfo['discount_amount'] = $order->getDiscountAmount();
		$orderInfo['grand_total'] = $order->getGrandTotal();

		$orderInfo['created_at'] = $order->getCreatedAt();
		$orderInfo['updated_at'] = $order->getUpdatedAt();


		$columnTitle = array_keys($orderInfo);
		
		$cntColumns = count($columnTitle);
		$blankOrderInfo = array();
		foreach ($columnTitle as $value) {
			$blankOrderInfo[$value] = '';
		}

		$orderItems = array();

		$ordered_items = $order->getAllVisibleItems();
		
		foreach($ordered_items as $item){
			//echo "<pre>"; print_r($item->getData());
			
			// if($item->getProductType() == 'configurable'){
			// 	$itemTmp = array();
   //              $itemTmp['sku'] = $item->getSku();
   //              $itemTmp['price'] = $item->getPrice();
   //          }elseif($item->getProductType() != 'configurable' && $item->getQtyShipped() != '0.0000'){
   //              $itemTmp['qty'] = $item->getQtyShipped();
   //              $itemTmp['name'] = $item->getName();				
			// 	$orderItems[] = $itemTmp;
   //          }elseif($item->getProductType() != 'configurable'){
   //          	$itemTmp = array();
   //      		$itemTmp['sku'] = $item->getSku();
   //          	$itemTmp['price'] = $item->getPrice();
   //          	$itemTmp['qty'] = $item->getQtyShipped();
   //          	$itemTmp['name'] = $item->getName();
			// 	$orderItems[] = $itemTmp;
   //          }

			$itemTmp = array();
    		$itemTmp['sku'] = $item->getSku();
        	$itemTmp['price'] = $item->getPrice();
        	$itemTmp['qty'] = $item->getQtyShipped();
        	$itemTmp['name'] = $item->getName();
			$orderItems[] = $itemTmp;

		}

		
		$orderInfoNew = array();
		for($i=0;$i<count($orderItems);$i++){
			if(!$i){
				$orderInfoNew = array_merge($orderInfo,$orderItems[$i]);
			}else{
				$orderInfoNew = array_merge($blankOrderInfo,$orderItems[$i]);
			}

			$columnTitle = array_keys($orderInfoNew);

			if(!$orderCnt){			
				fputcsv($fp, $columnTitle);	
			}

			fputcsv($fp, $orderInfoNew);
		}
		
		//fputcsv($fp, $orderInfo);

		echo "<pre>"; print_r($orderInfoNew);
		$orderCnt++;
	}
}
echo "Done";exit;