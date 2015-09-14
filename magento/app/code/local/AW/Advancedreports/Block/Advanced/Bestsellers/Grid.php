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
 * Bestsellers Report Grid
 */
class AW_Advancedreports_Block_Advanced_Bestsellers_Grid extends AW_Advancedreports_Block_Advanced_Grid
{
    const OPTION_BESTSELLER_GROUPED_SKU = 'advancedreports_bestsellers_options_skutype';

    protected $_routeOption = AW_Advancedreports_Helper_Data::ROUTE_ADVANCED_BESTSELLERS;
    protected $_bestsellerVarData;

    public function __construct()
    {
        parent::__construct();
        $this->setFilterVisibility(true);
        $this->setId('gridBestsellers');

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

    protected function _prepareCollection()
    {
        parent::_prepareCollection();
        $this->prepareReportCollection();
        $this->_preparePage();

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
        /** @var AW_Advancedreports_Model_Mysql4_Collection_Bestsellers $collection */
        $collection = Mage::getResourceModel('advancedreports/collection_bestsellers')->reInitSelect();

        $this->setCollection($collection);
        $this->addOrderItems($this->getCustomOption('advancedreports_bestsellers_options_bestsellers_count'), $from, $to);

        $key = $this->getFilter('reload_key');
        if ($key === 'qty') {
            $this->setDefaultPercentField('sum_qty');
            $this->getCollection()->orderByQty();
        } elseif ($key === 'total') {
            $this->setDefaultPercentField('sum_total');
            $this->getCollection()->orderByTotal();
        }
        $this->getCollection()->setDateFilter($from, $to)->setState();

        $storeIds = $this->getStoreIds();
        if (count($storeIds)) {
            $this->getCollection()->setStoreFilter($storeIds);
        }

        $this->getCollection()->addProfitInfo($from, $to);

        return $this->getCollection();
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

        $this->getAggregator()->prepareAggregatedCollection($dateFrom, $dateTo)->addOrderColumn();

        /** @var AW_Advancedreports_Model_Mysql4_Cache_Collection $collection */
        $collection = $this->getAggregator()->getAggregatetCollection();
        $this->setCollection($collection);

        if ($sort = $this->_getSort()) {
            $collection->addOrder($sort, $this->_getDir());
            $this->getColumn($sort)->setDir($this->_getDir());
        }

        $this->_saveFilters();
        $this->_setColumnFilters();

        return $this;
    }


    /**
     * Retrieves initialization array for custom report option
     *
     * @return array
     */
    public function getCustomOptionsRequired()
    {
        $array = parent::getCustomOptionsRequired();
        $skutypes = Mage::getSingleton('advancedreports/system_config_source_skutype')->toOptionArray();
        $addArray = array(
            array(
                'id'      => 'advancedreports_bestsellers_options_bestsellers_count',
                'type'    => 'text',
                'args'    => array(
                    'label'    => $this->__('Products to show'),
                    'title'    => $this->__('Products to show'),
                    'name'     => 'advancedreports_bestsellers_options_bestsellers_count',
                    'class'    => '',
                    'required' => true,
                ),
                'default' => '10',
            ),
            array(
                'id'      => self::OPTION_BESTSELLER_GROUPED_SKU,
                'type'    => 'select',
                'args'    => array(
                    'label'    => $this->__('SKU usage'),
                    'title'    => $this->__('SKU usage'),
                    'name'     => self::OPTION_BESTSELLER_GROUPED_SKU,
                    'class'    => '',
                    'required' => true,
                    'values'   => $skutypes,
                ),
                'default' => AW_Advancedreports_Model_System_Config_Source_Skutype::SKUTYPE_SIMPLE,
            ),
        );
        return array_merge($array, $addArray);
    }

    /**
     * Filter collection by Store Ids
     *
     * @param array $storeIds
     *
     * @return AW_Advancedreports_Block_Advanced_Bestsellers_Grid
     */
    public function setStoreFilter($storeIds)
    {
        $this->getCollection()->setStoreFilter($storeIds);
        return $this;
    }

    public function addOrderItems($limit = 10, $dateFrom, $dateTo)
    {
        $skuType = $this->getCustomOption(self::OPTION_BESTSELLER_GROUPED_SKU);

        $this->getCollection()->addOrderItems($limit, $dateFrom, $dateTo, $skuType);
        return $this;
    }

    public function getNeedReload()
    {
        return Mage::helper('advancedreports')->getNeedReload($this->_routeOption);
    }

    /*
     * Need to sort bestsellers array
     */
    protected function _compareTotalElements($a, $b)
    {
        if ($a['sum_total'] == $b['sum_total']) {
            return 0;
        }
        return ($a['sum_total'] > $b['sum_total']) ? -1 : 1;
    }

    /*
    * Need to sort bestsellers array
    */
    protected function _compareQtyElements($a, $b)
    {
        if ($a['sum_qty'] == $b['sum_qty']) {
            return 0;
        }
        return ($a['sum_qty'] > $b['sum_qty']) ? -1 : 1;
    }

    /*
     * Prepare data array for Pie and Grid
     */
    protected function _prepareData()
    {
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'sort_order',
            array(
                 'header'   => Mage::helper('reports')->__('N'),
                 'width'    => '60px',
                 'align'    => 'right',
                 'index'    => 'sort_order',
                 'type'     => 'number',
                 'disable_total' => true,
            )
        );

        $this->addColumn(
            'sku',
            array(
                 'header'   => Mage::helper('reports')->__('SKU'),
                 'index'    => 'sku',
                 'type'     => 'text',
            )
        );

        $this->addColumn(
            'name',
            array(
                'header'   => Mage::helper('reports')->__('Product Name'),
                'index'    => 'name',
                'type'     => 'text',
            )
        );

        $this->addColumn(
            'sum_qty',
            array(
                'header'   => $this->__('Quantity'),
                'width'    => '120px',
                'align'    => 'right',
                'index'    => 'sum_qty',
                'renderer' => 'advancedreports/widget_grid_column_renderer_percent',
                'total'    => 'sum',
                'type'     => 'number',
            )
        );

        $this->addColumn(
            'sum_total',
            array(
                'header'        => Mage::helper('reports')->__('Total'),
                'width'         => '120px',
                'type'          => 'currency',
                'currency_code' => $this->getCurrentCurrencyCode(),
                'renderer'      => 'advancedreports/widget_grid_column_renderer_percent',
                'total'         => 'sum',
                'index'         => 'sum_total',
            )
        );

        $this->addProfitColumns();

        $this->addColumn(
            'action',
            array(
                'header'   => Mage::helper('catalog')->__('Action'),
                'width'    => '50px',
                'type'     => 'action',
                'align'    => 'right',
                'getter'   => 'getProductId',
                'actions'  => array(
                    array(
                        'caption' => $this->__('View'),
                        'url'     => array(
                            'base'   => 'adminhtml/catalog_product/edit',
                            'params' => array(),
                        ),
                        'field'   => 'id',
                    )
                ),
                'filter'   => false,
                'sortable' => false,
                'index'    => 'stores',
                'is_system' => true,
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

    public function getRowUrl($row)
    {
        //return $this->getUrl('adminhtml/catalog_product/edit', array('id' => $row->getProductId() ));
    }

    public function hasAggregation()
    {
        return true;
    }
}