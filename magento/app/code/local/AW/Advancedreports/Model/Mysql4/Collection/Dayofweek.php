<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Advancedreports
 * @version    2.6.3
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancedreports_Model_Mysql4_Collection_Dayofweek extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Set up day of week filter
     * $dayofweek = 1,2,3,4,5,6,7
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Dayofweek
     */
    public function setDayOfWeekFilter($isAllStores = false)
    {
        if ($isAllStores) {
            $currencyRate = "main_table.store_to_base_rate";
        } else {
            $currencyRate = new Zend_Db_Expr("'1'");
        }

        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $globalTz = Mage::helper('advancedreports')->getTimeZoneOffset(true);

        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                "main_table.entity_id = item.order_id AND item.parent_item_id IS NULL",
                array(
                    'day_of_week' => "DAYOFWEEK(CONVERT_TZ(main_table.{$filterField}, '+00:00', '{$globalTz}'))",
                    'sum_qty'     => 'SUM(item.qty_ordered)',
                    'sum_total'   => "SUM(item.base_row_total * $currencyRate)",
                    'name'        => 'name', 'sku' => 'sku',
                )
            )
            ->group("DAYOFWEEK(CONVERT_TZ(main_table.{$filterField}, '+00:00', '{$globalTz}'))");

        return $this;
    }
}