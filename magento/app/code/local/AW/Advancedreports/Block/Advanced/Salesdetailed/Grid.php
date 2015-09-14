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
 * Sales Report Grid
 */
class AW_Advancedreports_Block_Advanced_Salesdetailed_Grid extends AW_Advancedreports_Block_Advanced_Grid
{
    protected $_routeOption = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_SALESDETAILED;
    protected $_optCollection;
    protected $_optCache = array();

    /**
     * Cache with addresses for orders
     *
     * @var array
     */
    protected $_addresses = array();

    public function __construct()
    {
        parent::__construct();
        $this->setUseAjax(true);
        $this->setFilterVisibility(true);
        $this->setId('gridAdvancedSales');

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
    public function  getCustomOptionsRequired()
    {
        $array = parent::getCustomOptionsRequired();

        $include = Mage::getModel('advancedreports/system_config_source_include');
        $addArray = array(
            array(
                'id'      => 'include_refunded',
                'type'    => 'select',
                'args'    => array(
                    'label'  => $this->__('Include refunded items'),
                    'title'  => $this->__('Include refunded items'),
                    'name'   => 'include_refunded',
                    'values' => $include->toOptionArray(),
                ),
                'default' => '1',
            ),
        );
        return array_merge($array, $addArray);
    }

    protected function _addCustomData($row)
    {
        $this->_customData[] = $row;
        return $this;
    }

    /**
     * Prepare collection for aggregation
     *
     * @param datetime $from
     * @param datetime $to
     *
     * @return array
     */
    public function getPreparedData($from, $to)
    {
        $storeIds = $this->getStoreIds();
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Sales $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_sales');
        $collection->reInitSelect(empty($storeIds));

        $collection->setDateFilter($from, $to)->setState();
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        $collection->addOrderItems()
            ->addCustomerInfo()
            ->addManufacturer()
            ->addAddress()
            ->addProfitInfo($from, $to);

        if (!$this->getCustomOption('include_refunded')) {
            $collection->excludeRefunded();
        }

        return $collection;
    }

    public function _prepareCollection()
    {
        $this->prepareReportCollection();
        $this->_preparePage();

        return $this;
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
        } else {
            $collection->addOrder('order_created_at', 'DESC');
        }
        $this->_saveFilters();
        $this->_setColumnFilters();

        return $this;
    }

    protected function _addOptionToCache($id, $value)
    {
        $this->_optCache[$id] = $value;
    }

    protected function _optionInCache($id)
    {
        if (count($this->_optCache)) {
            foreach ($this->_optCache as $key => $value) {
                if ($key == $id) {
                    return $value;
                }
            }
        }
        return null;
    }

    protected function _getManufacturer($optionId)
    {
        if (!$this->_optCollection) {
            $this->_optCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setStoreFilter(0, false)
                ->load()
            ;
        }
        # seach in quick cache
        if ($val = $this->_optionInCache($optionId)) {
            return $val;
        }
        # search in chached collection
        foreach ($this->_optCollection as $item) {
            if ($optionId == $item->getOptionId()) {
                $this->_addOptionToCache($optionId, $item->getValue());
                return $item->getValue();
            }
        }
        return null;
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
            'order_increment_id',
            array(
                'header'      => $this->__('Order #'),
                'index'       => 'order_increment_id',
                'type'        => 'text',
                'width'       => '80px',
            )
        );

        $this->addColumn('status', array(
                'header' => $this->__('Order Status'),
                'index' => 'status',
                'type'  => 'options',
                'width' => '70px',
                'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));

        $this->addColumn(
            'order_created_at',
            array(
                'header'        => $this->__('Order Date'),
                'index'         => 'order_created_at',
                'type'          => 'datetime',
                'width'         => '140px',
                'align'         => 'right',
                'is_period_key' => true,
            )
        );

        $this->addColumn(
            'xsku',
            array(
                'header'      => $this->__('SKU'),
                'width'       => '120px',
                'index'       => 'xsku',
                'type'        => 'text',
            )
        );

        $this->addColumn(
            'customer_email',
            array(
                'header'      => $this->__('Customer Email'),
                'index'       => 'customer_email',
                'type'        => 'text',
                'width'       => '100px',
            )
        );

        $this->addColumn(
            'customer_name',
            array(
                'header'      => $this->__('Customer Name'),
                'width'       => '120px',
                'index'       => 'customer_name',
                'type'        => 'text',
            )
        );

        $this->addColumn(
            'customer_group',
            array(
                'header'      => $this->__('Customer Group'),
                'index'       => 'customer_group',
                'type'        => 'text',
                'width'       => '100px',
            )
        );

        $this->addColumn(
            'order_country',
            array(
                'header'      => $this->__('Country'),
                'index'       => 'order_country',
                'type'        => 'country',
                'width'       => '100px',
            )
        );

        $this->addColumn(
            'order_region',
            array(
                'header'      => $this->__('Region'),
                'index'       => 'order_region',
                'type'        => 'text',
                'width'       => '100px',
            )
        );

        $this->addColumn(
            'order_city',
            array(
                'header'      => $this->__('City'),
                'index'       => 'order_city',
                'type'        => 'text',
                'width'       => '100px',
            )
        );

        $this->addColumn(
            'order_postcode',
            array(
                'header'      => $this->__('Zip Code'),
                'index'       => 'order_postcode',
                'type'        => 'text',
                'width'       => '60px',
            )
        );

        $this->addColumn(
            'xname',
            array(
                'header'      => $this->__('Product Name'),
                'index'       => 'xname',
                'type'        => 'text',
            )
        );

        $this->addColumn(
            'product_manufacturer',
            array(
                'header'      => $this->__('Manufacturer'),
                'index'       => 'product_manufacturer',
                'type'        => 'text',
                'width'       => '100px',
            )
        );

        $this->addColumn(
            'xqty_ordered',
            array(
                'header'      => $this->__('Qty. Ordered'),
                'width'       => '60px',
                'index'       => 'xqty_ordered',
                'total'       => 'sum',
                'type'        => 'number',
                'renderer'    => 'advancedreports/widget_grid_column_renderer_percent',
            )
        );

        $this->addColumn(
            'xqty_invoiced',
            array(
                'header'      => $this->__('Qty. Invoiced'),
                'width'       => '60px',
                'index'       => 'xqty_invoiced',
                'total'       => 'sum',
                'type'        => 'number',
                'renderer'    => 'advancedreports/widget_grid_column_renderer_percent',
            )
        );

        $this->addColumn(
            'xqty_shipped',
            array(
                'header'      => $this->__('Qty. Shipped'),
                'width'       => '60px',
                'index'       => 'xqty_shipped',
                'total'       => 'sum',
                'type'        => 'number',
                'renderer'    => 'advancedreports/widget_grid_column_renderer_percent',
            )
        );

        $this->addColumn(
            'xqty_refunded',
            array(
                'header'      => $this->__('Qty. Refunded'),
                'width'       => '60px',
                'index'       => 'xqty_refunded',
                'total'       => 'sum',
                'type'        => 'number',
                'renderer'    => 'advancedreports/widget_grid_column_renderer_percent',
            )
        );

        $this->addColumn(
            'base_xprice',
            array(
                'header'           => $this->__('Price'),
                'width'            => '80px',
                'type'             => 'currency',
                'currency_code'    => $this->getCurrentCurrencyCode(),
                'total'            => 'sum',
                'index'            => 'base_xprice',
                'column_css_class' => 'nowrap',
                'default'          => $defValue,
                'disable_total'    => 1,
            )
        );

        $this->addColumn(
            'base_original_xprice',
            array(
                'header'           => $this->__('Original Price'),
                'width'            => '80px',
                'type'             => 'currency',
                'currency_code'    => $this->getCurrentCurrencyCode(),
                'total'            => 'sum',
                'index'            => 'base_original_xprice',
                'column_css_class' => 'nowrap',
                'default'          => $defValue,
                'disable_total'    => 1,
            )
        );

        $this->addColumn(
            'base_row_subtotal',
            array(
                'header'           => $this->__('Subtotal'),
                'width'            => '80px',
                'type'             => 'currency',
                'currency_code'    => $this->getCurrentCurrencyCode(),
                'total'            => 'sum',
                'index'            => 'base_row_subtotal',
                'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
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
                'total'            => 'sum',
                'index'            => 'base_xdiscount_amount',
                'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                'column_css_class' => 'nowrap',
                'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_xtax_amount',
            array(
                'header'           => $this->__('Tax'),
                'width'            => '80px',
                'type'             => 'currency',
                'currency_code'    => $this->getCurrentCurrencyCode(),
                'total'            => 'sum',
                'index'            => 'base_xtax_amount',
                'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
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
            'base_tax_invoiced',
            array(
                'header'           => $this->__('Tax Invoiced'),
                'width'            => '80px',
                'type'             => 'currency',
                'currency_code'    => $this->getCurrentCurrencyCode(),
                'renderer'         => 'advancedreports/widget_grid_column_renderer_percent',
                'total'            => 'sum',
                'index'            => 'base_tax_invoiced',
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

        $this->addProfitColumns();

        $this->addColumn(
            'view_order',
            array(
                'header'      => $this->__('View Order'),
                'width'       => '70px',
                'type'        => 'action',
                'align'       => 'left',
                'getter'      => 'getOrderId',
                'actions'     => array(
                    array(
                        'caption' => $this->__('View'),
                        'url'     => array(
                            'base'   => 'adminhtml/sales_order/view',
                            'params' => array(),
                        ),
                        'field'   => 'order_id',
                    )
                ),
                'filter'      => false,
                'renderer'      => 'advancedreports/widget_grid_column_renderer_action',
                'sortable'    => false,
                'index'       => 'order_id',
                'is_system'   => true,
            )
        );

        $this->addColumn(
            'view_product',
            array(
                'header'      => $this->__('View Product'),
                'width'       => '70px',
                'type'        => 'action',
                'align'       => 'left',
                'getter'      => 'getProductId',
                'actions'     => array(
                    array(
                        'caption' => $this->__('View'),
                        'url'     => array(
                            'base'   => 'adminhtml/catalog_product/edit',
                            'params' => array(),
                        ),
                        'field'   => 'id',
                    )
                ),
                'filter'      => false,
                'renderer'      => 'advancedreports/widget_grid_column_renderer_action',
                'sortable'    => false,
                'index'       => 'product_id',
                'is_system'   => true,
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

    public function getGridUrl()
    {
        $params = Mage::app()->getRequest()->getParams();
        $params['_secure'] = Mage::app()->getStore(true)->isCurrentlySecure();
        return $this->getUrl('*/*/grid', $params);
    }

    public function hasAggregation()
    {
        return true;
    }
}
