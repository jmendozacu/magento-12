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
 * Advanced Grid Abstract Class
 */
class AW_Advancedreports_Block_Advanced_Grid extends Mage_Adminhtml_Block_Report_Grid
{
    /**
     * Array of custom data that using to build chart
     *
     * @var array
     */
    protected $_customData = array();

    /**
     * Array of Custom Data converted. Each element is Varien_Object
     *
     * @var array
     */
    protected $_customVarData;

    /**
     * Current timezone
     *
     * @var string
     */
    protected $_ctz;

    /**
     * Possible filters to save
     *
     * @var array
     */
    protected $_filtersToSave = array(
        'custom_date_range',
        'report_from',
        'report_to',
        'report_period',
    );

    protected $_customOptions = array();

    /**
     * Saved Grand Totals
     *
     * @var array
     */
    protected $_grandTotals;

    /**
     * Key to sort Custom Data
     *
     * @var string
     */
    protected $_sortBy;

    /**
     * StoreIds cache
     *
     * @var array
     */
    protected $_storeIds;

    /**
     * Table names cache
     *
     * @deprecated
     * @var array
     */
    protected $_tables = array();

    protected $_columnConfigEnabled = true;

    protected $_subReportSize = null;
    protected $_defaultLimit  = 50;

    protected $_defaultPercentField = null;

    protected $_filterValues = array();

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(Mage::helper('advancedreports')->getGridTemplate());
        $this->setExportVisibility(true);
        $this->setPagerVisibility(true);
        $this->setStoreSwitcherVisibility(true);
    }

    protected function _prepareLayout()
    {
        /** @var Mage_Page_Block_Html_Head $headBlock */
        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $headBlock->addJs('advancedreports/timeframe.js');
            $headBlock->addItem('skin_css', 'aw_advancedreports/css/styles.css');
            $headBlock->addItem('skin_css', 'aw_advancedreports/css/timeframe.css');
            $headBlock->addItem('skin_css', 'aw_advancedreports/css/gui.css');
        }

        return parent::_prepareLayout();
    }
    /**
     * prepare data for dashboard, export and others
     *
     */
    public function prepareBlockData()
    {
        $this->getCollection()->setPageSize(1000);
        $this->_prepareData();

        return $this;
    }

    /**
     * prepare collection for dashboard, export and others
     *
     */
    public function prepareBlockCollection()
    {
        $this->_prepareCollection();

        return $this;
    }

    /**
     * Retrieves flag to reload grid after setting up of filter
     *
     * @return boolean
     */
    public function getNeedReload()
    {
        return Mage::helper('advancedreports')->getNeedReload($this->_routeOption);
    }

    public function getDefaultPercentField()
    {
        return $this->_defaultPercentField;
    }

    public function setDefaultPercentField($field)
    {
        $this->_defaultPercentField = $field;
        return $this;
    }
    /**
     * Retrieves name of table in DB
     *
     * @deprecated
     *
     * @param string $tableName
     *
     * @return string
     */
    public function getTable($tableName)
    {
        if (!isset($this->_tables[$tableName])) {
            $this->_tables[$tableName] = Mage::getSingleton('core/resource')->getTableName($tableName);
        }
        return $this->_tables[$tableName];
    }

    public function getReportsListHtml()
    {
        return $this->getLayout()->createBlock('advancedreports/reports_list', 'advancedreports.reports.list')->toHtml();
    }

    /**
     * Retrieves Advanced Store Switcher
     *
     * @return string
     */
    public function getStoreSwitcherHtml()
    {
        $block = $this->getLayout()->createBlock('advancedreports/store_switcher', 'advancedreports.store.switcher');
        if ($block) {
            return $block->toHtml();
        }
        return parent::getStoreSwitcherHtml();
    }

    public function getPagerHtml()
    {
        $block = $this->getLayout()->createBlock('advancedreports/reports_pager', 'advancedreports.pager');
        if ($block) {
            $block
                ->setCollection($this->getCollection())
                ->setJsObjectName($this->getJsObjectName())
                ->setVarNameLimit($this->getVarNameLimit())
            ;
            return $block->toHtml();
        }
        return '';
    }

    /**
     * Retrieves flag to calculate
     *
     * @return boolean
     */
    public function getNeedTotal()
    {
        return Mage::helper('advancedreports')->getNeedTotal($this->_routeOption);
    }

    /**
     * Retrieves current report option
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->_routeOption;
    }

    public function getOptionsValues()
    {
        $hash = array();

        # Getting custom options
        $custom = $this->getCustomOptionsRequired();
        foreach ($custom as $option) {
            if (($id = $option['id']) != 'custom_columns') {
                $hash[] = $this->getCustomOption($id);
            }
        }

        # Getting order status values
        $hash[] = Mage::helper('advancedreports')->confProcessOrders();
        return str_replace(",", "_", (implode("_", $hash)));
    }

    /**
     * Retrieves array with prepared data
     * <ul>
     *  <li>No filter</li>
     *  <li>No sort</li>
     * </ul>
     *
     * @param datetime $from
     * @param datetime $to
     *
     * @return array
     */
    public function getPreparedData($from, $to)
    {
        return array();
    }

    /**
     * Retrieves aggregator
     *
     * @return AW_Advancedreports_Helper_Tools_Aggregator
     */
    public function getAggregator()
    {
        return Mage::helper('advancedreports')->getAggregator();
    }

    /**
     * Set up column filters
     */
    protected function _setColumnFilters()
    {
        if (count($this->getColumns())) {
            foreach ($this->getColumns() as $column) {
                if ($filter = $this->getFilter($column->getId())) {
                    if (!is_array($filter)) {
                        $filter = trim($filter);
                    }
                    $column->getFilter()->setValue($filter);
                    $this->_addColumnFilterToCollection($column);
                }
            }
        }
    }

    public function getSetupHtml()
    {
        return Mage::helper('advancedreports')->getSetup()->setReportId($this->getId())->getHtml();
    }

    /**
     * Retrieves custom option value
     *
     * @param string $path
     *
     * @return string
     */
    public function getCustomOption($path)
    {
        if (!isset($this->_customOptions[$path])) {
            if (($value = Mage::helper('advancedreports')->getSetup()->getCustomConfig($path)) !== null) {
                return $this->_customOptions[$path] = $value;
            } elseif (is_array($options = $this->getCustomOptionsRequired())) {
                foreach ($options as $option) {
                    if ($option['id'] == $path) {
                        return $this->_customOptions[$path] = $option['default'];
                    }
                }
            }
        }
        return isset($this->_customOptions[$path]) ? $this->_customOptions[$path] : null;
    }

    /**
     * Convert stdObj to array
     *
     * @param stdObj $d
     *
     * @return array
     */
    protected function _objectToArray($d)
    {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }
        return $d;
    }

    /**
     * Retrieves array with options
     *
     * @param string $columnId
     *
     * @return array
     */
    public function findColumnCustomOptions($columnId)
    {
        try {
            $customOptions = unserialize($this->getCustomOption('custom_columns'));

            if (!is_array($customOptions)) {
                return null;
            }
            foreach ($customOptions as $cOption) {
                $option = $this->_objectToArray($cOption);
                if ($option['column_id'] == $columnId) {
                    return $option;
                }
            }
        } catch (Exception $e) {
            # Nothing to do
            Mage::throwException($e->getMessage());
        }
        return null;
    }

    public function getColumns()
    {
        if (!$this->getData('fetched_custom_columns')) {
            Varien_Profiler::start('aw::advancedreports::grid::retrives_columns');
            $columns = parent::getColumns();
            $showColumns = array();
            foreach ($columns as $column) {
                $options = $this->findColumnCustomOptions($column->getId());
                if ($options && is_array($options)) {
                    $column->setCustomHeader($options['custom_header']);
                    $column->setCustomVisible($options['checked']);
                } else {
                    $column->setCustomHeader($column->getHeader());
                    $column->setCustomVisible(true);
                }

                if ($column->getCustomVisible()) {
                    $column->setHeader($column->getCustomHeader());
                    $showColumns[] = $column;
                }
            }
            Varien_Profiler::stop('aw::advancedreports::grid::retrives_columns');
            $this->setData('fetched_custom_columns', $showColumns);
        }
        return $this->getData('fetched_custom_columns');
    }

    /**
     * Retrieves "Custom Columns" enabled flag
     *
     * @return boolean
     */
    public function getCustomColumnConfigEnabled()
    {
        return $this->_columnConfigEnabled;
    }

    public function getSetupColumns()
    {
        $this->_prepareColumns();

        $columns = parent::getColumns();
        foreach ($columns as $column) {
            $options = $this->findColumnCustomOptions($column->getId());
            if ($options && is_array($options)) {
                $column->setCustomHeader($options['custom_header']);
                $column->setCustomVisible($options['checked']);
            } else {
                $column->setCustomHeader($column->getHeader());
                $column->setCustomVisible(true);
            }
        }
        return $columns;
    }

    /**
     * Retrieves options required for report
     *
     * @return array
     */
    public function getCustomOptionsRequired()
    {
        $arr = array();
        if ($this->_columnConfigEnabled) {
            $arr[] = array(
                'id'            => 'custom_columns',
                'default'       => serialize(array()),
                'prepare_value' => 'json2serialize',
                'hidden'        => true,
            );
        }
        return $arr;
    }

    /**
     * Retrieves filter semafor for Row of Data
     *
     * @param Varien_Object $row
     *
     * @return boolean
     */
    protected function _filterPass($row)
    {
        $result = true;
        if (!$this->getFilterVisibility() || !count($this->getColumns())) {
            return $result;
        }

        foreach ($this->getColumns() as $column) {
            if ((!$filter = $this->getFilter($column->getId())) || !array_key_exists($column->getId(), $row) || !isset($row[$column->getId()])) {
                continue;
            }

            $rowValue = $row[$column->getId()];
            switch ($column->getType()) {
                case 'text': case 'country':
                    # Filter by string
                    if (strpos(strtolower($rowValue), strtolower($filter)) === false) {
                        $result = false;
                    }
                break;
                case 'datetime': case 'country':
                    # Filter by Datetime
                    if (is_array($filter)) {
                        if (isset($filter['from'])) {
                            $date = new Zend_Date($filter['from'], Zend_Date::DATE_SHORT, $filter['locale']);
                            if ($rowValue <= date('Y-m-d 00:00:00', $date->getTimestamp())) {
                                $result = false;
                            }
                        }
                        if (isset($filter['to'])) {
                            $date = new Zend_Date($filter['to'], Zend_Date::DATE_SHORT, $filter['locale']);
                            if ($rowValue >= date('Y-m-d 23:59:59', $date->getTimestamp())) {
                                $result = false;
                            }
                        }
                    }
                break;
                case 'number': case 'currency':
                    # filter by Number and Currency
                    if (is_array($filter)) {
                        if (isset($filter['from'])) {
                            if ($rowValue < $filter['from']) {
                                $result = false;
                            }
                        }
                        if (isset($filter['to'])) {
                            if ($rowValue > $filter['to']) {
                                $result = false;
                            }
                        }
                    }
                break;
                case 'store': case 'select':
                    # filter by Number and Currency
                    if (!is_array($rowValue)) {
                        $rowValue = explode(",", $rowValue);
                    }
                    if (!in_array($filter, $rowValue)) {
                        $result = false;
                    }
                break;
            }
        }
        return $result;
    }

    /**
     * Retrieves refresh button html
     *
     * @return string
     */
    public function getRefreshButtonHtml()
    {
        $refreshButton = $this->getChild('refresh_button');
        if (!$refreshButton) {
            $refreshButton = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                        'label'     => Mage::helper('adminhtml')->__('Refresh'),
                        'onclick'   => $this->getRefreshButtonCallback(),
                        'class'   => 'task'
                    ));
        }
        $refreshButton->setLabel(Mage::helper('advancedreports')->__('Apply'));

        return $refreshButton->toHtml();
    }


    /**
     * Retrieves report view session key
     *
     * @return string
     */
    public function getViewKey()
    {
        if (!$this->getData('stored_view_key')) {
            if (Mage::app()->getRequest()->getParam('view_key')) {
                $viewKey = Mage::app()->getRequest()->getParam('view_key');
            } else {
                $viewKey = Mage::helper('advancedreports')->getView()->getNewKey($this->getId());
            }
            $this->setData('stored_view_key', $viewKey);
        }
        return $this->getData('stored_view_key');
    }

    public function setId($value)
    {
        parent::setId($value);
        $this->setData('id', $value);
        Mage::helper('advancedreports')->getView()->setCurrentReportId($value, $this->getViewKey());
        Mage::register(AW_Advancedreports_Helper_Setup::DATA_KEY_REPORT_ID, $value, true);
        return $this;
    }

    /**
     * Format datetime with timezone offset from string to Mysql format
     *
     * @param string $date
     *
     * @return Datetime
     */
    public function _getMysqlFromFormat($date)
    {
        $dateFrom = new Zend_Date(Mage::helper('advancedreports/date')->toTimestamp($date), null, Mage::app()->getLocale()->getLocaleCode());
        $dateFrom->setHour(0)->setMinute(0)->setSecond(0);

        $timeZone = Mage::app()->getStore()->getConfig('general/locale/timezone');
        $offset = Mage::getModel('core/date')->calculateOffset($timeZone);
        $dateFrom->addTimestamp(-$offset);
        return $dateFrom->toString('yyyy-MM-dd HH:mm:ss');
    }

    /**
     * Format datetime with timezone offset from string to Mysql format
     *
     * @param string $date
     *
     * @return Datetime
     */
    public function _getMysqlToFormat($date)
    {
        $dateTo = new Zend_Date(Mage::helper('advancedreports/date')->toTimestamp($date), null, Mage::app()->getLocale()->getLocaleCode());
        $dateTo->setHour(23)->setMinute(59)->setSecond(59);

        $timeZone = Mage::app()->getStore()->getConfig('general/locale/timezone');
        $offset = Mage::getModel('core/date')->calculateOffset($timeZone);
        $dateTo->addTimestamp(-$offset);
        return $dateTo->toString('yyyy-MM-dd HH:mm:ss');
    }

    /**
     * Format datetime without timezone offset from string to Mysql format
     *
     * @param string $date
     *
     * @return Datetime
     */
    public function _getUnshiftedMysqlFromFormat($date)
    {
        $dateFrom = new Zend_Date(Mage::helper('advancedreports/date')->toTimestamp($date), null, Mage::app()->getLocale()->getLocaleCode());
        return $dateFrom->toString('yyyy-MM-dd 00:00:00');
    }

    /**
     * Format datetime without timezone offset from string to Mysql format
     *
     * @param string $date
     *
     * @return Datetime
     */
    public function _getUnshiftedMysqlToFormat($date)
    {
        $dateTo = new Zend_Date(Mage::helper('advancedreports/date')->toTimestamp($date), null, Mage::app()->getLocale()->getLocaleCode());
        return $dateTo->toString('yyyy-MM-dd 23:59:59');
    }

    /**
     * Prepare abstract collection
     *  - Set date filters
     *  - Set State filter
     *  - set Store filter
     *
     * Notice: Use if your colleciton is instance of AW_Advancedreports_Model_Mysql4_Collection_Abstract
     *
     * @return AW_Advancedreports_Block_Advanced_Grid
     */
    public function _prepareAbstractCollection()
    {
        $dateFrom = $this->_getMysqlFromFormat($this->getFilter('report_from'));
        $dateTo = $this->_getMysqlToFormat($this->getFilter('report_to'));
        $this->getCollection()->setDateFilter($dateFrom, $dateTo);
        $this->getCollection()->setState();

        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $this->getCollection()->setStoreFilter($storeIds);
        }
        return $this;
    }

    /**
     * Retrieves store ids for filter a collection
     *
     * @return array
     */
    public function getStoreIds()
    {
        if (!$this->_storeIds) {
            $storeIds = array();
            if ($this->getRequest()->getParam('store')) {
                $storeIds = array($this->getParam('store'));
            } else {
                if ($this->getRequest()->getParam('website')) {
                    $storeIds = Mage::app()->getWebsite($this->getRequest()->getParam('website'))->getStoreIds();
                } else {
                    if ($this->getRequest()->getParam('group')) {
                        $storeIds = Mage::app()->getGroup($this->getRequest()->getParam('group'))->getStoreIds();
                    }
                }
            }
            $this->_storeIds = $storeIds;
        }
        return $this->_storeIds;
    }

    /**
     * Retrieves filter string
     *
     * @deprecated
     * @return string
     */
    protected function _getProcessStates()
    {
        $states = explode(",", Mage::helper('advancedreports')->confProcessOrders());
        $isFirst = true;
        $filter = "";
        foreach ($states as $state) {
            if (!$isFirst) {
                $filter .= " OR ";
            }
            $filter .= "val.value = '" . $state . "'";
            $isFirst = false;
        }
        return "(" . $filter . ")";
    }

    /**
     * Prepare filter query
     *
     * @param array $data Associative array
     *
     * @return string
     */
    protected function _createQuery($data = array())
    {
        return http_build_query($data);
    }

    /**
     * Prepare filters before
     *
     * @return AW_Advancedreports_Block_Advanced_Grid
     */
    protected function _prepareFiltersBefore()
    {
        $filterStr = base64_decode($this->getRequest()->getParam($this->getVarNameFilter()));
        $filterStr = str_replace("%26", "XXXDUMMYAMPERSANDXXX", $filterStr);
        # Preapre default date range
        parse_str($filterStr, $data);
        if (!isset($data['custom_date_range'])) {
            if ($this->getFilter('custom_date_range')) {
                $data['custom_date_range'] = $this->getFilter('custom_date_range');
            } else {
                $data['custom_date_range'] = Mage::helper('advancedreports')->getDefaultCustomDateRange();
            }
        }
        if (!isset($data['report_from'])) {
            if ($this->getFilter('report_from')) {
                $data['report_from'] = $this->getFilter('report_from');
            } else {
                $date = new Zend_Date(strtotime(date('m/01/y')),null, $this->getLocale()->getLocaleCode());
                $data['report_from'] = $date->toString(AW_Advancedreports_Helper_Data::FRONTEND_ZEND_DATE_FORMAT);
            }
        }
        if (!isset($data['report_to'])) {
            if ($this->getFilter('report_to')) {
                $data['report_to'] = $this->getFilter('report_to');
            } else {
                $date = new Zend_Date(null, null, $this->getLocale()->getLocaleCode());
                $data['report_to'] = $date->toString(AW_Advancedreports_Helper_Data::FRONTEND_ZEND_DATE_FORMAT);
            }
        }
        if (!isset($data['report_locale_code'])) {
            if ($this->getFilter('report_locale_code')) {
                $data['report_locale_code'] = $this->getFilter('report_locale_code');
            } else {
                $data['report_locale_code'] = $this->getLocale()->getLocaleCode();
            }
        }
        if (!isset($data['reload_key'])) {
            if ($this->getFilter('reload_key')) {
                $data['reload_key'] = $this->getFilter('reload_key');
            } else {
                $params = Mage::helper('advancedreports')->getReloadKeys();
                $data['reload_key'] = $params[0]['value'];
            }
        }
        if (!isset($data['report_period'])) {
            if ($this->getFilter('report_period')) {
                $data['report_period'] = $this->getFilter('report_period');
            } else {
                foreach (Mage::helper('advancedreports')->getReportPeriods() as $key => $value) {
                    $data['report_period'] = $key;
                    break;
                }
            }
        }
        if (!isset($data['param_selector'])) {
            if ($this->getFilter('param_selector')) {
                $data['param_selector'] = $this->getFilter('param_selector');
            } else {
                $params = $this->getChartParams();
                $data['param_selector'] = $params[0]['value'];
            }
        }

        if ($this->getLocale()->getLocaleCode() !== $data['report_locale_code']) {
            $data['report_locale_code'] = $this->getLocale()->getLocaleCode();
        }
        $filterStr = $this->_createQuery($data);
        # finish preporation
        $filterStr = base64_encode($filterStr);
        $this->getRequest()->setParam($this->getVarNameFilter(), $filterStr);
        return $this;
    }

    /**
     * Prepare filters after
     *
     * @return AW_Advancedreports_Block_Advanced_Grid
     */
    protected function _prepareFiltersAfter()
    {
        foreach ($this->_filters as $key => &$value) {
            $value = str_replace("XXXDUMMYAMPERSANDXXX", "&", $value);
        }
        return $this;
    }

    protected function _setUpFilters()
    {
        $filter = $this->getParam($this->getVarNameFilter(), null);

        if (is_null($filter)) {
            $filter = $this->_defaultFilter;
        }
        if (is_string($filter)) {
            $data = array();
            $filter = base64_decode($filter);

            parse_str(urldecode($filter), $data);
            if (!isset($data['report_from']) && !$this->getFilter('report_from')) {
                $date = new Zend_Date(strtotime(date('m/01/y')), null, $this->getLocale()->getLocaleCode());
                $data['report_from'] = $date->toString(AW_Advancedreports_Helper_Data::FRONTEND_ZEND_DATE_FORMAT);
            }
            if (!isset($data['report_to']) && !$this->getFilter('report_to')) {
                $date = new Zend_Date(null, null, $this->getLocale()->getLocaleCode());
                $data['report_to'] = $date->toString(AW_Advancedreports_Helper_Data::FRONTEND_ZEND_DATE_FORMAT);
            }
            $this->_setFilterValues($data);
        } else {
            if ($filter && is_array($filter)) {
                $this->_setFilterValues($filter);
            } else {
                if (0 !== sizeof($this->_defaultFilter)) {
                    $this->_setFilterValues($this->_defaultFilter);
                }
            }
        }
        return $this;
    }

    /**
     * Prepare collection to use
     *
     * @return AW_Advancedreports_Block_Advanced_Grid
     */
    protected function _prepareCollection()
    {
        $this->_setUpReportKey();
        $this->_prepareFiltersBefore();
        $this->_setUpFilters();

        $this->setCollection(Mage::getModel('advancedreports/order')->getCollection());
        $dateFrom = $this->_getMysqlFromFormat($this->getFilter('report_from'));
        $dateTo = $this->_getMysqlToFormat($this->getFilter('report_to'));

        $this->setDateFilter($dateFrom, $dateTo)->setState();
        $storeIds = $this->getStoreIds();
        if ($this->getRequest()->getParam('store')) {
            $storeIds = array($this->getParam('store'));
        } else {
            if ($this->getRequest()->getParam('website')) {
                $storeIds = Mage::app()->getWebsite($this->getRequest()->getParam('website'))->getStoreIds();
            } else {
                if ($this->getRequest()->getParam('group')) {
                    $storeIds = Mage::app()->getGroup($this->getRequest()->getParam('group'))->getStoreIds();
                }
            }
        }

        if (count($storeIds)) {
            $this->getCollection()->setStoreIds($storeIds);
        }
        $this->_prepareFiltersAfter();
        $this->_saveFilters();
        $this->_setColumnFilters();

        return $this;
    }

    /**
     * Collection
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Abstract
     */
    public function getCollection()
    {
        return $this->_collection;
    }

    public function getFilter($name)
    {
        if (array_key_exists($name, $this->_filterValues) && $this->_filterValues[$name]) {
            return $this->_filterValues[$name];
        }
        if (!Mage::helper('advancedreports/queue')->confCrossreportFilters() || $this->getParentFilters()) {
            $this->_filterValues[$name] = parent::getFilter($name);
            return $this->_filterValues[$name];
        }
        if (Mage::helper('advancedreports/queue')->getLastFilter($name)
            && Mage::helper('advancedreports/view')->isReportChanged(
                $this->getId(), $this->getViewKey()
            )
        ) {
            $this->_filterValues[$name] = Mage::helper('advancedreports/queue')->getLastFilter($name);
        } else {
            $this->_filterValues[$name] = parent::getFilter($name);
        }
        return $this->_filterValues[$name];
    }

    protected function _saveFilters()
    {
        $filterValues = array();
        foreach ($this->_filtersToSave as $filterKey) {
            if ($this->getFilter($filterKey)) {
                $filterValues[$filterKey] = $this->getFilter($filterKey);
            }
        }
        Mage::helper('advancedreports/queue')->saveLastFilters($filterValues);
        return $filterValues;
    }

    protected function _setUpReportKey()
    {
        Mage::register(AW_Advancedreports_Helper_Setup::DATA_KEY_REPORT_ID, $this->getId(), true);
        Mage::register(AW_Advancedreports_Block_Adminhtml_Setup::DATA_KEY_REPORT_ROUTE, $this->getRoute(), true);
        Mage::register(AW_Advancedreports_Block_Adminhtml_Setup::DATA_KEY_REPORT_NAME, $this->getRequest()->getParam('name'), true);
        return $this;
    }

    /**
     * Prepare collection to use by older method of prepare
     *
     * @return AW_Advancedreports_Block_Advanced_Grid
     */
    protected function _prepareOlderCollection()
    {
        $collection = Mage::getResourceModel('advancedreports/report_collection');
        $this->_setUpReportKey();
        $this->_prepareFiltersBefore();
        $this->_setUpFilters();
        $this->setCollection($collection);
        $this->getCollection()->initReport('reports/product_ordered_collection');

        $dateFrom = $this->_getUnshiftedMysqlFromFormat($this->getFilter('report_from'));
        $dateTo = $this->_getUnshiftedMysqlToFormat($this->getFilter('report_to'));

        $this->getCollection()->setInterval($dateFrom, $dateTo);
        $this->getCollection()->setPeriod($this->getFilter('report_period'));

        if ($sort = $this->_getSort()) {
            $this->getCollection()->addOrder($sort, $this->_getDir());
            $sortColumn = $this->getColumn($sort);
            if ($sortColumn) {
                $sortColumn->setDir($this->_getDir());
            }
        }
        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $this->getCollection()->setStoreIds($storeIds);
        }
        $this->_prepareFiltersAfter();
        $this->_saveFilters();
        $this->_setColumnFilters();

        return $this;
    }

    /**
     * Retrieves chart params to build line chart
     *
     * @return array
     */
    public function getChartParams()
    {
        return Mage::helper('advancedreports')->getChartParams($this->_routeOption);
    }

    /**
     * Retrieves flag to show chart
     *
     * @return boolean
     */
    public function hasRecords()
    {
        return (count($this->_customData) > 1)
            && Mage::helper('advancedreports')->getChartParams($this->_routeOption)
            && count(Mage::helper('advancedreports')->getChartParams($this->_routeOption))
        ;
    }

    public function getShowCustomGrid()
    {
        return true;
    }

    public function getHideNativeGrid()
    {
        return true;
    }

    public function getHideShowBy()
    {
        return true;
    }

    /**
     * Set up sort direction
     *
     * @param string $id
     * @param string $dir
     */
    protected function _setColumnDir($id, $dir)
    {
        if (count($this->_columns)) {
            foreach ($this->_columns as $column) {
                if ($column->getId() == $id) {
                    $column->setDir($dir);
                    $this->_sortBy = $id;
                    return;
                }
            }
        }
    }

    /**
     * Retrieves sort key
     *
     * @return string
     */
    protected function _getSort()
    {
        return $this->getRequest()->getParam('sort');
    }

    /**
     * Retrieves sort direction
     *
     * @return string
     */
    protected function _getDir()
    {
        return $this->getRequest()->getParam('dir');
    }

    /**
     * Prepare data to build grid
     */
    protected function _prepareData()
    {
        if ($this->_getDir()) {
            $this->_setColumnDir($this->_getSort(), $this->_getDir());
        }
    }

    /**
     * Compare two objects for current sorting column and current direction
     *
     * @param Varien_Object $a
     * @param Varien_Object $b
     *
     * @return integer
     */
    protected function _compareVarDataElements($a, $b)
    {
        $key = "get" . Mage::helper('advancedreports')->getDataKey($this->_getSort());
        if ($a->$key() == $b->$key()) {
            return 0;
        }

        # Custom percent sortage
        if ($this->_getSort() && $this->getColumn($this->_getSort())
            && $this->getColumn($this->_getSort())->getData(
                'custom_sorting_percent'
            )
        ) {
            if ($this->_getDir() == "asc") {
                return ((int)str_replace(' %', '', $a->$key()) < (int)str_replace(' %', '', $b->$key())) ? -1 : 1;
            } else {
                return ((int)str_replace(' %', '', $a->$key()) > (int)str_replace(' %', '', $b->$key())) ? -1 : 1;
            }
            # Sorting by position (required for periods sorting)
        } elseif ($this->_getSort() && $this->getColumn($this->_getSort())
            && $this->getColumn($this->_getSort())->getData('is_position_sorting')
        ) {
            if ($this->_getDir() == "asc") {
                return ($a->getData('sort_position') < $b->getData('sort_position')) ? -1 : 1;
            } else {
                return ($a->getData('sort_position') > $b->getData('sort_position')) ? -1 : 1;
            }
        } elseif ($this->_getSort() && $this->getColumn($this->_getSort())
            && $this->getColumn($this->_getSort())->getData('is_period_sorting')
        ) {
            $locale = new Zend_Locale($this->getLocale()->getLocaleCode());
            $dateFormat = Zend_Locale_Format::getDateFormat($locale);

            if ($this->getFilter('report_period') == 'month') {
                $dateFormat = 'MM/yyyy';
            }
            if ($this->getFilter('report_period') == 'quarter') {
                $dateFormat = 'QMM/yyyy';
            }
            if ($this->getFilter('report_period') == 'year') {
                $dateFormat = 'yyyy';
            }

            $aData = new Zend_Date($a->$key(), $dateFormat, $this->getLocale()->getLocaleCode());
            $bData = new Zend_Date($b->$key(), $dateFormat, $this->getLocale()->getLocaleCode());
            if ($this->_getDir() == "asc") {
                return ($aData->getTimestamp() < $bData->getTimestamp()) ? -1 : 1;
            } else {
                return ($aData->getTimestamp() > $bData->getTimestamp()) ? -1 : 1;
            }
        } else {
            if ($this->_getDir() == "asc") {
                return ($a->$key() < $b->$key()) ? -1 : 1;
            } else {
                return ($a->$key() > $b->$key()) ? -1 : 1;
            }
        }
    }

    public function addProfitColumns()
    {
        $defValue = sprintf("%f", 0);
        $defValue = Mage::app()->getLocale()->currency($this->getCurrentCurrencyCode())->toCurrency($defValue);

        $this->addColumn(
            'total_cost',
            array(
                'header'        => Mage::helper('reports')->__('Total Cost'),
                'width'         => '120px',
                'type'          => 'currency',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'index'         => 'total_cost',
                'ddl_type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'ddl_size'      => '12,4',
                'ddl_options'   => array('nullable' => true),
                'default'       => $defValue,
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
                'ddl_type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'ddl_size'      => '12,4',
                'ddl_options'   => array('nullable' => true),
                'default'       => $defValue,
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
                'ddl_type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'ddl_size'      => '12,4',
                'ddl_options'   => array('nullable' => true),
                'default'       => $defValue,
            )
        );

        $this->addColumn(
            'total_profit',
            array(
                'header'        => Mage::helper('reports')->__('Total Profit'),
                'width'         => '120px',
                'type'          => 'currency',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'index'         => 'total_profit',
                'ddl_type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
                'ddl_size'      => '12,4',
                'ddl_options'   => array('nullable' => true),
                'default'       => $defValue,
            )
        );

        $this->addColumn(
            'total_margin',
            array(
                'header'        => Mage::helper('reports')->__('Total Margin'),
                'width'         => '120px',
                'type'          => 'number',
                'index'         => 'total_margin',
                'filter'        => false,
                'renderer'      => 'advancedreports/widget_grid_column_renderer_profit',
                'disable_total' => true,
                'ddl_type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
                'ddl_size'      => '12,0',
                'ddl_options'   => array('nullable' => true),
            )
        );

        return $this;
    }

    /**
     * Retrieves is aggregated report type
     *
     * @return boolean
     */
    public function hasAggregation()
    {
        return false;
    }

    public function getGridData()
    {
        # Aggregate data before this statement
        if ($this->hasAggregation()) {
            return $this->getCollection();
        } else {
            return $this->getCustomVarData();
        }
    }

    /**
     * Retrieves Custom Data array with Varien_Object converted data rows
     *
     * @return array
     */
    public function getCustomVarData()
    {
        if ($this->_customVarData) {
            $offset = ($this->getCollection()->getCurPage() - 1) * $this->getCollection()->getPageSize();
            $customVarData = array_slice($this->_customVarData, $offset, $this->getCollection()->getPageSize(), true);
            return $customVarData;
        }
        $this->_prepareCustomVarData();
        $offset = ($this->getCollection()->getCurPage() - 1) * $this->getCollection()->getPageSize();
        $customVarData = array_slice($this->_customVarData, $offset, $this->getCollection()->getPageSize(), true);
        return $customVarData;
    }

    /**
     * Retrieves Custom Data array with Varien_Object converted data rows
     *
     * @return array
     */
    public function getGridDataForExport()
    {
        if ($this->hasAggregation()) {
            $collection = $this->getCollection();
            $collection->setPageSize(null)->setCurPage(1);
            return $collection;
        } else {
            if ($this->_customVarData) {
                return $this->_customVarData;
            }
            $this->_prepareCustomVarData();
            return $this->_customVarData;
        }
    }

    protected function _prepareCustomVarData()
    {
        $this->_customVarData = array();
        foreach ($this->_customData as $d) {
            $obj = new Varien_Object();
            $obj->setData($d);
            $this->_customVarData[] = $obj;
        }
        if (!$this->hasAggregation()) {
            if ($this->_customVarData && is_array($this->_customVarData) && $this->_getSort() && $this->_getDir()) {
                @usort($this->_customVarData, array(&$this, "_compareVarDataElements"));
            }
        }
        return $this;
    }
    /**
     * Retrieves empty Periods array
     *
     * @return array
     */
    public function getPeriods()
    {
        return array();
    }

    /**
     * Retrieves old version of getPeriods()
     *
     * @return array
     */
    protected function _getOlderPeriods()
    {
        return parent::getPeriods();
    }

    /**
     * Retrieves Grand Totals array
     *
     * @return array
     */
    public function getGrandTotals()
    {
        if (!$this->_grandTotals) {
            $this->_grandTotals = new Varien_Object();
            if (count($this->_customData) || $this->hasAggregation()) {
                foreach ($this->_columns as $column) {
                    if (($column->getType() == "currency" || $column->getType() == "number")
                        && !$column->getDisableTotal()
                    ) {
                        $sum = 0;
                        if ($this->hasAggregation()) {
                            $sum = $this->getCollection()->getTotal($column->getindex());
                        } else {
                            foreach ($this->_customData as $data) {
                                if (isset($data[$column->getIndex()])) {
                                    $sum += $data[$column->getIndex()];
                                }
                            }
                        }
                        $this->_grandTotals->setData($column->getIndex(), $sum);
                    }
                }
            }
        }
        return $this->_grandTotals;
    }

    /**
     * Retrieves count of totals
     *
     * @return int
     */
    public function getCountTotals()
    {
        $count = 0;
        foreach ($this->_columns as $column) {
            if (($column->getType() == "currency" || $column->getType() == "number") && !$column->getDisableTotal()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Retrieves Excel file content
     *
     * @param string $filename
     *
     * @return string
     */
    public function getExcel($filename = '')
    {
        $this->_prepareGrid();

        $data = array();
        foreach ($this->getColumns() as $column) {
            if (!$column->getIsSystem() && $column->getIndex() != 'stores' && $column->getType() != 'action') {
                $row[] = $column->getHeader();
            }
        }
        $data[] = $row;
        $gridData = $this->getGridDataForExport();
        if (count($gridData)) {
            foreach ($gridData as $obj) {
                $row = array();
                foreach ($this->getColumns() as $column) {
                    if (!$column->getIsSystem() && $column->getIndex() != 'stores' && $column->getType() != 'action') {
                        $row[] = $column->getRowFieldExport($obj);
                    }
                }
                $data[] = $row;
            }
            if ($this->getNeedTotal() && $this->getCountTotals()) {
                $_isFirst = true;
                $row = array();
                foreach ($this->getColumns() as $_column) {
                    if ($_isFirst) {
                        $row[] = $this->getTotalText();
                    } elseif ($_column->getType() == "action" || $_column->getDisableTotal()) {
                        $row[] = "";
                    } else {
                        $row[] = $_column->getRowFieldExport($this->getGrandTotals());
                    }
                    $_isFirst = false;
                }
                $data[] = $row;
            }
        }
        $xmlObj = new Varien_Convert_Parser_Xml_Excel();
        $xmlObj->setVar('single_sheet', $filename);
        $xmlObj->setData($data);
        $xmlObj->unparse();
        return $xmlObj->getData();
    }

    /**
     * Retrieves CSV file content
     *
     * @param string $filename
     * @return string
     */
    public function getCsv($filename = '')
    {
        $csv = '';
        $this->_prepareGrid();
        foreach ($this->getColumns() as $column) {
            if (!$column->getIsSystem() && $column->getIndex() != 'stores' && $column->getType() != 'action') {
                $data[] = '"' . $column->getHeader() . '"';
            }
        }
        $csv .= implode(',', $data) . "\n";

        $gridData = $this->getGridDataForExport();
        if (!count($gridData)) {
            return $csv;
        }
        foreach ($gridData as $obj) {
            $data = array();
            foreach ($this->getColumns() as $column) {
                if (!$column->getIsSystem() && $column->getIndex() != 'stores' && $column->getType() != 'action') {
                    $data[]
                        = '"' . str_replace(array('"', '\\'), array('""', '\\\\'), $column->getRowFieldExport($obj)) . '"';
                }
            }
            $csv .= implode(',', $data) . "\n";
        }

        if ($this->getNeedTotal() && $this->getCountTotals()) {
            $_isFirst = true;
            $data = array();
            foreach ($this->getColumns() as $_column) {
                if ($_isFirst) {
                    $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'), $this->getTotalText()) . '"';
                } elseif ($_column->getType() == "action" || $_column->getDisableTotal()) {
                    $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'), "") . '"';
                } else {
                    $data[] = '"' . str_replace(
                        array('"', '\\'), array('""', '\\\\'), $_column->getRowFieldExport($this->getGrandTotals())
                    ) . '"';
                }
                $_isFirst = false;
            }
            $csv .= implode(',', $data) . "\n";
        }
        return $csv;
    }

    protected function _getExportTotals()
    {
        $row    = array();
        foreach ($this->_columns as $column) {
            if ($column->getIsSystem()) {
                continue;
            }
            if ($column->getType() == "action" || $column->getDisableTotal()) {
                $row[] = '';
                continue;
            }
            $row[] = $column->getRowFieldExport($this->getGrandTotals());
        }
        return $row;
    }
}