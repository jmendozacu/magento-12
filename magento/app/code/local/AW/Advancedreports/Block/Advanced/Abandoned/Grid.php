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


class AW_Advancedreports_Block_Advanced_Abandoned_Grid extends AW_Advancedreports_Block_Advanced_Grid
{
    /**
     * Route to extract config from helper
     *
     * @var string
     */
    protected $_routeOption = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_ABANDONED;

    const GRID_ID_ADVANCED_ABANDONED = 'gridAbandoned';

    public function __construct()
    {
        parent::__construct();
        $this->setId(self::GRID_ID_ADVANCED_ABANDONED);
    }

    public function hasRecords()
    {
        return count(Mage::helper('advancedreports')->getChartParams($this->_routeOption));
    }

    public function hasAggregation()
    {
        return false;
    }

    /**
     * Prepare collection of report
     *
     * @return AW_Advancedreports_Block_Advanced_Abandoned_Grid
     */
    protected function _prepareCollection()
    {
        $this->prepareReportCollection();
        $this->_prepareData();

        return $this;
    }

    public function prepareReportCollection()
    {
        parent::_prepareOlderCollection();
        return $this;
    }

    /**
     * Add data to Data cache
     *
     * @param array $row Row of data
     *
     * @return AW_Advancedreports_Block_Advanced_Abandoned_Grid
     */
    protected function _addCustomData($row)
    {
        $this->_customData[] = $row;
        return $this;
    }


    protected function _getAbandonedCartsCollection($dateFrom, $dateTo)
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Purchased $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_abandoned');
        $collection->reInitSelect();
        $collection->setPeriod($this->getFilter('report_period'));
        $collection->setDateFilter($dateFrom, $dateTo);

        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        $collection
            ->addAbandonedInfo();

        return $collection;
    }

    public function getHideShowBy()
    {
        return false;
    }
    /**
     * Prepare data array for Chart
     *
     * @return AW_Advancedreports_Block_Advanced_Abandoned_Grid
     */
    protected function _prepareData()
    {
        $position = 0;
        foreach ($this->getCollection()->getIntervals() as $_item) {
            $row = array();
            $row['period'] = $_item['title'];
            $row['sort_position'] = $position++;
            $row['completed_carts'] = 0;
            $row['abandoned_carts'] = 0;
            $row['abandoned_carts_total'] = '0.000';
            $row['total_carts'] = 0;
            $row['abandonment_rate'] = 0;
            foreach ($this->_getAbandonedCartsCollection($_item['start'], $_item['end']) as $abandonedRow) {
                $row['completed_carts'] = $abandonedRow->getCompletedCarts();
                $row['abandoned_carts'] = $abandonedRow->getAbandonedCarts();
                $row['abandoned_carts_total'] = $abandonedRow->getAbandonedCartsTotal();
                $row['total_carts'] = $abandonedRow->getTotalCarts();
                $row['abandonment_rate'] = $abandonedRow->getAbandonmentRate();
            }

            $this->_addCustomData($row);
        }

        if (!count($this->_customData)) {
            return $this;
        }

        $this->_preparePage();
        $this->getCollection()->setSize(count($this->_customData));
        Mage::helper('advancedreports')->setChartData($this->_customData, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        parent::_prepareData();
        return $this;
    }

    /**
     * Prepare columns to show grid
     *
     * @return AW_Advancedreports_Block_Advanced_Abandoned_Grid
     */
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
            'total_carts',
            array(
                'header' => $this->__('Total Carts'),
                'align'  => 'right',
                'index'  => 'total_carts',
                'total'  => 'sum',
                'type'   => 'number',
            )
        );

        $this->addColumn(
            'completed_carts',
            array(
                'header' => $this->__('Completed Carts'),
                'type'   => 'number',
                'total'  => 'sum',
                'index'  => 'completed_carts',
            )
        );

        $this->addColumn(
            'abandoned_carts',
            array(
                'header' => $this->__('Abandoned Carts'),
                'type'   => 'number',
                'total'  => 'sum',
                'index'  => 'abandoned_carts',
            )
        );

        $this->addColumn(
            'abandoned_carts_total',
            array(
                'header'        => $this->__('Abandoned Carts Total'),
                'index'         => 'abandoned_carts_total',
                'type'          => 'currency',
                'total'         => 'sum',
                'currency_code' => $this->getCurrentCurrencyCode(),
            )
        );

        $this->addColumn(
            'abandonment_rate',
            array(
                'header'        => $this->__('Abandonment Rate'),
                'type'          => 'number',
                'index'         => 'abandonment_rate',
                'width'         => '150px',
                'renderer'      => 'advancedreports/widget_grid_column_renderer_profit',
                'disable_total' => true
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
        return AW_Advancedreports_Block_Chart::CHART_TYPE_LINE;
    }

    public function getPeriods()
    {
        return parent::_getOlderPeriods();
    }
}