<?php
include_once(Mage::getModuleDir('controllers', 'TBT_Rewards') . DS . 'Front' . DS . 'AbstractController.php');
class TBT_Rewards_CustomerController extends TBT_Rewards_Front_AbstractController {
	
	public function indexAction() {

		// Erply Magento Sync Start

		if (Mage::getSingleton('customer/session')->isLoggedIn()) {

		
			$process = Mage::getModel('index/indexer')->getProcessByCode('rewards_transfer')->reindexAll();
			
		    $storeId = 2;
		    $erplyModel = Mage::getModel('Erply/Erply');

		    if($erplyModel->verifyUser($storeId)){
		        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');    

		        $customer = Mage::getSingleton('customer/session')->getCustomer();
		        $customerAddressId = $customer->getDefaultBilling();
		        $phone = '';
		        if($customerAddressId){
		            $address = Mage::getModel('customer/address')->load($customerAddressId);
		            $phone = $address->getBillingTelephone();
		        }
		        $customerId = $customer->getId();
		        $erplyCustomerId = $customer->getErplyCustomerId();
		        $email = $customer->getEmail();        
		        $newCustomer = false;

		        if($erplyCustomerId =='' || $erplyCustomerId == 0){

		            if($email != ''){
		                $params = array(
		                    'searchName' => $email,
		                );
		                $responseJson = $erplyModel->sendRequest('getCustomers', $params);
		                $response = json_decode($responseJson, true);
		                if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
		                    Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                    break;
		                }else{
		                    if(!isset($response['records']) || !count($response['records'])){
		                        if($phone != ''){
		                            $params = array(
		                                'searchName'    => $phone,
		                            );
		                            $responseJson = $erplyModel->sendRequest('getCustomers', $params);
		                            $response = json_decode($responseJson, true);
		                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
		                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                                break;
		                            }else{
		                                if(isset($response['records'])){
		                                    if(!count($response['records'])){
		                                        $newCustomer = true; 
		                                    }else{
		                                        if(count($response['records']) > 1){
		                                            $newCustomer = true; 
		                                        }else{
		                                            if(isset($response['records'][0]['customerID'])){
		                                                $customerID = $response['records'][0]['customerID'];
		                                                $customer->setErplyCustomerId($customerID);
		                                                $customer->save();
		                                                $erplyCustomerId = $customerID;
		                                            }
		                                            $newCustomer = false; 
		                                        }                           
		                                    }
		                                }else{
		                                    $newCustomer = true; 
		                                }    
		                            }                            
		                        }else{
		                            $newCustomer = true; 
		                        }
		                    }else{
		                        if(isset($response['records'][0]['customerID'])){
		                            $customerID = $response['records'][0]['customerID'];
		                            $customer->setErplyCustomerId($customerID);
		                            $customer->save();
		                            $erplyCustomerId = $customerID;
		                        }                        
		                        $newCustomer = false; 
		                    }
		                }                    
		            }

		            if($newCustomer){
		                $params = array(
		                    'firstName'    => $customer->getFirstname(),
		                    'lastName'    => $customer->getLastname(),
		                    'email'    => $customer->getEmail(),
		                    'phone'    => $customer->getBillingTelephone(),
		                );
		                $responseJson = $erplyModel->sendRequest('saveCustomer', $params);
		                $response = json_decode($responseJson, true);
		                if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
		                    Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                    break;
		                }else{
		                    if(isset($response['records'][0]['customerID'])){
		                        $customerID = $response['records'][0]['customerID'];
		                        $customer->setErplyCustomerId($customerID);
		                        $customer->save();
		                        $erplyCustomerId = $customerID;
		                    }
		                }              
		            }else{
		                //echo "customer already exist";
		            }

		        }else{
		            
		        }

		        if($customer->getErplyCustomerId() != '' && $customer->getErplyCustomerId() != 0){

		            $params = array(
		                'customerID' => $erplyCustomerId,
		            );
		            $responseJson = $erplyModel->sendRequest('getCustomerRewardPoints', $params);
		            $response = json_decode($responseJson, true);

		            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
		                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                break;
		            }else{
		                
		                $erplyPoints = 0;
		                if(isset($response['records'][0]['points'])){
		                    $erplyPoints = $response['records'][0]['points'];
		                }
		                $sql = "SELECT * FROM rewards_customer_index_points WHERE customer_id = ".$customer->getId();
		                $rows = $readConnection->fetchAll($sql);
		                $magentoPoints = 0;
		                if(isset($rows[0]['customer_points_usable'])){
		                    $magentoPoints = $rows[0]['customer_points_usable'];
		                }else{
		                    $sqlInsert = "INSERT INTO rewards_customer_index_points ( customer_id,customer_points_usable,customer_points_pending_event,customer_points_pending_time,customer_points_pending_approval,customer_points_active ) VALUES ('".$customerId."',0,0,0,0,0) ";
		                    $writeConnection->query($sqlInsert);
		                }

		                // $params = array(
	                 //        'customerID' => $erplyCustomerId,
	                 //    );
	                 //    $responseJson = $erplyModel->sendRequest('getEarnedRewardPointRecords', $params);
	                 //    $response = json_decode($responseJson, true);
	                 //    echo "<pre>"; print_r($response);exit;

		                Mage::log('magentoPoints:'.$magentoPoints,null,'debug.log');
		                Mage::log('erplyPoints:'.$erplyPoints,null,'debug.log');
		                if($magentoPoints != $erplyPoints){

		                    // Reward Points Erply >To> Magento
		                    $params = array(
		                        'customerID' => $erplyCustomerId,
		                    );
		                    $responseJson = $erplyModel->sendRequest('getEarnedRewardPointRecords', $params);
		                    $response = json_decode($responseJson, true);
		                    
		                    if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
		                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                        break;
		                    }else{
		                        if(isset($response['records'])){
		                            foreach ($response['records'] as $records) {
		                                $sqlSelect = "SELECT erply_transaction_id FROM rewards_transfer WHERE erply_transaction_id = '".$records['transactionID']."'";
		                                $resultRows = $readConnection->fetchAll($sqlSelect);            
		                                if(!count($resultRows)){
		                                    $invoiceNo = $records['invoiceNo'];
		                                    $comments = '';
		                                    if($invoiceNo != ''){
		                                        $comments = 'Erply Invoice # $invoiceNo - Order Received - Point Earned';
		                                    }else{
		                                        $comments = 'Erply Points Earned';
		                                    }
		                                    $earnedPoints = $records['earnedPoints'];
		                                    $transactionID = $records['transactionID'];
		                                    $currentDate = date('Y-m-d h:i:s');

		                                    if($invoiceNo =='' || empty(Mage::getModel('sales/order')->loadByIncrementId($invoiceNo)->getData())){

		                                        $sqlInsert = "INSERT INTO rewards_transfer 
		                                        (customer_id, quantity, comments, status, currency_id, erply_transaction_id , creation_ts , last_update_ts)
		                                        VALUES 
		                                        ('$customerId', '$earnedPoints','$comments', 5,1,'$transactionID', '$currentDate', '$currentDate'); commit;";
		                                        $writeConnection->query($sqlInsert);

		                                        $customer_points_usable = $magentoPoints + $earnedPoints;
		                                        $sqlUpdate = "UPDATE rewards_customer_index_points SET customer_points_usable = '$customer_points_usable', customer_points_active = '$customer_points_usable'  WHERE customer_id = ".$customer->getId();
		                                        $writeConnection->query($sqlUpdate);
		                                    }
		                                }
		                            }
		                        }
		                    }

		                    $params = array(
		                        'customerID' => $erplyCustomerId,
		                    );
		                    $responseJson = $erplyModel->sendRequest('getUsedRewardPointRecords', $params);
		                    $response = json_decode($responseJson, true);

		                    if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
		                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                        break;
		                    }else{
		                        if(isset($response['records'])){
		                            foreach ($response['records'] as $records) {
		                                $sqlSelect = "SELECT erply_transaction_id FROM rewards_transfer WHERE erply_transaction_id = '".$records['transactionID']."'";
		                                $resultRows = $readConnection->fetchAll($sqlSelect);		                                
		                                if(!count($resultRows)){
		                                    $invoiceNo = $records['invoiceNo'];
		                                    $comments = '';
		                                    if($invoiceNo != ''){                                        
		                                        $comments = 'Erply Invoice # $invoiceNo - Order Received - Point Spent';
		                                    }else{
		                                        $comments = 'Erply Points Spent';
		                                    }
		                                    $usedPoints = $records['usedPoints'] * -1;
		                                    $transactionID = $records['transactionID'];
		                                    $currentDate = date('Y-m-d h:i:s');
		                                    if($invoiceNo =='' || empty(Mage::getModel('sales/order')->loadByIncrementId($invoiceNo)->getData())){
		                                        $sqlInsert = "INSERT INTO rewards_transfer 
		                                        (customer_id, quantity, comments, status, currency_id, erply_transaction_id , creation_ts , last_update_ts)
		                                        VALUES 
		                                        ('$customerId', '$usedPoints','$comments', 5,1,'$transactionID', '$currentDate', '$currentDate'); commit;";
		                                        $writeConnection->query($sqlInsert);

		                                        $customer_points_usable = $magentoPoints + $usedPoints;
		                                        $sqlUpdate = "UPDATE rewards_customer_index_points SET customer_points_usable = '$customer_points_usable' , customer_points_active = '$customer_points_usable'  WHERE customer_id = ".$customer->getId();
		                                        $writeConnection->query($sqlUpdate);
		                                    }
		                                }
		                            }
		                        }
		                    }


		                    // Reward Points Magento >To> Erply

		                    


		                    $sqlSelect = "SELECT * FROM rewards_transfer WHERE customer_id = '".$customerId."' AND erply_transaction_id = 0 AND status = 5";
		                    $resultRows = $readConnection->fetchAll($sqlSelect);
		                    Mage::log('resultRows',null,'debug.log');
		                    Mage::log($resultRows,null,'debug.log');
		                    foreach ($resultRows as $result) {
		                        if($result['erply_transaction_id'] == 0 || $result['erply_transaction_id'] == ''){
		                            $rewards_transfer_id  = $result['rewards_transfer_id'];
		                            Mage::log('rewards_transfer_id:'.$rewards_transfer_id,null,'debug.log');
		                            if($result['quantity'] > 0){
		                                $params = array(
		                                    'customerID'    => $erplyCustomerId,
		                                    'points' => $result['quantity'],
		                                    'description' => 'Magento Transaction: '. $result['comments'],
		                                );
		                                $responseJson = $erplyModel->sendRequest('addCustomerRewardPoints', $params);                
		                                $response = json_decode($responseJson, true);
		                                Mage::log('response',null,'debug.log');
		                                Mage::log($response,null,'debug.log');

		                                if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
		                                    Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                                    break;
		                                }else{
		                                    if(isset($response['records'][0]['transactionID'])){
		                                        $transactionID = $response['records'][0]['transactionID'];
		                                        $sqlUpdate = "UPDATE rewards_transfer SET erply_transaction_id = '$transactionID' WHERE rewards_transfer_id = ".$rewards_transfer_id;
		                                        $writeConnection->query($sqlUpdate);
		                                    }
		                                }                                
		                            }



		                            if($result['quantity'] < 0){
		                                $params = array(
		                                    'customerID'    => $erplyCustomerId,
		                                    'points' => $result['quantity'] * -1,
		                                    'description' => 'Magento Transaction: '. $result['comments'],
		                                );
		                                $responseJson = $erplyModel->sendRequest('subtractCustomerRewardPoints', $params);
		                                $response = json_decode($responseJson, true);
		                                if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
		                                    Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                                    break;
		                                }else{
		                                    if(isset($response['records'][0]['transactionID'])){
		                                        $transactionID = $response['records'][0]['transactionID'];
		                                        $sqlUpdate = "UPDATE rewards_transfer SET erply_transaction_id = '$transactionID' WHERE rewards_transfer_id = ".$rewards_transfer_id;
		                                        $writeConnection->query($sqlUpdate);
		                                    }
		                                }                                       
		                            }



		                        }
		                    }

		                    $params = array(
		                        'customerID' => $erplyCustomerId,
		                    );
		                    $responseJson = $erplyModel->sendRequest('getCustomerRewardPoints', $params);
		                    $response = json_decode($responseJson, true); 
		                

		                    if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
		                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                        break;
		                    }else{
		                        

		                        $erplyPoints = 0;
		                        if(isset($response['records'][0]['points'])){
		                            $erplyPoints = $response['records'][0]['points'];
		                        }
		                        
		                        $sql = "SELECT * FROM rewards_customer_index_points WHERE customer_id = ".$customer->getId();
		                        $rows = $readConnection->fetchAll($sql);
		                        $magentoPoints = 0;
		                        if(isset($rows[0]['customer_points_usable'])){
		                            $magentoPoints = $rows[0]['customer_points_usable'];
		                        }else{
		                            $sqlInsert = "INSERT INTO rewards_customer_index_points ( customer_id,customer_points_usable,customer_points_pending_event,customer_points_pending_time,customer_points_pending_approval,customer_points_active ) VALUES ('".$customerId."',0,0,0,0,0) ";
		                            $writeConnection->query($sqlInsert);
		                        }


		                        if($magentoPoints != $erplyPoints){
		                            // if($magentoPoints > $erplyPoints){
		                            //     $diff = $magentoPoints - $erplyPoints;
		                            //     $params = array(
		                            //         'customerID'    => $customer->getErplyCustomerId(),
		                            //         'points' => $diff,
		                            //         'description' => 'Magento - Erply Point Difference Adjustment',
		                            //     );
		                            //     $responseJson = $erplyModel->sendRequest('addCustomerRewardPoints', $params);                
		                            //     $response = json_decode($responseJson, true);
		                            //     if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
		                            //         Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
		                            //         break;
		                            //     }else{
		                            //         // Transfer Done
		                            //     }
		                            // }

		                            // if($magentoPoints < $erplyPoints){

		                            //     $diff = $erplyPoints - $magentoPoints;
		                            //     $currentDate = date('Y-m-d h:i:s');
		                                
		                            //     $sqlInsert = "INSERT INTO rewards_transfer 
		                            //     (customer_id, quantity, comments, status, currency_id, erply_transaction_id , creation_ts , last_update_ts)
		                            //     VALUES 
		                            //     ('$customerId', '$diff','Erply - Magento Point Different Adjustment', 5,1,1, '$currentDate', '$currentDate'); commit;";
		                            //     $writeConnection->query($sqlInsert);

		                            //     $customer_points_usable = $magentoPoints + $diff;
		                            //     $sqlUpdate = "UPDATE rewards_customer_index_points SET customer_points_usable = '$customer_points_usable', customer_points_active = '$customer_points_usable'   WHERE customer_id = ".$customer->getId();
		                            //     $writeConnection->query($sqlUpdate);
		                            // }
		                        }


		                    }
		                        
		                }
		            }
		        }
		    }
		}

		// Erply Magento Sync End








		$this->loadLayout ();
		
		$customer = Mage::getSingleton ( 'rewards/session' )->getSessionCustomer ();
		
		Mage::register ( 'customer', $customer );
		
		$this->renderLayout ();
	}
	
	public function preDispatch() {
		
		parent::preDispatch ();
		
		if (! Mage::getSingleton ( 'customer/session' )->authenticate ( $this )) {
			$this->setFlag ( '', 'no-dispatch', true );
		}
		if (! Mage::helper ( 'rewards/config' )->getIsCustomerRewardsActive ()) {
			$this->norouteAction ();
			return;
		}
	}

}