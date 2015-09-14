<?php
class AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbycategory
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbycategory
     */
    public function reInitSelect()
    {
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();

        $this->getSelect()->reset();
        $this->getSelect()->from(array('main_table' => $orderTable),
            array(
                'order_created_at'   => $filterField,
                'order_id'           => 'entity_id',
                'order_increment_id' => 'increment_id',
                'status' => 'status'
            ));

        return $this;
    }

    public function addOrderItemInfo()
    {
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $refundTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_creditmemo_item');

        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                "(item.order_id = main_table.entity_id AND item.parent_item_id IS NULL)",
                array(
                    'items_ordered'    => "SUM(item.qty_ordered)",
                    'sum_tax_invoiced' => "SUM(item.base_tax_invoiced)",
                    'sum_invoiced'     => "SUM(item.base_row_invoiced)",
                    'sum_subtotal'     => "SUM(item.base_row_total)",
                    'sum_total'        => "SUM(item.base_row_total + item.base_tax_amount + COALESCE(item.base_hidden_tax_amount, 0) + COALESCE(item.base_weee_tax_applied_amount, 0) - COALESCE(item.base_discount_amount, 0))",
                )
            )
            ->joinLeft(
                array('refunded' => $refundTable),
                "(refunded.order_item_id = item.item_id)",
                array('sum_refunded' => 'SUM(refunded.base_row_total)')
            );

        return $this;
    }

    public function addCategoryFilter($categoryNames)
    {
        $categoryNames = explode(',', $categoryNames);
        $catNameAttr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_category', 'name');
        $catNameTable = $catNameAttr->getBackendTable();
        $categoryTable = Mage::helper('advancedreports/sql')->getTable('catalog_category_product');

        $this->getSelect()
            ->join(
                array('category' => $categoryTable),
                "(category.product_id = item.product_id)",
                array()
            )
            ->join(
                array('category_name' => $catNameTable),
                $this->getConnection()
                    ->quoteInto(
                        "(category_name.entity_id = category.category_id
                        AND category_name.value IN (?)
                        AND category_name.attribute_id = {$catNameAttr->getId()})", $categoryNames
                    ),
                array()
            );

        return $this;
    }

    public function addProfitInfo($dateFrom, $dateTo)
    {
        parent::addProfitInfo($dateFrom, $dateTo);

        $this->getSelect()
            ->columns(
                array(
                    'total_cost'             => "COALESCE(SUM(t.total_cost), 0)",
                    'total_profit'           => 'COALESCE(SUM(t.total_profit), 0)',
                    'total_margin'           => "COALESCE((100 * (SUM(t.total_revenue) - SUM(t.total_cost))/ SUM(t.total_revenue)), 0)",
                    'total_revenue_excl_tax' => 'COALESCE(SUM(t.total_revenue_excl_tax), 0)',
                    'total_revenue'          => 'COALESCE(SUM(t.total_revenue), 0)',
                )
            );
        return $this;
    }
}