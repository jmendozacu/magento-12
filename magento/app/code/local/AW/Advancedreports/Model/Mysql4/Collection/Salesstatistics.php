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


class AW_Advancedreports_Model_Mysql4_Collection_Salesstatistics
    extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    public function addOrderValue()
    {
        $orderTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_order');

        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('main_table' => $orderTable),
            array(
                'orders_count'         => "COUNT(main_table.entity_id)",
                'base_total_invoiced'  => "SUM(main_table.base_total_invoiced)",
                'grouper'              => new Zend_Db_Expr('ROUND(1)'),
            )
        );

        $this->getSelect()->group('grouper');

        return $this;
    }
}