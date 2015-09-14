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


class AW_Advancedreports_Model_Mysql4_Collection_Country extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Add address data to Report Collection
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Country
     */
    public function addAddress()
    {
        $salesFlatOrderAddress = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_address');
        $this->getSelect()
            ->joinLeft(
                array('flat_order_addr_ship' => $salesFlatOrderAddress),
                "flat_order_addr_ship.parent_id = main_table.entity_id AND flat_order_addr_ship.address_type = 'shipping'",
                array()
            )
            ->joinLeft(
                array('flat_order_addr_bill' => $salesFlatOrderAddress),
                "flat_order_addr_bill.parent_id = main_table.entity_id AND flat_order_addr_bill.address_type = 'billing'",
                array()
            )
            ->columns(
                array('country_id' => 'IFNULL(flat_order_addr_ship.country_id, flat_order_addr_bill.country_id)')
            )
            ->group('IFNULL(flat_order_addr_ship.country_id, flat_order_addr_bill.country_id)');

        return $this;
    }

    /**
     * Add items to select request
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Country
     */
    public function addOrderItemsCount($isAllStores = false)
    {
        if ($isAllStores) {
            $currencyRate = "main_table.store_to_base_rate";
        } else {
            $currencyRate = new Zend_Db_Expr("'1'");
        }

        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');

        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                "(item.order_id = main_table.entity_id AND item.parent_item_id IS NULL)",
                array(
                    'sum_qty'   => 'SUM(item.qty_ordered)',
                    'sum_total' => "SUM(item.base_row_total * $currencyRate)",
                )
            )
            ->where("main_table.entity_id = item.order_id");
        return $this;
    }
}