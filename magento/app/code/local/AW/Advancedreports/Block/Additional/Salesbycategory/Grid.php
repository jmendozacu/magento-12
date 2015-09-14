<?php
/**
 * Sales by Coupon Code Report Grid
 */
class AW_Advancedreports_Block_Additional_Salesbycategory_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    /**
     * Route to get config from helper
     * @var string
     */
    protected $_routeOption
        = AW_Advancedreports_Helper_Additional_Salesbycategory::ROUTE_ADDITIONAL_SALESBYCATEGORY
    ;

    public function __construct()
    {
        parent::__construct();
        $this->setId('gridAdditionalSalesbycategory');
    }

    public function getHideShowBy()
    {
        return false;
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

    protected function _getCategoriesStatistics($from, $to, $categoryNames)
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Standard_Sales $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_salesbycategory');
        $collection->reInitSelect()
            ->addOrderItemInfo()
            ->addCategoryFilter(trim($categoryNames))
            ->setState()
            ->setDateFilter($from, $to)
            ->addProfitInfo($from, $to)
        ;

        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }

        return $collection;
    }

    public function getAdditionalSelectorHtml()
    {
        $dropdownLabel = $this->__("(Sub)category");
        $dropdownTitle = $this->__("Enter product categories, separated by comma");
        $dropdownValue = $this->getFilter("product_category");
        $dropdownUrl = $this->getUrl('advancedreports_admin/additional_salesbycategory/getCategory', array('category'=>'{{category}}', 'limit'=>'{{limit}}'));
        $dropdownContent = '<li id="{{category_id}}" class="is_li">'
                                .'<div id="overlay_{{category_id}}" class="aw_arep_category_li_overlay" onmousemove="_categories.mouseOver(event); return false;" onclick="_categories.mouseClick(event); return false;" ></div>'
                                .'<span id="span_{{category_id}}">{{category_title}}</span>'
                            .'</li>';

        $output = "<div class='f-left aw_arep_category_container'>
                    <div style='float:left'>{$dropdownLabel}:&nbsp;<input class='input-text no-changes required-entry advanced-filter' title='{$dropdownTitle}'  value='{$dropdownValue}' type='text' name='product_category' id='product_category' autocomplete='off'/>&nbsp;</div>
                    <div class='arep-button-set category-grid'>
                        {$this->getRefreshButtonHtml()}
                    </div>
                    <div id='product_category_advaice'></div>
                    <div id='product_category_loader' class='aw_arep_category_loader'></div>
                    <div id='product_category_dropdown' class='aw_arep_category_dropdown'>
                        <ul id='aw_arep_category_ul' class='product_category_dropdown_ul' onmouseout='_categories.mouseOut(event); return false;' ></ul>
                    </div>
                    <script type='text/javascript'>
                        var _categories = new AWAdvancedReportsCategories({
                            input:    'product_category',
                            loader:   'product_category_loader',
                            dropdown: 'product_category_dropdown',
                            category_ul: 'aw_arep_category_ul',
                            disable_selector: '#product_category_dropdown ul li.active',
                            url: '{$dropdownUrl}',
                            template: '{$dropdownContent}'
                        });
                    </script>
                </div>";

        return $output;
    }

    public function getShowAdditionalSelector()
    {
        return true;
    }

    protected function _addCustomData($row)
    {
        $this->_customData[] = $row;
        return $this;
    }

    protected function _prepareData()
    {
        $categoryNames = $this->getFilter('product_category');
        if (!$categoryNames) {
            $this->getCollection()->setInterval(null, null);
            return $this;
        }
        foreach ($this->getCollection()->getIntervals() as $_item) {
            $row = array();
            foreach ($this->_getCategoriesStatistics($_item['start'], $_item['end'], $categoryNames) as $item) {
                $row = $item->getData();
            }
            $row['period'] = $_item['title'];
            if (!isset($row['items_ordered'])) {
                $row['items_ordered'] = 0;
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

    protected function _prepareColumns()
    {
        $this->addColumn(
            'period',
            array(
                'header'            => $this->__('Period'),
                'width'             => '150px',
                'index'             => 'period',
                'type'              => 'text',
                'align'             => 'right',
                'header_css_class'  => 'column-period-header',
                'is_period_sorting' => true,
            )
        );

        $this->addColumn(
            'items_ordered',
            array(
                'header' => Mage::helper('reports')->__('Items Ordered'),
                'index'  => 'items_ordered',
                'total'  => 'sum',
                'type'   => 'number',
            )
        );

        $currencyCode = $this->getCurrentCurrencyCode();
        $this->addColumn(
            'sum_tax_invoiced',
            array(
                'header'        => Mage::helper('reports')->__('Tax Invoiced'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'sum_tax_invoiced',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'sum_invoiced',
            array(
                'header'        => Mage::helper('reports')->__('Invoiced'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'sum_invoiced',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'sum_subtotal',
            array(
                'header'        => Mage::helper('reports')->__('Subtotal'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'sum_subtotal',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'sum_total',
            array(
                'header'        => Mage::helper('reports')->__('Total'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'sum_total',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'sum_refunded',
            array(
                'header'        => Mage::helper('reports')->__('Refunded'),
                'type'          => 'currency',
                'currency_code' => $currencyCode,
                'index'         => 'sum_refunded',
                'total'         => 'sum',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'total_cost',
            array(
                'header'        => Mage::helper('reports')->__('Total Cost'),
                'width'         => '120px',
                'type'          => 'currency',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'index'         => 'total_cost',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'total_revenue_excl_tax',
            array(
                'header'        => Mage::helper('reports')->__('Total Revenue (excl.tax)'),
                'width'         => '120px',
                'type'          => 'currency',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'index'         => 'total_revenue_excl_tax',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'total_revenue',
            array(
                'header'        => Mage::helper('reports')->__('Total Revenue'),
                'width'         => '120px',
                'type'          => 'currency',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'index'         => 'total_revenue',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'total_profit',
            array(
                'header'        => Mage::helper('reports')->__('Total Profit'),
                'width'         => '120px',
                'type'          => 'currency',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'index'         => 'total_profit',
                'renderer'      => 'adminhtml/report_grid_column_renderer_currency',
            )
        );

        $this->addColumn(
            'total_margin',
            array(
                'header'        => Mage::helper('reports')->__('Total Margin'),
                'width'         => '120px',
                'type'          => 'text',
                'index'         => 'total_margin',
                'renderer'      => 'advancedreports/widget_grid_column_renderer_profit',
                'disable_total' => true,
            )
        );

        $this->addExportType('*/*/exportOrderedCsv/name/' . $this->_getName(), $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel/name/' . $this->_getName(), $this->__('Excel'));

        return $this;
    }

    public function getIsSalesByCategory()
    {
        return true;
    }

    public function getChartType()
    {
        return AW_Advancedreports_Block_Chart::CHART_TYPE_LINE;
    }

    public function getPeriods()
    {
        return parent::_getOlderPeriods();
    }
}