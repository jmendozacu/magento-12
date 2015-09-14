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


class AW_Advancedreports_Model_Mysql4_Collection_Abandoned extends AW_Advancedreports_Model_Mysql4_Collection_Abstract
{
    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product
     */
    public function reInitSelect()
    {
        $quoteTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_quote');

        $this->getSelect()->reset();
        $this->getSelect()->from(array('main_table' => $quoteTable), array());
        $this->getSelect()->where("main_table.items_count > 0");

        return $this;
    }

    /**
     * Set up date filter to collection of grid
     *
     * @param Datetime $from
     * @param Datetime $to
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Abstract
     */
    public function setDateFilter($from, $to)
    {
        $this->_from = $from;
        $this->_to = $to;
        $this->getSelect()
            ->where("main_table.updated_at >= ?", $from)
            ->where("main_table.updated_at <= ?", $to)
        ;
        return $this;
    }

    /**
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product
     */
    public function addAbandonedInfo()
    {
        $quoteTable = Mage::helper('advancedreports/sql')->getTable('sales_flat_quote');

        //add completed_carts column
        $this->getSelect()
            ->joinLeft(
                array('quote_completed' => $quoteTable),
                "main_table.entity_id = quote_completed.entity_id AND COALESCE(quote_completed.reserved_order_id, '') <> ''",
                array(
                    'completed_carts' => "COALESCE(COUNT(quote_completed.entity_id), 0)",
                )
            );

        //add abandoned_carts column
        $this->getSelect()
            ->joinLeft(
                array('quote_abandoned' => $quoteTable),
                "main_table.entity_id = quote_abandoned.entity_id AND COALESCE(quote_abandoned.reserved_order_id, '')  = ''",
                array(
                    'abandoned_carts' => "COALESCE(COUNT(quote_abandoned.entity_id), 0)"
                )
            );

        //add abandoned_carts_total column
        $this->getSelect()
            ->joinLeft(
                array('quote_abandoned_total' => $quoteTable),
                "main_table.entity_id = quote_abandoned_total.entity_id AND COALESCE(quote_abandoned_total.reserved_order_id, '')  = ''",
                array(
                    'abandoned_carts_total' => "COALESCE(SUM(quote_abandoned_total.base_grand_total * quote_abandoned_total.store_to_base_rate), 0)",
                )
            );

        //add total_carts column
        $this->getSelect()
            ->joinLeft(
                array('quote_total' => $quoteTable),
                "main_table.entity_id = quote_total.entity_id",
                array(
                    'total_carts' => "COALESCE(COUNT(quote_total.entity_id), 0)",
                )
            );

        $this->getSelect()
            ->columns(array('abandonment_rate' => 'COALESCE((100/ COUNT(quote_total.entity_id)) * COUNT(quote_abandoned.entity_id), 0)'));

        return $this;
    }
}