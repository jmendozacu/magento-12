<?php
/**
 * Sales by Payment Type Report Grid
 */
class AW_Advancedreports_Block_Additional_Salesbypaymenttype_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    protected $_routeOption
        = AW_Advancedreports_Helper_Additional_Salesbypaymenttype::ROUTE_ADDITIONAL_SALESBYPAYMENTTYPE
    ;
    protected $_methodCache = array();
    protected $_methodExcludes = array(
        'googlecheckout' => 'Google Checkout',
        'klarna_partpayment' => 'Klarna Part payment',
        'klarna_invoice' => 'Klarna Invoice'
    );

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(Mage::helper('advancedreports')->getGridTemplate());
        $this->setExportVisibility(true);
        $this->setStoreSwitcherVisibility(true);
        $this->setId('gridAdditionalSalesbypaymenttype');
    }

    public function _prepareCollection()
    {
        parent::_prepareCollection();
        $this->prepareReportCollection();
        $this->_prepareData();
    }

    public function prepareReportCollection()
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbypaymenttype $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_salesbypaymenttype')->reInitSelect();
        $this->setCollection($collection);
        $this->_prepareAbstractCollection();

        $collection->addPaymentMethod();

        return $this;
    }

    public function hasRecords()
    {
        return false;
    }

    public function getHideShowBy()
    {
        return true;
    }

    protected function _addCustomData($row)
    {
        $this->_customData[] = $row;
        return $this;
    }

    protected function _getMethodInstance($code)
    {
        $key = Mage_Payment_Helper_Data::XML_PATH_PAYMENT_METHODS . '/' . $code . '/model';
        $class = Mage::getStoreConfig($key);
        if (!$class) {
            return false;
        }
        return Mage::getSingleton($class);
    }

    protected function _getMethodTitle($code)
    {
        if (!isset($this->_methodCache[$code])) {
            if (isset($this->_methodExcludes[$code])) {
                $this->_methodCache[$code] = Mage::helper('payment')->__($this->_methodExcludes[$code]);
            } elseif ($method = $this->_getMethodInstance($code)) {
                $this->_methodCache[$code] = $method->getTitle();
            } else {
                $this->_methodCache[$code] = '';
            }
        }
        if (!$this->_methodCache[$code]) {
            $this->_methodCache[$code] = $code;
        }
        return $this->_methodCache[$code];
    }

    protected function _prepareData()
    {
        Varien_Profiler::start('aw::advancedreports::salesbypaymenttype::prepare_data');
        $pageNum = 1;
        $pageSize = 3000;
        $read = Mage::helper('advancedreports')->getReadAdapter();
        while (true) {
            $select = clone $this->getCollection()->getSelect();
            $select->limitPage($pageNum, $pageSize);
            $items = $read->fetchAll($select->__toString());
            if (count($items) == 0) {
                break;
            }
            foreach ($items as $row) {
                if (array_key_exists('method', $row)) {
                    $row['payment_type'] = $this->_getMethodTitle($row['method']);
                } else {
                    $row['payment_type'] = $this->__('Not set');
                }
                $row['title'] = $row['payment_type'];

                $this->_addCustomData($row);
            }
            $pageNum++;
        }
        $this->_preparePage();
        $this->getCollection()->setSize(count($this->_customData));
        Varien_Profiler::stop('aw::advancedreports::salesbypaymenttype::prepare_data');
        parent::_prepareData();
        return $this;
    }

    protected function _prepareColumns()
    {
        $defValue = sprintf("%f", 0);
        $defValue = Mage::app()->getLocale()->currency($this->getCurrentCurrencyCode())->toCurrency($defValue);

        $this->addColumn(
            'payment_type',
            array(
                 'header' => $this->__('Payment Type'),
                 'index'  => 'payment_type',
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