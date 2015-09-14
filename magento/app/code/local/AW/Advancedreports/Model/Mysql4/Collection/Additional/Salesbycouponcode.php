<?php
class AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbycouponcode
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{

    /**
     * create collection
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbycouponcode
     */
    public function getSalesbycouponcodeCollection()
    {
        $notSetCode = Mage::helper('advancedreports')->__('Not set');
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $this->getSelect()->reset();
        $this->getSelect()->from(array('main_table' => $orderTable), array());

        $this->getSelect()->columns(
            array(
                'coupon_code' => "IFNULL(coupon_code, '{$notSetCode}')",
                'orders_count' => "COUNT(entity_id)",
                'total_qty_ordered' => "SUM(total_qty_ordered)",
                'base_subtotal' => "SUM(base_subtotal)",
                'base_tax_amount' => "SUM(base_tax_amount)",
                'base_shipping_amount' => "SUM(base_shipping_amount)",
                'base_discount_amount' => "SUM(base_discount_amount)",
                'base_grand_total' => "SUM(base_subtotal) + SUM(base_discount_amount) + SUM(base_tax_amount) + SUM(base_shipping_amount)",
                'base_total_invoiced' => "SUM(base_total_invoiced)",
                'base_total_refunded' => "SUM(base_total_refunded)",
            )
        );

        $this->getSelect()->group("IFNULL(coupon_code, '{$notSetCode}')");

        return $this;
    }

}