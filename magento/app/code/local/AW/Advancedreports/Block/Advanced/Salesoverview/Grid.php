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


class AW_Advancedreports_Block_Advanced_Salesoverview_Grid extends AW_Advancedreports_Block_Advanced_Grid
{
    protected $_routeOption = AW_Advancedreports_Helper_Data::ROUTE_SALES_SALESOVERVIEW;
    protected $_reportCollections = array();

    public function __construct()
    {
        parent::__construct();
        $this->setId('gridSalesoverview');
    }

    public function hasRecords()
    {
        return (count($this->getCollection()->getIntervals()) > 1)
            && count(Mage::helper('advancedreports')->getChartParams($this->_routeOption))
        ;
    }

    public function getHideShowBy()
    {
        return false;
    }

    public function getHideNativeGrid()
    {
        return true;
    }

    public function getShowCustomGrid()
    {
        return true;
    }

    protected function _addCustomData($row)
    {
        if (!isset($row['items_count'])) {
            $row['items_count'] = 0;
        }
        if (!isset($row['orders'])) {
            $row['orders'] = 0;
        }
        if (!isset($row['discount'])) {
            $row['discount'] = 0;
        }
        $this->_customData[] = $row;
        return $this;
    }

    public function _prepareCollection()
    {
        $this->prepareReportCollection();
        $this->getCollection()->setCurPage($this->getParam($this->getVarNamePage(), $this->_defaultPage));
        $this->_prepareData();
        return $this;
    }

    public function prepareReportCollection()
    {
        parent::_prepareOlderCollection();

        return $this;
    }

    protected function _getItemStatistics($from, $to)
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Standard_Sales $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_standard_sales');
        $collection->reInitSelect()
            ->addItems()
            ->setState()
            ->setDateFilter($from, $to)
        ;

        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        $items = new Varien_Object(array('items_count' => 0));

        if (count($collection)) {
            foreach ($collection as $item) {
                $items->setItemsCount($items->getItemsCount() + $item->getItemsCount());
            }
        }
        return $items;
    }

    public function getReport($from, $to)
    {
        $key = $from . ' - ' . $to;
        if (isset($this->_reportCollections[$key])) {
            return $this->_reportCollections[$key];
        }
        $storeIds = $this->getStoreIds();
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Standard_Sales $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_standard_sales');
        $collection->reInitSelect()
            ->addSumColumns(empty($storeIds))
            ->addGroupByIntOne()
            ->setState()
            ->setDateFilter($from, $to)
        ;

        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        $this->_reportCollections[$key] = $collection;
        return $collection;
    }

    protected function _prepareData()
    {
        //Remember available keys
        $keys = array();
        foreach ($this->getChartParams() as $param) {
            $keys[] = $param['value'];
        }

        $dataKeys = array();
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem() && in_array($column->getIndex(), $keys)) {
                $dataKeys[] = $column->getIndex();
            }
        }
        //Get data
        $data = array();
        foreach ($this->getCollection()->getIntervals() as $_index => $_item) {
            $report = $this->getReport($_item['start'], $_item['end']);
            $row = array();
            foreach ($report->getItems() as $item) {
                $row['total'] = $item->getTotal();
                $row['items_per_order'] = $item->getItemsCount()/ $item->getOrders();
                foreach ($this->_columns as $column) {
                    $row[$column->getIndex()] = $item->getData($column->getIndex());
                }
            }

            $row['period'] = $_index;
            $data[] = $row;
            $this->_addCustomData($row);
        }

        $this->_preparePage();
        $this->getCollection()->setSize(count($this->_customData));
        if ($data) {
            Mage::helper('advancedreports')->setChartData($data, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        }
        parent::_prepareData();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'period',
            array(
                'header' => $this->__('Period'),
                'align'  => 'right',
                'index'  => 'period',
                'type'   => 'text',
                'width'  => '150px',
                'is_period_sorting' => true,
            )
        );

        $this->addColumn(
            'orders',
            array(
                'header' => Mage::helper('reports')->__('Number of Orders'),
                'index'  => 'orders',
                'total'  => 'sum',
                'type'   => 'number',
            )
        );

        $this->addColumn(
            'items_count',
            array(
                'header' => Mage::helper('reports')->__('Items Ordered'),
                'index'  => 'items_count',
                'total'  => 'sum',
                'type'   => 'number',
            )
        );

        $currencyCode = $this->getCurrentCurrencyCode();
        $this->addColumn(
            'subtotal',
            array(
                'header'        => Mage::helper('reports')->__('Subtotal'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'subtotal',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'tax',
            array(
                'header'        => Mage::helper('reports')->__('Tax'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'tax',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'shipping',
            array(
                'header'        => Mage::helper('reports')->__('Shipping'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'shipping',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'discount',
            array(
                'header'        => Mage::helper('reports')->__('Discounts'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'discount',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'total',
            array(
                'header'        => Mage::helper('reports')->__('Total'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'total',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'invoiced',
            array(
                'header'        => Mage::helper('reports')->__('Invoiced'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'invoiced',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'refunded',
            array(
                'header'        => Mage::helper('reports')->__('Refunded'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'refunded',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addExportType('*/*/exportCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('reports')->__('Excel'));

        return $this;
    }

    public function getChartType()
    {
        return AW_Advancedreports_Block_Chart::CHART_TYPE_LINE;
    }

    public function getPeriods()
    {
        return parent::_getOlderPeriods();
    }

    public function getExcel($filename = '')
    {
        return parent::getExcel($filename);
    }

    public function getCsv($filename = '')
    {
        return parent::getCsv($filename);
    }
}
