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


class AW_Advancedreports_Helper_Abstract extends Mage_Core_Helper_Abstract
{
    /**
     * Returns AW_Advancedreports version
     *
     * @return string
     */
    public function getVersion()
    {
        return (string)Mage::getConfig()->getNode('modules/AW_Advancedreports/version');
    }

    /**
     * Retrieves Queue helper
     *
     * @return AW_Advancedreports_Helper_Queue
     */
    public function getQueue()
    {
        return Mage::helper('advancedreports/queue');
    }

    /**
     * Retrieves Date helper
     *
     * @return AW_Advancedreports_Helper_Date
     */
    public function getDate()
    {
        return Mage::helper('advancedreports/date');
    }

    /**
     * Retrieves View Helper
     *
     * @return AW_Advancedreports_Helper_View
     */
    public function getView()
    {
        return Mage::helper('advancedreports/view');
    }

    /**
     * Retrieves Setup Helper
     *
     * @return AW_Advancedreports_Helper_Setup
     */
    public function getSetup()
    {
        return Mage::helper('advancedreports/setup');
    }

    /**
     * Retrieves Aggregator
     *
     * @return AW_Advancedreports_Helper_Tools_Aggreagtor
     */
    public function getAggregator()
    {
        return Mage::helper('advancedreports/tools_aggregator');
    }
}