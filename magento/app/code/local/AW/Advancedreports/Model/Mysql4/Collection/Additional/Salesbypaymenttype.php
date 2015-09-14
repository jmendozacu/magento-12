<?php

class AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbypaymenttype
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{

    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbypaymenttype
     */
    public function reInitSelect()
    {
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('main_table' => $orderTable),
            array(
                'qty_ordered'          => 'SUM(COALESCE(main_table.total_qty_ordered, 0))',
                'base_subtotal'        => 'SUM(COALESCE(main_table.base_subtotal, 0))',
                'base_shipping_amount' => 'SUM(COALESCE(main_table.base_shipping_amount, 0))',
                'base_tax_amount'      => 'SUM(COALESCE(main_table.base_tax_amount, 0))',
                'base_discount_amount' => 'SUM(COALESCE(main_table.base_discount_amount, 0))',
                'base_grand_total'     => "SUM(COALESCE(main_table.base_grand_total, 0))",
                'base_total_invoiced'  => 'SUM(COALESCE(main_table.base_total_invoiced, 0))',
                'base_total_refunded'  => 'SUM(COALESCE(main_table.base_total_refunded, 0))'

            )
        );
        return $this;
    }

    /**
     * Add payment type
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbypaymenttype
     */
    public function addPaymentMethod()
    {
        $salesOrderPayment = Mage::helper('advancedreports/sql')->getTable('sales_flat_order_payment');
        $this->getSelect()
            ->join(
                array('salesPayment' => $salesOrderPayment),
                "salesPayment.parent_id = main_table.entity_id",
                array('method')
            )
            ->group('method');
        ;

        return $this;
    }


}