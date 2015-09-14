<?php

class AW_Advancedreports_Block_Additional_Salesstatistics_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    protected $_routeOption = AW_Advancedreports_Helper_Additional_Salesstatistics::ROUTE_ADDITIONAL_SALESSTATISTICS;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(Mage::helper('advancedreports')->getGridTemplate());
        $this->setExportVisibility(true);
        $this->setStoreSwitcherVisibility(true);
        $this->setId('gridSalesstatistics');
    }

    public function getHideShowBy()
    {
        return false;
    }

    protected function _addCustomData($row)
    {
        $this->_customData[] = $row;
        return $this;
    }

    public function _prepareCollection()
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

    protected function _getItemStatistics($from, $to)
    {
        /** $collection @var AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesstatistics */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_salesstatistics');

        $collection->reInitItemSelect();

        $collection->setDateFilter($from, $to)->setState();
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }
        $collection->addItems();

        if (count($collection)) {
            foreach ($collection as $item) {
                return $item;
            }
        }
        return new Varien_Object();
    }

    protected function _getOrderStatistics($from, $to)
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesstatistics $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_salesstatistics');

        $collection->reInitOrderSelect();

        $collection->setDateFilter($from, $to)->setState();
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        if (count($collection)) {
            foreach ($collection as $item) {
                return $item;
            }
        }
        return new Varien_Object();
    }

    protected function _prepareData()
    {
        # primary analise
        foreach ($this->getCollection()->getIntervals() as $_item) {
            $row = $this->_getOrderStatistics($_item['start'], $_item['end']);
            $item = $this->_getItemStatistics($_item['start'], $_item['end']);
            $row->setData('items_count', max(intval($item->getData('items_count')), 0));
            $row->setData('items_invoiced', max(intval($item->getData('items_invoiced')), 0));

            $row->setPeriod($_item['title']);

            if (!$row->getOrdersCount()) {
                $row->setOrdersCount(0);
            }
            if (!$row->getBaseDiscountAmount()) {
                $row->setBaseDiscountAmount(0);
            }
            if ($row->getOrdersCount()) {
                $row->setAvgOrderAmount($row->getBaseTotalInvoiced() / $row->getOrdersCount());
            } else {
                $row->setAvgOrderAmount(0);
            }
            if ($row->getData('items_invoiced')) {
                $row->setAvgItemCost($row->getBaseTotalInvoiced() / $row->getData('items_invoiced'));
            } else {
                $row->setAvgItemCost(0);
            }

            $this->_addCustomData($row->getData());
        }

        $chartLabels = array(
            'avg_order_amount' => $this->__('Order Amount(Avg)'),
            'avg_item_cost'    => $this->__('Item Price(Avg)')
        );
        $keys = array();
        foreach ($chartLabels as $key => $value) {
            $keys[] = $key;
        }
        $this->_preparePage();
        $this->getCollection()->setSize(count($this->_customData));

        Mage::helper('advancedreports')->setChartData($this->_customData, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        Mage::helper('advancedreports')->setChartKeys($keys, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        Mage::helper('advancedreports')->setChartLabels($chartLabels, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        parent::_prepareData();
        return $this;
    }

    protected function _prepareColumns()
    {
        $defValue = sprintf("%f", 0);
        $defValue = Mage::app()->getLocale()->currency($this->getCurrentCurrencyCode())->toCurrency($defValue);

        $this->addColumn(
            'period',
            array(
                'header'            => $this->getPeriodText(),
                'width'             => '150px',
                'index'             => 'period',
                'align'             => 'right',
                'type'              => 'text',
                'is_period_sorting' => true,
            )
        );

        $this->addColumn(
            'orders_count',
            array(
                 'header' => $this->__('Orders'),
                 'width'  => '60px',
                 'index'  => 'orders_count',
                 'total'  => 'sum',
                 'type'   => 'number'
            )
        );

        $this->addColumn(
            'items_count',
            array(
                 'header' => $this->__('Items'),
                 'width'  => '60px',
                 'index'  => 'items_count',
                 'total'  => 'sum',
                 'type'   => 'number'
            )
        );

        $this->addColumn(
            'base_subtotal',
            array(
                 'header'           => $this->__('Subtotal'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
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

        $this->addColumn(
            'avg_order_amount',
            array(
                 'header'           => $this->__('Order Amount(Avg)'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'total'            => 'sum',
                 'index'            => 'avg_order_amount',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
                 'disable_total'    => 1,
            )
        );

        $this->addColumn(
            'avg_item_cost',
            array(
                 'header'           => $this->__('Item Final Price(Avg)'),
                 'width'            => '80px',
                 'type'             => 'currency',
                 'currency_code'    => $this->getCurrentCurrencyCode(),
                 'total'            => 'sum',
                 'index'            => 'avg_item_cost',
                 'column_css_class' => 'nowrap',
                 'default'          => $defValue,
                 'disable_total'    => 1,
            )
        );

        $this->addExportType('*/*/exportOrderedCsv', $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel', $this->__('Excel'));

        return $this;
    }

    public function getChartType()
    {
        return AW_Advancedreports_Block_Chart::CHART_TYPE_MULTY_LINE;
    }

    public function getPeriods()
    {
        return parent::_getOlderPeriods();
    }
}
