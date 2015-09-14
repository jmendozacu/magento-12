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
 * Sales by Product Report Grid
 */
class AW_Advancedreports_Block_Advanced_Product_Grid extends AW_Advancedreports_Block_Advanced_Grid
{
    protected $_routeOption = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_PRODUCTS;
    protected $_skus = array();
    protected $_filterSkus = array();
    protected $_skuColumns = array();
    protected $_possibleRequiredOptions = array();

    protected $_columnConfigEnabled = false;

    protected $_productsCache = array();
    protected $_parentsAndChilds = array();

    /**
     * Additional skus (Optional skus) for main product
     *
     * @var array
     */
    protected $_additionalSkus = array();

    /**
     * If sku inputed with mask, we restore it here.
     * For future group by sky request
     *
     * @var array
     */
    protected $_maskedSkus = array();

    /**
     * Detail grouped
     */
    const DETAIL_SUMM = 0;

    /**
     * Detail detailed
     */
    const DETAIL_DETAIL = 1;

    protected $_detailOptions
        = array(
            self::DETAIL_SUMM => 'Grouped',
            self::DETAIL_DETAIL => 'Detailed',
        );

    public function __construct()
    {
        parent::__construct();
        $this->setId('gridProduct');
        $this->setShowAdditionalSelector(true);
    }

    public function getDetailKey()
    {
        $detailKey = $this->getFilter('detail_key');
        if ($detailKey === null) {
            $detailKey = 0;
        }
        return $detailKey;
    }

    public function getGrouped()
    {
        return ($this->getDetailKey() == self::DETAIL_SUMM);
    }

    public function getAdditionalSelectorHtml()
    {
        $out = '<div class="report_detail_box" style="margin-right: 3px;">';
        $out .= '<input type="hidden" name="detail_key" id="detail_key" value="'.$this->getDetailKey().'"/>';

        $out .= '<div id="report_detail_list">';
        foreach ($this->_detailOptions as $value => $label) {
            $out .= '<button class="report_period_item ' . (($this->getDetailKey() == $value) ? 'active ' : '') . '" onclick="$(\'detail_key\').value = '. $value. ';'.$this->getRefreshButtonCallback().' ">'
                . $this->__($label) . '</button>';
        }
        $out .= '</div>';
        $out .= '</div>';
        return $out;
    }

    /**
     * Retrieves initialization array for custom report option
     *
     * @return array
     */
    public function  getCustomOptionsRequired()
    {
        $array = parent::getCustomOptionsRequired();
        ///TODO Not implemented feature
        $addArray = array(
            array(
                'id' => 'product_sku_limit',
                'type' => 'text',
                'args' => array(
                    'label' => $this->__('The number of records in the SKU Advisor'),
                    'title' => $this->__('The number of records in the SKU Advisor'),
                    'name' => 'product_sku_limit',
                    'class' => '',
                    'required' => true,
                ),
                'default' => '10'
            ),

        );
        return array_merge($array, $addArray);
    }

    protected function _prepareLayout()
    {
        $this
            ->_prepareFiltersBefore()
            ->_setUpFilters()
        ;
        # prepare SKUs
        if ($this->getFilter('product_sku')) {
            $this->setSkus($this->getFilter('product_sku'));
            Mage::helper('advancedreports')->setSkus($this->getFilter('product_sku'));
        } else {
            if ($skus = Mage::helper('advancedreports')->getSkus()) {
                $this->setSkus($skus);
            }
        }
        parent::_prepareLayout();
        return $this;
    }

    public function getDisableAutoload()
    {
        return true;
    }

    public function getHideShowBy()
    {
        return false;
    }

    public function getIsSalesByProduct()
    {
        return true;
    }

    protected function _addCustomData($row)
    {
        $key = $this->getFilter('reload_key');
        if (count($this->_customData)) {
            foreach ($this->_customData as &$d) {
                if ($d['period'] == $row['period']) {
                    if (isset($d[$row['sku']])) {
                        $qty = $d[$row['sku']];
                        unset($d[$row['sku']]);
                        if (isset($d[$row['column_id']])) {
                            unset($d[$row['column_id']]);
                        }

                        if ($key === 'total') {
                            $d[$row['sku']] = $row['total'] + $qty;
                            $d[$row['column_id']] = $row['total'] + $qty;
                        } else {
                            $d[$row['sku']] = $row['ordered_qty'] + $qty;
                            $d[$row['column_id']] = $row['ordered_qty'] + $qty;
                        }
                    } else {
                        if ($key === 'total') {
                            $d[$row['sku']] = $row['total'];
                            $d[$row['column_id']] = $row['total'];
                        } else {
                            $d[$row['sku']] = $row['ordered_qty'];
                            $d[$row['column_id']] = $row['ordered_qty'];
                        }
                    }

                    if ($row['column_id']) {
                        $d['total_cost'] = $row['total_cost'];
                        $d['total_revenue_excl_tax'] = $row['total_revenue_excl_tax'];
                        $d['total_revenue'] = $row['total_revenue'];
                        $d['total_profit'] = $row['total_profit'];
                        $d['total_margin'] = $row['total_margin'];
                    }

                    unset($d);
                    return $this;
                }
            }
            unset($d);
        }
        $this->_customData[] = $row;
        return $this;
    }

    protected function _isValidSku($sku)
    {
        return $this->_getProductName($sku);
    }

    protected function _getProductName($sku, $parentSku = null, $isAdditional = false)
    {
        if ($isAdditional) {
            $out = Mage::helper('advancedreports')->getProductNameBySku($sku);
            $append = str_replace($parentSku, "", $sku);
            if ($append) {
                $out .= " ($append)";
            }
            return $out;
        } else {
            return Mage::helper('advancedreports')->getProductNameBySku($sku);
        }
    }

    protected function _registerProduct($sku, $isMasked = false)
    {
        $sku = trim($sku);
        $_productId = Mage::getModel('catalog/product')->getIdBySku($sku);
        $product = null;
        if ($_productId) {
            $product = Mage::getModel('catalog/product')->load($_productId);
        }
        $usedProductsIds = null;

        if ($product && $product->getId()
            && $product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE
        ) {
            $usedProductsIds = $product->getTypeInstance(true)->getUsedProductIds($product);
        }

        $preparedSku = $sku;
        # Remove double stars
        while (strpos($preparedSku, "**") !== false) {
            $preparedSku = str_replace("**", "*", trim($preparedSku));
        }
        $preparedSku = str_replace("*", "%", trim($preparedSku));

        /** @var AW_Advancedreports_Model_Mysql4_Collection_Product_Item $items */
        $items = Mage::getResourceModel('advancedreports/collection_product_item')->reInitSelect();
        if ($usedProductsIds && is_array($usedProductsIds)) {
            $usedProductsIds[] = $product->getId();
            $items->addFieldToFilter('product_id', array('in' => $usedProductsIds));
        } elseif ($_productId) {
            $items->addFieldToFilter('product_id', $_productId);
        } else {
            $items->addFieldToFilter('sku', array('like' => $preparedSku));
            $items->groupByAttribute('sku');
        }
        $items->orderByAttribute('parent_item_id');
        $dateFrom = $this->_getMysqlFromFormat($this->getFilter('report_from'));
        $dateTo = $this->_getMysqlToFormat($this->getFilter('report_to'));
        $items->setDateFilter($dateFrom, $dateTo);

        $isGrouped = $this->getGrouped();
        if ($isMasked) {
            $this->_skus[] = $sku;
            if ($isGrouped) {
                $this->_filterSkus[] = $sku;
                $this->addSkuToColumns($sku);
            }

            foreach ($items as $item) {
                $this->_additionalSkus[$sku][] = $item->getSku();
                $this->_filterSkus[] = $item->getSku();
                if ($isGrouped) {
                    continue;
                }
                $this->addSkuToColumns($item->getSku());
            }
            return $this;
        }

        $this->_skus[] = $sku;
        $this->_filterSkus[] = Mage::helper('advancedreports')->getProductSkuBySku($sku);
        $this->addSkuToColumns($sku);

        foreach ($items as $item) {
            if (!$this->_isProductAdditionalFor($sku, $item->getSku(), $item->getProductId())) {
                continue;
            }

            $this->_filterSkus[] = $item->getSku();
            if ($isGrouped) {
                continue;
            }
            $this->_additionalSkus[$sku][] = $item->getSku();
            //prepare custom options columns for product in detailed report
            if (!$_productId || ($_productId && $_productId != $item->getProductId()))  {
                $this->addSkuToColumns($item->getSku());
            }
            $productId = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($productId);
            if (!$product || !$product->getId()) {
                continue;
            }

            $productOptions = $product->getOptions();
            foreach ($productOptions as $option) {
                foreach($option->getValuesCollection() as $optionValue) {
                    $this->addSkuToColumns($optionValue->getDefaultTitle());
                }
            }
        }
        return $this;
    }

    /**
     * Parse filter string and set up skus to report them
     *
     * @param string $value
     */
    public function setSkus($value)
    {
        $skus = explode(',', $value);
        if ($skus && is_array($skus) && count($skus)) {
            foreach ($skus as $sku) {
                #Masked sku
                if (strpos(trim($sku), "*") !== false) {
                    $this->_registerProduct($sku, true);
                } elseif (trim($sku)) {
                    $this->_registerProduct($sku);
                }
            }
        }
    }

    public function addSkuToColumns($sku, $prefix = 'column')
    {
        if ($sku && !isset($this->_skuColumns[$sku])) {
            $this->_skuColumns[$sku] = $prefix . (count($this->_skuColumns) + 1);
        }
        return $this;
    }

    public function getColumnBySku($sku)
    {
        if ($sku && isset($this->_skuColumns[$sku])) {
            return $this->_skuColumns[$sku];
        }
        return null;
    }

    public function getSkus()
    {
        return $this->_skus;
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

    /**
     * Search sku in additionl skus.
     * Retrieves existanse flag.
     *
     * @param string $sku Sku for search
     *
     * @return boolean
     */
    protected function _isInAdditional($sku)
    {
        foreach ($this->_additionalSkus as $k => $skus) {
            if (isset($skus) && is_array($skus) && in_array($sku, $skus)) {
                return true;
            }
        }
        return false;
    }

    protected function _getOrderCollection($from, $to)
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Product $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_product');
        $collection->reInitSelect();
        $collection->setDateFilter($from, $to)->setState();
        $collection->addFieldToFilter('item.sku', array('in' => $this->_filterSkus))->addItems(true, $from, $to);
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
            $collection->setStoreIds($storeIds);
        }

        if (count($this->getSkus()) == 1 && $this->getGrouped()) {
            $collection->addProfitInfo($from, $to);
        }
        return $collection;
    }

    protected function _prepareData()
    {
        $chartLabels = array();
        if (count($this->getSkus())) {
            # primary analise
            $_intervals = $this->getCollection()->getIntervals();
            foreach ($_intervals as $_item) {
                $row = array();
                $items = $this->_getOrderCollection($_item['start'], $_item['end']);
                $row['period'] = $_item['title'];
                $this->_addCustomData($row);
                foreach ($items as $item) {
                    if (!in_array($item->getSku(), $this->_skus)
                        && !$this->_isInAdditional($item->getSku())
                        && !in_array($item->getSku(), $this->_filterSkus)
                    ) {
                        continue;
                    }

                    $row['total_cost'] = $item->getTotalCost();
                    $row['total_revenue_excl_tax'] = $item->getTotalRevenueExclTax();
                    $row['total_revenue'] = $item->getTotalRevenue();
                    $row['total_profit'] = $item->getTotalProfit();
                    $row['total_margin'] = $item->getTotalMargin();

                    $_parentProductId = $item->getParentProductId();
                    if (false !== ($_indexKey = array_search($item->getItemProductId(), $this->_possibleRequiredOptions))) {
                        $_parentProductId = $this->_possibleRequiredOptions[$_indexKey];
                    }

                    if (null !== $_parentProductId
                        && $_parentProductId != $item->getItemProductId()
                    ) {
                        if (!isset($_parentProduct) || $_parentProduct->getId() != $_parentProductId) {
                            $_parentProduct = Mage::getModel('catalog/product')->load($_parentProductId);
                        }

                        if ($_parentProduct->getId()) {
                            $row['period'] = $_item['title'];

                            if ($_parentProduct->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
                                $row['sku'] = $item->getSku();
                                $row['column_id'] = $this->getColumnBySku($item->getSku());
                                $row['ordered_qty'] = $item->getSumQty();
                                $row['total'] = $item->getSumTotal() == 0 ? $item->getParentSumTotal() : $item->getSumTotal();
                                $this->_addCustomData($row);
                            }
                            continue;
                        }
                    }

                    if (null !== $_parentProductId
                        && $_parentProductId == $item->getItemProductId()
                    ) {
                        if (!isset($_parentProduct) || $_parentProduct->getId() != $_parentProductId) {
                            $_parentProduct = Mage::getModel('catalog/product')->load($_parentProductId);
                        }
                        $productOptions = unserialize($item->getProductOptions());
                        $customOptions = array();
                        if (array_key_exists('options', $productOptions)) {
                            $customOptions = $productOptions['options'];
                        }
                        foreach($customOptions as $option) {
                            if (!isset($option['option_id'])) {
                                continue;
                            }
                            $values = array();
                            if (array_key_exists('option_value',$option) && isset($option['option_value'])) {
                                $values = explode(',', $option['option_value']);
                            }
                            $optionModel = Mage::getModel('catalog/product_option')->load($option['option_id']);

                            foreach ($optionModel->getValuesCollection() as $optValue) {
                                if (array_search($optValue->getId(), $values) === false) {
                                    continue;
                                }

                                $row['period'] = $_item['title'];
                                $row['sku'] = trim($optValue->getDefaultTitle());
                                $row['column_id'] = $this->getColumnBySku(trim($optValue->getDefaultTitle()));
                                $row['ordered_qty'] = $item->getSumQty();
                                $row['total'] = $item->getSumTotal() == 0 ? $item->getParentSumTotal() : $item->getSumTotal();

                                $this->_addCustomData($row);
                            }
                        }
                        $row['period'] = $_item['title'];
                        $row['sku'] = $_parentProduct->getSku();
                        $row['column_id'] = $this->getColumnBySku($_parentProduct->getSku());
                        $row['ordered_qty'] = $item->getSumQty();
                        $row['total'] = $item->getSumTotal() == 0 ? $item->getParentSumTotal() : $item->getSumTotal();

                        $this->_addCustomData($row);
                        continue;
                    }

                    if (!$_parentProductId
                        && $item->getProductType() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE
                    ) {
                        $product = Mage::getModel('catalog/product')->load($item->getItemProductId());
                        if (!$product || !$product->getId()) {
                            continue;
                        }
                        $row['period'] = $_item['title'];
                        $row['sku'] = $product->getSku();
                        $row['column_id'] = $this->getColumnBySku($product->getSku());
                        $row['ordered_qty'] = $item->getSumQty();
                        $row['total'] = $item->getSumTotal() == 0 ? $item->getParentSumTotal() : $item->getSumTotal();

                        $this->_addCustomData($row);
                        continue;
                    }

                    if ((null === $_parentProductId && !$this->_isInAdditional($item->getSku()))
                        || (!$this->getGrouped())
                        || (null === $_parentProductId && $this->_isInAdditional($item->getSku()) && $this->getGrouped())
                    ) {
                        $row['period'] = $_item['title'];
                        $row['sku'] = $item->getSku();
                        $row['column_id'] = $this->getColumnBySku($item->getSku());
                        $row['ordered_qty'] = $item->getSumQty();
                        $row['total'] = $item->getSumTotal() == 0 ? $item->getParentSumTotal() : $item->getSumTotal();

                        $this->_addCustomData($row);
                   }
                }
            }

            # final preporation of data
            if (count($this->_customData)) {
                foreach ($this->getSkus() as $sku) {
                    foreach ($this->_customData as &$d) {
                        if (!isset($d[$sku])) {
                            $d[$sku] = 0;
                        }
                    }
                    if ($this->getGrouped()) {
                        if (isset($this->_additionalSkus[$sku]) && is_array($this->_additionalSkus[$sku])) {
                            foreach (array_unique($this->_additionalSkus[$sku]) as $addSku) {
                                foreach ($this->_customData as &$d) {
                                    if (isset($d[$addSku]) && isset($d[$sku])) {
                                        $d[$sku] += $d[$addSku];
                                        $d[$this->getColumnBySku($sku)] = $d[$sku];
                                    }
                                }
                            }
                        }
                    } else {
                            foreach ($this->_skuColumns as $addSku => $column) {
                                foreach ($this->_customData as &$d) {
                                    if (!isset($d[$addSku])) {
                                        $d[$addSku] = 0;
                                    }
                                }
                            }
                    }
                }
            }

            foreach ($this->_skuColumns as $sku => $column) {
                $chartLabels[$sku] = $sku;
            }
        }

        $chartKeys = array();
        foreach ($this->_skuColumns as $sku => $column) {
            $chartKeys[] = $sku;
        }

        # Reclean data
        $newData = array();

        foreach ($this->_customData as $data) {
            $newSubData = array();
            foreach ($data as $k => $v) {
                if ($k) {
                    $newSubData[$k] = $v;
                }
            }
            $newData[] = $newSubData;
        }

        $this->_customData = $newData;
        $this->_preparePage();
        $this->getCollection()->setSize(count($this->_customData));
        Mage::helper('advancedreports')->setChartData($this->_customData, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        Mage::helper('advancedreports')->setChartKeys($chartKeys, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        Mage::helper('advancedreports')->setChartLabels($chartLabels, Mage::helper('advancedreports')->getDataKey($this->_routeOption));
        parent::_prepareData();
        return $this;
    }

    protected function _getProductBySku($sku)
    {
        if (!isset($this->_productsCache[$sku])) {
            /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
            $productCollection = Mage::getModel('catalog/product')->getCollection();
            $productCollection->addFieldToFilter('sku', array('eq' => $sku));
            $this->_productsCache[$sku] = $productCollection->getSize() ? $productCollection->getFirstItem() : false;
        }
        return $this->_productsCache[$sku];
    }

    protected function _isProductAdditionalFor($sku, $addSku, $addProductId = null)
    {
        /** @var $generalProduct Mage_Catalog_Model_Product */
        $generalProduct = $this->_getProductBySku($sku);
        /** @var $additionalProduct Mage_Catalog_Model_Product */
        $additionalProduct = $this->_getProductBySku($addSku);

        //possible sku included options labels
        if (false === $additionalProduct && null !== $addProductId) {
            $additionalProduct = Mage::getModel('catalog/product')->load($addProductId);
            if ($additionalProduct->getId() == $generalProduct->getId()
            ) {
                array_push($this->_possibleRequiredOptions, $generalProduct->getId());
                return true;
            }
        }

        if ($generalProduct && $additionalProduct) {
            $gpId = $generalProduct->getId();
            $apId = $additionalProduct->getId();
            if (!isset($this->_parentsAndChilds[$gpId])) {
                $this->_parentsAndChilds[$gpId] = $generalProduct->getTypeInstance()->getChildrenIds(
                    $generalProduct->getId()
                );
                $this->_parentsAndChilds[$gpId] = isset($this->_parentsAndChilds[$gpId][0])
                    ? $this->_parentsAndChilds[$gpId][0] : array();
            }
            return in_array($apId, $this->_parentsAndChilds[$gpId]);
        }
        return false;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'period',
            array(
                'header'            => $this->getPeriodText(),
                'width'             => '150px',
                'align'             => 'right',
                'index'             => 'period',
                'type'              => 'text',
                'header_css_class'  => 'column-period-header',
                'sortable'          => true,
                'is_period_sorting' => true,
            )
        );

        $key = $this->getFilter('reload_key');
        $defValue = sprintf("%f", 0);
        $defValue = Mage::app()->getLocale()->currency($this->getCurrentCurrencyCode())->toCurrency($defValue);
        $defValue = $key === 'total' ? $defValue : '0';
        $type = $key === 'total' ? 'currency' : 'number';

        foreach ($this->_skus as $sku) {
            if (strpos(trim($sku), "*") && $this->getGrouped()) {
                $this->addColumn(
                    $this->getColumnBySku($sku),
                    array(
                        'header'        => $sku,
                        'index'         => $this->getColumnBySku($sku),
                        'type'          => $type,
                        'currency_code' => $this->getCurrentCurrencyCode(),
                        'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                        'default'       => $defValue,
                    )
                );
            } elseif(!strpos(trim($sku), "*")) {
                $this->addColumn(
                    $this->getColumnBySku($sku),
                    array(
                        'header'        => $this->_getProductName($sku),
                        'index'         => $this->getColumnBySku($sku),
                        'type'          => $type,
                        'currency_code' => $this->getCurrentCurrencyCode(),
                        'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                        'default'       => $defValue,
                    )
                );
            }

            # Add columns with additional filter
            if (!$this->getGrouped()) {
                foreach ($this->_skuColumns as $addSku => $column) {
                    $this->addColumn(
                        $column,
                        array(
                            'header'        => $this->_getProductName($addSku, $sku, true),
                            'index'         => $column,
                            'type'          => $type,
                            'currency_code' => $this->getCurrentCurrencyCode(),
                            'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                            'default'       => $defValue,
                        )
                    );
                }
            }
        }
        $skus = $this->getSkus();
        if (count($skus) == 1 && $this->getGrouped() && !strpos(trim(array_pop($skus)), "*")) {
            $this->addProfitColumns();
        }
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