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


class AW_Advancedreports_Block_Reports_List extends Mage_Adminhtml_Block_Template
{
    protected $_dashboardReports = array();
    protected $_salesReports = array();
    protected $_customerReports = array();

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('advancedreports/list.phtml');
    }

    public function prepareReports()
    {
        $reportsModel = Mage::getModel('advancedreports/reports');
        $additionalReports = $reportsModel->getAdditionalReports();
        $reports = $reportsModel->getReports();
        $allReports = array_merge($reports, $additionalReports);
        $this->_dashboardReport = array();
        $this->_salesReports = array();
        $this->_customerReports = array();
        foreach($allReports as $report) {
            switch ($report->getType()) {
                case 'dashboard':
                    $this->_dashboardReports[$report->getSortOrder()] = $report;
                    break;
                case 'sales':
                    $this->_salesReports[$report->getSortOrder()] = $report;
                    break;
                case 'customer':
                    $this->_customerReports[$report->getSortOrder()] = $report;
                    break;
            }
        }
        ksort($this->_dashboardReports);
        ksort($this->_salesReports);
        ksort($this->_customerReports);
        return $this;
    }

    public function getDashboardReports()
    {
        $this->prepareReports();
        return $this->_dashboardReports;
    }

    public function getSalesReports()
    {
        $this->prepareReports();
        return $this->_salesReports;
    }

    public function getCustomerReports()
    {
        $this->prepareReports();
        return $this->_customerReports;
    }

    public function getReportUrl($report)
    {
        return Mage::helper("adminhtml")->getUrl($report->getAction());
    }

    public function isReportActive($reportName) {
        if (Mage::app()->getRequest()->getParam('name') && Mage::app()->getRequest()->getControllerName() == 'additional_report') {
            $activeReportName = Mage::app()->getRequest()->getParam('name');
        } else {
            $activeReportName = Mage::app()->getRequest()->getControllerName();
        }

        if ($activeReportName == $reportName) {
            return true;
        }
        return false;
    }
}
