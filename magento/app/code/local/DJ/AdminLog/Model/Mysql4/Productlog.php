<?php
class DJ_AdminLog_Model_Mysql4_Productlog extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("adminlog/productlog", "product_log_id");
    }
}