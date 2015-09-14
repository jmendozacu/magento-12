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


class AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    /**
     * Not simple product types
     *
     * @var array
     */
    protected $_notSimple = array('configurable');
    protected $_storeIds = array();

    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
     */
    public function reInitSelect()
    {
        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('e' => $this->getEntity()->getEntityTable()),
            array()
        );
        return $this;
    }

    public function setDateFilter($from, $to)
    {
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $this->getSelect()
            ->where("orders.{$filterField} >= ?", $from)
            ->where("orders.{$filterField} <= ?", $to);
        return $this;
    }

    protected function _getProcessStates()
    {
        $states = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $isFirst = true;
        $filter = "";
        foreach ($states as $state) {
            if (!$isFirst) {
                $filter .= " OR ";
            }
            $filter .= "orders.status = '" . $state . "'";
            $isFirst = false;
        }
        return "(" . $filter . ")";
    }

    public function setState()
    {
        $this->getSelect()
            ->where($this->_getProcessStates());

        return $this;
    }

    public function setStoreFilter($storeIds = array())
    {
        $this->_storeIds = $storeIds;
        $this->getSelect()->where("orders.store_id in ('" . implode("','", $storeIds) . "')");
        return $this;
    }

    public function getStoreIds()
    {
        return $this->_storeIds;
    }

    public function addOrderItems(
        $limit = 10, $dateFrom, $dateTo, $skuType = AW_Advancedreports_Model_System_Config_Source_Skutype::SKUTYPE_SIMPLE
    )
    {
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $notSimple = "'" . implode("','", $this->_notSimple) . "'";

        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        if (count($this->_storeIds)) {
            $currencyRate = new Zend_Db_Expr("'1'");
        } else {
            $currencyRate = 'orders.store_to_base_rate';
        }
        if ($skuType == AW_Advancedreports_Model_System_Config_Source_Skutype::SKUTYPE_SIMPLE) {
            $priceAttr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'price');
            $priceTable = $priceAttr->getBackendTable();

            $this->getSelect()
                ->join(
                    array('item' => $itemTable),
                    "(item.product_id = e.entity_id AND ((item.parent_item_id IS NULL
                    AND item.product_type NOT IN ({$notSimple})) OR (item.parent_item_id IS NOT NULL
                    AND item.product_type NOT IN ({$notSimple}))))",
                    array(
                        'product_id' => 'product_id',
                        'sum_qty' => 'COALESCE(SUM(item.qty_ordered), 0)',
                        'sum_total' => "(COALESCE(SUM(IFNULL(item2.base_row_total, item.base_row_total)),0)
                            + COALESCE(SUM(IFNULL(item2.base_hidden_tax_amount, item.base_hidden_tax_amount)), 0.0000)
                            + COALESCE(SUM(IFNULL(item2.base_weee_tax_applied_amount, item.base_weee_tax_applied_amount)), 0)
                            + COALESCE(SUM(IFNULL(item2.base_tax_amount,item.base_tax_amount)), 0)
                            - COALESCE(SUM(IFNULL(item2.base_discount_amount,item.base_discount_amount)), 0))* $currencyRate",
                        'name' => 'name',
                        'sku' => 'sku',
                    )
                )
                ->joinLeft(
                    array('item2' => $itemTable),
                    "(item.parent_item_id = item2.item_id AND item2.product_type IN ('configurable'))",
                    array()
                )
                ->joinLeft(
                    array('item_price' => $priceTable),
                    "item_price.entity_id = item.product_id AND item_price.attribute_id = {$priceAttr->getId()}",
                    array()
                )
                ->where("(item.product_type <> 'bundle' OR item_price.value > 0)")
                ->where("(item.product_type <> 'configurable')")
            ;
        } else {
            $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
            $orderStatusList = explode(",", Mage::helper('advancedreports')->confProcessOrders());
            $orderStatusList = implode("','", $orderStatusList);

            $storeIdsCondition = '1=1';
            if ($storeIds = $this->getStoreIds()) {
                $storeIdsCondition = "(t_order.store_id in ('" . implode("','", $storeIds) . "'))";
            }

            $productTable = Mage::helper('advancedreports/sql')->getTable('catalog_product_entity');
            $this->getSelect()
                ->join(
                    array('item' => $itemTable),
                    "(item.product_id = e.entity_id AND item.parent_item_id IS NULL)",
                    array(
                        'product_id' => 'product_id',
                        'sum_qty' => 'COALESCE(SUM(item.qty_ordered), 0)',
                        'sum_total' => "(
                            COALESCE(SUM(item.base_row_total),0)
                            + COALESCE(SUM(item.base_hidden_tax_amount), 0.0000)
                            + COALESCE(SUM(item.base_weee_tax_applied_amount), 0)
                            + COALESCE(SUM(item.base_tax_amount), 0)
                            - SUM(COALESCE(t_discount.item_discount, item.base_discount_amount, 0))
                        )",
                        'name' => 'name',
                        'sku' => 'realP.sku',
                    )
                )
                ->joinLeft(
                    array('t_discount' => new Zend_Db_Expr(
                        "(SELECT IF(t_item.base_discount_amount = 0, SUM(t_item2.base_discount_amount), t_item.base_discount_amount) AS `item_discount`,
                        t_item.item_id AS `discount_item_id`
                        FROM {$orderTable} AS `t_order`
                        INNER JOIN {$itemTable} AS `t_item` ON (t_item.order_id = t_order.entity_id AND t_item.parent_item_id IS NULL)
                        INNER JOIN {$itemTable} AS `t_item2` ON (t_item2.order_id = t_order.entity_id AND t_item2.parent_item_id IS NOT NULL AND t_item2.parent_item_id = t_item.item_id AND t_item.product_type IN ('configurable', 'bundle'))
                        WHERE (t_order.{$filterField} >= '{$dateFrom}' AND t_order.{$filterField} <= '{$dateTo}') AND (t_order.status IN ('{$orderStatusList}'))
                        AND {$storeIdsCondition}
                        GROUP BY t_item.item_id)"
                    )),
                    'item.item_id = t_discount.discount_item_id',
                    array()
                )
                ->joinLeft(
                    array('realP' => $productTable),
                    "item.product_id = realP.entity_id",
                    array()
                );
        }

        $this->getSelect()
            ->join(array('orders' => $orderTable), "orders.entity_id = item.order_id", array())
            ->group('e.entity_id')
            ->limit($limit);
        return $this;
    }

    /**
     * Set up order by total
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
     */
    public function orderByTotal()
    {
        $this->getSelect()
            ->order('sum_total DESC');
        return $this;
    }

    /**
     * Set up order by quantitty
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
     */
    public function orderByQty()
    {
        $this->getSelect()
            ->order('sum_qty DESC');
        return $this;
    }

    /**
     * Set up profit columns for collection
     * ATTENTION: use this method only for collections with joined 'item' => 'sales_flat_order_item' table
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Bestsellers
     */
    public function addProfitInfo($dateFrom, $dateTo)
    {
        $costAttr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'cost');
        $costTable = $costAttr->getBackendTable();
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $skuTypeCondition = '1=1';
        $itemProductIdField = "IFNULL(item.product_id, item2.product_id)";
        $typeList = "'configurable'";
        $skuType = Mage::helper('advancedreports/setup')->getCustomConfig('advancedreports_bestsellers_options_skutype');
        if ($skuType == AW_Advancedreports_Model_System_Config_Source_Skutype::SKUTYPE_GROUPED) {
            $itemProductIdField = "IFNULL(item2.product_id, item.product_id)";
            $skuTypeCondition = "(item.parent_item_id IS NULL OR item2.product_type = 'configurable')";
            $typeList = "'configurable', 'bundle'";
        }

        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $orderStatusList = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $orderStatusList = implode("','", $orderStatusList);

        $storeIdsCondition = '1=1';
        if ($storeIds = $this->getStoreIds()) {
            $storeIdsCondition = "(order.store_id in ('" . implode("','", $storeIds) . "'))";
        }

        $this->getSelect()
            ->joinLeft(
                array('profit' => new Zend_Db_Expr(
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
                    {$itemProductIdField} AS `item_product_id`
                    FROM {$itemTable} AS `item`
                    INNER JOIN {$orderTable} AS `order` ON order.entity_id = item.order_id
                    LEFT JOIN {$itemTable} AS `item2` ON order.entity_id = item2.order_id AND item2.item_id = item.parent_item_id AND item2.product_type IN ({$typeList})
                    LEFT JOIN {$costTable} AS `cost` ON cost.entity_id = item.product_id AND cost.attribute_id = {$costAttr->getId()}
                    WHERE COALESCE(cost.value,0) > 0 AND {$skuTypeCondition} AND (order.{$filterField} >= '{$dateFrom}' AND order.{$filterField} <= '{$dateTo}') AND (order.status IN ('{$orderStatusList}'))
                    AND {$storeIdsCondition}
                    GROUP BY {$itemProductIdField} )"
                )),
                'item.product_id = profit.item_product_id',
                array(
                    'total_cost'             => "COALESCE(profit.total_cost, 0)",
                    'total_profit'           => 'COALESCE(profit.total_profit, 0)',
                    'total_margin'           => "COALESCE(profit.total_margin, 0)",
                    'total_revenue_excl_tax' => 'COALESCE(profit.total_revenue_excl_tax, 0)',
                    'total_revenue'          => 'COALESCE(profit.total_revenue, 0)',
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