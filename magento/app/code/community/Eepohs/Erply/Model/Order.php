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
/**
 * Created by Rauno Väli
 * Date: 27.03.12
 * Time: 10:25
 */
class Eepohs_Erply_Model_Order extends Mage_Core_Model_Abstract
{

    private $_storeId;
    protected static $_defaultInvoiceState = 'PENDING';
    protected static $_invoiceStatesAry = array(
        'PENDING' => 'PENDING',
        'PROCESSING' => 'READY'
    );

    public function _construct()
    {
        parent::_construct();
    }

    public function prepareOrder($order, $erpOrder = false, $storeId)
    {

        $this->_storeId = $storeId;

        Mage::helper('Erply')->log("Customer ID on order: " . $order['customer_id']);
        Mage::helper('Erply')->log($order["billing_address"]);
        $this->_data = array();
        $erpAttributes = array();

        // if order is synchronized then update
        if ( $erpOrder ) {
            $this->_data['id'] = $erpOrder['id'];
            if ( isset($this->_data['attributes']) ) {
                $erpAttributes = $this->_data['attributes'];
                $this->offsetUnset('attributes');
            }
        }

        // Customer may or may not exist. In case of guest checkout there is no
        // customer record and we have to make a new Erply customer by Magento
        // billing information.
        if ( isset($order['customer_id']) && !empty($order['customer_id']) ) {
            // Customer exists. Synchronize customer before procceeding.
            $customer = Mage::getSingleton('Erply/Customer');            
            $customerId = $customer->getCustomerExists($order['customer_email'], $storeId);
            Mage::log('customerId:'.$customerId,null,'erp-error.log');
            // check if order customer synchronized
            if ( !$customerId ) {
                $customerId = $customer->addNewCustomer($order['customer_id'], $storeId);
                Mage::log('customerId:'.$customerId,null,'erp-error.log');                
                if (!$customerId) {
                    Mage::helper('Erply')->log(sprintf('%s(%s): Couldn not add new customer', __METHOD__, __LINE__));
                }
            }


            if ( !empty($customerId) ) {
                $this->_data['customerID'] = $this->_data['payerID'] = $customerId;

                // payerAddressID
                $address = Mage::getModel('Erply/Address');
                $billingAddressTypeId = Mage::getStoreConfig('eepohs_erply/customer/billing_address', $this->_storeId);
                $shippingAddressTypeId = Mage::getStoreConfig('eepohs_erply/customer/shipping_address', $this->_storeId);

                Mage::log('billingAddressTypeId:'.$billingAddressTypeId,null,'erp-error.log');
                Mage::log('shippingAddressTypeId:'.$shippingAddressTypeId,null,'erp-error.log');

                $this->_data['payerAddressID'] = $address->saveCustomerAddress($customerId, $billingAddressTypeId, $order["billing_address"], $this->_storeId);
                $this->_data['addressID'] = $address->saveCustomerAddress($customerId, $shippingAddressTypeId, $order["shipping_address"], $this->_storeId);
                Mage::log("this->_data",null,'erp-error.log');
                Mage::log($this->_data,null,'erp-error.log');
            }
        } else {
            //Customer Checkout
            $customerData = array(
                'firstName' => $order['billing_address']['firstname']
                , 'lastName' => $order['billing_address']['lastname']
                , 'email' => $order['customer_email']
                , 'phone' => $order['billing_address']['telephone']
                , 'fax' => $order['billing_address']['fax']
                , 'notes' => 'Created from Magento'
            );
            
            $customerId = Mage::getModel('Erply/Customer')->sendCustomerFromOrder($customerData, $this->_storeId);
            if ( !empty($customerId) ) {
                $this->_data['customerID'] = $this->_data['payerID'] = $customerId;

                // payerAddressID
                $address = Mage::getModel('Erply/Address');
                $billingAddressTypeId = Mage::getStoreConfig('eepohs_erply/customer/billing_address', $this->_storeId);
                $shippingAddressTypeId = Mage::getStoreConfig('eepohs_erply/customer/shipping_address', $this->_storeId);

                $this->_data['payerAddressID'] = $address->saveCustomerAddress($customerId, $billingAddressTypeId, $order["billing_address"], $this->_storeId);
                $this->_data['addressID'] = $address->saveCustomerAddress($customerId, $shippingAddressTypeId, $order["shipping_address"], $this->_storeId);
            }
        }

        // type
        $this->_data['type'] = 'INVWAYBILL'; //$this->erpOrderType;

        // currencyCode
        $this->_data['currencyCode'] = $order['order_currency_code'];

        // date
        $this->_data['date'] = date('Y-m-d', strtotime($order['created_at']));

        // time
        $this->_data['time'] = date('H:m:s', strtotime($order['created_at']));

        // invoiceState
        $orderState = strtoupper($order['status']);
        $this->_data['invoiceState'] = isset($this->_invoiceStatesAry[$orderState]) ? self::$_invoiceStatesAry[$orderState] : self::$_defaultInvoiceState;

        // internalNotes
        $this->_data['internalNotes'] = 'Magento Order #' . $order['increment_id'];

        // Get gift message into internal notes
        if ( !empty($order['gift_message_id']) ) {
            $giftMessageMod = Mage::getModel('giftmessage/message')->load((int) $order['gift_message_id']);
            $this->_data['internalNotes'] .= "\n\n" . sprintf('
Gift Message
--------------------------------
From: %s
To: %s
Message: %s', $giftMessageMod->getSender(), $giftMessageMod->getRecipient(), $giftMessageMod->getMessage());
        }

        // invoiceNo. Only set if new order and number must be numeric.
        if ( !isset($this->_data['id'])
            // && (int)Mage::getStoreConfig('erply/sync_config/use_magento_document_no') == 1
            && is_numeric($order['increment_id'])
        ) {
            $this->_data['invoiceNo'] = $order['increment_id'];
        }

        // Employee
        $employeeId = 0;
        if ( !empty($employeeId) ) {
            $this->_data['employeeID'] = $employeeId;
        }

        // Warehouse
        $erplyWarehouseId = (int) Mage::getStoreConfig('eepohs_erply/product/warehouse',0);
        if ( $erplyWarehouseId > 0 ) {
            $this->_data['warehouseID'] = $erplyWarehouseId;
        }

        // Payment type
        $this->_data['paymentType'] = "CARD";
        //$this->_data['paymentStatus'] = "PAID";
        
        // Confirmed
        $this->_data['confirmInvoice'] = 1;
        $this->_data['purchaseOrderDone'] = 1;
        //echo "<pre>"; print_r($this->_data);exit;
        
        /*
         * Items
         */
        $productModel = Mage::getModel('Erply/Product');

        $key = 1;
        //$erpVatrates = $this->getVatRates();
        
        $orderIncrementId = $order['increment_id'];
        $orderData = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $items = $orderData->getAllVisibleItems();

        $key = 0;
        foreach ($items as $item) {
            $erpProductId = null;                
            if($erpProduct = $productModel->findProductBySKU($item->getSku())){
                if(isset($erpProduct['productID'])){
                    $erpProductId = $erpProduct['productID'];
                }                        
            }
            if(isset($erpProductId) && !empty($erpProductId) ) {
                $this->_data['productID' . $key] = $erpProductId;
            }

            if($item->getName()){
                $this->_data['itemName' . $key] = $item->getName();
            }

            if($item->getQtyOrdered()){
                $this->_data['amount' . $key] = (int) $item->getQtyOrdered();
            }

            if($item->getPriceInclTax()){
                $this->_data['price' . $key] = (float) $item->getPrice();
            }

            $key++;
        }

        if (isset($order['shipping_amount'])){
            $this->_data['itemName' . $key] = 'Shipping: ' . $order['shipping_description'];
            $this->_data['amount' . $key] = 1;
            $this->_data['price' . $key] = $order['shipping_amount'];
            $key++;
        }

        if ($order['tax_amount'] != '0.0000'){
            $this->_data['itemName' . $key] = 'Tax: ';
            $this->_data['amount' . $key] = 1;
            $this->_data['price' . $key] = $order['tax_amount'];
            $key++;
        }

        if ($order['discount_amount'] != '0.0000'){
            $this->_data['itemName' . $key] = 'Discount: ' . $order['coupon_code'];
            $this->_data['amount' . $key] = 1;
            $this->_data['price' . $key] = (float) $order['discount_amount'];
            $key++;
        }

        if ($order['rewards_discount_amount'] != '0.0000'){
            $this->_data['itemName' . $key] = 'Reward Discount: ';
            $this->_data['amount' . $key] = 1;
            $this->_data['price' . $key] = ((float) $order['rewards_discount_amount']) * -1;
            $key++;
        }

        $this->_data['notes'] = $order["shipping_description"];

        return $this->_data;
    }



    protected function getVatRates()
    {
        $erplyCalls = 0;

        $erply = Mage::getModel('Erply/Erply');
        $erply->verifyUser($this->_storeId);
        $erplyCalls++;
        $vatRates = $erply->sendRequest('getVatRates');
        $vatRates = json_decode($vatRates, true);
        Mage::log('Eepohs_Erply_Model_Order getVatRates() Total API Calls:'.$erplyCalls,null,'erply_limit.log');
        if ( $vatRates["status"]["responseStatus"] == "ok" ) {
            return $vatRates["records"];
        } else {
            return false;
        }
    }

    /*
     * Function converts input associative array data to plain array with one input array value as key
     * and array of values as value
     * @param $array|array - array to convert
     * @param $key|string - the name of $array value to become a key
     * @param $value|array - an array of name values of $array to become a value
     * @return $newarray|array
     */

    protected function toKeyValueArray($array, $key, $valueArr)
    {
        $newarray = array();
        foreach ( $array as $item ) {
            foreach ( $valueArr as $value ) {
                if ( count($valueArr) == 1 ) {
                    $newarray[$item[$key]] = $item[$value];
                } else {
                    $newarray[$item[$key]][$value] = $item[$value];
                }
            }
        }
        return $newarray;
    }

}
