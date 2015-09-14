<?php

class TBT_Rewards_Model_Customer_Indexer_Observer extends Varien_Object
{
    const REWARDS_TRANSFER_ENTITY = TBT_Rewards_Model_Customer_Indexer_Points::REWARDS_TRANSFER_ENTITY;
    const REWARDS_CUSTOMER_ENTITY = TBT_Rewards_Model_Customer_Indexer_Points::REWARDS_CUSTOMER_ENTITY;

    protected $_oldTransfer = null;

    /**
     * Update points via observer method (updateUsablePointsBalance)
     * @param  Varien_Event_Observer $observer
     * @return TBT_Rewards_Model_Customer_Indexer_Points
     */
    public function updateUsablePointsBalance($observer)
    {
        try {
            if(!Mage::helper('rewards/customer_points_index')->canIndex()) {
                //shouldn't be using the index
                return $this;
            }

            $transfer = Mage::helper('rewards/dispatch')->getEventObject($observer);
            
            
            if ($this->_getShouldSkipIndex($transfer)) {
                return $this;
            }

            $this->_oldTransfer = clone($transfer);

            Mage::getSingleton ( 'index/indexer' )->processEntityAction ( $transfer, self::REWARDS_TRANSFER_ENTITY, Mage_Index_Model_Event::TYPE_SAVE );

        } catch(Exception $e) {
            Mage::helper('rewards/debug')->logException($e);
        }

        $customerId = $transfer->getCustomerId();
        if (isset($customerId) && $customerId != '') {
            $process = Mage::getModel('index/indexer')->getProcessByCode('rewards_transfer')->reindexAll();
            $storeId = 2;
            $erplyModel = Mage::getModel('Erply/Erply');

            if($erplyModel->verifyUser($storeId)){
                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');    

                $customer = Mage::getModel("customer/customer")->load($customerId);
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
                    Mage::log('email: '.$email,null,'EarplyTest.log');
                    if($email != ''){
                        $params = array(
                            'searchName' => $email,
                        );
                        $responseJson = $erplyModel->sendRequest('getCustomers', $params);
                        $response = json_decode($responseJson, true);
                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                            break;
                        }                
                        if ($response["status"]["responseStatus"] == "error"){
                            Mage::log($response["status"]["errorCode"],null,'erply_limit.log');                            
                            break;
                        }
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
                                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                        break;
                                    }                
                                    if ($response["status"]["responseStatus"] == "error"){
                                        Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                        break;
                                    }
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
                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                            break;
                        }                
                        if ($response["status"]["responseStatus"] == "error"){
                            Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                            break;
                        }
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
                    if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                        break;
                    }                
                    if ($response["status"]["responseStatus"] == "error"){
                        Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                        break;
                    }
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

                            // Reward Points Erply >To> Magento
                            $params = array(
                                'customerID' => $erplyCustomerId,
                            );
                            $responseJson = $erplyModel->sendRequest('getEarnedRewardPointRecords', $params);
                            $response = json_decode($responseJson, true);
                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                break;
                            }                
                            if ($response["status"]["responseStatus"] == "error"){
                                Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                break;
                            }
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

                                                //Mage::log('magentoPoints: '.$magentoPoints,null,'EarplyTest.log');
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
                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                break;
                            }                
                            if ($response["status"]["responseStatus"] == "error"){
                                Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                break;
                            }
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
                                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                            break;
                                        }                
                                        if ($response["status"]["responseStatus"] == "error"){
                                            Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                            break;
                                        }
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
                                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                            break;
                                        }                
                                        if ($response["status"]["responseStatus"] == "error"){
                                            Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                            break;
                                        }
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
                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                break;
                            }                
                            if ($response["status"]["responseStatus"] == "error"){
                                Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                break;
                            }

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
     * Observes 'rewards_order_points_transfer_after_create' which gets triggered when point transfers are created for
     * an order. Check TBT_Rewards_Model_Observer_Sales_Order_Save_After_Create::createPointsTransfers()
     *
     * @param  Varien_Event_Observer $observer
     * @return $this
     */
    public function updateIndexAfterOrderPointsCreated($observer)
    {
        $this->_updateIndexAfterOrderAction($observer);
        return $this;
    }

    /**
     * Observes 'rewards_order_points_transfer_after_approved' which gets triggered when point transfers are approved for
     * an order. Check TBT_Rewards_Model_Observer_Sales_Order_Save_After_Approve::approveAssociatedPendingTransfersOnShipment()
     * and TBT_Rewards_Model_Observer_Sales_Order_Invoice_Pay::approveAssociatedPendingTransfersOnInvoice()
     *
     * @param  Varien_Event_Observer $observer
     * @return $this
     */
    public function updateIndexAfterOrderPointsApproved($observer)
    {
        $this->_updateIndexAfterOrderAction($observer);
        return $this;
    }

    /**
     * Observes 'rewards_sales_order_transfer_ajuster_done' which gets triggered when an admin operation is performed
     * on an order which leads to some points adjustments (canceling order).
     *
     * @param  Varien_Event_Observer $observer
     * @return $this
     */
    public function updateIndexAfterOrderCanceled($observer)
    {
        $this->_updateIndexAfterOrderAction($observer);
        return $this;
    }

    /**
     * Observes 'rewards_sales_order_payment_automatic_cancel_done' which gets triggered when an admin operation is
     * performed on an order which leads to some points adjustments (if it a mass admin cancel operation, a payment
     * failure at checkout (paypal, authorize.net), or if Magento prior to 1.4.1.1 and it's a single admin order cancel).
     *
     * @param  Varien_Event_Observer $observer
     * @return $this
     */
    public function updateIndexAfterPaymentCanceled($observer)
    {
        $this->_updateIndexAfterOrderAction($observer);
        return $this;
    }

    /**
     * Triggers our Customer Points Balance Indexer to updates customer points balance after an order operation.
     * @param  Varien_Event_Observer $observer
     * @return $this
     */
    protected function _updateIndexAfterOrderAction($observer)
    {
       try {
            if (!Mage::helper('rewards/customer_points_index')->canIndex()) {
                //shouldn't be using the index
                return $this;
            }

            $event = $observer->getEvent();
            if (!$event) {
                return $this;
            }

            $order            = $event->getOrder();
            $session_customer = $this->_getRewardsCustomer($order);

            if (!$session_customer || !$session_customer->getId()) {
                // Only if a customer model exists and that customer has been already created.
                Mage::helper('rewards/debug')->warn("No customer was found for this order (#{$order->getIncrementId()}), so their points index could not be updated.");

                return $this;
            }

            Mage::getSingleton('index/indexer')->processEntityAction($session_customer, self::REWARDS_CUSTOMER_ENTITY, Mage_Index_Model_Event::TYPE_SAVE);

        } catch(Exception $e) {
            Mage::helper('rewards/debug')->logException($e);
        }

        
        return $this;
    }

    /**
     * Update points via observer method (updateIndexBeforeOrderSave)
     * @param  Varien_Event_Observer $observer
     * @return TBT_Rewards_Model_Customer_Indexer_Points
     */
    public function updateIndexBeforeOrderSave($observer)
    {
        try {
            if(!Mage::helper('rewards/customer_points_index')->canIndex()) {
                //shouldn't be using the index
                return $this;
            }

            $event = $observer->getEvent();
            if (!$event) {
                return $this;
            }

            $order = $event->getOrder();
            if (!$order) {
                return $this;
            }

            $session_customer = $this->_getRewardsCustomer($order);

            if(!$session_customer || !$session_customer->getId()) {
                // no logging required as we'll check again in self::_updateIndexAfterOrderAction()
                return $this;
            }

            Mage::getSingleton('index/indexer')->processEntityAction($session_customer, self::REWARDS_CUSTOMER_ENTITY, Mage_Index_Model_Event::TYPE_SAVE);

        } catch(Exception $e) {
            Mage::helper('rewards/debug')->logException($e);
        }

        return $this;
    }

    /**
     * Update points via observer method (updateIndexOnNewCustomer)
     * @param  Varien_Event_Observer $observer
     * @return TBT_Rewards_Model_Customer_Indexer_Points
     */
    public function updateIndexOnNewCustomer($observer)
    {
        try {
            if(!Mage::helper('rewards/customer_points_index')->canIndex()) {
                //shouldn't be using the index
                return $this;
            }

            $customer = $observer->getEvent()->getCustomer();

            if(!$customer || !$customer->getId()) {
                // Only if a customer model exists and that customer has been already created.
                Mage::helper('rewards/customer_points_index')->error();
                Mage::helper('rewards/debug')->error("Customer model does not exist in observer or that customer has not been saved yet.  This caused the points index to be to become out of sync and disabled.");

                return $this;
            }

            $customer = Mage::getModel('rewards/customer')->load($customer->getId());
            Mage::getSingleton('index/indexer')->processEntityAction($customer, self::REWARDS_CUSTOMER_ENTITY, Mage_Index_Model_Event::TYPE_SAVE);

        } catch(Exception $e) {
            Mage::helper('rewards/debug')->logException($e);
        }

        return $this;
    }

    /**
     * Fetches the customer model from either an order/quote or the session, depending on what's available.
     * @param Mage_Sales_Model_Order $order or quote
     * @return TBT_Rewards_Model_Customer
     */
    protected function _getRewardsCustomer($order=null)
    {
        // If the customer exists in the order, use that. If not, use the session customer from the rewards model.
        if ($order) {
            if( $order ->getCustomer() ) {
                // The index session dispatch requires a rewards model, so we should load that.
                $session_customer = $order->getCustomer();
                if (! ($session_customer instanceof TBT_Rewards_Model_Customer)) {
                    $session_customer = Mage::getModel('rewards/customer')->getRewardsCustomer( $session_customer );
                }
            } else {
                $session_customer = Mage::getModel('rewards/customer')->load( $order->getCustomerId() );
            }
        } else {
            $session_customer = $this->_getRewardsSess()->getSessionCustomer();
        }

        return $session_customer;
    }

    protected function _getShouldSkipIndex($transfer)
    {
        $isOrderTransfer = $transfer->getReferenceType() == TBT_Rewards_Model_Transfer_Reference::REFERENCE_ORDER;
        $skip = $this->_alreadyIndexed($transfer) || $isOrderTransfer;

        return $skip;
    }

    /**
     * Fetches the customer rewards session.
     *
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSess()
    {
        return Mage::getSingleton ( 'rewards/session' );
    }

    /**
     * Fetches the checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton ( 'checkout/session' );
    }

    /**
     * This will check if this transfer was already indexed. Needed for
     * new transfers which are saved then saved again to set the 'source_reference_id'
     * field. (see TBT_Rewards_Model_Transfer::_afterSave())
     *
     * @param  TBT_Rewards_Model_Transfer $transfer
     * @return bool
     */
    protected function _alreadyIndexed($transfer)
    {
        if (!$this->_oldTransfer) {
            return false;
        }

        if ($this->_oldTransfer->getId() != $transfer->getId()) {
            return false;
        }

        if ($this->_oldTransfer->getStatus() != $transfer->getStatus()) {
            return false;
        }

        return true;
    }
}
