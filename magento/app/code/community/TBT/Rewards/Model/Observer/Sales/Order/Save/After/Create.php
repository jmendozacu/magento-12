<?php

/**
 * WDCA - Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the WDCA SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
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
 * After order save observer. This class handles much of the catalog and cart points transfers
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Observer_Sales_Order_Save_After_Create
{
    /**
     * Creates the catalog and cart points transfers needed as a result of an order.
     *
     * @param   Varien_Event_Observer $observer
     * @return  TBT_Rewards_Model_Observer_Sales_Order_Save_After_Create
     */
    public function createPointsTransfers($observer)
    {
        $event        = $observer->getEvent ();
        $order        = $event->getOrder ();
        $skipDispatch = false;

        // this event is fired twice, make sure we run code only once
        if ($event->getName() == 'sales_order_save_commit_after' && Mage::registry('rewards_createPointsTransfers_run')) {
            return $this;
        }

        // Check if the method is triggered from
        // Mage::dispatchEvent('sales_order_payment_place_start', array('payment' => $this));
        if ( $event->getPayment() instanceof Mage_Sales_Model_Order_Payment ) {
            // skip this if we are in admin, because we do this on 'sales_order_save_commit_after' event (only needed
            // for Authorize.net on front-end)
            if ($this->_isAdmin()) {
                return $this;
            }
            $skipDispatch = true;
            $order = $event->getPayment()->getOrder();
        }


        if (! $order) {
            return $this;
        }

        if (!$skipDispatch) {
            Mage::dispatchEvent( 'rewards_order_points_transfer_before_create', array (
                    'order' => $order,
                    'catalog_transfers' => $this->_getCatalogTransfers(),
                    'cart_transfers' => $this->_getCartTransfers()
            ));
        }

        $this->_processCatalogTransfers($order);

        $this->_processCartTransfers($order);

        if (  $this->_doDisplayPendingMsgs($order)  ) {
            $this->_processPendingMsgs($order);
        }

        if (!$skipDispatch) {
            Mage::dispatchEvent( 'rewards_order_points_transfer_after_create', array (
                'order' => $order,
                'catalog_transfers' => $this->_getCatalogTransfers(),
                'cart_transfers' => $this->_getCartTransfers()
            ));
        }

        if ($event->getName() == 'sales_order_save_commit_after') {
            Mage::register('rewards_createPointsTransfers_run', true);
        }

        $this->_cleanUpTempData($order);

        /* Magento >> Erply */
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



        return $this;
    }

    /**
     * Cleans up temporary data, cart points usage data and memory notes to process the points.
     */
    protected function _cleanUpTempData($order)
    {
        $catalog_transfers = $this->_getCatalogTransfers();

        // Clear memory of points redeemed transfers and order
        $catalog_transfers->clearRedeemedPoints ();
        $catalog_transfers->clearIncrementId ();


        $cart_transfers = $this->_getCartTransfers();

        //@nelkaake Monday March 29, 2010 03:55:05 AM : Reset points spending
        //@mhadianfard: to prevent race condition when observer is called more than once, do a check before clearing
        if ($order->getIncrementId () == $cart_transfers->getIncrementId ()) {
            Mage::getSingleton ( 'rewards/session' )->setPointsSpending ( 0 );
        }


        // Clear memory of points transfers and order
        $cart_transfers->clearIncrementId ();
        $cart_transfers->clearCartPoints ();

        return $this;
    }

    /**
     * Process catalog rules
     * @param TBT_Rewards_Model_Sales_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _processCatalogTransfers($order)
    {
        $catalog_transfers = Mage::getSingleton ( 'rewards/observer_sales_catalogtransfers' );

        if ($order->getIncrementId () != $catalog_transfers->getIncrementId ()) {
            return $this;
        }

        $this->_processCatalogEarnedPoints($catalog_transfers, $order);

        // Clear memory of points earned transfers
        $catalog_transfers->clearEarnedPoints ();

        $this->_processCatalogRedeemedPoints($catalog_transfers, $order);

        return $this;

    }

    /**
     * Process shopping cart rules
     * @param TBT_Rewards_Model_Sales_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _processCartTransfers($order)
    {
        $cart_transfers = Mage::getSingleton ( 'rewards/observer_sales_carttransfers' );

        if ($order->getIncrementId () != $cart_transfers->getIncrementId ()) {
            return $this;
        }

        $this->_processAllCartPoints($cart_transfers, $order);

        $this->_processCartRedemptionRules($cart_transfers, $order);

        return $this;
    }

    /**
     *
     * @param TBT_Rewards_Model_Observer_Sales_Catalogtransfers $catalog_transfers
     * @param TBT_Rewards_Model_Sales_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _processCatalogEarnedPoints($catalog_transfers, $order)
    {
        foreach ( $catalog_transfers->getAllEarnedPoints () as $earned_point_totals ) {
            if (! $earned_point_totals) {
                continue;
            }

            foreach ( $earned_point_totals as $transfer_points ) {
                $transfer_points = ( array ) $transfer_points;
                try {
                    $is_transfer_successful = Mage::helper ( 'rewards/transfer' )->transferOrderPoints (
                        $transfer_points [TBT_Rewards_Model_Catalogrule_Rule::POINTS_AMT] * $transfer_points [TBT_Rewards_Model_Catalogrule_Rule::POINTS_APPLICABLE_QTY],
                        $transfer_points [TBT_Rewards_Model_Catalogrule_Rule::POINTS_CURRENCY_ID],
                        $order->getId (),
                        $transfer_points [TBT_Rewards_Model_Catalogrule_Rule::POINTS_RULE_ID] );
                } catch ( Exception $ex ) {
                    throw new Mage_Core_Exception ( $ex->getMessage () );
                }

            }
        }

        return $this;
    }

    /**
     *
     * @param TBT_Rewards_Model_Observer_Sales_Catalogtransfers $catalog_transfers
     * @param TBT_Rewards_Model_Sales_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _processCatalogRedeemedPoints($catalog_transfers, $order)
    {

        foreach ( $catalog_transfers->getAllRedeemedPoints () as $redeemed_point_totals ) {
            if (! $redeemed_point_totals) {
                continue;
            }

            foreach ( $redeemed_point_totals as $transfer_points ) {
                $transfer_points = ( array ) $transfer_points;
                try {
                    $is_transfer_successful = Mage::helper ( 'rewards/transfer' )->transferOrderPoints (
                        $transfer_points [TBT_Rewards_Model_Catalogrule_Rule::POINTS_AMT] * $transfer_points [TBT_Rewards_Model_Catalogrule_Rule::POINTS_APPLICABLE_QTY] * - 1,
                        $transfer_points [TBT_Rewards_Model_Catalogrule_Rule::POINTS_CURRENCY_ID],
                        $order->getId (),
                        $transfer_points [TBT_Rewards_Model_Catalogrule_Rule::POINTS_RULE_ID]
                    );
                } catch ( Exception $ex ) {
                    throw new Mage_Core_Exception ( $ex->getMessage () );
                }
            }
        }

        return $this;
    }

    /**
     *
     * @param TBT_Rewards_Model_Observer_Sales_Carttransfers $cart_transfers
     * @param TBT_Rewards_Model_Sales_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _processAllCartPoints($cart_transfers, $order)
    {
        foreach ( $cart_transfers->getAllCartPoints () as $cart_points ) {
            if (! $cart_points) {
                continue;
            }

            try {
                $is_transfer_successful = Mage::helper ( 'rewards/transfer' )->transferOrderPoints ( $cart_points ['amount'], $cart_points ['currency'], $order->getId (), $cart_points ['rule_id'] );
            } catch ( Exception $ex ) {
                throw new Mage_Core_Exception ( $ex->getMessage () );
            }
        }

        return $this;
    }

    /**
     *
     * @param TBT_Rewards_Model_Observer_Sales_Carttransfers $cart_transfers
     * @param TBT_Rewards_Model_Sales_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _processCartRedemptionRules($cart_transfers, $order)
    {
        foreach ( $cart_transfers->getRedemptionRuleIds () as $rule_id ) {
            $points = null;

            // Get the points amount
            try {
                $points = $this->_getRewardsSession ()->calculateCartPoints ( $rule_id, $order->getAllItems (), true );
            } catch ( Exception $e ) {
                die ( $e->getMessage () );
            }

            // If no point samount was retrieved, continue on to the next redemption
            if (!is_array ( $points )) {
                continue;
            }

            // Try to transfer points to the customer
            try {
                $is_transfer_successful = Mage::helper ( 'rewards/transfer' )->transferOrderPoints ( $points ['amount'], $points ['currency'], $order->getId (), $rule_id );
            } catch ( Exception $ex ) {
                throw new Mage_Core_Exception ( $ex->getMessage () );
            }
        }

        return $this;

    }


    /**
     * If there exist any points
     * @param TBT_Rewards_Model_Sales_Order $order
     * @return boolean
     */
    protected function _doDisplayPendingMsgs($order)
    {
        $catalog_transfers = Mage::getSingleton ( 'rewards/observer_sales_catalogtransfers' );
        $cart_transfers = Mage::getSingleton ( 'rewards/observer_sales_carttransfers' );

        // If cart transfers dont match the order increment ID or no increment ID is in the current order model, don't display
        // any messages because we'll probably make a second pass through this function.
        // @nelkaake: I have no idea why we only check the cart transfers here,
        //             but we seem to not be setting the catalog_transfers variable correctly above...
        if ($order->getIncrementId () != $cart_transfers->getIncrementId ()) {
            return false;
        }

        // If no point swere earned or spent, there is no need to display any messages.
        if (!$order->hasPointsEarningOrSpending()) {
            return false;
        }

        // TODO @nelkaake 08-18-2011: is this next IF statement needed?  I know the process pending msgs is, but do we need the if statement?
        if(!$order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT)) {
            return false;
        }

        return true;
    }

    /**
     * Sends any order and pending messages to the display
     * @param TBT_Rewards_Model_Sales_Order $order
     */
    protected function _processPendingMsgs($order)
    {
        $earned_points_string = Mage::getModel('rewards/points')->set($order->getTotalEarnedPoints());
        $redeemed_points_string = Mage::getModel('rewards/points')->set($order->getTotalSpentPoints());

        if ($this->_getRewardsSession ()->isAdminMode ()) {
            return $this->_processAdminPendingMsgs($order);
        }

        if ($order->hasPointsEarning () && !$order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'You earned %s for the order you just placed.', $earned_points_string ) );
        } elseif (!$order->hasPointsEarning () && $order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'You spent %s for the order you just placed.', $redeemed_points_string ) );
        } elseif ($order->hasPointsEarning () && $order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'You earned %s and spent %s for the order you just placed.', $earned_points_string, $redeemed_points_string ) );
        } else {
            // no points earned or spent
        }

        if ($order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT)) {
            $this->_dispatchSuccess (Mage::helper('rewards')->__('The points you earned are currently pending the completion of your order. You will be able to spend these points after we finish processing your order.'));
        } elseif ($order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_APPROVAL)) {
            $this->_dispatchSuccess (Mage::helper('rewards')->__('The points you earned are currently on hold until we have processed your order. You will be able to spend these points after an administrator has approved the order.'));
        } else {
            // points were likely approved, so no extra message is needed.
        }

        return $this;
    }



    /**
     * Sends any order and pending messages to the display
     * @param TBT_Rewards_Model_Sales_Order $order
     */
    protected function _processAdminPendingMsgs($order)
    {
        $earned_points_string = Mage::getModel('rewards/points')->set($order->getTotalEarnedPoints());
        $redeemed_points_string = Mage::getModel('rewards/points')->set($order->getTotalSpentPoints());

        if(!$this->_getRewardsSession ()->isAdminMode ()) {
            return $this;
        }

        if ($order->hasPointsEarning () && !$order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'The customer was credited %s for the order.', $earned_points_string ) );
        } elseif (!$order->hasPointsEarning () && $order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'The customer was deducted %s for the order.', $redeemed_points_string ) );
        } elseif ($order->hasPointsEarning () && $order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'The customer was credited %s and deducted %s for the order.', $earned_points_string, $redeemed_points_string ) );
        } else {
            // no points earned or spent
        }

        if ($order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT)) {
            $this->_dispatchSuccess (Mage::helper('rewards')->__("The customer's earned points are currently pending the completion of the order. They will be able to spend their points after order process is complete."));
        } elseif ($order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_APPROVAL)) {
            $this->_dispatchSuccess (Mage::helper('rewards')->__("The customer's earned points are currently on hold. The customer will not be able to spend their points until an administator approves the transaction(s)."));
        } else {
            // points were likely approved, so no extra message is needed.
        }


        return $this;
    }


    /**
     * @param Mage_Core_Model_Message $message
     */
    protected function _dispatchCheckoutMsg($message)
    {
        //Mage::helper('rewards/debug')->noticeBacktrace("Adding msg with _dispatchCheckoutMsg: ". $message->getText());
        $message->setIdentifier('TBT_Rewards_Model_Observer_Sales_Order_Save_After_Create(pending points)');
        Mage::getSingleton('core/session')->addMessage($message);
        return $this;
    }


    /**
     * @param string $str_msg
     */
    protected function _dispatchSuccess($str_msg)
    {
        /* @var $message Mage_Core_Model_Message */
        $message_factory = Mage::getSingleton('core/message');
        $message = $message_factory->success($str_msg);

        return $this->_dispatchCheckoutMsg($message);
    }
    /**
     * @param string $string
     */
    protected function _dispatchNotice($str_msg)
    {
        /* @var $message Mage_Core_Model_Message */
        $message_factory = Mage::getSingleton('core/message');
        $message = $message_factory->notice($str_msg);

        return $this->_dispatchCheckoutMsg($message);
    }

    /**
     * Fetches the rewards session
     *
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSession()
    {
        return Mage::getSingleton ( 'rewards/session' );
    }

    /**
     * @return TBT_Rewards_Observer_Sales_Catalogtransfers
     */
    protected function _getCatalogTransfers()
    {
        return Mage::getSingleton ( 'rewards/observer_sales_catalogtransfers' );
    }

    /**
     * @return TBT_Rewards_Observer_Sales_Carttransfers
     */
    protected function _getCartTransfers()
    {
        return Mage::getSingleton ( 'rewards/observer_sales_carttransfers' );
    }

    /**
     * Checks if we are in Admin (back-end) section.
     * @return boolean Returns true if we are in admin, false otherwise
     */
    protected function  _isAdmin()
    {
        return Mage::helper('rewards')->getIsAdmin();
    }

}
