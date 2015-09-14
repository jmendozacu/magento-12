<?php
class AW_Advancedreports_Helper_Additional_Salesbycategory extends Mage_Core_Helper_Abstract
{
    const ROUTE_ADDITIONAL_SALESBYCATEGORY = 'additional_salesbycategory';

    public function getChartParams($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYCATEGORY] = array(
            array('value' => 'sum_total', 'label' => 'Total'),
            array('value' => 'items_ordered', 'label' => 'Items Ordered'),
        );
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

    public function getNeedReload($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYCATEGORY] = false;
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

    public function getNeedTotal($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYCATEGORY] = true;
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }
}


