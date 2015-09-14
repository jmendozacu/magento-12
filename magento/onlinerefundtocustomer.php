<?php 
require_once 'app/Mage.php';
ini_set('memory_limit', '1024M');
error_reporting(E_ALL);
ini_set('display_errors', 1);
umask(0);
Mage::app();
Mage::register('isSecureArea', true);

//date_default_timezone_set("America/New_York");

Mage::log('Online Refund CronJob started',null,'onlinerefundtocustomer.log');
Mage::log('GMT Time Now : '.date('Y-m-d H:i:s'),null,'onlinerefundtocustomer.log');

/*$time = time();
$to = date('Y-m-d H:i:s', $time);
$lastTime = $time - 86400; // 60*60*24
$from = date('Y-m-d H:i:s', $lastTime);
$time_id = '';
$lastRunTime = '';

$read = Mage::getSingleton('core/resource')->getConnection('core_read');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');

$select = $read->select()->from('online_refund_script_time', array('*'));
$rowArray =$read->fetchRow($select); 

if(isset($rowArray) && !empty($rowArray)){
    $from = $rowArray['todatetime'];
    $time_id = $rowArray['id'];
    $lastRunTime = $rowArray['todatetime'];
} 
else{
    $write->beginTransaction();
    $__fields = array();
    $__fields['fromdatetime'] = $from;
    $__fields['todatetime'] = $to;
    $write->insert('online_refund_script_time', $__fields);
    $write->commit();
    
    $select = $read->select()->from('online_refund_script_time', array('*'));
    $rowArray =$read->fetchRow($select);    
    $time_id = $rowArray['id'];
}

$totalDiff = '';
$totalHour = '';

if($lastRunTime != ''){
    $lastRunTimeObj = new DateTime($lastRunTime);
    $toObj = new DateTime($to);
    $interval = $lastRunTimeObj->diff($toObj);
    $totalDiff = $interval->format('%a');
    $totalHour = $interval->h;
}
else{
    $totalDiff = '1';
}*/

//Mage::log('Last Run Hours Difference : '.$totalHour,null,'onlinerefundtocustomer.log');

// if($totalDiff > 0){
//if($totalHour > 5){
//if(($totalHour > 8 && strtotime(date('Y-m-d')) == strtotime('2015-08-21')) || $totalHour > 23){
if(1){

    Mage::log('Refund Process Started ....',null,'onlinerefundtocustomer.log');

    $orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', 'processing')->addAttributeToSelect('*');
    $is_any_refunded = false;
    foreach ($orders as $order) {

        $orderedItems = $order->getAllVisibleItems(); 
        $totalOrderItems = count($orderedItems);

        foreach($orderedItems as $item){
            if($item->getQtyRefunded() > 0){
                continue;
            }

            $itemSku = $item->getSku();     
            $itemQtyOrdered = $item->getQtyOrdered();  
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku',$itemSku);

            if($product){
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $productShippingFrom = $product->getAttributeText('shipping_from');
                $productQty = $stock->getQty();
                $is_in_stock =  $stock->getIsInStock();
                $in_stock = true;
                if($productQty == 0 || $is_in_stock == 0){
                    $in_stock = false;
                } 

                if($productShippingFrom == 'DropShip' && $in_stock == false){
                // if($itemSku == '11020-Gold' && $order->getIncrementId() == '200005568'){
                    echo $order->getRealOrderId().'<br/>';
                    $data = array(
                        'qtys' => array(
                            $item->getId() => $itemQtyOrdered,
                        ),
                        'do_offline' => 0,
                        'comment_text' => $product->getName().' is out of stock.',
                        'send_email'=> 1,
                        'adjustment_positive' => 0,
                        'adjustment_negative' => 0,
                        'comment_customer_notify'=>1,
                        'is_visible_on_front'=>0,

                    );
                    
                    if($totalOrderItems > 1){
                        $refundCnt = 0;
                        foreach ($orderedItems as $_item) {
                            if($_item->getQtyRefunded() > 0){
                                $refundCnt++;
                            }
                        }
                        if($totalOrderItems != $refundCnt + 1){
                            $data['shipping_amount'] = '0';
                        }
                    }

                    $creditmemo = false;
                    $orderId = $order->getId();

                    $invIncrementIDs = array();
                    if ($order->hasInvoices()) {
                        foreach ($order->getInvoiceCollection() as $inv) {
                            $invIncrementIDs[] = $inv->getId();
                        }
                    }

                    $invoiceId = $invIncrementIDs[0];
                    
                    $invoice = false;
                    if ($invoiceId) {
                        $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId)->setOrder($order);
                    }

                    if (!$order->canCreditmemo()) {
                        echo 'Cannot create credit memo for the order.';continue;
                    }

                    $qtys = array();
                    $backToStock = array();
                    
                    // echo '<pre>';print_r($data);exit;
                    $service = Mage::getModel('sales/service_order', $order);
                    if ($invoice) {
                        $creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);
                    } else {
                        $creditmemo = $service->prepareCreditmemo($data);
                    }

                    if ($creditmemo) {
                        if (($creditmemo->getGrandTotal() <=0) && (!$creditmemo->getAllowZeroGrandTotal())) {
                            echo 'Credit memo total must be positive.';continue;
                        }

                        $comment = '';
                        if (!empty($data['comment_text'])) {
                            
                            $creditmemo->addComment($data['comment_text'],isset($data['comment_customer_notify']),isset($data['is_visible_on_front']));
                            
                            if (isset($data['comment_customer_notify'])) {
                                $comment = $data['comment_text'];
                            }
                        }
                        
                        if (isset($data['do_refund'])) {
                            $creditmemo->setRefundRequested(true);
                        }
                        if (isset($data['do_offline'])) {
                            $creditmemo->setOfflineRequested((bool)(int)$data['do_offline']);
                        }

                        try{
                            $creditmemo->register();    
                        }
                        catch(Exception $e){
                            continue;
                        }

                        if (!empty($data['send_email'])) {
                            $creditmemo->setEmailSent(true);
                        }

                        $creditmemo->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));

                        $transactionSave = Mage::getModel('core/resource_transaction')->addObject($creditmemo)->addObject($creditmemo->getOrder());
                        if ($creditmemo->getInvoice()) {
                            $transactionSave->addObject($creditmemo->getInvoice());
                        }
                        $transactionSave->save();


                        $creditmemo->sendEmail(!empty($data['send_email']), $comment);
                        Mage::log('Order Id : '.$order->getRealOrderId().' , Item Sku : '.$itemSku.' , Action : Refund',null,'onlinerefundtocustomer.log');
                        $is_any_refunded = true;
                        echo 'Order Id : '.$order->getRealOrderId().'<br/>';
                        echo 'Item Sku : '.$itemSku.'<br/>';
                        echo 'The credit memo has been created.<hr/>';

                    }
                }
            }
            
        }
    }

    /*$write->beginTransaction();
    $__fields = array();
    $__fields['fromdatetime'] = $from;
    $__fields['todatetime'] = $to;
    $__where = $write->quoteInto('id =?', $time_id);
    $write->update('online_refund_script_time', $__fields, $__where);
    $write->commit();*/

    if(!$is_any_refunded){
        echo 'There are no order items to refund.';
    }

}

Mage::log('Online Refund CronJob Finished',null,'onlinerefundtocustomer.log');
echo "done";