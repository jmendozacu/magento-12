<?php
class AW_Advancedreports_Model_Mysql4_Collection_Additional_Customerbycountry
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Add address field to collection
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Customerbycountry
     */
    public function addAddress()
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
                    'order_country' => new Zend_Db_Expr(' IFNULL( IFNULL(flat_order_addr_ship.country_id, flat_order_addr_bil.country_id), "'
                    . Mage::helper('advancedreports')->__('Not set') . '")')
                )
            )
        ;

        return $this;
    }

    /**
     * Add Order items count field to collection
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Customerbycountry
     */
    public function addOrderItems()
    {
        $this->getSelect()
            ->columns(array('items_count' => new Zend_Db_Expr('SUM(main_table.total_qty_ordered)')))
            ->group('order_country')
            ->order('main_table.customer_id')
        ;
        return $this;
    }
}