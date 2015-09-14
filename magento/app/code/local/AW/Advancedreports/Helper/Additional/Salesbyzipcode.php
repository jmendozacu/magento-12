<?php
class AW_Advancedreports_Helper_Additional_Salesbyzipcode extends Mage_Core_Helper_Abstract
{
    const ROUTE_ADDITIONAL_SALESBYZIPCODE = 'additional_salesbyzipcode';

    public function getChartParams($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYZIPCODE] = array(
            array('value' => 'base_grand_total', 'label' => 'Total'),
        );
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

    public function getNeedReload($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYZIPCODE] = false;
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

    public function getNeedTotal($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYZIPCODE] = true;
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

}


