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


class AW_Advancedreports_Model_Mysql4_Collection_Abstract extends AW_Advancedreports_Model_Mysql4_Order_Collection
{

    protected $_period;
    protected $_periodFormat;

    protected $_storeIds;


    public function getPeriods()
    {
        return array(
            'day'     => Mage::helper('advancedreports')->__('Day'),
            'week'    => Mage::helper('advancedreports')->__('Week'),
            'month'   => Mage::helper('advancedreports')->__('Month'),
            'quarter' => Mage::helper('advancedreports')->__('Quarter'),
            'year'    => Mage::helper('advancedreports')->__('Year'),
        );
    }

    public function setPeriod($period)
    {
        $this->_period = $period;
    }

    public function setStoreIds($storeIds)
    {
        $this->_storeIds = $storeIds;
    }

    public function getStoreIds()
    {
        return $this->_storeIds;
    }

    /**
     * Not use inside all whose wrappers
     *
     * @deprecated
     *
     * @param $table
     *
     * @return string
     */
    public function getTable($table)
    {
        return parent::getTable($table);
    }

    /**
     * Set up date filter to collection of grid
     *
     * @param Datetime $from
     * @param Datetime $to
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Abstract
     */
    public function setDateFilter($from, $to)
    {
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $this->_from = $from;
        $this->_to = $to;
        $this->getSelect()
            ->where("main_table.{$filterField} >= ?", $from)
            ->where("main_table.{$filterField} <= ?", $to)
        ;
        return $this;
    }

    /**
     * Set up order state filter
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Abstract
     */
    public function setState()
    {
        $this->addAttributeToFilter('status', explode(",", Mage::helper('advancedreports')->confProcessOrders()));

        return $this;
    }

    /**
     * Set up profit columns for collection
     * ATTENTION: use this method only for collections with joined 'item' => 'sales_flat_order_item' table
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $groupBy
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Abstract
     */
    public function addProfitInfo($dateFrom, $dateTo)
    {
        $costAttr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'cost');
        $costTable = $costAttr->getBackendTable();
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $orderStatusList = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $orderStatusList = implode("','", $orderStatusList);

        $groupBy = 'item.product_id';
        $additionalJoinCondition = '1=1';
        $skuTypeCondition = '1=1';
        $storeIdsCondition = '1=1';
        $itemProductIdField = "IFNULL(item.product_id, item2.product_id)";
        $typeList = "'configurable'";

        if ($storeIds = $this->getStoreIds()) {
            $storeIdsCondition = "(order.store_id in ('" . implode("','", $storeIds) . "'))";
        }

        if ($this instanceof AW_Advancedreports_Model_Mysql4_Collection_Sales) {
            $itemProductIdField = "IFNULL(item2.product_id, item.product_id)";
            $groupBy = 'item.product_id, item.order_id';
            $additionalJoinCondition = 'item.order_id = t.item_order_id AND (t.parent_item_id = item.item_id OR t.parent_item_id IS NULL)';
        }

        if ($this instanceof AW_Advancedreports_Model_Mysql4_Collection_Product) {
            $itemProductIdField = "item.product_id";
            $skuTypeCondition = 'item.parent_item_id IS NULL';
        }

        if ($this instanceof AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbycategory) {
            $itemProductIdField = "IFNULL(item2.product_id, item.product_id)";
            $groupBy = 'item.product_id, item.order_id';
            $additionalJoinCondition = 'item.order_id = t.item_order_id AND (t.parent_item_id = item.item_id OR t.parent_item_id IS NULL)';
        }

        $this->getSelect()
            ->joinLeft(
                new Zend_Db_Expr(
                    "(SELECT (SUM(IFNULL(cost.value, 0)) * SUM(IFNULL(item.qty_ordered,item2.qty_ordered))/COUNT(IFNULL(item.qty_ordered,item2.qty_ordered))) AS `total_cost`,
                    (
                        SUM(IFNULL(item2.base_row_total, item.base_row_total))
                        + SUM(IFNULL(item2.base_tax_amount, item.base_tax_amount))
                        + SUM(COALESCE(item2.base_hidden_tax_amount, item.base_hidden_tax_amount, 0))
                        + SUM(IFNULL(item2.base_weee_tax_applied_amount, item.base_weee_tax_applied_amount))
                        - SUM(IFNULL(item2.base_discount_amount, item.base_discount_amount))
                        - (SUM(IFNULL(cost.value, 0)) * SUM(IFNULL(item.qty_ordered,item2.qty_ordered))/COUNT(IFNULL(item.qty_ordered,item2.qty_ordered)))
                    ) AS `total_profit`,
                    (
                        100
                        * (
                            SUM(IFNULL(item2.base_row_total, item.base_row_total))
                            + SUM(IFNULL(item2.base_tax_amount, item.base_tax_amount))
                            + SUM(COALESCE(item2.base_hidden_tax_amount, item.base_hidden_tax_amount, 0))
                            + SUM(IFNULL(item2.base_weee_tax_applied_amount, item.base_weee_tax_applied_amount))
                            - SUM(IFNULL(item2.base_discount_amount, item.base_discount_amount))
                            - (SUM(IFNULL(cost.value, 0)) * SUM(IFNULL(item.qty_ordered,item2.qty_ordered))/COUNT(IFNULL(item.qty_ordered,item2.qty_ordered)))
                        )
                        / (
                            SUM(IFNULL(item2.base_row_total, item.base_row_total))
                            + SUM(IFNULL(item2.base_tax_amount, item.base_tax_amount))
                            + SUM(COALESCE(item2.base_hidden_tax_amount, item.base_hidden_tax_amount, 0))
                            + SUM(IFNULL(item2.base_weee_tax_applied_amount, item.base_weee_tax_applied_amount))
                            - SUM(IFNULL(item2.base_discount_amount, item.base_discount_amount))
                        )
                    ) AS `total_margin`,
                    (
                        SUM(IFNULL(item2.base_row_total, item.base_row_total))
                        - SUM(IFNULL(item2.base_discount_amount, item.base_discount_amount))
                    ) AS `total_revenue_excl_tax`,
                    (
                        SUM(IFNULL(item2.base_row_total, item.base_row_total))
                        + SUM(IFNULL(item2.base_tax_amount, item.base_tax_amount))
                        + SUM(COALESCE(item2.base_hidden_tax_amount, item.base_hidden_tax_amount, 0))
                        + SUM(IFNULL(item2.base_weee_tax_applied_amount, item.base_weee_tax_applied_amount))
                        - SUM(IFNULL(item2.base_discount_amount, item.base_discount_amount))
                    ) AS `total_revenue`,
                    {$itemProductIdField} AS `item_product_id`,
                    item.order_id AS `item_order_id`,
                    item.parent_item_id AS `parent_item_id`
                    FROM {$itemTable} AS `item`
                    INNER JOIN {$orderTable} AS `order` ON order.entity_id = item.order_id
                    LEFT JOIN {$itemTable} AS `item2` ON order.entity_id = item2.order_id AND item2.item_id = item.parent_item_id AND item2.product_type IN ({$typeList})
                    LEFT JOIN {$costTable} AS `cost` ON cost.entity_id = item.product_id AND cost.attribute_id = {$costAttr->getId()}
                    WHERE COALESCE(cost.value,0) > 0 AND {$skuTypeCondition} AND (order.{$filterField} >= '{$dateFrom}' AND order.{$filterField} <= '{$dateTo}') AND (order.status IN ('{$orderStatusList}'))
                    AND {$storeIdsCondition}
                    GROUP BY {$groupBy})"
                ),
                "item.product_id = t.item_product_id AND {$additionalJoinCondition}",
                array(
                    'total_cost'             => "COALESCE(t.total_cost, 0)",
                    'total_profit'           => 'COALESCE(t.total_profit, 0)',
                    'total_margin'           => "COALESCE(t.total_margin, 0)",
                    'total_revenue_excl_tax' => 'COALESCE(t.total_revenue_excl_tax, 0)',
                    'total_revenue'          => 'COALESCE(t.total_revenue, 0)',
                )
            );

        return $this;
    }

    public function setSize($size)
    {
        $this->_totalRecords = $size;
        return $this;
    }
}