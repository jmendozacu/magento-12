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


class AW_Advancedreports_Model_Mysql4_Collection_Product extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product
     */
    public function reInitSelect()
    {
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $this->getSelect()->reset();
        $this->getSelect()->from(array('main_table' => $orderTable), array());
        return $this;
    }

    /**
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product
     */
    public function addItems($joinParentItem = false, $dateFrom, $dateTo)
    {
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');
        $_joinCondition = "main_table.entity_id = item.order_id AND item.parent_item_id IS NULL";
        if (true === $joinParentItem) {
            $_joinCondition = "main_table.entity_id = item.order_id";
        }

        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $orderStatusList = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $orderStatusList = implode("','", $orderStatusList);

        $storeIdsCondition = '1=1';
        if ($storeIds = $this->getStoreIds()) {
            $storeIdsCondition = "(t_order.store_id in ('" . implode("','", $storeIds) . "'))";
        }

        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                $_joinCondition,
                array(
                    'sum_qty'         => 'SUM(item.qty_ordered)',
                    'sum_total'       => 'SUM(item.base_row_total) - COALESCE(SUM(t_discount.item_discount),0) + SUM(item.base_tax_amount)',
                    'name'            => 'name', 'sku' => 'sku',
                    'item_product_id' => 'item.product_id',
                    'product_type'    => 'item.product_type',
                    'product_options' => 'item.product_options',
                )
            )
            ->joinLeft(
                array('t_discount' => new Zend_Db_Expr(
                    "(SELECT IF(t_item.base_discount_amount = 0, SUM(t_item2.base_discount_amount), t_item.base_discount_amount) AS `item_discount`,
                        t_item.item_id AS `discount_item_id`
                        FROM {$orderTable} AS `t_order`
                        INNER JOIN {$itemTable} AS `t_item` ON (t_item.order_id = t_order.entity_id AND t_item.parent_item_id IS NULL)
                        LEFT JOIN {$itemTable} AS `t_item2` ON (t_item2.order_id = t_order.entity_id AND t_item2.parent_item_id IS NOT NULL AND t_item2.parent_item_id = t_item.item_id AND t_item.product_type IN ('configurable', 'bundle'))
                        WHERE (t_order.{$filterField} >= '{$dateFrom}' AND t_order.{$filterField} <= '{$dateTo}') AND (t_order.status IN ('{$orderStatusList}'))
                        AND {$storeIdsCondition}
                        GROUP BY t_item.item_id)"
                )),
                'item.item_id = t_discount.discount_item_id',
                array()
            )
            ->group('item.product_id')
            ->group('item.sku')
        ;
        if (true === $joinParentItem) {
            $this->getSelect()
                ->joinLeft(
                    array('item_parent' => $itemTable),
                    "main_table.entity_id = item_parent.order_id AND item.parent_item_id = item_parent.item_id",
                    array(
                        'parent_sum_total'  => 'SUM(item_parent.base_row_total) - SUM(item_parent.base_discount_amount) + SUM(item_parent.base_tax_amount)',
                        'parent_product_id' => 'item_parent.product_id',
                    )
                )
                ->group('item_parent.sku')
            ;
        }
        return $this;
    }
}