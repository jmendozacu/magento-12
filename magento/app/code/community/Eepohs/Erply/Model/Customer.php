<?php
class Eepohs_Erply_Model_Customer extends Mage_Core_Model_Abstract
{
    public function getCustomerExists($email, $storeId) {
        if($email) {
            $c = Mage::getModel('Erply/Erply');
            $c->verifyUser($storeId);
            $params = array(
                'searchName'  =>  $email
            );
            $erplyCalls++;
            $response = $c->sendRequest('getCustomers', $params);
            $response = json_decode($response, true);
            Mage::log('Eepohs_Erply_Model_Customer getCustomerExists() Total API Calls: 1',null,'erply_limit.log');
            if(isset($response["records"]) && isset($response["records"][0]["customerID"])){
                if(count($response["records"]) > 0 && $response["records"][0]["customerID"] > 0) {
                    return $response["records"][0]["customerID"];
                }    
            }
            return false;
        }
    }

    public function sendCustomerFromOrder($customer, $storeId){
        $c = Mage::getModel('Erply/Erply');
        $c->verifyUser($storeId);
        $params = array();
        $customerID = $this->getCustomerExists($customer['email'],$storeId);
        if($customer instanceof Mage_Customer_Model_Customer) {
        $params = array(
            'firstName' => $customer['firstName'],
            'lastName'  =>  $customer['lastName'],
            'email'     =>  $customer['email']
        );
        } else {
            $params = $customer;
        }
        if($customerID) {
            $params["customerID"] = $customerID;
        }
        $customerData = $c->sendRequest('saveCustomer', $params);
        $customerData = json_decode($customerData, true);
        Mage::log('Eepohs_Erply_Model_Customer sendCustomerFromOrder() Total API Calls: 1',null,'erply_limit.log');
        if($customerData["status"]["responseStatus"] == "ok") {            
            return $customerData["records"][0]["customerID"];
        } else {
            return false;
        }
    }

    public function sendCustomer($customer, $storeId) {
        $c = Mage::getModel('Erply/Erply');
        $c->verifyUser($storeId);
        $params = array();
        $customerID = $this->getCustomerExists($customer->getEmail(),$storeId);
        if($customer instanceof Mage_Customer_Model_Customer) {
            $params = array(
                'firstName' => $customer->getFirstname(),
                'lastName'  =>  $customer->getLastname(),
                'email'     =>  $customer->getEmail()
            );
        } else {
            $params = $customer;
        }
        if($customer->getData('dob')) {
            $params["birthday"] = $customer->getData('dob');
        }
        if($customerID) {
            $params["customerID"] = $customerID;
        }
        $customerData = $c->sendRequest('saveCustomer', $params);
        $customerData = json_decode($customerData, true);        
        Mage::log('Eepohs_Erply_Model_Customer sendCustomer() Total API Calls: 1',null,'erply_limit.log');
        if($customerData["status"]["responseStatus"] == "ok") {
            return $customerData["records"][0]["customerID"];
        } else {
            return false;
        }
    }

    public function addNewCustomer($customerId, $storeId) {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        return $this->sendCustomer($customer, $storeId);
    }

    public function addNewWebsiteCustomer($customerarr,$storeId) {
        Mage::app('default');
        $customer = Mage::getModel("customer/customer");
        $websiteId = 2;
        $storeId  =2;
        $store = Mage::getModel('core/store')->load($storeId);
        if ($websiteId) {
            $customer->setWebsiteId($websiteId);
        }
        $customer->loadByEmail($customerarr['email']);
        if ($customer->getId()) {
            // Aleady exist
        }else{
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setGroupId('1')
                ->setFirstname($customerarr['firstName'])
                ->setLastname($customerarr['lastName'])
                ->setEmail($customerarr['email']);
            $customer->save();
            $address = Mage::getModel("customer/address");
            $address->setCustomerId($customer->getId())
                    ->setFirstname($customerarr->$customerarr['firstName'])
                    ->setLastname($customerarr->$customerarr['lastName'])
                    ->setCountryId($customerarr['country'])
                    ->setPostcode($customerarr['postalCode'])
                    ->setCity($customerarr['city'])
                    ->setTelephone($customerarr['phone'])
                    ->setFax($customerarr['fax'])
                    ->setCompany($customerarr['companyName'])
                    ->setStreet($customerarr['street'])
                    ->setIsDefaultBilling('1')
                    ->setIsDefaultShipping('1')
                    ->setSaveInAddressBook('1');
             
            $address->save();
        }
        return true;
    }
}