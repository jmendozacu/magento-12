<?php
/**
 * Sales by Coupon Code Report Grid
 */
class AW_Advancedreports_Block_Additional_Salesbycouponcode_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    /**
     * Route to get config from helper
     * @var string
     */
    protected $_routeOption
        = AW_Advancedreports_Helper_Additional_Salesbycouponcode::ROUTE_ADDITIONAL_SALESBYCOUPONCODE
    ;

    public function __construct()
    {
        parent::__construct();
        $this->setFilterVisibility(true);
        $this->setId('gridAdditionalSalesbycouponcode');
        $this->setDefaultSort('qty_ordered_count');

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

    public function getHideShowBy()
    {
        return true;
    }

    public function _prepareCollection()
    {
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
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Sales $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_salesbycouponcode');
        $collection->getSalesbycouponcodeCollection();

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

        # Start aggregator
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

        return $this;
    }

    protected function _prepareData()
    {
        return $this;
    }

    protected function _prepareColumns()
    {
        $defValue = sprintf("%f", 0);
        $defValue = Mage::app()->getLocale()->currency($this->getCurrentCurrencyCode())->toCurrency($defValue);

        $this->addColumn(
            'coupon_code',
            array(
                'header'       => $this->__('Coupon Code'),
                'index'        => 'coupon_code',
                'type'         => 'text',
                'width'        => '100px',
            )
        );

        $this->addColumn(
            'orders_count',
            array(
                'header'   => $this->__('Orders'),
                'width'    => '60px',
                'index'    => 'orders_count',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'total'    => 'sum',
                'type'     => 'number',
            )
        );

        $this->addColumn(
            'total_qty_ordered',
            array(
                'header'       => $this->__('Items'),
                'width'        => '60px',
                'index'        => 'total_qty_ordered',
                'renderer'     => 'advancedreports/widget_grid_column_renderer_percent',
                'total'        => 'sum',
                'type'         => 'number',
            )
        );

        $this->addColumn(
            'base_subtotal',
            array(
                 'header'           => $this->__('Subtotal'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_subtotal',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_tax_amount',
            array(
                 'header'           => $this->__('Tax'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_tax_amount',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_shipping_amount',
            array(
                 'header'           => $this->__('Shipping'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_shipping_amount',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_discount_amount',
            array(
                 'header'           => $this->__('Discounts'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_discount_amount',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_grand_total',
            array(
                 'header'           => $this->__('Total'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_grand_total',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_total_invoiced',
            array(
                 'header'           => $this->__('Invoiced'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_total_invoiced',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_total_refunded',
            array(
                 'header'           => $this->__('Refunded'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_total_refunded',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addExportType('*/*/exportOrderedCsvFile/name/' . $this->_getName(), $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcelFile/name/' . $this->_getName(), $this->__('Excel'));

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