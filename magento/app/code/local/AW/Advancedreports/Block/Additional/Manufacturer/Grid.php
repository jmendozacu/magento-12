<?php
/**
 * Sales by Manufacturer Report Grid
 */
class AW_Advancedreports_Block_Additional_Manufacturer_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    protected $_routeOption = AW_Advancedreports_Helper_Additional_Manufacturer::ROUTE_ADDITIONAL_MANUFACTURER;
    protected $_optCollection;
    protected $_optCache = array();

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(Mage::helper('advancedreports')->getGridTemplate());
        $this->setExportVisibility(true);
        $this->setStoreSwitcherVisibility(true);
        $this->setId('gridAdditionalManufacturer');

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

    public function hasRecords()
    {
        return false;
    }

    public function getHideShowBy()
    {
        return true;
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
        $collection = Mage::getResourceModel('advancedreports/collection_additional_manufacturer');
        $collection->reInitSelect();

        $collection->setDateFilter($from, $to)->setState();
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }
        $attributeCode = $this->getCustomOption('attribute_code');
        $collection->addOrderItems()->addProductAttribute($attributeCode);

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

    protected function _addCustomData($row)
    {
        $this->_customData[] = $row;
        return $this;
    }

    /**
     * Retrieves initialization array for custom report option
     *
     * @return array
     */
    public function  getCustomOptionsRequired()
    {
        $array = parent::getCustomOptionsRequired();

        $collection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()->addHasOptionsFilter();
        $attributeOptions = array();
        foreach ($collection->getItems() as $attribute) {
            if ($attribute->getFrontendInput() != 'select') {
                continue;
            }
            $attributeOptions[] = array('value' => $attribute->getAttributeCode(), 'label' => $attribute->getFrontendLabel());
        }

        $addArray = array(
            array(
                'id'      => 'attribute_code',
                'type'    => 'select',
                'args'    => array(
                    'label'  => $this->__('Product Attribute'),
                    'title'  => $this->__('Product Attribute'),
                    'name'   => 'attribute_code',
                    'values' => $attributeOptions,
                ),
                'default' => 'manufacturer',
            ),
         );
        return array_merge($array, $addArray);
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
            'product_attribute',
            array(
                 'header' => $this->__('Attribute Options'),
                 'index'  => 'product_attribute',
                 'type'   => 'text',
                 'width'  => '100px',
            )
        );

        $this->addColumn(
            'qty_ordered',
            array(
                'header'   => $this->__('Quantity'),
                'width'    => '60px',
                'index'    => 'qty_ordered',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'total'    => 'sum',
                'type'     => 'number'
            )
        );

        $this->addColumn(
            'base_row_subtotal',
            array(
                 'header'           => $this->__('Subtotal'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_row_subtotal',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_xdiscount_amount',
            array(
                 'header'           => $this->__('Discounts'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_xdiscount_amount',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_row_xtotal',
            array(
                 'header'           => $this->__('Total'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_row_xtotal',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_tax_xamount',
            array(
                 'header'           => $this->__('Tax'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_tax_xamount',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_row_xtotal_incl_tax',
            array(
                 'header'           => $this->__('Total Incl. Tax'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_row_xtotal_incl_tax',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_row_xinvoiced',
            array(
                 'header'           => $this->__('Invoiced'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_row_xinvoiced',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_tax_xinvoiced',
            array(
                 'header'           => $this->__('Tax Invoiced'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_tax_xinvoiced',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_row_xinvoiced_incl_tax',
            array(
                 'header'           => $this->__('Invoiced Incl. Tax'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_row_xinvoiced_incl_tax',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_row_xrefunded',
            array(
                 'header'           => $this->__('Refunded'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_row_xrefunded',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_tax_xrefunded',
            array(
                 'header'           => $this->__('Tax Refunded'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_tax_xrefunded',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_row_xrefunded_incl_tax',
            array(
                 'header'           => $this->__('Refunded Incl. Tax'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                 'total'            => 'sum',
                 'index'            => 'base_row_xrefunded_incl_tax',
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
        return AW_Advancedreports_Block_Chart::CHART_TYPE_PIE3D;
    }

    public function hasAggregation()
    {
        return true;
    }
}