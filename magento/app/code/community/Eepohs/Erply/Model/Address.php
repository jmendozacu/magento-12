<?php
class Eepohs_Erply_Model_Address extends Eepohs_Erply_Model_Erply
{
	private $attrName;
	private $attrType;
	private $erpTypeID;

    public function _construct()
    {

    }

    protected function getExistingAddress($customerId, $typeId, $storeId) {        
        $params = array(
            'ownerID'   =>  $customerId,
            'typeID'    =>  $typeId
        );
        $reponse = $this->sendRequest('getAddresses', $params);
        $reponse = json_decode($reponse, true);
        Mage::log('Eepohs_Erply_Model_Address getExistingAddress() Total API Calls: 1',null,'erply_limit.log');
        if(isset($reponse["records"]) && count($reponse["records"]) > 0) {
            return $reponse["records"][0]["addressID"];
        } else {
            return false;
        }

    }

    public function saveCustomerAddress($customerId, $typeId, $data, $storeId) {
        $this->verifyUser($storeId);
        $params = array(
            'ownerID'   =>  $customerId,
            'typeID'    =>  $typeId,
            'street'    =>  $data["street"],
            'city'      =>  $data["city"],
            'postalCode'  =>  $data["postcode"],
            'state'     =>  $data["region"],
            'country'   =>  $data["country_id"]

        );
        if($addressId = $this->getExistingAddress($customerId, $typeId, $storeId)) {
            $params["addressID"] = $addressId;
        }
        $erplyCalls++;
        $reponse = $this->sendRequest('saveAddress', $params);
        $reponse = json_decode($reponse, true);
        Mage::log('Eepohs_Erply_Model_Address saveCustomerAddress() Total API Calls: 1',null,'erply_limit.log');
        if(isset($reponse["records"]) && count($reponse["records"]) > 0) {
            return $reponse["records"][0]["addressID"];
        } else {
            return false;
        }        
    }
}