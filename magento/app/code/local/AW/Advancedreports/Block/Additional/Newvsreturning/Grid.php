<?php
/**
 * New vs Returning Customers Report Grid
 */
class AW_Advancedreports_Block_Additional_Newvsreturning_Grid extends AW_Advancedreports_Block_Additional_Grid
{
    /**
     * Route to access in session to chart params
     *
     * @var string
     */
    protected $_routeOption = AW_Advancedreports_Helper_Additional_Newvsreturning::ROUTE_ADDITIONAL_NEWVSRETURNING;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(Mage::helper('advancedreports')->getGridTemplate());
        $this->setExportVisibility(true);
        $this->setStoreSwitcherVisibility(true);
        $this->setId('gridAdditionalNewvsreturning');
    }

    /**
     * Flag to hide Show By block
     *
     * @return boolean
     */
    public function getHideShowBy()
    {
        return false;
    }

    /**
     * Prepare collection to use in grid
     *
     * @return AW_Advancedreports_Block_Additional_Newvsreturning_Grid
     */
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
     * Retrives report data for period
     *
     * @param string $from
     * @param string $to
     *
     * @return Varien_Object
     */
    protected function _getInfo($from, $to)
    {
        $collection = $this->_getOrdersCollection($from, $to);
        $result = array();
        $customersEmails = $collection->getColumnValues('customer_email');
        $oldCustomers = array();
        $oldCustomersArray = $collection->getOldCustomers($from, $customersEmails);

        foreach ($oldCustomersArray as $value) {
            $oldCustomers[$value['customer_email']] = true;
        }

        $timeZone = Mage::app()->getStore()->getConfig('general/locale/timezone');
        $_newCustomers = array();

        $pageNum = 1;
        $pageSize = 3000;
        $read = Mage::helper('advancedreports')->getReadAdapter();
        while (true) {
            $select = clone $collection->getSelect();
            $select->limitPage($pageNum, $pageSize);
            $items = $read->fetchAll($select->__toString());
            if (count($items) == 0) {
                break;
            }
            foreach ($items as $item) {
                $orderDate = new Zend_Date($item['date'], 'yyyy-MM-dd HH:mm', $this->getLocale()->getLocaleCode());
                $orderDate->setTimezone($timeZone);
                if (!array_key_exists($item['customer_email'], $oldCustomers)
                    && !array_key_exists($item['customer_email'], $_newCustomers)
                ) {
                    $item['is_new'] = 1;
                    $_newCustomers[$item['customer_email']] = true;
                }
                if (array_key_exists($orderDate->toString('yyyy-MM-dd'), $result)) {
                    $periodResult = $result[$orderDate->toString('yyyy-MM-dd')];
                } else {
                    $periodResult = new Varien_Object(array('new_customers' => 0, 'returning_customers' => 0));
                }
                if (array_key_exists('is_new', $item)) {
                    $periodResult->setNewCustomers($periodResult->getNewCustomers() + 1);
                } else {
                    $periodResult->setReturningCustomers($periodResult->getReturningCustomers() + 1);
                }
                if (array_key_exists('is_new', $item) && $item['orders_count'] > 1) {
                    $periodResult->setReturningCustomers($periodResult->getReturningCustomers() + 1);
                }
                $result[$orderDate->toString('yyyy-MM-dd')] = $periodResult;
            }
            $pageNum++;
        }
        return $result;
    }

    /**
     * Save data row to use in chart
     *
     * @param array $row
     *
     * @return AW_Advancedreports_Block_Additional_Newvsreturning_Grid
     */
    protected function _addCustomData($row)
    {
        $this->_customData[] = $row;
        return $this;
    }

    /**
     * Retrives collection with orders count
     *
     * @param string|datetime $from
     * @param string|datetime $to
     *
     * @return AW_Advancedreports_Model_Mysql4_Collection_Additional_Newvsreturning
     */
    protected function _getOrdersCollection($from, $to)
    {
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Additional_Newvsreturning $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_additional_newvsreturning');
        $collection->reInitOrdersCollection();
        $collection->setDateFilter($from, $to);

        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $collection->setStoreFilter($storeIds);
        }
        return $collection;
    }


    /**
     * Retrives is day in period
     *
     * @param string|datetime $from
     * @param string|datetime $to
     * @param string|datetime $day
     *
     * @return boolean
     */
    protected function _is_day_in_period($from, $to, $day)
    {
        return ($from <= $day && $day <= $to);
    }


    protected function _prepareData()
    {
        # primary analise
        $dateFrom = $this->_getMysqlFromFormat($this->getFilter('report_from'));
        $dateTo = $this->_getMysqlToFormat($this->getFilter('report_to'));
        $resultArrayCollection = $this->_getInfo($dateFrom, $dateTo);

        $timeZone = Mage::app()->getStore()->getConfig('general/locale/timezone');
        $offset = Mage::getModel('core/date')->calculateOffset($timeZone);
        foreach ($this->getCollection()->getIntervals() as $_item) {
            $dateStart = new Zend_Date($_item['start'], 'yyyy-MM-dd HH:mm:ss', $this->getLocale()->getLocaleCode());
            $dateEnd = new Zend_Date($_item['end'], 'yyyy-MM-dd HH:mm:ss', $this->getLocale()->getLocaleCode());
            $row = new Varien_Object(
                array(
                     'new_customers'       => 0,
                     'returning_customers' => 0,
                     'period'              => $_item['title']
                )
            );
            $dateStart->subTimestamp(-$offset);
            $dateEnd->subTimestamp(-$offset);

            while ($dateStart->compare($dateEnd) < 1) {
                if (array_key_exists($dateStart->toString('yyyy-MM-dd'), $resultArrayCollection)) {
                    $resultObject = $resultArrayCollection[$dateStart->toString('yyyy-MM-dd')];
                    $row->setNewCustomers(
                        $row->getNewCustomers() + $resultObject->getNewCustomers()
                    );
                    $row->setReturningCustomers(
                        $row->getReturningCustomers() + $resultObject->getReturningCustomers()
                    );
                }
                $dateStart->addDay(1);
            }

            $this->_addCustomData($row->getData());
        }

        $chartLabels = array('new_customers'       => $this->__('New Customers'),
                             'returning_customers' => $this->__('Returning Customers'));
        $keys = array();
        foreach ($chartLabels as $key => $value) {
            $keys[] = $key;
        }

        foreach ($this->_customData as &$d) {
            $total = $d['new_customers'] + $d['returning_customers'];
            if ($total > 0) {
                $d['percent_of_returning'] = round($d['returning_customers'] * 100 / $total, 1);
                $d['percent_of_returning_data'] = round($d['returning_customers'] * 100 / $total, 1);
            } else {
                $d['percent_of_returning'] = 0;
                $d['percent_of_returning_data'] = 0;
            }

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
            'new_customers',
            array(
                 'header' => $this->__('New Customers'),
                 'index'  => 'new_customers',
                 'type'   => 'number',
                 'width'  => '80px',
            )
        );

        $this->addColumn(
            'returning_customers',
            array(
                 'header' => $this->__('Returning Customers'),
                 'index'  => 'returning_customers',
                 'type'   => 'number',
                 'width'  => '80px',
            )
        );

        $this->addColumn(
            'percent_of_returning',
            array(
                'header'        => $this->__('Percent of Returning'),
                'width'         => '80px',
                'align'         => 'right',
                'index'         => 'percent_of_returning',
                'type'          => 'number',
                'renderer'      => 'advancedreports/widget_grid_column_renderer_returningPercent',
                'disable_total' => true,
            )
        );

        $this->addExportType('*/*/exportOrderedCsv/name/' . $this->_getName(), $this->__('CSV'));
        $this->addExportType('*/*/exportOrderedExcel/name/' . $this->_getName(), $this->__('Excel'));

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