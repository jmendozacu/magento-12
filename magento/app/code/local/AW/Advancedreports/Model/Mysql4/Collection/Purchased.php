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


class AW_Advancedreports_Model_Mysql4_Collection_Purchased extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Add columns to collection select
     * @param bool $isAllStores
     *
     * @return $this
     */
    public function addOrderItemsCount($isAllStores = false)
    {
        if ($isAllStores) {
            $currencyRate = "main_table.store_to_base_rate";
        } else {
            $currencyRate = new Zend_Db_Expr("'1'");
        }
        $this->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(
                array(
                    'customers'             => new Zend_Db_Expr('SUM(1)'),
                    'title'                 => new Zend_Db_Expr('ROUND(main_table.total_qty_ordered)'),
                    'sum_qty'               => new Zend_Db_Expr('main_table.total_qty_ordered'),
                    'x_base_total'          => new Zend_Db_Expr("SUM((main_table.base_subtotal + main_table.base_discount_amount + main_table.base_tax_amount) * $currencyRate)"),
                    'x_base_total_invoiced' => new Zend_Db_Expr("SUM(base_total_invoiced * $currencyRate)"),
                    'x_base_total_refunded' => new Zend_Db_Expr("SUM(base_total_refunded * $currencyRate)"),
                )
            )
            ->group('main_table.total_qty_ordered')
        ;
        return $this;
    }
}