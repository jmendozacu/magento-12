<?php

class AW_Advancedreports_Model_Mysql4_Collection_Additional_Newvsreturning
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Reinitialize collection
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Newvsreturning
     */
    public function reInitOrdersCollection()
    {
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');
        $this->getSelect()->reset();

        $arr = array(
            'date'           => "DATE_FORMAT(main_table.created_at, '%Y-%m-%d %H:%i') AS date",
            'orders_count'   => "COUNT(main_table.entity_id)", # Just because it's unique
            'customer_id'    => "main_table.customer_id",
            'customer_email' => "main_table.customer_email",
        );

        $this->getSelect()->from(array('main_table' => $orderTable), $arr);
        $this->getSelect()->group(array("date", "main_table.customer_email"));
        $this->getSelect()->where('main_table.status IN(?)', explode(',', Mage::helper('advancedreports')->confProcessOrders()));

        return $this;
    }


    public function getOldCustomers($from, array $customersEmails)
    {
        $_select = $this->getConnection()->select();
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');
        $_select
            ->from(array('order_flat' => $orderTable), 'order_flat.customer_email')
            ->where('order_flat.created_at < ?', $from)
            ->where('order_flat.customer_email IN(?)', $customersEmails)
            ->group('order_flat.customer_email')
        ;

        $_select->where('order_flat.status IN(?)', explode(',', Mage::helper('advancedreports')->confProcessOrders()));

        $result = $readConnection->fetchAll($_select);
        return $result;
    }
}