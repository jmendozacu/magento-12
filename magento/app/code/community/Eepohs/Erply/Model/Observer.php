<?php
class Eepohs_Erply_Model_Observer extends Mage_Core_Model_Abstract
{
    const XML_PATH_SCHEDULE_AHEAD_FOR = 'system/cron/schedule_ahead_for';

    public function sendOrder($observer) {   
        $event = $observer->getEvent();
        $order = $event->getInvoice()->getOrder();
        $incrementId = $order->getIncrementId();
        $storeId = $order->getStoreId();
        if(Mage::getStoreConfig('eepohs_erply/account/disable_order', $storeId)) {
            Mage::helper('Erply')->log("Sending order to erply is disabled for store: #".$storeId);
            return false;
        }
        if($incrementId){
            try {
                $orderData = Mage::getModel('sales/order_api')->info($incrementId);
                $e = Mage::getModel('Erply/Erply');
                $storeId = $order->getStoreId();
                $e->verifyUser($storeId);
                $data = Mage::getModel('Erply/Order')->prepareOrder($orderData, false, $storeId);
                if ($data) {
                    $response = $e->sendRequest('saveSalesDocument', $data);
                    Mage::helper('Erply')->log("Saving order data to erply:" . print_r($data, true));
                    $response = json_decode($response, true);
                    Mage::log('Eepohs_Erply_Model_Observer sendOrder() Total API Calls: 1',null,'erply_limit.log');
                    if($response["status"]["responseStatus"] == "ok") {
                        $documentId = $response["records"][0]["invoiceID"];
                        $this->sendPayment($data, $storeId, $documentId);
                        Mage::helper('Erply')->log("Erply reponse on order save:" . var_export($response, true));
                        Mage::helper('Erply')->log("Saved order to erply: #" . $response['records'][0]['invoiceID']);
                        Mage::helper('Erply')->log("Erply documentId is: ".$documentId);
                        
                        if(isset($response['records'][0]['invoiceID'])){
                            return $response['records'][0]['invoiceID'];    
                        }else{
                            return false;
                        }
                    }else{
                        return false;
                    }
                } else {
                    Mage::helper('Erply')->log("Failed to send order");
                    return false;
                }
            } catch (Exception $e) {
                Mage::helper('Erply')->log("Failed to send order to Erply with message: ".$e->getMessage());
                Mage::helper('Erply')->log("Exception trace: ".$e->getTraceAsString());
            }
        }
    }

    protected function sendPayment($orderData, $storeId, $documentId) {
        try {
            $e = Mage::getModel('Erply/Erply');
            $e->verifyUser($storeId);
            $orderData["documentID"] = $documentId;
            $paymentData = Mage::getModel('Erply/Payment')->preparePayment($orderData, $storeId);
            if($paymentData) {
                Mage::helper('Erply')->log("Erply - sending payment data: ".print_r($paymentData, true));
                $erplyCalls++;
                $response = $e->sendRequest('savePayment', $paymentData);
                $response = json_decode($response, true);
                Mage::log('Eepohs_Erply_Model_Observer sendPayment() Total API Calls: 1',null,'erply_limit.log');
                Mage::helper('Erply')->log("Erply payment saving reponse: ".print_r($response, true));
                if($response["status"]["responseStatus"] == "ok") {
                    Mage::helper('Erply')->log("Erply payment saving was successful");
                }
            }
        } catch (Exception $e) {
            Mage::helper('Erply')->log("Failed to create payment for order: ".$e->getMessage());
        }
    }
}