<?php

class AW_Advancedreports_Helper_Additional_Salesbyproductattr extends Mage_Core_Helper_Abstract
{
    const ROUTE_ADDITIONAL_SALESBYPRODUCTATTR = 'additional_salesbyproductattr';

    public function getChartParams($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYPRODUCTATTR] = array(
            array('value' => 'base_row_total', 'label' => 'Total'),
        );
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

    public function getNeedReload($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYPRODUCTATTR] = false;
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

    public function getNeedTotal($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYPRODUCTATTR] = true;
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

}