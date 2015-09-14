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


class AW_Advancedreports_Model_Mysql4_Collection_Product_Item extends Mage_Sales_Model_Mysql4_Order_Item_Collection
{
    /**
     * Reinitialize select
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product_Item
     */
    public function reInitSelect()
    {
        $this->getSelect()->reset();
        $this->getSelect()->from(
            array('main_table' => $this->getMainTable()),
            array(
                'sku' => 'sku',
                'product_id'=>'product_id'
            )
        );

        return $this;
    }
    /**
     * Group collection by attribute
     *
     * @param $attribute
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product_Item
     */
    public function groupByAttribute($attribute)
    {
        $this->getSelect()->group($attribute);
        return $this;
    }

    /**
     * Order collection by attribute
     *
     * @param $attribute
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product_Item
     */
    public function orderByAttribute($attribute, $dir = 'DESC')
    {
        $this->addOrder($attribute, $dir);
        return $this;
    }

    /**
     * Set up date filter to collection of grid
     *
     * @param Datetime $from
     * @param Datetime $to
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Product_Item
     */
    public function setDateFilter($from, $to)
    {
        $filterField = Mage::helper('advancedreports')->confOrderDateFilter();
        $this->_from = $from;
        $this->_to = $to;
        $this->getSelect()
            ->where("main_table.{$filterField} >= ?", $from)
            ->where("main_table.{$filterField} <= ?", $to)
        ;
        return $this;
    }
}