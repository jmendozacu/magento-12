<?php
/**
 * Sales by ZIP Code Report Grid
 */
class AW_Advancedreports_Block_Additional_Salesbyzipcode_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    protected $_routeOption = AW_Advancedreports_Helper_Additional_Salesbyzipcode::ROUTE_ADDITIONAL_SALESBYZIPCODE;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(Mage::helper('advancedreports')->getGridTemplate());
        $this->setExportVisibility(true);
        $this->setStoreSwitcherVisibility(true);
        $this->setId('gridAdditionalSalesbyzipcode');
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
        $this->_prepareData();

        return $this;
    }

    public function prepareReportCollection()
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbyzipcode $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_salesbyzipcode');
        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(
                array(
                    'orders_count'         => new Zend_Db_Expr('SUM(1)'),
                    'base_subtotal'        => new Zend_Db_Expr('SUM(main_table.base_subtotal)'),
                    'base_tax_amount'      => new Zend_Db_Expr('SUM(main_table.base_tax_amount)'),
                    'base_shipping_amount' => new Zend_Db_Expr('SUM(main_table.base_shipping_amount)'),
                    'base_discount_amount' => new Zend_Db_Expr('SUM(main_table.base_discount_amount)'),
                    'base_total_invoiced'  => new Zend_Db_Expr('SUM(main_table.base_total_invoiced)'),
                    'base_total_refunded'  => new Zend_Db_Expr('SUM(main_table.base_total_refunded)'),
                )
            )
        ;
        $this->setCollection($collection);
        $this->_prepareAbstractCollection();

        $collection
            ->addOrderItemsCount()
            ->addZipInfo()
        ;

        return $this;
    }

    protected function _prepareData()
    {
        $read = Mage::helper('advancedreports')->getReadAdapter();
        $select = $this->getCollection()->getSelect();
        $this->_customData = $read->fetchAll($select->__toString());

        $this->_preparePage();
        $this->getCollection()->setSize(count($this->_customData));
        parent::_prepareData();
        return $this;
    }

    protected function _prepareColumns()
    {
        $defValue = sprintf("%f", 0);
        $defValue = Mage::app()->getLocale()->currency($this->getCurrentCurrencyCode())->toCurrency($defValue);

        $this->addColumn(
            'postcode',
            array(
                'header' => $this->__('ZIP Code'),
                'index'  => 'postcode',
                'type'   => 'text',
                'width'  => '100px',
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
                'type'     => 'number'
            )
        );

        $this->addColumn(
            'qty_ordered_count',
            array(
                'header'   => $this->__('Items'),
                'width'    => '60px',
                'index'    => 'qty_ordered_count',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'total'    => 'sum',
                'type'     => 'number'
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

        $this->addExportType('*/*/exportOrderedCsv/name/' . $this->_getName(), $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel/name/' . $this->_getName(), $this->__('Excel'));

        return $this;
    }

    public function getChartType()
    {
        return AW_Advancedreports_Block_Chart::CHART_TYPE_PIE3D;
    }
}