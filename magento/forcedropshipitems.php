<?php 
require_once 'app/Mage.php';
ini_set('memory_limit', '1024M');
error_reporting(E_ALL);
ini_set('display_errors', 1);
umask(0);
Mage::app();
Mage::register('isSecureArea', true);

//date_default_timezone_set("America/New_York");

$fromDateTime = date('Y-m-d H:i:s');
$toDateTime = date('Y-m-d H:i:s');
$timeId = '';

$read = Mage::getSingleton('core/resource')->getConnection('core_read');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$select = $read->select()->from('dropship_orders_script_time', array('*'));
$rowArray =$read->fetchRow($select);

if(isset($rowArray) && !empty($rowArray)){
    $timeId = $rowArray['id'];
    $fromDateTime = $rowArray['fromdatetime'];
    $toDateTime = $rowArray['todatetime'];
}else{
    $write->beginTransaction();
    $__fields = array();
    $__fields['fromdatetime'] = $fromDateTime;
    $__fields['todatetime'] = $toDateTime;
    $write->insert('dropship_orders_script_time', $__fields);
    $write->commit();
    
    $select = $read->select()->from('dropship_orders_script_time', array('*'));
    $rowArray =$read->fetchRow($select);    
    $time_id = $rowArray['id'];
}


Mage::log('Force Drop Ship started',null,'dropship.log');
Mage::log('GMT Time Now : '.date('Y-m-d H:i:s'),null,'dropship.log');


if(1){

    Mage::log('Creating Email ...... ',null,'dropship.log');

    $_orderCollection = Mage::getModel('sales/order')->getCollection()
       ->addAttributeToSelect('*')
       ->addFieldToFilter('status', Mage_Sales_Model_Order::STATE_PROCESSING)
       ->addAttributeToFilter('created_at', array('from' => $fromDateTime,'to' => $toDateTime))
       ->addAttributeToSort('created_at', 'DESC')
       ->load();

    $cnt = 1;

    $table = '<table border=1>';
    $table .= '<tr>';
    $table .= '<th>Order Id</th><th>Product Name</th><th>Product Sku</th><th>Product Quantity</th>';
    $table .= '</tr>';

    $filename = 'dropship_items_report.csv';
    $file = fopen($filename,"w");
    fputcsv($file,array('Order Id','Product Name','Product Sku','Product Quantity'));

    foreach ($_orderCollection as $order) {
        $order_id = $order->getRealOrderId();

        $ordered_items = $order->getAllVisibleItems();

        foreach ($ordered_items as $item) {
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku',$item->getSku());
            if($product){
                $shippingFrom = $product->getAttributeText('shipping_from');
                if($shippingFrom == 'DropShip'){
                    $productId = $product->getId();
                    $productName = $product->getName();
                    $productSku = $product->getSku();
                    $productQty = $item->getQtyOrdered();

                    $table .= '<tr>';
                    $table .= '<td>'.$order_id.'</td><td>'.$productName.'</td><td>'.$productSku.'</td><td>'.$productQty.'</td>';
                    $table .= '</tr>';

                    fputcsv($file,array($order_id,$productName,$productSku,$productQty));
                    

                }   
            }
        }
        $cnt++;
    }

    $table .= '</table>';

    $today = date("j F, Y");

    $subject = "TheVapeStoreOnline - Dropship order item on ".$today;
    $message = '';
    if($_orderCollection->count() == 0){
        $message = "<html><body><p>There are no orders of dropship product for today!</p></body></html>";
    }
    else{
        $message = "<html><body><p>Please find below dropship order items!</p>".$table."</body></html>";
    }

    require_once 'mandrill/Mandrill.php';
    $mandrill = new Mandrill('zyXUZzLudN97bnWt_fYBCA');
    try {
        $message = array(
            'html' => $message,
            'subject' => $subject,
            'from_email' => 'sales@thevapestoreonline.com',
            'from_name' => 'TheVapeStoreOnline',
            'to' => array(
                array(
                    'email' => 'dharmesh.php@gmail.com',
                    'name' => 'Dharmesh',
                    'type' => 'to'
                ),
                array(
                    'email' => 'rohan@quicknetsoft.com',
                    'name' => 'Rohan',
                    'type' => 'to'
                ),
                array(
                    'email' => 'danny@vaporin.com',
                    'name' => 'Danny',
                    'type' => 'to'
                ),
                array(
                    'email' => 'danny@vpco.com',
                    'name' => 'Danny',
                    'type' => 'to'
                ),
                array(
                    'email' => 'dmozlin@gmail.com',
                    'name' => 'Danny',
                    'type' => 'to'
                ),
                array(
                    'email' => 'Bbal@vpco.com',
                    'name' => 'Bbal',
                    'type' => 'to'
                ),
            ),
            'headers' => array('Reply-To' => 'sales@thevapestoreonline.com'),
            'important' => false,
            'track_opens' => null,
            'track_clicks' => null,
            'auto_text' => null,
            'auto_html' => null,
            'inline_css' => null,
            'url_strip_qs' => null,
            'preserve_recipients' => null,
            'view_content_link' => null,
            //'bcc_address' => 'dharmesh.php@gmail.com',
            'tracking_domain' => null,
            'signing_domain' => null,
            'return_path_domain' => null,
            'merge' => true,
            'merge_language' => 'mailchimp',
        );
        $result = $mandrill->messages->send($message);
        //print_r($result);
    } catch(Mandrill_Error $e) {
        //echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
        throw $e;
    }

    $write->beginTransaction();     
    $__fields = array();
    $__fields['fromdatetime'] = $toDateTime;
    $__fields['todatetime'] = $currentDateTime;
    $__where = $write->quoteInto('id =?', $timeId);
    $write->update('dropship_orders_script_time', $__fields, $__where);     
    $write->commit();

    Mage::log('Sent Email Via Mandrill ',null,'dropship.log');
    Mage::log($result,null,'dropship.log');

    
    Mage::log('Database Updated ',null,'dropship.log');

}

Mage::log('Force Drop Ship Ended',null,'dropship.log');

echo "done";