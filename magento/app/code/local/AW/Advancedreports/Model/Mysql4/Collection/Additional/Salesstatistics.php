<?php

class AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesstatistics
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Sales
     */
    public function reInitItemSelect()
    {
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');
        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('main_table' => $orderTable),
            array(
                 'grouper' => new Zend_Db_Expr('ROUND(1)'),
            )
        );

        $this->getSelect()->group('grouper');
        return $this;
    }

    public function reInitOrderSelect()
    {
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');


        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('main_table' => $orderTable),
            array(
                 'orders_count'         => "COUNT(main_table.entity_id)",
                 # Just because it's unique
                 'base_subtotal'        => "SUM(main_table.base_subtotal)",
                 'base_tax_amount'      => "SUM(main_table.base_tax_amount)",
                 'base_discount_amount' => "SUM(main_table.base_discount_amount)",
                 'base_grand_total'     => "SUM(main_table.base_subtotal) + SUM(main_table.base_discount_amount) + SUM(main_table.base_tax_amount)",
                 'base_total_invoiced'  => "SUM(main_table.base_total_invoiced)",
                 'base_total_refunded'  => "SUM(main_table.base_total_refunded)",
                 'grouper'              => new Zend_Db_Expr('ROUND(1)'),
            )
        );

        $this->getSelect()->group('grouper');

        return $this;
    }

    /**
     * Add items
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesstatistics
     */
    public function addItems()
    {
        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $this->getSelect()->join(
            array(
                 'item' => $itemTable), "main_table.entity_id = item.order_id AND item.parent_item_id IS NULL",
            array(
                 'items_count'    => 'SUM(item.qty_ordered)',
                 'items_invoiced' => 'SUM(item.qty_invoiced)'
            )
        );
        return $this;
    }
}
