<?php

class AW_Advancedreports_Block_Additional_Salesbyproductattr_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    protected $_routeOption
        = AW_Advancedreports_Helper_Additional_Salesbyproductattr::ROUTE_ADDITIONAL_SALESBYPRODUCTATTR
    ;
    protected $_filter = null;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(Mage::helper('advancedreports')->getGridTemplate());
        $this->setExportVisibility(true);
        $this->setStoreSwitcherVisibility(true);
        $this->setId('gridAdditionalSalesbyproductattr');
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

    public function getProductAttributeFilter()
    {
        return $this->_filter;
    }

    public function _prepareCollection()
    {
        $this->prepareReportCollection();
        $this->_prepareData();
        return $this;
    }

    public function prepareReportCollection()
    {
        $filterStr = base64_decode($this->getRequest()->getParam($this->getVarNameFilter()));
        parse_str($filterStr, $data);
        if (array_key_exists('productattr', $data)) {
            $this->_filter = $data['productattr'];
        }
        parent::_prepareOlderCollection();

        return $this;
    }

    protected function _getItemStatistics($from, $to, $filter, $pageNum, $pageSize)
    {
        /** $collection @var AW_Advancedreports_Model_Mysql4_Collection_Additional_Salesbyproductattr */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_salesbyproductattr');

        $collection->reInitItemSelect($filter);

        $collection->setDateFilter($from, $to)->setState();
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        $read = Mage::helper('advancedreports')->getReadAdapter();
        $select = clone $collection->getSelect();
        $select->limitPage($pageNum, $pageSize);

        return $read->fetchAll($select->__toString());
    }

    protected function _prepareData()
    {
        Varien_Profiler::start('aw::advancedreports::salesbyproductattribute::prepare_data');

        # primary analise
        foreach ($this->getCollection()->getIntervals() as $_item) {

            $row = array();
            $row['period'] = $_item['title'];
            $row['qty_ordered'] = 0;
            $row['base_row_subtotal'] = 0;
            $row['base_tax_amount'] = 0;
            $row['base_row_total'] = 0;
            $row['base_row_invoiced'] = 0;
            $row['base_amount_refunded'] = 0;

            $pageNum = 1;
            $pageSize = 3000;
            while (true) {
                $items = $this->_getItemStatistics($_item['start'], $_item['end'], $this->_filter, $pageNum, $pageSize);
                if (count($items) == 0) {
                    break;
                }
                foreach ($items as $item) {
                    if ($item['product_type'] == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                        continue;
                    }
                    if ($item['product_type'] == 'bundle') {
                        if (!$item['product_id']) {
                            continue;
                        }
                        $_product = Mage::getModel('catalog/product')->load($item['product_id']);
                        if (!$_product || !$_product->getId() || $_product->getPrice() == 0) {
                            continue;
                        }
                    }
                    $row['qty_ordered'] += $item['qty_ordered'];
                    $row['base_row_subtotal']+= $item['base_row_subtotal'];
                    $row['base_tax_amount'] += $item['base_tax_amount'];
                    $row['base_row_total']+= $item['base_row_total'];
                    $row['base_row_invoiced'] += $item['base_row_invoiced'];
                    $row['base_amount_refunded'] += $item['base_amount_refunded'];
                }
                $pageNum++;
            }
            $this->_addCustomData($row);
        }

        $helper = Mage::helper('advancedreports');
        $chartLabels = array(
            'base_row_invoiced' => $helper->__('Invoiced')
        );
        $helper->setChartData($this->_customData, $helper->getDataKey($this->_routeOption));
        $helper->setChartKeys(array_keys($chartLabels), $helper->getDataKey($this->_routeOption));
        $helper->setChartLabels($chartLabels, $helper->getDataKey($this->_routeOption));
        $this->_preparePage();
        $this->getCollection()->setSize(count($this->_customData));
        parent::_prepareData();
        Varien_Profiler::stop('aw::advancedreports::salesbyproductattribute::prepare_data');
        return $this;
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('advancedreports');
        $defValue = sprintf("%f", 0);
        $defValue = Mage::app()->getLocale()->currency($this->getCurrentCurrencyCode())->toCurrency($defValue);

        $this->addColumn(
            'period',
            array(
                'header'            => $this->getPeriodText(),
                'align'             => 'right',
                'width'             => '150px',
                'index'             => 'period',
                'type'              => 'text',
                'is_period_sorting' => true,
            )
        );

        $this->addColumn(
            'qty_ordered',
            array(
                'header' => $helper->__('Items Ordered'),
                'width'  => '60px',
                'index'  => 'qty_ordered',
                'total'  => 'sum',
                'type'   => 'number'
            )
        );

        $this->addColumn(
            'base_row_subtotal',
            array(
                'header'           => $helper->__('Subtotal'),
                'width'            => '80px',
                'type'             => 'currency',
                'currency_code'    => $this->getCurrentCurrencyCode(),
                'total'            => 'sum',
                'index'            => 'base_row_subtotal',
                'column_css_class' => 'nowrap',
                'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_tax_amount',
            array(
                'header'           => $helper->__('Tax'),
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
            'base_row_total',
            array(
                'header'           => $helper->__('Total'),
                'width'            => '80px',
                'type'             => 'currency',
                'currency_code'    => $this->getCurrentCurrencyCode(),
                'total'            => 'sum',
                'index'            => 'base_row_total',
                'column_css_class' => 'nowrap',
                'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_row_invoiced',
            array(
                'header'           => $helper->__('Invoiced'),
                'width'            => '80px',
                'type'             => 'currency',
                'currency_code'    => $this->getCurrentCurrencyCode(),
                'total'            => 'sum',
                'index'            => 'base_row_invoiced',
                'column_css_class' => 'nowrap',
                'default'          => $defValue,
            )
        );

        $this->addColumn(
            'base_amount_refunded',
            array(
                'header'           => $helper->__('Refunded'),
                'width'            => '80px',
                'type'             => 'currency',
                'currency_code'    => $this->getCurrentCurrencyCode(),
                'total'            => 'sum',
                'index'            => 'base_amount_refunded',
                'column_css_class' => 'nowrap',
                'default'          => $defValue,
            )
        );

        $this->addExportType('*/*/exportOrderedCsv', $helper->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel', $helper->__('Excel'));
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