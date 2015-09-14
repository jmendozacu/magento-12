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
class Eepohs_Erply_ErplyController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        // $order = Mage::getModel('sales/order')->loadByIncrementId('200004221');
        // if($order){
        //     $params = array(
        //         'id'    => $erplyOrderId,
        //         'paymentStatus' => 'PAID',
        //     );
        //     echo "<pre>";print_r($order->getData());exit;
        //     $items = $order->getAllVisibleItems();
        //     $key = 1;
        //     foreach($items as $item){
        //         echo "<pre>"; print_r($item->getData());
        //     }
        //     exit;
        // }
        // exit;

               
        $storeId = 2;
        $erplyModel = Mage::getModel('Erply/Erply');

        if($erplyModel->verifyUser($storeId)){
            /*
            $customers = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->joinAttribute('erply_customer_id', 'customer/erply_customer_id', 'entity_id', null, 'left')
                ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                ->addAttributeToSelect('email')->setOrder('entity_id', 'desc');

            foreach ($customers as $customer) {

                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');    

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


                if($customerId > 580){

                    Mage::log('CustomerSync :'.$customerId);
                    Mage::log('CustomerSync :'.$email);
                    
                    if($erplyCustomerId =='' || $erplyCustomerId == 0){
                        if($email != ''){
                            $params = array(
                                'searchName' => $email,
                            );
                            $responseJson = $erplyModel->sendRequest('getCustomers', $params);
                            $response = json_decode($responseJson, true);

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
            
                }

            }
            */



            /*$productModel = Mage::getModel('Erply/Product');
            $orderIncrementId = "200003637";
            $erplyOrderId = 39470;
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            echo "<pre>"; print_r($order);exit;

            if($order){

                $params = array(
                    'id'    => $erplyOrderId,
                    'paymentStatus' => 'PAID',
                );

                $items = $order->getAllVisibleItems();

                $rewards_discount_amount_item = 0;
                if($order->getRewardsDiscountAmount() != '0.0000' && $order->getRewardsDiscountAmount() != ''){
                    $rewards_discount_amount = (float) $order->getRewardsDiscountAmount();
                    $totalItems = count($items->getData());
                    $rewards_discount_amount_item = $rewards_discount_amount / $totalItems;
                }

                $key = 1;
                foreach($items as $item){
                    echo "<pre>"; print_r($item->getData());
                    $erpProductId = null;
                    if($erpProduct = $productModel->findProductBySKU($item->getSku())){
                        if(isset($erpProduct['productID'])){
                            $erpProductId = $erpProduct['productID'];
                        }                        
                    }
                    if(isset($erpProductId) && !empty($erpProductId) ) {
                        $params['productID' . $key] = $erpProductId;
                    }
                    if($item->getName()){
                        $params['itemName' . $key] = $item->getName();    
                    }

                    if($item->getQtyOrdered()){
                        $params['amount' . $key] = (int) $item->getQtyOrdered();
                    }
                    
                    if($item->getPrice()){
                        $params['price' . $key] = (float) $item->getPrice();
                    }

                    if($item->getTaxPercent() != '0.0000'){
                        $params['price' . $key] = (float) $item->getPriceInclTax();
                    }

                    if($item->getDiscountPercent() != '0.0000'){
                        $params['discount' . $key] = $item->getDiscountPercent();
                    }else{
                        if($item->getDiscountAmount() != '0.0000'){
                            $discountAmount = (float) $item->getDiscountAmount() / (float) $item->getQtyOrdered();
                            $params['price' . $key] = (float) $params['price' . $key] -  $discountAmount;
                        }
                    }

                    if($rewards_discount_amount_item){
                        $params['price' . $key] = (float) $params['price' . $key] -  $rewards_discount_amount_item;
                    }

                    $key++;
                }

                if($order->getShippingAmount()) {
                    $s = Mage::getModel('sales/quote_address_rate')->getCollection();
                    foreach ( $s as $s1 ) {
                        if ( $s1->getCode() == $order->getShippingMethod()) {
                            break;
                        }
                    }
                    $shippingDescription = isset($s1["method_title"]) ? '(' . $s1["method_title"] . ')' : '';
                    $params['itemName' . $key] = 'Shipping ' . $shippingDescription;
                    $params['amount' . $key] = 1;
                    $params['price' . $key] = $order->getShippingAmount();
 
                    $key++;
                }

                echo "<pre>"; print_r($params);

                $responseJson = $erplyModel->sendRequest('saveSalesDocument', $params);
                $response = json_decode($responseJson, true);
                echo "<pre>"; print_r($response);
            }

            exit;*/

           /* echo "<br>Update Script Started";

            $salesUpdatePageNo = 1;
            $salesUpdateLoop = true;
            while($salesUpdateLoop){
                $params = array(
                    'type'    => 'INVWAYBILL',
                    'ids' => '49195',
                    'pageNo'    => $salesUpdatePageNo,
                    'recordsOnPage' => 200
                );
                $responseJson = $erplyModel->sendRequest('getSalesDocuments', $params);
                $response = json_decode($responseJson, true);
                echo "<br>Fetched 200 Records ";
                if(isset($response['status']['errorCode']) && $response['status']['errorCode'] == 1002 ){ //   Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.
                    Mage::log('Error : Hourly request limit (by default 1000 requests) has been exceeded for this account. Please resume next hour.',null,'erply_limit.log');
                    $salesUpdateLoop = false;
                    break;
                }                
                if ($response["status"]["responseStatus"] == "error"){
                    Mage::log($response["status"]["errorCode"],null,'erply_limit.log');
                    $salesUpdateLoop = false;
                    break;
                }
                if ($response["status"]["responseStatus"] == "error" || count($response["records"]) == 0){
                    $salesUpdateLoop = false;
                }else{
                    if(isset($response["records"])){
                        foreach($response["records"] as $record){


                            $salesDocumentId = $record['id'];
                            $orderIncrementId = $record['number'];

                            echo '<hr><br>salesDocumentId:'.$salesDocumentId;
                            echo '<br>invoiceNumber:'.$orderIncrementId;
                            echo '<br>paymentStatus:'.$record['paymentStatus'];

                            $productModel = Mage::getModel('Erply/Product');
                            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

                            //if($record['paymentStatus'] == 'UNPAID'){
                            if(!empty($order->getData())){

                                if((float)$order->getGrandTotal() != (float)$record['total']){

                                //echo "<pre>"; print_r($record);exit;

                                    echo '<br>Updating Record';
                                
                                    Mage::log('===================================', null,'sales_document_update.log');
                                    Mage::log('Erply Sales Document Id : '.$salesDocumentId, null,'sales_document_update.log');
                                    Mage::log('Erply Order Increment Id : '.$orderIncrementId, null,'sales_document_update.log');
                                    Mage::log('Erply Old Payment Status : '.$record['paymentStatus'], null,'sales_document_update.log');
                                    Mage::log('Erply Old Total : '.$record['total'], null,'sales_document_update.log');

                                    $params = array(
                                        'id'    => $salesDocumentId,
                                        'paymentStatus' => 'PAID',
                                    );

                                    $items = $order->getAllVisibleItems();
                                    
                                    $rewards_discount_amount_item = 0;
                                    if($order->getRewardsDiscountAmount() != '0.0000' && $order->getRewardsDiscountAmount() != ''){
                                        $rewards_discount_amount = (float) $order->getRewardsDiscountAmount();
                                        $totalItems = count($items->getData());
                                        $rewards_discount_amount_item = $rewards_discount_amount / $totalItems;
                                    }

                                    $key = 1; $discountAmount=0;
                                    foreach($items as $item){
                                        echo "<pre>"; print_r($item->getData());

                                        $erpProductId = null;
                                        if($erpProduct = $productModel->findProductBySKU($item->getSku())){
                                            if(isset($erpProduct['productID'])){
                                                $erpProductId = $erpProduct['productID'];
                                            }                        
                                        }
                                        if(isset($erpProductId) && !empty($erpProductId) ) {
                                            $params['productID' . $key] = $erpProductId;
                                        }
                                        if($item->getName()){
                                            $params['itemName' . $key] = $item->getName();    
                                        }

                                        if($item->getQtyOrdered()){
                                            $params['amount' . $key] = (int) $item->getQtyOrdered();
                                        }
                                        
                                        if($item->getPrice()){
                                            $params['price' . $key] = (float) $item->getPrice();
                                        }

                                        if($item->getTaxPercent() != '0.0000'){
                                            $params['price' . $key] = (float) $item->getPriceInclTax();
                                        }

                                        // $price = (float) $item->getPriceInclTax();

                                        // $discountAmount = (float) $item->getDiscountAmount() / (float) $item->getQtyOrdered();
                                        // $totalDiscount = $discountAmount;
                                        // if($rewards_discount_amount_item){
                                        //     $totalDiscount += $rewards_discount_amount_item;
                                        // }
                                        // $discountPer =  $totalDiscount * 100 / $price;

                                        // $params['discount' . $key] = $discountPer;

                                        // if($item->getDiscountPercent() != '0.0000'){
                                        //     $params['discount' . $key] = $item->getDiscountPercent();
                                        // }else{
                                        //     if($item->getDiscountAmount() != '0.0000'){
                                        //         $discountAmount = (float) $item->getDiscountAmount() / (float) $item->getQtyOrdered();
                                        //         $params['price' . $key] = (float) $params['price' . $key] -  $discountAmount;
                                        //     }
                                        // }

                                        // if($rewards_discount_amount_item){
                                        //     $params['price' . $key] = (float) $params['price' . $key] -  $rewards_discount_amount_item;
                                        // }

                                        if(isset($product['discount_amount'])){
                                            $discountAmount += (float) $product['discount_amount'];
                                        }

                                        $key++;
                                    }

                                    if($order->getShippingAmount()) {
                                        $s = Mage::getModel('sales/quote_address_rate')->getCollection();
                                        foreach ( $s as $s1 ) {
                                            if ( $s1->getCode() == $order->getShippingMethod()) {
                                                break;
                                            }
                                        }
                                        $shippingDescription = isset($s1["method_title"]) ? '(' . $s1["method_title"] . ')' : '';
                                        $params['itemName' . $key] = 'Shipping ' . $shippingDescription;
                                        $params['amount' . $key] = 1;
                                        $params['price' . $key] = $order->getShippingAmount();

                                        $key++;
                                    }

                                    if($discountAmount){
                                        $params['itemName' . $key] = 'Discount ';
                                        $params['amount' . $key] = 1;
                                        $params['price' . $key] = $discountAmount * -1;
                                    }



                                    echo "<pre>"; print_r($params);exit;

                                    $responseJson = $erplyModel->sendRequest('saveSalesDocument', $params);
                                    $response = json_decode($responseJson, true);

                                    echo '<br>Update Successfully. Response: ';
                                    
                                    Mage::log('Erply New Payment Status : PAID', null,'sales_document_update.log');
                                    Mage::log('Erply New Total : '.$response['records'][0]['total'], null,'sales_document_update.log');

                                    echo "<pre>"; print_r($response);
                                }

                            }
                            
                        }
                        unset($response);
                        exit;
                        $salesUpdatePageNo++;
                    }
                }
            }*/
        }

       // exit;

       // Load layout
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        // Add left sidebar
        $this->_addLeft(
        $this->getLayout()
        ->createBlock('Eepohs_Erply_Block_SidebarBlock')
        );
        // Render layout
        $this->renderLayout();
    }

    public function scheduleImportAction() {

        $data = $this->getRequest()->getPost();
        $queueData["type"] = $data["import_type"];
        $queueData["storeId"] = $data["store_id"];
        /*
         * Schedule Mass Import to next cron runtime.
         */
        $runEvery = Mage::getStoreConfig('eepohs_erply/queue/run_every', $data["store_id"]);
//        $scheduleDate = $data["scheduled_ate"];
        $now = Mage::getModel('core/date')->gmtTimestamp();
//        Mage::helper('Erply')->log("Timestamp: ".$now);
        $minutes = date('i', $now);
        $minutes = round(($minutes+$runEvery/2)/$runEvery)*$runEvery;
        $hours = date('H',$now);
        if($minutes < 10) {
            $minutes = "0".$minutes;
        }
        if($minutes == 60) {
            $minutes = "00";
            $hours = date("H", strtotime('+1 hours',$now));
        }

        $scheduleDateTime = date('Y-m-d '.$hours.':'.$minutes.':00', $now);
        $queueData["scheduleDateTime"] = $scheduleDateTime;
        /*
         * If there are any Queue's with same run code, then let's delete them
         */
        Mage::getModel('Erply/Queue')->deleteQueueByCode('erply_'.$queueData["type"]);

        Mage::getModel('Erply/Queue')->addQueue($queueData);
        Mage::getSingleton('core/session')->addSuccess(Mage::helper('Erply')->__('Import for %s has been scheduled at %s!', $queueData["type"], $queueData["scheduleDateTime"]));
        //$this->_redirectUrl($this->getUrl('Erply/Index'));
        $this->_redirect('Erply/Erply', $arguments=array());
        //$this->_redirect();
    }
}
