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

/**
 * Products by Customer Report Grid
 */
class AW_Advancedreports_Block_Advanced_Purchased_Grid extends AW_Advancedreports_Block_Advanced_Grid
{
    /**
     * Route to extract config from helper
     *
     * @var string
     */
    protected $_routeOption = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_PURCHASED;

    public function __construct()
    {
        parent::__construct();
        $this->setId('gridPurchased');
    }

    /**
     * Has records to build report
     *
     * @return boolean
     */
    public function hasRecords()
    {
        return false;
    }

    /**
     * Prepare collection of report
     *
     * @return AW_Advancedreports_Block_Advanced_Purchased_Grid
     */
    protected function _prepareCollection()
    {
        parent::_prepareCollection();
        $this->prepareReportCollection();
        $this->_prepareData();

        return $this;
    }

    public function prepareReportCollection()
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Purchased $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_purchased');
        $this->setCollection($collection);
        $dateFrom = $this->_getMysqlFromFormat($this->getFilter('report_from'));
        $dateTo = $this->_getMysqlToFormat($this->getFilter('report_to'));
        $this->getCollection()->setDateFilter($dateFrom, $dateTo)->setState();
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        $collection->addOrderItemsCount(empty($storeIds));
        Mage::helper('advancedreports')->setNeedMainTableAlias(true);

        return $this;
    }

    /**
     * Prepare data array for Pie and Grid
     *
     * @return AW_Advancedreports_Block_Advanced_Purchased_Grid
     */
    protected function _prepareData()
    {
        $read = Mage::helper('advancedreports')->getReadAdapter();
        $select = $this->getCollection()->getSelect();
        $this->_customData = $read->fetchAll($select->__toString());

        if (!count($this->_customData)) {
            return $this;
        }
        usort($this->_customData, array(&$this, "_compareQtyElements"));
        $this->_preparePage();
        $this->getCollection()->setSize(count($this->_customData));
        Mage::helper('advancedreports')->setChartData($this->_customData, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        parent::_prepareData();
        return $this;
    }

    /**
     * Sort bestsellers values in two arrays
     *
     * @param array $a
     * @param array $b
     *
     * @return integer
     */
    protected function _compareQtyElements($a, $b)
    {
        if ($a['sum_qty'] == $b['sum_qty']) {
            return 0;
        }
        return ($a['sum_qty'] > $b['sum_qty']) ? -1 : 1;
    }

    /**
     * Prepare columns to show grid
     *
     * @return AW_Advancedreports_Block_Advanced_Purchased_Grid
     */
    protected function _prepareColumns()
    {
        $currencyCode = $this->getCurrentCurrencyCode();
        $defValue = sprintf("%f", 0);
        $defValue = Mage::app()->getLocale()->currency($this->getCurrentCurrencyCode())->toCurrency($defValue);

        $this->addColumn(
            'sum_qty',
            array(
                'header'   => $this->__('Products Purchased'),
                'align'    => 'right',
                'index'    => 'sum_qty',
                'total'    => 'sum',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'type'     => 'number',
            )
        );

        $this->addColumn(
            'customers',
            array(
                'header'   => $this->__('Number of Customers'),
                'align'    => 'right',
                'index'    => 'customers',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'total'    => 'sum',
                'type'     => 'number',
            )
        );

        $this->addColumn(
            'x_base_total',
            array(
                'header'        => $this->__('Total'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'x_base_total',
                'total'         => 'sum',
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'default'       => $defValue,
            )
        );

        $this->addColumn(
            'x_base_total_invoiced',
            array(
                'header'        => $this->__('Invoiced'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'x_base_total_invoiced',
                'total'         => 'sum',
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'default'       => $defValue,
            )
        );

        $this->addColumn(
            'x_base_total_refunded',
            array(
                'header'        => $this->__('Refunded'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'x_base_total_refunded',
                'total'         => 'sum',
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'default'       => $defValue,
            )
        );

        $this->addExportType('*/*/exportOrderedCsv', $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel', $this->__('Excel'));

        return $this;
    }

    /**
     * Retrieves type of chart for grid
     * (need for compatibiliy wit other reports)
     *
     * @return string
     */
    public function getChartType()
    {
        return AW_Advancedreports_Block_Chart::CHART_TYPE_PIE3D;
    }
}