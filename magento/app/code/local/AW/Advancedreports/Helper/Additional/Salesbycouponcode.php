<?php
class AW_Advancedreports_Helper_Additional_Salesbycouponcode extends Mage_Core_Helper_Abstract
{
    const ROUTE_ADDITIONAL_SALESBYCOUPONCODE = 'additional_salesbycouponcode';

    public function getChartParams($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYCOUPONCODE] = array(
            array('value' => 'base_grand_total', 'label' => 'Total'),
            array('value' => 'total_qty_ordered', 'label' => 'Items Ordered'),
        );
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

    public function getNeedReload($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYCOUPONCODE] = false;
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

    public function getNeedTotal($key)
    {
        $params = array();
        $params[self::ROUTE_ADDITIONAL_SALESBYCOUPONCODE] = true;
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }

}


