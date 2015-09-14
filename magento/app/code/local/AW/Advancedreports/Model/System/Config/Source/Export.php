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


class AW_Advancedreports_Model_System_Config_Source_Export
{
    protected $_excludeReportsList = array(
        'dashboard'          => 'Dashboard',
        'product'            => 'Sales by Product',
        'salesbycategory'    => 'Sales by Category',
        'salesbyproductattr' => 'Sales by Product Attributes'
    );

    public function toOptionArray()
    {
        $exportList = array();

        $reportsModel = Mage::getModel('advancedreports/reports');
        $additionalReports = $reportsModel->getAdditionalReports();
        $reports = $reportsModel->getReports();
        $allReports = array_merge($reports, $additionalReports);

        foreach ($allReports as $report) {
            if (array_key_exists($report->getName(), $this->_excludeReportsList)) {
                continue;
            }
            $exportList[] = array('value' => $report->getName(), 'label' => Mage::helper('advancedreports')->__($report->getTitle()));
        }

        return $exportList;
    }
}