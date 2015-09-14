<?php

class AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbyzipcode
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Add order item
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbyzipcode
     */
    public function addOrderItems()
    {
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();

        $itemTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_item');
        $this->getSelect()
            ->join(
                array('item' => $itemTable),
                "(item.order_id = main_table.entity_id AND item.parent_item_id IS NULL)"
            )
            ->order("main_table.{$filterField} DESC");

        return $this;
    }


    /**
     * Add zip info
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbyzipcode
     */
    public function addZipInfo()
    {
        $salesFlatOrderAddress = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_address');
        $this->getSelect()
            ->joinLeft(
                array('flat_order_addr_ship' => $salesFlatOrderAddress),
                "flat_order_addr_ship.parent_id = main_table.entity_id "
                . "AND flat_order_addr_ship.address_type = 'shipping'",
                array()
            )
            ->joinLeft(
                array('flat_order_addr_bil' => $salesFlatOrderAddress),
                "flat_order_addr_bil.parent_id = main_table.entity_id "
                . "AND flat_order_addr_bil.address_type = 'billing'",
                array(
                    'postcode' => new Zend_Db_Expr(' IFNULL( IFNULL(flat_order_addr_ship.postcode, flat_order_addr_bil.postcode), "'
                        . Mage::helper('advancedreports')->__('Not set') . '")')
                 )
            )
            ->group('postcode')
        ;

        return $this;
    }


    public function addOrderItemsCount()
    {
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();

        $this->getSelect()
            ->columns(array('qty_ordered_count' => new Zend_Db_Expr('SUM(total_qty_ordered)')))
            ->columns(array('base_grand_total' => "(main_table.base_subtotal + main_table.base_shipping_amount + main_table.base_discount_amount + main_table.base_tax_amount)"));


        return $this;
    }

}