<?php
/**
 * NB! This is a BETA release of Erply Connector.
 *
 * Use with caution and at your own risk.
 *
 * The author does not take any responsibility for any loss or damage to business
 * or customers or anything at all. These terms may change without further notice.
 *
 * License terms are subject to change. License is all-restrictive until
 * said otherwise.
 *
 * @author Eepohs Ltd
 */

class Eepohs_Erply_Model_Cron extends Eepohs_Erply_Model_Erply
{
    public function __construct() {
        $pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();
        foreach ($pCollection as $process) {
            $process->setMode(Mage_Index_Model_Process::MODE_MANUAL)->save();
        }
    }

    public function __destruct() {
        $pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();
        foreach ($pCollection as $process) {
            $process->setMode(Mage_Index_Model_Process::MODE_REAL_TIME)->save();
        }
    }
    
    public function productUpdate(){
        Mage::log('Eepohs_Erply_Model_Cron productUpdate() started',null,'erply_limit.log');
        Mage::log('Product Import Started',null,'erply_product.log');
        $erplyCalls = 0;
        $storeId = 2;
        $erplyModel = Mage::getModel('Erply/Erply');
        if($erplyModel->verifyUser($storeId)){
            $productImportPageNo = 1;
            $productImportLoop = true;
            while($productImportLoop){
                $params = array(
                    'active'    => 1,
                    'getStockInfo' => 1,
                    'getFIFOCost' => 1,
                    'getPriceListPrices' => 1,
                    'pageNo'    => $productImportPageNo,
                    'recordsOnPage' => 100
                );
                Mage::log('Request Params',null,'erply_product.log');
                Mage::log($params,null,'erply_product.log');
                $erplyCalls++;
                $responseJson = $erplyModel->sendRequest('getProducts', $params);
                $response = json_decode($responseJson, true);
                Mage::log('Response Status',null,'erply_product.log');
                Mage::log($response['status'],null,'erply_product.log');
                if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                    Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                    $productImportLoop = false;
                    break;
                }                
                if ($response["status"]["responseStatus"] == "error"){
                    Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                    $productImportLoop = false;
                    break;
                }
                if ($response["status"]["responseStatus"] == "error" || count($response["records"]) == 0){
                    $productImportLoop = false;
                }else{
                    if(isset($response["records"])){
                        $products = $response["records"];
                        $store = Mage::getModel('core/store')->load($storeId);
                        Mage::getModel('Erply/Product')->importProductsDJ($products, $storeId, $store);
                        unset($response);
                        $productImportPageNo++;
                    }
                }
            } 
        }
        Mage::log('Product Import Ended',null,'erply_product.log');
        Mage::log('Total API Calls: '.$erplyCalls,null,'erply_limit.log');
        Mage::log('Eepohs_Erply_Model_Cron productUpdate() ended',null,'erply_limit.log');
    }

    public function customerUpdate(){

        /*


        Mage::log('Eepohs_Erply_Model_Cron customerUpdate() started',null,'erply_limit.log');
        $erplyCalls = 0;

        $storeId = 2;
        $erplyModel = Mage::getModel('Erply/Erply');

        if($erplyModel->verifyUser($storeId)){

            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

            $sql = "SELECT customer_id FROM customer_sync_log";
            $rows = $readConnection->fetchAll($sql);
            
            $customerIds = array_column($rows, 'customer_id');
            if(empty($customerIds)){
                $customerIds[] = 0;
            }

            $customers = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->joinAttribute('erply_customer_id', 'customer/erply_customer_id', 'entity_id', null, 'left')
                ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                ->addAttributeToSelect('email')
                ->addAttributeToFilter('entity_id',array('nin'=> $customerIds))
                ->setPageSize(100)
                ->setCurPage(1)
                ->setOrder('entity_id', 'desc');

            if(!count($customers->getData())){
                $sqlTruncate = "TRUNCATE TABLE customer_sync_log; commit;";
                $writeConnection->query($sqlTruncate);
            }

            foreach ($customers as $customer) {

                //$customer = Mage::getModel('customer/customer')->load(6875);

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
                        $erplyCalls++;
                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                            $productImportLoop = false;
                            break;
                        }                
                        if ($response["status"]["responseStatus"] == "error"){
                            Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                            $productImportLoop = false;
                            break;
                        }

                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                            break;
                        }else{
                            if(!isset($response['records']) || !count($response['records'])){
                                if($phone != ''){
                                    $params = array(
                                        'searchName'    => $phone,
                                    );
                                    $responseJson = $erplyModel->sendRequest('getCustomers', $params);
                                    $response = json_decode($responseJson, true);
                                    $erplyCalls++;
                                    if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                        $productImportLoop = false;
                                        break;
                                    }                
                                    if ($response["status"]["responseStatus"] == "error"){
                                        Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                        $productImportLoop = false;
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
                        $erplyCalls++;
                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                            $productImportLoop = false;
                            break;
                        }                
                        if ($response["status"]["responseStatus"] == "error"){
                            Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                            $productImportLoop = false;
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
                    $erplyCalls++;
                    if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                        $productImportLoop = false;
                        break;
                    }                
                    if ($response["status"]["responseStatus"] == "error"){
                        Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                        $productImportLoop = false;
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
                            $erplyCalls++;

                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                $productImportLoop = false;
                                break;
                            }                
                            if ($response["status"]["responseStatus"] == "error"){
                                Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                $productImportLoop = false;
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
                                                $comments = 'Erply Invoice # '.$invoiceNo.' - Order Received - Point Earned';
                                            }else{
                                                $comments = 'Erply Points Earned';
                                            }
                                            $earnedPoints = $records['earnedPoints'];
                                            $transactionID = $records['transactionID'];
                                            $currentDate = date('Y-m-d h:i:s');

                                            //if($invoiceNo =='' || empty(Mage::getModel('sales/order')->loadByIncrementId($invoiceNo)->getData())){
                                                $sqlInsert = "INSERT INTO rewards_transfer 
                                                (customer_id, quantity, comments, status, currency_id, reason_id, erply_transaction_id , creation_ts , last_update_ts)
                                                VALUES 
                                                ('$customerId', '".$earnedPoints."','".$comments."', 5,1,1,'".$transactionID."', '".$currentDate."', '".$currentDate."'); commit;";
                                                $writeConnection->query($sqlInsert);

                                                $customer_points_usable = $magentoPoints + $earnedPoints;
                                                $sqlUpdate = "UPDATE rewards_customer_index_points SET customer_points_usable = '".$customer_points_usable."', customer_points_active = '".$customer_points_usable."'  WHERE customer_id = ".$customer->getId();
                                                $writeConnection->query($sqlUpdate);
                                            //}
                                        }
                                    }
                                }
                            }

                            $params = array(
                                'customerID' => $erplyCustomerId,
                            );
                            $responseJson = $erplyModel->sendRequest('getUsedRewardPointRecords', $params);
                            $response = json_decode($responseJson, true);
                            $erplyCalls++;

                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                $productImportLoop = false;
                                break;
                            }                
                            if ($response["status"]["responseStatus"] == "error"){
                                Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                $productImportLoop = false;
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
                                                $comments = 'Erply Invoice # '.$invoiceNo.' - Order Received - Point Spent';
                                            }else{
                                                $comments = 'Erply Points Spent';
                                            }
                                            $usedPoints = $records['usedPoints'] * -1;
                                            $transactionID = $records['transactionID'];
                                            $currentDate = date('Y-m-d h:i:s');
                                            //if($invoiceNo =='' || empty(Mage::getModel('sales/order')->loadByIncrementId($invoiceNo)->getData())){
                                                $sqlInsert = "INSERT INTO rewards_transfer 
                                                (customer_id, quantity, comments, status, currency_id, reason_id, erply_transaction_id , creation_ts , last_update_ts)
                                                VALUES 
                                                ('$customerId', '".$earnedPoints."','".$comments."', 5,1,1,'".$transactionID."', '".$currentDate."', '".$currentDate."'); commit;";
                                                $writeConnection->query($sqlInsert);

                                                $customer_points_usable = $magentoPoints + $usedPoints;
                                                $sqlUpdate = "UPDATE rewards_customer_index_points SET customer_points_usable = '".$customer_points_usable."' , customer_points_active = '".$customer_points_usable."'  WHERE customer_id = ".$customer->getId();
                                                $writeConnection->query($sqlUpdate);
                                            //}
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
                                        $erplyCalls++;
                                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                            $productImportLoop = false;
                                            break;
                                        }                
                                        if ($response["status"]["responseStatus"] == "error"){
                                            Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                            $productImportLoop = false;
                                            break;
                                        }
                                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                                            break;
                                        }else{
                                            if(isset($response['records'][0]['transactionID'])){
                                                $transactionID = $response['records'][0]['transactionID'];
                                                $sqlUpdate = "UPDATE rewards_transfer SET erply_transaction_id = '".$transactionID."' WHERE rewards_transfer_id = ".$rewards_transfer_id;
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
                                        $erplyCalls++;
                                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                            $productImportLoop = false;
                                            break;
                                        }                
                                        if ($response["status"]["responseStatus"] == "error"){
                                            Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                            $productImportLoop = false;
                                            break;
                                        }
                                        if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){
                                            Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_productimport.log');
                                            break;
                                        }else{
                                            if(isset($response['records'][0]['transactionID'])){
                                                $transactionID = $response['records'][0]['transactionID'];
                                                $sqlUpdate = "UPDATE rewards_transfer SET erply_transaction_id = '".$transactionID."' WHERE rewards_transfer_id = ".$rewards_transfer_id;
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
                            $erplyCalls++;
                            if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                                Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                                $productImportLoop = false;
                                break;
                            }                
                            if ($response["status"]["responseStatus"] == "error"){
                                Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                                $productImportLoop = false;
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
                                    $sqlInsert = "INSERT INTO rewards_customer_index_points (customer_id,customer_points_usable,customer_points_pending_event,customer_points_pending_time,customer_points_pending_approval,customer_points_active ) VALUES ('".$customerId."',0,0,0,0,0) ";
                                    $writeConnection->query($sqlInsert);
                                }

                            }
                                
                        }
                    }
                }

                $sqlInsertLog = "INSERT INTO customer_sync_log (customer_id) VALUES ('".$customerId."'); commit;";
                $writeConnection->query($sqlInsertLog);

            }
        }
        
        Mage::log(' Total API Calls:'.$erplyCalls,null,'erply_limit.log');
        Mage::log('Eepohs_Erply_Model_Cron customerUpdate() Ended',null,'erply_limit.log');*/
   
    }

    public function checkPendingOrders() {
        Mage::log('checkPendingOrders call started',null,'erply_limit.log');
        
        $erplyCalls = 0;
        $storeId = 2;
        $erplyModel = Mage::getModel('Erply/Erply');
        if($erplyModel->verifyUser($storeId)){
            $orders = Mage::getModel('sales/order')->getCollection()->addAttributeToSelect("*")->addAttributeToFilter('status', 'processing');
            $params = array();
            if($orders->getSize() > 0) {
                Mage::helper('Erply')->log("Starting order status checking");
                Mage::helper('Erply')->log("Found ".$orders->getSize()." pending orders in Magento");
                foreach($orders as $order) {
                    $isComplete = false;
                    $params["number"] = $order->getIncrementId();
                    Mage::helper('Erply')->log("Request to Erply for Magento order #".$order->getIncrementId()." - ".print_r($params, true));
                    $erplyCalls++;
                    $request = $erplyModel->sendRequest('getSalesDocuments', $params);
                    $response = json_decode($request, true);
                    Mage::helper('Erply')->log("Reponse from Erply for Magento order #".$order->getIncrementId()." - ".print_r($response, true));

                    if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                        Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                        $productImportLoop = false;
                        break;
                    }                
                    if ($response["status"]["responseStatus"] == "error"){
                        Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                        $productImportLoop = false;
                        break;
                    }
                    if ($response["status"]["responseStatus"] == "error" || count($response["records"]) == 0){
                        Mage::helper('Erply')->log($response["status"]["errorCode"]);
                        continue;
                    }

                    $erpOrder = $response["records"][0];
                    try {
                        if($erpOrder["invoiceState"] == "SHIPPED" || $erpOrder["invoiceState"] == "FULFILLED") {
                            $shipment = $order->prepareShipment();
                            $shipment->register();
                            $order->setIsInProcess(true);
                            $order->addStatusHistoryComment('Order is now Complete.', false);
                            $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($shipment)
                                ->addObject($shipment->getOrder())
                                ->save();
                            Mage::helper('Erply')->log("Marked order #".$order->getIncrementId()." as Completed");
                        } elseif($erpOrder["invoiceState"] == "CANCELLED") {
                            if($order->canCancel()) {
                                $order->cancel()->save();
                                Mage::helper('Erply')->log("Marked order #".$order->getIncrementId()." as Cancelled");
                            }
                        }
                    }catch (Exception $e) {
                        Mage::helper('Erply')->log("Failed to change order status: ".$e->getMessage());
                    }
                }
            }
        }
        
        Mage::log('checkPendingOrders Total API Calls:'.$erplyCalls,null,'erply_limit.log');
        Mage::log('checkPendingOrders call ended',null,'erply_limit.log');
    }
}