<?php
/**
 * Customers By Country Report Grid
 */
class AW_Advancedreports_Block_Additional_Customersbycountry_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    protected $_routeOption
        = AW_Advancedreports_Helper_Additional_Customersbycountry::ROUTE_ADDITIONAL_CUSTOMERSBYCOUNTRY;
    protected $_usersCollection = null;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(Mage::helper('advancedreports')->getGridTemplate());
        $this->setExportVisibility(true);
        $this->setStoreSwitcherVisibility(true);
        $this->setId('gridAdditionalCustomersbycountry');

        # Set default sorting
        if (!$this->getRequest()->getParam('sort')){
            $this->getRequest()->setParam('sort', 'items_count');
            $this->getRequest()->setParam('dir', 'desc');
        }
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
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Additional_Customerbycountry $collection  */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_customerbycountry');
        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(
                array(
                    'users_count'          => new Zend_Db_Expr('0'),
                    'customers_count'      => new Zend_Db_Expr('COUNT(DISTINCT(main_table.customer_email))'),
                    'base_subtotal'        => new Zend_Db_Expr('SUM(main_table.base_subtotal)'),
                    'base_grand_total'     => new Zend_Db_Expr('SUM(main_table.base_grand_total)'),
                    'base_tax_amount'      => new Zend_Db_Expr('SUM(main_table.base_tax_amount)'),
                    'base_shipping_amount' => new Zend_Db_Expr('SUM(main_table.base_shipping_amount)'),
                    'base_discount_amount' => new Zend_Db_Expr('SUM(main_table.base_discount_amount)'),
                    'base_total_invoiced'  => new Zend_Db_Expr('SUM(main_table.base_total_invoiced)'),
                    'base_total_refunded'  => new Zend_Db_Expr('SUM(main_table.base_total_refunded)'),
                    'orders_count'         => new Zend_Db_Expr('SUM(1)'),
                )
            )
        ;
        $this->setCollection( $collection );
        $this->_prepareAbstractCollection();
        $collection->addAddress();
        $collection->addOrderItems();
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

    protected function _prepareData()
    {
        $read = Mage::helper('advancedreports')->getReadAdapter();
        $select = $this->getCollection()->getSelect();
        $this->_customData = $read->fetchAll($select->__toString());

        //collect users by country
        $date_from = $this->_getMysqlFromFormat($this->getFilter('report_from'));
        $date_to = $this->_getMysqlToFormat($this->getFilter('report_to'));
        # check Store Filter
        $storeIds = null;
        if ($this->getRequest()->getParam('store')) {
            $storeIds = array($this->getParam('store'));
        } else if ($this->getRequest()->getParam('website')) {
            $storeIds = Mage::app()->getWebsite($this->getRequest()->getParam('website'))->getStoreIds();
        } else if ($this->getRequest()->getParam('group')){
            $storeIds = Mage::app()->getGroup($this->getRequest()->getParam('group'))->getStoreIds();
        }
        foreach ($this->_customData as &$d) {
            $d['users_count'] = Mage::helper('advancedreports/additional_customersbycountry')->getUsersByCountry($date_from, $date_to, $d['order_country'], $storeIds);
        }
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
            'order_country',
            array(
                 'header' => $this->__('Country'),
                 'index'  => 'order_country',
                 'type'   => 'text',
                 'width'  => '100px',
                 'renderer' => 'adminhtml/widget_grid_column_renderer_country',
            )
        );
        $this->addColumn(
            'users_count',
            array(
                'header'   => $this->__('New Accounts'),
                'width'    => '60px',
                'index'    => 'users_count',
                'total'    => 'sum',
                'type'     => 'number',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'default'  => '0',
            )
        );
        $this->addColumn(
            'customers_count',
            array(
                'header'   => $this->__('New Accounts with Orders'),
                'width'    => '60px',
                'index'    => 'customers_count',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'total'    => 'sum',
                'type'     => 'number',
                'default'  => '0',
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
                'default'  => '0',
            )
        );
        $this->addColumn(
            'items_count',
            array(
                'header'   => $this->__('Items'),
                'width'    => '60px',
                'index'    => 'items_count',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'total'    => 'sum',
                'type'     => 'number',
                'default'  => '0',
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
        $this->addExportType('*/*/exportOrderedCsv/name/'.$this->_getName(), $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel/name/'.$this->_getName(), $this->__('Excel'));
        return $this;
    }
}