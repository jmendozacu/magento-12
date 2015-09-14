<?php

/**
 * WDCA - Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the WDCA SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

 * The Open Software License is available at this URL: 
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * DISCLAIMER
 * 
 * By adding to, editing, or in any way modifying this code, WDCA is 
 * not held liable for any inconsistencies or abnormalities in the 
 * behaviour of this code. 
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by WDCA, outlined in the 
 * provided Sweet Tooth License. 
 * Upon discovery of modified code in the process of support, the Licensee 
 * is still held accountable for any and all billable time WDCA spent 
 * during the support process.
 * WDCA does not guarantee compatibility with any other framework extension. 
 * WDCA is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to 
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy 
 * immediately.
 * 
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Transfer
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Newsletter_Subscription_Transfer extends TBT_Rewards_Model_Transfer {
	
	public function _construct() {
		parent::_construct ();
	}
	
	/**
	 * 
	 * @param int $id
	 */
	public function setNewsletterId($id) {
		$this->clearReferences ();
		$this->setReferenceType ( TBT_Rewards_Model_Newsletter_Subscription_Reference::REFERENCE_TYPE_ID );
		$this->setReferenceId ( $id );
		$this->_data ['newsletter_id'] = $id;
		
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function isNewsletter() {
		return ($this->getReferenceType () == TBT_Rewards_Model_Newsletter_Subscription_Reference::REFERENCE_TYPE_ID) || isset ( $this->_data ['newsletter_id'] );
	}
	
	/**
	 * 
	 * Gets all transfers associated with the given newsletter ID
	 * @param int $newsletter_id
	 */
	public function getTransfersAssociatedWithNewsletter($newsletter_id) {
		return $this->getCollection ()->addFilter ( 'reference_type', TBT_Rewards_Model_Newsletter_Subscription_Reference::REFERENCE_TYPE_ID )->addFilter ( 'reference_id', $newsletter_id );
	}
	
	/**
	 * Fetches the transfer helper
	 *
	 * @return TBT_Rewards_Helper_Transfer
	 */
	protected function _getTransferHelper() {
		return Mage::helper ( 'rewards/transfer' );
	}
	
	/**
	 * Fetches the rewards special validator singleton
	 *
	 * @return TBT_Rewards_Model_Special_Validator
	 */
	protected function _getSpecialValidator() {
		return Mage::getSingleton ( 'rewards/special_validator' );
	}
	
	/**
	 * Fetches the rewards special validator singleton
	 *
	 * @return TBT_Rewards_Model_Special_Validator
	 */
	protected function _getNewsletterValidator() {
		return Mage::getSingleton ( 'rewards/newsletter_validator' );
	}
	
	/**
	 * Creates a customer point-transfer of any amount or currency.
	 *
	 * @param  $rule    : Special Rule
	 * @return boolean            : whether or not the point-transfer succeeded
	 */
	public function createNewsletterSubscriptionPoints(TBT_Rewards_Model_Newsletter_Subscriber_Wrapper $rsubscriber, $rule) {
		
		$num_points = $rule->getPointsAmount ();
		$currency_id = $rule->getPointsCurrencyId ();
		$rule_id = $rule->getId ();
		$transfer = $this->initTransfer ( $num_points, $currency_id, $rule_id );
		$customer = Mage::getModel('rewards/customer')->getRewardsCustomer($rsubscriber->getCustomer ());
		$store_id = $customer->getStore ()->getId ();
		
		if (! $transfer) {
			return false;
		}
		
		// get the default starting status - usually Pending
		$initial_status = Mage::helper ( 'rewards/newsletter_config' )->getInitialTransferStatusAfterNewsletter ( $store_id );
		
		if (! $transfer->setStatus ( null, $initial_status )) {
			// we tried to use an invalid status... is getInitialTransferStatusAfterReview() improper ??
			return false;
		}
		
		// Translate the message through the core translation engine (nto the store view system) in case people want to use that instead
		// This is not normal, but we found that a lot of people preferred to use the standard translation system insteaed of the 
		// store view system so this lets them use both.
		$initial_transfer_msg = Mage::getStoreConfig ( 'rewards/transferComments/newsletterEarned', $store_id );
		$comments = Mage::helper ( 'rewards' )->__ ( $initial_transfer_msg );
		
		$customer_id = $rsubscriber->getCustomer ()->getId ();
		
		$this->setNewsletterId ( $rsubscriber->getNewsletterId () )->setComments ( $comments )->setCustomerId ( $customer_id )->save ();
		
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
                            foreach ($resultRows as $result) {
                                if($result['erply_transaction_id'] == 0 || $result['erply_transaction_id'] == ''){
                                    $rewards_transfer_id  = $result['rewards_transfer_id'];
                                    
                                    if($result['quantity'] > 0){
                                        $params = array(
                                            'customerID'    => $customer->getErplyCustomerId(),
                                            'points' => $result['quantity'],
                                            'description' => 'Magento Transaction: '. $result['comments'],
                                        );
                                        $responseJson = $erplyModel->sendRequest('addCustomerRewardPoints', $params);                
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



                                    if($result['quantity'] < 0){
                                        $params = array(
                                            'customerID'    => $customer->getErplyCustomerId(),
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

		return true;
	}

}