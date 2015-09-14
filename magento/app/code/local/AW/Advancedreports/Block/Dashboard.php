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


class AW_Advancedreports_Block_Dashboard extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('advancedreports/dashboard.phtml');
    }

    protected function _prepareLayout()
    {
        /** @var Mage_Page_Block_Html_Head $headBlock */
        $headBlock = $this->getLayout()->getBlock('head');
        $headBlock->addJs('advancedreports/timeframe.js');
        $headBlock->addItem('skin_css', 'aw_advancedreports/css/styles.css');
        $headBlock->addItem('skin_css', 'aw_advancedreports/css/timeframe.css');
        $headBlock->addItem('skin_css', 'aw_advancedreports/css/gui.css');
        return parent::_prepareLayout();
    }

    protected function _getDashboardPeriod()
    {
        $periods = array();
        $currentDate = new Zend_Date(null,
            AW_Advancedreports_Helper_Data::FRONTEND_ZEND_DATE_FORMAT,
            Mage_Core_Model_Locale::DEFAULT_LOCALE);
        $currentDate->setHour(23)->setMinute(59)->setSecond(59);

        $periods['end'] = $currentDate->toString(AW_Advancedreports_Helper_Data::FRONTEND_ZEND_DATE_FORMAT);

        $currentDate->setHour(0)->setMinute(0)->setSecond(0);
        $currentDate->sub(30, Zend_Date::DAY);

        $periods['start'] = $currentDate->toString(AW_Advancedreports_Helper_Data::FRONTEND_ZEND_DATE_FORMAT);
        return $periods;
    }

    protected function _createChart($type, $option, $width, $route)
    {
        $output = $this->getLayout()->createBlock('advancedreports/chart')
            ->setType($type)
            ->setOption($option)
            ->setWidth($width)
            ->setRouteOption($route)
            ->setHeight(300)
            ->setBackgroundColor('f9f9f9')
            ->toHtml()
        ;

        return $output;
    }

    protected function _createReportBlock($route)
    {
        $periods = $this->_getDashboardPeriod();
        $block = $this->getLayout()->createBlock('advancedreports/'.$route.'_grid');
        $block->setParentFilters(true);
        $block->setFilter('report_from', $periods['start']);
        $block->setFilter('report_to', $periods['end']);
        $block->setFilter('reload_key', 'qty');
        $block->setFilter('report_period', 'day');

        return $block;
    }


    public function getReportsListHtml()
    {
        return $this->getLayout()->createBlock('advancedreports/reports_list', 'advancedreports.reports.list')->toHtml();
    }

    public function getStoreSwitcherHtml()
    {
        $block = $this->getLayout()->createBlock('advancedreports/store_switcher', 'advancedreports.store.switcher');
        if ($block) {
            return $block->toHtml();
        }
        return parent::getStoreSwitcherHtml();
    }

    public function getSalesOverviewChart()
    {
        $route = AW_Advancedreports_Helper_Data::ROUTE_SALES_SALESOVERVIEW;
        $block = $this->_createReportBlock($route);
        $block->prepareReportCollection();
        $block->prepareBlockData();

        $chartParams = Mage::helper('advancedreports')->getChartParams($route);
        $chartType = $block->getChartType();

        $chart = $this->_createChart($chartType, $chartParams[0]['value'], 1000, $route);
        return $chart;
    }

    public function getAverageOrderValueChart()
    {
        $route = AW_Advancedreports_Helper_Data::ROUTE_SALES_SALESOVERVIEW;
        $block = $this->_createReportBlock($route);
        $block->prepareReportCollection();

        $customData = array();
        $storeIds = $block->getStoreIds();

        foreach ($block->getCollection()->getIntervals() as $_item) {
            $row = $this->_getOrderStatistics($_item['start'], $_item['end'], $storeIds);
            $row->setPeriod($_item['title']);

            if (!$row->getOrdersCount()) {
                $row->setOrdersCount(0);
            }
            if ($row->getOrdersCount()) {
                $row->setAvgOrderAmount($row->getBaseTotalInvoiced() / $row->getOrdersCount());
            } else {
                $row->setAvgOrderAmount(0);
            }

            $customData[] = $row->getData();
        }

        $chartLabels = array(
            'avg_order_amount' => $block->__('Order Amount(Avg)')
        );
        $keys = array();
        foreach ($chartLabels as $key => $value) {
            $keys[] = $key;
        }

        Mage::helper('advancedreports')->setChartData($customData, Mage::helper('advancedreports')->getDataKey(AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_SALESSTATISTICS));
        Mage::helper('advancedreports')->setChartKeys($keys, Mage::helper('advancedreports')->getDataKey(AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_SALESSTATISTICS));
        Mage::helper('advancedreports')->setChartLabels($chartLabels, Mage::helper('advancedreports')->getDataKey(AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_SALESSTATISTICS));

        $chartParams = Mage::helper('advancedreports')->getChartParams(AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_SALESSTATISTICS);
        $chartType = 'lc';

        $chart = $this->_createChart($chartType, $chartParams[0]['value'], 1000, AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_SALESSTATISTICS);
        return $chart;
    }

    protected function _getOrderStatistics($from, $to, $storeIds)
    {
        $collection = Mage::getResourceModel('advancedreports/collection_salesstatistics');
        $collection->addOrderValue();
        $collection->setDateFilter($from, $to)->setState();

        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }
        if (count($collection)) {
            foreach ($collection as $item) {
                return $item;
            }
        }
        return new Varien_Object();
    }

    public function getItemsPerOrderChart()
    {
        $route = AW_Advancedreports_Helper_Data::ROUTE_SALES_SALESOVERVIEW;
        $block = $this->_createReportBlock($route);
        $block->prepareReportCollection();
        $block->prepareBlockData();

        $chartType = $block->getChartType();

        $chart = $this->_createChart($chartType, 'items_per_order', 660, $route);
        return $chart;
    }

    public function getNewSignupChart()
    {
        $route = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_USERS;
        $block = $this->_createReportBlock($route);
        $block->prepareReportCollection();
        $block->prepareBlockData();

        $chartLabels = null;
        $keys = null;

        Mage::helper('advancedreports')->setChartKeys($keys, Mage::helper('advancedreports')->getDataKey($route));
        Mage::helper('advancedreports')->setChartLabels($chartLabels, Mage::helper('advancedreports')->getDataKey($route));
        $chart = $this->_createChart('lc', 'accounts', 660, $route);
        return $chart;
    }

    public function getBestsellersReport()
    {
        $route = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_BESTSELLERS;
        $block = $this->_createReportBlock($route);

        $block->prepareReportCollection();
        $collection = $block->getCollection();
        $collection->getSelect()->limit(10);

        return $collection;
    }

    public function getSalesByCountryReport()
    {
        $route = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_COUNTRY;
        $block = $this->_createReportBlock($route);

        $block->prepareReportCollection();
        $block->prepareBlockData();

        $data = array_slice($block->getCountryData(), 0, 10, true);

        return $data;
    }

    public function formatCurrency($value)
    {
        return Mage::app()->getStore()->getCurrentCurrency()->format($value, null, false);
    }
}