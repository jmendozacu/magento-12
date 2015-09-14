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


class AW_Advancedreports_Block_Advanced_Stockvssold_Grid extends AW_Advancedreports_Block_Advanced_Grid
{
    protected $_routeOption = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_STOCKVSSOLD;

    public function __construct()
    {
        parent::__construct();
        $this->setFilterVisibility(true);
        $this->setId('gridAdvancedStockvssold');

        # Init aggregator
        $this->getAggregator()->initAggregator(
            $this, AW_Advancedreports_Helper_Tools_Aggregator::TYPE_LIST, $this->getRoute(),
            Mage::helper('advancedreports')->confOrderDateFilter()
        );
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $this->getAggregator()->setStoreFilter($storeIds);
        }
    }

    /**
     * Retrieves initialization array for custom report option
     *
     * @return array
     */
    public function getCustomOptionsRequired()
    {
        $array = parent::getCustomOptionsRequired();

        $addArray = array(
            array(
                'id'      => 'advancedreports_stockvssold_options_estimation_threshold',
                'type'    => 'text',
                'args'    => array(
                    'label'    => $this->__('Out of Stock Estimation Threshold'),
                    'title'    => $this->__('Out of Stock Estimation Threshold'),
                    'name'     => 'advancedreports_stockvssold_options_estimation_threshold',
                    'class'    => 'validate-greater-than-zero',
                    'required' => true,
                ),
                'default' => '90',
            ),
        );
        return array_merge($array, $addArray);
    }

    protected function _addCustomData($row)
    {
        $this->_customData[] = $row;
        return $this;
    }

    public function _prepareCollection()
    {
        parent::_prepareCollection();
        $this->prepareReportCollection();
        $this->_preparePage();

        return $this;
    }

    /**
     * Prepare collection for aggregation
     *
     * @param datetime $from
     * @param datetime $to
     *
     * @return collection
     */
    public function getPreparedData($from, $to)
    {
        $collection = Mage::getResourceModel('advancedreports/collection_stockvssold');
        $estimationDays = $this->getCustomOption('advancedreports_stockvssold_options_estimation_threshold');

        $collection
            ->reInitSelect()
            ->addOrderItems()
            ->addProductInfo()
            ->addEstimationThreshold($estimationDays)
        ;
        $collection->setDateFilter($from, $to)->setState();
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        return $collection;
    }

    public function prepareReportCollection()
    {
        $this
            ->_setUpReportKey()
            ->_setUpFilters()
        ;

        $dateFrom = $this->_getMysqlFromFormat($this->getFilter('report_from'));
        $dateTo = $this->_getMysqlToFormat($this->getFilter('report_to'));

        $this->getAggregator()->prepareAggregatedCollection($dateFrom, $dateTo);

        /** @var AW_Advancedreports_Model_Mysql4_Cache_Collection $collection */
        $collection = $this->getAggregator()->getAggregatetCollection();
        $this->setCollection($collection);

        if ($sort = $this->_getSort()) {
            $collection->addOrder($sort, $this->_getDir());
            $this->getColumn($sort)->setDir($this->_getDir());
        }

        $this->_saveFilters();
        $this->_setColumnFilters();

        return $this;
    }



    protected function _prepareData()
    {
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'name',
            array(
                'header'       => $this->__('Product Name'),
                'index'        => 'name',
                'type'         => 'text'
            )
        );

        $this->addColumn(
            'sku',
            array(
                'header'        => $this->__('SKU'),
                'index'         => 'sku',
                'type'          => 'text'
            )
        );

        $this->addColumn(
            'price',
            array(
                'header'        => $this->__('Price'),
                'index'         => 'price',
                'type'          => 'currency',
                'disable_total' => true,
                'currency_code' => $this->getCurrentCurrencyCode(),
            )
        );

        $this->addColumn(
            'sum_qty',
            array(
                'header'       => $this->__('Items Ordered'),
                'index'        => 'sum_qty',
                'type'         => 'number',
                'total'        => 'sum',
                'renderer'     => 'advancedreports/widget_grid_column_renderer_percent',
                'width'        => '100px',
            )
        );

        $this->addColumn(
            'sum_total',
            array(
                'header'        => $this->__('Total'),
                'width'         => '120px',
                'type'          => 'currency',
                'total'         => 'sum',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'index'         => 'sum_total'
            )
        );

        $this->addColumn(
            'sum_invoiced',
            array(
                'header'        => $this->__('Invoiced'),
                'width'         => '120px',
                'type'          => 'currency',
                'total'         => 'sum',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'index'         => 'sum_invoiced'
            )
        );

        $this->addColumn(
            'sum_refunded',
            array(
                'header'        => $this->__('Refunded'),
                'width'         => '120px',
                'type'          => 'currency',
                'total'         => 'sum',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'index'         => 'sum_refunded'
            )
        );

        $this->addColumn(
            'cost',
            array(
                'header'        => $this->__('Product Cost'),
                'width'         => '120px',
                'type'          => 'currency',
                'total'         => 'sum',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'index'         => 'cost'
            )
        );

        $this->addColumn(
            'item_qty',
            array(
                'header'        => $this->__('Stock Qty'),
                'index'         => 'item_qty',
                'disable_total' => true,
                'type'          => 'number',
                'width'         => '100px'
            )
        );

        $this->addColumn(
            'esitmation_data',
            array(
                'header'        => $this->__('Out of Stock Estimate'),
                'index'         => 'esitmation_data',
                'disable_total' => true,
                'type'          => 'date',
                'align'         => 'right',
            )
        );

        $this->addExportType('*/*/exportOrderedCsv', $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel', $this->__('Excel'));
        return $this;
    }

    public function getChartType()
    {
        return 'none';
    }

    public function hasRecords()
    {
        return false;
    }

    public function hasAggregation()
    {
        return true;
    }
}
