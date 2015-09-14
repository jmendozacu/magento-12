<?php
class AW_Advancedreports_Helper_Additional_Customersbycountry extends Mage_Core_Helper_Abstract
{
    const ROUTE_ADDITIONAL_CUSTOMERSBYCOUNTRY = 'additional_customersbycountry';

    public function getChartParams($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_CUSTOMERSBYCOUNTRY] = array(
            array( 'value'=>'base_grand_total', 'label'=>'Total' ),
        );
        if (isset($params[$key])){
            return $params[$key];
        }
        return null;
    }

    public function getNeedReload($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_CUSTOMERSBYCOUNTRY] = false;
        if (isset($params[$key])){
            return $params[$key];
        }
        return null;
    }

    public function getNeedTotal($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_CUSTOMERSBYCOUNTRY] = true;
        if (isset($params[$key])){
            return $params[$key];
        }
        return null;
    }

    public function getUsersByCountry($date_from, $date_to, $countryCode, $storeIds)
    {
        $select = Mage::getModel('customer/customer')->getCollection()->getSelect();
        $addressTable = Mage::helper('advancedreports/sql')->getTable('customer_address_entity_varchar');
        $entityTypeId = Mage::getModel('eav/entity')->setType('customer_address')->getTypeId();
        $attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode($entityTypeId, 'country_id');
        $select
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(
                array(
                    'users_count' => new Zend_Db_Expr('COUNT(e.entity_id)')
                )
            )
            ->join(
                array('address' => $addressTable),
                "address.entity_id = e.entity_id "
                . "AND address.attribute_id = " . $attributeModel->getId(),
                array()
            )
            ->where("e.created_at >= ?", $date_from)
            ->where("e.created_at <= ?", $date_to)
            ->where("e.is_active = ?", 1)
            ->where('IFNULL(address.value,"' . Mage::helper('advancedreports')->__('Not set') . '") = ?', $countryCode)
            ->group('IFNULL(address.value,"' . Mage::helper('advancedreports')->__('Not set') . '")')
        ;
        if ($storeIds) {
            $select->where("e.store_id in ('".implode("','", $storeIds)."')");
        }
        $read = Mage::helper('advancedreports')->getReadAdapter();
        return $read->fetchOne($select->__toString());
    }
}